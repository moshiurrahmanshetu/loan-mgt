<?php
/**
 * System Permissions Matrix Overview
 * Loan Management System (loan-mgt) - Phase 8
 */

$pageTitle = 'System Permissions Matrix';
$activeNav = 'permissions';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// 1. Permission Guard
require_permission('permissions.view');

$db = get_db_connection();

// 2. Fetch Permissions with Roles Count
$permissions = $db->query("
    SELECT p.*,
           (SELECT COUNT(*) FROM role_permissions rp WHERE rp.permission_id = p.id) AS assigned_roles_count
    FROM permissions p
    ORDER BY p.module ASC, p.id ASC
")->fetchAll();

$grouped = [];
foreach ($permissions as $p) {
    $grouped[$p['module']][] = $p;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/roles/index.php'); ?>" class="text-decoration-none text-muted">Roles & Permissions</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Permissions Matrix</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h2 class="h4 fw-bold text-dark mb-0">System Permissions Matrix</h2>
            <span class="badge bg-light text-dark border"><?php echo count($permissions); ?> Granular Permissions</span>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo url('modules/roles/index.php'); ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-shield-lock me-1"></i> Manage Roles
        </a>
    </div>
</div>

<div class="alert alert-light border shadow-sm mb-4 small d-flex align-items-center">
    <i class="bi bi-shield-check text-primary fs-4 me-3"></i>
    <div>
        <strong>System-Defined Permission Architecture:</strong>
        Permissions represent fine-grained security privileges across all 11 business modules. To grant or revoke permissions, edit the corresponding role in the <a href="<?php echo url('modules/roles/index.php'); ?>" class="text-primary fw-semibold">Roles Management</a> panel.
    </div>
</div>

<!-- Grouped Permissions Table Grid -->
<div class="row g-4 mb-4">
    <?php foreach ($grouped as $moduleName => $perms): ?>
        <div class="col-12 col-xl-6">
            <div class="card shadow-sm h-100 mb-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-folder2-open text-primary"></i>
                        <h3 class="h6 mb-0 fw-bold text-dark"><?php echo e($moduleName); ?> Module</h3>
                    </div>
                    <span class="badge bg-light text-dark border"><?php echo count($perms); ?> Capabilities</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light text-muted text-uppercase" style="font-size: 0.7rem;">
                                <tr>
                                    <th class="ps-3 py-2">Permission Name</th>
                                    <th class="py-2">Slug</th>
                                    <th class="pe-3 py-2 text-end">Assigned Roles</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($perms as $p): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <strong class="text-dark d-block"><?php echo e($p['name']); ?></strong>
                                            <span class="text-muted d-block" style="font-size: 0.75rem;"><?php echo e($p['description']); ?></span>
                                        </td>
                                        <td class="font-monospace text-primary" style="font-size: 0.75rem;">
                                            <?php echo e($p['slug']); ?>
                                        </td>
                                        <td class="pe-3 text-end">
                                            <span class="badge bg-light text-dark border">
                                                <?php echo number_format($p['assigned_roles_count']); ?> Roles
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
