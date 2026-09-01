<?php
/**
 * Administrative Password Reset View & Handler
 * Loan Management System (loan-mgt) - Phase 8
 */

$pageTitle = 'Reset User Password';
$activeNav = 'users';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// 1. Permission Guard
require_permission('users.edit');

$db = get_db_connection();

$userId = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
if ($userId <= 0) {
    set_flash('danger', 'Invalid user account.');
    redirect('modules/users/index.php');
}

// 2. Fetch User Record
$stmt = $db->prepare('SELECT id, name, username, email FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('danger', 'User account not found.');
    redirect('modules/users/index.php');
}

// 3. Process POST Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('danger', 'Security token mismatch. Please try again.');
        redirect('modules/users/change-password.php?id=' . $userId);
    }

    $password        = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    $errors = [];
    if (empty($password) || mb_strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters in length.';
    } elseif ($password !== $passwordConfirm) {
        $errors[] = 'Password confirmation does not match.';
    }

    if (!empty($errors)) {
        set_flash('danger', implode('<br>', $errors));
        redirect('modules/users/change-password.php?id=' . $userId);
    }

    try {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $updateStmt = $db->prepare('UPDATE users SET password = :p WHERE id = :id');
        $updateStmt->execute([':p' => $hash, ':id' => $userId]);

        set_flash('success', "Password for user '{$user['name']}' has been updated successfully.");
        redirect('modules/users/view.php?id=' . $userId);
    } catch (Exception $e) {
        error_log('Password reset error: ' . $e->getMessage());
        set_flash('danger', 'An error occurred while resetting password.');
        redirect('modules/users/change-password.php?id=' . $userId);
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/users/index.php'); ?>" class="text-decoration-none text-muted">User Management</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/users/view.php?id=' . $user['id']); ?>" class="text-decoration-none text-muted"><?php echo e($user['name']); ?></a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Reset Password</li>
            </ol>
        </nav>
        <h2 class="h4 fw-bold text-dark mb-0">Reset User Password</h2>
    </div>
    <div>
        <a href="<?php echo url('modules/users/view.php?id=' . $user['id']); ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to User Profile
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-md-7 col-xl-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h3 class="h6 mb-0 fw-bold"><i class="bi bi-shield-lock-fill me-2 text-warning"></i> Set New Password for <?php echo e($user['name']); ?></h3>
            </div>
            <div class="card-body p-4">
                <div class="p-3 bg-light rounded border mb-4 small">
                    <span class="text-muted d-block">Account Details:</span>
                    <strong class="text-dark"><?php echo e($user['name']); ?></strong> (@<?php echo e($user['username'] ?: 'user_' . $user['id']); ?>) &bull; <?php echo e($user['email']); ?>
                </div>

                <form action="<?php echo url('modules/users/change-password.php'); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">

                    <!-- New Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label small fw-semibold">New Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" id="password" class="form-control" required minlength="8" placeholder="Enter new password (min 8 chars)">
                    </div>

                    <!-- Confirm New Password -->
                    <div class="mb-4">
                        <label for="password_confirm" class="form-label small fw-semibold">Confirm New Password <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirm" id="password_confirm" class="form-control" required minlength="8" placeholder="Re-enter new password">
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?php echo url('modules/users/view.php?id=' . $user['id']); ?>" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-warning text-dark px-4 fw-semibold">
                            <i class="bi bi-key-fill me-1"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
