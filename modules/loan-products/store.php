<?php
/**
 * Store Loan Product Handler
 * Loan Management System (loan-mgt) - Phase 3
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/flash.php';

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/loan-products/index.php');
}

// 1. Authorization Guard
if (!can_manage_loan_products()) {
    set_flash('danger', 'Unauthorized: You do not have permissions to create loan products.');
    redirect('modules/loan-products/index.php');
}

// 2. CSRF Verification
if (!verify_csrf_token()) {
    set_flash('danger', 'Security token expired. Please try submitting the form again.');
    redirect('modules/loan-products/create.php');
}

// 3. Extract and Sanitize Inputs
$productCode         = strtoupper(trim($_POST['product_code'] ?? ''));
$name                = trim($_POST['name'] ?? '');
$description         = trim($_POST['description'] ?? '');
$minAmountRaw        = trim($_POST['minimum_amount'] ?? '');
$maxAmountRaw        = trim($_POST['maximum_amount'] ?? '');
$interestRateRaw     = trim($_POST['interest_rate'] ?? '');
$interestMethod      = trim($_POST['interest_method'] ?? 'flat');
$termMinRaw          = trim($_POST['term_min'] ?? '1');
$termMaxRaw          = trim($_POST['term_max'] ?? '12');
$termUnit            = trim($_POST['term_unit'] ?? 'months');
$repaymentFrequency  = trim($_POST['repayment_frequency'] ?? 'monthly');
$processingFeeRaw    = trim($_POST['processing_fee'] ?? '0.00');
$status              = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : 'active';

$_SESSION['_old_product_input'] = $_POST;

// 4. Server-Side Validation
$errors = [];

if (empty($productCode) || mb_strlen($productCode) > 20) {
    $errors[] = 'Product code is required and must not exceed 20 characters.';
}

if (empty($name) || mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    $errors[] = 'Product name is required and must be between 2 and 100 characters.';
}

if (!is_numeric($minAmountRaw) || (float)$minAmountRaw < 0) {
    $errors[] = 'Minimum loan amount must be a valid non-negative number.';
}

if (!is_numeric($maxAmountRaw) || (float)$maxAmountRaw < 0) {
    $errors[] = 'Maximum loan amount must be a valid non-negative number.';
} elseif (is_numeric($minAmountRaw) && (float)$maxAmountRaw < (float)$minAmountRaw) {
    $errors[] = 'Maximum loan amount must be greater than or equal to minimum amount.';
}

if (!is_numeric($interestRateRaw) || (float)$interestRateRaw < 0) {
    $errors[] = 'Interest rate must be a valid non-negative percentage.';
}

if (!in_array($interestMethod, ['flat', 'reducing_balance'], true)) {
    $errors[] = 'Invalid interest calculation method selected.';
}

if (!ctype_digit((string)$termMinRaw) || (int)$termMinRaw < 1) {
    $errors[] = 'Minimum term must be an integer of at least 1.';
}

if (!ctype_digit((string)$termMaxRaw) || (int)$termMaxRaw < 1) {
    $errors[] = 'Maximum term must be an integer of at least 1.';
} elseif (ctype_digit((string)$termMinRaw) && (int)$termMaxRaw < (int)$termMinRaw) {
    $errors[] = 'Maximum term must be greater than or equal to minimum term.';
}

if (!in_array($termUnit, ['days', 'weeks', 'months'], true)) {
    $errors[] = 'Invalid term unit specified.';
}

if (!in_array($repaymentFrequency, ['daily', 'weekly', 'biweekly', 'monthly'], true)) {
    $errors[] = 'Invalid repayment frequency specified.';
}

if (!is_numeric($processingFeeRaw) || (float)$processingFeeRaw < 0) {
    $errors[] = 'Processing fee must be a valid non-negative percentage.';
}

$db = get_db_connection();

// Verify unique product code
if (!empty($productCode)) {
    $stmtCheck = $db->prepare('SELECT id FROM loan_products WHERE product_code = :code LIMIT 1');
    $stmtCheck->execute([':code' => $productCode]);
    if ($stmtCheck->fetch()) {
        $errors[] = 'Product code "' . e($productCode) . '" is already in use by another template.';
    }
}

if (!empty($errors)) {
    set_flash('danger', implode('<br>', $errors));
    redirect('modules/loan-products/create.php');
}

try {
    // 5. Insert Record into Database
    $insertSql = 'INSERT INTO loan_products (
        product_code, name, description, minimum_amount, maximum_amount,
        interest_rate, interest_method, term_min, term_max, term_unit,
        repayment_frequency, processing_fee, status, created_by, created_at
    ) VALUES (
        :code, :name, :description, :min_amt, :max_amt,
        :rate, :method, :term_min, :term_max, :term_unit,
        :freq, :fee, :status, :created_by, NOW()
    )';

    $stmt = $db->prepare($insertSql);
    $stmt->execute([
        ':code'        => $productCode,
        ':name'        => $name,
        ':description' => $description ?: null,
        ':min_amt'     => (float)$minAmountRaw,
        ':max_amt'     => (float)$maxAmountRaw,
        ':rate'        => (float)$interestRateRaw,
        ':method'      => $interestMethod,
        ':term_min'    => (int)$termMinRaw,
        ':term_max'    => (int)$termMaxRaw,
        ':term_unit'   => $termUnit,
        ':freq'        => $repaymentFrequency,
        ':fee'         => (float)$processingFeeRaw,
        ':status'      => $status,
        ':created_by'  => auth_id()
    ]);

    $productId = (int)$db->lastInsertId();
    unset($_SESSION['_old_product_input']);

    set_flash('success', 'Loan product <strong>' . e($name) . '</strong> (' . e($productCode) . ') created successfully.');
    redirect('modules/loan-products/view.php?id=' . $productId);

} catch (Exception $e) {
    error_log('Loan product store error: ' . $e->getMessage());
    set_flash('danger', 'A database error occurred while creating the loan product.');
    redirect('modules/loan-products/create.php');
}
