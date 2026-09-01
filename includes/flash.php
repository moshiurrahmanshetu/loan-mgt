<?php
/**
 * Session Flash Messaging System
 * Loan Management System (loan-mgt) - Phase 1
 */

require_once __DIR__ . '/../config/session.php';

/**
 * Sets a flash message to be displayed on the next request.
 *
 * @param string $type success | danger | error | warning | info
 * @param string $message Flash message text
 * @return void
 */
function set_flash(string $type, string $message): void
{
    // Normalize 'error' to Bootstrap's 'danger'
    if ($type === 'error') {
        $type = 'danger';
    }

    if (!isset($_SESSION['_flash_messages']) || !is_array($_SESSION['_flash_messages'])) {
        $_SESSION['_flash_messages'] = [];
    }

    $_SESSION['_flash_messages'][] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Checks if any flash messages are queued.
 *
 * @return bool
 */
function has_flash(): bool
{
    return !empty($_SESSION['_flash_messages']);
}

/**
 * Renders all queued flash messages as Bootstrap 5 alert components and clears them.
 *
 * @return void
 */
function display_flash(): void
{
    if (!has_flash()) {
        return;
    }

    $messages = $_SESSION['_flash_messages'];
    unset($_SESSION['_flash_messages']);

    $iconMap = [
        'success' => 'bi-check-circle-fill',
        'danger'  => 'bi-exclamation-triangle-fill',
        'warning' => 'bi-exclamation-circle-fill',
        'info'    => 'bi-info-circle-fill',
    ];

    foreach ($messages as $item) {
        $type = htmlspecialchars($item['type'] ?? 'info', ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars($item['message'] ?? '', ENT_QUOTES, 'UTF-8');
        $icon = $iconMap[$type] ?? 'bi-info-circle-fill';

        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">';
        echo '  <i class="bi ' . $icon . ' me-2 fs-5 flex-shrink-0"></i>';
        echo '  <div class="flex-grow-1">' . $message . '</div>';
        echo '  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
}
