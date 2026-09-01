<?php
/**
 * Loan Disbursement Report
 * Loan Management System (loan-mgt) - Phase 6
 */

$pageTitle = 'Disbursement Report';
$activeNav = 'reports';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// 1. Role Guard
if (!can_access_report('disbursement')) {
    set_flash('danger', 'Unauthorized: You do not have permission to view the Disbursement Report.');
    redirect('modules/reports/index.php');
}

$db = get_db_connection();

// 2. Query Filters & Validation
$fromDate  = trim($_GET['from_date'] ?? '');
$toDate    = trim($_GET['to_date'] ?? '');
$method    = trim($_GET['method'] ?? 'all');
$disbBy    = (int)($_GET['disbursed_by'] ?? 0);
$search    = trim($_GET['search'] ?? '');
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 15;
$offset    = ($page - 1) * $perPage;

$dateError = null;
if (!empty($fromDate) && !empty($toDate)) {
    if (strtotime($fromDate) > strtotime($toDate)) {
        $dateError = 'Validation Error: "From Date" cannot be after "To Date".';
        $fromDate = '';
        $toDate = '';
    }
}

$whereClauses = [
    "l.status IN ('active', 'completed')",
    "l.disbursement_date IS NOT NULL"
];
$params = [];

if (!empty($fromDate) && strtotime($fromDate)) {
    $whereClauses[] = "l.disbursement_date >= :from_date";
    $params[':from_date'] = $fromDate;
}

if (!empty($toDate) && strtotime($toDate)) {
    $whereClauses[] = "l.disbursement_date <= :to_date";
    $params[':to_date'] = $toDate;
}

$validMethods = ['cash', 'bank_transfer', 'mobile_banking'];
if (in_array($method, $validMethods, true)) {
    $whereClauses[] = "l.disbursement_method = :method";
    $params[':method'] = $method;
}

if ($disbBy > 0) {
    $whereClauses[] = "l.disbursed_by = :disb_by";
    $params[':disb_by'] = $disbBy;
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
           COALESCE(SUM(l.disbursed_amount), 0) AS sum_disbursed
    FROM loans l
    JOIN customers c ON l.customer_id = c.id
    {$whereSql}
";
$aggStmt = $db->prepare($aggSql);
$aggStmt->execute($params);
$agg = $aggStmt->fetch();

$totalRecords   = (int)($agg['total_count'] ?? 0);
$totalDisbursed = (float)($agg['sum_disbursed'] ?? 0.0);
$totalPages     = max(1, ceil($totalRecords / $perPage));

if ($page > $totalPages && $totalRecords > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// 4. Fetch Paginated Records
$selectSql = "
    SELECT l.*, 
           c.id AS customer_id, c.customer_code, c.full_name AS customer_name, c.phone AS customer_phone,
           lp.name AS product_name,
           u.name AS disburser_name
    FROM loans l
    JOIN customers c ON l.customer_id = c.id
    LEFT JOIN loan_products lp ON l.loan_product_id = lp.id
    LEFT JOIN users u ON l.disbursed_by = u.id
    {$whereSql}
    ORDER BY l.disbursement_date DESC, l.id DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $db->prepare($selectSql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$disbursements = $stmt->fetchAll();

// Fetch disburser users
$disbUsers = $db->query("SELECT id, name FROM users WHERE role IN ('admin', 'manager') ORDER BY name ASC")->fetchAll();

// Build Query String for Export & Print
$filterParams = http_build_query([
    'report'       => 'disbursement',
    'from_date'    => $fromDate,
    'to_date'      => $toDate,
    'method'       => $method,
    'disbursed_by' => $disbBy,
    'search'       => $search,
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
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Disbursement Report</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h2 class="h4 fw-bold text-dark mb-0">Loan Capital Disbursement Report</h2>
            <span class="badge bg-light text-dark border"><?php echo number_format($totalRecords); ?> Records</span>
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

<?php if (!empty($dateError)): ?>
    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-exclamation-circle-fill me-2 fs-5"></i>
        <div><?php echo e($dateError); ?></div>
    </div>
<?php endif; ?>

<!-- Filters Toolbar Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body p-3">
        <form action="<?php echo url('modules/reports/disbursement-report.php'); ?>" method="GET" class="row g-2 align-items-center">
            <!-- Search -->
            <div class="col-12 col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search loan #, customer..." value="<?php echo e($search); ?>">
            </div>

            <!-- Method Filter -->
            <div class="col-6 col-md-2">
                <select name="method" class="form-select form-select-sm">
                    <option value="all" <?php echo $method === 'all' ? 'selected' : ''; ?>>All Channels</option>
                    <option value="cash" <?php echo $method === 'cash' ? 'selected' : ''; ?>>Cash</option>
                    <option value="bank_transfer" <?php echo $method === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                    <option value="mobile_banking" <?php echo $method === 'mobile_banking' ? 'selected' : ''; ?>>Mobile Banking</option>
                </select>
            </div>

            <!-- Disbursed By User -->
            <div class="col-6 col-md-2">
                <select name="disbursed_by" class="form-select form-select-sm">
                    <option value="0">All Officers</option>
                    <?php foreach ($disbUsers as $u): ?>
                        <option value="<?php echo (int)$u['id']; ?>" <?php echo $disbBy === (int)$u['id'] ? 'selected' : ''; ?>>
                            <?php echo e($u['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Dates -->
            <div class="col-6 col-md-2">
                <input type="date" name="from_date" class="form-control form-control-sm" value="<?php echo e($fromDate); ?>" placeholder="From Date" title="From Date">
            </div>
            <div class="col-6 col-md-2">
                <input type="date" name="to_date" class="form-control form-control-sm" value="<?php echo e($toDate); ?>" placeholder="To Date" title="To Date">
            </div>

            <!-- Filter Buttons -->
            <div class="col-12 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1" title="Apply Filter">
                    <i class="bi bi-funnel"></i>
                </button>
                <?php if ($search !== '' || $method !== 'all' || $disbBy > 0 || $fromDate !== '' || $toDate !== ''): ?>
                    <a href="<?php echo url('modules/reports/disbursement-report.php'); ?>" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Report Summary KPI Strip -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6">
        <div class="card h-100 mb-0 shadow-sm border-0 bg-light">
            <div class="card-body p-3">
                <span class="text-muted small text-uppercase d-block">Number of Disbursements</span>
                <span class="h5 fw-bold text-dark mb-0"><?php echo number_format($totalRecords); ?></span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <div class="card h-100 mb-0 shadow-sm border-0 bg-light">
            <div class="card-body p-3">
                <span class="text-muted small text-uppercase d-block">Total Capital Disbursed</span>
                <span class="h5 fw-bold text-primary font-monospace mb-0"><?php echo format_currency($totalDisbursed); ?></span>
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
                        <th class="ps-3 py-3">Disbursement Date</th>
                        <th class="py-3">Loan Account</th>
                        <th class="py-3">Borrower Details</th>
                        <th class="py-3">Product</th>
                        <th class="py-3 text-end">Disbursed Amount</th>
                        <th class="py-3">Payment Channel</th>
                        <th class="py-3">Authorized By</th>
                        <th class="pe-3 py-3 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($disbursements)): ?>
                        <?php foreach ($disbursements as $row): ?>
                            <tr>
                                <td class="ps-3 fw-semibold text-dark text-nowrap">
                                    <?php echo date('M d, Y', strtotime($row['disbursement_date'])); ?>
                                </td>
                                <td class="font-monospace fw-bold">
                                    <a href="<?php echo url('modules/loans/view.php?id=' . $row['id']); ?>" class="text-decoration-none text-primary">
                                        <?php echo e($row['loan_number']); ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="<?php echo url('modules/customers/view.php?id=' . $row['customer_id']); ?>" class="text-decoration-none text-dark fw-semibold">
                                        <?php echo e($row['customer_name']); ?>
                                    </a>
                                    <div class="small text-muted font-monospace"><?php echo e($row['customer_code']); ?></div>
                                </td>
                                <td class="small text-dark fw-semibold"><?php echo e($row['product_name'] ?? 'Product'); ?></td>
                                <td class="text-end fw-bold font-monospace text-dark"><?php echo format_currency($row['disbursed_amount']); ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?php echo e(get_disbursement_method_label($row['disbursement_method'] ?? 'cash')); ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?php echo e($row['disburser_name'] ?? 'System Officer'); ?></td>
                                <td class="pe-3 text-end">
                                    <a href="<?php echo url('modules/loans/view.php?id=' . $row['id']); ?>" class="btn btn-sm btn-outline-secondary" title="View Loan File">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-journal-x fs-3 d-block mb-2 text-muted"></i>
                                <h5 class="fw-bold text-dark">No disbursement records found</h5>
                                <p class="small mb-0">No records matched your specified search filter criteria.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($disbursements)): ?>
                    <tfoot class="table-light fw-bold" style="border-top: 2px solid #cbd5e1;">
                        <tr>
                            <td colspan="4" class="ps-3 py-3 text-dark text-uppercase small">Grand Total Disbursed:</td>
                            <td class="text-end py-3 text-primary font-monospace"><?php echo format_currency($totalDisbursed); ?></td>
                            <td colspan="3"></td>
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
                    <a class="page-link" href="<?php echo url('modules/reports/disbursement-report.php?page=' . ($page - 1) . '&' . $filterParams); ?>">Previous</a>
                </li>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo url('modules/reports/disbursement-report.php?page=' . $p . '&' . $filterParams); ?>"><?php echo $p; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo url('modules/reports/disbursement-report.php?page=' . ($page + 1) . '&' . $filterParams); ?>">Next</a>
                </li>
            </ul>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
