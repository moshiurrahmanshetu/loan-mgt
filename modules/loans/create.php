<?php
/**
 * Create Loan Application View
 * Loan Management System (loan-mgt) - Phase 3
 */

$pageTitle = 'New Loan Application';
$activeNav = 'loans';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Authorization Guard: Only Admin, Manager, Loan Officer can create loan applications
if (!can_create_loans()) {
    set_flash('danger', 'Unauthorized: You do not have permissions to originate loan applications.');
    redirect('modules/loans/index.php');
}

$db = get_db_connection();

// 1. Fetch active customers only
$custStmt = $db->query('SELECT id, customer_code, full_name, phone, monthly_income, occupation FROM customers WHERE status = "active" ORDER BY full_name ASC');
$customers = $custStmt->fetchAll();

// 2. Fetch active loan products only
$prodStmt = $db->query('SELECT * FROM loan_products WHERE status = "active" ORDER BY name ASC');
$products = $prodStmt->fetchAll();

$preselectedCustomerId = (int)($_GET['customer_id'] ?? 0);
$preselectedProductId  = (int)($_GET['product_id'] ?? 0);

$old = $_SESSION['_old_loan_input'] ?? [];
unset($_SESSION['_old_loan_input']);

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/loans/index.php'); ?>" class="text-decoration-none text-muted">Loan Applications</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">New Application</li>
            </ol>
        </nav>
        <h2 class="h4 fw-bold text-dark mb-0">Originate Loan Application</h2>
    </div>

    <div>
        <a href="<?php echo url('modules/loans/index.php'); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Portfolio
        </a>
    </div>
</div>

<?php if (empty($customers)): ?>
    <div class="alert alert-warning d-flex align-items-center mb-4">
        <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
        <div>
            <strong>No active customers found.</strong> You must have at least one active customer in the system before originating a loan.
            <a href="<?php echo url('modules/customers/create.php'); ?>" class="alert-link ms-2">Register a Customer &rarr;</a>
        </div>
    </div>
<?php endif; ?>

<?php if (empty($products)): ?>
    <div class="alert alert-warning d-flex align-items-center mb-4">
        <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
        <div>
            <strong>No active loan products available.</strong> You must create and activate at least one loan product template first.
            <?php if (can_manage_loan_products()): ?>
                <a href="<?php echo url('modules/loan-products/create.php'); ?>" class="alert-link ms-2">Create Loan Product &rarr;</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<form action="<?php echo url('modules/loans/store.php'); ?>" method="POST" id="loanApplicationForm" autocomplete="off">
    <?php echo csrf_field(); ?>

    <div class="row g-4">
        <!-- Main Application Column -->
        <div class="col-12 col-lg-8">
            <!-- 1. Customer Selection -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-person-check-fill me-2 text-primary"></i> 1. Select Active Borrower</h3>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label for="customer_id" class="form-label">Borrower / Customer <span class="text-danger">*</span></label>
                        <select class="form-select" id="customer_id" name="customer_id" required>
                            <option value="">-- Choose an active customer --</option>
                            <?php foreach ($customers as $cust): ?>
                                <?php 
                                    $selected = ((int)($old['customer_id'] ?? $preselectedCustomerId) === (int)$cust['id']) ? 'selected' : '';
                                    $incomeDisplay = format_currency($cust['monthly_income']);
                                ?>
                                <option value="<?php echo (int)$cust['id']; ?>" <?php echo $selected; ?>
                                        data-name="<?php echo e($cust['full_name']); ?>"
                                        data-code="<?php echo e($cust['customer_code']); ?>"
                                        data-phone="<?php echo e($cust['phone']); ?>"
                                        data-income="<?php echo e($incomeDisplay); ?>"
                                        data-occupation="<?php echo e($cust['occupation'] ?? 'N/A'); ?>">
                                    <?php echo e($cust['customer_code']); ?> — <?php echo e($cust['full_name']); ?> (<?php echo e($cust['phone']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text small">Only active borrowers can apply for new credit facilities.</div>
                    </div>

                    <!-- Customer Detail Callout -->
                    <div id="customerSummaryBox" class="p-3 bg-light rounded border d-none">
                        <div class="row g-2 small">
                            <div class="col-6 col-md-3">
                                <span class="text-muted d-block">Customer Code</span>
                                <strong id="cBoxCode" class="font-monospace text-dark">-</strong>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="text-muted d-block">Full Name</span>
                                <strong id="cBoxName" class="text-dark">-</strong>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="text-muted d-block">Occupation</span>
                                <strong id="cBoxOccupation" class="text-dark">-</strong>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="text-muted d-block">Monthly Income</span>
                                <strong id="cBoxIncome" class="text-success">-</strong>
                            </div>
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
                            <option value="">-- Choose a loan product template --</option>
                            <?php foreach ($products as $prod): ?>
                                <?php 
                                    $selected = ((int)($old['loan_product_id'] ?? $preselectedProductId) === (int)$prod['id']) ? 'selected' : '';
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
                    <div id="productRulesCard" class="p-3 bg-light rounded border mb-0 d-none">
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
                                <input type="number" step="0.01" min="1" class="form-control" id="requested_amount" name="requested_amount" value="<?php echo e($old['requested_amount'] ?? ''); ?>" placeholder="0.00" required>
                            </div>
                            <div class="form-text small" id="amountHelp">Enter an amount within product limits.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="term" class="form-label">Loan Term <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" min="1" class="form-control" id="term" name="term" value="<?php echo e($old['term'] ?? ''); ?>" placeholder="Duration" required>
                                <span class="input-group-text bg-light" id="termUnitLabel">Months</span>
                            </div>
                            <div class="form-text small" id="termHelp">Select term within product duration range.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="application_date" class="form-label">Application Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="application_date" name="application_date" value="<?php echo e($old['application_date'] ?? date('Y-m-d')); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="purpose" class="form-label">Loan Purpose</label>
                        <textarea class="form-control" id="purpose" name="purpose" rows="2" placeholder="e.g. Working capital for retail inventory, home renovation, education..."><?php echo e($old['purpose'] ?? ''); ?></textarea>
                    </div>

                    <div class="mb-0">
                        <label for="notes" class="form-label">Internal Underwriting Notes / Comments</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Guarantor details, credit check remarks, or collateral notes..."><?php echo e($old['notes'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right / Calculation Preview Column -->
        <div class="col-12 col-lg-4">
            <!-- Calculation Preview Card -->
            <div class="card shadow-sm mb-4 border-primary">
                <div class="card-header bg-primary text-white py-3">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-calculator me-2"></i> Estimated Loan Breakdown</h3>
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
                        <i class="bi bi-info-circle me-1"></i> Processing fee is charged upfront and excluded from repayment total. All calculations are preliminary estimates.
                    </div>
                </div>
            </div>

            <!-- Submit Action Card -->
            <div class="card shadow-sm mb-0">
                <div class="card-header bg-white py-3">
                    <h4 class="h6 mb-0 fw-bold"><i class="bi bi-send me-2 text-primary"></i> Application Submission</h4>
                </div>
                <div class="card-body p-3 d-grid gap-2">
                    <button type="submit" name="action" value="submit" class="btn btn-primary py-2.5 fw-semibold">
                        <i class="bi bi-send-fill me-1"></i> Submit for Approval (Pending)
                    </button>
                    <button type="submit" name="action" value="draft" class="btn btn-outline-secondary py-2">
                        <i class="bi bi-file-earmark me-1"></i> Save as Draft
                    </button>
                    <a href="<?php echo url('modules/loans/index.php'); ?>" class="btn btn-link text-muted btn-sm text-decoration-none">
                        Cancel & Return
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const customerSelect = document.getElementById('customer_id');
    const productSelect  = document.getElementById('loan_product_id');
    const amountInput    = document.getElementById('requested_amount');
    const termInput      = document.getElementById('term');
    const termUnitLabel  = document.getElementById('termUnitLabel');
    const amountHelp     = document.getElementById('amountHelp');
    const termHelp       = document.getElementById('termHelp');

    const customerSummaryBox = document.getElementById('customerSummaryBox');
    const productRulesCard   = document.getElementById('productRulesCard');

    function updateCustomerSummary() {
        const selected = customerSelect.options[customerSelect.selectedIndex];
        if (!selected || !selected.value) {
            customerSummaryBox.classList.add('d-none');
            return;
        }
        document.getElementById('cBoxCode').textContent = selected.getAttribute('data-code') || '-';
        document.getElementById('cBoxName').textContent = selected.getAttribute('data-name') || '-';
        document.getElementById('cBoxOccupation').textContent = selected.getAttribute('data-occupation') || '-';
        document.getElementById('cBoxIncome').textContent = selected.getAttribute('data-income') || '-';
        customerSummaryBox.classList.remove('d-none');
    }

    function updateProductRulesAndCalculation() {
        const selected = productSelect.options[productSelect.selectedIndex];
        if (!selected || !selected.value) {
            productRulesCard.classList.add('d-none');
            termUnitLabel.textContent = 'Months';
            resetCalculation();
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

        // Display Rule Box
        document.getElementById('pBoxAmountRange').textContent = '$' + minAmt.toLocaleString(undefined, {minimumFractionDigits: 2}) + ' – $' + maxAmt.toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('pBoxRate').textContent = rate.toFixed(2) + '% (' + (method === 'flat' ? 'Flat' : 'Reducing') + ')';
        document.getElementById('pBoxTermRange').textContent = termMin + ' – ' + termMax + ' ' + unit.charAt(0).toUpperCase() + unit.slice(1);
        document.getElementById('pBoxFrequencyFee').textContent = freq.charAt(0).toUpperCase() + freq.slice(1) + ' / ' + feeRate.toFixed(2) + '% Fee';
        productRulesCard.classList.remove('d-none');

        termUnitLabel.textContent = unit.charAt(0).toUpperCase() + unit.slice(1);
        amountHelp.textContent = 'Allowed Range: $' + minAmt.toLocaleString() + ' to $' + maxAmt.toLocaleString();
        termHelp.textContent = 'Allowed Duration: ' + termMin + ' to ' + termMax + ' ' + unit;

        // Perform Preview Calculations
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

    function resetCalculation() {
        document.getElementById('calcPrincipal').textContent = '$0.00';
        document.getElementById('calcRate').textContent = '0.00%';
        document.getElementById('calcMethod').textContent = '-';
        document.getElementById('calcInterest').textContent = '$0.00';
        document.getElementById('calcFeePercent').textContent = '0%';
        document.getElementById('calcFeeAmount').textContent = '$0.00';
        document.getElementById('calcTotal').textContent = '$0.00';
    }

    customerSelect.addEventListener('change', updateCustomerSummary);
    productSelect.addEventListener('change', updateProductRulesAndCalculation);
    amountInput.addEventListener('input', updateProductRulesAndCalculation);
    termInput.addEventListener('input', updateProductRulesAndCalculation);

    // Initial triggers if pre-selected
    updateCustomerSummary();
    updateProductRulesAndCalculation();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
