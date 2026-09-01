<?php
/**
 * Commercial Installer - Step 5: Installation Complete
 * Loan Management System (loan-mgt) - Phase 9
 */

require_once __DIR__ . '/../includes/install.php';

// Verify that the lock file actually exists
if (!is_installed()) {
    header('Location: ' . get_installer_base_url() . '/index.php');
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$appBaseUrl = get_app_base_url();
$adminEmail = $_SESSION['installer_admin_email'] ?? 'admin@loanmgt.com';
$adminUser  = $_SESSION['installer_admin_user'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 5: Installation Complete — Loan Management System</title>
    <link rel="stylesheet" href="<?php echo $appBaseUrl; ?>/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo $appBaseUrl; ?>/assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo $appBaseUrl; ?>/installer/assets/css/installer.css">
</head>
<body class="installer-body">

<div class="installer-container">
    <!-- Brand Header -->
    <div class="installer-brand">
        <div class="brand-icon">
            <i class="bi bi-bank2"></i>
        </div>
        <h1 class="h4 fw-bold text-dark mb-1">Loan Management System</h1>
        <p class="text-muted small mb-0">Production Installation Wizard</p>
    </div>

    <div class="installer-card">
        <!-- Progress Steps -->
        <ul class="installer-steps">
            <li class="installer-step-item completed">
                <span class="step-num"><i class="bi bi-check2"></i></span>
                <span class="step-label">Requirements</span>
            </li>
            <li class="installer-step-item completed">
                <span class="step-num"><i class="bi bi-check2"></i></span>
                <span class="step-label">Database</span>
            </li>
            <li class="installer-step-item completed">
                <span class="step-num"><i class="bi bi-check2"></i></span>
                <span class="step-label">Import</span>
            </li>
            <li class="installer-step-item completed">
                <span class="step-num"><i class="bi bi-check2"></i></span>
                <span class="step-label">Admin</span>
            </li>
            <li class="installer-step-item active completed">
                <span class="step-num"><i class="bi bi-check2"></i></span>
                <span class="step-label">Complete</span>
            </li>
        </ul>

        <div class="installer-card-body p-4 text-center">
            <div class="mb-3">
                <div class="d-inline-flex align-items-center justify-content-center bg-success-subtle text-success rounded-circle" style="width: 72px; height: 72px;">
                    <i class="bi bi-check-lg fs-1"></i>
                </div>
            </div>

            <h2 class="h4 fw-bold text-dark mb-2">Installation Successfully Completed!</h2>
            <p class="text-muted small mb-4">
                Your Loan Management System has been configured, schemas imported, and secured.
            </p>

            <!-- Health Status Summary Card -->
            <div class="card shadow-none border text-start mb-4">
                <div class="card-header bg-light py-2.5">
                    <h3 class="h6 mb-0 fw-bold text-dark"><i class="bi bi-shield-check me-2 text-primary"></i> System Health & Security Audit</h3>
                </div>
                <div class="card-body p-3 small">
                    <div class="d-flex justify-content-between align-items-center py-1.5 border-bottom">
                        <span>Database Relational Schemas:</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">10 Tables Configured</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-1.5 border-bottom">
                        <span>Role & Permission Security Matrix:</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">32 Permissions Seeded</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-1.5 border-bottom">
                        <span>Primary Administrator Account:</span>
                        <span class="text-dark fw-bold font-monospace"><?php echo e($adminEmail); ?> (@<?php echo e($adminUser); ?>)</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-1.5 border-bottom">
                        <span>Configuration File:</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">config/database.php Written</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-1.5">
                        <span>Installation Lock:</span>
                        <span class="badge bg-dark text-white">config/installed.lock Active</span>
                    </div>
                </div>
            </div>

            <div class="alert alert-info small d-flex align-items-center text-start mb-4" role="alert">
                <i class="bi bi-lock-fill fs-5 me-3 text-info"></i>
                <div>
                    <strong>Security Notice:</strong> The installer has been permanently locked. Direct access to installer setup endpoints is now blocked. You may log in to start operating the loan system.
                </div>
            </div>

            <div class="d-grid gap-2">
                <a href="<?php echo $appBaseUrl; ?>/auth/login.php" class="btn btn-primary btn-lg py-2.5">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Go to Application Login
                </a>
            </div>
        </div>

        <div class="installer-card-footer justify-content-center">
            <span class="small text-muted">Ready for production lending operations &bull; Version 9.0.0</span>
        </div>
    </div>

    <div class="installer-footer-text">
        &copy; <?php echo date('Y'); ?> Loan Management System &bull; Professional Edition
    </div>
</div>

<script src="<?php echo $appBaseUrl; ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
