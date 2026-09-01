<?php
/**
 * Custom Role Store POST Handler
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

require_permission('roles.create');

// 2. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('danger', 'Security validation failed (CSRF token mismatch).');
    redirect('modules/roles/create.php');
}

$db = get_db_connection();

// 3. Collect & Sanitize Inputs
$name        = trim($_POST['name'] ?? '');
$slug        = strtolower(trim($_POST['slug'] ?? ''));
$description = trim($_POST['description'] ?? '');
$status      = trim($_POST['status'] ?? 'active');

// 4. Validate
$errors = [];

if (empty($name)) {
    $errors[] = 'Role name is required.';
} elseif (mb_strlen($name) > 100) {
    $errors[] = 'Role name cannot exceed 100 characters.';
}

if (empty($slug)) {
    $errors[] = 'Role slug identifier is required.';
} elseif (!preg_match('/^[a-z0-9_]{3,40}$/', $slug)) {
    $errors[] = 'Role slug must be 3-40 characters, consisting only of lowercase letters, numbers, and underscores.';
} else {
    // Check slug uniqueness
    $checkStmt = $db->prepare('SELECT id FROM roles WHERE slug = :s LIMIT 1');
    $checkStmt->execute([':s' => $slug]);
    if ($checkStmt->fetch()) {
        $errors[] = "A role with the slug '{$slug}' already exists.";
    }
}

if (!in_array($status, ['active', 'inactive'], true)) {
    $status = 'active';
}

if (!empty($errors)) {
    set_flash('danger', implode('<br>', $errors));
    redirect('modules/roles/create.php');
}

// 5. Insert Role
try {
    $insertStmt = $db->prepare('
        INSERT INTO roles (name, slug, description, is_system, status, created_at)
        VALUES (:name, :slug, :desc, 0, :status, NOW())
    ');
    $insertStmt->execute([
        ':name'   => $name,
        ':slug'   => $slug,
        ':desc'   => $description ?: null,
        ':status' => $status,
    ]);

    $newRoleId = (int)$db->lastInsertId();

    set_flash('success', "Role '{$name}' created successfully. You can now configure its permissions below.");
    redirect('modules/roles/permissions.php?id=' . $newRoleId);

} catch (Exception $e) {
    error_log('Role creation error: ' . $e->getMessage());
    set_flash('danger', 'A database error occurred while creating role.');
    redirect('modules/roles/create.php');
}
