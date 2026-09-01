<?php
/**
 * Official Payment Receipt View
 * Loan Management System (loan-mgt) - Phase 5
 */

$pageTitle = 'Payment Receipt';
$activeNav = 'repayments';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

$ref = trim($_GET['ref'] ?? '');
$id  = (int)($_GET['id'] ?? 0);

if (empty($ref) && $id <= 0) {
    set_flash('danger', 'Invalid payment receipt reference specified.');
    redirect('modules/repayments/index.php');
}

$db = get_db_connection();

if (!empty($ref)) {
    $stmt = $db->prepare('
        SELECT p.*, 
               l.loan_number, l.requested_amount, l.status AS loan_status,
               c.customer_code, c.full_name AS customer_name, c.phone AS customer_phone,
               c.email AS customer_email, c.address AS customer_address, c.city AS customer_city,
               li.installment_number, li.due_date AS installment_due_date, li.installment_amount,
               li.paid_amount AS inst_paid_amount, li.remaining_amount AS inst_remaining_amount,
               li.status AS inst_status,
               u.name AS collector_name, u.role AS collector_role
        FROM loan_payments p
        JOIN loans l ON p.loan_id = l.id
        JOIN customers c ON p.customer_id = c.id
        JOIN loan_installments li ON p.installment_id = li.id
        LEFT JOIN users u ON p.collected_by = u.id
        WHERE p.payment_reference = :ref
        LIMIT 1
    ');
    $stmt->execute([':ref' => $ref]);
} else {
    $stmt = $db->prepare('
        SELECT p.*, 
               l.loan_number, l.requested_amount, l.status AS loan_status,
               c.customer_code, c.full_name AS customer_name, c.phone AS customer_phone,
               c.email AS customer_email, c.address AS customer_address, c.city AS customer_city,
               li.installment_number, li.due_date AS installment_due_date, li.installment_amount,
               li.paid_amount AS inst_paid_amount, li.remaining_amount AS inst_remaining_amount,
               li.status AS inst_status,
               u.name AS collector_name, u.role AS collector_role
        FROM loan_payments p
        JOIN loans l ON p.loan_id = l.id
        JOIN customers c ON p.customer_id = c.id
        JOIN loan_installments li ON p.installment_id = li.id
        LEFT JOIN users u ON p.collected_by = u.id
        WHERE p.id = :id
        LIMIT 1
    ');
    $stmt->execute([':id' => $id]);
}

$payment = $stmt->fetch();

if (!$payment) {
    set_flash('danger', 'Payment receipt record not found.');
    redirect('modules/repayments/index.php');
}

// Compute total loan remaining balance
$totalLoanRemaining = (float)$db->query("
    SELECT COALESCE(SUM(remaining_amount), 0) 
    FROM loan_installments 
    WHERE loan_id = " . (int)$payment['loan_id']
)->fetchColumn();

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Print-Only Layout Styling -->
<style>
@media print {
    .navbar, .sidebar, .breadcrumb, .no-print, .btn {
        display: none !important;
    }
    .main-content {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    .receipt-container {
        border: 1px solid #333 !important;
        box-shadow: none !important;
        width: 100% !important;
        max-width: 100% !important;
    }
}
</style>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 no-print">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/repayments/index.php'); ?>" class="text-decoration-none text-muted">Repayments</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/repayments/view.php?loan_id=' . $payment['loan_id']); ?>" class="text-decoration-none text-muted"><?php echo e($payment['loan_number']); ?></a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Receipt <?php echo e($payment['payment_reference']); ?></li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h2 class="h4 fw-bold text-dark mb-0">Payment Receipt</h2>
            <span class="badge bg-success">Verified Transaction</span>
        </div>
    </div>

    <!-- Action Toolbar -->
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-primary" onclick="window.print();">
            <i class="bi bi-printer me-1"></i> Print Receipt
        </button>
        <a href="<?php echo url('modules/repayments/view.php?loan_id=' . $payment['loan_id']); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Ledger
        </a>
        <?php if ($payment['loan_status'] === 'active' && can_collect_payments() && $totalLoanRemaining > 0): ?>
            <a href="<?php echo url('modules/repayments/collect.php?loan_id=' . $payment['loan_id']); ?>" class="btn btn-outline-success">
                <i class="bi bi-cash me-1"></i> Collect Another Payment
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Receipt Card -->
<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm mb-4 receipt-container border">
            <!-- Receipt Header -->
            <div class="card-header bg-white py-4 px-4 border-bottom">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <div class="bg-primary text-white rounded p-2 d-inline-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                <i class="bi bi-bank2 fs-5"></i>
                            </div>
                            <span class="fs-4 fw-bold text-dark"><?php echo e(APP_NAME); ?></span>
                        </div>
                        <p class="text-muted small mb-0">Official Repayment Acknowledgment Slip</p>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-light text-dark border font-monospace fs-6 px-3 py-2">
                            <?php echo e($payment['payment_reference']); ?>
                        </span>
                        <div class="text-muted small mt-1">Date: <?php echo date('F j, Y', strtotime($payment['payment_date'])); ?></div>
                    </div>
                </div>
            </div>

            <!-- Receipt Body -->
            <div class="card-body p-4">
                <!-- Borrower & Loan Info Grid -->
                <div class="row g-3 mb-4 pb-3 border-bottom small">
                    <div class="col-6">
                        <span class="text-muted text-uppercase d-block" style="font-size: 0.75rem;">Received From (Borrower)</span>
                        <strong class="text-dark fs-6"><?php echo e($payment['customer_name']); ?></strong>
                        <div class="text-muted font-monospace"><?php echo e($payment['customer_code']); ?> &bull; <?php echo e($payment['customer_phone']); ?></div>
                        <div class="text-muted"><?php echo e($payment['customer_address'] ?: ($payment['customer_city'] ?: 'Address on file')); ?></div>
                    </div>

                    <div class="col-6 text-end">
                        <span class="text-muted text-uppercase d-block" style="font-size: 0.75rem;">Loan Account Details</span>
                        <strong class="text-dark fs-6 font-monospace"><?php echo e($payment['loan_number']); ?></strong>
                        <div class="text-muted">Installment #<?php echo $payment['installment_number']; ?> (Due: <?php echo date('M d, Y', strtotime($payment['installment_due_date'])); ?>)</div>
                        <div class="text-muted">Channel: <span class="fw-semibold text-dark"><?php echo e(get_payment_method_label($payment['payment_method'])); ?></span></div>
                    </div>
                </div>

                <!-- Transaction Amount Banner -->
                <div class="p-3 bg-light rounded text-center mb-4 border">
                    <span class="text-muted small text-uppercase fw-semibold d-block mb-1">Total Amount Received</span>
                    <span class="display-6 fw-bold text-success font-monospace"><?php echo format_currency($payment['amount']); ?></span>
                </div>

                <!-- Installment & Balance Breakdown Table -->
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 0.9rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Description</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Scheduled Installment #<?php echo $payment['installment_number']; ?> Total Amount</td>
                                <td class="text-end font-monospace"><?php echo format_currency($payment['installment_amount']); ?></td>
                            </tr>
                            <tr class="table-success table-opacity-10 fw-bold">
                                <td>Payment Applied on This Receipt</td>
                                <td class="text-end text-success font-monospace">-<?php echo format_currency($payment['amount']); ?></td>
                            </tr>
                            <tr>
                                <td>Installment #<?php echo $payment['installment_number']; ?> Remaining Balance</td>
                                <td class="text-end font-monospace <?php echo (float)$payment['inst_remaining_amount'] > 0 ? 'text-danger fw-bold' : 'text-success'; ?>">
                                    <?php echo format_currency($payment['inst_remaining_amount']); ?>
                                </td>
                            </tr>
                            <tr class="fw-bold table-light">
                                <td>Total Remaining Loan Portfolio Balance</td>
                                <td class="text-end font-monospace text-primary">
                                    <?php echo format_currency($totalLoanRemaining); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($payment['notes'])): ?>
                    <div class="mb-4 small">
                        <span class="text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 0.75rem;">Payment Remarks / Notes</span>
                        <div class="p-2.5 bg-light rounded text-dark font-monospace"><?php echo nl2br(e($payment['notes'])); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Signatures & Audit Stamp -->
                <div class="row pt-4 mt-4 border-top text-center small text-muted">
                    <div class="col-6">
                        <div class="mb-4 pb-2">_____________________________</div>
                        <span class="d-block fw-semibold text-dark"><?php echo e($payment['customer_name']); ?></span>
                        <span>Borrower Signature</span>
                    </div>

                    <div class="col-6">
                        <div class="mb-4 pb-2">_____________________________</div>
                        <span class="d-block fw-semibold text-dark"><?php echo e($payment['collector_name'] ?? 'Authorized Officer'); ?></span>
                        <span>Received & Authorized By</span>
                    </div>
                </div>
            </div>

            <!-- Receipt Footer -->
            <div class="card-footer bg-white text-muted small text-center py-3 border-top">
                <i class="bi bi-shield-check text-success me-1"></i> Computer-generated official receipt. Recorded on <?php echo date('M d, Y g:i A', strtotime($payment['created_at'])); ?>.
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
