<?php
/**
 * Loan Applications & Portfolio Report
 * Loan Management System (loan-mgt) - Phase 6
 */

$pageTitle = 'Loan Applications Report';
$activeNav = 'reports';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// 1. Role Guard
if (!can_access_report('loan')) {
    set_flash('danger', 'Unauthorized: You do not have permission to view the Loan Report.');
    redirect('modules/reports/index.php');
}

$db = get_db_connection();

// 2. Query Filters & Validation
$fromDate  = trim($_GET['from_date'] ?? '');
$toDate    = trim($_GET['to_date'] ?? '');
$status    = trim($_GET['status'] ?? 'all');
$productId = (int)($_GET['product_id'] ?? 0);
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

$whereClauses = [];
$params       = [];

if (!empty($fromDate) && strtotime($fromDate)) {
    $whereClauses[] = "l.application_date >= :from_date";
    $params[':from_date'] = $fromDate;
}

if (!empty($toDate) && strtotime($toDate)) {
    $whereClauses[] = "l.application_date <= :to_date";
    $params[':to_date'] = $toDate;
}

$validStatuses = ['draft', 'pending', 'approved', 'active', 'completed', 'rejected', 'cancelled'];
if (in_array($status, $validStatuses, true)) {
    $whereClauses[] = "l.status = :status";
    $params[':status'] = $status;
}

if ($productId > 0) {
    $whereClauses[] = "l.loan_product_id = :product_id";
    $params[':product_id'] = $productId;
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

// 3. Compute Non-Duplicated Summary Aggregates
$aggSql = "
    SELECT COUNT(*) AS total_count,
           COALESCE(SUM(l.requested_amount), 0) AS sum_principal,
           COALESCE(SUM(l.estimated_interest_amount), 0) AS sum_interest,
           COALESCE(SUM(l.estimated_total_payable), 0) AS sum_payable
    FROM loans l
    JOIN customers c ON l.customer_id = c.id
    {$whereSql}
";
$aggStmt = $db->prepare($aggSql);
$aggStmt->execute($params);
$agg = $aggStmt->fetch();

$totalRecords   = (int)($agg['total_count'] ?? 0);
$totalPrincipal = (float)($agg['sum_principal'] ?? 0.0);
$totalInterest  = (float)($agg['sum_interest'] ?? 0.0);
$totalPayable   = (float)($agg['sum_payable'] ?? 0.0);
$totalPages     = max(1, ceil($totalRecords / $perPage));

if ($page > $totalPages && $totalRecords > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// 4. Fetch Paginated Loan Records
$selectSql = "
    SELECT l.*, 
           c.id AS customer_id, c.customer_code, c.full_name AS customer_name, c.phone AS customer_phone,
           lp.name AS product_name
    FROM loans l
    JOIN customers c ON l.customer_id = c.id
    LEFT JOIN loan_products lp ON l.loan_product_id = lp.id
    {$whereSql}
    ORDER BY l.application_date DESC, l.id DESC
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

// Fetch products for filter dropdown
$products = $db->query("SELECT id, name FROM loan_products ORDER BY name ASC")->fetchAll();

// Build Query String for Export & Print
$filterParams = http_build_query([
    'report'     => 'loan',
    'from_date'  => $fromDate,
    'to_date'    => $toDate,
    'status'     => $status,
    'product_id' => $productId,
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
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Loan Applications Report</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h2 class="h4 fw-bold text-dark mb-0">Loan Applications & Portfolio Report</h2>
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
        <form action="<?php echo url('modules/reports/loan-report.php'); ?>" method="GET" class="row g-2 align-items-center">
            <!-- Search -->
            <div class="col-12 col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search loan #, customer..." value="<?php echo e($search); ?>">
            </div>

            <!-- Status Filter -->
            <div class="col-6 col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>

            <!-- Product Filter -->
            <div class="col-6 col-md-2">
                <select name="product_id" class="form-select form-select-sm">
                    <option value="0">All Products</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?php echo (int)$p['id']; ?>" <?php echo $productId === (int)$p['id'] ? 'selected' : ''; ?>>
                            <?php echo e($p['name']); ?>
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
                <?php if ($search !== '' || $status !== 'all' || $productId > 0 || $fromDate !== '' || $toDate !== ''): ?>
                    <a href="<?php echo url('modules/reports/loan-report.php'); ?>" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Report Summary KPI Strip -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card h-100 mb-0 shadow-sm border-0 bg-light">
            <div class="card-body p-3">
                <span class="text-muted small text-uppercase d-block">Filtered Loans</span>
                <span class="h5 fw-bold text-dark mb-0"><?php echo number_format($totalRecords); ?></span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card h-100 mb-0 shadow-sm border-0 bg-light">
            <div class="card-body p-3">
                <span class="text-muted small text-uppercase d-block">Total Principal</span>
                <span class="h5 fw-bold text-primary font-monospace mb-0"><?php echo format_currency($totalPrincipal); ?></span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card h-100 mb-0 shadow-sm border-0 bg-light">
            <div class="card-body p-3">
                <span class="text-muted small text-uppercase d-block">Expected Interest</span>
                <span class="h5 fw-bold text-dark font-monospace mb-0"><?php echo format_currency($totalInterest); ?></span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card h-100 mb-0 shadow-sm border-0 bg-light">
            <div class="card-body p-3">
                <span class="text-muted small text-uppercase d-block">Total Contract Value</span>
                <span class="h5 fw-bold text-success font-monospace mb-0"><?php echo format_currency($totalPayable); ?></span>
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
                        <th class="py-3 text-end">Principal</th>
                        <th class="py-3 text-end">Interest</th>
                        <th class="py-3 text-end">Total Payable</th>
                        <th class="py-3">Term & Frequency</th>
                        <th class="py-3">App Date</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="pe-3 py-3 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($loans)): ?>
                        <?php foreach ($loans as $row): ?>
                            <tr>
                                <td class="ps-3 fw-semibold font-monospace">
                                    <a href="<?php echo url('modules/loans/view.php?id=' . $row['id']); ?>" class="text-decoration-none text-primary">
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
                                <td class="text-end fw-bold font-monospace text-dark"><?php echo format_currency($row['requested_amount']); ?></td>
                                <td class="text-end font-monospace text-muted"><?php echo format_currency($row['estimated_interest_amount']); ?></td>
                                <td class="text-end font-monospace fw-bold text-success"><?php echo format_currency($row['estimated_total_payable']); ?></td>
                                <td class="small text-muted"><?php echo (int)$row['term'] . ' ' . ucfirst($row['term_unit']); ?> &bull; <?php echo ucfirst($row['repayment_frequency']); ?></td>
                                <td class="small text-nowrap"><?php echo date('M d, Y', strtotime($row['application_date'])); ?></td>
                                <td class="text-center"><?php echo get_loan_status_badge($row['status']); ?></td>
                                <td class="pe-3 text-end">
                                    <a href="<?php echo url('modules/loans/view.php?id=' . $row['id']); ?>" class="btn btn-sm btn-outline-secondary" title="View Loan File">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-journal-x fs-3 d-block mb-2 text-muted"></i>
                                <h5 class="fw-bold text-dark">No loan records found</h5>
                                <p class="small mb-0">No loan records matched your specified search filter criteria.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($loans)): ?>
                    <tfoot class="table-light fw-bold" style="border-top: 2px solid #cbd5e1;">
                        <tr>
                            <td colspan="3" class="ps-3 py-3 text-dark text-uppercase small">Grand Totals (Filtered Dataset):</td>
                            <td class="text-end py-3 text-dark font-monospace"><?php echo format_currency($totalPrincipal); ?></td>
                            <td class="text-end py-3 text-muted font-monospace"><?php echo format_currency($totalInterest); ?></td>
                            <td class="text-end py-3 text-success font-monospace"><?php echo format_currency($totalPayable); ?></td>
                            <td colspan="4"></td>
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
                    <a class="page-link" href="<?php echo url('modules/reports/loan-report.php?page=' . ($page - 1) . '&' . $filterParams); ?>">Previous</a>
                </li>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo url('modules/reports/loan-report.php?page=' . $p . '&' . $filterParams); ?>"><?php echo $p; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo url('modules/reports/loan-report.php?page=' . ($page + 1) . '&' . $filterParams); ?>">Next</a>
                </li>
            </ul>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
