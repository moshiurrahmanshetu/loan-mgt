<?php
/**
 * Commercial Installer - Step 4: Administrator Account & System Setup
 * Loan Management System (loan-mgt) - Phase 9
 */

require_once __DIR__ . '/../includes/install.php';

require_not_installed();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$appBaseUrl = get_app_base_url();

// Verify Step 3 completed
if (empty($_SESSION['installer_db']) || empty($_SESSION['installer_db_imported'])) {
    header('Location: ' . $appBaseUrl . '/installer/import.php');
    exit;
}

$dbConfig = $_SESSION['installer_db'];
$message = null;
$messageType = null;

// Supported Timezones
$timezones = [
    'America/New_York'    => 'Eastern Time (US & Canada) — UTC-5/UTC-4',
    'America/Chicago'     => 'Central Time (US & Canada) — UTC-6/UTC-5',
    'America/Denver'      => 'Mountain Time (US & Canada) — UTC-7/UTC-6',
    'America/Los_Angeles' => 'Pacific Time (US & Canada) — UTC-8/UTC-7',
    'Europe/London'       => 'London (GMT / BST) — UTC+0/UTC+1',
    'Asia/Dhaka'          => 'Dhaka (BST) — UTC+6',
    'Asia/Dubai'          => 'Dubai (GST) — UTC+4',
    'Asia/Kolkata'        => 'India Standard Time (IST) — UTC+5:30',
    'Asia/Singapore'      => 'Singapore (SGT) — UTC+8',
    'Asia/Tokyo'          => 'Tokyo (JST) — UTC+9',
    'UTC'                 => 'Coordinated Universal Time (UTC)',
];

// Handle Admin Account Creation & Finalization
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_installer_csrf_token()) {
        $message = 'Security validation failed (CSRF token mismatch). Please retry.';
        $messageType = 'danger';
    } else {
        $adminName       = trim($_POST['admin_name'] ?? '');
        $adminUsername   = trim($_POST['admin_username'] ?? 'admin');
        $adminEmail      = trim($_POST['admin_email'] ?? '');
        $adminPass       = (string)($_POST['admin_pass'] ?? '');
        $adminPassConf   = (string)($_POST['admin_pass_conf'] ?? '');
        $companyName     = trim($_POST['company_name'] ?? 'Loan Management System');
        $systemName      = trim($_POST['system_name'] ?? 'LoanMgt');
        $currencySymbol  = trim($_POST['currency_symbol'] ?? '$');
        $timezone        = trim($_POST['timezone'] ?? 'America/New_York');

        $errors = [];

        if (empty($adminName)) {
            $errors[] = 'Administrator full name is required.';
        }
        if (empty($adminUsername) || !preg_match('/^[a-zA-Z0-9_\.]{3,30}$/', $adminUsername)) {
            $errors[] = 'Username must be 3-30 characters (letters, numbers, underscores, periods only).';
        }
        if (empty($adminEmail) || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid administrator email address is required.';
        }
        if (empty($adminPass) || mb_strlen($adminPass) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        } elseif ($adminPass !== $adminPassConf) {
            $errors[] = 'Password confirmation does not match.';
        }
        if (empty($companyName) || empty($systemName) || empty($currencySymbol)) {
            $errors[] = 'Company name, system brand name, and currency symbol are required.';
        }

        if (!empty($errors)) {
            $message = implode('<br>', $errors);
            $messageType = 'danger';
        } else {
            try {
                $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']};charset=utf8mb4";
                $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);

                // 1. Resolve Administrator Role ID
                $roleStmt = $pdo->query("SELECT id FROM roles WHERE slug = 'admin' LIMIT 1");
                $adminRoleId = (int)($roleStmt->fetchColumn() ?: 1);

                // 2. Hash password with BCRYPT
                $passwordHash = password_hash($adminPass, PASSWORD_BCRYPT);

                // 3. Insert or update Admin User
                $checkUserStmt = $pdo->prepare("SELECT id FROM users WHERE email = :e OR username = :u LIMIT 1");
                $checkUserStmt->execute([':e' => $adminEmail, ':u' => $adminUsername]);
                $existingAdminId = $checkUserStmt->fetchColumn();

                if ($existingAdminId) {
                    $updateAdminStmt = $pdo->prepare("
                        UPDATE users SET
                            name = :name,
                            username = :username,
                            email = :email,
                            password = :password,
                            role = 'admin',
                            role_id = :role_id,
                            status = 'active'
                        WHERE id = :id
                    ");
                    $updateAdminStmt->execute([
                        ':name'     => $adminName,
                        ':username' => $adminUsername,
                        ':email'    => $adminEmail,
                        ':password' => $passwordHash,
                        ':role_id'  => $adminRoleId,
                        ':id'       => $existingAdminId,
                    ]);
                } else {
                    $insertAdminStmt = $pdo->prepare("
                        INSERT INTO users (
                            name, username, email, phone, password, role, role_id, status, created_at
                        ) VALUES (
                            :name, :username, :email, NULL, :password, 'admin', :role_id, 'active', NOW()
                        )
                    ");
                    $insertAdminStmt->execute([
                        ':name'     => $adminName,
                        ':username' => $adminUsername,
                        ':email'    => $adminEmail,
                        ':password' => $passwordHash,
                        ':role_id'  => $adminRoleId,
                    ]);
                }

                // 4. Update initial system settings
                $setStmt = $pdo->prepare("
                    INSERT INTO settings (setting_key, setting_value, setting_type)
                    VALUES (:key, :val, :type)
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
                ");

                $initialSettings = [
                    'company_name'    => [$companyName, 'text'],
                    'system_name'     => [$systemName, 'text'],
                    'company_email'   => [$adminEmail, 'text'],
                    'currency_symbol' => [$currencySymbol, 'text'],
                    'timezone'        => [$timezone, 'text'],
                ];

                foreach ($initialSettings as $k => $data) {
                    $setStmt->execute([
                        ':key'  => $k,
                        ':val'  => $data[0],
                        ':type' => $data[1],
                    ]);
                }

                // 5. Generate config/database.php
                $configWritten = generate_database_config(
                    $dbConfig['host'],
                    $dbConfig['name'],
                    $dbConfig['user'],
                    $dbConfig['pass'],
                    $dbConfig['port']
                );

                if (!$configWritten) {
                    throw new Exception('Unable to write config/database.php. Please verify that config/ directory is writable.');
                }

                // 6. Create config/installed.lock
                $lockCreated = create_installation_lock([
                    'installed_at' => date('c'),
                    'version'      => '9.0.0',
                    'admin_email'  => $adminEmail,
                ]);

                if (!$lockCreated) {
                    throw new Exception('Unable to create config/installed.lock. Please verify directory permissions.');
                }

                // 7. Cleanup sensitive session data & set completed state
                unset($_SESSION['installer_db']);
                $_SESSION['installer_completed']   = true;
                $_SESSION['installer_admin_email'] = $adminEmail;
                $_SESSION['installer_admin_user']  = $adminUsername;

                header('Location: ' . $appBaseUrl . '/installer/complete.php');
                exit;

            } catch (Exception $e) {
                error_log('Installer admin setup error: ' . $e->getMessage());
                $message = 'Setup error: ' . $e->getMessage();
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
    <title>Step 4: Administrator Setup — Loan Management System</title>
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
            <li class="installer-step-item active">
                <span class="step-num">4</span>
                <span class="step-label">Admin</span>
            </li>
            <li class="installer-step-item">
                <span class="step-num">5</span>
                <span class="step-label">Complete</span>
            </li>
        </ul>

        <form action="<?php echo $appBaseUrl; ?>/installer/admin.php" method="POST" id="adminForm">
            <input type="hidden" name="csrf_token" value="<?php echo installer_csrf_token(); ?>">

            <div class="installer-card-body p-4">
                <h2 class="h5 fw-bold text-dark mb-1">Step 4: Administrator Account & System Branding</h2>
                <p class="text-muted small mb-4">
                    Create your primary administrative account and configure your initial lending platform preferences.
                </p>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo e($messageType); ?> small d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-exclamation-circle-fill fs-5 me-3 text-<?php echo e($messageType); ?>"></i>
                        <div><?php echo $message; ?></div>
                    </div>
                <?php endif; ?>

                <!-- Section 1: Administrator Account -->
                <div class="card shadow-none border mb-4">
                    <div class="card-header bg-light py-2.5">
                        <h3 class="h6 mb-0 fw-bold text-dark"><i class="bi bi-person-badge-fill me-2 text-primary"></i> Super Administrator Credentials</h3>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="admin_name" class="form-label small fw-semibold">Administrator Name <span class="text-danger">*</span></label>
                                <input type="text" name="admin_name" id="admin_name" class="form-control" required value="<?php echo e($_POST['admin_name'] ?? 'System Administrator'); ?>" placeholder="e.g. Johnathan Smith">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="admin_username" class="form-label small fw-semibold">Username <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">@</span>
                                    <input type="text" name="admin_username" id="admin_username" class="form-control" required value="<?php echo e($_POST['admin_username'] ?? 'admin'); ?>" pattern="^[a-zA-Z0-9_\.]{3,30}$">
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="admin_email" class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="admin_email" id="admin_email" class="form-control" required value="<?php echo e($_POST['admin_email'] ?? 'admin@loanmgt.com'); ?>" placeholder="admin@yourcompany.com">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="admin_pass" class="form-label small fw-semibold">Password <span class="text-danger">*</span></label>
                                <input type="password" name="admin_pass" id="admin_pass" class="form-control" required minlength="8" placeholder="Minimum 8 characters">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="admin_pass_conf" class="form-label small fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" name="admin_pass_conf" id="admin_pass_conf" class="form-control" required minlength="8" placeholder="Re-enter password">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Organization & Regional Settings -->
                <div class="card shadow-none border mb-0">
                    <div class="card-header bg-light py-2.5">
                        <h3 class="h6 mb-0 fw-bold text-dark"><i class="bi bi-gear-fill me-2 text-primary"></i> Initial Organization & Regional Preferences</h3>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="company_name" class="form-label small fw-semibold">Company / Organization Name <span class="text-danger">*</span></label>
                                <input type="text" name="company_name" id="company_name" class="form-control" required value="<?php echo e($_POST['company_name'] ?? 'Loan Management System'); ?>">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="system_name" class="form-label small fw-semibold">Brand / Application Name <span class="text-danger">*</span></label>
                                <input type="text" name="system_name" id="system_name" class="form-control" required value="<?php echo e($_POST['system_name'] ?? 'LoanMgt'); ?>">
                            </div>

                            <div class="col-12 col-md-4">
                                <label for="currency_symbol" class="form-label small fw-semibold">Currency Symbol <span class="text-danger">*</span></label>
                                <input type="text" name="currency_symbol" id="currency_symbol" class="form-control font-monospace" required value="<?php echo e($_POST['currency_symbol'] ?? '$'); ?>" maxlength="5">
                            </div>

                            <div class="col-12 col-md-8">
                                <label for="timezone" class="form-label small fw-semibold">Timezone <span class="text-danger">*</span></label>
                                <select name="timezone" id="timezone" class="form-select" required>
                                    <?php foreach ($timezones as $tzKey => $tzLabel): ?>
                                        <option value="<?php echo e($tzKey); ?>" <?php echo (($_POST['timezone'] ?? 'America/New_York') === $tzKey) ? 'selected' : ''; ?>>
                                            <?php echo e($tzLabel); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="installer-card-footer">
                <a href="<?php echo $appBaseUrl; ?>/installer/import.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
                <button type="submit" id="btnFinish" class="btn btn-primary px-4">
                    <i class="bi bi-check-circle-fill me-1"></i> Complete Installation & Lock <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>
        </form>
    </div>

    <div class="installer-footer-text">
        &copy; <?php echo date('Y'); ?> Loan Management System &bull; Professional Edition
    </div>
</div>

<script>
document.getElementById('adminForm').addEventListener('submit', function() {
    var btn = document.getElementById('btnFinish');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Finalizing Installation...';
});
</script>
<script src="<?php echo $appBaseUrl; ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
