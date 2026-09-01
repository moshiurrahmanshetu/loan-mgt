<?php
/**
 * Role Update POST Handler
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

require_permission('roles.edit');

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

// 4. Collect & Sanitize Inputs
$name        = $role['is_system'] ? $role['name'] : trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$status      = $role['is_system'] ? 'active' : trim($_POST['status'] ?? 'active');

$errors = [];
if (empty($name)) {
    $errors[] = 'Role name cannot be blank.';
} elseif (mb_strlen($name) > 100) {
    $errors[] = 'Role name cannot exceed 100 characters.';
}

if (!in_array($status, ['active', 'inactive'], true)) {
    $status = 'active';
}

if (!empty($errors)) {
    set_flash('danger', implode('<br>', $errors));
    redirect('modules/roles/edit.php?id=' . $roleId);
}

// 5. Update Database Record
try {
    $updateStmt = $db->prepare('
        UPDATE roles SET
            name        = :name,
            description = :desc,
            status      = :status
        WHERE id = :id
    ');
    $updateStmt->execute([
        ':name'   => $name,
        ':desc'   => $description ?: null,
        ':status' => $status,
        ':id'     => $roleId,
    ]);

    set_flash('success', "Role '{$name}' details updated successfully.");
    redirect('modules/roles/view.php?id=' . $roleId);

} catch (Exception $e) {
    error_log('Role update error: ' . $e->getMessage());
    set_flash('danger', 'A database error occurred while updating role.');
    redirect('modules/roles/edit.php?id=' . $roleId);
}
