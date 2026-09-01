<?php
/**
 * Dynamic Professional Operations Dashboard
 * Loan Management System (loan-mgt) - Phase 7
 */

$pageTitle = 'Executive Dashboard';
$activeNav = 'dashboard';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

$currentUser = auth_user();
$db = get_db_connection();

// Refresh user record
$stmt = $db->prepare('SELECT id, name, email, phone, role, status, last_login, created_at FROM users WHERE id = :id');
$stmt->execute([':id' => $currentUser['id']]);
$userRecord = $stmt->fetch() ?: $currentUser;

$userRole = $userRecord['role'] ?? 'loan_officer';
$roleLabel = get_role_label($userRole);
$roleBadgeClass = 'badge-role-' . $userRole;
$lastLoginDisplay = !empty($userRecord['last_login']) 
    ? date('F j, Y, g:i A', strtotime($userRecord['last_login'])) 
    : 'First session recorded';

$today = date('Y-m-d');
$currentYearMonth = date('Y-m');
$sevenDaysOut = date('Y-m-d', strtotime('+7 days'));

// -------------------------------------------------------------
// 1. Core Summary Metrics (Real-Time Database Queries)
// -------------------------------------------------------------

// Customer Metrics
$totalCustomers = (int)$db->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$newCustThisMonth = (int)$db->query("SELECT COUNT(*) FROM customers WHERE DATE_FORMAT(created_at, '%Y-%m') = '{$currentYearMonth}'")->fetchColumn();

// Active Loans & Valuation
$activeLoansCount = (int)$db->query("SELECT COUNT(*) FROM loans WHERE status = 'active'")->fetchColumn();
$activePayableVal = (float)$db->query("SELECT COALESCE(SUM(estimated_total_payable), 0) FROM loans WHERE status = 'active'")->fetchColumn();

// Disbursed Capital
$totalDisbursed = (float)$db->query("SELECT COALESCE(SUM(disbursed_amount), 0) FROM loans WHERE status IN ('active', 'completed') AND disbursement_date IS NOT NULL")->fetchColumn();
$disbThisMonth = (float)$db->query("SELECT COALESCE(SUM(disbursed_amount), 0) FROM loans WHERE status IN ('active', 'completed') AND DATE_FORMAT(disbursement_date, '%Y-%m') = '{$currentYearMonth}'")->fetchColumn();

// Collections Realized
$totalCollected = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM loan_payments")->fetchColumn();
$todayCollected = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM loan_payments WHERE payment_date = '{$today}'")->fetchColumn();
$thisMonthCollected = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM loan_payments WHERE DATE_FORMAT(payment_date, '%Y-%m') = '{$currentYearMonth}'")->fetchColumn();

// Outstanding Active Portfolio Balance
$totalOutstanding = (float)$db->query("
    SELECT COALESCE(SUM(li.remaining_amount), 0) 
    FROM loan_installments li 
    JOIN loans l ON li.loan_id = l.id 
    WHERE l.status = 'active' AND li.remaining_amount > 0
")->fetchColumn();

// Overdue Delinquency
$overdueStmt = $db->query("
    SELECT COUNT(*) AS cnt, COALESCE(SUM(li.remaining_amount), 0) AS amt 
    FROM loan_installments li 
    JOIN loans l ON li.loan_id = l.id 
    WHERE l.status = 'active' AND li.due_date < '{$today}' AND li.remaining_amount > 0
");
$overdueData = $overdueStmt->fetch();
$overdueCount  = (int)($overdueData['cnt'] ?? 0);
$overdueAmount = (float)($overdueData['amt'] ?? 0.0);

// Due Today Installments
$dueTodayStmt = $db->query("
    SELECT COUNT(*) AS cnt, COALESCE(SUM(li.remaining_amount), 0) AS amt 
    FROM loan_installments li 
    JOIN loans l ON li.loan_id = l.id 
    WHERE l.status = 'active' AND li.due_date = '{$today}' AND li.remaining_amount > 0
");
$dueTodayData = $dueTodayStmt->fetch();
$dueTodayCount  = (int)($dueTodayData['cnt'] ?? 0);
$dueTodayAmount = (float)($dueTodayData['amt'] ?? 0.0);

// -------------------------------------------------------------
// 2. Portfolio Status Breakdown (Live Aggregates)
// -------------------------------------------------------------
$statusCountsStmt = $db->query("
    SELECT status,
           COUNT(*) AS total_count,
           COALESCE(SUM(requested_amount), 0) AS total_requested,
           COALESCE(SUM(disbursed_amount), 0) AS total_disbursed
    FROM loans
    GROUP BY status
");
$statusDistribution = [
    'pending'   => ['count' => 0, 'requested' => 0.0, 'disbursed' => 0.0],
    'approved'  => ['count' => 0, 'requested' => 0.0, 'disbursed' => 0.0],
    'active'    => ['count' => 0, 'requested' => 0.0, 'disbursed' => 0.0],
    'completed' => ['count' => 0, 'requested' => 0.0, 'disbursed' => 0.0],
    'draft'     => ['count' => 0, 'requested' => 0.0, 'disbursed' => 0.0],
    'rejected'  => ['count' => 0, 'requested' => 0.0, 'disbursed' => 0.0],
    'cancelled' => ['count' => 0, 'requested' => 0.0, 'disbursed' => 0.0],
];
while ($row = $statusCountsStmt->fetch()) {
    $st = $row['status'];
    if (isset($statusDistribution[$st])) {
        $statusDistribution[$st] = [
            'count'     => (int)$row['total_count'],
            'requested' => (float)$row['total_requested'],
            'disbursed' => (float)$row['total_disbursed']
        ];
    }
}
$pendingReviewCount = $statusDistribution['pending']['count'];
$approvedReadyCount = $statusDistribution['approved']['count'];

// -------------------------------------------------------------
// 3. Monthly Activity (Trailing 6 Calendar Months)
// -------------------------------------------------------------
$monthlyActivity = [];
for ($i = 5; $i >= 0; $i--) {
    $dt = new DateTime("first day of -{$i} month");
    $ym = $dt->format('Y-m');
    $label = $dt->format('M Y');
    
    $disbMonth = (float)$db->query("
        SELECT COALESCE(SUM(disbursed_amount), 0) 
        FROM loans 
        WHERE status IN ('active', 'completed') AND DATE_FORMAT(disbursement_date, '%Y-%m') = '{$ym}'
    ")->fetchColumn();

    $collMonth = (float)$db->query("
        SELECT COALESCE(SUM(amount), 0) 
        FROM loan_payments 
        WHERE DATE_FORMAT(payment_date, '%Y-%m') = '{$ym}'
    ")->fetchColumn();
    
    $monthlyActivity[] = [
        'month_key'   => $ym,
        'month_label' => $label,
        'disbursed'   => $disbMonth,
        'collected'   => $collMonth,
    ];
}

// -------------------------------------------------------------
// 4. Data Tables: Recent Loans, Recent Payments, Upcoming Due
// -------------------------------------------------------------

// Recent Loans (Latest 6)
$recentLoans = $db->query("
    SELECT l.id, l.loan_number, l.requested_amount, l.application_date, l.status,
           c.id AS customer_id, c.customer_code, c.full_name AS customer_name,
           lp.name AS product_name
    FROM loans l
    JOIN customers c ON l.customer_id = c.id
    LEFT JOIN loan_products lp ON l.loan_product_id = lp.id
    ORDER BY l.id DESC
    LIMIT 6
")->fetchAll();

// Recent Payments (Latest 6)
$recentPayments = $db->query("
    SELECT p.id, p.payment_reference, p.payment_date, p.amount, p.payment_method,
           l.id AS loan_id, l.loan_number,
           c.id AS customer_id, c.full_name AS customer_name
    FROM loan_payments p
    JOIN loans l ON p.loan_id = l.id
    JOIN customers c ON p.customer_id = c.id
    ORDER BY p.id DESC
    LIMIT 6
")->fetchAll();

// Upcoming Installments (Next 7 Days)
$upcomingInstallments = $db->query("
    SELECT li.id, li.loan_id, li.installment_number, li.due_date, li.installment_amount,
           li.paid_amount, li.remaining_amount,
           l.loan_number,
           c.id AS customer_id, c.customer_code, c.full_name AS customer_name, c.phone AS customer_phone
    FROM loan_installments li
    JOIN loans l ON li.loan_id = l.id
    JOIN customers c ON l.customer_id = c.id
    WHERE l.status = 'active' 
      AND li.due_date BETWEEN '{$today}' AND '{$sevenDaysOut}'
      AND li.remaining_amount > 0
    ORDER BY li.due_date ASC, li.id ASC
    LIMIT 6
")->fetchAll();

$canCollect = can_collect_payments();
$canDisburse = can_disburse_loans();
$canCreateLoan = can_create_loans();

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Welcome Banner -->
<div class="card mb-4 border-0 shadow-sm" style="background-color: #ffffff; border-left: 4px solid var(--primary-color) !important;">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h1 class="h4 mb-0 fw-bold text-dark">Executive Dashboard</h1>
                    <span class="badge badge-role <?php echo $roleBadgeClass; ?>"><?php echo e($roleLabel); ?></span>
                </div>
                <p class="text-muted mb-0 small">
                    Signed in as <strong><?php echo e($userRecord['name']); ?></strong> &bull; Operating on live financial ledger.
                </p>
            </div>
            <div class="text-md-end">
                <span class="small text-muted d-block">Session Authentication</span>
                <span class="fw-semibold text-dark small"><i class="bi bi-clock-history me-1 text-primary"></i> <?php echo e($lastLoginDisplay); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Role Operational Alert Priority -->
<?php if ($userRole === 'collector'): ?>
    <!-- Collector Priority Banner -->
    <div class="card shadow-sm mb-4 border-0" style="border-left: 4px solid #16a34a !important; background-color: #f8fafc;">
        <div class="card-body p-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <span class="fw-bold text-dark d-block"><i class="bi bi-calendar2-check-fill text-success me-2"></i> Collector Action Priority</span>
                <span class="small text-muted">
                    Due Today: <strong><?php echo $dueTodayCount; ?></strong> installments (<?php echo format_currency($dueTodayAmount); ?>) &bull; 
                    Overdue: <strong class="text-danger"><?php echo $overdueCount; ?></strong> installments (<?php echo format_currency($overdueAmount); ?>)
                </span>
            </div>
            <div class="d-flex gap-2">
                <a href="<?php echo url('modules/repayments/index.php?status=due_today'); ?>" class="btn btn-sm btn-outline-success">
                    View Today's Due
                </a>
                <a href="<?php echo url('modules/repayments/overdue.php'); ?>" class="btn btn-sm btn-danger">
                    Manage Overdue
                </a>
            </div>
        </div>
    </div>
<?php elseif ($userRole === 'loan_officer' && $pendingReviewCount > 0): ?>
    <!-- Loan Officer Pipeline Banner -->
    <div class="card shadow-sm mb-4 border-0" style="border-left: 4px solid #d97706 !important; background-color: #f8fafc;">
        <div class="card-body p-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <span class="fw-bold text-dark d-block"><i class="bi bi-hourglass-split text-warning me-2"></i> Underwriting Pipeline Attention</span>
                <span class="small text-muted">
                    There are currently <strong><?php echo $pendingReviewCount; ?></strong> loan application(s) awaiting underwriting credit review.
                </span>
            </div>
            <div>
                <a href="<?php echo url('modules/loans/index.php?status=pending'); ?>" class="btn btn-sm btn-warning text-dark fw-semibold">
                    Review Pending Loans
                </a>
            </div>
        </div>
    </div>
<?php elseif (($userRole === 'admin' || $userRole === 'manager') && $approvedReadyCount > 0): ?>
    <!-- Admin/Manager Disbursement Ready Banner -->
    <div class="card shadow-sm mb-4 border-0" style="border-left: 4px solid #0284c7 !important; background-color: #f8fafc;">
        <div class="card-body p-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <span class="fw-bold text-dark d-block"><i class="bi bi-cash-coin text-info me-2"></i> Capital Release Authorization</span>
                <span class="small text-muted">
                    <strong><?php echo $approvedReadyCount; ?></strong> approved loan(s) are waiting for fund disbursement and schedule activation.
                </span>
            </div>
            <div>
                <a href="<?php echo url('modules/loans/index.php?status=approved'); ?>" class="btn btn-sm btn-primary">
                    Disburse Approved Loans
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Top 6 Financial & Portfolio Summary Cards Grid -->
<div class="row g-3 mb-4">
    <!-- 1. Registered Borrowers -->
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card h-100 mb-0 shadow-sm border-0" style="border-left: 4px solid #0f172a !important;">
            <div class="card-body p-3.5">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Registered Borrowers</span>
                    <i class="bi bi-people-fill text-dark fs-5"></i>
                </div>
                <div class="h4 mb-1 fw-bold text-dark"><?php echo number_format($totalCustomers); ?></div>
                <div class="d-flex justify-content-between align-items-center small text-muted">
                    <span>New this month: <strong class="text-dark">+<?php echo $newCustThisMonth; ?></strong></span>
                    <a href="<?php echo url('modules/customers/index.php'); ?>" class="text-decoration-none text-primary">Browse &rarr;</a>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Active Loans Portfolio -->
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card h-100 mb-0 shadow-sm border-0" style="border-left: 4px solid #2563eb !important;">
            <div class="card-body p-3.5">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Active Loan Accounts</span>
                    <i class="bi bi-check2-circle text-primary fs-5"></i>
                </div>
                <div class="h4 mb-1 fw-bold text-primary"><?php echo number_format($activeLoansCount); ?> Active</div>
                <div class="d-flex justify-content-between align-items-center small text-muted">
                    <span>Valuation: <strong class="text-dark font-monospace"><?php echo format_currency($activePayableVal); ?></strong></span>
                    <a href="<?php echo url('modules/loans/index.php?status=active'); ?>" class="text-decoration-none text-primary">Portfolio &rarr;</a>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Capital Disbursed -->
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card h-100 mb-0 shadow-sm border-0" style="border-left: 4px solid #0284c7 !important;">
            <div class="card-body p-3.5">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Total Capital Disbursed</span>
                    <i class="bi bi-send-check text-info fs-5"></i>
                </div>
                <div class="h4 mb-1 fw-bold text-dark font-monospace"><?php echo format_currency($totalDisbursed); ?></div>
                <div class="d-flex justify-content-between align-items-center small text-muted">
                    <span>This Month: <strong class="text-dark font-monospace"><?php echo format_currency($disbThisMonth); ?></strong></span>
                    <?php if (can_access_report('disbursement')): ?>
                        <a href="<?php echo url('modules/reports/disbursement-report.php'); ?>" class="text-decoration-none text-primary">Audit &rarr;</a>
                    <?php else: ?>
                        <span class="text-muted">Lifetime</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Total Collections -->
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card h-100 mb-0 shadow-sm border-0" style="border-left: 4px solid #16a34a !important;">
            <div class="card-body p-3.5">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Total Collections Realized</span>
                    <i class="bi bi-cash-stack text-success fs-5"></i>
                </div>
                <div class="h4 mb-1 fw-bold text-success font-monospace"><?php echo format_currency($totalCollected); ?></div>
                <div class="d-flex justify-content-between align-items-center small text-muted">
                    <span>Today: <strong class="text-success font-monospace"><?php echo format_currency($todayCollected); ?></strong></span>
                    <a href="<?php echo url('modules/repayments/payment-history.php'); ?>" class="text-decoration-none text-success">Ledger &rarr;</a>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Total Outstanding Balance -->
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card h-100 mb-0 shadow-sm border-0" style="border-left: 4px solid #d97706 !important;">
            <div class="card-body p-3.5">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Total Active Outstanding</span>
                    <i class="bi bi-wallet2 text-warning fs-5"></i>
                </div>
                <div class="h4 mb-1 fw-bold text-dark font-monospace"><?php echo format_currency($totalOutstanding); ?></div>
                <div class="d-flex justify-content-between align-items-center small text-muted">
                    <span>Unsettled Principal + Interest</span>
                    <a href="<?php echo url('modules/repayments/index.php'); ?>" class="text-decoration-none text-primary">Repayments &rarr;</a>
                </div>
            </div>
        </div>
    </div>

    <!-- 6. Overdue Delinquency -->
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card h-100 mb-0 shadow-sm border-0" style="border-left: 4px solid #dc2626 !important;">
            <div class="card-body p-3.5">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Overdue Delinquency</span>
                    <i class="bi bi-exclamation-octagon text-danger fs-5"></i>
                </div>
                <div class="h4 mb-1 fw-bold text-danger font-monospace"><?php echo format_currency($overdueAmount); ?></div>
                <div class="d-flex justify-content-between align-items-center small text-muted">
                    <span>Delinquent: <strong class="text-danger"><?php echo $overdueCount; ?></strong> installments</span>
                    <a href="<?php echo url('modules/repayments/overdue.php'); ?>" class="text-decoration-none text-danger fw-semibold">Manage &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Overdue Attention Alert / Calm Good-Standing State -->
<?php if ($overdueCount > 0): ?>
    <div class="card shadow-sm mb-4 border-0" style="border-left: 4px solid #dc2626 !important; background-color: #fff5f5;">
        <div class="card-body p-3.5 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h2 class="h6 fw-bold text-danger mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i> Overdue Attention Required</h2>
                <p class="text-muted small mb-0">
                    <strong><?php echo $overdueCount; ?></strong> loan installments have surpassed their maturity dates with a total exposure of <strong class="text-danger font-monospace"><?php echo format_currency($overdueAmount); ?></strong>.
                </p>
            </div>
            <div>
                <a href="<?php echo url('modules/repayments/overdue.php'); ?>" class="btn btn-danger btn-sm text-nowrap">
                    <i class="bi bi-exclamation-octagon me-1"></i> Review Overdue Installments
                </a>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card shadow-sm mb-4 border-0" style="border-left: 4px solid #16a34a !important; background-color: #f0fdf4;">
        <div class="card-body p-3 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-shield-check text-success fs-5"></i>
                <span class="fw-semibold text-dark small">Portfolio Health: Zero overdue delinquency. All loan accounts are operating in good standing.</span>
            </div>
            <span class="badge bg-success small">100% On-Time</span>
        </div>
    </div>
<?php endif; ?>

<!-- Main Operations Grid: 2 Columns -->
<div class="row g-4 mb-4">
    <!-- Left Column: Portfolio Status Distribution & Monthly Activity -->
    <div class="col-12 col-lg-7">
        <!-- 1. Portfolio Status Distribution -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0 fw-bold"><i class="bi bi-pie-chart-fill me-2 text-primary"></i> Loan Portfolio Status Breakdown</h2>
                <a href="<?php echo url('modules/loans/index.php'); ?>" class="small text-decoration-none text-muted">View All Loans &rarr;</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-3 py-2.5">Status Category</th>
                                <th class="py-2.5 text-center">Accounts</th>
                                <th class="py-2.5 text-end">Requested Volume</th>
                                <th class="pe-3 py-2.5 text-end">Disbursed Volume</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($statusDistribution as $stKey => $stData): ?>
                                <tr>
                                    <td class="ps-3">
                                        <a href="<?php echo url('modules/loans/index.php?status=' . $stKey); ?>" class="text-decoration-none">
                                            <?php echo get_loan_status_badge($stKey); ?>
                                        </a>
                                    </td>
                                    <td class="text-center fw-bold text-dark">
                                        <a href="<?php echo url('modules/loans/index.php?status=' . $stKey); ?>" class="text-decoration-none text-dark">
                                            <?php echo number_format($stData['count']); ?>
                                        </a>
                                    </td>
                                    <td class="text-end font-monospace text-muted small">
                                        <?php echo format_currency($stData['requested']); ?>
                                    </td>
                                    <td class="pe-3 text-end font-monospace fw-semibold text-dark">
                                        <?php echo format_currency($stData['disbursed']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 2. Monthly Activity (Trailing 6 Months) -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0 fw-bold"><i class="bi bi-graph-up me-2 text-primary"></i> Monthly Activity (Trailing 6 Months)</h2>
                <?php if (can_access_report('portfolio')): ?>
                    <a href="<?php echo url('modules/reports/portfolio-report.php'); ?>" class="small text-decoration-none text-muted">Portfolio Audit &rarr;</a>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-3 py-2.5">Month</th>
                                <th class="py-2.5 text-end">Capital Disbursed</th>
                                <th class="py-2.5 text-end">Repayments Collected</th>
                                <th class="pe-3 py-2.5 text-end">Net Operational Cashflow</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthlyActivity as $ma): ?>
                                <?php $net = $ma['collected'] - $ma['disbursed']; ?>
                                <tr>
                                    <td class="ps-3 fw-semibold text-dark"><?php echo e($ma['month_label']); ?></td>
                                    <td class="text-end font-monospace text-dark"><?php echo format_currency($ma['disbursed']); ?></td>
                                    <td class="text-end font-monospace text-success fw-semibold"><?php echo format_currency($ma['collected']); ?></td>
                                    <td class="pe-3 text-end font-monospace fw-bold <?php echo $net >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo ($net >= 0 ? '+' : '') . format_currency($net); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Quick Operations & Upcoming Due Installments -->
    <div class="col-12 col-lg-5">
        <!-- 1. Role-Adaptive Quick Actions -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h2 class="h6 mb-0 fw-bold"><i class="bi bi-lightning-charge-fill me-2 text-primary"></i> Role Operations Quick Links</h2>
            </div>
            <div class="list-group list-group-flush small">
                <?php if ($canCreateLoan): ?>
                    <a href="<?php echo url('modules/loans/create.php'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2.5">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-plus-circle-fill text-primary fs-6"></i>
                            <span class="fw-semibold text-dark">Originate New Loan Application</span>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                <?php endif; ?>

                <?php if ($canCollect): ?>
                    <a href="<?php echo url('modules/repayments/index.php'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2.5">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-cash-stack text-success fs-6"></i>
                            <span class="fw-semibold text-dark">Repayment & Payment Collection</span>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                <?php endif; ?>

                <?php if ($canDisburse && $approvedReadyCount > 0): ?>
                    <a href="<?php echo url('modules/loans/index.php?status=approved'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2.5 bg-light">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-send-check text-info fs-6"></i>
                            <span class="fw-semibold text-dark">Disburse Approved Loans (<?php echo $approvedReadyCount; ?>)</span>
                        </div>
                        <span class="badge bg-primary">Ready</span>
                    </a>
                <?php endif; ?>

                <a href="<?php echo url('modules/repayments/overdue.php'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2.5">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-octagon text-danger fs-6"></i>
                        <span class="fw-semibold text-dark">Overdue Delinquency Tracking</span>
                    </div>
                    <?php if ($overdueCount > 0): ?>
                        <span class="badge bg-danger"><?php echo $overdueCount; ?> Late</span>
                    <?php else: ?>
                        <i class="bi bi-chevron-right text-muted"></i>
                    <?php endif; ?>
                </a>

                <a href="<?php echo url('modules/customers/index.php'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2.5">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-people text-dark fs-6"></i>
                        <span class="fw-semibold text-dark">Borrower Customer Directory</span>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>

                <?php if (can_access_report('dashboard')): ?>
                    <a href="<?php echo url('modules/reports/index.php'); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2.5">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-bar-chart-line text-primary fs-6"></i>
                            <span class="fw-semibold text-dark">Financial & Operational Reports</span>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2. Upcoming Installments (Next 7 Days) -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0 fw-bold"><i class="bi bi-calendar-event me-2 text-primary"></i> Upcoming Installments (Next 7 Days)</h2>
                <a href="<?php echo url('modules/repayments/index.php'); ?>" class="small text-decoration-none text-muted">Schedule &rarr;</a>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($upcomingInstallments)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light text-muted text-uppercase" style="font-size: 0.7rem;">
                                <tr>
                                    <th class="ps-3 py-2">Loan / Borrower</th>
                                    <th class="py-2">Due Date</th>
                                    <th class="py-2 text-end">Remaining</th>
                                    <th class="pe-3 py-2 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcomingInstallments as $ui): ?>
                                    <?php $isToday = ($ui['due_date'] === $today); ?>
                                    <tr>
                                        <td class="ps-3">
                                            <a href="<?php echo url('modules/repayments/view.php?loan_id=' . $ui['loan_id']); ?>" class="fw-bold font-monospace text-decoration-none text-primary d-block">
                                                <?php echo e($ui['loan_number']); ?>
                                            </a>
                                            <span class="text-dark fw-semibold"><?php echo e($ui['customer_name']); ?></span>
                                        </td>
                                        <td>
                                            <span class="fw-semibold <?php echo $isToday ? 'text-warning text-dark badge bg-warning' : 'text-dark'; ?>">
                                                <?php echo date('M d', strtotime($ui['due_date'])); ?>
                                            </span>
                                            <span class="text-muted d-block" style="font-size: 0.7rem;">Inst #<?php echo $ui['installment_number']; ?></span>
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-dark">
                                            <?php echo format_currency($ui['remaining_amount']); ?>
                                        </td>
                                        <td class="pe-3 text-end">
                                            <?php if ($canCollect): ?>
                                                <a href="<?php echo url('modules/repayments/collect.php?loan_id=' . $ui['loan_id'] . '&installment_id=' . $ui['id']); ?>" class="btn btn-xs btn-outline-success py-0 px-2" title="Collect Payment">
                                                    Collect
                                                </a>
                                            <?php else: ?>
                                                <a href="<?php echo url('modules/repayments/view.php?loan_id=' . $ui['loan_id']); ?>" class="btn btn-xs btn-outline-secondary py-0 px-2">
                                                    View
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-calendar-check fs-3 d-block mb-2 text-muted"></i>
                        <p class="small mb-0">No installments scheduled for payment in the next 7 days.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Row: Recent Loans & Recent Repayments Activity -->
<div class="row g-4">
    <!-- Recent Loans Table -->
    <div class="col-12 col-lg-6">
        <div class="card shadow-sm mb-4 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0 fw-bold"><i class="bi bi-file-earmark-text me-2 text-primary"></i> Recent Loan Applications</h2>
                <a href="<?php echo url('modules/loans/index.php'); ?>" class="small text-decoration-none text-muted">View All &rarr;</a>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($recentLoans)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light text-muted text-uppercase" style="font-size: 0.7rem;">
                                <tr>
                                    <th class="ps-3 py-2">Loan #</th>
                                    <th class="py-2">Borrower</th>
                                    <th class="py-2 text-end">Amount</th>
                                    <th class="py-2 text-center">Status</th>
                                    <th class="pe-3 py-2 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentLoans as $rl): ?>
                                    <tr>
                                        <td class="ps-3 font-monospace fw-bold">
                                            <a href="<?php echo url('modules/loans/view.php?id=' . $rl['id']); ?>" class="text-decoration-none text-primary">
                                                <?php echo e($rl['loan_number']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <a href="<?php echo url('modules/customers/view.php?id=' . $rl['customer_id']); ?>" class="text-decoration-none text-dark fw-semibold">
                                                <?php echo e($rl['customer_name']); ?>
                                            </a>
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-dark">
                                            <?php echo format_currency($rl['requested_amount']); ?>
                                        </td>
                                        <td class="text-center">
                                            <?php echo get_loan_status_badge($rl['status']); ?>
                                        </td>
                                        <td class="pe-3 text-end">
                                            <a href="<?php echo url('modules/loans/view.php?id=' . $rl['id']); ?>" class="btn btn-xs btn-outline-secondary py-0 px-2" title="View Loan Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        <p class="small mb-0">No loan applications recorded yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Payments Table -->
    <div class="col-12 col-lg-6">
        <div class="card shadow-sm mb-4 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0 fw-bold"><i class="bi bi-receipt me-2 text-primary"></i> Recent Repayment Receipts</h2>
                <a href="<?php echo url('modules/repayments/payment-history.php'); ?>" class="small text-decoration-none text-muted">Full History &rarr;</a>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($recentPayments)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light text-muted text-uppercase" style="font-size: 0.7rem;">
                                <tr>
                                    <th class="ps-3 py-2">Receipt Ref</th>
                                    <th class="py-2">Borrower</th>
                                    <th class="py-2 text-end">Amount</th>
                                    <th class="py-2">Date</th>
                                    <th class="pe-3 py-2 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentPayments as $rp): ?>
                                    <tr>
                                        <td class="ps-3 font-monospace fw-bold">
                                            <a href="<?php echo url('modules/repayments/receipt.php?ref=' . $rp['payment_reference']); ?>" class="text-decoration-none text-primary">
                                                <?php echo e($rp['payment_reference']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <a href="<?php echo url('modules/customers/view.php?id=' . $rp['customer_id']); ?>" class="text-decoration-none text-dark fw-semibold">
                                                <?php echo e($rp['customer_name']); ?>
                                            </a>
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-success">
                                            <?php echo format_currency($rp['amount']); ?>
                                        </td>
                                        <td class="text-nowrap text-muted">
                                            <?php echo date('M d, Y', strtotime($rp['payment_date'])); ?>
                                        </td>
                                        <td class="pe-3 text-end">
                                            <a href="<?php echo url('modules/repayments/receipt.php?ref=' . $rp['payment_reference']); ?>" class="btn btn-xs btn-outline-secondary py-0 px-2" title="View Official Receipt">
                                                <i class="bi bi-receipt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        <p class="small mb-0">No repayment payments recorded yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
