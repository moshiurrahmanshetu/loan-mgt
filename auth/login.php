<?php
/**
 * Login View
 * Loan Management System (loan-mgt) - Phase 1
 */

require_once __DIR__ . '/../includes/install.php';
require_installed();

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/guest-check.php';
require_once __DIR__ . '/../includes/flash.php';

$oldEmail = $_SESSION['_old_email'] ?? '';
unset($_SESSION['_old_email']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — <?php echo e(APP_NAME); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?php echo asset('images/default-avatar.svg'); ?>">

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="<?php echo asset('vendor/bootstrap/css/bootstrap.min.css'); ?>">
    
    <!-- Bootstrap Icons CSS -->
    <link rel="stylesheet" href="<?php echo asset('vendor/bootstrap-icons/bootstrap-icons.min.css'); ?>">
    
    <!-- Custom Application CSS -->
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
</head>
<body class="bg-light">

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-brand">
            <div class="auth-brand-icon">
                <i class="bi bi-bank2"></i>
            </div>
            <h1 class="auth-title"><?php echo e(APP_NAME); ?></h1>
            <p class="auth-subtitle">Enterprise Loan & Portfolio Management</p>
        </div>

        <?php display_flash(); ?>

        <form action="<?php echo url('auth/authenticate.php'); ?>" method="POST" autocomplete="off">
            <?php echo csrf_field(); ?>

            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control border-start-0 ps-0" id="email" name="email" value="<?php echo e($oldEmail); ?>" placeholder="name@company.com" required autofocus>
                </div>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="password" class="form-label mb-0">Password</label>
                    <a href="<?php echo url('auth/forgot-password.php'); ?>" class="small text-decoration-none text-primary">Forgot password?</a>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" placeholder="••••••••" required>
                </div>
            </div>

            <div class="mb-4 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
                <label class="form-check-label small text-muted" for="remember">Remember this device</label>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Account
            </button>
        </form>

        <div class="mt-4 pt-3 border-top text-center text-muted small">
            <span>Development access: <code>admin@loanmgt.com</code> / <code>Admin@123456</code></span>
        </div>
    </div>
</div>

<!-- Bootstrap 5 Bundle JS -->
<script src="<?php echo asset('vendor/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
</body>
</html>
