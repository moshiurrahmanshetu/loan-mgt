<?php
/**
 * Customer Summary & Portfolio Performance Report
 * Loan Management System (loan-mgt) - Phase 6
 */

$pageTitle = 'Customer Summary Report';
$activeNav = 'reports';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// 1. Role Guard
if (!can_access_report('customer')) {
    set_flash('danger', 'Unauthorized: You do not have permission to view the Customer Summary Report.');
    redirect('modules/reports/index.php');
}

$db = get_db_connection();

// 2. Query Filters & Validation
$status   = trim($_GET['status'] ?? 'all');
$activity = trim($_GET['activity'] ?? 'all');
$search   = trim($_GET['search'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 15;
$offset   = ($page - 1) * $perPage;

$whereClauses = [];
$params       = [];

if (in_array($status, ['active', 'inactive'], true)) {
    $whereClauses[] = "c.status = :status";
    $params[':status'] = $status;
}

if ($activity === 'with_loans') {
    $whereClauses[] = "EXISTS (SELECT 1 FROM loans l WHERE l.customer_id = c.id)";
} elseif ($activity === 'with_active_loans') {
    $whereClauses[] = "EXISTS (SELECT 1 FROM loans l WHERE l.customer_id = c.id AND l.status = 'active')";
}

if ($search !== '') {
    $whereClauses[] = "(c.customer_code LIKE :s_code OR c.full_name LIKE :s_name OR c.phone LIKE :s_phone OR c.email LIKE :s_email OR c.city LIKE :s_city)";
    $wildcard = '%' . $search . '%';
    $params[':s_code']  = $wildcard;
    $params[':s_name']  = $wildcard;
    $params[':s_phone'] = $wildcard;
    $params[':s_email'] = $wildcard;
    $params[':s_city']  = $wildcard;
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// 3. Count Total Records
$countSql = "SELECT COUNT(*) FROM customers c {$whereSql}";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages   = max(1, ceil($totalRecords / $perPage));

if ($page > $totalPages && $totalRecords > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// 4. Fetch Paginated Customer Records with Aggregates
$selectSql = "
    SELECT c.id, c.customer_code, c.full_name, c.phone, c.email, c.city, c.status,
           COUNT(l.id) AS total_loans,
           COUNT(CASE WHEN l.status = 'active' THEN 1 END) AS active_loans,
           COUNT(CASE WHEN l.status = 'completed' THEN 1 END) AS completed_loans,
           COALESCE(SUM(l.disbursed_amount), 0) AS total_borrowed,
           (SELECT COALESCE(SUM(p.amount), 0) FROM loan_payments p WHERE p.customer_id = c.id) AS total_paid,
           (SELECT COALESCE(SUM(li.remaining_amount), 0) 
            FROM loan_installments li 
            JOIN loans l2 ON li.loan_id = l2.id 
            WHERE l2.customer_id = c.id AND l2.status = 'active' AND li.remaining_amount > 0) AS outstanding_balance
    FROM customers c
    LEFT JOIN loans l ON c.id = l.customer_id
    {$whereSql}
    GROUP BY c.id
    ORDER BY c.id DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $db->prepare($selectSql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$customers = $stmt->fetchAll();

// 5. Global Financial Totals for Filtered Set
$globalTotals = $db->query("
    SELECT 
        (SELECT COALESCE(SUM(disbursed_amount), 0) FROM loans WHERE status IN ('active', 'completed')) AS total_borrowed_sum,
        (SELECT COALESCE(SUM(amount), 0) FROM loan_payments) AS total_paid_sum,
        (SELECT COALESCE(SUM(li.remaining_amount), 0) FROM loan_installments li JOIN loans l ON li.loan_id = l.id WHERE l.status = 'active' AND li.remaining_amount > 0) AS total_outstanding_sum
")->fetch();

// Build Query String for Export & Print
$filterParams = http_build_query([
    'report'   => 'customer',
    'status'   => $status,
    'activity' => $activity,
    'search'   => $search,
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
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Customer Summary Report</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h2 class="h4 fw-bold text-dark mb-0">Customer Summary & Portfolio Report</h2>
            <span class="badge bg-light text-dark border"><?php echo number_format($totalRecords); ?> Customers</span>
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
        <form action="<?php echo url('modules/reports/customer-report.php'); ?>" method="GET" class="row g-2 align-items-center">
            <!-- Search -->
            <div class="col-12 col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search code, name, phone, city..." value="<?php echo e($search); ?>">
            </div>

            <!-- KYC Status -->
            <div class="col-6 col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All KYC Statuses</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active Status</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive Status</option>
                </select>
            </div>

            <!-- Loan Activity -->
            <div class="col-6 col-md-3">
                <select name="activity" class="form-select form-select-sm">
                    <option value="all" <?php echo $activity === 'all' ? 'selected' : ''; ?>>All Borrowers</option>
                    <option value="with_loans" <?php echo $activity === 'with_loans' ? 'selected' : ''; ?>>With Any Loan History</option>
                    <option value="with_active_loans" <?php echo $activity === 'with_active_loans' ? 'selected' : ''; ?>>With Active Outstanding Loans</option>
                </select>
            </div>

            <!-- Filter Buttons -->
            <div class="col-12 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1" title="Apply Filter">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <?php if ($search !== '' || $status !== 'all' || $activity !== 'all'): ?>
                    <a href="<?php echo url('modules/reports/customer-report.php'); ?>" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
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
                <span class="text-muted small text-uppercase d-block">Total Filtered Borrowers</span>
                <span class="h5 fw-bold text-dark mb-0"><?php echo number_format($totalRecords); ?></span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card h-100 mb-0 shadow-sm border-0 bg-light">
            <div class="card-body p-3">
                <span class="text-muted small text-uppercase d-block">Overall Lifetime Borrowed</span>
                <span class="h5 fw-bold text-primary font-monospace mb-0"><?php echo format_currency($globalTotals['total_borrowed_sum']); ?></span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-12 col-xl-4">
        <div class="card h-100 mb-0 shadow-sm border-0 bg-light">
            <div class="card-body p-3">
                <span class="text-muted small text-uppercase d-block">Total Outstanding Portfolio</span>
                <span class="h5 fw-bold text-danger font-monospace mb-0"><?php echo format_currency($globalTotals['total_outstanding_sum']); ?></span>
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
                        <th class="ps-3 py-3">Customer Code</th>
                        <th class="py-3">Borrower Details</th>
                        <th class="py-3">Location</th>
                        <th class="py-3 text-center">Total Loans</th>
                        <th class="py-3 text-center">Active Loans</th>
                        <th class="py-3 text-center">Completed</th>
                        <th class="py-3 text-end">Total Disbursed</th>
                        <th class="py-3 text-end">Total Repaid</th>
                        <th class="py-3 text-end">Outstanding Balance</th>
                        <th class="pe-3 py-3 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($customers)): ?>
                        <?php foreach ($customers as $row): ?>
                            <tr>
                                <td class="ps-3 font-monospace fw-bold">
                                    <a href="<?php echo url('modules/customers/view.php?id=' . $row['id']); ?>" class="text-decoration-none text-primary">
                                        <?php echo e($row['customer_code']); ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="<?php echo url('modules/customers/view.php?id=' . $row['id']); ?>" class="text-decoration-none text-dark fw-semibold">
                                        <?php echo e($row['full_name']); ?>
                                    </a>
                                    <div class="small text-muted font-monospace"><?php echo e($row['phone']); ?></div>
                                </td>
                                <td class="small text-muted"><?php echo e($row['city'] ?: '—'); ?></td>
                                <td class="text-center fw-semibold"><?php echo (int)$row['total_loans']; ?></td>
                                <td class="text-center">
                                    <?php if ((int)$row['active_loans'] > 0): ?>
                                        <span class="badge bg-primary"><?php echo (int)$row['active_loans']; ?> Active</span>
                                    <?php else: ?>
                                        <span class="text-muted small">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ((int)$row['completed_loans'] > 0): ?>
                                        <span class="badge bg-success"><?php echo (int)$row['completed_loans']; ?> Settled</span>
                                    <?php else: ?>
                                        <span class="text-muted small">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end font-monospace"><?php echo format_currency($row['total_borrowed']); ?></td>
                                <td class="text-end font-monospace text-success"><?php echo format_currency($row['total_paid']); ?></td>
                                <td class="text-end font-monospace fw-bold <?php echo (float)$row['outstanding_balance'] > 0 ? 'text-danger' : 'text-muted'; ?>">
                                    <?php echo format_currency($row['outstanding_balance']); ?>
                                </td>
                                <td class="pe-3 text-end">
                                    <a href="<?php echo url('modules/customers/view.php?id=' . $row['id']); ?>" class="btn btn-sm btn-outline-secondary" title="View Customer Profile">
                                        <i class="bi bi-person-badge"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-person-x fs-3 d-block mb-2 text-muted"></i>
                                <h5 class="fw-bold text-dark">No customer records found</h5>
                                <p class="small mb-0">No records matched your specified search filter criteria.</p>
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
                Showing page <strong><?php echo $page; ?></strong> of <strong><?php echo $totalPages; ?></strong> (Total <?php echo number_format($totalRecords); ?> customers)
            </span>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo url('modules/reports/customer-report.php?page=' . ($page - 1) . '&' . $filterParams); ?>">Previous</a>
                </li>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo url('modules/reports/customer-report.php?page=' . $p . '&' . $filterParams); ?>"><?php echo $p; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo url('modules/reports/customer-report.php?page=' . ($page + 1) . '&' . $filterParams); ?>">Next</a>
                </li>
            </ul>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
