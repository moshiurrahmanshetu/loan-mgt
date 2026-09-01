<?php
/**
 * Universal Clean Printable Report View
 * Loan Management System (loan-mgt) - Phase 6
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// 1. Validate Report Identifier & Security Guard
$allowedReports = ['loan', 'disbursement', 'repayment', 'overdue', 'customer', 'portfolio'];
$reportType = trim($_GET['report'] ?? '');

if (!in_array($reportType, $allowedReports, true)) {
    set_flash('danger', 'Invalid report type requested.');
    redirect('modules/reports/index.php');
}

if (!can_access_report($reportType)) {
    set_flash('danger', 'Unauthorized: You do not have permission to print this report.');
    redirect('modules/reports/index.php');
}

$db = get_db_connection();
$today = date('Y-m-d');
$user = auth_user();

// Extract parameters
$fromDate  = trim($_GET['from_date'] ?? '');
$toDate    = trim($_GET['to_date'] ?? '');
$status    = trim($_GET['status'] ?? 'all');
$productId = (int)($_GET['product_id'] ?? 0);
$method    = trim($_GET['method'] ?? 'all');
$disbBy    = (int)($_GET['disbursed_by'] ?? 0);
$collectorId = (int)($_GET['collector_id'] ?? 0);
$agingBand = trim($_GET['aging'] ?? 'all');
$activity  = trim($_GET['activity'] ?? 'all');
$search    = trim($_GET['search'] ?? '');

$filterSummaries = [];
if (!empty($fromDate) || !empty($toDate)) {
    $filterSummaries[] = 'Date Range: ' . ($fromDate ?: 'Start') . ' to ' . ($toDate ?: 'Current');
}
if ($status !== 'all' && $status !== '') {
    $filterSummaries[] = 'Status: ' . ucfirst($status);
}
if ($productId > 0) {
    $pName = $db->query("SELECT name FROM loan_products WHERE id = {$productId}")->fetchColumn();
    if ($pName) $filterSummaries[] = 'Product: ' . $pName;
}
if ($method !== 'all' && $method !== '') {
    $filterSummaries[] = 'Method: ' . get_payment_method_label($method);
}
if ($search !== '') {
    $filterSummaries[] = 'Search: "' . htmlspecialchars($search) . '"';
}

$reportTitle = match ($reportType) {
    'loan'         => 'Loan Applications & Portfolio Report',
    'disbursement' => 'Loan Capital Disbursement Report',
    'repayment'    => 'Repayment & Collections Ledger Report',
    'overdue'      => 'Overdue Delinquency Report',
    'customer'     => 'Customer Summary & Performance Report',
    'portfolio'    => 'Financial Portfolio Summary & Audit',
    default        => 'System Report',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($reportTitle); ?> — <?php echo e(APP_NAME); ?></title>
    <!-- Local Bootstrap 5 CSS -->
    <link rel="stylesheet" href="<?php echo asset('vendor/bootstrap/css/bootstrap.min.css'); ?>">
    <!-- Local Bootstrap Icons CSS -->
    <link rel="stylesheet" href="<?php echo asset('vendor/bootstrap-icons/bootstrap-icons.css'); ?>">
    <style>
        body {
            background-color: #f8fafc;
            color: #0f172a;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 0.875rem;
        }
        .print-container {
            max-width: 1140px;
            margin: 2rem auto;
            background: #ffffff;
            padding: 2.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }
        .table-print th {
            background-color: #f1f5f9 !important;
            color: #475569;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #cbd5e1 !important;
        }
        .table-print td, .table-print th {
            padding: 0.65rem 0.75rem;
            border-color: #e2e8f0;
        }
        @media print {
            body {
                background-color: #ffffff !important;
            }
            .no-print {
                display: none !important;
            }
            .print-container {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }
            .table-print th {
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

<!-- Action Bar (Hidden on Print) -->
<div class="no-print bg-dark text-white py-2 px-3 mb-4 sticky-top">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-printer-fill fs-5 text-primary"></i>
            <span class="fw-bold"><?php echo e($reportTitle); ?></span>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary btn-sm px-3" onclick="window.print();">
                <i class="bi bi-printer me-1"></i> Print Now
            </button>
            <button type="button" class="btn btn-outline-light btn-sm" onclick="window.close();">
                <i class="bi bi-x-circle me-1"></i> Close
            </button>
        </div>
    </div>
</div>

<div class="print-container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <div class="bg-primary text-white rounded p-2 d-inline-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                    <i class="bi bi-bank2 fs-5"></i>
                </div>
                <h1 class="h4 fw-bold text-dark mb-0"><?php echo e(APP_NAME); ?></h1>
            </div>
            <p class="text-muted small mb-0">Official Management Report</p>
        </div>
        <div class="text-end">
            <h2 class="h5 fw-bold text-dark mb-1"><?php echo e($reportTitle); ?></h2>
            <div class="text-muted small">Generated on: <strong><?php echo date('F j, Y, g:i A'); ?></strong></div>
            <div class="text-muted small">Generated by: <strong><?php echo e($user['name'] ?? 'System'); ?></strong></div>
        </div>
    </div>

    <!-- Active Filters Summary Banner -->
    <?php if (!empty($filterSummaries)): ?>
        <div class="p-2.5 bg-light rounded border mb-4 small">
            <strong class="text-dark me-2">Applied Filters:</strong>
            <?php foreach ($filterSummaries as $f): ?>
                <span class="badge bg-white text-dark border me-1"><?php echo e($f); ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Report Data Content -->
    <?php if ($reportType === 'loan'): ?>
        <?php
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
        $stmt = $db->prepare("SELECT l.*, c.customer_code, c.full_name AS customer_name, lp.name AS product_name FROM loans l JOIN customers c ON l.customer_id = c.id LEFT JOIN loan_products lp ON l.loan_product_id = lp.id {$whereSql} ORDER BY l.application_date DESC LIMIT 500");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $totPrinc = 0; $totInt = 0; $totPay = 0;
        ?>
        <table class="table table-sm table-bordered table-print align-middle">
            <thead>
                <tr>
                    <th>Loan #</th>
                    <th>Borrower</th>
                    <th>Product</th>
                    <th class="text-end">Principal</th>
                    <th class="text-end">Interest</th>
                    <th class="text-end">Payable</th>
                    <th>Term</th>
                    <th>App Date</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <?php $totPrinc += (float)$r['requested_amount']; $totInt += (float)$r['estimated_interest_amount']; $totPay += (float)$r['estimated_total_payable']; ?>
                    <tr>
                        <td class="font-monospace fw-bold"><?php echo e($r['loan_number']); ?></td>
                        <td><?php echo e($r['customer_name']); ?> (<?php echo e($r['customer_code']); ?>)</td>
                        <td><?php echo e($r['product_name']); ?></td>
                        <td class="text-end font-monospace"><?php echo format_currency($r['requested_amount']); ?></td>
                        <td class="text-end font-monospace"><?php echo format_currency($r['estimated_interest_amount']); ?></td>
                        <td class="text-end font-monospace fw-bold"><?php echo format_currency($r['estimated_total_payable']); ?></td>
                        <td><?php echo (int)$r['term'] . ' ' . ucfirst($r['term_unit']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($r['application_date'])); ?></td>
                        <td class="text-center text-capitalize"><?php echo e($r['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="fw-bold bg-light">
                <tr>
                    <td colspan="3" class="text-uppercase">Grand Totals (<?php echo count($rows); ?> Loans):</td>
                    <td class="text-end font-monospace"><?php echo format_currency($totPrinc); ?></td>
                    <td class="text-end font-monospace"><?php echo format_currency($totInt); ?></td>
                    <td class="text-end font-monospace"><?php echo format_currency($totPay); ?></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>

    <?php elseif ($reportType === 'disbursement'): ?>
        <?php
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
        $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
        $stmt = $db->prepare("SELECT l.*, c.customer_code, c.full_name AS customer_name, lp.name AS product_name, u.name AS disburser_name FROM loans l JOIN customers c ON l.customer_id = c.id LEFT JOIN loan_products lp ON l.loan_product_id = lp.id LEFT JOIN users u ON l.disbursed_by = u.id {$whereSql} ORDER BY l.disbursement_date DESC LIMIT 500");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $totDisb = 0;
        ?>
        <table class="table table-sm table-bordered table-print align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Loan #</th>
                    <th>Borrower</th>
                    <th>Product</th>
                    <th class="text-end">Disbursed Amount</th>
                    <th>Method</th>
                    <th>Authorized By</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <?php $totDisb += (float)$r['disbursed_amount']; ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($r['disbursement_date'])); ?></td>
                        <td class="font-monospace fw-bold"><?php echo e($r['loan_number']); ?></td>
                        <td><?php echo e($r['customer_name']); ?> (<?php echo e($r['customer_code']); ?>)</td>
                        <td><?php echo e($r['product_name']); ?></td>
                        <td class="text-end font-monospace fw-bold"><?php echo format_currency($r['disbursed_amount']); ?></td>
                        <td><?php echo e(get_disbursement_method_label($r['disbursement_method'] ?? 'cash')); ?></td>
                        <td><?php echo e($r['disburser_name'] ?? 'System Officer'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="fw-bold bg-light">
                <tr>
                    <td colspan="4" class="text-uppercase">Total Disbursed (<?php echo count($rows); ?> Loans):</td>
                    <td class="text-end font-monospace"><?php echo format_currency($totDisb); ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>

    <?php elseif ($reportType === 'repayment'): ?>
        <?php
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
        $whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
        $stmt = $db->prepare("SELECT p.*, l.loan_number, c.customer_code, c.full_name AS customer_name, li.installment_number, u.name AS collector_name FROM loan_payments p JOIN loans l ON p.loan_id = l.id JOIN customers c ON p.customer_id = c.id JOIN loan_installments li ON p.installment_id = li.id LEFT JOIN users u ON p.collected_by = u.id {$whereSql} ORDER BY p.payment_date DESC LIMIT 500");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $totColl = 0;
        ?>
        <table class="table table-sm table-bordered table-print align-middle">
            <thead>
                <tr>
                    <th>Receipt Ref</th>
                    <th>Payment Date</th>
                    <th>Loan #</th>
                    <th>Borrower</th>
                    <th class="text-center">Inst #</th>
                    <th class="text-end">Amount Paid</th>
                    <th>Method</th>
                    <th>Collected By</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <?php $totColl += (float)$r['amount']; ?>
                    <tr>
                        <td class="font-monospace fw-bold"><?php echo e($r['payment_reference']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($r['payment_date'])); ?></td>
                        <td class="font-monospace"><?php echo e($r['loan_number']); ?></td>
                        <td><?php echo e($r['customer_name']); ?></td>
                        <td class="text-center">#<?php echo $r['installment_number']; ?></td>
                        <td class="text-end font-monospace fw-bold"><?php echo format_currency($r['amount']); ?></td>
                        <td><?php echo e(get_payment_method_label($r['payment_method'])); ?></td>
                        <td><?php echo e($r['collector_name'] ?? 'System'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="fw-bold bg-light">
                <tr>
                    <td colspan="5" class="text-uppercase">Total Realized Collections (<?php echo count($rows); ?> Receipts):</td>
                    <td class="text-end font-monospace"><?php echo format_currency($totColl); ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>

    <?php elseif ($reportType === 'overdue'): ?>
        <?php
        $whereClauses = ["l.status = 'active'", "li.due_date < '{$today}'", "li.remaining_amount > 0"];
        $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
        $stmt = $db->prepare("SELECT li.*, DATEDIFF('{$today}', li.due_date) AS days_overdue, l.loan_number, c.customer_code, c.full_name AS customer_name, lp.name AS product_name FROM loan_installments li JOIN loans l ON li.loan_id = l.id JOIN customers c ON l.customer_id = c.id LEFT JOIN loan_products lp ON l.loan_product_id = lp.id {$whereSql} ORDER BY li.due_date ASC LIMIT 500");
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $totOverdue = 0;
        ?>
        <table class="table table-sm table-bordered table-print align-middle">
            <thead>
                <tr>
                    <th>Loan #</th>
                    <th>Borrower</th>
                    <th>Product</th>
                    <th class="text-center">Inst #</th>
                    <th>Due Date</th>
                    <th class="text-center">Days Overdue</th>
                    <th class="text-end">Scheduled Due</th>
                    <th class="text-end">Paid</th>
                    <th class="text-end">Overdue Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <?php $totOverdue += (float)$r['remaining_amount']; ?>
                    <tr>
                        <td class="font-monospace fw-bold"><?php echo e($r['loan_number']); ?></td>
                        <td><?php echo e($r['customer_name']); ?> (<?php echo e($r['customer_code']); ?>)</td>
                        <td><?php echo e($r['product_name']); ?></td>
                        <td class="text-center">#<?php echo $r['installment_number']; ?></td>
                        <td><?php echo date('M d, Y', strtotime($r['due_date'])); ?></td>
                        <td class="text-center fw-bold text-danger"><?php echo $r['days_overdue']; ?> Days</td>
                        <td class="text-end font-monospace"><?php echo format_currency($r['installment_amount']); ?></td>
                        <td class="text-end font-monospace"><?php echo format_currency($r['paid_amount']); ?></td>
                        <td class="text-end font-monospace fw-bold text-danger"><?php echo format_currency($r['remaining_amount']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="fw-bold bg-light">
                <tr>
                    <td colspan="8" class="text-uppercase">Total Delinquent Exposure (<?php echo count($rows); ?> Installments):</td>
                    <td class="text-end font-monospace text-danger"><?php echo format_currency($totOverdue); ?></td>
                </tr>
            </tfoot>
        </table>

    <?php elseif ($reportType === 'customer'): ?>
        <?php
        $stmt = $db->query("
            SELECT c.id, c.customer_code, c.full_name, c.phone, c.city,
                   COUNT(l.id) AS total_loans,
                   COUNT(CASE WHEN l.status = 'active' THEN 1 END) AS active_loans,
                   COALESCE(SUM(l.disbursed_amount), 0) AS total_borrowed,
                   (SELECT COALESCE(SUM(p.amount), 0) FROM loan_payments p WHERE p.customer_id = c.id) AS total_paid,
                   (SELECT COALESCE(SUM(li.remaining_amount), 0) FROM loan_installments li JOIN loans l2 ON li.loan_id = l2.id WHERE l2.customer_id = c.id AND l2.status = 'active' AND li.remaining_amount > 0) AS outstanding_balance
            FROM customers c
            LEFT JOIN loans l ON c.id = l.customer_id
            GROUP BY c.id
            ORDER BY c.id DESC
            LIMIT 500
        ");
        $rows = $stmt->fetchAll();
        $totCustBorr = 0; $totCustPaid = 0; $totCustOut = 0;
        ?>
        <table class="table table-sm table-bordered table-print align-middle">
            <thead>
                <tr>
                    <th>Customer Code</th>
                    <th>Full Name</th>
                    <th>Phone</th>
                    <th>City</th>
                    <th class="text-center">Total Loans</th>
                    <th class="text-center">Active Loans</th>
                    <th class="text-end">Disbursed</th>
                    <th class="text-end">Paid</th>
                    <th class="text-end">Outstanding</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <?php $totCustBorr += (float)$r['total_borrowed']; $totCustPaid += (float)$r['total_paid']; $totCustOut += (float)$r['outstanding_balance']; ?>
                    <tr>
                        <td class="font-monospace fw-bold"><?php echo e($r['customer_code']); ?></td>
                        <td><?php echo e($r['full_name']); ?></td>
                        <td><?php echo e($r['phone']); ?></td>
                        <td><?php echo e($r['city'] ?: '—'); ?></td>
                        <td class="text-center"><?php echo (int)$r['total_loans']; ?></td>
                        <td class="text-center"><?php echo (int)$r['active_loans']; ?></td>
                        <td class="text-end font-monospace"><?php echo format_currency($r['total_borrowed']); ?></td>
                        <td class="text-end font-monospace"><?php echo format_currency($r['total_paid']); ?></td>
                        <td class="text-end font-monospace fw-bold"><?php echo format_currency($r['outstanding_balance']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="fw-bold bg-light">
                <tr>
                    <td colspan="6" class="text-uppercase">Grand Totals (<?php echo count($rows); ?> Customers):</td>
                    <td class="text-end font-monospace"><?php echo format_currency($totCustBorr); ?></td>
                    <td class="text-end font-monospace"><?php echo format_currency($totCustPaid); ?></td>
                    <td class="text-end font-monospace"><?php echo format_currency($totCustOut); ?></td>
                </tr>
            </tfoot>
        </table>

    <?php elseif ($reportType === 'portfolio'): ?>
        <?php
        $totDisb = (float)$db->query("SELECT COALESCE(SUM(disbursed_amount), 0) FROM loans WHERE status IN ('active', 'completed')")->fetchColumn();
        $totColl = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM loan_payments")->fetchColumn();
        $totOut  = (float)$db->query("SELECT COALESCE(SUM(li.remaining_amount), 0) FROM loan_installments li JOIN loans l ON li.loan_id = l.id WHERE l.status = 'active' AND li.remaining_amount > 0")->fetchColumn();
        $totExpInt = (float)$db->query("SELECT COALESCE(SUM(estimated_interest_amount), 0) FROM loans WHERE status IN ('active', 'completed')")->fetchColumn();
        $totPayable = (float)$db->query("SELECT COALESCE(SUM(estimated_total_payable), 0) FROM loans WHERE status IN ('active', 'completed')")->fetchColumn();

        $prodRows = $db->query("
            SELECT lp.product_code, lp.name AS product_name, lp.interest_rate, lp.interest_method,
                   COUNT(l.id) AS total_loans,
                   COALESCE(SUM(CASE WHEN l.status IN ('active', 'completed') THEN l.disbursed_amount ELSE 0 END), 0) AS total_disbursed,
                   COALESCE(SUM(CASE WHEN l.status = 'active' THEN (SELECT SUM(li.remaining_amount) FROM loan_installments li WHERE li.loan_id = l.id) ELSE 0 END), 0) AS total_outstanding
            FROM loan_products lp
            LEFT JOIN loans l ON lp.id = l.loan_product_id
            GROUP BY lp.id
        ")->fetchAll();
        ?>
        <div class="row g-3 mb-4">
            <div class="col-4 border p-3 bg-light">
                <span class="text-muted d-block small">Capital Disbursed</span>
                <strong class="fs-5 font-monospace"><?php echo format_currency($totDisb); ?></strong>
            </div>
            <div class="col-4 border p-3 bg-light">
                <span class="text-muted d-block small">Repayments Realized</span>
                <strong class="fs-5 font-monospace text-success"><?php echo format_currency($totColl); ?></strong>
            </div>
            <div class="col-4 border p-3 bg-light">
                <span class="text-muted d-block small">Current Active Outstanding</span>
                <strong class="fs-5 font-monospace text-danger"><?php echo format_currency($totOut); ?></strong>
            </div>
        </div>

        <h3 class="h6 fw-bold mb-2">Product Portfolio Performance</h3>
        <table class="table table-sm table-bordered table-print align-middle mb-4">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Code</th>
                    <th>Interest</th>
                    <th class="text-center">Loans</th>
                    <th class="text-end">Disbursed</th>
                    <th class="text-end">Outstanding</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prodRows as $pr): ?>
                    <tr>
                        <td><?php echo e($pr['product_name']); ?></td>
                        <td class="font-monospace"><?php echo e($pr['product_code']); ?></td>
                        <td><?php echo number_format($pr['interest_rate'], 2); ?>% (<?php echo e($pr['interest_method']); ?>)</td>
                        <td class="text-center"><?php echo (int)$pr['total_loans']; ?></td>
                        <td class="text-end font-monospace"><?php echo format_currency($pr['total_disbursed']); ?></td>
                        <td class="text-end font-monospace fw-bold"><?php echo format_currency($pr['total_outstanding']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Signature & Footer Stamp -->
    <div class="row pt-5 mt-5 border-top text-center small text-muted">
        <div class="col-4">
            <div class="mb-4">_____________________________</div>
            <span>Prepared By</span>
        </div>
        <div class="col-4">
            <div class="mb-4">_____________________________</div>
            <span>Verified By / Auditor</span>
        </div>
        <div class="col-4">
            <div class="mb-4">_____________________________</div>
            <span>Approved By Management</span>
        </div>
    </div>
</div>

</body>
</html>
