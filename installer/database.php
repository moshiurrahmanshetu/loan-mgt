<?php
/**
 * Commercial Installer - Step 2: Database Credentials & Connection Test
 * Loan Management System (loan-mgt) - Phase 9
 */

require_once __DIR__ . '/../includes/install.php';

require_not_installed();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$appBaseUrl = get_app_base_url();

// Verify Step 1 completed
if (empty($_SESSION['installer_step1_passed'])) {
    header('Location: ' . $appBaseUrl . '/installer/requirements.php');
    exit;
}

$message = null;
$messageType = null;
$connectionTested = !empty($_SESSION['installer_db_tested']);

// Handle AJAX or standard POST submission for Test Connection & Proceed
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_installer_csrf_token()) {
        $message = 'Security validation failed (CSRF token mismatch). Please retry.';
        $messageType = 'danger';
    } else {
        $action   = $_POST['action'] ?? 'test';
        $dbHost   = trim($_POST['db_host'] ?? 'localhost');
        $dbPort   = trim($_POST['db_port'] ?? '3306');
        $dbName   = trim($_POST['db_name'] ?? '');
        $dbUser   = trim($_POST['db_user'] ?? '');
        $dbPass   = (string)($_POST['db_pass'] ?? '');

        if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
            $message = 'Please provide Database Host, Database Name, and Database Username.';
            $messageType = 'danger';
        } else {
            // Attempt live PDO connection test
            try {
                $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
                $testPdo = new PDO($dsn, $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5,
                ]);

                // Connection succeeded - stage credentials in session
                $_SESSION['installer_db'] = [
                    'host' => $dbHost,
                    'port' => $dbPort,
                    'name' => $dbName,
                    'user' => $dbUser,
                    'pass' => $dbPass,
                ];
                $_SESSION['installer_db_tested'] = true;
                $connectionTested = true;

                if ($action === 'proceed') {
                    header('Location: ' . $appBaseUrl . '/installer/import.php');
                    exit;
                } else {
                    $message = 'Database connection test successful! You may now proceed to Database Import.';
                    $messageType = 'success';
                }
            } catch (PDOException $e) {
                error_log('Installer DB connection failure: ' . $e->getMessage());
                $_SESSION['installer_db_tested'] = false;
                $connectionTested = false;
                $message = 'Unable to connect to the database service. Please verify your database host, port, database name, and credentials.';
                $messageType = 'danger';
            }
        }
    }
}

// Prefill form values from session if available
$stagedHost = $_SESSION['installer_db']['host'] ?? 'localhost';
$stagedPort = $_SESSION['installer_db']['port'] ?? '3306';
$stagedName = $_SESSION['installer_db']['name'] ?? 'loan_mgt';
$stagedUser = $_SESSION['installer_db']['user'] ?? 'root';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 2: Database Configuration — Loan Management System</title>
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
            <li class="installer-step-item active">
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

        <form action="<?php echo $appBaseUrl; ?>/installer/database.php" method="POST" id="dbForm">
            <input type="hidden" name="csrf_token" value="<?php echo installer_csrf_token(); ?>">
            <input type="hidden" name="action" id="formAction" value="test">

            <div class="installer-card-body p-4">
                <h2 class="h5 fw-bold text-dark mb-1">Step 2: Database Credentials</h2>
                <p class="text-muted small mb-4">
                    Enter the MySQL database connection parameters. Make sure the database exists in your hosting environment.
                </p>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo e($messageType); ?> small d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill'; ?> fs-5 me-3 text-<?php echo e($messageType); ?>"></i>
                        <div><?php echo e($message); ?></div>
                    </div>
                <?php endif; ?>

                <div class="row g-3">
                    <!-- Host -->
                    <div class="col-12 col-md-8">
                        <label for="db_host" class="form-label small fw-semibold">Database Host <span class="text-danger">*</span></label>
                        <input type="text" name="db_host" id="db_host" class="form-control" required value="<?php echo e($stagedHost); ?>" placeholder="e.g. localhost or 127.0.0.1">
                    </div>

                    <!-- Port -->
                    <div class="col-12 col-md-4">
                        <label for="db_port" class="form-label small fw-semibold">Port</label>
                        <input type="text" name="db_port" id="db_port" class="form-control font-monospace" value="<?php echo e($stagedPort); ?>" placeholder="3306">
                    </div>

                    <!-- Database Name -->
                    <div class="col-12">
                        <label for="db_name" class="form-label small fw-semibold">Database Name <span class="text-danger">*</span></label>
                        <input type="text" name="db_name" id="db_name" class="form-control" required value="<?php echo e($stagedName); ?>" placeholder="e.g. loan_mgt">
                        <div class="form-text">The target database must already exist in your MySQL server.</div>
                    </div>

                    <!-- Username -->
                    <div class="col-12 col-md-6">
                        <label for="db_user" class="form-label small fw-semibold">Database Username <span class="text-danger">*</span></label>
                        <input type="text" name="db_user" id="db_user" class="form-control" required value="<?php echo e($stagedUser); ?>" placeholder="e.g. root or db_user">
                    </div>

                    <!-- Password -->
                    <div class="col-12 col-md-6">
                        <label for="db_pass" class="form-label small fw-semibold">Database Password</label>
                        <input type="password" name="db_pass" id="db_pass" class="form-control" placeholder="Enter database password (if any)">
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top d-flex align-items-center justify-content-between">
                    <div class="small text-muted">
                        Click <strong>Test Connection</strong> to verify your settings before proceeding.
                    </div>
                    <button type="submit" onclick="document.getElementById('formAction').value='test'" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-plug-fill me-1"></i> Test Database Connection
                    </button>
                </div>
            </div>

            <div class="installer-card-footer">
                <a href="<?php echo $appBaseUrl; ?>/installer/requirements.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
                <?php if ($connectionTested): ?>
                    <button type="submit" onclick="document.getElementById('formAction').value='proceed'" class="btn btn-primary px-4">
                        Continue to Database Import <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                <?php else: ?>
                    <button type="submit" onclick="document.getElementById('formAction').value='test'" class="btn btn-primary px-4">
                        Test Connection & Proceed <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="installer-footer-text">
        &copy; <?php echo date('Y'); ?> Loan Management System &bull; Professional Edition
    </div>
</div>

<script src="<?php echo $appBaseUrl; ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
