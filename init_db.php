<?php
/**
 * Initialize Database with install.sql and Admin Account
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/install.php';

$pdo = get_db_connection();
$sql = file_get_contents(__DIR__ . '/database/install.sql');

echo "Importing install.sql into loan_mgt database...\n";
$res = run_sql_import($pdo, $sql);
if (!$res['success']) {
    die("Import Error: " . $res['error'] . "\n");
}
echo "Successfully executed " . $res['statements_executed'] . " SQL statements.\n";

// Insert/Ensure Administrator Account
$adminEmail = 'admin@loanmgt.com';
$adminUser  = 'admin';
$adminPass  = 'Admin@123456';
$adminHash  = password_hash($adminPass, PASSWORD_BCRYPT);

$roleStmt = $pdo->query("SELECT id FROM roles WHERE slug = 'admin' LIMIT 1");
$adminRoleId = (int)($roleStmt->fetchColumn() ?: 1);

$checkUser = $pdo->prepare("SELECT id FROM users WHERE email = :e OR username = :u LIMIT 1");
$checkUser->execute([':e' => $adminEmail, ':u' => $adminUser]);
$existingId = $checkUser->fetchColumn();

if ($existingId) {
    $upd = $pdo->prepare("
        UPDATE users SET 
            name = 'System Administrator',
            username = :u,
            email = :e,
            password = :p,
            role = 'admin',
            role_id = :rid,
            status = 'active'
        WHERE id = :id
    ");
    $upd->execute([
        ':u'   => $adminUser,
        ':e'   => $adminEmail,
        ':p'   => $adminHash,
        ':rid' => $adminRoleId,
        ':id'  => $existingId,
    ]);
    echo "Updated existing administrator account (ID #{$existingId}).\n";
} else {
    $ins = $pdo->prepare("
        INSERT INTO users (
            name, username, email, phone, password, role, role_id, status, created_at
        ) VALUES (
            'System Administrator', :u, :e, NULL, :p, 'admin', :rid, 'active', NOW()
        )
    ");
    $ins->execute([
        ':u'   => $adminUser,
        ':e'   => $adminEmail,
        ':p'   => $adminHash,
        ':rid' => $adminRoleId,
    ]);
    $newId = $pdo->lastInsertId();
    echo "Created default administrator account (ID #{$newId}): {$adminEmail} / {$adminPass}\n";
}

create_installation_lock(['admin_email' => $adminEmail, 'version' => '9.0.0']);
echo "Installation lock file updated at config/installed.lock\n";
