<?php
/**
 * Edit Loan Application View
 * Loan Management System (loan-mgt) - Phase 3
 */

$pageTitle = 'Edit Loan Application';
$activeNav = 'loans';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

$loanId = (int)($_GET['id'] ?? 0);
if ($loanId <= 0) {
    set_flash('danger', 'Invalid loan application specified.');
    redirect('modules/loans/index.php');
}

$db = get_db_connection();

$stmt = $db->prepare('
    SELECT l.*, 
           c.customer_code, c.full_name AS customer_name, c.phone AS customer_phone,
           lp.name AS current_product_name, lp.product_code AS current_product_code
    FROM loans l 
    JOIN customers c ON l.customer_id = c.id 
    LEFT JOIN loan_products lp ON l.loan_product_id = lp.id 
    WHERE l.id = :id 
    LIMIT 1
');
$stmt->execute([':id' => $loanId]);
$loan = $stmt->fetch();

if (!$loan) {
    set_flash('danger', 'Loan application record not found.');
    redirect('modules/loans/index.php');
}

// Authorization check: only editable if draft or pending according to role rules
if (!can_edit_loan($loan, auth_id())) {
    set_flash('danger', 'Unauthorized: You do not have permissions to modify this loan application in its current state (' . ucfirst($loan['status']) . ').');
    redirect('modules/loans/view.php?id=' . $loanId);
}

// Fetch active products plus current product if inactive
$prodStmt = $db->prepare('SELECT * FROM loan_products WHERE status = "active" OR id = :current_id ORDER BY name ASC');
$prodStmt->execute([':current_id' => $loan['loan_product_id']]);
$products = $prodStmt->fetchAll();

$old = $_SESSION['_old_loan_edit_input'] ?? [];
unset($_SESSION['_old_loan_edit_input']);

$data = !empty($old) ? $old : $loan;

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/loans/index.php'); ?>" class="text-decoration-none text-muted">Loan Applications</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/loans/view.php?id=' . $loan['id']); ?>" class="text-decoration-none text-muted"><?php echo e($loan['loan_number']); ?></a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Edit Application</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h2 class="h4 fw-bold text-dark mb-0">Edit Application: <?php echo e($loan['loan_number']); ?></h2>
            <?php echo get_loan_status_badge($loan['status']); ?>
        </div>
    </div>

    <div class="d-flex gap-2">
        <a href="<?php echo url('modules/loans/view.php?id=' . $loan['id']); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-eye me-1"></i> View Details
        </a>
        <a href="<?php echo url('modules/loans/index.php'); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Portfolio
        </a>
    </div>
</div>

<form action="<?php echo url('modules/loans/update.php'); ?>" method="POST" id="loanEditForm" autocomplete="off">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="id" value="<?php echo (int)$loan['id']; ?>">

    <div class="row g-4">
        <!-- Main Application Column -->
        <div class="col-12 col-lg-8">
            <!-- 1. Fixed Borrower Info -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-person-fill me-2 text-primary"></i> 1. Borrower (Fixed)</h3>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Customer Code</label>
                            <input type="text" class="form-control bg-light font-monospace" value="<?php echo e($loan['customer_code']); ?>" readonly disabled>
                        </div>
                        <div class="col-12 col-md-8">
                            <label class="form-label">Customer Full Name</label>
                            <input type="text" class="form-control bg-light" value="<?php echo e($loan['customer_name']); ?> (<?php echo e($loan['customer_phone']); ?>)" readonly disabled>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Product Selection & Terms -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-tag-fill me-2 text-primary"></i> 2. Loan Product Template</h3>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label for="loan_product_id" class="form-label">Loan Product <span class="text-danger">*</span></label>
                        <select class="form-select" id="loan_product_id" name="loan_product_id" required>
                            <?php foreach ($products as $prod): ?>
                                <?php 
                                    $selected = ((int)($data['loan_product_id'] ?? $loan['loan_product_id']) === (int)$prod['id']) ? 'selected' : '';
                                ?>
                                <option value="<?php echo (int)$prod['id']; ?>" <?php echo $selected; ?>
                                        data-code="<?php echo e($prod['product_code']); ?>"
                                        data-name="<?php echo e($prod['name']); ?>"
                                        data-min-amount="<?php echo (float)$prod['minimum_amount']; ?>"
                                        data-max-amount="<?php echo (float)$prod['maximum_amount']; ?>"
                                        data-rate="<?php echo (float)$prod['interest_rate']; ?>"
                                        data-method="<?php echo e($prod['interest_method']); ?>"
                                        data-term-min="<?php echo (int)$prod['term_min']; ?>"
                                        data-term-max="<?php echo (int)$prod['term_max']; ?>"
                                        data-term-unit="<?php echo e($prod['term_unit']); ?>"
                                        data-frequency="<?php echo e($prod['repayment_frequency']); ?>"
                                        data-fee="<?php echo (float)$prod['processing_fee']; ?>">
                                    <?php echo e($prod['product_code']); ?> — <?php echo e($prod['name']); ?> (<?php echo number_format($prod['interest_rate'], 2); ?>% <?php echo e(get_interest_method_label($prod['interest_method'])); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Product Rule Display Card -->
                    <div id="productRulesCard" class="p-3 bg-light rounded border mb-0">
                        <h4 class="small fw-bold text-dark text-uppercase mb-2"><i class="bi bi-info-circle me-1"></i> Configured Product Rules</h4>
                        <div class="row g-2 small">
                            <div class="col-6 col-md-3">
                                <span class="text-muted d-block">Allowed Amount</span>
                                <span id="pBoxAmountRange" class="fw-semibold text-dark">-</span>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="text-muted d-block">Interest Rate & Method</span>
                                <span id="pBoxRate" class="fw-semibold text-primary">-</span>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="text-muted d-block">Permitted Term</span>
                                <span id="pBoxTermRange" class="fw-semibold text-dark">-</span>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="text-muted d-block">Repayment & Fee</span>
                                <span id="pBoxFrequencyFee" class="fw-semibold text-dark">-</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Requested Amount & Term -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-cash-coin me-2 text-primary"></i> 3. Loan Amount & Duration</h3>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="requested_amount" class="form-label">Requested Loan Amount ($) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" min="1" class="form-control" id="requested_amount" name="requested_amount" value="<?php echo e($data['requested_amount'] ?? ''); ?>" required>
                            </div>
                            <div class="form-text small" id="amountHelp">Enter an amount within product limits.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="term" class="form-label">Loan Term <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" min="1" class="form-control" id="term" name="term" value="<?php echo e($data['term'] ?? ''); ?>" required>
                                <span class="input-group-text bg-light" id="termUnitLabel"><?php echo ucfirst($loan['term_unit']); ?></span>
                            </div>
                            <div class="form-text small" id="termHelp">Select term within product duration range.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="application_date" class="form-label">Application Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="application_date" name="application_date" value="<?php echo e($data['application_date'] ?? date('Y-m-d')); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="purpose" class="form-label">Loan Purpose</label>
                        <textarea class="form-control" id="purpose" name="purpose" rows="2"><?php echo e($data['purpose'] ?? ''); ?></textarea>
                    </div>

                    <div class="mb-0">
                        <label for="notes" class="form-label">Underwriting Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"><?php echo e($data['notes'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right / Calculation Preview Column -->
        <div class="col-12 col-lg-4">
            <!-- Calculation Preview Card -->
            <div class="card shadow-sm mb-4 border-primary">
                <div class="card-header bg-primary text-white py-3">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-calculator me-2"></i> Recalculated Loan Breakdown</h3>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Principal Amount:</span>
                        <span class="fw-bold text-dark" id="calcPrincipal">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Interest Rate:</span>
                        <span class="fw-semibold text-primary" id="calcRate">0.00%</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Interest Method:</span>
                        <span class="text-dark small" id="calcMethod">-</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Estimated Interest:</span>
                        <span class="fw-semibold text-dark" id="calcInterest">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Processing Fee (<span id="calcFeePercent">0%</span>):</span>
                        <span class="fw-semibold text-danger" id="calcFeeAmount">$0.00</span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold text-dark">Estimated Total Payable:</span>
                        <span class="h5 fw-bold text-primary mb-0" id="calcTotal">$0.00</span>
                    </div>
                    <div class="text-muted" style="font-size: 0.75rem;">
                        <i class="bi bi-info-circle me-1"></i> Saving will update contract snapshot values.
                    </div>
                </div>
            </div>

            <!-- Submit Action Card -->
            <div class="card shadow-sm mb-0">
                <div class="card-header bg-white py-3">
                    <h4 class="h6 mb-0 fw-bold"><i class="bi bi-save me-2 text-primary"></i> Save Changes</h4>
                </div>
                <div class="card-body p-3 d-grid gap-2">
                    <button type="submit" name="action" value="save" class="btn btn-primary py-2.5 fw-semibold">
                        <i class="bi bi-check-lg me-1"></i> Save Changes
                    </button>
                    <?php if ($loan['status'] === 'draft'): ?>
                        <button type="submit" name="action" value="submit" class="btn btn-success py-2">
                            <i class="bi bi-send-fill me-1"></i> Save & Submit for Approval
                        </button>
                    <?php endif; ?>
                    <a href="<?php echo url('modules/loans/view.php?id=' . $loan['id']); ?>" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productSelect = document.getElementById('loan_product_id');
    const amountInput   = document.getElementById('requested_amount');
    const termInput     = document.getElementById('term');
    const termUnitLabel = document.getElementById('termUnitLabel');
    const amountHelp    = document.getElementById('amountHelp');
    const termHelp      = document.getElementById('termHelp');
    const productRulesCard = document.getElementById('productRulesCard');

    function updateProductRulesAndCalculation() {
        const selected = productSelect.options[productSelect.selectedIndex];
        if (!selected || !selected.value) {
            productRulesCard.classList.add('d-none');
            return;
        }

        const minAmt   = parseFloat(selected.getAttribute('data-min-amount')) || 0;
        const maxAmt   = parseFloat(selected.getAttribute('data-max-amount')) || 0;
        const rate     = parseFloat(selected.getAttribute('data-rate')) || 0;
        const method   = selected.getAttribute('data-method') || 'flat';
        const termMin  = parseInt(selected.getAttribute('data-term-min'), 10) || 1;
        const termMax  = parseInt(selected.getAttribute('data-term-max'), 10) || 12;
        const unit     = selected.getAttribute('data-term-unit') || 'months';
        const freq     = selected.getAttribute('data-frequency') || 'monthly';
        const feeRate  = parseFloat(selected.getAttribute('data-fee')) || 0;

        document.getElementById('pBoxAmountRange').textContent = '$' + minAmt.toLocaleString(undefined, {minimumFractionDigits: 2}) + ' – $' + maxAmt.toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('pBoxRate').textContent = rate.toFixed(2) + '% (' + (method === 'flat' ? 'Flat' : 'Reducing') + ')';
        document.getElementById('pBoxTermRange').textContent = termMin + ' – ' + termMax + ' ' + unit.charAt(0).toUpperCase() + unit.slice(1);
        document.getElementById('pBoxFrequencyFee').textContent = freq.charAt(0).toUpperCase() + freq.slice(1) + ' / ' + feeRate.toFixed(2) + '% Fee';
        productRulesCard.classList.remove('d-none');

        termUnitLabel.textContent = unit.charAt(0).toUpperCase() + unit.slice(1);
        amountHelp.textContent = 'Allowed Range: $' + minAmt.toLocaleString() + ' to $' + maxAmt.toLocaleString();
        termHelp.textContent = 'Allowed Duration: ' + termMin + ' to ' + termMax + ' ' + unit;

        const principal = parseFloat(amountInput.value) || 0;
        document.getElementById('calcPrincipal').textContent = '$' + principal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('calcRate').textContent = rate.toFixed(2) + '%';
        document.getElementById('calcMethod').textContent = (method === 'flat') ? 'Flat Rate' : 'Reducing Balance';
        document.getElementById('calcFeePercent').textContent = feeRate.toFixed(2) + '%';

        const feeAmount = principal * (feeRate / 100);
        document.getElementById('calcFeeAmount').textContent = '$' + feeAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

        if (method === 'flat') {
            const interest = principal * (rate / 100);
            const totalPayable = principal + interest;
            document.getElementById('calcInterest').textContent = '$' + interest.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('calcTotal').textContent = '$' + totalPayable.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        } else {
            document.getElementById('calcInterest').textContent = 'Calculated at disbursement';
            document.getElementById('calcTotal').textContent = '$' + principal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' + Amortized Int.';
        }
    }

    productSelect.addEventListener('change', updateProductRulesAndCalculation);
    amountInput.addEventListener('input', updateProductRulesAndCalculation);
    termInput.addEventListener('input', updateProductRulesAndCalculation);

    updateProductRulesAndCalculation();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
