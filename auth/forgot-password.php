<?php
/**
 * Forgot Password View
 * Loan Management System (loan-mgt) - Phase 1
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/guest-check.php';
require_once __DIR__ . '/../includes/flash.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — <?php echo e(APP_NAME); ?></title>
    
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
                <i class="bi bi-shield-lock"></i>
            </div>
            <h1 class="auth-title">Password Recovery</h1>
            <p class="auth-subtitle">Loan Management Security Portal</p>
        </div>

        <?php display_flash(); ?>

        <div class="alert alert-info d-flex align-items-start mb-4" role="alert">
            <i class="bi bi-info-circle-fill fs-5 me-2 flex-shrink-0 mt-1"></i>
            <div>
                <strong>Self-Service Notice</strong><br>
                For institutional security compliance, password resets are handled directly by your system administrator.
            </div>
        </div>

        <p class="text-muted small mb-4">
            If you have forgotten your password or are locked out of your account, please reach out to your organizational IT support or System Administrator with your registered employee email.
        </p>

        <a href="<?php echo url('auth/login.php'); ?>" class="btn btn-outline-secondary w-100 py-2">
            <i class="bi bi-arrow-left me-1"></i> Back to Sign In
        </a>
    </div>
</div>

<!-- Bootstrap 5 Bundle JS -->
<script src="<?php echo asset('vendor/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
</body>
</html>
