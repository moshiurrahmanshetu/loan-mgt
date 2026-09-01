<?php
/**
 * Commercial Installer - Step 1: System Requirements & Permissions
 * Loan Management System (loan-mgt) - Phase 9
 */

require_once __DIR__ . '/../includes/install.php';

require_not_installed();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$appBaseUrl = get_app_base_url();

// 1. PHP Version Check
$phpRequired = '8.0.0';
$phpCurrent  = PHP_VERSION;
$phpPassed   = version_compare($phpCurrent, $phpRequired, '>=');

// 2. Required PHP Extensions Check
$requiredExtensions = [
    'pdo'       => 'PDO Core',
    'pdo_mysql' => 'PDO MySQL Driver',
    'mbstring'  => 'Multibyte String',
    'fileinfo'  => 'File Information (MIME Check)',
    'openssl'   => 'OpenSSL Cryptography',
    'json'      => 'JSON Parser',
    'ctype'     => 'Character Type Checking',
];

$extResults = [];
$allExtPassed = true;
foreach ($requiredExtensions as $extKey => $extLabel) {
    $loaded = extension_loaded($extKey);
    $extResults[$extKey] = [
        'name'   => $extLabel,
        'passed' => $loaded,
    ];
    if (!$loaded) {
        $allExtPassed = false;
    }
}

// 3. Writable Directories Check
$directories = [
    'config'             => ROOT_PATH . DIRECTORY_SEPARATOR . 'config',
    'uploads'            => ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads',
    'uploads/avatars'    => ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars',
    'uploads/customers'  => ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'customers',
    'uploads/settings'   => ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'settings',
];

// Ensure upload directories exist if possible
foreach ($directories as $key => $path) {
    if (!is_dir($path)) {
        @mkdir($path, 0755, true);
    }
}

$dirResults = [];
$allDirsWritable = true;
foreach ($directories as $key => $path) {
    $isWritable = is_dir($path) && is_writable($path);
    $dirResults[$key] = [
        'path'     => $key . '/',
        'writable' => $isWritable,
    ];
    if (!$isWritable) {
        $allDirsWritable = false;
    }
}

$canContinue = ($phpPassed && $allExtPassed && $allDirsWritable);
$_SESSION['installer_step1_passed'] = $canContinue;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 1: System Requirements — Loan Management System</title>
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
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold text-dark mb-0">Step 1: Environment & Permissions Audit</h2>
                <a href="<?php echo $appBaseUrl; ?>/installer/requirements.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-clockwise me-1"></i> Re-check
                </a>
            </div>
            <p class="text-muted small mb-4">
                The installer verifies that your server satisfies all requirements and file permissions.
            </p>

            <!-- 1. PHP Engine -->
            <div class="card shadow-none border mb-4">
                <div class="card-header bg-light py-2.5">
                    <h3 class="h6 mb-0 fw-bold text-dark"><i class="bi bi-code-slash me-2 text-primary"></i> PHP Version</h3>
                </div>
                <div class="card-body p-3 small">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>PHP Version:</strong>
                            <span class="text-muted ms-1">(Required: <?php echo $phpRequired; ?>+, Current: <?php echo $phpCurrent; ?>)</span>
                        </div>
                        <?php if ($phpPassed): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5"><i class="bi bi-check-circle-fill me-1"></i> Passed</span>
                        <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1.5"><i class="bi bi-x-circle-fill me-1"></i> Failed</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 2. PHP Extensions -->
            <div class="card shadow-none border mb-4">
                <div class="card-header bg-light py-2.5">
                    <h3 class="h6 mb-0 fw-bold text-dark"><i class="bi bi-puzzle me-2 text-primary"></i> PHP Extensions</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <tbody>
                                <?php foreach ($extResults as $extKey => $ext): ?>
                                    <tr>
                                        <td class="ps-3 py-2.5">
                                            <strong><?php echo e($ext['name']); ?></strong>
                                            <span class="text-muted font-monospace ms-1">(<?php echo e($extKey); ?>)</span>
                                        </td>
                                        <td class="pe-3 py-2.5 text-end">
                                            <?php if ($ext['passed']): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check2 me-1"></i> Enabled</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-x me-1"></i> Missing</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 3. Directory Permissions -->
            <div class="card shadow-none border mb-4">
                <div class="card-header bg-light py-2.5">
                    <h3 class="h6 mb-0 fw-bold text-dark"><i class="bi bi-folder-check me-2 text-primary"></i> Directory Write Permissions</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <tbody>
                                <?php foreach ($dirResults as $key => $dir): ?>
                                    <tr>
                                        <td class="ps-3 py-2.5">
                                            <code><?php echo e($dir['path']); ?></code>
                                        </td>
                                        <td class="pe-3 py-2.5 text-end">
                                            <?php if ($dir['writable']): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check2 me-1"></i> Writable</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-x me-1"></i> Not Writable</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php if (!$canContinue): ?>
                <div class="alert alert-danger small d-flex align-items-center mb-0" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-danger"></i>
                    <div>
                        <strong>Installation Blocked:</strong> One or more system checks have failed. Please resolve the issues marked above and refresh this page to proceed.
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-success small d-flex align-items-center mb-0" role="alert">
                    <i class="bi bi-check-circle-fill fs-4 me-3 text-success"></i>
                    <div>
                        <strong>Environment Verified:</strong> Your server satisfies all required PHP extensions and directory write permissions. You may now continue to database configuration.
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="installer-card-footer">
            <a href="<?php echo $appBaseUrl; ?>/installer/index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <?php if ($canContinue): ?>
                <a href="<?php echo $appBaseUrl; ?>/installer/database.php" class="btn btn-primary px-4">
                    Continue to Database Setup <i class="bi bi-arrow-right ms-1"></i>
                </a>
            <?php else: ?>
                <button class="btn btn-secondary px-4" disabled>
                    Requirements Unmet <i class="bi bi-lock-fill ms-1"></i>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="installer-footer-text">
        &copy; <?php echo date('Y'); ?> Loan Management System &bull; Professional Edition
    </div>
</div>

<script src="<?php echo $appBaseUrl; ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
