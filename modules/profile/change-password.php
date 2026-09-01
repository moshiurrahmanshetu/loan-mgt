<?php
/**
 * Password Change Handler
 * Loan Management System (loan-mgt) - Phase 1
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/flash.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/profile/index.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('danger', 'Security token expired. Please try changing your password again.');
    redirect('modules/profile/index.php#password-section');
}

$userId          = auth_id();
$currentPassword = $_POST['current_password'] ?? '';
$newPassword     = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// 2. Validate Inputs
if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    set_flash('danger', 'All password fields are required.');
    redirect('modules/profile/index.php#password-section');
}

if (mb_strlen($newPassword) < 8) {
    set_flash('danger', 'Your new password must be at least 8 characters long.');
    redirect('modules/profile/index.php#password-section');
}

if ($newPassword !== $confirmPassword) {
    set_flash('danger', 'The new password and confirmation password do not match.');
    redirect('modules/profile/index.php#password-section');
}

try {
    $db = get_db_connection();

    // 3. Fetch Current Password Hash
    $stmt = $db->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($currentPassword, $user['password'])) {
        set_flash('danger', 'The current password you entered is incorrect.');
        redirect('modules/profile/index.php#password-section');
    }

    // 4. Hash and Update New Password
    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
    $updateStmt = $db->prepare('UPDATE users SET password = :password WHERE id = :id');
    $updateStmt->execute([
        ':password' => $newHash,
        ':id'       => $userId
    ]);

    set_flash('success', 'Your password has been changed successfully.');
    redirect('modules/profile/index.php#password-section');

} catch (Exception $e) {
    error_log('Password update error: ' . $e->getMessage());
    set_flash('danger', 'An error occurred while updating your password. Please try again.');
    redirect('modules/profile/index.php#password-section');
}
