<?php
/**
 * Safe Session Management Configuration
 * Loan Management System (loan-mgt) - Phase 1
 */

if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        // Determine if connection is secure
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

        // Set secure session cookie parameters
        session_set_cookie_params([
            'lifetime' => 0, // Session cookie persists until browser is closed
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        // Name custom session to avoid default PHPSESSID collisions
        session_name('LOAN_MGT_SESS');

        session_start();
    } elseif (php_sapi_name() === 'cli') {
        @session_start();
    }
}

/**
 * Regenerates the session ID securely to prevent session fixation attacks.
 *
 * @param bool $deleteOldSession Whether to delete the old associated session file
 * @return bool
 */
function regenerate_user_session(bool $deleteOldSession = true): bool
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return session_regenerate_id($deleteOldSession);
    }
    return false;
}
