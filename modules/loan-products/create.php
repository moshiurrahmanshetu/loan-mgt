<?php
/**
 * Create Loan Product View
 * Loan Management System (loan-mgt) - Phase 3
 */

$pageTitle = 'Create Loan Product';
$activeNav = 'loan-products';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Authorization Check: Only Admin and Manager can configure loan products
if (!can_manage_loan_products()) {
    set_flash('danger', 'Unauthorized: Only Administrators and Loan Managers can create loan products.');
    redirect('modules/loan-products/index.php');
}

$db = get_db_connection();
$suggestedCode = generate_product_code($db);

$old = $_SESSION['_old_product_input'] ?? [];
unset($_SESSION['_old_product_input']);

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/loan-products/index.php'); ?>" class="text-decoration-none text-muted">Loan Products</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">New Product</li>
            </ol>
        </nav>
        <h2 class="h4 fw-bold text-dark mb-0">Create Loan Product Template</h2>
    </div>

    <div>
        <a href="<?php echo url('modules/loan-products/index.php'); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Products
        </a>
    </div>
</div>

<form action="<?php echo url('modules/loan-products/store.php'); ?>" method="POST" autocomplete="off">
    <?php echo csrf_field(); ?>

    <div class="row g-4">
        <!-- Main Form Column -->
        <div class="col-12 col-lg-8">
            <!-- 1. Product Identity -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-tag-fill me-2 text-primary"></i> 1. Product Identification</h3>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-4">
                            <label for="product_code" class="form-label">Product Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control font-monospace" id="product_code" name="product_code" value="<?php echo e($old['product_code'] ?? $suggestedCode); ?>" required maxlength="20">
                            <div class="form-text small">Unique code for this product rule.</div>
                        </div>
                        <div class="col-12 col-md-8">
                            <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo e($old['name'] ?? ''); ?>" placeholder="e.g. Small Business Growth Loan" required maxlength="100">
                        </div>
                    </div>

                    <div class="mb-0">
                        <label for="description" class="form-label">Product Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2" placeholder="Brief details regarding borrower eligibility and purpose..."><?php echo e($old['description'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- 2. Financial & Interest Parameters -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-percent me-2 text-primary"></i> 2. Financial & Interest Parameters</h3>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="minimum_amount" class="form-label">Minimum Principal Amount ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="minimum_amount" name="minimum_amount" value="<?php echo e($old['minimum_amount'] ?? '500.00'); ?>" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="maximum_amount" class="form-label">Maximum Principal Amount ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="maximum_amount" name="maximum_amount" value="<?php echo e($old['maximum_amount'] ?? '10000.00'); ?>" required>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="interest_rate" class="form-label">Interest Rate (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" class="form-control" id="interest_rate" name="interest_rate" value="<?php echo e($old['interest_rate'] ?? '12.00'); ?>" required>
                                <span class="input-group-text bg-light">%</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="interest_method" class="form-label">Interest Calculation Method <span class="text-danger">*</span></label>
                            <select class="form-select" id="interest_method" name="interest_method" required>
                                <option value="flat" <?php echo ($old['interest_method'] ?? 'flat') === 'flat' ? 'selected' : ''; ?>>Flat Rate (Total Principal %)</option>
                                <option value="reducing_balance" <?php echo ($old['interest_method'] ?? '') === 'reducing_balance' ? 'selected' : ''; ?>>Reducing Balance (Amortized)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Term Duration & Frequency -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-calendar3 me-2 text-primary"></i> 3. Term Duration & Repayment Schedule</h3>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-4">
                            <label for="term_min" class="form-label">Minimum Term <span class="text-danger">*</span></label>
                            <input type="number" min="1" class="form-control" id="term_min" name="term_min" value="<?php echo e($old['term_min'] ?? '1'); ?>" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="term_max" class="form-label">Maximum Term <span class="text-danger">*</span></label>
                            <input type="number" min="1" class="form-control" id="term_max" name="term_max" value="<?php echo e($old['term_max'] ?? '12'); ?>" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="term_unit" class="form-label">Term Unit <span class="text-danger">*</span></label>
                            <select class="form-select" id="term_unit" name="term_unit" required>
                                <option value="months" <?php echo ($old['term_unit'] ?? 'months') === 'months' ? 'selected' : ''; ?>>Months</option>
                                <option value="weeks" <?php echo ($old['term_unit'] ?? '') === 'weeks' ? 'selected' : ''; ?>>Weeks</option>
                                <option value="days" <?php echo ($old['term_unit'] ?? '') === 'days' ? 'selected' : ''; ?>>Days</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="repayment_frequency" class="form-label">Repayment Frequency <span class="text-danger">*</span></label>
                            <select class="form-select" id="repayment_frequency" name="repayment_frequency" required>
                                <option value="monthly" <?php echo ($old['repayment_frequency'] ?? 'monthly') === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                <option value="biweekly" <?php echo ($old['repayment_frequency'] ?? '') === 'biweekly' ? 'selected' : ''; ?>>Bi-Weekly (Every 2 Weeks)</option>
                                <option value="weekly" <?php echo ($old['repayment_frequency'] ?? '') === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                <option value="daily" <?php echo ($old['repayment_frequency'] ?? '') === 'daily' ? 'selected' : ''; ?>>Daily</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="processing_fee" class="form-label">Processing Fee Rate (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" class="form-control" id="processing_fee" name="processing_fee" value="<?php echo e($old['processing_fee'] ?? '1.50'); ?>" required>
                                <span class="input-group-text bg-light">%</span>
                            </div>
                            <div class="form-text small">Upfront administrative charge on loan principal.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar / Actions Column -->
        <div class="col-12 col-lg-4">
            <!-- Product Status Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-toggle-on me-2 text-primary"></i> Product Availability</h3>
                </div>
                <div class="card-body p-3">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="status" id="statusActive" value="active" <?php echo ($old['status'] ?? 'active') === 'active' ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-semibold text-success" for="statusActive">
                            <i class="bi bi-check-circle-fill me-1"></i> Active (Available for Loans)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="status" id="statusInactive" value="inactive" <?php echo ($old['status'] ?? '') === 'inactive' ? 'checked' : ''; ?>>
                        <label class="form-check-label text-muted" for="statusInactive">
                            <i class="bi bi-dash-circle me-1"></i> Inactive (Hidden from New Loans)
                        </label>
                    </div>
                </div>
            </div>

            <!-- Submit Action Card -->
            <div class="card shadow-sm mb-0">
                <div class="card-body p-3 d-grid gap-2">
                    <button type="submit" class="btn btn-primary py-2.5 fw-semibold">
                        <i class="bi bi-check-circle me-1"></i> Save Loan Product
                    </button>
                    <a href="<?php echo url('modules/loan-products/index.php'); ?>" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
