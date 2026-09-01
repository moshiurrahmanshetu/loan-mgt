<?php
/**
 * Delete Loan Product Handler
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

// 1. Authorization Guard: Only Admin can delete loan products
if (!has_role('admin')) {
    set_flash('danger', 'Unauthorized: Only System Administrators can delete loan products.');
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
    $stmt = $db->prepare('SELECT id, name, product_code FROM loan_products WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch();

    if (!$product) {
        set_flash('danger', 'Loan product record not found.');
        redirect('modules/loan-products/index.php');
    }

    // 3. Safety Check: Verify if any loan application references this product
    $stmtCheckLoans = $db->prepare('SELECT COUNT(*) FROM loans WHERE loan_product_id = :id');
    $stmtCheckLoans->execute([':id' => $productId]);
    $loanCount = (int)$stmtCheckLoans->fetchColumn();

    if ($loanCount > 0) {
        set_flash('danger', 'Cannot delete loan product <strong>' . e($product['name']) . '</strong> because it is currently linked to ' . $loanCount . ' loan application(s). You may deactivate it instead.');
        redirect('modules/loan-products/view.php?id=' . $productId);
    }

    // 4. Safe Delete
    $deleteStmt = $db->prepare('DELETE FROM loan_products WHERE id = :id');
    $deleteStmt->execute([':id' => $productId]);

    set_flash('success', 'Loan product <strong>' . e($product['name']) . '</strong> (' . e($product['product_code']) . ') has been permanently deleted.');
    redirect('modules/loan-products/index.php');

} catch (Exception $e) {
    error_log('Delete loan product error: ' . $e->getMessage());
    set_flash('danger', 'A database error occurred while attempting to delete the loan product.');
    redirect('modules/loan-products/index.php');
}
