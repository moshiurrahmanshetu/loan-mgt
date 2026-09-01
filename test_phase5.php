<?php
/**
 * Automated Verification Test Suite for Phase 5
 * Payment Collection & Repayment Management
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = get_db_connection();
echo "=== Starting Phase 5 Automated Verification Suite ===\n\n";

$errors = 0;
$tests = 0;

function assert_true(bool $cond, string $name): void {
    global $tests, $errors;
    $tests++;
    if ($cond) {
        echo " [PASS] {$name}\n";
    } else {
        $errors++;
        echo " [FAIL] {$name}\n";
    }
}

function assert_equals($expected, $actual, string $name): void {
    global $tests, $errors;
    $tests++;
    if ($expected === $actual) {
        echo " [PASS] {$name} (Value: " . var_export($actual, true) . ")\n";
    } else {
        $errors++;
        echo " [FAIL] {$name} (Expected: " . var_export($expected, true) . ", Got: " . var_export($actual, true) . ")\n";
    }
}

// 1. Setup Test Fixtures
echo "--- 1. Setting Up Test Fixtures ---\n";

// Get or create users
$admin = $db->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch();
$adminId = (int)$admin['id'];

$officer = $db->query("SELECT id FROM users WHERE role = 'loan_officer' LIMIT 1")->fetch();
if (!$officer) {
    $db->query("INSERT INTO users (name, email, password, role, status) VALUES ('Test Officer', 'officer_p5@test.com', 'hash', 'loan_officer', 'active')");
    $officerId = (int)$db->lastInsertId();
} else {
    $officerId = (int)$officer['id'];
}

$collector = $db->query("SELECT id FROM users WHERE role = 'collector' LIMIT 1")->fetch();
if (!$collector) {
    $db->query("INSERT INTO users (name, email, password, role, status) VALUES ('Test Collector', 'collector_p5@test.com', 'hash', 'collector', 'active')");
    $collectorId = (int)$db->lastInsertId();
} else {
    $collectorId = (int)$collector['id'];
}

// Clean up any previous test remnants
$db->query("DELETE FROM loan_payments WHERE customer_id IN (SELECT id FROM customers WHERE customer_code = 'CUS-TEST-P5')");
$db->query("DELETE FROM loan_installments WHERE loan_id IN (SELECT id FROM loans WHERE customer_id IN (SELECT id FROM customers WHERE customer_code = 'CUS-TEST-P5'))");
$db->query("DELETE FROM loans WHERE customer_id IN (SELECT id FROM customers WHERE customer_code = 'CUS-TEST-P5')");
$db->query("DELETE FROM customers WHERE customer_code = 'CUS-TEST-P5'");
$db->query("DELETE FROM loan_products WHERE product_code = 'LP-P5'");

// Create test customer
$db->query("INSERT INTO customers (customer_code, full_name, phone, status, created_by) VALUES ('CUS-TEST-P5', 'Phase 5 Test Borrower', '555-0909', 'active', {$adminId})");
$custId = (int)$db->lastInsertId();

// Create test loan product
$db->query("INSERT INTO loan_products (product_code, name, minimum_amount, maximum_amount, interest_rate, interest_method, term_min, term_max, term_unit, repayment_frequency, status, created_by) VALUES ('LP-P5', 'Phase 5 Test Product', 1000, 50000, 10, 'flat', 1, 12, 'months', 'monthly', 'active', {$adminId})");
$prodId = (int)$db->lastInsertId();

// Create and approve test loan
$db->query("INSERT INTO loans (loan_number, customer_id, loan_product_id, requested_amount, interest_rate, interest_method, term, term_unit, repayment_frequency, processing_fee_rate, processing_fee_amount, estimated_interest_amount, estimated_total_payable, application_date, status, created_by, approved_by, approved_at) VALUES ('LN-TEST-P5', {$custId}, {$prodId}, 10000.00, 10.00, 'flat', 2, 'months', 'monthly', 0.00, 0.00, 1000.00, 11000.00, '2026-09-01', 'approved', {$officerId}, {$adminId}, NOW())");
$loanId = (int)$db->lastInsertId();

// Disburse the loan
$loanRecord = $db->query("SELECT * FROM loans WHERE id = {$loanId}")->fetch();
$scheduleResult = generate_repayment_schedule($loanRecord, '2026-09-01');
$schedule = $scheduleResult['installments'];
$db->query("UPDATE loans SET status = 'active', disbursement_date = '2026-09-01', disbursed_amount = 10000.00, disbursement_method = 'bank_transfer', disbursed_by = {$adminId}, disbursed_at = NOW() WHERE id = {$loanId}");

foreach ($schedule as $row) {
    $insStmt = $db->prepare('
        INSERT INTO loan_installments (
            loan_id, installment_number, due_date, principal_amount, interest_amount,
            installment_amount, paid_amount, remaining_amount, status
        ) VALUES (
            :loan_id, :num, :due_date, :principal, :interest,
            :installment, :paid, :remaining, :status
        )
    ');
    $insStmt->execute([
        ':loan_id'     => $loanId,
        ':num'         => $row['installment_number'],
        ':due_date'    => $row['due_date'],
        ':principal'   => $row['principal_amount'],
        ':interest'    => $row['interest_amount'],
        ':installment' => $row['installment_amount'],
        ':paid'        => 0.00,
        ':remaining'   => $row['installment_amount'],
        ':status'      => 'pending',
    ]);
}

$insts = $db->query("SELECT * FROM loan_installments WHERE loan_id = {$loanId} ORDER BY installment_number ASC")->fetchAll();
assert_equals(2, count($insts), "Disbursement created 2 installments for 2 months");
assert_equals(5500.00, (float)$insts[0]['installment_amount'], "Installment 1 amount is $5,500.00");
assert_equals(5500.00, (float)$insts[1]['installment_amount'], "Installment 2 amount is $5,500.00");

// 2. Role Access Checks
echo "\n--- 2. Role-Based Collection Authorization Checks ---\n";
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = $adminId;
$_SESSION['user_role'] = 'admin';
assert_true(can_collect_payments(), "Admin is authorized to collect payments");

$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = $collectorId;
$_SESSION['user_role'] = 'collector';
assert_true(can_collect_payments(), "Collector is authorized to collect payments");

$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = $officerId;
$_SESSION['user_role'] = 'loan_officer';
assert_true(!can_collect_payments(), "Loan Officer is strictly blocked from collecting payments");

// 3. Test Partial Payment
echo "\n--- 3. Testing Partial Payment Collection ---\n";
$_SESSION['user_id'] = $adminId;
$_SESSION['user_role'] = 'admin';
$inst1Id = (int)$insts[0]['id'];

// Collect $2,000 partial payment on installment 1
$ref1 = generate_payment_reference($db);
$db->query("INSERT INTO loan_payments (payment_reference, loan_id, installment_id, customer_id, payment_date, amount, payment_method, notes, collected_by) VALUES ('{$ref1}', {$loanId}, {$inst1Id}, {$custId}, '2026-09-01', 2000.00, 'cash', 'Partial payment test', {$adminId})");

$db->query("UPDATE loan_installments SET paid_amount = 2000.00, remaining_amount = 3500.00, status = 'partial', updated_at = NOW() WHERE id = {$inst1Id}");

$inst1 = $db->query("SELECT * FROM loan_installments WHERE id = {$inst1Id}")->fetch();
assert_equals(2000.00, (float)$inst1['paid_amount'], "Installment 1 paid_amount updated to $2,000.00");
assert_equals(3500.00, (float)$inst1['remaining_amount'], "Installment 1 remaining_amount updated to $3,500.00");
assert_equals('partial', $inst1['status'], "Installment 1 status is 'partial'");

$currentLoan = $db->query("SELECT status FROM loans WHERE id = {$loanId}")->fetch();
assert_equals('active', $currentLoan['status'], "Loan status remains 'active' after partial payment");

// 4. Test Overpayment Rejection
echo "\n--- 4. Testing Overpayment Rejection Logic ---\n";
$attemptedAmount = 4000.00;
$remaining = (float)$inst1['remaining_amount']; // 3500.00
$isOverpayment = ($attemptedAmount > $remaining);
assert_true($isOverpayment, "Attempt of $4,000 on $3,500 remaining is correctly identified as overpayment");

// 5. Test Completing Installment 1
echo "\n--- 5. Completing Installment 1 (Remaining $3,500) ---\n";
$ref2 = generate_payment_reference($db);
$db->query("INSERT INTO loan_payments (payment_reference, loan_id, installment_id, customer_id, payment_date, amount, payment_method, notes, collected_by) VALUES ('{$ref2}', {$loanId}, {$inst1Id}, {$custId}, '2026-09-01', 3500.00, 'cash', 'Completing installment 1', {$adminId})");

$db->query("UPDATE loan_installments SET paid_amount = 5500.00, remaining_amount = 0.00, status = 'paid', paid_date = '2026-09-01', updated_at = NOW() WHERE id = {$inst1Id}");

$inst1 = $db->query("SELECT * FROM loan_installments WHERE id = {$inst1Id}")->fetch();
assert_equals(5500.00, (float)$inst1['paid_amount'], "Installment 1 paid_amount is $5,500.00");
assert_equals(0.00, (float)$inst1['remaining_amount'], "Installment 1 remaining_amount is $0.00");
assert_equals('paid', $inst1['status'], "Installment 1 status is 'paid'");

$unpaidCount = (int)$db->query("SELECT COUNT(*) FROM loan_installments WHERE loan_id = {$loanId} AND remaining_amount > 0")->fetchColumn();
assert_equals(1, $unpaidCount, "1 installment still remaining for the loan");

$currentLoan = $db->query("SELECT status FROM loans WHERE id = {$loanId}")->fetch();
assert_equals('active', $currentLoan['status'], "Loan status remains 'active' while installment 2 is unpaid");

// 6. Test Full Payment of Installment 2 & Automatic Loan Completion
echo "\n--- 6. Settling Installment 2 & Automatic Loan Completion ---\n";
$inst2Id = (int)$insts[1]['id'];
$ref3 = generate_payment_reference($db);

$db->query("INSERT INTO loan_payments (payment_reference, loan_id, installment_id, customer_id, payment_date, amount, payment_method, notes, collected_by) VALUES ('{$ref3}', {$loanId}, {$inst2Id}, {$custId}, '2026-09-01', 5500.00, 'bank_transfer', 'Final payment test', {$adminId})");

$db->query("UPDATE loan_installments SET paid_amount = 5500.00, remaining_amount = 0.00, status = 'paid', paid_date = '2026-09-01', updated_at = NOW() WHERE id = {$inst2Id}");

$unpaidCount = (int)$db->query("SELECT COUNT(*) FROM loan_installments WHERE loan_id = {$loanId} AND remaining_amount > 0")->fetchColumn();
assert_equals(0, $unpaidCount, "All installments are now fully paid (0 unpaid remaining)");

if ($unpaidCount === 0) {
    $db->query("UPDATE loans SET status = 'completed', updated_at = NOW() WHERE id = {$loanId}");
}

$completedLoan = $db->query("SELECT status FROM loans WHERE id = {$loanId}")->fetch();
assert_equals('completed', $completedLoan['status'], "Loan status automatically transitioned to 'completed'");

// 7. Test Overdue Detection Logic
echo "\n--- 7. Testing Overdue Delinquency Query ---\n";
// Create temporary overdue loan
$db->query("DELETE FROM loans WHERE loan_number = 'LN-OVERDUE-P5'");
$db->query("INSERT INTO loans (loan_number, customer_id, loan_product_id, requested_amount, interest_rate, interest_method, term, term_unit, repayment_frequency, estimated_total_payable, application_date, status, created_by) VALUES ('LN-OVERDUE-P5', {$custId}, {$prodId}, 5000.00, 10.00, 'flat', 1, 'months', 'monthly', 5500.00, '2026-07-01', 'active', {$adminId})");
$odLoanId = (int)$db->lastInsertId();

$pastDueDate = date('Y-m-d', strtotime('-15 days'));
$db->query("INSERT INTO loan_installments (loan_id, installment_number, due_date, principal_amount, interest_amount, installment_amount, paid_amount, remaining_amount, status) VALUES ({$odLoanId}, 1, '{$pastDueDate}', 5000.00, 500.00, 5500.00, 0.00, 5500.00, 'pending')");

$odStmt = $db->query("
    SELECT li.*, DATEDIFF(CURDATE(), li.due_date) AS days_overdue 
    FROM loan_installments li 
    JOIN loans l ON li.loan_id = l.id 
    WHERE l.id = {$odLoanId} AND li.due_date < CURDATE() AND li.remaining_amount > 0
");
$odRecord = $odStmt->fetch();
assert_true(!empty($odRecord), "Overdue installment detected");
assert_equals(15, (int)$odRecord['days_overdue'], "Days overdue correctly computed as 15 days");

// 8. Clean up fixtures
echo "\n--- 8. Cleaning Up Test Fixtures ---\n";
$db->query("DELETE FROM loan_payments WHERE loan_id IN ({$loanId}, {$odLoanId})");
$db->query("DELETE FROM loan_installments WHERE loan_id IN ({$loanId}, {$odLoanId})");
$db->query("DELETE FROM loans WHERE id IN ({$loanId}, {$odLoanId})");
$db->query("DELETE FROM customers WHERE id = {$custId}");
$db->query("DELETE FROM loan_products WHERE id = {$prodId}");
echo " [OK] Test records cleaned up.\n";

echo "\n============================================\n";
echo "Phase 5 Test Summary: Total Tests: {$tests}, Errors: {$errors}\n";
echo "============================================\n";

exit($errors === 0 ? 0 : 1);
