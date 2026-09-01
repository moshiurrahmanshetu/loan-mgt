<?php
/**
 * Global Payment Transactions History
 * Loan Management System (loan-mgt) - Phase 5
 */

$pageTitle = 'Payment History';
$activeNav = 'repayments';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = get_db_connection();

// 1. Query Filters
$search    = trim($_GET['search'] ?? '');
$method    = trim($_GET['method'] ?? 'all');
$startDate = trim($_GET['start_date'] ?? '');
$endDate   = trim($_GET['end_date'] ?? '');
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 10;
$offset    = ($page - 1) * $perPage;

$whereClauses = [];
$params       = [];

if ($search !== '') {
    $whereClauses[] = "(p.payment_reference LIKE :s_ref OR l.loan_number LIKE :s_ln OR c.full_name LIKE :s_name OR c.phone LIKE :s_phone OR c.customer_code LIKE :s_code)";
    $wildcard = '%' . $search . '%';
    $params[':s_ref']   = $wildcard;
    $params[':s_ln']    = $wildcard;
    $params[':s_name']  = $wildcard;
    $params[':s_phone'] = $wildcard;
    $params[':s_code']  = $wildcard;
}

$validMethods = ['cash', 'bank_transfer', 'mobile_banking'];
if (in_array($method, $validMethods, true)) {
    $whereClauses[] = "p.payment_method = :method";
    $params[':method'] = $method;
}

if (!empty($startDate) && strtotime($startDate)) {
    $whereClauses[] = "p.payment_date >= :start_date";
    $params[':start_date'] = $startDate;
}

if (!empty($endDate) && strtotime($endDate)) {
    $whereClauses[] = "p.payment_date <= :end_date";
    $params[':end_date'] = $endDate;
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// 2. Count Total Records & Total Volume
$countSql = "
    SELECT COUNT(*) AS total_count, COALESCE(SUM(p.amount), 0) AS total_sum
    FROM loan_payments p
    JOIN loans l ON p.loan_id = l.id
    JOIN customers c ON p.customer_id = c.id
    {$whereSql}
";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$countData = $countStmt->fetch();

$totalRecords = (int)($countData['total_count'] ?? 0);
$totalSum     = (float)($countData['total_sum'] ?? 0.0);
$totalPages   = max(1, ceil($totalRecords / $perPage));

if ($page > $totalPages && $totalRecords > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// 3. Fetch Paginated Payment Records
$selectSql = "
    SELECT p.*, 
           l.loan_number, 
           c.id AS customer_id, c.customer_code, c.full_name AS customer_name, c.phone AS customer_phone,
           li.installment_number,
           u.name AS collector_name
    FROM loan_payments p
    JOIN loans l ON p.loan_id = l.id
    JOIN customers c ON p.customer_id = c.id
    JOIN loan_installments li ON p.installment_id = li.id
    LEFT JOIN users u ON p.collected_by = u.id
    {$whereSql}
    ORDER BY p.id DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $db->prepare($selectSql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$payments = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/repayments/index.php'); ?>" class="text-decoration-none text-muted">Repayments</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Payment History</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h2 class="h4 fw-bold text-dark mb-0">Payment Transactions History</h2>
            <span class="badge bg-light text-dark border"><?php echo number_format($totalRecords); ?> Receipts</span>
        </div>
    </div>

    <div class="d-flex gap-2">
        <a href="<?php echo url('modules/repayments/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Repayment Dashboard
        </a>
    </div>
</div>

<!-- Filters Toolbar Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body p-3">
        <form action="<?php echo url('modules/repayments/payment-history.php'); ?>" method="GET" class="row g-2 align-items-center">
            <!-- Search -->
            <div class="col-12 col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search ref #, loan #, customer..." value="<?php echo e($search); ?>">
                </div>
            </div>

            <!-- Method Filter -->
            <div class="col-12 col-sm-6 col-md-2">
                <select name="method" class="form-select">
                    <option value="all" <?php echo $method === 'all' ? 'selected' : ''; ?>>All Channels</option>
                    <option value="cash" <?php echo $method === 'cash' ? 'selected' : ''; ?>>Cash</option>
                    <option value="bank_transfer" <?php echo $method === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                    <option value="mobile_banking" <?php echo $method === 'mobile_banking' ? 'selected' : ''; ?>>Mobile Banking</option>
                </select>
            </div>

            <!-- Date Range -->
            <div class="col-6 col-sm-3 col-md-2">
                <input type="date" name="start_date" class="form-control" value="<?php echo e($startDate); ?>" placeholder="Start Date" title="Start Date">
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <input type="date" name="end_date" class="form-control" value="<?php echo e($endDate); ?>" placeholder="End Date" title="End Date">
            </div>

            <!-- Action Buttons -->
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <?php if ($search !== '' || $method !== 'all' || $startDate !== '' || $endDate !== ''): ?>
                    <a href="<?php echo url('modules/repayments/payment-history.php'); ?>" class="btn btn-outline-secondary" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Transactions Summary Card -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h4 class="h6 mb-0 fw-bold"><i class="bi bi-receipt-cutoff me-2 text-primary"></i> Transactions Log</h4>
        <span class="small text-muted">Total Volume: <strong class="text-success font-monospace fs-6"><?php echo format_currency($totalSum); ?></strong></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3 py-3">Receipt Reference</th>
                        <th class="py-3">Payment Date</th>
                        <th class="py-3">Loan Account</th>
                        <th class="py-3">Borrower</th>
                        <th class="py-3 text-center">Installment #</th>
                        <th class="py-3 text-end">Amount Paid</th>
                        <th class="py-3">Payment Method</th>
                        <th class="py-3">Collected By</th>
                        <th class="pe-3 py-3 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($payments)): ?>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td class="ps-3 font-monospace fw-bold">
                                    <a href="<?php echo url('modules/repayments/receipt.php?ref=' . $p['payment_reference']); ?>" class="text-decoration-none text-primary">
                                        <?php echo e($p['payment_reference']); ?>
                                    </a>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($p['payment_date'])); ?></td>
                                <td>
                                    <a href="<?php echo url('modules/repayments/view.php?loan_id=' . $p['loan_id']); ?>" class="text-decoration-none font-monospace fw-semibold text-dark">
                                        <?php echo e($p['loan_number']); ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="<?php echo url('modules/customers/view.php?id=' . $p['customer_id']); ?>" class="text-decoration-none text-dark fw-semibold">
                                        <?php echo e($p['customer_name']); ?>
                                    </a>
                                    <div class="small text-muted font-monospace"><?php echo e($p['customer_code']); ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">#<?php echo $p['installment_number']; ?></span>
                                </td>
                                <td class="text-end fw-bold text-success font-monospace">
                                    <?php echo format_currency($p['amount']); ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?php echo e(get_payment_method_label($p['payment_method'])); ?>
                                    </span>
                                </td>
                                <td class="small text-muted">
                                    <?php echo e($p['collector_name'] ?? 'System'); ?>
                                </td>
                                <td class="pe-3 text-end">
                                    <a href="<?php echo url('modules/repayments/receipt.php?ref=' . $p['payment_reference']); ?>" class="btn btn-sm btn-outline-secondary" title="View Official Receipt">
                                        <i class="bi bi-receipt me-1"></i> Receipt
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-receipt fs-3 d-block mb-2 text-muted"></i>
                                <h5 class="fw-bold text-dark">No transaction receipts found</h5>
                                <p class="small mb-0">No payment records matched your search filter criteria.</p>
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
                Showing page <strong><?php echo $page; ?></strong> of <strong><?php echo $totalPages; ?></strong> (Total <?php echo number_format($totalRecords); ?> payments)
            </span>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo url('modules/repayments/payment-history.php?page=' . ($page - 1) . '&search=' . urlencode($search) . '&method=' . urlencode($method) . '&start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate)); ?>">Previous</a>
                </li>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo url('modules/repayments/payment-history.php?page=' . $p . '&search=' . urlencode($search) . '&method=' . urlencode($method) . '&start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate)); ?>"><?php echo $p; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo url('modules/repayments/payment-history.php?page=' . ($page + 1) . '&search=' . urlencode($search) . '&method=' . urlencode($method) . '&start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate)); ?>">Next</a>
                </li>
            </ul>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
