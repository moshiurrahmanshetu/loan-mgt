<?php
/**
 * User Account Status Toggle Handler (Activate/Deactivate)
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

require_permission('users.edit');

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

// 3. Self-Deactivation Protection
$currentId = auth_id();
if ($userId === (int)$currentId) {
    set_flash('danger', 'Action blocked: You cannot deactivate your own active administrator account.');
    redirect('modules/users/index.php');
}

$db = get_db_connection();

// 4. Fetch User Record
$stmt = $db->prepare('SELECT id, name, status FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('danger', 'User account not found.');
    redirect('modules/users/index.php');
}

$newStatus = ($user['status'] === 'active') ? 'inactive' : 'active';

try {
    $updateStmt = $db->prepare('UPDATE users SET status = :st WHERE id = :id');
    $updateStmt->execute([':st' => $newStatus, ':id' => $userId]);

    $actionWord = ($newStatus === 'active') ? 'activated' : 'deactivated';
    set_flash('success', "User '{$user['name']}' has been {$actionWord} successfully.");
} catch (Exception $e) {
    error_log('Status toggle error: ' . $e->getMessage());
    set_flash('danger', 'A database error occurred while updating account status.');
}

redirect('modules/users/index.php');
