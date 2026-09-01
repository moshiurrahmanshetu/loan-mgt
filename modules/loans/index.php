<?php
/**
 * Loan Applications List View
 * Loan Management System (loan-mgt) - Phase 3
 */

$pageTitle = 'Loan Applications';
$activeNav = 'loans';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = get_db_connection();

// 1. Query Parameters
$search       = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 10;
$offset       = ($page - 1) * $perPage;

// 2. Build Where Filter
$whereClauses = [];
$params       = [];

if ($search !== '') {
    $whereClauses[] = '(l.loan_number LIKE :search_ln OR c.full_name LIKE :search_name OR c.phone LIKE :search_phone OR c.customer_code LIKE :search_code)';
    $wildcard = '%' . $search . '%';
    $params[':search_ln']    = $wildcard;
    $params[':search_name']  = $wildcard;
    $params[':search_phone'] = $wildcard;
    $params[':search_code']  = $wildcard;
}

$validStatuses = ['draft', 'pending', 'approved', 'active', 'rejected', 'cancelled'];
if (in_array($statusFilter, $validStatuses, true)) {
    $whereClauses[] = 'l.status = :status';
    $params[':status'] = $statusFilter;
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// 3. Count Total Records
$countSql = "SELECT COUNT(*) FROM loans l 
             JOIN customers c ON l.customer_id = c.id 
             JOIN loan_products lp ON l.loan_product_id = lp.id 
             {$whereSql}";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRecords / $perPage));

if ($page > $totalPages && $totalRecords > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// 4. Fetch Paginated Records
$selectSql = "SELECT l.*, 
                     c.customer_code, c.full_name AS customer_name, c.phone AS customer_phone,
                     lp.name AS product_name, lp.product_code,
                     u.name AS creator_name,
                     ua.name AS approver_name
              FROM loans l 
              JOIN customers c ON l.customer_id = c.id 
              JOIN loan_products lp ON l.loan_product_id = lp.id 
              LEFT JOIN users u ON l.created_by = u.id 
              LEFT JOIN users ua ON l.approved_by = ua.id 
              {$whereSql} 
              ORDER BY l.id DESC 
              LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($selectSql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$loans = $stmt->fetchAll();

function loans_pagination_url(int $p, string $search, string $status): string
{
    $query = ['page' => $p];
    if ($search !== '') {
        $query['search'] = $search;
    }
    if ($status !== 'all') {
        $query['status'] = $status;
    }
    return url('modules/loans/index.php?' . http_build_query($query));
}

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Page Header & Action Controls -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Loan Applications</li>
            </ol>
        </nav>
        <h2 class="h4 fw-bold text-dark mb-0">Loan Portfolio & Applications</h2>
    </div>

    <?php if (can_create_loans()): ?>
        <div>
            <a href="<?php echo url('modules/loans/create.php'); ?>" class="btn btn-primary d-inline-flex align-items-center">
                <i class="bi bi-plus-circle-fill me-2"></i> New Loan Application
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Search & Filter Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?php echo url('modules/loans/index.php'); ?>" class="row g-2 align-items-center">
            <!-- Search Keyword -->
            <div class="col-12 col-md-6 col-lg-5">
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="search" value="<?php echo e($search); ?>" placeholder="Search by loan #, customer name, phone, code...">
                </div>
            </div>

            <!-- Status Filter -->
            <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                <select name="status" class="form-select">
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending Review</option>
                    <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved (Ready)</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active (Disbursed)</option>
                    <option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>>Drafts</option>
                    <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="col-12 col-sm-6 col-md-3 col-lg-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1 flex-md-grow-0 px-4">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <?php if ($search !== '' || $statusFilter !== 'all'): ?>
                    <a href="<?php echo url('modules/loans/index.php'); ?>" class="btn btn-outline-secondary" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Loan Applications Data Table Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3 py-3" style="width: 120px;">Loan Number</th>
                        <th class="py-3">Borrower / Customer</th>
                        <th class="py-3">Product</th>
                        <th class="py-3 text-end">Requested Amount</th>
                        <th class="py-3">Term & Frequency</th>
                        <th class="py-3">Application Date</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="pe-3 py-3 text-end" style="min-width: 130px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($loans)): ?>
                        <?php foreach ($loans as $loan): ?>
                            <?php 
                                $appDate = date('M d, Y', strtotime($loan['application_date']));
                                $termDisplay = $loan['term'] . ' ' . ucfirst($loan['term_unit']);
                                $freqLabel = get_frequency_label($loan['repayment_frequency']);
                                $isEditable = can_edit_loan($loan);
                            ?>
                            <tr>
                                <td class="ps-3 fw-semibold">
                                    <a href="<?php echo url('modules/loans/view.php?id=' . $loan['id']); ?>" class="text-decoration-none font-monospace fw-bold text-primary">
                                        <?php echo e($loan['loan_number']); ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="<?php echo url('modules/customers/view.php?id=' . $loan['customer_id']); ?>" class="fw-semibold text-dark text-decoration-none d-block">
                                        <?php echo e($loan['customer_name']); ?>
                                    </a>
                                    <div class="small text-muted font-monospace">
                                        <?php echo e($loan['customer_code']); ?> &bull; <?php echo e($loan['customer_phone']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark d-block"><?php echo e($loan['product_name']); ?></span>
                                    <span class="small text-muted"><?php echo e(get_interest_method_label($loan['interest_method'])); ?> (<?php echo number_format($loan['interest_rate'], 2); ?>%)</span>
                                </td>
                                <td class="text-end fw-bold text-dark">
                                    <?php echo format_currency($loan['requested_amount']); ?>
                                    <div class="small text-muted fw-normal">
                                        Est. Payable: <?php echo format_currency($loan['estimated_total_payable']); ?>
                                    </div>
                                </td>
                                <td class="small">
                                    <span class="text-dark fw-semibold"><?php echo e($termDisplay); ?></span>
                                    <span class="text-muted d-block"><?php echo e($freqLabel); ?></span>
                                </td>
                                <td class="small text-muted">
                                    <?php echo e($appDate); ?>
                                    <div class="small text-muted">By: <?php echo e($loan['creator_name'] ?? 'System'); ?></div>
                                </td>
                                <td class="text-center">
                                    <?php echo get_loan_status_badge($loan['status']); ?>
                                </td>
                                <td class="pe-3 text-end text-nowrap">
                                    <div class="btn-group btn-group-sm" role="group" aria-label="Loan Actions">
                                        <a href="<?php echo url('modules/loans/view.php?id=' . $loan['id']); ?>" class="btn btn-outline-secondary" title="View Application" data-bs-toggle="tooltip">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <?php if ($loan['status'] === 'approved' && can_disburse_loans()): ?>
                                            <a href="<?php echo url('modules/loans/disburse.php?id=' . $loan['id']); ?>" class="btn btn-outline-primary" title="Disburse Loan" data-bs-toggle="tooltip">
                                                <i class="bi bi-cash-coin"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($loan['status'] === 'active'): ?>
                                            <a href="<?php echo url('modules/loans/schedule.php?id=' . $loan['id']); ?>" class="btn btn-outline-secondary" title="Repayment Schedule" data-bs-toggle="tooltip">
                                                <i class="bi bi-calendar-check"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($isEditable): ?>
                                            <a href="<?php echo url('modules/loans/edit.php?id=' . $loan['id']); ?>" class="btn btn-outline-secondary" title="Edit Application" data-bs-toggle="tooltip">
                                                <i class="bi bi-pencil"></i>
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
                                    <i class="bi bi-cash-stack text-muted display-6 d-block mb-3"></i>
                                    <h5 class="fw-bold text-dark">No loan applications found</h5>
                                    <p class="text-muted small mb-3">
                                        <?php if ($search !== '' || $statusFilter !== 'all'): ?>
                                            No loans match your search criteria. Try resetting filters.
                                        <?php else: ?>
                                            No loan applications have been submitted yet.
                                        <?php endif; ?>
                                    </p>
                                    <?php if ($search !== '' || $statusFilter !== 'all'): ?>
                                        <a href="<?php echo url('modules/loans/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Clear Filters
                                        </a>
                                    <?php elseif (can_create_loans()): ?>
                                        <a href="<?php echo url('modules/loans/create.php'); ?>" class="btn btn-primary btn-sm">
                                            <i class="bi bi-plus-circle-fill me-1"></i> New Loan Application
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

    <!-- Pagination Footer -->
    <?php if ($totalRecords > 0): ?>
        <div class="card-footer bg-white border-top py-3 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
            <div class="text-muted small">
                Showing <strong><?php echo number_format($offset + 1); ?></strong> to <strong><?php echo number_format(min($offset + $perPage, $totalRecords)); ?></strong> of <strong><?php echo number_format($totalRecords); ?></strong> applications
            </div>

            <?php if ($totalPages > 1): ?>
                <nav aria-label="Loans list pagination">
                    <ul class="pagination pagination-sm mb-0 justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo loans_pagination_url($page - 1, $search, $statusFilter); ?>" aria-label="Previous">&lsaquo;</a>
                        </li>
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo loans_pagination_url($p, $search, $statusFilter); ?>"><?php echo $p; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo loans_pagination_url($page + 1, $search, $statusFilter); ?>" aria-label="Next">&rsaquo;</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
