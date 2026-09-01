<?php
/**
 * Role Details, Assigned Users & Granted Permissions View
 * Loan Management System (loan-mgt) - Phase 8
 */

$pageTitle = 'Role Details';
$activeNav = 'roles';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// 1. Permission Guard
require_permission('roles.view');

$roleId = (int)($_GET['id'] ?? 0);
if ($roleId <= 0) {
    set_flash('danger', 'Invalid role ID.');
    redirect('modules/roles/index.php');
}

$db = get_db_connection();

// 2. Fetch Role Record
$stmt = $db->prepare('SELECT * FROM roles WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $roleId]);
$role = $stmt->fetch();

if (!$role) {
    set_flash('danger', 'Role not found.');
    redirect('modules/roles/index.php');
}

// 3. Fetch Assigned Users
$usersStmt = $db->prepare('SELECT id, name, username, email, phone, avatar, status, last_login FROM users WHERE role_id = :rid ORDER BY name ASC');
$usersStmt->execute([':rid' => $roleId]);
$assignedUsers = $usersStmt->fetchAll();

// 4. Fetch Granted Permissions
$permStmt = $db->prepare('
    SELECT p.id, p.name, p.slug, p.module, p.description
    FROM permissions p
    JOIN role_permissions rp ON p.id = rp.permission_id
    WHERE rp.role_id = :rid
    ORDER BY p.module ASC, p.name ASC
');
$permStmt->execute([':rid' => $roleId]);
$grantedPermissions = $permStmt->fetchAll();

$groupedPermissions = [];
foreach ($grantedPermissions as $p) {
    $groupedPermissions[$p['module']][] = $p;
}

$canEdit = has_permission('roles.edit');

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/roles/index.php'); ?>" class="text-decoration-none text-muted">Roles & Permissions</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page"><?php echo e($role['name']); ?></li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h2 class="h4 fw-bold text-dark mb-0"><?php echo e($role['name']); ?></h2>
            <span class="badge badge-role badge-role-<?php echo e($role['slug']); ?>"><?php echo e($role['slug']); ?></span>
            <?php if ($role['is_system']): ?>
                <span class="badge bg-dark text-white">System Role</span>
            <?php endif; ?>
            <?php if ($role['status'] === 'active'): ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
            <?php else: ?>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Inactive</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Action Toolbar -->
    <div class="d-flex flex-wrap gap-2">
        <a href="<?php echo url('modules/roles/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <?php if ($canEdit): ?>
            <a href="<?php echo url('modules/roles/permissions.php?id=' . $role['id']); ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-shield-check me-1"></i> Configure Permissions
            </a>
            <a href="<?php echo url('modules/roles/edit.php?id=' . $role['id']); ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-pencil me-1"></i> Edit Details
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Role Details & Assigned Users -->
    <div class="col-12 col-lg-5">
        <!-- Role Information Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h3 class="h6 mb-0 fw-bold"><i class="bi bi-info-circle me-2 text-primary"></i> Role Information</h3>
            </div>
            <div class="card-body p-3">
                <p class="text-muted small mb-3"><?php echo e($role['description'] ?: 'No detailed description provided.'); ?></p>
                
                <div class="small">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Slug Identifier:</span>
                        <strong class="text-dark font-monospace"><?php echo e($role['slug']); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Role Classification:</span>
                        <span><?php echo $role['is_system'] ? '<span class="badge bg-dark">Protected System Role</span>' : '<span class="badge bg-light text-dark border">Custom Role</span>'; ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Total Assigned Users:</span>
                        <strong class="text-dark"><?php echo count($assignedUsers); ?> staff accounts</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="text-muted">Created Date:</span>
                        <strong class="text-dark"><?php echo format_date($role['created_at']); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assigned Users List Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h3 class="h6 mb-0 fw-bold"><i class="bi bi-people me-2 text-primary"></i> Assigned Staff Accounts (<?php echo count($assignedUsers); ?>)</h3>
                <?php if (has_permission('users.create')): ?>
                    <a href="<?php echo url('modules/users/create.php'); ?>" class="small text-decoration-none text-primary">+ Add User</a>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($assignedUsers)): ?>
                    <div class="list-group list-group-flush small">
                        <?php foreach ($assignedUsers as $u): ?>
                            <?php $av = get_avatar_url($u['avatar'] ?? null, $u['name']); ?>
                            <a href="<?php echo url('modules/users/view.php?id=' . $u['id']); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2.5">
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?php echo e($av); ?>" alt="<?php echo e($u['name']); ?>" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                                    <div>
                                        <span class="fw-semibold text-dark d-block"><?php echo e($u['name']); ?></span>
                                        <span class="text-muted" style="font-size: 0.75rem;">@<?php echo e($u['username'] ?: 'user_' . $u['id']); ?> &bull; <?php echo e($u['email']); ?></span>
                                    </div>
                                </div>
                                <span class="badge bg-<?php echo $u['status'] === 'active' ? 'success' : 'danger'; ?>-subtle text-<?php echo $u['status'] === 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($u['status']); ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        <p class="small mb-0">No staff users currently assigned to this role.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Granted Permissions -->
    <div class="col-12 col-lg-7">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-shield-lock me-2 text-primary"></i> Granted System Permissions (<?php echo count($grantedPermissions); ?>)</h3>
                </div>
                <?php if ($canEdit): ?>
                    <a href="<?php echo url('modules/roles/permissions.php?id=' . $role['id']); ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-gear me-1"></i> Edit Permissions
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body p-4">
                <?php if ($role['slug'] === 'admin'): ?>
                    <div class="alert alert-info d-flex align-items-center mb-0">
                        <i class="bi bi-shield-fill-check fs-4 me-3 text-info"></i>
                        <div>
                            <strong class="d-block">Administrator Full System Access</strong>
                            <span class="small">The Administrator role inherently possesses all permissions across all modules.</span>
                        </div>
                    </div>
                <?php elseif (!empty($groupedPermissions)): ?>
                    <div class="row g-3">
                        <?php foreach ($groupedPermissions as $moduleName => $perms): ?>
                            <div class="col-12 col-md-6">
                                <div class="p-3 bg-light rounded border h-100">
                                    <h4 class="h6 fw-bold text-dark border-bottom pb-2 mb-2 d-flex align-items-center justify-content-between">
                                        <span><?php echo e($moduleName); ?></span>
                                        <span class="badge bg-white text-dark border"><?php echo count($perms); ?></span>
                                    </h4>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach ($perms as $p): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle small py-1.5 px-2">
                                                <i class="bi bi-check2 me-1"></i> <?php echo e($p['name']); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-shield-x fs-3 d-block mb-2 text-muted"></i>
                        <p class="small mb-0">No permissions granted yet. Click "Configure Permissions" to assign module permissions.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
