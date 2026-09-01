<?php
/**
 * Overdue Installments Management View
 * Loan Management System (loan-mgt) - Phase 5
 */

$pageTitle = 'Overdue Installments';
$activeNav = 'repayments';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = get_db_connection();
$today = date('Y-m-d');

// 1. Query Filters
$search  = trim($_GET['search'] ?? '');
$sortBy  = trim($_GET['sort'] ?? 'days_desc');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

$whereClauses = [
    "l.status = 'active'",
    "li.due_date < :today",
    "li.remaining_amount > 0"
];
$params = [':today' => $today];

if ($search !== '') {
    $whereClauses[] = "(l.loan_number LIKE :s_ln OR c.full_name LIKE :s_name OR c.phone LIKE :s_phone OR c.customer_code LIKE :s_code)";
    $wildcard = '%' . $search . '%';
    $params[':s_ln']    = $wildcard;
    $params[':s_name']  = $wildcard;
    $params[':s_phone'] = $wildcard;
    $params[':s_code']  = $wildcard;
}

$whereSql = 'WHERE ' . implode(' AND ', $whereClauses);

// Sorting
$orderSql = match ($sortBy) {
    'days_asc'   => 'li.due_date DESC',
    'amount_desc'=> 'li.remaining_amount DESC',
    'amount_asc' => 'li.remaining_amount ASC',
    default      => 'li.due_date ASC', // Oldest overdue first
};

// 2. Count Total Records & Total Overdue Amount
$countSql = "
    SELECT COUNT(*) AS total_count, COALESCE(SUM(li.remaining_amount), 0) AS total_overdue
    FROM loan_installments li
    JOIN loans l ON li.loan_id = l.id
    JOIN customers c ON l.customer_id = c.id
    {$whereSql}
";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$countData = $countStmt->fetch();

$totalRecords = (int)($countData['total_count'] ?? 0);
$totalOverdue = (float)($countData['total_overdue'] ?? 0.0);
$totalPages   = max(1, ceil($totalRecords / $perPage));

if ($page > $totalPages && $totalRecords > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// 3. Fetch Paginated Overdue Installments
$selectSql = "
    SELECT li.*, 
           DATEDIFF(:today_calc, li.due_date) AS days_overdue,
           l.loan_number, l.requested_amount,
           c.id AS customer_id, c.customer_code, c.full_name AS customer_name, c.phone AS customer_phone,
           lp.name AS product_name
    FROM loan_installments li
    JOIN loans l ON li.loan_id = l.id
    JOIN customers c ON l.customer_id = c.id
    LEFT JOIN loan_products lp ON l.loan_product_id = lp.id
    {$whereSql}
    ORDER BY {$orderSql}
    LIMIT :limit OFFSET :offset
";

$stmt = $db->prepare($selectSql);
$stmt->bindValue(':today_calc', $today);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$overdueList = $stmt->fetchAll();

$canCollect = can_collect_payments();

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/repayments/index.php'); ?>" class="text-decoration-none text-muted">Repayments</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Overdue Delinquency</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h2 class="h4 fw-bold text-dark mb-0">Overdue Installments Tracking</h2>
            <span class="badge bg-danger"><?php echo number_format($totalRecords); ?> Delinquent</span>
        </div>
    </div>

    <div class="d-flex gap-2">
        <a href="<?php echo url('modules/repayments/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Repayment Dashboard
        </a>
    </div>
</div>

<!-- Overdue KPI Banner -->
<div class="card shadow-sm mb-4 border-0" style="border-left: 4px solid #dc2626 !important; background-color: #ffffff;">
    <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h3 class="h6 fw-bold text-dark mb-1"><i class="bi bi-exclamation-triangle-fill text-danger me-2"></i> Delinquency Risk Summary</h3>
            <p class="text-muted small mb-0">
                Installments listed below have passed their scheduled maturity due date and have outstanding unpaid balances.
            </p>
        </div>
        <div class="text-md-end">
            <span class="small text-muted d-block">Total Overdue Exposure</span>
            <span class="h4 fw-bold text-danger font-monospace mb-0"><?php echo format_currency($totalOverdue); ?></span>
        </div>
    </div>
</div>

<!-- Filters Toolbar Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body p-3">
        <form action="<?php echo url('modules/repayments/overdue.php'); ?>" method="GET" class="row g-2 align-items-center">
            <!-- Search -->
            <div class="col-12 col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search loan #, customer name, phone..." value="<?php echo e($search); ?>">
                </div>
            </div>

            <!-- Sort By -->
            <div class="col-12 col-sm-6 col-md-3">
                <select name="sort" class="form-select">
                    <option value="days_desc" <?php echo $sortBy === 'days_desc' ? 'selected' : ''; ?>>Most Overdue (Oldest Due Date)</option>
                    <option value="days_asc" <?php echo $sortBy === 'days_asc' ? 'selected' : ''; ?>>Least Overdue (Recent Due Date)</option>
                    <option value="amount_desc" <?php echo $sortBy === 'amount_desc' ? 'selected' : ''; ?>>Highest Overdue Amount</option>
                    <option value="amount_asc" <?php echo $sortBy === 'amount_asc' ? 'selected' : ''; ?>>Lowest Overdue Amount</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="col-12 col-sm-6 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <?php if ($search !== '' || $sortBy !== 'days_desc'): ?>
                    <a href="<?php echo url('modules/repayments/overdue.php'); ?>" class="btn btn-outline-secondary" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Overdue Installments Data Table Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3 py-3">Loan Account</th>
                        <th class="py-3">Borrower Details</th>
                        <th class="py-3 text-center">Installment #</th>
                        <th class="py-3">Due Date</th>
                        <th class="py-3 text-center">Days Overdue</th>
                        <th class="py-3 text-end">Installment Total</th>
                        <th class="py-3 text-end">Paid Amount</th>
                        <th class="py-3 text-end">Overdue Balance</th>
                        <th class="pe-3 py-3 text-end" style="min-width: 130px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($overdueList)): ?>
                        <?php foreach ($overdueList as $row): ?>
                            <tr>
                                <td class="ps-3 fw-semibold">
                                    <a href="<?php echo url('modules/repayments/view.php?loan_id=' . $row['loan_id']); ?>" class="text-decoration-none font-monospace fw-bold text-primary">
                                        <?php echo e($row['loan_number']); ?>
                                    </a>
                                    <div class="small text-muted"><?php echo e($row['product_name'] ?? 'Loan'); ?></div>
                                </td>
                                <td>
                                    <a href="<?php echo url('modules/customers/view.php?id=' . $row['customer_id']); ?>" class="text-decoration-none text-dark fw-semibold">
                                        <?php echo e($row['customer_name']); ?>
                                    </a>
                                    <div class="small text-muted font-monospace">
                                        <?php echo e($row['customer_code']); ?> &bull; <?php echo e($row['customer_phone']); ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">#<?php echo $row['installment_number']; ?></span>
                                </td>
                                <td class="text-danger fw-semibold text-nowrap">
                                    <?php echo date('M d, Y', strtotime($row['due_date'])); ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-danger">
                                        <?php echo $row['days_overdue']; ?> Days Late
                                    </span>
                                </td>
                                <td class="text-end fw-semibold text-dark font-monospace">
                                    <?php echo format_currency($row['installment_amount']); ?>
                                </td>
                                <td class="text-end text-success font-monospace">
                                    <?php echo format_currency($row['paid_amount']); ?>
                                </td>
                                <td class="text-end fw-bold text-danger font-monospace">
                                    <?php echo format_currency($row['remaining_amount']); ?>
                                </td>
                                <td class="pe-3 text-end">
                                    <?php if ($canCollect): ?>
                                        <a href="<?php echo url('modules/repayments/collect.php?loan_id=' . $row['loan_id'] . '&installment_id=' . $row['id']); ?>" class="btn btn-sm btn-success text-nowrap">
                                            <i class="bi bi-cash me-1"></i> Collect
                                        </a>
                                    <?php else: ?>
                                        <a href="<?php echo url('modules/repayments/view.php?loan_id=' . $row['loan_id']); ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-shield-check fs-2 text-success d-block mb-2"></i>
                                <h5 class="fw-bold text-dark">No overdue installments</h5>
                                <p class="small mb-0">Excellent portfolio health. There are currently no delinquent installments past due.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-body p-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <span class="small text-muted">
                Showing page <strong><?php echo $page; ?></strong> of <strong><?php echo $totalPages; ?></strong> (Total <?php echo number_format($totalRecords); ?> overdue installments)
            </span>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo url('modules/repayments/overdue.php?page=' . ($page - 1) . '&search=' . urlencode($search) . '&sort=' . urlencode($sortBy)); ?>">Previous</a>
                </li>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo url('modules/repayments/overdue.php?page=' . $p . '&search=' . urlencode($search) . '&sort=' . urlencode($sortBy)); ?>"><?php echo $p; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo url('modules/repayments/overdue.php?page=' . ($page + 1) . '&search=' . urlencode($search) . '&sort=' . urlencode($sortBy)); ?>">Next</a>
                </li>
            </ul>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
