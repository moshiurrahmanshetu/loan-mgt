<?php
/**
 * Toggle Loan Product Status Handler
 * Loan Management System (loan-mgt) - Phase 3
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/flash.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/loan-products/index.php');
}

// 1. Authorization Check
if (!can_manage_loan_products()) {
    set_flash('danger', 'Unauthorized: You do not have permission to toggle loan product status.');
    redirect('modules/loan-products/index.php');
}

// 2. CSRF Verification
if (!verify_csrf_token()) {
    set_flash('danger', 'Security token expired. Please try again.');
    redirect('modules/loan-products/index.php');
}

$productId = (int)($_POST['id'] ?? 0);
if ($productId <= 0) {
    set_flash('danger', 'Invalid loan product specified.');
    redirect('modules/loan-products/index.php');
}

$db = get_db_connection();

try {
    $stmt = $db->prepare('SELECT id, name, product_code, status FROM loan_products WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch();

    if (!$product) {
        set_flash('danger', 'Loan product record not found.');
        redirect('modules/loan-products/index.php');
    }

    $newStatus = ($product['status'] === 'active') ? 'inactive' : 'active';

    $updateStmt = $db->prepare('UPDATE loan_products SET status = :status, updated_at = NOW() WHERE id = :id');
    $updateStmt->execute([
        ':status' => $newStatus,
        ':id'     => $productId
    ]);

    $statusLabel = ($newStatus === 'active') ? 'activated' : 'deactivated';
    set_flash('success', 'Loan product <strong>' . e($product['name']) . '</strong> (' . e($product['product_code']) . ') has been ' . $statusLabel . '.');

} catch (Exception $e) {
    error_log('Toggle loan product status error: ' . $e->getMessage());
    set_flash('danger', 'A database error occurred while updating the product status.');
}

$redirectTo = trim($_POST['redirect_to'] ?? '');
if (!empty($redirectTo) && strpos($redirectTo, url('')) === 0) {
    header('Location: ' . $redirectTo);
    exit;
}

redirect('modules/loan-products/view.php?id=' . $productId);
