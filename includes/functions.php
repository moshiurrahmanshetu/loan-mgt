<?php
/**
 * Global Helper Functions
 * Loan Management System (loan-mgt) - Phase 1
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';

/**
 * Escapes HTML output securely.
 *
 * @param mixed $value
 * @return string
 */
function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Generates an application URL.
 *
 * @param string $path
 * @return string
 */
function url(string $path = ''): string
{
    $cleanPath = ltrim($path, '/');
    return rtrim(BASE_URL, '/') . ($cleanPath !== '' ? '/' . $cleanPath : '');
}

/**
 * Generates an asset URL.
 *
 * @param string $path
 * @return string
 */
function asset(string $path = ''): string
{
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Performs a safe HTTP redirect and terminates execution.
 *
 * @param string $path Target path or full URL
 * @return void
 */
function redirect(string $path): void
{
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        $target = $path;
    } else {
        $target = url($path);
    }
    header("Location: " . $target);
    exit;
}

/**
 * Returns the current CSRF token, generating one if it does not exist.
 *
 * @return string
 */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

/**
 * Renders a hidden HTML input containing the CSRF token.
 *
 * @return string
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Verifies the submitted CSRF token against the session token.
 *
 * @param string|null $token Submitted token (defaults to $_POST['_csrf_token'])
 * @return bool
 */
function verify_csrf_token(?string $token = null): bool
{
    if ($token === null) {
        $token = $_POST['_csrf_token'] ?? '';
    }
    $sessionToken = $_SESSION['_csrf_token'] ?? '';

    if (empty($token) || empty($sessionToken)) {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

/**
 * Checks whether the current user is authenticated.
 *
 * @return bool
 */
function is_logged_in(): bool
{
    return !empty($_SESSION['logged_in']) && !empty($_SESSION['user_id']);
}

/**
 * Retrieves the authenticated user's session payload.
 *
 * @return array|null
 */
function auth_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }

    return [
        'id'         => $_SESSION['user_id'] ?? null,
        'name'       => $_SESSION['user_name'] ?? 'User',
        'email'      => $_SESSION['user_email'] ?? '',
        'role'       => $_SESSION['user_role'] ?? 'loan_officer',
        'avatar'     => $_SESSION['user_avatar'] ?? null,
        'last_login' => $_SESSION['user_last_login'] ?? null,
    ];
}

/**
 * Returns the ID of the authenticated user.
 *
 * @return int|null
 */
function auth_id(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Checks if the authenticated user has a specific role or one of multiple roles.
 *
 * @param string|array $roles Single role string or array of allowed roles
 * @return bool
 */
function has_role($roles): bool
{
    if (!is_logged_in()) {
        return false;
    }
    $currentRole = $_SESSION['user_role'] ?? '';
    if (is_array($roles)) {
        return in_array($currentRole, $roles, true);
    }
    return $currentRole === $roles;
}

/**
 * Returns a human-friendly label for user roles.
 *
 * @param string $role
 * @return string
 */
function get_role_label(string $role): string
{
    $labels = [
        'admin'        => 'Administrator',
        'manager'      => 'Loan Manager',
        'loan_officer' => 'Loan Officer',
        'collector'    => 'Debt Collector',
    ];
    return $labels[$role] ?? ucfirst(str_replace('_', ' ', $role));
}

/**
 * Returns the URL for a user's avatar image or a clean local fallback avatar.
 *
 * @param string|null $avatarFilename
 * @param string $name
 * @return string
 */
function get_avatar_url(?string $avatarFilename = null, string $name = 'User'): string
{
    if (!empty($avatarFilename)) {
        $filePath = AVATAR_UPLOAD_DIR . DIRECTORY_SEPARATOR . $avatarFilename;
        if (file_exists($filePath)) {
            return AVATAR_UPLOAD_URL . '/' . rawurlencode($avatarFilename);
        }
    }
    return asset('images/default-avatar.svg');
}

/**
 * Computes uppercase initials from a full name (e.g. "John Doe" -> "JD").
 *
 * @param string $name
 * @return string
 */
function get_initials(string $name): string
{
    $words = preg_split('/\s+/', trim($name));
    $initials = '';
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }
        if (mb_strlen($initials) >= 2) {
            break;
        }
    }
    return $initials ?: 'U';
}

/**
 * Sends critical security and anti-caching HTTP headers.
 *
 * @param bool $noCache Whether to force disable browser caching for protected views
 * @return void
 */
function send_security_headers(bool $noCache = false): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    if ($noCache) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    }
}

/**
 * Generates a unique, sequential customer code (e.g. CUS-000001).
 *
 * @param PDO $db
 * @return string
 */
function generate_customer_code(PDO $db): string
{
    $stmt = $db->query('SELECT MAX(id) as max_id FROM customers');
    $maxId = (int)($stmt->fetchColumn() ?: 0);
    $nextNumber = $maxId + 1;

    do {
        $candidateCode = sprintf('CUS-%06d', $nextNumber);
        $checkStmt = $db->prepare('SELECT id FROM customers WHERE customer_code = :code LIMIT 1');
        $checkStmt->execute([':code' => $candidateCode]);
        $exists = $checkStmt->fetchColumn();
        if ($exists) {
            $nextNumber++;
        }
    } while ($exists);

    return $candidateCode;
}

/**
 * Returns the URL for a customer's photo or local default fallback.
 *
 * @param string|null $photoFilename
 * @param string $name
 * @return string
 */
function get_customer_photo_url(?string $photoFilename = null, string $name = 'Customer'): string
{
    if (!empty($photoFilename)) {
        $filePath = CUSTOMER_UPLOAD_DIR . DIRECTORY_SEPARATOR . $photoFilename;
        if (file_exists($filePath)) {
            return CUSTOMER_UPLOAD_URL . '/' . rawurlencode($photoFilename);
        }
    }
    return asset('images/default-avatar.svg');
}

/**
 * Formats a numeric value into clean currency display.
 *
 * @param float|int|string|null $amount
 * @param string $symbol
 * @return string
 */
function format_currency($amount, string $symbol = '$'): string
{
    $val = (float)($amount ?? 0);
    return $symbol . number_format($val, 2, '.', ',');
}

/**
 * Role Check: Whether current user can create and edit customers.
 * Allowed: Admin, Manager, Loan Officer.
 *
 * @return bool
 */
function can_manage_customers(): bool
{
    return has_role(['admin', 'manager', 'loan_officer']);
}

/**
 * Role Check: Whether current user can activate or deactivate customers.
 * Allowed: Admin, Manager.
 *
 * @return bool
 */
function can_toggle_customer_status(): bool
{
    return has_role(['admin', 'manager']);
}

/**
 * Role Check: Whether current user can delete customers.
 * Allowed: Admin only.
 *
 * @return bool
 */
function can_delete_customers(): bool
{
    return has_role('admin');
}

