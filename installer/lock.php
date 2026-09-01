<?php
/**
 * Commercial Installer - Installation Locked Screen
 * Loan Management System (loan-mgt) - Phase 9
 */

require_once __DIR__ . '/../includes/install.php';

$appBaseUrl = get_app_base_url();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Locked — Loan Management System</title>
    <link rel="stylesheet" href="<?php echo $appBaseUrl; ?>/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo $appBaseUrl; ?>/assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo $appBaseUrl; ?>/installer/assets/css/installer.css">
</head>
<body class="installer-body">

<div class="installer-container" style="max-width: 600px;">
    <!-- Brand Header -->
    <div class="installer-brand">
        <div class="brand-icon" style="background-color: #0f172a;">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <h1 class="h4 fw-bold text-dark mb-1">Loan Management System</h1>
        <p class="text-muted small mb-0">Production Security Guard</p>
    </div>

    <div class="installer-card">
        <div class="installer-card-body p-4 text-center">
            <div class="mb-3">
                <div class="d-inline-flex align-items-center justify-content-center bg-dark text-white rounded-circle" style="width: 64px; height: 64px;">
                    <i class="bi bi-lock-fill fs-2"></i>
                </div>
            </div>

            <h2 class="h5 fw-bold text-dark mb-2">Installation Already Completed</h2>
            <p class="text-muted small mb-4">
                The application has already been installed and the setup wizard is permanently locked to protect system security and configuration integrity.
            </p>

            <div class="alert alert-light border text-start small mb-4">
                <strong class="d-block mb-1 text-dark"><i class="bi bi-info-circle me-1 text-primary"></i> Reinstallation Procedure:</strong>
                <span class="text-muted">
                    If you need to perform a fresh installation, please backup your database, remove the <code>config/installed.lock</code> file, and restart the setup wizard as documented in the user manual.
                </span>
            </div>

            <div class="d-grid">
                <a href="<?php echo $appBaseUrl; ?>/auth/login.php" class="btn btn-primary">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Go to Application Login
                </a>
            </div>
        </div>
        <div class="installer-card-footer justify-content-center py-2.5">
            <span class="small text-muted">Protected by <code>config/installed.lock</code></span>
        </div>
    </div>

    <div class="installer-footer-text">
        &copy; <?php echo date('Y'); ?> Loan Management System &bull; Professional Edition
    </div>
</div>

<script src="<?php echo $appBaseUrl; ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
