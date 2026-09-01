<?php
/**
 * User Account Deletion POST Handler
 * Loan Management System (loan-mgt) - Phase 8
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// 1. Method & Permission Guard
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/users/index.php');
}

require_permission('users.delete');

// 2. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('danger', 'Security validation failed (CSRF token mismatch).');
    redirect('modules/users/index.php');
}

$userId = (int)($_POST['id'] ?? 0);
if ($userId <= 0) {
    set_flash('danger', 'Invalid user account.');
    redirect('modules/users/index.php');
}

// 3. Self-Deletion Protection
$currentId = auth_id();
if ($userId === (int)$currentId) {
    set_flash('danger', 'Action blocked: You cannot delete your own currently logged-in account.');
    redirect('modules/users/index.php');
}

$db = get_db_connection();

// 4. Fetch User Record
$stmt = $db->prepare('SELECT id, name, avatar FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('danger', 'User account not found.');
    redirect('modules/users/index.php');
}

// 5. Check Historical Record Attachments
$hasLoans      = (int)$db->query("SELECT COUNT(*) FROM loans WHERE created_by = {$userId} OR approved_by = {$userId} OR disbursed_by = {$userId}")->fetchColumn();
$hasPayments   = (int)$db->query("SELECT COUNT(*) FROM loan_payments WHERE collected_by = {$userId}")->fetchColumn();
$hasCustomers  = (int)$db->query("SELECT COUNT(*) FROM customers WHERE created_by = {$userId}")->fetchColumn();
$hasProducts   = (int)$db->query("SELECT COUNT(*) FROM loan_products WHERE created_by = {$userId}")->fetchColumn();

if ($hasLoans > 0 || $hasPayments > 0 || $hasCustomers > 0 || $hasProducts > 0) {
    set_flash('warning', "Cannot delete user '{$user['name']}': This account is linked to historical financial records (loans, payments, or customer profiles). To preserve audit integrity, please deactivate the account instead.");
    redirect('modules/users/index.php');
}

// 6. Delete User Record
try {
    $deleteStmt = $db->prepare('DELETE FROM users WHERE id = :id');
    $deleteStmt->execute([':id' => $userId]);

    // Clean avatar file if exists
    if (!empty($user['avatar'])) {
        $avatarPath = AVATAR_UPLOAD_DIR . DIRECTORY_SEPARATOR . $user['avatar'];
        if (file_exists($avatarPath)) {
            @unlink($avatarPath);
        }
    }

    set_flash('success', "User '{$user['name']}' has been permanently deleted.");
} catch (Exception $e) {
    error_log('User deletion error: ' . $e->getMessage());
    set_flash('danger', 'A database error occurred while deleting user account.');
}

redirect('modules/users/index.php');
