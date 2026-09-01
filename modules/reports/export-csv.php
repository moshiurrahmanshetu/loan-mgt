<?php
/**
 * Universal CSV Export Handler
 * Loan Management System (loan-mgt) - Phase 6
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// 1. Validate Report Type & Access Control
$allowedReports = ['loan', 'disbursement', 'repayment', 'overdue', 'customer'];
$reportType = trim($_GET['report'] ?? '');

if (!in_array($reportType, $allowedReports, true)) {
    set_flash('danger', 'Invalid report export requested.');
    redirect('modules/reports/index.php');
}

if (!can_access_report($reportType)) {
    set_flash('danger', 'Unauthorized: You do not have permission to export this report.');
    redirect('modules/reports/index.php');
}

$db = get_db_connection();
$today = date('Y-m-d');

// 2. Extract Query Filters
$fromDate    = trim($_GET['from_date'] ?? '');
$toDate      = trim($_GET['to_date'] ?? '');
$status      = trim($_GET['status'] ?? 'all');
$productId   = (int)($_GET['product_id'] ?? 0);
$method      = trim($_GET['method'] ?? 'all');
$disbBy      = (int)($_GET['disbursed_by'] ?? 0);
$collectorId = (int)($_GET['collector_id'] ?? 0);
$agingBand   = trim($_GET['aging'] ?? 'all');
$activity    = trim($_GET['activity'] ?? 'all');
$search      = trim($_GET['search'] ?? '');

// 3. Prepare CSV Output Stream
$filename = 'loan_mgt_' . $reportType . '_report_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Output UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

function write_sanitized_row($stream, array $row): void {
    $sanitized = array_map('sanitize_csv_cell', $row);
    fputcsv($stream, $sanitized);
}

// 4. Generate Dataset Based on Report Type
if ($reportType === 'loan') {
    // Header
    write_sanitized_row($output, [
        'Loan Number',
        'Customer Code',
        'Customer Name',
        'Phone',
        'Product Name',
        'Requested Principal ($)',
        'Interest Rate (%)',
        'Interest Method',
        'Estimated Interest ($)',
        'Total Payable ($)',
        'Term',
        'Frequency',
        'Application Date',
        'Status'
    ]);

    $whereClauses = [];
    $params = [];
    if (!empty($fromDate) && strtotime($fromDate)) {
        $whereClauses[] = "l.application_date >= :from_date";
        $params[':from_date'] = $fromDate;
    }
    if (!empty($toDate) && strtotime($toDate)) {
        $whereClauses[] = "l.application_date <= :to_date";
        $params[':to_date'] = $toDate;
    }
    if (in_array($status, ['draft', 'pending', 'approved', 'active', 'completed', 'rejected', 'cancelled'], true)) {
        $whereClauses[] = "l.status = :status";
        $params[':status'] = $status;
    }
    if ($productId > 0) {
        $whereClauses[] = "l.loan_product_id = :product_id";
        $params[':product_id'] = $productId;
    }
    if ($search !== '') {
        $whereClauses[] = "(l.loan_number LIKE :s_ln OR c.full_name LIKE :s_name OR c.phone LIKE :s_phone OR c.customer_code LIKE :s_code)";
        $wildcard = '%' . $search . '%';
        $params[':s_ln'] = $wildcard; $params[':s_name'] = $wildcard; $params[':s_phone'] = $wildcard; $params[':s_code'] = $wildcard;
    }
    $whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

    $stmt = $db->prepare("
        SELECT l.*, c.customer_code, c.full_name AS customer_name, c.phone AS customer_phone, lp.name AS product_name
        FROM loans l
        JOIN customers c ON l.customer_id = c.id
        LEFT JOIN loan_products lp ON l.loan_product_id = lp.id
        {$whereSql}
        ORDER BY l.application_date DESC, l.id DESC
    ");
    $stmt->execute($params);

    while ($r = $stmt->fetch()) {
        write_sanitized_row($output, [
            $r['loan_number'],
            $r['customer_code'],
            $r['customer_name'],
            $r['customer_phone'],
            $r['product_name'] ?? 'Product',
            number_format((float)$r['requested_amount'], 2, '.', ''),
            number_format((float)$r['interest_rate'], 2, '.', ''),
            get_interest_method_label($r['interest_method']),
            number_format((float)$r['estimated_interest_amount'], 2, '.', ''),
            number_format((float)$r['estimated_total_payable'], 2, '.', ''),
            (int)$r['term'] . ' ' . ucfirst($r['term_unit']),
            ucfirst($r['repayment_frequency']),
            $r['application_date'],
            ucfirst($r['status']),
        ]);
    }

} elseif ($reportType === 'disbursement') {
    // Header
    write_sanitized_row($output, [
        'Disbursement Date',
        'Loan Number',
        'Customer Code',
        'Customer Name',
        'Phone',
        'Product Name',
        'Disbursed Amount ($)',
        'Disbursement Channel',
        'Authorized By'
    ]);

    $whereClauses = ["l.status IN ('active', 'completed')", "l.disbursement_date IS NOT NULL"];
    $params = [];
    if (!empty($fromDate) && strtotime($fromDate)) {
        $whereClauses[] = "l.disbursement_date >= :from_date";
        $params[':from_date'] = $fromDate;
    }
    if (!empty($toDate) && strtotime($toDate)) {
        $whereClauses[] = "l.disbursement_date <= :to_date";
        $params[':to_date'] = $toDate;
    }
    if (in_array($method, ['cash', 'bank_transfer', 'mobile_banking'], true)) {
        $whereClauses[] = "l.disbursement_method = :method";
        $params[':method'] = $method;
    }
    if ($disbBy > 0) {
        $whereClauses[] = "l.disbursed_by = :disb_by";
        $params[':disb_by'] = $disbBy;
    }
    if ($search !== '') {
        $whereClauses[] = "(l.loan_number LIKE :s_ln OR c.full_name LIKE :s_name OR c.phone LIKE :s_phone OR c.customer_code LIKE :s_code)";
        $wildcard = '%' . $search . '%';
        $params[':s_ln'] = $wildcard; $params[':s_name'] = $wildcard; $params[':s_phone'] = $wildcard; $params[':s_code'] = $wildcard;
    }
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);

    $stmt = $db->prepare("
        SELECT l.*, c.customer_code, c.full_name AS customer_name, c.phone AS customer_phone,
               lp.name AS product_name, u.name AS disburser_name
        FROM loans l
        JOIN customers c ON l.customer_id = c.id
        LEFT JOIN loan_products lp ON l.loan_product_id = lp.id
        LEFT JOIN users u ON l.disbursed_by = u.id
        {$whereSql}
        ORDER BY l.disbursement_date DESC, l.id DESC
    ");
    $stmt->execute($params);

    while ($r = $stmt->fetch()) {
        write_sanitized_row($output, [
            $r['disbursement_date'],
            $r['loan_number'],
            $r['customer_code'],
            $r['customer_name'],
            $r['customer_phone'],
            $r['product_name'] ?? 'Product',
            number_format((float)$r['disbursed_amount'], 2, '.', ''),
            get_disbursement_method_label($r['disbursement_method'] ?? 'cash'),
            $r['disburser_name'] ?? 'System Officer',
        ]);
    }

} elseif ($reportType === 'repayment') {
    // Header
    write_sanitized_row($output, [
        'Receipt Reference',
        'Payment Date',
        'Loan Number',
        'Customer Code',
        'Customer Name',
        'Installment Number',
        'Amount Paid ($)',
        'Payment Method',
        'Collected By'
    ]);

    $whereClauses = [];
    $params = [];
    if (!empty($fromDate) && strtotime($fromDate)) {
        $whereClauses[] = "p.payment_date >= :from_date";
        $params[':from_date'] = $fromDate;
    }
    if (!empty($toDate) && strtotime($toDate)) {
        $whereClauses[] = "p.payment_date <= :to_date";
        $params[':to_date'] = $toDate;
    }
    if (in_array($method, ['cash', 'bank_transfer', 'mobile_banking'], true)) {
        $whereClauses[] = "p.payment_method = :method";
        $params[':method'] = $method;
    }
    if ($collectorId > 0) {
        $whereClauses[] = "p.collected_by = :collector_id";
        $params[':collector_id'] = $collectorId;
    }
    if ($search !== '') {
        $whereClauses[] = "(p.payment_reference LIKE :s_ref OR l.loan_number LIKE :s_ln OR c.full_name LIKE :s_name OR c.phone LIKE :s_phone OR c.customer_code LIKE :s_code)";
        $wildcard = '%' . $search . '%';
        $params[':s_ref'] = $wildcard; $params[':s_ln'] = $wildcard; $params[':s_name'] = $wildcard; $params[':s_phone'] = $wildcard; $params[':s_code'] = $wildcard;
    }
    $whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

    $stmt = $db->prepare("
        SELECT p.*, l.loan_number, c.customer_code, c.full_name AS customer_name,
               li.installment_number, u.name AS collector_name
        FROM loan_payments p
        JOIN loans l ON p.loan_id = l.id
        JOIN customers c ON p.customer_id = c.id
        JOIN loan_installments li ON p.installment_id = li.id
        LEFT JOIN users u ON p.collected_by = u.id
        {$whereSql}
        ORDER BY p.payment_date DESC, p.id DESC
    ");
    $stmt->execute($params);

    while ($r = $stmt->fetch()) {
        write_sanitized_row($output, [
            $r['payment_reference'],
            $r['payment_date'],
            $r['loan_number'],
            $r['customer_code'],
            $r['customer_name'],
            $r['installment_number'],
            number_format((float)$r['amount'], 2, '.', ''),
            get_payment_method_label($r['payment_method']),
            $r['collector_name'] ?? 'System',
        ]);
    }

} elseif ($reportType === 'overdue') {
    // Header
    write_sanitized_row($output, [
        'Loan Number',
        'Customer Code',
        'Customer Name',
        'Phone',
        'Product Name',
        'Installment Number',
        'Due Date',
        'Days Overdue',
        'Installment Total ($)',
        'Paid Amount ($)',
        'Overdue Balance ($)'
    ]);

    $whereClauses = [
        "l.status = 'active'",
        "li.due_date < :today",
        "li.remaining_amount > 0"
    ];
    $params = [':today' => $today];

    if ($productId > 0) {
        $whereClauses[] = "l.loan_product_id = :product_id";
        $params[':product_id'] = $productId;
    }
    if ($agingBand === '1_30') {
        $whereClauses[] = "DATEDIFF(:today_1, li.due_date) BETWEEN 1 AND 30";
        $params[':today_1'] = $today;
    } elseif ($agingBand === '31_60') {
        $whereClauses[] = "DATEDIFF(:today_2, li.due_date) BETWEEN 31 AND 60";
        $params[':today_2'] = $today;
    } elseif ($agingBand === '61_90') {
        $whereClauses[] = "DATEDIFF(:today_3, li.due_date) BETWEEN 61 AND 90";
        $params[':today_3'] = $today;
    } elseif ($agingBand === '90_plus') {
        $whereClauses[] = "DATEDIFF(:today_4, li.due_date) > 90";
        $params[':today_4'] = $today;
    }
    if ($search !== '') {
        $whereClauses[] = "(l.loan_number LIKE :s_ln OR c.full_name LIKE :s_name OR c.phone LIKE :s_phone OR c.customer_code LIKE :s_code)";
        $wildcard = '%' . $search . '%';
        $params[':s_ln'] = $wildcard; $params[':s_name'] = $wildcard; $params[':s_phone'] = $wildcard; $params[':s_code'] = $wildcard;
    }
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);

    $stmt = $db->prepare("
        SELECT li.*, DATEDIFF(:today_calc, li.due_date) AS days_overdue,
               l.loan_number, c.customer_code, c.full_name AS customer_name, c.phone AS customer_phone,
               lp.name AS product_name
        FROM loan_installments li
        JOIN loans l ON li.loan_id = l.id
        JOIN customers c ON l.customer_id = c.id
        LEFT JOIN loan_products lp ON l.loan_product_id = lp.id
        {$whereSql}
        ORDER BY li.due_date ASC
    ");
    $stmt->bindValue(':today_calc', $today);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();

    while ($r = $stmt->fetch()) {
        write_sanitized_row($output, [
            $r['loan_number'],
            $r['customer_code'],
            $r['customer_name'],
            $r['customer_phone'],
            $r['product_name'] ?? 'Product',
            $r['installment_number'],
            $r['due_date'],
            $r['days_overdue'],
            number_format((float)$r['installment_amount'], 2, '.', ''),
            number_format((float)$r['paid_amount'], 2, '.', ''),
            number_format((float)$r['remaining_amount'], 2, '.', ''),
        ]);
    }

} elseif ($reportType === 'customer') {
    // Header
    write_sanitized_row($output, [
        'Customer Code',
        'Full Name',
        'Phone',
        'Email',
        'City',
        'KYC Status',
        'Total Loans Originated',
        'Active Loans',
        'Completed Loans',
        'Total Borrowed Capital ($)',
        'Total Repayments Paid ($)',
        'Outstanding Balance ($)'
    ]);

    $whereClauses = [];
    $params = [];
    if (in_array($status, ['active', 'inactive'], true)) {
        $whereClauses[] = "c.status = :status";
        $params[':status'] = $status;
    }
    if ($activity === 'with_loans') {
        $whereClauses[] = "EXISTS (SELECT 1 FROM loans l WHERE l.customer_id = c.id)";
    } elseif ($activity === 'with_active_loans') {
        $whereClauses[] = "EXISTS (SELECT 1 FROM loans l WHERE l.customer_id = c.id AND l.status = 'active')";
    }
    if ($search !== '') {
        $whereClauses[] = "(c.customer_code LIKE :s_code OR c.full_name LIKE :s_name OR c.phone LIKE :s_phone OR c.email LIKE :s_email OR c.city LIKE :s_city)";
        $wildcard = '%' . $search . '%';
        $params[':s_code'] = $wildcard; $params[':s_name'] = $wildcard; $params[':s_phone'] = $wildcard; $params[':s_email'] = $wildcard; $params[':s_city'] = $wildcard;
    }
    $whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

    $stmt = $db->prepare("
        SELECT c.id, c.customer_code, c.full_name, c.phone, c.email, c.city, c.status,
               COUNT(l.id) AS total_loans,
               COUNT(CASE WHEN l.status = 'active' THEN 1 END) AS active_loans,
               COUNT(CASE WHEN l.status = 'completed' THEN 1 END) AS completed_loans,
               COALESCE(SUM(l.disbursed_amount), 0) AS total_borrowed,
               (SELECT COALESCE(SUM(p.amount), 0) FROM loan_payments p WHERE p.customer_id = c.id) AS total_paid,
               (SELECT COALESCE(SUM(li.remaining_amount), 0) 
                FROM loan_installments li 
                JOIN loans l2 ON li.loan_id = l2.id 
                WHERE l2.customer_id = c.id AND l2.status = 'active' AND li.remaining_amount > 0) AS outstanding_balance
        FROM customers c
        LEFT JOIN loans l ON c.id = l.customer_id
        {$whereSql}
        GROUP BY c.id
        ORDER BY c.id DESC
    ");
    $stmt->execute($params);

    while ($r = $stmt->fetch()) {
        write_sanitized_row($output, [
            $r['customer_code'],
            $r['full_name'],
            $r['phone'],
            $r['email'] ?? '',
            $r['city'] ?? '',
            ucfirst($r['status']),
            $r['total_loans'],
            $r['active_loans'],
            $r['completed_loans'],
            number_format((float)$r['total_borrowed'], 2, '.', ''),
            number_format((float)$r['total_paid'], 2, '.', ''),
            number_format((float)$r['outstanding_balance'], 2, '.', ''),
        ]);
    }
}

fclose($output);
exit;
