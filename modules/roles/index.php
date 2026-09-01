<?php
/**
 * Role Management Catalog & Overview
 * Loan Management System (loan-mgt) - Phase 8
 */

$pageTitle = 'Role Management';
$activeNav = 'roles';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// 1. Permission Guard
require_permission('roles.view');

$db = get_db_connection();

// 2. Fetch Roles with Assigned Users and Permissions Counts
$roles = $db->query("
    SELECT r.*,
           (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id) AS assigned_users_count,
           (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id) AS permissions_count
    FROM roles r
    ORDER BY r.is_system DESC, r.id ASC
")->fetchAll();

$canCreate = has_permission('roles.create');
$canEdit   = has_permission('roles.edit');
$canDelete = has_permission('roles.delete');

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Roles & Permissions</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h2 class="h4 fw-bold text-dark mb-0">System & Custom User Roles</h2>
            <span class="badge bg-light text-dark border"><?php echo count($roles); ?> Roles</span>
        </div>
    </div>

    <!-- Action Toolbar -->
    <div class="d-flex flex-wrap gap-2">
        <?php if ($canCreate): ?>
            <a href="<?php echo url('modules/roles/create.php'); ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Add Custom Role
            </a>
        <?php endif; ?>
        <?php if (has_permission('permissions.view')): ?>
            <a href="<?php echo url('modules/permissions/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-shield-check me-1"></i> Permissions Matrix
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Roles Table Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3 py-3">Role Name</th>
                        <th class="py-3">Description</th>
                        <th class="py-3 text-center">Assigned Users</th>
                        <th class="py-3 text-center">Granted Permissions</th>
                        <th class="py-3 text-center">Type</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="pe-3 py-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($roles)): ?>
                        <?php foreach ($roles as $r): ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-light p-2 rounded border text-primary">
                                            <i class="bi bi-shield-lock-fill"></i>
                                        </div>
                                        <div>
                                            <a href="<?php echo url('modules/roles/view.php?id=' . $r['id']); ?>" class="text-decoration-none text-dark fw-bold d-block">
                                                <?php echo e($r['name']); ?>
                                            </a>
                                            <span class="small text-muted font-monospace"><?php echo e($r['slug']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="small text-muted"><?php echo e($r['description'] ?: 'No description provided.'); ?></span>
                                </td>
                                <td class="text-center">
                                    <a href="<?php echo url('modules/users/index.php?role_id=' . $r['id']); ?>" class="badge bg-light text-dark border text-decoration-none fw-semibold">
                                        <i class="bi bi-people me-1"></i> <?php echo number_format($r['assigned_users_count']); ?>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        <?php echo $r['slug'] === 'admin' ? 'All (29)' : number_format($r['permissions_count']); ?> Perms
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($r['is_system']): ?>
                                        <span class="badge bg-dark text-white" style="font-size: 0.7rem;">System Role</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border" style="font-size: 0.7rem;">Custom</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($r['status'] === 'active'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-3 text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="<?php echo url('modules/roles/view.php?id=' . $r['id']); ?>" class="btn btn-sm btn-outline-secondary" title="View Role Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($canEdit): ?>
                                            <a href="<?php echo url('modules/roles/permissions.php?id=' . $r['id']); ?>" class="btn btn-sm btn-outline-primary" title="Configure Permissions">
                                                <i class="bi bi-shield-check"></i>
                                            </a>
                                            <a href="<?php echo url('modules/roles/edit.php?id=' . $r['id']); ?>" class="btn btn-sm btn-outline-secondary" title="Edit Role Details">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($canDelete && !$r['is_system']): ?>
                                            <form action="<?php echo url('modules/roles/delete.php'); ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete role <?php echo addslashes($r['name']); ?>?');">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Custom Role">
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
                            <td colspan="7" class="text-center py-4 text-muted">No roles configured in database.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
