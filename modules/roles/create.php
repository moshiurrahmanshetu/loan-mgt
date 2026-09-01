<?php
/**
 * Create Custom Role Form
 * Loan Management System (loan-mgt) - Phase 8
 */

$pageTitle = 'Add Custom Role';
$activeNav = 'roles';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// 1. Permission Guard
require_permission('roles.create');

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/roles/index.php'); ?>" class="text-decoration-none text-muted">Roles & Permissions</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Add Custom Role</li>
            </ol>
        </nav>
        <h2 class="h4 fw-bold text-dark mb-0">Create Custom Operational Role</h2>
    </div>
    <div>
        <a href="<?php echo url('modules/roles/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Roles
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-xl-7">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h3 class="h6 mb-0 fw-bold"><i class="bi bi-shield-plus me-2 text-primary"></i> Custom Role Specification</h3>
            </div>
            <div class="card-body p-4">
                <form action="<?php echo url('modules/roles/store.php'); ?>" method="POST">
                    <?php echo csrf_field(); ?>

                    <!-- Role Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold">Role Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" required placeholder="e.g. Senior Underwriter, Branch Auditor">
                    </div>

                    <!-- Role Identifier / Slug -->
                    <div class="mb-3">
                        <label for="slug" class="form-label small fw-semibold">Role Slug Identifier <span class="text-danger">*</span></label>
                        <input type="text" name="slug" id="slug" class="form-control font-monospace" required placeholder="e.g. senior_underwriter, branch_auditor" pattern="^[a-z0-9_]{3,40}$" title="3-40 characters, lowercase letters, numbers, and underscores only">
                        <div class="form-text">Unique system key used in role-based authorization checks.</div>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label small fw-semibold">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3" placeholder="Describe the operational responsibilities of this role..."></textarea>
                    </div>

                    <!-- Status -->
                    <div class="mb-4">
                        <label for="status" class="form-label small fw-semibold">Role Status <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="active" selected>Active (Available for Assignment)</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?php echo url('modules/roles/index.php'); ?>" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-circle me-1"></i> Create Role & Configure Permissions
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
