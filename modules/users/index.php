<?php
/**
 * User Management Directory & Staff Accounts
 * Loan Management System (loan-mgt) - Phase 8
 */

$pageTitle = 'User Management';
$activeNav = 'users';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// 1. Permission Guard
require_permission('users.view');

$db = get_db_connection();

// 2. Query Filters & Pagination
$search     = trim($_GET['search'] ?? '');
$roleId     = (int)($_GET['role_id'] ?? 0);
$status     = trim($_GET['status'] ?? 'all');
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 10;
$offset     = ($page - 1) * $perPage;

$whereClauses = [];
$params       = [];

if ($search !== '') {
    $whereClauses[] = "(u.name LIKE :s_name OR u.username LIKE :s_user OR u.email LIKE :s_email OR u.phone LIKE :s_phone)";
    $wildcard = '%' . $search . '%';
    $params[':s_name']  = $wildcard;
    $params[':s_user']  = $wildcard;
    $params[':s_email'] = $wildcard;
    $params[':s_phone'] = $wildcard;
}

if ($roleId > 0) {
    $whereClauses[] = "u.role_id = :role_id";
    $params[':role_id'] = $roleId;
}

if (in_array($status, ['active', 'inactive'], true)) {
    $whereClauses[] = "u.status = :status";
    $params[':status'] = $status;
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// 3. Count Total Records
$countSql = "SELECT COUNT(*) FROM users u {$whereSql}";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages   = max(1, ceil($totalRecords / $perPage));

if ($page > $totalPages && $totalRecords > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// 4. Fetch Paginated Users with Roles
$selectSql = "
    SELECT u.id, u.name, u.username, u.email, u.phone, u.avatar, u.status, u.last_login, u.created_at,
           r.id AS role_id, r.name AS role_name, r.slug AS role_slug
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
    {$whereSql}
    ORDER BY u.id DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $db->prepare($selectSql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll();

// Fetch roles for filter dropdown
$roles = $db->query("SELECT id, name, slug FROM roles WHERE status = 'active' ORDER BY name ASC")->fetchAll();

$canCreate = has_permission('users.create');
$canEdit   = has_permission('users.edit');
$canDelete = has_permission('users.delete');
$currentId = auth_id();

// Build query string for pagination
$filterParams = http_build_query([
    'search'  => $search,
    'role_id' => $roleId,
    'status'  => $status,
]);

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">User Management</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h2 class="h4 fw-bold text-dark mb-0">System Users & Staff Directory</h2>
            <span class="badge bg-light text-dark border"><?php echo number_format($totalRecords); ?> Users</span>
        </div>
    </div>

    <!-- Action Toolbar -->
    <div class="d-flex flex-wrap gap-2">
        <?php if ($canCreate): ?>
            <a href="<?php echo url('modules/users/create.php'); ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-person-plus-fill me-1"></i> Add New User
            </a>
        <?php endif; ?>
        <?php if (has_permission('roles.view')): ?>
            <a href="<?php echo url('modules/roles/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-shield-lock me-1"></i> Manage Roles
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filters Toolbar Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body p-3">
        <form action="<?php echo url('modules/users/index.php'); ?>" method="GET" class="row g-2 align-items-center">
            <!-- Search -->
            <div class="col-12 col-md-5">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name, username, email, phone..." value="<?php echo e($search); ?>">
            </div>

            <!-- Role Filter -->
            <div class="col-6 col-md-3">
                <select name="role_id" class="form-select form-select-sm">
                    <option value="0">All Roles</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?php echo (int)$r['id']; ?>" <?php echo $roleId === (int)$r['id'] ? 'selected' : ''; ?>>
                            <?php echo e($r['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="col-6 col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>

            <!-- Filter Buttons -->
            <div class="col-12 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1" title="Apply Filter">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <?php if ($search !== '' || $roleId > 0 || $status !== 'all'): ?>
                    <a href="<?php echo url('modules/users/index.php'); ?>" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Users Table Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3 py-3">Staff Member</th>
                        <th class="py-3">Contact Details</th>
                        <th class="py-3">Assigned Role</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="py-3">Registered Date</th>
                        <th class="py-3">Last Active</th>
                        <th class="pe-3 py-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $u): ?>
                            <?php 
                            $avatarUrl = get_avatar_url($u['avatar'] ?? null, $u['name']);
                            $roleSlug = $u['role_slug'] ?? 'loan_officer';
                            $roleName = $u['role_name'] ?? get_role_label($roleSlug);
                            $isSelf = ((int)$u['id'] === (int)$currentId);
                            ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?php echo e($avatarUrl); ?>" alt="<?php echo e($u['name']); ?>" class="avatar-img rounded-circle" style="width: 36px; height: 36px; object-fit: cover;">
                                        <div>
                                            <a href="<?php echo url('modules/users/view.php?id=' . $u['id']); ?>" class="text-decoration-none text-dark fw-bold d-block">
                                                <?php echo e($u['name']); ?>
                                                <?php if ($isSelf): ?>
                                                    <span class="badge bg-light text-primary border ms-1" style="font-size: 0.65rem;">You</span>
                                                <?php endif; ?>
                                            </a>
                                            <span class="small text-muted font-monospace">@<?php echo e($u['username'] ?: 'user_' . $u['id']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark"><?php echo e($u['email']); ?></div>
                                    <div class="small text-muted font-monospace"><?php echo e($u['phone'] ?: '—'); ?></div>
                                </td>
                                <td>
                                    <span class="badge badge-role badge-role-<?php echo e($roleSlug); ?>">
                                        <?php echo e($roleName); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($u['status'] === 'active'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?php echo format_date($u['created_at']); ?></td>
                                <td class="small text-muted">
                                    <?php echo !empty($u['last_login']) ? format_date($u['last_login'], 'M d, Y g:i A') : '<span class="text-muted fst-italic">Never</span>'; ?>
                                </td>
                                <td class="pe-3 text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li>
                                                <a class="dropdown-item py-1.5 small" href="<?php echo url('modules/users/view.php?id=' . $u['id']); ?>">
                                                    <i class="bi bi-eye text-primary me-2"></i> View Profile
                                                </a>
                                            </li>
                                            <?php if ($canEdit): ?>
                                                <li>
                                                    <a class="dropdown-item py-1.5 small" href="<?php echo url('modules/users/edit.php?id=' . $u['id']); ?>">
                                                        <i class="bi bi-pencil text-secondary me-2"></i> Edit Account
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item py-1.5 small" href="<?php echo url('modules/users/change-password.php?id=' . $u['id']); ?>">
                                                        <i class="bi bi-shield-lock text-warning me-2"></i> Reset Password
                                                    </a>
                                                </li>
                                                <?php if (!$isSelf): ?>
                                                    <li><hr class="dropdown-divider my-1"></li>
                                                    <li>
                                                        <form action="<?php echo url('modules/users/toggle-status.php'); ?>" method="POST" class="d-inline">
                                                            <?php echo csrf_field(); ?>
                                                            <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                                                            <button type="submit" class="dropdown-item py-1.5 small text-<?php echo $u['status'] === 'active' ? 'warning' : 'success'; ?>">
                                                                <i class="bi bi-<?php echo $u['status'] === 'active' ? 'pause-circle' : 'play-circle'; ?> me-2"></i>
                                                                <?php echo $u['status'] === 'active' ? 'Deactivate Account' : 'Activate Account'; ?>
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <?php if ($canDelete && !$isSelf): ?>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <form action="<?php echo url('modules/users/delete.php'); ?>" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete user <?php echo addslashes($u['name']); ?>? This action cannot be undone.');">
                                                        <?php echo csrf_field(); ?>
                                                        <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                                                        <button type="submit" class="dropdown-item py-1.5 small text-danger">
                                                            <i class="bi bi-trash me-2"></i> Delete User
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-person-x fs-3 d-block mb-2 text-muted"></i>
                                <h5 class="fw-bold text-dark">No user accounts found</h5>
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
                Showing page <strong><?php echo $page; ?></strong> of <strong><?php echo $totalPages; ?></strong> (Total <?php echo number_format($totalRecords); ?> users)
            </span>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo url('modules/users/index.php?page=' . ($page - 1) . '&' . $filterParams); ?>">Previous</a>
                </li>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo url('modules/users/index.php?page=' . $p . '&' . $filterParams); ?>"><?php echo $p; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo url('modules/users/index.php?page=' . ($page + 1) . '&' . $filterParams); ?>">Next</a>
                </li>
            </ul>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
