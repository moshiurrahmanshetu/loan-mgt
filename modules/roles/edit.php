<?php
/**
 * Edit Role Details Form
 * Loan Management System (loan-mgt) - Phase 8
 */

$pageTitle = 'Edit Role';
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
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Edit Role</li>
            </ol>
        </nav>
        <h2 class="h4 fw-bold text-dark mb-0">Edit Role Details</h2>
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

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-xl-7">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h3 class="h6 mb-0 fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i> Modify Role Attributes</h3>
            </div>
            <div class="card-body p-4">
                <form action="<?php echo url('modules/roles/update.php'); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo (int)$role['id']; ?>">

                    <!-- Role Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold">Role Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" required value="<?php echo e($role['name']); ?>" <?php echo $role['is_system'] ? 'readonly' : ''; ?>>
                        <?php if ($role['is_system']): ?>
                            <div class="form-text text-muted">System role names are protected from modification.</div>
                        <?php endif; ?>
                    </div>

                    <!-- Role Slug -->
                    <div class="mb-3">
                        <label for="slug" class="form-label small fw-semibold">Role Slug Identifier <span class="text-danger">*</span></label>
                        <input type="text" name="slug" id="slug" class="form-control font-monospace" required value="<?php echo e($role['slug']); ?>" readonly>
                        <div class="form-text text-muted">Role slug cannot be altered after creation to protect authorization integrity.</div>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label small fw-semibold">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3"><?php echo e($role['description'] ?? ''); ?></textarea>
                    </div>

                    <!-- Status -->
                    <div class="mb-4">
                        <label for="status" class="form-label small fw-semibold">Role Status <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select" required <?php echo $role['is_system'] ? 'disabled' : ''; ?>>
                            <option value="active" <?php echo $role['status'] === 'active' ? 'selected' : ''; ?>>Active (Available for Assignment)</option>
                            <option value="inactive" <?php echo $role['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                        <?php if ($role['is_system']): ?>
                            <input type="hidden" name="status" value="active">
                            <div class="form-text text-muted">Core system roles must remain active.</div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?php echo url('modules/roles/index.php'); ?>" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-circle me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
