<?php
/**
 * Update Loan Application Handler
 * Loan Management System (loan-mgt) - Phase 3
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/flash.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/loans/index.php');
}

$loanId = (int)($_POST['id'] ?? 0);
if ($loanId <= 0) {
    set_flash('danger', 'Invalid loan application specified.');
    redirect('modules/loans/index.php');
}

// 1. CSRF Verification
if (!verify_csrf_token()) {
    set_flash('danger', 'Security token expired. Please try submitting the form again.');
    redirect('modules/loans/edit.php?id=' . $loanId);
}

$db = get_db_connection();

// Verify loan exists
$stmt = $db->prepare('SELECT * FROM loans WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $loanId]);
$loan = $stmt->fetch();

if (!$loan) {
    set_flash('danger', 'Loan application record not found.');
    redirect('modules/loans/index.php');
}

// 2. Authorization Check
if (!can_edit_loan($loan, auth_id())) {
    set_flash('danger', 'Unauthorized: You do not have permissions to modify this loan application in its current state (' . ucfirst($loan['status']) . ').');
    redirect('modules/loans/view.php?id=' . $loanId);
}

// 3. Extract and Sanitize Inputs
$loanProductId      = (int)($_POST['loan_product_id'] ?? $loan['loan_product_id']);
$requestedAmountRaw = trim($_POST['requested_amount'] ?? (string)$loan['requested_amount']);
$termRaw            = trim($_POST['term'] ?? (string)$loan['term']);
$applicationDate    = trim($_POST['application_date'] ?? $loan['application_date']);
$purpose            = trim($_POST['purpose'] ?? ($loan['purpose'] ?? ''));
$notes              = trim($_POST['notes'] ?? ($loan['notes'] ?? ''));
$action             = trim($_POST['action'] ?? 'save');

$_SESSION['_old_loan_edit_input'] = $_POST;

$errors = [];

if (!is_numeric($requestedAmountRaw) || (float)$requestedAmountRaw <= 0) {
    $errors[] = 'Please enter a valid requested loan amount.';
}

if (!ctype_digit((string)$termRaw) || (int)$termRaw < 1) {
    $errors[] = 'Please enter a valid positive loan term duration.';
}

if (empty($applicationDate) || !strtotime($applicationDate)) {
    $errors[] = 'Please enter a valid application date.';
}

// 4. Fetch and Validate Product Rules
$stmtProd = $db->prepare('SELECT * FROM loan_products WHERE id = :id LIMIT 1');
$stmtProd->execute([':id' => $loanProductId]);
$product = $stmtProd->fetch();

if (!$product) {
    $errors[] = 'The selected loan product does not exist.';
} else {
    $requestedAmount = (float)$requestedAmountRaw;
    $minAmount       = (float)$product['minimum_amount'];
    $maxAmount       = (float)$product['maximum_amount'];

    if ($requestedAmount < $minAmount || $requestedAmount > $maxAmount) {
        $errors[] = 'Requested amount ' . format_currency($requestedAmount) . ' is outside product allowed limits (' . format_currency($minAmount) . ' to ' . format_currency($maxAmount) . ').';
    }

    $term    = (int)$termRaw;
    $termMin = (int)$product['term_min'];
    $termMax = (int)$product['term_max'];

    if ($term < $termMin || $term > $termMax) {
        $errors[] = 'Requested term of ' . $term . ' ' . $product['term_unit'] . ' is outside product allowed duration (' . $termMin . ' to ' . $termMax . ' ' . $product['term_unit'] . ').';
    }
}

if (!empty($errors)) {
    set_flash('danger', implode('<br>', $errors));
    redirect('modules/loans/edit.php?id=' . $loanId);
}

// 5. Compute Updated Snapshot Figures
$requestedAmount = (float)$requestedAmountRaw;
$term            = (int)$termRaw;
$interestRate    = (float)$product['interest_rate'];
$interestMethod  = $product['interest_method'];
$feeRate         = (float)$product['processing_fee'];

$calc = calculate_loan_preview($requestedAmount, $interestRate, $interestMethod, $feeRate);
$processingFeeAmount     = $calc['fee_amount'];
$estimatedInterestAmount = $calc['interest_amount'];
$estimatedTotalPayable   = $calc['total_payable'];

// Determine status transition
$newStatus = $loan['status'];
if ($loan['status'] === 'draft' && $action === 'submit') {
    $newStatus = 'pending';
}

try {
    $updateSql = 'UPDATE loans SET 
        loan_product_id           = :prod_id,
        requested_amount          = :req_amt,
        interest_rate             = :rate,
        interest_method           = :method,
        term                      = :term,
        term_unit                 = :unit,
        repayment_frequency       = :freq,
        processing_fee_rate       = :fee_rate,
        processing_fee_amount     = :fee_amt,
        estimated_interest_amount = :interest_amt,
        estimated_total_payable   = :total_payable,
        purpose                   = :purpose,
        application_date          = :app_date,
        status                    = :status,
        notes                     = :notes,
        updated_at                = NOW()
    WHERE id = :id';

    $stmt = $db->prepare($updateSql);
    $stmt->execute([
        ':prod_id'      => $loanProductId,
        ':req_amt'      => $requestedAmount,
        ':rate'         => $interestRate,
        ':method'       => $interestMethod,
        ':term'         => $term,
        ':unit'         => $product['term_unit'],
        ':freq'         => $product['repayment_frequency'],
        ':fee_rate'     => $feeRate,
        ':fee_amt'      => $processingFeeAmount,
        ':interest_amt' => $estimatedInterestAmount,
        ':total_payable'=> $estimatedTotalPayable,
        ':purpose'      => $purpose ?: null,
        ':app_date'     => $applicationDate,
        ':status'       => $newStatus,
        ':notes'        => $notes ?: null,
        ':id'           => $loanId
    ]);

    unset($_SESSION['_old_loan_edit_input']);

    $statusMsg = ($newStatus === 'pending' && $loan['status'] === 'draft') ? ' and submitted for approval' : '';
    set_flash('success', 'Loan application <strong>' . e($loan['loan_number']) . '</strong> updated successfully' . $statusMsg . '.');
    redirect('modules/loans/view.php?id=' . $loanId);

} catch (Exception $e) {
    error_log('Loan update error: ' . $e->getMessage());
    set_flash('danger', 'A database error occurred while updating the loan application.');
    redirect('modules/loans/edit.php?id=' . $loanId);
}
