<?php
/**
 * Add New User Form
 * Loan Management System (loan-mgt) - Phase 8
 */

$pageTitle = 'Add New User';
$activeNav = 'users';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// 1. Permission Guard
require_permission('users.create');

$db = get_db_connection();

// 2. Fetch Active Roles
$roles = $db->query("SELECT id, name, slug, description FROM roles WHERE status = 'active' ORDER BY name ASC")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/users/index.php'); ?>" class="text-decoration-none text-muted">User Management</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Add New User</li>
            </ol>
        </nav>
        <h2 class="h4 fw-bold text-dark mb-0">Create Staff User Account</h2>
    </div>
    <div>
        <a href="<?php echo url('modules/users/index.php'); ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Users Directory
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-xl-9">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h3 class="h6 mb-0 fw-bold"><i class="bi bi-person-plus-fill me-2 text-primary"></i> Staff Account Registration Details</h3>
            </div>
            <div class="card-body p-4">
                <form action="<?php echo url('modules/users/store.php'); ?>" method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h4 class="h6 text-primary fw-bold border-bottom pb-2 mb-3">1. Personal & Contact Information</h4>
                        </div>

                        <!-- Full Name -->
                        <div class="col-12 col-md-6">
                            <label for="name" class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" required placeholder="e.g. Johnathan Smith">
                        </div>

                        <!-- Username -->
                        <div class="col-12 col-md-6">
                            <label for="username" class="form-label small fw-semibold">Username <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">@</span>
                                <input type="text" name="username" id="username" class="form-control" required placeholder="e.g. jsmith" pattern="^[a-zA-Z0-9_\.]{3,30}$" title="3-30 characters, letters, numbers, underscores and periods only">
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="col-12 col-md-6">
                            <label for="email" class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control" required placeholder="e.g. jsmith@loanmgt.com">
                        </div>

                        <!-- Phone -->
                        <div class="col-12 col-md-6">
                            <label for="phone" class="form-label small fw-semibold">Phone Number</label>
                            <input type="text" name="phone" id="phone" class="form-control" placeholder="e.g. +1 (555) 234-5678">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h4 class="h6 text-primary fw-bold border-bottom pb-2 mb-3">2. Role & Security Authentication</h4>
                        </div>

                        <!-- Assigned Role -->
                        <div class="col-12 col-md-6">
                            <label for="role_id" class="form-label small fw-semibold">Assigned Role <span class="text-danger">*</span></label>
                            <select name="role_id" id="role_id" class="form-select" required>
                                <option value="">Select Role...</option>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?php echo (int)$r['id']; ?>">
                                        <?php echo e($r['name']); ?> (<?php echo e($r['slug']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Controls system permissions and authorization for this account.</div>
                        </div>

                        <!-- Account Status -->
                        <div class="col-12 col-md-6">
                            <label for="status" class="form-label small fw-semibold">Account Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="active" selected>Active (Can Authenticate)</option>
                                <option value="inactive">Inactive (Access Blocked)</option>
                            </select>
                        </div>

                        <!-- Password -->
                        <div class="col-12 col-md-6">
                            <label for="password" class="form-label small fw-semibold">Initial Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" id="password" class="form-control" required minlength="8" placeholder="Minimum 8 characters">
                        </div>

                        <!-- Confirm Password -->
                        <div class="col-12 col-md-6">
                            <label for="password_confirm" class="form-label small fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirm" id="password_confirm" class="form-control" required minlength="8" placeholder="Re-enter password">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h4 class="h6 text-primary fw-bold border-bottom pb-2 mb-3">3. Profile Avatar (Optional)</h4>
                        </div>

                        <!-- Avatar Upload -->
                        <div class="col-12 col-md-8">
                            <label for="avatar" class="form-label small fw-semibold">Profile Photo</label>
                            <input type="file" name="avatar" id="avatar" class="form-control" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">Allowed formats: JPG, PNG, WEBP. Maximum file size: 2MB.</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?php echo url('modules/users/index.php'); ?>" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-circle me-1"></i> Register User Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
