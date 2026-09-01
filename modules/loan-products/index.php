<?php
/**
 * Loan Products List View
 * Loan Management System (loan-mgt) - Phase 3
 */

$pageTitle = 'Loan Products';
$activeNav = 'loan-products';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = get_db_connection();

// 1. Sanitize query parameters
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// 2. Build filter conditions
$whereClauses = [];
$params = [];

if ($search !== '') {
    $whereClauses[] = '(lp.product_code LIKE :search_code OR lp.name LIKE :search_name OR lp.description LIKE :search_desc)';
    $wildcard = '%' . $search . '%';
    $params[':search_code'] = $wildcard;
    $params[':search_name'] = $wildcard;
    $params[':search_desc'] = $wildcard;
}

if (in_array($statusFilter, ['active', 'inactive'], true)) {
    $whereClauses[] = 'lp.status = :status';
    $params[':status'] = $statusFilter;
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// 3. Total count
$countSql = "SELECT COUNT(*) FROM loan_products lp {$whereSql}";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRecords / $perPage));

if ($page > $totalPages && $totalRecords > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// 4. Fetch paginated records with active loans count
$selectSql = "SELECT lp.*, COUNT(l.id) AS total_loans 
              FROM loan_products lp 
              LEFT JOIN loans l ON lp.id = l.loan_product_id 
              {$whereSql} 
              GROUP BY lp.id 
              ORDER BY lp.id DESC 
              LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($selectSql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

function product_pagination_url(int $p, string $search, string $status): string
{
    $query = ['page' => $p];
    if ($search !== '') {
        $query['search'] = $search;
    }
    if ($status !== 'all') {
        $query['status'] = $status;
    }
    return url('modules/loan-products/index.php?' . http_build_query($query));
}

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Page Header & Action Controls -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Loan Products</li>
            </ol>
        </nav>
        <h2 class="h4 fw-bold text-dark mb-0">Loan Products Catalog</h2>
    </div>

    <?php if (can_manage_loan_products()): ?>
        <div>
            <a href="<?php echo url('modules/loan-products/create.php'); ?>" class="btn btn-primary d-inline-flex align-items-center">
                <i class="bi bi-plus-circle-fill me-2"></i> Create Loan Product
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Search & Filter Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?php echo url('modules/loan-products/index.php'); ?>" class="row g-2 align-items-center">
            <!-- Search Keyword -->
            <div class="col-12 col-md-6 col-lg-5">
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="search" value="<?php echo e($search); ?>" placeholder="Search by product name, code, description...">
                </div>
            </div>

            <!-- Status Filter -->
            <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                <select name="status" class="form-select">
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active Products</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive Products</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="col-12 col-sm-6 col-md-3 col-lg-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1 flex-md-grow-0 px-4">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <?php if ($search !== '' || $statusFilter !== 'all'): ?>
                    <a href="<?php echo url('modules/loan-products/index.php'); ?>" class="btn btn-outline-secondary" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Loan Products Data Table Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3 py-3" style="width: 110px;">Code</th>
                        <th class="py-3">Product Name</th>
                        <th class="py-3">Amount Range</th>
                        <th class="py-3">Interest Rate & Method</th>
                        <th class="py-3">Term & Frequency</th>
                        <th class="py-3 text-center">Processing Fee</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="pe-3 py-3 text-end" style="min-width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $product): ?>
                            <?php 
                                $isActive = ($product['status'] === 'active');
                                $statusBadge = $isActive ? 'badge-status-active' : 'badge-status-inactive';
                                $methodLabel = get_interest_method_label($product['interest_method']);
                                $freqLabel = get_frequency_label($product['repayment_frequency']);
                                $termDisplay = $product['term_min'] . ' – ' . $product['term_max'] . ' ' . ucfirst($product['term_unit']);
                                $amountRange = format_currency($product['minimum_amount']) . ' – ' . format_currency($product['maximum_amount']);
                            ?>
                            <tr>
                                <td class="ps-3 fw-semibold">
                                    <span class="badge bg-light text-dark border font-monospace"><?php echo e($product['product_code']); ?></span>
                                </td>
                                <td>
                                    <a href="<?php echo url('modules/loan-products/view.php?id=' . $product['id']); ?>" class="fw-semibold text-dark text-decoration-none d-block">
                                        <?php echo e($product['name']); ?>
                                    </a>
                                    <?php if (!empty($product['description'])): ?>
                                        <div class="small text-muted text-truncate" style="max-width: 260px;">
                                            <?php echo e($product['description']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="small fw-semibold text-dark text-nowrap">
                                    <?php echo e($amountRange); ?>
                                </td>
                                <td class="small">
                                    <span class="fw-bold text-primary"><?php echo number_format($product['interest_rate'], 2); ?>%</span>
                                    <span class="text-muted d-block"><?php echo e($methodLabel); ?></span>
                                </td>
                                <td class="small">
                                    <span class="text-dark fw-semibold"><?php echo e($termDisplay); ?></span>
                                    <span class="text-muted d-block"><?php echo e($freqLabel); ?></span>
                                </td>
                                <td class="text-center small fw-semibold text-dark">
                                    <?php echo number_format($product['processing_fee'], 2); ?>%
                                </td>
                                <td class="text-center">
                                    <span class="badge <?php echo $statusBadge; ?>">
                                        <?php echo e(ucfirst($product['status'])); ?>
                                    </span>
                                </td>
                                <td class="pe-3 text-end text-nowrap">
                                    <div class="btn-group btn-group-sm" role="group" aria-label="Product Actions">
                                        <a href="<?php echo url('modules/loan-products/view.php?id=' . $product['id']); ?>" class="btn btn-outline-secondary" title="View Product Rules" data-bs-toggle="tooltip">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <?php if (can_manage_loan_products()): ?>
                                            <a href="<?php echo url('modules/loan-products/edit.php?id=' . $product['id']); ?>" class="btn btn-outline-secondary" title="Edit Product" data-bs-toggle="tooltip">
                                                <i class="bi bi-pencil"></i>
                                            </a>

                                            <form action="<?php echo url('modules/loan-products/toggle-status.php'); ?>" method="POST" class="d-inline" onsubmit="return confirm('Change status for <?php echo e(addslashes($product['name'])); ?> to <?php echo $isActive ? 'Inactive' : 'Active'; ?>?');">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="id" value="<?php echo (int)$product['id']; ?>">
                                                <input type="hidden" name="redirect_to" value="<?php echo e($_SERVER['REQUEST_URI'] ?? ''); ?>">
                                                <button type="submit" class="btn btn-outline-secondary" title="<?php echo $isActive ? 'Deactivate Product' : 'Activate Product'; ?>" data-bs-toggle="tooltip">
                                                    <i class="bi <?php echo $isActive ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted'; ?>"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (can_manage_loan_products() && has_role('admin')): ?>
                                            <?php if ((int)$product['total_loans'] === 0): ?>
                                                <form action="<?php echo url('modules/loan-products/delete.php'); ?>" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete loan product <?php echo e(addslashes($product['name'])); ?>?');">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="id" value="<?php echo (int)$product['id']; ?>">
                                                    <button type="submit" class="btn btn-outline-secondary text-danger" title="Delete Product" data-bs-toggle="tooltip">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-outline-secondary text-muted" disabled title="Cannot delete: product is used in <?php echo $product['total_loans']; ?> loan applications" data-bs-toggle="tooltip">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="py-4">
                                    <i class="bi bi-tags text-muted display-6 d-block mb-3"></i>
                                    <h5 class="fw-bold text-dark">No loan products found</h5>
                                    <p class="text-muted small mb-3">
                                        <?php if ($search !== '' || $statusFilter !== 'all'): ?>
                                            No products match your filter criteria. Try adjusting your search query.
                                        <?php else: ?>
                                            No loan products have been configured yet.
                                        <?php endif; ?>
                                    </p>
                                    <?php if ($search !== '' || $statusFilter !== 'all'): ?>
                                        <a href="<?php echo url('modules/loan-products/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Clear Filters
                                        </a>
                                    <?php elseif (can_manage_loan_products()): ?>
                                        <a href="<?php echo url('modules/loan-products/create.php'); ?>" class="btn btn-primary btn-sm">
                                            <i class="bi bi-plus-circle-fill me-1"></i> Create First Loan Product
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
                Showing <strong><?php echo number_format($offset + 1); ?></strong> to <strong><?php echo number_format(min($offset + $perPage, $totalRecords)); ?></strong> of <strong><?php echo number_format($totalRecords); ?></strong> loan products
            </div>

            <?php if ($totalPages > 1): ?>
                <nav aria-label="Product list pagination">
                    <ul class="pagination pagination-sm mb-0 justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo product_pagination_url($page - 1, $search, $statusFilter); ?>" aria-label="Previous">&lsaquo;</a>
                        </li>
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo product_pagination_url($p, $search, $statusFilter); ?>"><?php echo $p; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo product_pagination_url($page + 1, $search, $statusFilter); ?>" aria-label="Next">&rsaquo;</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
