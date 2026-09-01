<?php
/**
 * Loan Product View
 * Loan Management System (loan-mgt) - Phase 3
 */

$pageTitle = 'Loan Product Details';
$activeNav = 'loan-products';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

$productId = (int)($_GET['id'] ?? 0);
if ($productId <= 0) {
    set_flash('danger', 'Invalid loan product specified.');
    redirect('modules/loan-products/index.php');
}

$db = get_db_connection();

$stmt = $db->prepare('
    SELECT lp.*, 
           u.name AS creator_name, 
           u.role AS creator_role 
    FROM loan_products lp 
    LEFT JOIN users u ON lp.created_by = u.id 
    WHERE lp.id = :id 
    LIMIT 1
');
$stmt->execute([':id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('danger', 'Loan product record not found.');
    redirect('modules/loan-products/index.php');
}

// Fetch linked loan statistics
$statStmt = $db->prepare('
    SELECT 
        COUNT(*) AS total_applications,
        COALESCE(SUM(requested_amount), 0) AS total_volume,
        COUNT(CASE WHEN status = "approved" THEN 1 END) AS approved_count,
        COUNT(CASE WHEN status = "pending" THEN 1 END) AS pending_count
    FROM loans 
    WHERE loan_product_id = :id
');
$statStmt->execute([':id' => $productId]);
$stats = $statStmt->fetch();

$isActive = ($product['status'] === 'active');
$statusBadge = $isActive ? 'badge-status-active' : 'badge-status-inactive';
$methodLabel = get_interest_method_label($product['interest_method']);
$freqLabel = get_frequency_label($product['repayment_frequency']);
$termDisplay = $product['term_min'] . ' – ' . $product['term_max'] . ' ' . ucfirst($product['term_unit']);
$createdDate = date('F j, Y, g:i a', strtotime($product['created_at']));
$updatedDate = date('F j, Y, g:i a', strtotime($product['updated_at']));

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/loan-products/index.php'); ?>" class="text-decoration-none text-muted">Loan Products</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page"><?php echo e($product['product_code']); ?></li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h2 class="h4 fw-bold text-dark mb-0"><?php echo e($product['name']); ?></h2>
            <span class="badge <?php echo $statusBadge; ?>"><?php echo e(ucfirst($product['status'])); ?></span>
        </div>
    </div>

    <!-- Action Toolbar -->
    <div class="d-flex flex-wrap gap-2">
        <a href="<?php echo url('modules/loan-products/index.php'); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Products Catalog
        </a>

        <?php if (can_manage_loan_products()): ?>
            <a href="<?php echo url('modules/loan-products/edit.php?id=' . $product['id']); ?>" class="btn btn-outline-secondary">
                <i class="bi bi-pencil me-1"></i> Edit Product
            </a>

            <form action="<?php echo url('modules/loan-products/toggle-status.php'); ?>" method="POST" class="d-inline" onsubmit="return confirm('Change status to <?php echo $isActive ? 'Inactive' : 'Active'; ?>?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo (int)$product['id']; ?>">
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="bi <?php echo $isActive ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted'; ?> me-1"></i>
                    <?php echo $isActive ? 'Deactivate' : 'Activate'; ?>
                </button>
            </form>

            <?php if (has_role('admin') && (int)$stats['total_applications'] === 0): ?>
                <form action="<?php echo url('modules/loan-products/delete.php'); ?>" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete loan product <?php echo e(addslashes($product['name'])); ?>?');">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo (int)$product['id']; ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i> Delete
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Product Summary & Meta -->
    <div class="col-12 col-lg-4">
        <!-- Identity Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h3 class="h6 mb-0 fw-bold"><i class="bi bi-tag-fill me-2 text-primary"></i> Product Profile</h3>
            </div>
            <div class="card-body p-4 text-center">
                <div class="brand-icon mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.75rem;">
                    <i class="bi bi-tags"></i>
                </div>
                <h3 class="h5 fw-bold text-dark mb-1"><?php echo e($product['name']); ?></h3>
                <span class="badge bg-light text-dark border font-monospace px-2.5 py-1.5 mb-3"><?php echo e($product['product_code']); ?></span>

                <div class="text-muted small text-start border-top pt-3">
                    <?php echo !empty($product['description']) ? nl2br(e($product['description'])) : '<span class="fst-italic">No additional product description provided.</span>'; ?>
                </div>
            </div>
        </div>

        <!-- System Audit Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h4 class="h6 mb-0 fw-bold"><i class="bi bi-info-circle me-2 text-primary"></i> Template Audit Info</h4>
            </div>
            <div class="card-body p-3 small">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Created By:</span>
                    <span class="fw-semibold text-dark">
                        <?php echo !empty($product['creator_name']) ? e($product['creator_name']) . ' (' . e(get_role_label($product['creator_role'] ?? '')) . ')' : 'System'; ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Creation Date:</span>
                    <span class="text-dark"><?php echo e($createdDate); ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Last Updated:</span>
                    <span class="text-dark"><?php echo e($updatedDate); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Lending Rules & Portfolio Metrics -->
    <div class="col-12 col-lg-8">
        <!-- 1. Lending Rules & Parameters -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h4 class="h6 mb-0 fw-bold"><i class="bi bi-sliders me-2 text-primary"></i> Lending Parameters & Policy Rules</h4>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12 col-sm-6">
                        <span class="text-muted small d-block">Principal Amount Limits</span>
                        <span class="fw-bold text-dark fs-6">
                            <?php echo format_currency($product['minimum_amount']); ?> – <?php echo format_currency($product['maximum_amount']); ?>
                        </span>
                    </div>

                    <div class="col-12 col-sm-6">
                        <span class="text-muted small d-block">Nominal Interest Rate</span>
                        <span class="fw-bold text-primary fs-6"><?php echo number_format($product['interest_rate'], 2); ?>%</span>
                    </div>

                    <div class="col-12 col-sm-6">
                        <span class="text-muted small d-block">Interest Calculation Method</span>
                        <span class="fw-semibold text-dark"><?php echo e($methodLabel); ?></span>
                    </div>

                    <div class="col-12 col-sm-6">
                        <span class="text-muted small d-block">Repayment Frequency</span>
                        <span class="fw-semibold text-dark"><?php echo e($freqLabel); ?></span>
                    </div>

                    <div class="col-12 col-sm-6">
                        <span class="text-muted small d-block">Permitted Term Duration</span>
                        <span class="fw-semibold text-dark"><?php echo e($termDisplay); ?></span>
                    </div>

                    <div class="col-12 col-sm-6">
                        <span class="text-muted small d-block">Processing Fee Rate</span>
                        <span class="fw-semibold text-dark"><?php echo number_format($product['processing_fee'], 2); ?>%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Linked Loan Applications Summary -->
        <div class="card shadow-sm mb-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h4 class="h6 mb-0 fw-bold"><i class="bi bi-bar-chart-fill me-2 text-primary"></i> Portfolio Utilization</h4>
                <a href="<?php echo url('modules/loans/index.php'); ?>" class="small text-decoration-none">View All Loans &rarr;</a>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 text-center">
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded">
                            <div class="h5 fw-bold text-dark mb-0"><?php echo number_format((int)$stats['total_applications']); ?></div>
                            <span class="text-muted small">Total Applications</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded">
                            <div class="h5 fw-bold text-success mb-0"><?php echo format_currency($stats['total_volume']); ?></div>
                            <span class="text-muted small">Total Volume</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded">
                            <div class="h5 fw-bold text-success mb-0"><?php echo number_format((int)$stats['approved_count']); ?></div>
                            <span class="text-muted small">Approved Loans</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded">
                            <div class="h5 fw-bold text-warning mb-0"><?php echo number_format((int)$stats['pending_count']); ?></div>
                            <span class="text-muted small">Pending Review</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
