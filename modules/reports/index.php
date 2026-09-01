<?php
/**
 * Reports & Analytics Dashboard
 * Loan Management System (loan-mgt) - Phase 6
 */

$pageTitle = 'Reports & Analytics';
$activeNav = 'reports';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// 1. Role Guard
if (!can_access_report('dashboard')) {
    set_flash('danger', 'Unauthorized: You do not have permission to view Reports & Analytics.');
    redirect('modules/dashboard/index.php');
}

$db = get_db_connection();

// 2. Date Range Filter Handling & Validation
$fromDate = trim($_GET['from_date'] ?? '');
$toDate   = trim($_GET['to_date'] ?? '');
$dateError = null;

if (!empty($fromDate) && !empty($toDate)) {
    if (strtotime($fromDate) > strtotime($toDate)) {
        $dateError = 'Validation Error: "From Date" cannot be after "To Date".';
        $fromDate = '';
        $toDate = '';
    }
}

// 3. Compute Real-time Operational & Financial Metrics
$today = date('Y-m-d');

// Customers Count
$custSql = 'SELECT COUNT(*) FROM customers' . (!empty($fromDate) && !empty($toDate) ? " WHERE DATE(created_at) BETWEEN '{$fromDate}' AND '{$toDate}'" : '');
$totalCustomers = (int)$db->query($custSql)->fetchColumn();

// Loans Count & Status Distribution
$loanDateClause = (!empty($fromDate) && !empty($toDate)) ? " WHERE application_date BETWEEN '{$fromDate}' AND '{$toDate}'" : '';
$totalLoans = (int)$db->query("SELECT COUNT(*) FROM loans {$loanDateClause}")->fetchColumn();

$statusCountsStmt = $db->query("
    SELECT status, COUNT(*) AS cnt, COALESCE(SUM(requested_amount), 0) AS total_amt 
    FROM loans {$loanDateClause}
    GROUP BY status
");
$statusDistribution = [];
while ($row = $statusCountsStmt->fetch()) {
    $statusDistribution[$row['status']] = [
        'count'  => (int)$row['cnt'],
        'amount' => (float)$row['total_amt']
    ];
}

$approvedLoans  = $statusDistribution['approved']['count'] ?? 0;
$activeLoans    = $statusDistribution['active']['count'] ?? 0;
$completedLoans = $statusDistribution['completed']['count'] ?? 0;
$pendingLoans   = $statusDistribution['pending']['count'] ?? 0;
$rejectedLoans  = $statusDistribution['rejected']['count'] ?? 0;

// Total Disbursed Volume
$disbDateClause = (!empty($fromDate) && !empty($toDate)) ? " AND disbursement_date BETWEEN '{$fromDate}' AND '{$toDate}'" : '';
$totalDisbursed = (float)$db->query("
    SELECT COALESCE(SUM(disbursed_amount), 0) 
    FROM loans 
    WHERE status IN ('active', 'completed') AND disbursement_date IS NOT NULL {$disbDateClause}
")->fetchColumn();

// Total Collections Realized
$payDateClause = (!empty($fromDate) && !empty($toDate)) ? " WHERE payment_date BETWEEN '{$fromDate}' AND '{$toDate}'" : '';
$totalCollected = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM loan_payments {$payDateClause}")->fetchColumn();

// Total Outstanding Portfolio Balance (Active loans)
$totalOutstanding = (float)$db->query("
    SELECT COALESCE(SUM(li.remaining_amount), 0) 
    FROM loan_installments li 
    JOIN loans l ON li.loan_id = l.id 
    WHERE l.status = 'active' AND li.remaining_amount > 0
")->fetchColumn();

// Overdue Delinquency Exposure
$overdueExposure = (float)$db->query("
    SELECT COALESCE(SUM(li.remaining_amount), 0) 
    FROM loan_installments li 
    JOIN loans l ON li.loan_id = l.id 
    WHERE l.status = 'active' AND li.due_date < '{$today}' AND li.remaining_amount > 0
")->fetchColumn();

$overdueCount = (int)$db->query("
    SELECT COUNT(*) 
    FROM loan_installments li 
    JOIN loans l ON li.loan_id = l.id 
    WHERE l.status = 'active' AND li.due_date < '{$today}' AND li.remaining_amount > 0
")->fetchColumn();

// Product-wise Performance Breakdown
$productStats = $db->query("
    SELECT lp.id, lp.product_code, lp.name AS product_name,
           COUNT(l.id) AS total_loans,
           COALESCE(SUM(CASE WHEN l.status IN ('active', 'completed') THEN l.disbursed_amount ELSE 0 END), 0) AS total_disbursed,
           COALESCE(SUM(CASE WHEN l.status = 'active' THEN (SELECT SUM(li.remaining_amount) FROM loan_installments li WHERE li.loan_id = l.id) ELSE 0 END), 0) AS total_outstanding
    FROM loan_products lp
    LEFT JOIN loans l ON lp.id = l.loan_product_id
    GROUP BY lp.id
    ORDER BY total_loans DESC
")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Reports & Analytics</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h2 class="h4 fw-bold text-dark mb-0">Financial & Operational Reports</h2>
            <span class="badge bg-primary">Phase 6 Active</span>
        </div>
    </div>
</div>

<?php if (!empty($dateError)): ?>
    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-exclamation-circle-fill me-2 fs-5"></i>
        <div><?php echo e($dateError); ?></div>
    </div>
<?php endif; ?>

<!-- Date Filter Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body p-3">
        <form action="<?php echo url('modules/reports/index.php'); ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-3">
                <label for="from_date" class="form-label small text-muted mb-0 fw-semibold">From Date</label>
                <input type="date" name="from_date" id="from_date" class="form-control form-control-sm" value="<?php echo e($fromDate); ?>">
            </div>

            <div class="col-12 col-md-3">
                <label for="to_date" class="form-label small text-muted mb-0 fw-semibold">To Date</label>
                <input type="date" name="to_date" id="to_date" class="form-control form-control-sm" value="<?php echo e($toDate); ?>">
            </div>

            <div class="col-12 col-md-4 d-flex align-items-end gap-2 pt-md-3">
                <button type="submit" class="btn btn-primary btn-sm px-3">
                    <i class="bi bi-funnel me-1"></i> Apply Filter
                </button>
                <?php if (!empty($fromDate) || !empty($toDate)): ?>
                    <a href="<?php echo url('modules/reports/index.php'); ?>" class="btn btn-outline-secondary btn-sm" title="Reset Date Filter">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </a>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($fromDate) && !empty($toDate)): ?>
                <div class="col-12 mt-2">
                    <span class="badge bg-light text-dark border">
                        <i class="bi bi-calendar-range me-1 text-primary"></i> Filter Range: 
                        <?php echo date('M d, Y', strtotime($fromDate)); ?> — <?php echo date('M d, Y', strtotime($toDate)); ?>
                    </span>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Financial Summary Cards Grid -->
<div class="row g-3 mb-4">
    <!-- Total Customers -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100 mb-0 shadow-sm border-0" style="border-left: 4px solid #0f172a !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Registered Borrowers</span>
                    <i class="bi bi-people-fill text-dark fs-5"></i>
                </div>
                <div class="h4 mb-1 fw-bold text-dark"><?php echo number_format($totalCustomers); ?></div>
                <div class="small text-muted">Customer KYC records</div>
            </div>
        </div>
    </div>

    <!-- Total Loans -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100 mb-0 shadow-sm border-0" style="border-left: 4px solid #2563eb !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Total Originated</span>
                    <i class="bi bi-cash-stack text-primary fs-5"></i>
                </div>
                <div class="h4 mb-1 fw-bold text-primary"><?php echo number_format($totalLoans); ?> Loans</div>
                <div class="small text-muted">Active: <?php echo $activeLoans; ?> | Completed: <?php echo $completedLoans; ?></div>
            </div>
        </div>
    </div>

    <!-- Disbursed Volume -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100 mb-0 shadow-sm border-0" style="border-left: 4px solid #0284c7 !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Total Disbursed</span>
                    <i class="bi bi-send-check text-info fs-5"></i>
                </div>
                <div class="h4 mb-1 fw-bold text-dark font-monospace"><?php echo format_currency($totalDisbursed); ?></div>
                <div class="small text-muted">Released to borrowers</div>
            </div>
        </div>
    </div>

    <!-- Collections Realized -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100 mb-0 shadow-sm border-0" style="border-left: 4px solid #16a34a !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Total Collected</span>
                    <i class="bi bi-check2-circle text-success fs-5"></i>
                </div>
                <div class="h4 mb-1 fw-bold text-success font-monospace"><?php echo format_currency($totalCollected); ?></div>
                <div class="small text-muted">Repayment receipts logged</div>
            </div>
        </div>
    </div>

    <!-- Total Outstanding Portfolio -->
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card h-100 mb-0 shadow-sm border-0" style="border-left: 4px solid #d97706 !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Total Outstanding Balance</span>
                    <i class="bi bi-wallet2 text-warning fs-5"></i>
                </div>
                <div class="h4 mb-1 fw-bold text-dark font-monospace"><?php echo format_currency($totalOutstanding); ?></div>
                <div class="small text-muted">Active loans remaining balance</div>
            </div>
        </div>
    </div>

    <!-- Overdue Delinquency Exposure -->
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card h-100 mb-0 shadow-sm border-0" style="border-left: 4px solid #dc2626 !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Overdue Delinquency</span>
                    <i class="bi bi-exclamation-octagon text-danger fs-5"></i>
                </div>
                <div class="h4 mb-1 fw-bold text-danger font-monospace"><?php echo format_currency($overdueExposure); ?></div>
                <div class="small text-muted"><?php echo $overdueCount; ?> installments past due date</div>
            </div>
        </div>
    </div>

    <!-- Approved Loans (Pending Disbursement) -->
    <div class="col-12 col-sm-12 col-xl-4">
        <div class="card h-100 mb-0 shadow-sm border-0" style="border-left: 4px solid #475569 !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Approved (Pending Disbursement)</span>
                    <i class="bi bi-clock text-secondary fs-5"></i>
                </div>
                <div class="h4 mb-1 fw-bold text-dark"><?php echo number_format($approvedLoans); ?> Loans</div>
                <div class="small text-muted">Pending review: <?php echo $pendingLoans; ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Report Navigation Catalog Grid -->
<h3 class="h6 fw-bold text-dark mb-3"><i class="bi bi-grid-fill me-2 text-primary"></i> Available Financial & Operational Reports</h3>
<div class="row g-3 mb-4">
    <!-- 1. Loan Report -->
    <?php if (can_access_report('loan')): ?>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="bg-primary text-white rounded p-2 d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                            <h4 class="h6 fw-bold text-dark mb-0">Loan Applications Report</h4>
                        </div>
                        <p class="text-muted small mb-3">
                            Comprehensive portfolio analysis of originated loans, application dates, approval states, products, and terms.
                        </p>
                    </div>
                    <div>
                        <a href="<?php echo url('modules/reports/loan-report.php'); ?>" class="btn btn-outline-primary btn-sm w-100 fw-semibold">
                            <i class="bi bi-file-earmark-text me-1"></i> Open Loan Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 2. Disbursement Report -->
    <?php if (can_access_report('disbursement')): ?>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="bg-info text-white rounded p-2 d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                <i class="bi bi-send-check"></i>
                            </div>
                            <h4 class="h6 fw-bold text-dark mb-0">Disbursement Report</h4>
                        </div>
                        <p class="text-muted small mb-3">
                            Audit of capital disbursements released to borrowers by date, channel, and authorizing officer.
                        </p>
                    </div>
                    <div>
                        <a href="<?php echo url('modules/reports/disbursement-report.php'); ?>" class="btn btn-outline-info btn-sm w-100 fw-semibold text-dark">
                            <i class="bi bi-file-earmark-text me-1"></i> Open Disbursement Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 3. Repayment Report -->
    <?php if (can_access_report('repayment')): ?>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="bg-success text-white rounded p-2 d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                <i class="bi bi-receipt"></i>
                            </div>
                            <h4 class="h6 fw-bold text-dark mb-0">Repayment & Collections Report</h4>
                        </div>
                        <p class="text-muted small mb-3">
                            Real-time collection transactions ledger with payment receipts, channels, collectors, and settlement breakdown.
                        </p>
                    </div>
                    <div>
                        <a href="<?php echo url('modules/reports/repayment-report.php'); ?>" class="btn btn-outline-success btn-sm w-100 fw-semibold">
                            <i class="bi bi-file-earmark-text me-1"></i> Open Repayment Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 4. Overdue Report -->
    <?php if (can_access_report('overdue')): ?>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="bg-danger text-white rounded p-2 d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <h4 class="h6 fw-bold text-dark mb-0">Overdue Delinquency Report</h4>
                        </div>
                        <p class="text-muted small mb-3">
                            Tracking of delinquent installments past maturity date with calculated days overdue and exposure balances.
                        </p>
                    </div>
                    <div>
                        <a href="<?php echo url('modules/reports/overdue-report.php'); ?>" class="btn btn-outline-danger btn-sm w-100 fw-semibold">
                            <i class="bi bi-file-earmark-text me-1"></i> Open Overdue Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 5. Customer Report -->
    <?php if (can_access_report('customer')): ?>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="bg-dark text-white rounded p-2 d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                <i class="bi bi-people"></i>
                            </div>
                            <h4 class="h6 fw-bold text-dark mb-0">Customer Summary Report</h4>
                        </div>
                        <p class="text-muted small mb-3">
                            Borrower directory performance summary with active loans, total borrowed volume, and outstanding balances.
                        </p>
                    </div>
                    <div>
                        <a href="<?php echo url('modules/reports/customer-report.php'); ?>" class="btn btn-outline-dark btn-sm w-100 fw-semibold">
                            <i class="bi bi-file-earmark-text me-1"></i> Open Customer Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 6. Portfolio Report -->
    <?php if (can_access_report('portfolio')): ?>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="bg-warning text-dark rounded p-2 d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                <i class="bi bi-pie-chart-fill"></i>
                            </div>
                            <h4 class="h6 fw-bold text-dark mb-0">Portfolio Financial Summary</h4>
                        </div>
                        <p class="text-muted small mb-3">
                            Executive financial summary of principal disbursed, collections, revenue yield, and status breakdowns.
                        </p>
                    </div>
                    <div>
                        <a href="<?php echo url('modules/reports/portfolio-report.php'); ?>" class="btn btn-outline-warning btn-sm w-100 fw-semibold text-dark">
                            <i class="bi bi-file-earmark-text me-1"></i> Open Portfolio Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Product-wise Breakdown Card -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h4 class="h6 mb-0 fw-bold"><i class="bi bi-tags-fill me-2 text-primary"></i> Loan Products Portfolio Breakdown</h4>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3 py-3">Product Name</th>
                        <th class="py-3">Code</th>
                        <th class="py-3 text-center">Originated Loans</th>
                        <th class="py-3 text-end">Total Disbursed</th>
                        <th class="pe-3 py-3 text-end">Current Outstanding</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($productStats)): ?>
                        <?php foreach ($productStats as $p): ?>
                            <tr>
                                <td class="ps-3 fw-semibold text-dark">
                                    <a href="<?php echo url('modules/loan-products/view.php?id=' . $p['id']); ?>" class="text-decoration-none text-dark">
                                        <?php echo e($p['product_name']); ?>
                                    </a>
                                </td>
                                <td class="font-monospace text-muted"><?php echo e($p['product_code']); ?></td>
                                <td class="text-center fw-bold"><?php echo number_format($p['total_loans']); ?></td>
                                <td class="text-end font-monospace"><?php echo format_currency($p['total_disbursed']); ?></td>
                                <td class="pe-3 text-end font-monospace fw-semibold <?php echo (float)$p['total_outstanding'] > 0 ? 'text-danger' : 'text-muted'; ?>">
                                    <?php echo format_currency($p['total_outstanding']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No product performance data available.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
