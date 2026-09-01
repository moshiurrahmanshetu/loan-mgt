<?php
/**
 * Role Deletion POST Handler
 * Loan Management System (loan-mgt) - Phase 8
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// 1. Method & Permission Guard
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/roles/index.php');
}

require_permission('roles.delete');

// 2. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('danger', 'Security validation failed (CSRF token mismatch).');
    redirect('modules/roles/index.php');
}

$roleId = (int)($_POST['id'] ?? 0);
if ($roleId <= 0) {
    set_flash('danger', 'Invalid role ID.');
    redirect('modules/roles/index.php');
}

$db = get_db_connection();

// 3. Fetch Role Record
$stmt = $db->prepare('SELECT * FROM roles WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $roleId]);
$role = $stmt->fetch();

if (!$role) {
    set_flash('danger', 'Role not found.');
    redirect('modules/roles/index.php');
}

// 4. System Role Protection
if (!empty($role['is_system']) || in_array($role['slug'], ['admin', 'manager', 'loan_officer', 'collector'], true)) {
    set_flash('danger', "Protected System Role: The '{$role['name']}' role is required for core system operation and cannot be deleted.");
    redirect('modules/roles/index.php');
}

// 5. Assigned Users Protection
$userCountStmt = $db->prepare('SELECT COUNT(*) FROM users WHERE role_id = :rid');
$userCountStmt->execute([':rid' => $roleId]);
$assignedCount = (int)$userCountStmt->fetchColumn();

if ($assignedCount > 0) {
    set_flash('warning', "Cannot delete role '{$role['name']}': This role is currently assigned to {$assignedCount} staff account(s). Please reassign those users to another role before deleting.");
    redirect('modules/roles/index.php');
}

// 6. Delete Role
try {
    $deleteStmt = $db->prepare('DELETE FROM roles WHERE id = :id');
    $deleteStmt->execute([':id' => $roleId]);

    set_flash('success', "Custom role '{$role['name']}' has been deleted successfully.");
} catch (Exception $e) {
    error_log('Role deletion error: ' . $e->getMessage());
    set_flash('danger', 'A database error occurred while deleting role.');
}

redirect('modules/roles/index.php');
