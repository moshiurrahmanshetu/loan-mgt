<?php
/**
 * Authentication Guard
 * Loan Management System (loan-mgt) - Phase 1
 *
 * Protects pages from unauthenticated access.
 */

require_once __DIR__ . '/install.php';
require_installed();

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/flash.php';

// Prevent browser from caching protected views
send_security_headers(true);

if (!is_logged_in()) {
    set_flash('danger', 'Your session has expired or you are not authorized. Please log in to continue.');
    redirect('auth/login.php');
}

// Live Session Integrity Check: Verify account status & refresh role
try {
    require_once __DIR__ . '/../config/database.php';
    $dbAuthCheck = get_db_connection();
    $authCheckStmt = $dbAuthCheck->prepare('
        SELECT u.id, u.name, u.email, u.avatar, u.status, u.role_id,
               COALESCE(r.slug, u.role) AS active_role_slug
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE u.id = :id LIMIT 1
    ');
    $authCheckStmt->execute([':id' => $_SESSION['user_id']]);
    $liveUser = $authCheckStmt->fetch();

    if (!$liveUser || $liveUser['status'] !== 'active') {
        // Account has been deleted or deactivated - invalidate session immediately
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        
        session_start();
        set_flash('danger', 'Your account has been deactivated or disabled. Please contact an administrator.');
        redirect('auth/login.php');
    }

    // Synchronize session payload with live database attributes
    $_SESSION['user_name']   = $liveUser['name'];
    $_SESSION['user_email']  = $liveUser['email'];
    $_SESSION['user_avatar'] = $liveUser['avatar'];
    $_SESSION['user_role']   = $liveUser['active_role_slug'];

} catch (Exception $e) {
    error_log('Session validation database error: ' . $e->getMessage());
}
