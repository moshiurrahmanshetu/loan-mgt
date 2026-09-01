<?php
/**
 * Save Role Permissions Transactional POST Handler
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

$roleId = (int)($_POST['role_id'] ?? 0);
if ($roleId <= 0) {
    set_flash('danger', 'Invalid role ID.');
    redirect('modules/roles/index.php');
}

$db = get_db_connection();

// 3. Verify Role Record
$stmt = $db->prepare('SELECT id, name, slug FROM roles WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $roleId]);
$role = $stmt->fetch();

if (!$role) {
    set_flash('danger', 'Role not found.');
    redirect('modules/roles/index.php');
}

// 4. Sanitize Submitted Permission IDs
$selectedPermIds = array_filter(array_map('intval', (array)($_POST['permissions'] ?? [])), function($id) {
    return $id > 0;
});

// 5. Execute Transactional Update
try {
    $db->beginTransaction();

    // Remove existing role assignments
    $deleteStmt = $db->prepare('DELETE FROM role_permissions WHERE role_id = :rid');
    $deleteStmt->execute([':rid' => $roleId]);

    // Insert newly selected permissions
    if (!empty($selectedPermIds)) {
        // Validate submitted permission IDs exist in permissions table
        $placeholders = implode(',', array_fill(0, count($selectedPermIds), '?'));
        $validPermStmt = $db->prepare("SELECT id FROM permissions WHERE id IN ({$placeholders})");
        $validPermStmt->execute(array_values($selectedPermIds));
        $validPermIds = $validPermStmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($validPermIds)) {
            $insertStmt = $db->prepare('INSERT INTO role_permissions (role_id, permission_id, created_at) VALUES (:rid, :pid, NOW())');
            foreach ($validPermIds as $pid) {
                $insertStmt->execute([
                    ':rid' => $roleId,
                    ':pid' => (int)$pid
                ]);
            }
        }
    }

    $db->commit();

    $count = count($selectedPermIds);
    set_flash('success', "Permissions for role '{$role['name']}' updated successfully ({$count} granted).");
    redirect('modules/roles/permissions.php?id=' . $roleId);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Save role permissions error: ' . $e->getMessage());
    set_flash('danger', 'A database transaction error occurred while saving role permissions.');
    redirect('modules/roles/permissions.php?id=' . $roleId);
}
