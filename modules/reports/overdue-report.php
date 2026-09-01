<?php
/**
 * Overdue Delinquency Report
 * Loan Management System (loan-mgt) - Phase 6
 */

$pageTitle = 'Overdue Delinquency Report';
$activeNav = 'reports';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// 1. Role Guard
if (!can_access_report('overdue')) {
    set_flash('danger', 'Unauthorized: You do not have permission to view the Overdue Delinquency Report.');
    redirect('modules/reports/index.php');
}

$db = get_db_connection();
$today = date('Y-m-d');

// 2. Query Filters & Validation
$productId = (int)($_GET['product_id'] ?? 0);
$agingBand = trim($_GET['aging'] ?? 'all');
$search    = trim($_GET['search'] ?? '');
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 15;
$offset    = ($page - 1) * $perPage;

$whereClauses = [
    "l.status = 'active'",
    "li.due_date < :today",
    "li.remaining_amount > 0"
];
$params = [':today' => $today];

if ($productId > 0) {
    $whereClauses[] = "l.loan_product_id = :product_id";
    $params[':product_id'] = $productId;
}

if ($agingBand === '1_30') {
    $whereClauses[] = "DATEDIFF(:today_1, li.due_date) BETWEEN 1 AND 30";
    $params[':today_1'] = $today;
} elseif ($agingBand === '31_60') {
    $whereClauses[] = "DATEDIFF(:today_2, li.due_date) BETWEEN 31 AND 60";
    $params[':today_2'] = $today;
} elseif ($agingBand === '61_90') {
    $whereClauses[] = "DATEDIFF(:today_3, li.due_date) BETWEEN 61 AND 90";
    $params[':today_3'] = $today;
} elseif ($agingBand === '90_plus') {
    $whereClauses[] = "DATEDIFF(:today_4, li.due_date) > 90";
    $params[':today_4'] = $today;
}

if ($search !== '') {
    $whereClauses[] = "(l.loan_number LIKE :s_ln OR c.full_name LIKE :s_name OR c.phone LIKE :s_phone OR c.customer_code LIKE :s_code)";
    $wildcard = '%' . $search . '%';
    $params[':s_ln']    = $wildcard;
    $params[':s_name']  = $wildcard;
    $params[':s_phone'] = $wildcard;
    $params[':s_code']  = $wildcard;
}

$whereSql = 'WHERE ' . implode(' AND ', $whereClauses);

// 3. Compute Summary Aggregates
$aggSql = "
    SELECT COUNT(*) AS total_count,
           COALESCE(SUM(li.installment_amount), 0) AS sum_total_due,
           COALESCE(SUM(li.paid_amount), 0) AS sum_paid,
           COALESCE(SUM(li.remaining_amount), 0) AS sum_remaining
    FROM loan_installments li
    JOIN loans l ON li.loan_id = l.id
    JOIN customers c ON l.customer_id = c.id
    {$whereSql}
";
$aggStmt = $db->prepare($aggSql);
$aggStmt->execute($params);
$agg = $aggStmt->fetch();

$totalRecords   = (int)($agg['total_count'] ?? 0);
$totalScheduled = (float)($agg['sum_total_due'] ?? 0.0);
$totalPaid      = (float)($agg['sum_paid'] ?? 0.0);
$totalOverdue   = (float)($agg['sum_remaining'] ?? 0.0);
$totalPages     = max(1, ceil($totalRecords / $perPage));

if ($page > $totalPages && $totalRecords > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// 4. Fetch Paginated Records
$selectSql = "
    SELECT li.*, 
           DATEDIFF(:today_calc, li.due_date) AS days_overdue,
           l.loan_number, 
           c.id AS customer_id, c.customer_code, c.full_name AS customer_name, c.phone AS customer_phone,
           lp.name AS product_name
    FROM loan_installments li
    JOIN loans l ON li.loan_id = l.id
    JOIN customers c ON l.customer_id = c.id
    LEFT JOIN loan_products lp ON l.loan_product_id = lp.id
    {$whereSql}
    ORDER BY li.due_date ASC
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

// Fetch products for filter
$products = $db->query("SELECT id, name FROM loan_products ORDER BY name ASC")->fetchAll();

// Build Query String for Export & Print
$filterParams = http_build_query([
    'report'     => 'overdue',
    'product_id' => $productId,
    'aging'      => $agingBand,
    'search'     => $search,
]);

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/reports/index.php'); ?>" class="text-decoration-none text-muted">Reports</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Overdue Report</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h2 class="h4 fw-bold text-dark mb-0">Overdue Delinquency Report</h2>
            <span class="badge bg-danger"><?php echo number_format($totalRecords); ?> Delinquent</span>
        </div>
    </div>

    <!-- Action Toolbar -->
    <div class="d-flex flex-wrap gap-2">
        <a href="<?php echo url('modules/reports/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Reports Dashboard
        </a>
        <a href="<?php echo url('modules/reports/export-csv.php?' . $filterParams); ?>" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
        </a>
        <a href="<?php echo url('modules/reports/print.php?' . $filterParams); ?>" target="_blank" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-printer me-1"></i> Print Report
        </a>
    </div>
</div>

<!-- Filters Toolbar Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body p-3">
        <form action="<?php echo url('modules/reports/overdue-report.php'); ?>" method="GET" class="row g-2 align-items-center">
            <!-- Search -->
            <div class="col-12 col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search loan #, customer..." value="<?php echo e($search); ?>">
            </div>

            <!-- Aging Band -->
            <div class="col-6 col-md-3">
                <select name="aging" class="form-select form-select-sm">
                    <option value="all" <?php echo $agingBand === 'all' ? 'selected' : ''; ?>>All Delinquency Aging</option>
                    <option value="1_30" <?php echo $agingBand === '1_30' ? 'selected' : ''; ?>>1 – 30 Days Overdue</option>
                    <option value="31_60" <?php echo $agingBand === '31_60' ? 'selected' : ''; ?>>31 – 60 Days Overdue</option>
                    <option value="61_90" <?php echo $agingBand === '61_90' ? 'selected' : ''; ?>>61 – 90 Days Overdue</option>
                    <option value="90_plus" <?php echo $agingBand === '90_plus' ? 'selected' : ''; ?>>90+ Days (Severe)</option>
                </select>
            </div>

            <!-- Product Filter -->
            <div class="col-6 col-md-3">
                <select name="product_id" class="form-select form-select-sm">
                    <option value="0">All Products</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?php echo (int)$p['id']; ?>" <?php echo $productId === (int)$p['id'] ? 'selected' : ''; ?>>
                            <?php echo e($p['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filter Buttons -->
            <div class="col-12 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1" title="Apply Filter">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <?php if ($search !== '' || $agingBand !== 'all' || $productId > 0): ?>
                    <a href="<?php echo url('modules/reports/overdue-report.php'); ?>" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Report Summary KPI Strip -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card h-100 mb-0 shadow-sm border-0 bg-light">
            <div class="card-body p-3">
                <span class="text-muted small text-uppercase d-block">Overdue Installments</span>
                <span class="h5 fw-bold text-dark mb-0"><?php echo number_format($totalRecords); ?></span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card h-100 mb-0 shadow-sm border-0 bg-light">
            <div class="card-body p-3">
                <span class="text-muted small text-uppercase d-block">Partial Recoveries Received</span>
                <span class="h5 fw-bold text-success font-monospace mb-0"><?php echo format_currency($totalPaid); ?></span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-12 col-xl-4">
        <div class="card h-100 mb-0 shadow-sm border-0 bg-light">
            <div class="card-body p-3">
                <span class="text-muted small text-uppercase d-block">Net Overdue Delinquency</span>
                <span class="h5 fw-bold text-danger font-monospace mb-0"><?php echo format_currency($totalOverdue); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Report Data Table Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3 py-3">Loan Account</th>
                        <th class="py-3">Borrower Details</th>
                        <th class="py-3">Product</th>
                        <th class="py-3 text-center">Installment #</th>
                        <th class="py-3">Due Date</th>
                        <th class="py-3 text-center">Days Overdue</th>
                        <th class="py-3 text-end">Scheduled Due</th>
                        <th class="py-3 text-end">Paid Amount</th>
                        <th class="py-3 text-end">Overdue Balance</th>
                        <th class="pe-3 py-3 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($overdueList)): ?>
                        <?php foreach ($overdueList as $row): ?>
                            <tr>
                                <td class="ps-3 font-monospace fw-bold">
                                    <a href="<?php echo url('modules/repayments/view.php?loan_id=' . $row['loan_id']); ?>" class="text-decoration-none text-primary">
                                        <?php echo e($row['loan_number']); ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="<?php echo url('modules/customers/view.php?id=' . $row['customer_id']); ?>" class="text-decoration-none text-dark fw-semibold">
                                        <?php echo e($row['customer_name']); ?>
                                    </a>
                                    <div class="small text-muted font-monospace"><?php echo e($row['customer_phone']); ?></div>
                                </td>
                                <td class="small text-dark fw-semibold"><?php echo e($row['product_name'] ?? 'Product'); ?></td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">#<?php echo $row['installment_number']; ?></span>
                                </td>
                                <td class="text-danger fw-semibold text-nowrap"><?php echo date('M d, Y', strtotime($row['due_date'])); ?></td>
                                <td class="text-center">
                                    <span class="badge bg-danger"><?php echo $row['days_overdue']; ?> Days Late</span>
                                </td>
                                <td class="text-end font-monospace text-dark"><?php echo format_currency($row['installment_amount']); ?></td>
                                <td class="text-end font-monospace text-success"><?php echo format_currency($row['paid_amount']); ?></td>
                                <td class="text-end font-monospace fw-bold text-danger"><?php echo format_currency($row['remaining_amount']); ?></td>
                                <td class="pe-3 text-end">
                                    <a href="<?php echo url('modules/repayments/view.php?loan_id=' . $row['loan_id']); ?>" class="btn btn-sm btn-outline-secondary" title="View Repayment Ledger">
                                        <i class="bi bi-wallet2"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-shield-check fs-2 text-success d-block mb-2"></i>
                                <h5 class="fw-bold text-dark">No overdue installments found</h5>
                                <p class="small mb-0">No delinquent records matched your selected criteria.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($overdueList)): ?>
                    <tfoot class="table-light fw-bold" style="border-top: 2px solid #cbd5e1;">
                        <tr>
                            <td colspan="6" class="ps-3 py-3 text-dark text-uppercase small">Grand Total Overdue Exposure:</td>
                            <td class="text-end py-3 text-dark font-monospace"><?php echo format_currency($totalScheduled); ?></td>
                            <td class="text-end py-3 text-success font-monospace"><?php echo format_currency($totalPaid); ?></td>
                            <td class="text-end py-3 text-danger font-monospace"><?php echo format_currency($totalOverdue); ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-body p-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <span class="small text-muted">
                Showing page <strong><?php echo $page; ?></strong> of <strong><?php echo $totalPages; ?></strong> (Total <?php echo number_format($totalRecords); ?> records)
            </span>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo url('modules/reports/overdue-report.php?page=' . ($page - 1) . '&' . $filterParams); ?>">Previous</a>
                </li>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo url('modules/reports/overdue-report.php?page=' . $p . '&' . $filterParams); ?>"><?php echo $p; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo url('modules/reports/overdue-report.php?page=' . ($page + 1) . '&' . $filterParams); ?>">Next</a>
                </li>
            </ul>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
