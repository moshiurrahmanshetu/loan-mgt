<?php
/**
 * Commercial Installer - Welcome & Setup Overview
 * Loan Management System (loan-mgt) - Phase 9
 */

require_once __DIR__ . '/../includes/install.php';

// Enforce installation lock
require_not_installed();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$appBaseUrl = get_app_base_url();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Setup Wizard — Loan Management System</title>
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
        <p class="text-muted small mb-0">Production Installation & Setup Wizard (v9.0.0)</p>
    </div>

    <!-- Main Card -->
    <div class="installer-card">
        <!-- Progress Steps -->
        <ul class="installer-steps">
            <li class="installer-step-item active">
                <span class="step-num">1</span>
                <span class="step-label">Requirements</span>
            </li>
            <li class="installer-step-item">
                <span class="step-num">2</span>
                <span class="step-label">Database</span>
            </li>
            <li class="installer-step-item">
                <span class="step-num">3</span>
                <span class="step-label">Import</span>
            </li>
            <li class="installer-step-item">
                <span class="step-num">4</span>
                <span class="step-label">Admin</span>
            </li>
            <li class="installer-step-item">
                <span class="step-num">5</span>
                <span class="step-label">Complete</span>
            </li>
        </ul>

        <div class="installer-card-body p-4">
            <h2 class="h5 fw-bold text-dark mb-3">Welcome to the Installation Wizard</h2>
            <p class="text-muted small mb-4">
                Thank you for choosing the <strong>Loan Management System</strong>. This automated wizard will guide you through the process of verifying your server environment, configuring database parameters, importing application schemas, and registering your initial system administrator account.
            </p>

            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6">
                    <div class="p-3 bg-light rounded border h-100">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-shield-check text-primary fs-5"></i>
                            <h3 class="h6 mb-0 fw-bold text-dark">Pre-Installation Checklist</h3>
                        </div>
                        <ul class="small text-muted mb-0 ps-3">
                            <li>PHP 8.0 or higher with PDO MySQL</li>
                            <li>Writable <code>config/</code> and <code>uploads/</code></li>
                            <li>MySQL 5.7+ / MariaDB 10.4+ server</li>
                        </ul>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="p-3 bg-light rounded border h-100">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-database text-primary fs-5"></i>
                            <h3 class="h6 mb-0 fw-bold text-dark">Required Credentials</h3>
                        </div>
                        <ul class="small text-muted mb-0 ps-3">
                            <li>MySQL database name</li>
                            <li>MySQL username and password</li>
                            <li>Database host (e.g. <code>localhost</code>)</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="alert alert-info d-flex align-items-center small mb-0" role="alert">
                <i class="bi bi-info-circle-fill fs-5 me-3 text-info"></i>
                <div>
                    <strong>Commercial Marketplace Package:</strong> The installer includes the master <code>database/install.sql</code> package. No manual SQL editing or command-line scripting is required.
                </div>
            </div>
        </div>

        <div class="installer-card-footer">
            <span class="text-muted small">Step 1 of 5</span>
            <a href="<?php echo $appBaseUrl; ?>/installer/requirements.php" class="btn btn-primary px-4">
                Check Server Requirements <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>

    <div class="installer-footer-text">
        &copy; <?php echo date('Y'); ?> Loan Management System &bull; Professional Edition
    </div>
</div>

<script src="<?php echo $appBaseUrl; ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
