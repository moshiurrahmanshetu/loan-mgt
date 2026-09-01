<?php
/**
 * Commercial Installer - Step 3: Database Schema Import
 * Loan Management System (loan-mgt) - Phase 9
 */

require_once __DIR__ . '/../includes/install.php';

require_not_installed();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$appBaseUrl = get_app_base_url();

// Verify Step 2 completed
if (empty($_SESSION['installer_db']) || empty($_SESSION['installer_db_tested'])) {
    header('Location: ' . $appBaseUrl . '/installer/database.php');
    exit;
}

$dbConfig = $_SESSION['installer_db'];
$message = null;
$messageType = null;
$importCompleted = !empty($_SESSION['installer_db_imported']);

// Establish PDO connection for checks and import
try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    header('Location: ' . $appBaseUrl . '/installer/database.php');
    exit;
}

// Check existing tables
$existingTables = [];
try {
    $tblStmt = $pdo->query("SHOW TABLES");
    $existingTables = $tblStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
} catch (Exception $e) {}

$hasExistingAppTables = false;
$appKeyTables = ['users', 'roles', 'permissions', 'settings', 'loans', 'customers'];
foreach ($appKeyTables as $tbl) {
    if (in_array($tbl, $existingTables, true)) {
        $hasExistingAppTables = true;
        break;
    }
}

// Handle Import POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_installer_csrf_token()) {
        $message = 'Security validation failed (CSRF token mismatch). Please retry.';
        $messageType = 'danger';
    } else {
        $sourceType = $_POST['sql_source'] ?? 'default';
        $sqlContent = '';

        if ($sourceType === 'default') {
            $defaultSqlPath = ROOT_PATH . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'install.sql';
            if (!file_exists($defaultSqlPath)) {
                $message = 'Default bundled install.sql package was not found on server disk.';
                $messageType = 'danger';
            } else {
                $sqlContent = file_get_contents($defaultSqlPath);
            }
        } elseif ($sourceType === 'upload') {
            if (!isset($_FILES['custom_sql']) || $_FILES['custom_sql']['error'] !== UPLOAD_ERR_OK) {
                $message = 'Please select a valid .sql file for upload.';
                $messageType = 'danger';
            } else {
                $file = $_FILES['custom_sql'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if ($ext !== 'sql') {
                    $message = 'Invalid file format. Only .sql files are allowed for database import.';
                    $messageType = 'danger';
                } elseif ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
                    $message = 'Uploaded SQL file size exceeds the 10MB limit.';
                    $messageType = 'danger';
                } else {
                    $sqlContent = file_get_contents($file['tmp_name']);
                }
            }
        }

        if (!empty($sqlContent)) {
            $importResult = run_sql_import($pdo, $sqlContent);

            if ($importResult['success']) {
                $_SESSION['installer_db_imported'] = true;
                $_SESSION['installer_statements_count'] = $importResult['statements_executed'];
                header('Location: ' . $appBaseUrl . '/installer/admin.php');
                exit;
            } else {
                $_SESSION['installer_db_imported'] = false;
                $message = 'Database import error: ' . $importResult['error'] . ' (Statement: ' . e($importResult['statement']) . ')';
                $messageType = 'danger';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 3: Database Import — Loan Management System</title>
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
            <li class="installer-step-item active">
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

        <form action="<?php echo $appBaseUrl; ?>/installer/import.php" method="POST" enctype="multipart/form-data" id="importForm">
            <input type="hidden" name="csrf_token" value="<?php echo installer_csrf_token(); ?>">

            <div class="installer-card-body p-4">
                <h2 class="h5 fw-bold text-dark mb-1">Step 3: Initialize Database Schema</h2>
                <p class="text-muted small mb-4">
                    Import the application tables, granular permission matrix, default system roles, and lending templates into <strong><?php echo e($dbConfig['name']); ?></strong>.
                </p>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo e($messageType); ?> small d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-exclamation-circle-fill fs-5 me-3 text-<?php echo e($messageType); ?>"></i>
                        <div><?php echo $message; ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($hasExistingAppTables): ?>
                    <div class="alert alert-warning small d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-5 me-3 text-warning"></i>
                        <div>
                            <strong>Existing Tables Detected:</strong> The database <code><?php echo e($dbConfig['name']); ?></code> already contains application tables. The installer will create any missing tables and ensure system roles and permissions are up to date.
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Import Option Radios -->
                <div class="d-flex flex-column gap-3 mb-4">
                    <!-- Option A: Bundled Default -->
                    <div class="p-3 bg-light rounded border">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="sql_source" id="source_default" value="default" checked onchange="toggleCustomUpload(false)">
                            <label class="form-check-label" for="source_default">
                                <strong class="text-dark d-block">Use Bundled Master SQL Package (Recommended)</strong>
                                <span class="small text-muted d-block">
                                    Imports <code>database/install.sql</code> containing all 10 relational tables, 32 granular permissions, 4 system roles, and standard lending product templates.
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Option B: Custom Upload -->
                    <div class="p-3 bg-light rounded border">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="sql_source" id="source_upload" value="upload" onchange="toggleCustomUpload(true)">
                            <label class="form-check-label" for="source_upload">
                                <strong class="text-dark d-block">Upload Custom SQL File</strong>
                                <span class="small text-muted d-block">
                                    Provide a custom compatible <code>.sql</code> script (e.g. customized organizational seeds).
                                </span>
                            </label>
                        </div>
                        <div id="customUploadBox" style="display: none;" class="mt-2 ps-4">
                            <input type="file" name="custom_sql" id="custom_sql" class="form-control form-control-sm" accept=".sql">
                            <div class="form-text small">Accepted format: .sql files only (Max: 10MB).</div>
                        </div>
                    </div>
                </div>

                <div id="importingNotice" style="display: none;" class="alert alert-info small align-items-center">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                    <span>Importing database tables and system seeds, please wait...</span>
                </div>
            </div>

            <div class="installer-card-footer">
                <a href="<?php echo $appBaseUrl; ?>/installer/database.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
                <button type="submit" id="btnImport" class="btn btn-primary px-4">
                    <i class="bi bi-database-fill-check me-1"></i> Import Database & Continue <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>
        </form>
    </div>

    <div class="installer-footer-text">
        &copy; <?php echo date('Y'); ?> Loan Management System &bull; Professional Edition
    </div>
</div>

<script>
function toggleCustomUpload(show) {
    document.getElementById('customUploadBox').style.display = show ? 'block' : 'none';
}

document.getElementById('importForm').addEventListener('submit', function() {
    var btn = document.getElementById('btnImport');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Importing...';
    document.getElementById('importingNotice').style.display = 'flex';
});
</script>
<script src="<?php echo $appBaseUrl; ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
