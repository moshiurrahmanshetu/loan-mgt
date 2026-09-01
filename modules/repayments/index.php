<?php
/**
 * Repayment & Collection Dashboard
 * Loan Management System (loan-mgt) - Phase 5
 */

$pageTitle = 'Repayment & Collections';
$activeNav = 'repayments';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = get_db_connection();

// 1. KPI Metrics
$today = date('Y-m-d');

// Active Loans Count
$activeLoansCount = (int)$db->query("SELECT COUNT(*) FROM loans WHERE status = 'active'")->fetchColumn();

// Total Outstanding Across Active Portfolio
$totalOutstanding = (float)$db->query("
    SELECT COALESCE(SUM(li.remaining_amount), 0) 
    FROM loan_installments li 
    JOIN loans l ON li.loan_id = l.id 
    WHERE l.status = 'active' AND li.remaining_amount > 0
")->fetchColumn();

// Today's Due Installments & Amount
$dueTodayStmt = $db->query("
    SELECT COUNT(*) AS cnt, COALESCE(SUM(li.remaining_amount), 0) AS amt 
    FROM loan_installments li 
    JOIN loans l ON li.loan_id = l.id 
    WHERE l.status = 'active' AND li.due_date = '{$today}' AND li.remaining_amount > 0
");
$dueTodayData = $dueTodayStmt->fetch();
$dueTodayCount  = (int)($dueTodayData['cnt'] ?? 0);
$dueTodayAmount = (float)($dueTodayData['amt'] ?? 0.0);

// Today's Realized Collections
$todayCollection = (float)$db->query("
    SELECT COALESCE(SUM(amount), 0) 
    FROM loan_payments 
    WHERE payment_date = '{$today}'
")->fetchColumn();

// Overdue Installments & Amount
$overdueStmt = $db->query("
    SELECT COUNT(*) AS cnt, COALESCE(SUM(li.remaining_amount), 0) AS amt 
    FROM loan_installments li 
    JOIN loans l ON li.loan_id = l.id 
    WHERE l.status = 'active' AND li.due_date < '{$today}' AND li.remaining_amount > 0
");
$overdueData = $overdueStmt->fetch();
$overdueCount  = (int)($overdueData['cnt'] ?? 0);
$overdueAmount = (float)($overdueData['amt'] ?? 0.0);

// 2. Query & Filter Parameters
$search       = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'active');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 10;
$offset       = ($page - 1) * $perPage;

$whereClauses = [];
$params       = [];

// Scope only to loans with repayment activity (active or completed)
if ($statusFilter === 'completed') {
    $whereClauses[] = "l.status = 'completed'";
} elseif ($statusFilter === 'all') {
    $whereClauses[] = "l.status IN ('active', 'completed')";
} elseif ($statusFilter === 'due_today') {
    $whereClauses[] = "l.status = 'active' AND EXISTS (SELECT 1 FROM loan_installments li WHERE li.loan_id = l.id AND li.due_date = '{$today}' AND li.remaining_amount > 0)";
} elseif ($statusFilter === 'overdue') {
    $whereClauses[] = "l.status = 'active' AND EXISTS (SELECT 1 FROM loan_installments li WHERE li.loan_id = l.id AND li.due_date < '{$today}' AND li.remaining_amount > 0)";
} else {
    // Default: Active
    $whereClauses[] = "l.status = 'active'";
    $statusFilter = 'active';
}

if ($search !== '') {
    $whereClauses[] = "(l.loan_number LIKE :s_ln OR c.full_name LIKE :s_name OR c.phone LIKE :s_phone OR c.customer_code LIKE :s_code)";
    $wildcard = '%' . $search . '%';
    $params[':s_ln']    = $wildcard;
    $params[':s_name']  = $wildcard;
    $params[':s_phone'] = $wildcard;
    $params[':s_code']  = $wildcard;
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Count Total
$countSql = "SELECT COUNT(*) FROM loans l JOIN customers c ON l.customer_id = c.id {$whereSql}";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages   = max(1, ceil($totalRecords / $perPage));

if ($page > $totalPages && $totalRecords > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// Fetch Loans with Repayment Metrics & Next Due Date
$selectSql = "
    SELECT l.id, l.loan_number, l.requested_amount, l.interest_rate, l.interest_method,
           l.estimated_total_payable, l.disbursement_date, l.status,
           c.id AS customer_id, c.customer_code, c.full_name AS customer_name, c.phone AS customer_phone,
           lp.name AS product_name,
           (SELECT COALESCE(SUM(li.paid_amount), 0) FROM loan_installments li WHERE li.loan_id = l.id) AS total_paid,
           (SELECT COALESCE(SUM(li.remaining_amount), 0) FROM loan_installments li WHERE li.loan_id = l.id) AS total_remaining,
           (SELECT MIN(li.due_date) FROM loan_installments li WHERE li.loan_id = l.id AND li.remaining_amount > 0) AS next_due_date,
           (SELECT MIN(li.installment_number) FROM loan_installments li WHERE li.loan_id = l.id AND li.remaining_amount > 0) AS next_installment_num
    FROM loans l 
    JOIN customers c ON l.customer_id = c.id 
    LEFT JOIN loan_products lp ON l.loan_product_id = lp.id 
    {$whereSql}
    ORDER BY (CASE WHEN l.status = 'active' THEN 0 ELSE 1 END) ASC, next_due_date ASC, l.id DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $db->prepare($selectSql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$loans = $stmt->fetchAll();

$canCollect = can_collect_payments();

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Repayment & Collections</li>
            </ol>
        </nav>
        <h2 class="h4 fw-bold text-dark mb-0">Repayment & Collection Management</h2>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="<?php echo url('modules/repayments/overdue.php'); ?>" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-exclamation-triangle me-1"></i> Overdue Installments (<?php echo $overdueCount; ?>)
        </a>
        <a href="<?php echo url('modules/repayments/payment-history.php'); ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-clock-history me-1"></i> Payment History
        </a>
    </div>
</div>

<!-- KPI Summary Cards Grid -->
<div class="row g-3 mb-4">
    <!-- Active Portfolio -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100 mb-0 shadow-sm border-0" style="border-left: 4px solid #2563eb !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Active Loans</span>
                    <i class="bi bi-check2-circle text-primary fs-5"></i>
                </div>
                <div class="h4 mb-1 fw-bold text-dark"><?php echo number_format($activeLoansCount); ?></div>
                <div class="small text-muted">Outstanding: <?php echo format_currency($totalOutstanding); ?></div>
            </div>
        </div>
    </div>

    <!-- Today's Due -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100 mb-0 shadow-sm border-0" style="border-left: 4px solid #eab308 !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Due Today</span>
                    <i class="bi bi-calendar-event text-warning fs-5"></i>
                </div>
                <div class="h4 mb-1 fw-bold text-warning"><?php echo format_currency($dueTodayAmount); ?></div>
                <div class="small text-muted"><?php echo $dueTodayCount; ?> installments scheduled today</div>
            </div>
        </div>
    </div>

    <!-- Today's Collections -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100 mb-0 shadow-sm border-0" style="border-left: 4px solid #16a34a !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Today's Collections</span>
                    <i class="bi bi-cash-stack text-success fs-5"></i>
                </div>
                <div class="h4 mb-1 fw-bold text-success"><?php echo format_currency($todayCollection); ?></div>
                <div class="small text-muted">Received today (<?php echo date('M d'); ?>)</div>
            </div>
        </div>
    </div>

    <!-- Overdue Installments -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100 mb-0 shadow-sm border-0" style="border-left: 4px solid #dc2626 !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-semibold">Overdue Delinquency</span>
                    <i class="bi bi-exclamation-octagon text-danger fs-5"></i>
                </div>
                <div class="h4 mb-1 fw-bold text-danger"><?php echo format_currency($overdueAmount); ?></div>
                <div class="small text-muted"><?php echo $overdueCount; ?> installments past due date</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters & Search Toolbar Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body p-3">
        <form action="<?php echo url('modules/repayments/index.php'); ?>" method="GET" class="row g-2 align-items-center">
            <!-- Search -->
            <div class="col-12 col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search loan #, customer name, phone..." value="<?php echo e($search); ?>">
                </div>
            </div>

            <!-- Status Filter -->
            <div class="col-12 col-sm-6 col-md-4">
                <select name="status" class="form-select">
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active Loans (Outstanding)</option>
                    <option value="due_today" <?php echo $statusFilter === 'due_today' ? 'selected' : ''; ?>>Due Today Only (<?php echo $dueTodayCount; ?>)</option>
                    <option value="overdue" <?php echo $statusFilter === 'overdue' ? 'selected' : ''; ?>>Overdue Loans (<?php echo $overdueCount; ?>)</option>
                    <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed / Fully Paid</option>
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Repayment Accounts</option>
                </select>
            </div>

            <!-- Filter Actions -->
            <div class="col-12 col-sm-6 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1 flex-md-grow-0 px-4">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <?php if ($search !== '' || $statusFilter !== 'active'): ?>
                    <a href="<?php echo url('modules/repayments/index.php'); ?>" class="btn btn-outline-secondary" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Repayment Accounts Data Table Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3 py-3">Loan Account</th>
                        <th class="py-3">Borrower Details</th>
                        <th class="py-3 text-end">Total Payable</th>
                        <th class="py-3 text-end">Paid Amount</th>
                        <th class="py-3 text-end">Outstanding</th>
                        <th class="py-3 text-center">Next Due Date</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="pe-3 py-3 text-end" style="min-width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($loans)): ?>
                        <?php foreach ($loans as $row): ?>
                            <?php 
                                $isOverdue = (!empty($row['next_due_date']) && $row['next_due_date'] < $today && (float)$row['total_remaining'] > 0);
                                $isDueToday = (!empty($row['next_due_date']) && $row['next_due_date'] === $today && (float)$row['total_remaining'] > 0);
                            ?>
                            <tr>
                                <td class="ps-3 fw-semibold">
                                    <a href="<?php echo url('modules/repayments/view.php?loan_id=' . $row['id']); ?>" class="text-decoration-none font-monospace fw-bold text-primary">
                                        <?php echo e($row['loan_number']); ?>
                                    </a>
                                    <div class="small text-muted"><?php echo e($row['product_name'] ?? 'Loan'); ?></div>
                                </td>
                                <td>
                                    <a href="<?php echo url('modules/customers/view.php?id=' . $row['customer_id']); ?>" class="fw-semibold text-dark text-decoration-none d-block">
                                        <?php echo e($row['customer_name']); ?>
                                    </a>
                                    <div class="small text-muted font-monospace">
                                        <?php echo e($row['customer_code']); ?> &bull; <?php echo e($row['customer_phone']); ?>
                                    </div>
                                </td>
                                <td class="text-end fw-semibold text-dark">
                                    <?php echo format_currency($row['estimated_total_payable']); ?>
                                </td>
                                <td class="text-end text-success fw-semibold">
                                    <?php echo format_currency($row['total_paid']); ?>
                                </td>
                                <td class="text-end fw-bold <?php echo (float)$row['total_remaining'] > 0 ? 'text-danger' : 'text-muted'; ?>">
                                    <?php echo format_currency($row['total_remaining']); ?>
                                </td>
                                <td class="text-center small">
                                    <?php if (!empty($row['next_due_date'])): ?>
                                        <div class="fw-semibold <?php echo $isOverdue ? 'text-danger' : ($isDueToday ? 'text-warning' : 'text-dark'); ?>">
                                            <?php echo date('M d, Y', strtotime($row['next_due_date'])); ?>
                                        </div>
                                        <span class="badge bg-light text-muted border" style="font-size: 0.7rem;">
                                            Inst #<?php echo $row['next_installment_num']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-success"><i class="bi bi-check-circle me-1"></i> Fully Settled</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php echo get_loan_status_badge($row['status']); ?>
                                </td>
                                <td class="pe-3 text-end text-nowrap">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?php echo url('modules/repayments/view.php?loan_id=' . $row['id']); ?>" class="btn btn-outline-secondary" title="View Schedule & Ledger" data-bs-toggle="tooltip">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <?php if ($row['status'] === 'active' && $canCollect && (float)$row['total_remaining'] > 0): ?>
                                            <a href="<?php echo url('modules/repayments/collect.php?loan_id=' . $row['id']); ?>" class="btn btn-success" title="Collect Payment" data-bs-toggle="tooltip">
                                                <i class="bi bi-cash me-1"></i> Collect
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="py-4">
                                    <i class="bi bi-wallet2 text-muted display-6 d-block mb-3"></i>
                                    <h5 class="fw-bold text-dark">No repayment accounts found</h5>
                                    <p class="text-muted small mb-3">
                                        <?php if ($search !== '' || $statusFilter !== 'active'): ?>
                                            No accounts matched your current search or filter criteria.
                                        <?php else: ?>
                                            There are currently no active disbursed loans in the repayment portfolio.
                                        <?php endif; ?>
                                    </p>
                                    <?php if ($search !== '' || $statusFilter !== 'active'): ?>
                                        <a href="<?php echo url('modules/repayments/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination Card -->
<?php if ($totalPages > 1): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-body p-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <span class="small text-muted">
                Showing page <strong><?php echo $page; ?></strong> of <strong><?php echo $totalPages; ?></strong> (Total <?php echo number_format($totalRecords); ?> accounts)
            </span>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo url('modules/repayments/index.php?page=' . ($page - 1) . '&search=' . urlencode($search) . '&status=' . urlencode($statusFilter)); ?>">Previous</a>
                </li>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo url('modules/repayments/index.php?page=' . $p . '&search=' . urlencode($search) . '&status=' . urlencode($statusFilter)); ?>"><?php echo $p; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo url('modules/repayments/index.php?page=' . ($page + 1) . '&search=' . urlencode($search) . '&status=' . urlencode($statusFilter)); ?>">Next</a>
                </li>
            </ul>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
