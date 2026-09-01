<?php
/**
 * Edit Loan Product View
 * Loan Management System (loan-mgt) - Phase 3
 */

$pageTitle = 'Edit Loan Product';
$activeNav = 'loan-products';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Authorization Check: Only Admin and Manager can edit loan products
if (!can_manage_loan_products()) {
    set_flash('danger', 'Unauthorized: Only Administrators and Loan Managers can modify loan products.');
    redirect('modules/loan-products/index.php');
}

$productId = (int)($_GET['id'] ?? 0);
if ($productId <= 0) {
    set_flash('danger', 'Invalid loan product specified.');
    redirect('modules/loan-products/index.php');
}

$db = get_db_connection();

$stmt = $db->prepare('SELECT * FROM loan_products WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('danger', 'Loan product record not found.');
    redirect('modules/loan-products/index.php');
}

$old = $_SESSION['_old_product_input'] ?? [];
unset($_SESSION['_old_product_input']);

$data = !empty($old) ? $old : $product;

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/loan-products/index.php'); ?>" class="text-decoration-none text-muted">Loan Products</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/loan-products/view.php?id=' . $product['id']); ?>" class="text-decoration-none text-muted"><?php echo e($product['product_code']); ?></a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Edit Product</li>
            </ol>
        </nav>
        <h2 class="h4 fw-bold text-dark mb-0">Edit Product: <?php echo e($product['name']); ?></h2>
    </div>

    <div class="d-flex gap-2">
        <a href="<?php echo url('modules/loan-products/view.php?id=' . $product['id']); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-eye me-1"></i> View Details
        </a>
        <a href="<?php echo url('modules/loan-products/index.php'); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Products
        </a>
    </div>
</div>

<form action="<?php echo url('modules/loan-products/update.php'); ?>" method="POST" autocomplete="off">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="id" value="<?php echo (int)$product['id']; ?>">

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
                            <label class="form-label">Product Code (Fixed)</label>
                            <input type="text" class="form-control bg-light font-monospace" value="<?php echo e($product['product_code']); ?>" readonly disabled>
                        </div>
                        <div class="col-12 col-md-8">
                            <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo e($data['name'] ?? ''); ?>" required maxlength="100">
                        </div>
                    </div>

                    <div class="mb-0">
                        <label for="description" class="form-label">Product Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2"><?php echo e($data['description'] ?? ''); ?></textarea>
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
                            <input type="number" step="0.01" min="0" class="form-control" id="minimum_amount" name="minimum_amount" value="<?php echo e($data['minimum_amount'] ?? '500.00'); ?>" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="maximum_amount" class="form-label">Maximum Principal Amount ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="maximum_amount" name="maximum_amount" value="<?php echo e($data['maximum_amount'] ?? '10000.00'); ?>" required>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="interest_rate" class="form-label">Interest Rate (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" class="form-control" id="interest_rate" name="interest_rate" value="<?php echo e($data['interest_rate'] ?? '12.00'); ?>" required>
                                <span class="input-group-text bg-light">%</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="interest_method" class="form-label">Interest Calculation Method <span class="text-danger">*</span></label>
                            <select class="form-select" id="interest_method" name="interest_method" required>
                                <option value="flat" <?php echo ($data['interest_method'] ?? 'flat') === 'flat' ? 'selected' : ''; ?>>Flat Rate (Total Principal %)</option>
                                <option value="reducing_balance" <?php echo ($data['interest_method'] ?? '') === 'reducing_balance' ? 'selected' : ''; ?>>Reducing Balance (Amortized)</option>
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
                            <input type="number" min="1" class="form-control" id="term_min" name="term_min" value="<?php echo e($data['term_min'] ?? '1'); ?>" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="term_max" class="form-label">Maximum Term <span class="text-danger">*</span></label>
                            <input type="number" min="1" class="form-control" id="term_max" name="term_max" value="<?php echo e($data['term_max'] ?? '12'); ?>" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="term_unit" class="form-label">Term Unit <span class="text-danger">*</span></label>
                            <select class="form-select" id="term_unit" name="term_unit" required>
                                <option value="months" <?php echo ($data['term_unit'] ?? 'months') === 'months' ? 'selected' : ''; ?>>Months</option>
                                <option value="weeks" <?php echo ($data['term_unit'] ?? '') === 'weeks' ? 'selected' : ''; ?>>Weeks</option>
                                <option value="days" <?php echo ($data['term_unit'] ?? '') === 'days' ? 'selected' : ''; ?>>Days</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="repayment_frequency" class="form-label">Repayment Frequency <span class="text-danger">*</span></label>
                            <select class="form-select" id="repayment_frequency" name="repayment_frequency" required>
                                <option value="monthly" <?php echo ($data['repayment_frequency'] ?? 'monthly') === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                <option value="biweekly" <?php echo ($data['repayment_frequency'] ?? '') === 'biweekly' ? 'selected' : ''; ?>>Bi-Weekly (Every 2 Weeks)</option>
                                <option value="weekly" <?php echo ($data['repayment_frequency'] ?? '') === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                <option value="daily" <?php echo ($data['repayment_frequency'] ?? '') === 'daily' ? 'selected' : ''; ?>>Daily</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="processing_fee" class="form-label">Processing Fee Rate (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" class="form-control" id="processing_fee" name="processing_fee" value="<?php echo e($data['processing_fee'] ?? '1.50'); ?>" required>
                                <span class="input-group-text bg-light">%</span>
                            </div>
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
                        <input class="form-check-input" type="radio" name="status" id="statusActive" value="active" <?php echo ($data['status'] ?? 'active') === 'active' ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-semibold text-success" for="statusActive">
                            <i class="bi bi-check-circle-fill me-1"></i> Active (Available for Loans)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="status" id="statusInactive" value="inactive" <?php echo ($data['status'] ?? '') === 'inactive' ? 'checked' : ''; ?>>
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
                        <i class="bi bi-check-lg me-1"></i> Save Changes
                    </button>
                    <a href="<?php echo url('modules/loan-products/view.php?id=' . $product['id']); ?>" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
