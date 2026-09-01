<?php
/**
 * Authentication Handler
 * Loan Management System (loan-mgt) - Phase 1
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/flash.php';

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('auth/login.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('danger', 'Invalid or expired security token. Please submit the form again.');
    redirect('auth/login.php');
}

// 2. Validate input presence
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$_SESSION['_old_email'] = $email;

if (empty($email) || empty($password)) {
    set_flash('danger', 'Please provide both your email address and password.');
    redirect('auth/login.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('danger', 'Invalid email address format.');
    redirect('auth/login.php');
}

try {
    $db = get_db_connection();

    // 3. Find user by email using prepared statement
    $stmt = $db->prepare('SELECT id, name, email, phone, password, avatar, role, status, last_login FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    // Generic error message to prevent account enumeration
    $invalidCredentialsMsg = 'Invalid email address or password.';

    if (!$user) {
        set_flash('danger', $invalidCredentialsMsg);
        redirect('auth/login.php');
    }

    // 4. Check user account status
    if ($user['status'] !== 'active') {
        set_flash('danger', 'Your account has been deactivated. Please contact a system administrator.');
        redirect('auth/login.php');
    }

    // 5. Verify password hash
    if (!password_verify($password, $user['password'])) {
        set_flash('danger', $invalidCredentialsMsg);
        redirect('auth/login.php');
    }

    // 6. Regenerate session ID to prevent fixation
    regenerate_user_session(true);

    // 7. Store authentication information in session
    $_SESSION['user_id']         = (int)$user['id'];
    $_SESSION['user_name']       = $user['name'];
    $_SESSION['user_email']      = $user['email'];
    $_SESSION['user_role']       = $user['role'];
    $_SESSION['user_avatar']     = $user['avatar'];
    $_SESSION['user_last_login'] = $user['last_login'];
    $_SESSION['logged_in']       = true;

    // Clear old email flash
    unset($_SESSION['_old_email']);

    // 8. Update last_login timestamp
    $updateStmt = $db->prepare('UPDATE users SET last_login = NOW() WHERE id = :id');
    $updateStmt->execute([':id' => $user['id']]);

    // 9. Redirect to dashboard
    set_flash('success', 'Welcome back, ' . $user['name'] . '!');
    redirect('modules/dashboard/index.php');

} catch (Exception $e) {
    error_log('Authentication error: ' . $e->getMessage());
    set_flash('danger', 'An unexpected error occurred while authenticating. Please try again later.');
    redirect('auth/login.php');
}
