<?php
/**
 * Customer List View (with Search, Filter & Pagination)
 * Loan Management System (loan-mgt) - Phase 2
 */

$pageTitle = 'Customers';
$activeNav = 'customers';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = get_db_connection();

// 1. Get and sanitize query parameters
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// 2. Build filtered query
$whereClauses = [];
$params = [];

if ($search !== '') {
    $whereClauses[] = '(customer_code LIKE :search_code OR full_name LIKE :search_name OR phone LIKE :search_phone OR email LIKE :search_email)';
    $searchWildcard = '%' . $search . '%';
    $params[':search_code'] = $searchWildcard;
    $params[':search_name'] = $searchWildcard;
    $params[':search_phone'] = $searchWildcard;
    $params[':search_email'] = $searchWildcard;
}

if (in_array($statusFilter, ['active', 'inactive'], true)) {
    $whereClauses[] = 'status = :status';
    $params[':status'] = $statusFilter;
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// 3. Count total matching records
$countSql = "SELECT COUNT(*) FROM customers {$whereSql}";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRecords / $perPage));

if ($page > $totalPages && $totalRecords > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// 4. Fetch paginated customer records
$selectSql = "SELECT id, customer_code, full_name, phone, email, occupation, monthly_income, photo, status, created_at 
              FROM customers 
              {$whereSql} 
              ORDER BY id DESC 
              LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($selectSql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$customers = $stmt->fetchAll();

// Pagination URL query builder helper
function pagination_url(int $p, string $search, string $status): string
{
    $query = ['page' => $p];
    if ($search !== '') {
        $query['search'] = $search;
    }
    if ($status !== 'all') {
        $query['status'] = $status;
    }
    return url('modules/customers/index.php?' . http_build_query($query));
}

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Page Header & Action Controls -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Customers</li>
            </ol>
        </nav>
        <h2 class="h4 fw-bold text-dark mb-0">Customer Portfolio</h2>
    </div>

    <?php if (can_manage_customers()): ?>
        <div>
            <a href="<?php echo url('modules/customers/create.php'); ?>" class="btn btn-primary d-inline-flex align-items-center">
                <i class="bi bi-person-plus-fill me-2"></i> Add New Customer
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Search & Filter Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?php echo url('modules/customers/index.php'); ?>" class="row g-2 align-items-center">
            <!-- Search Keyword -->
            <div class="col-12 col-md-6 col-lg-5">
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="search" value="<?php echo e($search); ?>" placeholder="Search by name, code, phone, email...">
                </div>
            </div>

            <!-- Status Filter -->
            <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                <select name="status" class="form-select">
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active Customers</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive Customers</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="col-12 col-sm-6 col-md-3 col-lg-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1 flex-md-grow-0 px-4">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <?php if ($search !== '' || $statusFilter !== 'all'): ?>
                    <a href="<?php echo url('modules/customers/index.php'); ?>" class="btn btn-outline-secondary" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Customer Data Table Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3 py-3" style="width: 140px;">Customer Code</th>
                        <th class="py-3">Customer</th>
                        <th class="py-3">Phone</th>
                        <th class="py-3">Email</th>
                        <th class="py-3">Occupation</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="py-3">Created</th>
                        <th class="pe-3 py-3 text-end" style="min-width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($customers)): ?>
                        <?php foreach ($customers as $customer): ?>
                            <?php 
                                $photoUrl = get_customer_photo_url($customer['photo'], $customer['full_name']);
                                $isActive = ($customer['status'] === 'active');
                                $statusBadge = $isActive ? 'badge-status-active' : 'badge-status-inactive';
                                $createdFormatted = date('M d, Y', strtotime($customer['created_at']));
                            ?>
                            <tr>
                                <td class="ps-3 fw-semibold">
                                    <a href="<?php echo url('modules/customers/view.php?id=' . $customer['id']); ?>" class="text-decoration-none text-primary font-monospace">
                                        <?php echo e($customer['customer_code']); ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2.5">
                                        <img src="<?php echo e($photoUrl); ?>" alt="<?php echo e($customer['full_name']); ?>" class="avatar-img flex-shrink-0" style="width: 34px; height: 34px;">
                                        <div>
                                            <a href="<?php echo url('modules/customers/view.php?id=' . $customer['id']); ?>" class="text-decoration-none fw-semibold text-dark d-block text-truncate" style="max-width: 200px;">
                                                <?php echo e($customer['full_name']); ?>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-nowrap small text-dark">
                                    <i class="bi bi-telephone text-muted me-1"></i> <?php echo e($customer['phone']); ?>
                                </td>
                                <td class="small text-muted text-truncate" style="max-width: 180px;">
                                    <?php echo !empty($customer['email']) ? e($customer['email']) : '<span class="text-muted fst-italic">N/A</span>'; ?>
                                </td>
                                <td class="small text-muted text-truncate" style="max-width: 160px;">
                                    <?php echo !empty($customer['occupation']) ? e($customer['occupation']) : '<span class="text-muted fst-italic">—</span>'; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?php echo $statusBadge; ?>">
                                        <?php echo e(ucfirst($customer['status'])); ?>
                                    </span>
                                </td>
                                <td class="small text-muted text-nowrap">
                                    <?php echo e($createdFormatted); ?>
                                </td>
                                <td class="pe-3 text-end text-nowrap">
                                    <div class="btn-group btn-group-sm" role="group" aria-label="Customer Actions">
                                        <!-- View Action -->
                                        <a href="<?php echo url('modules/customers/view.php?id=' . $customer['id']); ?>" class="btn btn-outline-secondary" title="View Profile" data-bs-toggle="tooltip">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <!-- Edit Action (Admin, Manager, Loan Officer) -->
                                        <?php if (can_manage_customers()): ?>
                                            <a href="<?php echo url('modules/customers/edit.php?id=' . $customer['id']); ?>" class="btn btn-outline-secondary" title="Edit Customer" data-bs-toggle="tooltip">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>

                                        <!-- Toggle Status Action (Admin, Manager) -->
                                        <?php if (can_toggle_customer_status()): ?>
                                            <form action="<?php echo url('modules/customers/toggle-status.php'); ?>" method="POST" class="d-inline" onsubmit="return confirm('Change status for <?php echo e(addslashes($customer['full_name'])); ?> to <?php echo $isActive ? 'Inactive' : 'Active'; ?>?');">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="id" value="<?php echo (int)$customer['id']; ?>">
                                                <input type="hidden" name="redirect_to" value="<?php echo e($_SERVER['REQUEST_URI'] ?? ''); ?>">
                                                <button type="submit" class="btn btn-outline-secondary" title="<?php echo $isActive ? 'Deactivate Customer' : 'Activate Customer'; ?>" data-bs-toggle="tooltip">
                                                    <i class="bi <?php echo $isActive ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted'; ?>"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Delete Action (Admin Only) -->
                                        <?php if (can_delete_customers()): ?>
                                            <form action="<?php echo url('modules/customers/delete.php'); ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete customer <?php echo e(addslashes($customer['full_name'])); ?> (<?php echo e($customer['customer_code']); ?>)?');">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="id" value="<?php echo (int)$customer['id']; ?>">
                                                <button type="submit" class="btn btn-outline-secondary text-danger" title="Delete Customer" data-bs-toggle="tooltip">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="py-4">
                                    <i class="bi bi-people text-muted display-6 d-block mb-3"></i>
                                    <h5 class="fw-bold text-dark">No customers found</h5>
                                    <p class="text-muted small mb-3">
                                        <?php if ($search !== '' || $statusFilter !== 'all'): ?>
                                            No records match your search criteria. Try adjusting your query or filter.
                                        <?php else: ?>
                                            Your customer portfolio is currently empty. Get started by adding your first borrower profile.
                                        <?php endif; ?>
                                    </p>
                                    <?php if ($search !== '' || $statusFilter !== 'all'): ?>
                                        <a href="<?php echo url('modules/customers/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Clear Filters
                                        </a>
                                    <?php elseif (can_manage_customers()): ?>
                                        <a href="<?php echo url('modules/customers/create.php'); ?>" class="btn btn-primary btn-sm">
                                            <i class="bi bi-person-plus-fill me-1"></i> Add First Customer
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
                Showing <strong><?php echo number_format($offset + 1); ?></strong> to <strong><?php echo number_format(min($offset + $perPage, $totalRecords)); ?></strong> of <strong><?php echo number_format($totalRecords); ?></strong> customers
            </div>

            <?php if ($totalPages > 1): ?>
                <nav aria-label="Customer list pagination">
                    <ul class="pagination pagination-sm mb-0 justify-content-center">
                        <!-- First Page -->
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo pagination_url(1, $search, $statusFilter); ?>" aria-label="First">&laquo;</a>
                        </li>
                        <!-- Previous Page -->
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo pagination_url($page - 1, $search, $statusFilter); ?>" aria-label="Previous">&lsaquo;</a>
                        </li>

                        <!-- Page Numbers (Sliding window) -->
                        <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                        ?>
                        <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                            <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo pagination_url($p, $search, $statusFilter); ?>"><?php echo $p; ?></a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next Page -->
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo pagination_url($page + 1, $search, $statusFilter); ?>" aria-label="Next">&rsaquo;</a>
                        </li>
                        <!-- Last Page -->
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo pagination_url($totalPages, $search, $statusFilter); ?>" aria-label="Last">&raquo;</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
