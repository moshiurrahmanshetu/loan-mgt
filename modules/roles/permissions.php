<?php
/**
 * Role Permission Assignment Matrix Form
 * Loan Management System (loan-mgt) - Phase 8
 */

$pageTitle = 'Configure Role Permissions';
$activeNav = 'roles';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// 1. Permission Guard
require_permission('roles.edit');

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

// 3. Fetch All Permissions Grouped by Module
$allPerms = $db->query('SELECT * FROM permissions ORDER BY module ASC, id ASC')->fetchAll();
$groupedPermissions = [];
foreach ($allPerms as $p) {
    $groupedPermissions[$p['module']][] = $p;
}

// 4. Fetch Currently Assigned Permission IDs for This Role
$assignedStmt = $db->prepare('SELECT permission_id FROM role_permissions WHERE role_id = :rid');
$assignedStmt->execute([':rid' => $roleId]);
$assignedPermIds = $assignedStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/roles/index.php'); ?>" class="text-decoration-none text-muted">Roles & Permissions</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/roles/view.php?id=' . $role['id']); ?>" class="text-decoration-none text-muted"><?php echo e($role['name']); ?></a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Permissions Matrix</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h2 class="h4 fw-bold text-dark mb-0">Role Permissions: <?php echo e($role['name']); ?></h2>
            <span class="badge badge-role badge-role-<?php echo e($role['slug']); ?>"><?php echo e($role['slug']); ?></span>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo url('modules/roles/view.php?id=' . $role['id']); ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-eye me-1"></i> View Role
        </a>
        <a href="<?php echo url('modules/roles/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Roles
        </a>
    </div>
</div>

<form action="<?php echo url('modules/roles/save-permissions.php'); ?>" method="POST">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="role_id" value="<?php echo (int)$role['id']; ?>">

    <!-- Top Action Toolbar -->
    <div class="card shadow-sm mb-4 bg-light border">
        <div class="card-body p-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <div class="small text-muted">
                Select the granular feature permissions granted to staff accounts assigned to <strong><?php echo e($role['name']); ?></strong>.
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-dark btn-sm" onclick="toggleAllGlobal(true)">
                    <i class="bi bi-check-all me-1"></i> Select All
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleAllGlobal(false)">
                    <i class="bi bi-x me-1"></i> Deselect All
                </button>
                <button type="submit" class="btn btn-primary btn-sm px-3">
                    <i class="bi bi-shield-check me-1"></i> Save Permissions
                </button>
            </div>
        </div>
    </div>

    <!-- Grouped Permission Cards Grid -->
    <div class="row g-4 mb-4">
        <?php foreach ($groupedPermissions as $moduleName => $permissions): ?>
            <?php $moduleSlug = preg_replace('/[^a-z0-9_]/', '_', strtolower($moduleName)); ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card shadow-sm h-100 border-0" style="border-top: 3px solid var(--primary-color) !important;">
                    <div class="card-header bg-white py-2.5 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-folder2-open text-primary"></i>
                            <h3 class="h6 mb-0 fw-bold text-dark"><?php echo e($moduleName); ?></h3>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-xs btn-outline-primary py-0 px-1.5" onclick="toggleModuleCheckboxes('<?php echo $moduleSlug; ?>', true)" title="Select all in this module">
                                All
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1.5" onclick="toggleModuleCheckboxes('<?php echo $moduleSlug; ?>', false)" title="Deselect all in this module">
                                None
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($permissions as $p): ?>
                                <?php $isChecked = in_array((int)$p['id'], $assignedPermIds, true); ?>
                                <div class="form-check">
                                    <input class="form-check-input perm-check module-<?php echo $moduleSlug; ?>" type="checkbox" name="permissions[]" value="<?php echo (int)$p['id']; ?>" id="perm_<?php echo $p['id']; ?>" <?php echo $isChecked ? 'checked' : ''; ?>>
                                    <label class="form-check-label small" for="perm_<?php echo $p['id']; ?>">
                                        <span class="fw-semibold text-dark d-block"><?php echo e($p['name']); ?></span>
                                        <span class="text-muted font-monospace d-block" style="font-size: 0.72rem;"><?php echo e($p['slug']); ?></span>
                                        <?php if (!empty($p['description'])): ?>
                                            <span class="text-muted d-block" style="font-size: 0.75rem;"><?php echo e($p['description']); ?></span>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Bottom Submit Bar -->
    <div class="d-flex justify-content-between align-items-center pt-3 border-top mb-5">
        <a href="<?php echo url('modules/roles/index.php'); ?>" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-circle me-1"></i> Save Role Permissions
        </button>
    </div>
</form>

<script>
function toggleModuleCheckboxes(moduleSlug, checkState) {
    var checkboxes = document.querySelectorAll('.module-' + moduleSlug);
    checkboxes.forEach(function(cb) {
        cb.checked = checkState;
    });
}

function toggleAllGlobal(checkState) {
    var checkboxes = document.querySelectorAll('.perm-check');
    checkboxes.forEach(function(cb) {
        cb.checked = checkState;
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
