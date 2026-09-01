<?php
/**
 * Profile Management View
 * Loan Management System (loan-mgt) - Phase 1
 */

$pageTitle = 'My Profile';
$activeNav = 'profile';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

$currentUser = auth_user();
$db = get_db_connection();

$stmt = $db->prepare('SELECT id, name, email, phone, avatar, role, status, last_login, created_at FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $currentUser['id']]);
$userRecord = $stmt->fetch();

if (!$userRecord) {
    set_flash('danger', 'User record not found.');
    redirect('auth/logout.php');
}

$roleLabel = get_role_label($userRecord['role'] ?? 'loan_officer');
$roleBadgeClass = 'badge-role-' . ($userRecord['role'] ?? 'loan_officer');
$avatarUrl = get_avatar_url($userRecord['avatar'], $userRecord['name']);
$registeredDate = !empty($userRecord['created_at']) ? date('F j, Y', strtotime($userRecord['created_at'])) : 'N/A';
$lastLogin = !empty($userRecord['last_login']) ? date('F j, Y, g:i a', strtotime($userRecord['last_login'])) : 'Current session';

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row g-4">
    <!-- Left Column: Profile Card & Avatar Management -->
    <div class="col-12 col-lg-4">
        <!-- User Summary Card -->
        <div class="card shadow-sm mb-4" id="avatar-card">
            <div class="card-body text-center p-4">
                <div class="position-relative d-inline-block mb-3">
                    <img src="<?php echo e($avatarUrl); ?>" alt="<?php echo e($userRecord['name']); ?>" class="avatar-img-lg shadow-sm">
                </div>
                <h2 class="h5 fw-bold text-dark mb-1"><?php echo e($userRecord['name']); ?></h2>
                <div class="mb-2">
                    <span class="badge badge-role <?php echo $roleBadgeClass; ?>"><?php echo e($roleLabel); ?></span>
                </div>
                <p class="text-muted small mb-0"><?php echo e($userRecord['email']); ?></p>
            </div>
            <div class="card-footer bg-light border-top p-3 small text-muted">
                <div class="d-flex justify-content-between mb-1">
                    <span>Member Since:</span>
                    <span class="fw-semibold text-dark"><?php echo e($registeredDate); ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Account Status:</span>
                    <span class="badge badge-status-active">Active</span>
                </div>
            </div>
        </div>

        <!-- Avatar Upload Form -->
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h3 class="h6 mb-0 fw-bold"><i class="bi bi-camera me-2 text-primary"></i> Update Photo</h3>
            </div>
            <div class="card-body p-3">
                <form action="<?php echo url('modules/profile/upload-avatar.php'); ?>" method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    
                    <div class="mb-3">
                        <label for="avatar" class="form-label small text-muted">Select image file (JPG, PNG, WebP &bull; Max 2MB)</label>
                        <input class="form-control form-control-sm" type="file" id="avatar" name="avatar" accept=".jpg,.jpeg,.png,.webp" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-cloud-arrow-up me-1"></i> Upload Avatar
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Profile Details & Password Form -->
    <div class="col-12 col-lg-8">
        <!-- Personal Information Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-person-lines-fill text-primary fs-5"></i>
                    <h3 class="h6 mb-0 fw-bold">Personal Information</h3>
                </div>
            </div>
            <div class="card-body p-4">
                <form action="<?php echo url('modules/profile/update.php'); ?>" method="POST" autocomplete="off">
                    <?php echo csrf_field(); ?>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo e($userRecord['name']); ?>" required maxlength="100">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo e($userRecord['email']); ?>" required maxlength="150">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <label for="phone" class="form-label">Phone / Mobile Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo e($userRecord['phone'] ?? ''); ?>" placeholder="+1 (555) 000-0000" maxlength="30">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">System Role (Read-only)</label>
                            <input type="text" class="form-control bg-light" value="<?php echo e($roleLabel); ?>" readonly disabled>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Security & Password Change Card -->
        <div class="card shadow-sm mb-0" id="password-section">
            <div class="card-header bg-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-shield-lock text-warning fs-5"></i>
                    <h3 class="h6 mb-0 fw-bold">Security & Password</h3>
                </div>
            </div>
            <div class="card-body p-4">
                <form action="<?php echo url('modules/profile/change-password.php'); ?>" method="POST" autocomplete="off">
                    <?php echo csrf_field(); ?>

                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="current_password" name="current_password" placeholder="Enter your current password" required>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Min. 8 characters" minlength="8" required>
                            <div class="form-text small">Must be at least 8 characters long.</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repeat new password" minlength="8" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-outline-secondary px-4">
                            <i class="bi bi-key me-1"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
