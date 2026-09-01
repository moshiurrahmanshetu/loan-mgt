<?php
/**
 * Profile Update Handler
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
    set_flash('danger', 'Security token expired. Please try updating your profile again.');
    redirect('modules/profile/index.php');
}

$userId = auth_id();
$name   = trim($_POST['name'] ?? '');
$email  = trim($_POST['email'] ?? '');
$phone  = trim($_POST['phone'] ?? '');

// 2. Validate Inputs
if (empty($name) || mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    set_flash('danger', 'Please enter a valid full name between 2 and 100 characters.');
    redirect('modules/profile/index.php');
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
    set_flash('danger', 'Please enter a valid email address.');
    redirect('modules/profile/index.php');
}

if (!empty($phone) && mb_strlen($phone) > 30) {
    set_flash('danger', 'Phone number must not exceed 30 characters.');
    redirect('modules/profile/index.php');
}

try {
    $db = get_db_connection();

    // 3. Verify Email Uniqueness (excluding current user)
    $stmtCheck = $db->prepare('SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1');
    $stmtCheck->execute([
        ':email' => $email,
        ':id'    => $userId
    ]);

    if ($stmtCheck->fetch()) {
        set_flash('danger', 'The email address "' . $email . '" is already registered to another account.');
        redirect('modules/profile/index.php');
    }

    // 4. Update Database
    $stmtUpdate = $db->prepare('UPDATE users SET name = :name, email = :email, phone = :phone WHERE id = :id');
    $stmtUpdate->execute([
        ':name'  => $name,
        ':email' => $email,
        ':phone' => $phone ?: null,
        ':id'    => $userId
    ]);

    // 5. Update Active Session
    $_SESSION['user_name']  = $name;
    $_SESSION['user_email'] = $email;

    set_flash('success', 'Your personal details have been updated successfully.');
    redirect('modules/profile/index.php');

} catch (Exception $e) {
    error_log('Profile update error: ' . $e->getMessage());
    set_flash('danger', 'An error occurred while saving your profile changes. Please try again.');
    redirect('modules/profile/index.php');
}
