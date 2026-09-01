<?php
/**
 * Store Loan Application Handler
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

// 1. Authorization Guard
if (!can_create_loans()) {
    set_flash('danger', 'Unauthorized: You do not have permissions to originate loan applications.');
    redirect('modules/loans/index.php');
}

// 2. CSRF Verification
if (!verify_csrf_token()) {
    set_flash('danger', 'Security token expired. Please try submitting the form again.');
    redirect('modules/loans/create.php');
}

// 3. Extract and Sanitize Inputs
$customerId         = (int)($_POST['customer_id'] ?? 0);
$loanProductId      = (int)($_POST['loan_product_id'] ?? 0);
$requestedAmountRaw = trim($_POST['requested_amount'] ?? '');
$termRaw            = trim($_POST['term'] ?? '');
$applicationDate    = trim($_POST['application_date'] ?? date('Y-m-d'));
$purpose            = trim($_POST['purpose'] ?? '');
$notes              = trim($_POST['notes'] ?? '');
$action             = trim($_POST['action'] ?? 'submit');
$status             = ($action === 'draft') ? 'draft' : 'pending';

$_SESSION['_old_loan_input'] = $_POST;

$errors = [];

// Validate customer
if ($customerId <= 0) {
    $errors[] = 'Please select a valid customer.';
}

// Validate product
if ($loanProductId <= 0) {
    $errors[] = 'Please select a valid loan product template.';
}

if (!is_numeric($requestedAmountRaw) || (float)$requestedAmountRaw <= 0) {
    $errors[] = 'Please enter a valid requested loan amount.';
}

if (!ctype_digit((string)$termRaw) || (int)$termRaw < 1) {
    $errors[] = 'Please enter a valid positive loan term duration.';
}

if (empty($applicationDate) || !strtotime($applicationDate)) {
    $errors[] = 'Please enter a valid application date.';
}

$db = get_db_connection();

// 4. Verify Customer Exists and is ACTIVE
$customer = null;
if ($customerId > 0) {
    $stmtCust = $db->prepare('SELECT id, customer_code, full_name, status FROM customers WHERE id = :id LIMIT 1');
    $stmtCust->execute([':id' => $customerId]);
    $customer = $stmtCust->fetch();

    if (!$customer) {
        $errors[] = 'The selected customer does not exist.';
    } elseif ($customer['status'] !== 'active') {
        $errors[] = 'Cannot originate loan: Customer ' . e($customer['full_name']) . ' (' . e($customer['customer_code']) . ') is inactive.';
    }
}

// 5. Fetch Authoritative Loan Product and Validate Limits
$product = null;
if ($loanProductId > 0) {
    $stmtProd = $db->prepare('SELECT * FROM loan_products WHERE id = :id LIMIT 1');
    $stmtProd->execute([':id' => $loanProductId]);
    $product = $stmtProd->fetch();

    if (!$product) {
        $errors[] = 'The selected loan product does not exist.';
    } elseif ($product['status'] !== 'active') {
        $errors[] = 'Cannot originate loan: Loan product "' . e($product['name']) . '" is inactive and unavailable for new applications.';
    } else {
        // Validate Requested Amount against Product Min/Max
        $requestedAmount = (float)$requestedAmountRaw;
        $minAmount = (float)$product['minimum_amount'];
        $maxAmount = (float)$product['maximum_amount'];

        if ($requestedAmount < $minAmount || $requestedAmount > $maxAmount) {
            $errors[] = 'Requested amount ' . format_currency($requestedAmount) . ' is outside product allowed limits (' . format_currency($minAmount) . ' to ' . format_currency($maxAmount) . ').';
        }

        // Validate Term against Product Min/Max
        $term = (int)$termRaw;
        $termMin = (int)$product['term_min'];
        $termMax = (int)$product['term_max'];

        if ($term < $termMin || $term > $termMax) {
            $errors[] = 'Requested term of ' . $term . ' ' . $product['term_unit'] . ' is outside product allowed duration (' . $termMin . ' to ' . $termMax . ' ' . $product['term_unit'] . ').';
        }
    }
}

if (!empty($errors)) {
    set_flash('danger', implode('<br>', $errors));
    redirect('modules/loans/create.php');
}

// 6. Perform Server-Side Snapshot Calculations
$requestedAmount = (float)$requestedAmountRaw;
$term            = (int)$termRaw;
$interestRate    = (float)$product['interest_rate'];
$interestMethod  = $product['interest_method'];
$feeRate         = (float)$product['processing_fee'];

$calc = calculate_loan_preview($requestedAmount, $interestRate, $interestMethod, $feeRate);
$processingFeeAmount     = $calc['fee_amount'];
$estimatedInterestAmount = $calc['interest_amount'];
$estimatedTotalPayable   = $calc['total_payable'];

try {
    // 7. Atomic Database Transaction
    $db->beginTransaction();

    // Generate unique loan number
    $loanNumber = generate_loan_number($db);

    $insertSql = 'INSERT INTO loans (
        loan_number, customer_id, loan_product_id, requested_amount,
        interest_rate, interest_method, term, term_unit,
        repayment_frequency, processing_fee_rate, processing_fee_amount,
        estimated_interest_amount, estimated_total_payable, purpose,
        application_date, status, notes, created_by, created_at
    ) VALUES (
        :loan_number, :customer_id, :loan_product_id, :requested_amount,
        :interest_rate, :interest_method, :term, :term_unit,
        :repayment_frequency, :fee_rate, :fee_amount,
        :interest_amount, :total_payable, :purpose,
        :application_date, :status, :notes, :created_by, NOW()
    )';

    $stmt = $db->prepare($insertSql);
    $stmt->execute([
        ':loan_number'         => $loanNumber,
        ':customer_id'         => $customerId,
        ':loan_product_id'     => $loanProductId,
        ':requested_amount'    => $requestedAmount,
        ':interest_rate'       => $interestRate,
        ':interest_method'     => $interestMethod,
        ':term'                => $term,
        ':term_unit'           => $product['term_unit'],
        ':repayment_frequency' => $product['repayment_frequency'],
        ':fee_rate'            => $feeRate,
        ':fee_amount'          => $processingFeeAmount,
        ':interest_amount'     => $estimatedInterestAmount,
        ':total_payable'       => $estimatedTotalPayable,
        ':purpose'             => $purpose ?: null,
        ':application_date'    => $applicationDate,
        ':status'              => $status,
        ':notes'               => $notes ?: null,
        ':created_by'          => auth_id()
    ]);

    $newLoanId = (int)$db->lastInsertId();
    $db->commit();

    unset($_SESSION['_old_loan_input']);

    $statusMsg = ($status === 'draft') ? 'saved as Draft' : 'submitted for approval (Pending Review)';
    set_flash('success', 'Loan application <strong>' . e($loanNumber) . '</strong> for ' . e($customer['full_name']) . ' has been ' . $statusMsg . '.');
    redirect('modules/loans/view.php?id=' . $newLoanId);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Loan store error: ' . $e->getMessage());
    set_flash('danger', 'A database error occurred while creating the loan application.');
    redirect('modules/loans/create.php');
}
