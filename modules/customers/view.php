<?php
/**
 * Customer Profile Details View
 * Loan Management System (loan-mgt) - Phase 2
 */

$pageTitle = 'Customer Details';
$activeNav = 'customers';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

$customerId = (int)($_GET['id'] ?? 0);
if ($customerId <= 0) {
    set_flash('danger', 'Invalid customer identifier specified.');
    redirect('modules/customers/index.php');
}

$db = get_db_connection();

$stmt = $db->prepare('
    SELECT c.*, 
           u.name AS creator_name, 
           u.role AS creator_role 
    FROM customers c 
    LEFT JOIN users u ON c.created_by = u.id 
    WHERE c.id = :id 
    LIMIT 1
');
$stmt->execute([':id' => $customerId]);
$customer = $stmt->fetch();

if (!$customer) {
    set_flash('danger', 'Customer record not found.');
    redirect('modules/customers/index.php');
}

$photoUrl = get_customer_photo_url($customer['photo'], $customer['full_name']);
$isActive = ($customer['status'] === 'active');
$statusBadge = $isActive ? 'badge-status-active' : 'badge-status-inactive';

// Fetch customer's loan applications
$loanStmt = $db->prepare('
    SELECT l.*, lp.name AS product_name 
    FROM loans l 
    LEFT JOIN loan_products lp ON l.loan_product_id = lp.id 
    WHERE l.customer_id = :cid 
    ORDER BY l.id DESC
');
$loanStmt->execute([':cid' => $customerId]);
$customerLoans = $loanStmt->fetchAll();

// Compute age if DOB is present
$ageDisplay = 'N/A';
if (!empty($customer['date_of_birth'])) {
    try {
        $dobDate = new DateTime($customer['date_of_birth']);
        $today = new DateTime();
        $age = $today->diff($dobDate)->y;
        $ageDisplay = date('F j, Y', strtotime($customer['date_of_birth'])) . ' (' . $age . ' years old)';
    } catch (Exception $e) {
        $ageDisplay = date('F j, Y', strtotime($customer['date_of_birth']));
    }
}

$formattedIncome = format_currency($customer['monthly_income']);
$createdDate = date('F j, Y, g:i a', strtotime($customer['created_at']));
$updatedDate = date('F j, Y, g:i a', strtotime($customer['updated_at']));

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/customers/index.php'); ?>" class="text-decoration-none text-muted">Customers</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page"><?php echo e($customer['customer_code']); ?></li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h2 class="h4 fw-bold text-dark mb-0"><?php echo e($customer['full_name']); ?></h2>
            <span class="badge <?php echo $statusBadge; ?>"><?php echo e(ucfirst($customer['status'])); ?></span>
        </div>
    </div>

    <!-- Action Toolbar -->
    <div class="d-flex flex-wrap gap-2">
        <a href="<?php echo url('modules/customers/index.php'); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Customer List
        </a>

        <?php if (can_manage_customers()): ?>
            <a href="<?php echo url('modules/customers/edit.php?id=' . $customer['id']); ?>" class="btn btn-outline-secondary">
                <i class="bi bi-pencil me-1"></i> Edit Profile
            </a>
        <?php endif; ?>

        <?php if (can_toggle_customer_status()): ?>
            <form action="<?php echo url('modules/customers/toggle-status.php'); ?>" method="POST" class="d-inline" onsubmit="return confirm('Change status to <?php echo $isActive ? 'Inactive' : 'Active'; ?>?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo (int)$customer['id']; ?>">
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="bi <?php echo $isActive ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted'; ?> me-1"></i>
                    <?php echo $isActive ? 'Deactivate' : 'Activate'; ?>
                </button>
            </form>
        <?php endif; ?>

        <?php if (can_delete_customers()): ?>
            <form action="<?php echo url('modules/customers/delete.php'); ?>" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete customer <?php echo e(addslashes($customer['full_name'])); ?>? This action cannot be undone.');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo (int)$customer['id']; ?>">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash me-1"></i> Delete
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Profile Card & Audit Meta -->
    <div class="col-12 col-lg-4">
        <!-- Main Customer Identity Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-body text-center p-4">
                <div class="position-relative d-inline-block mb-3">
                    <img src="<?php echo e($photoUrl); ?>" alt="<?php echo e($customer['full_name']); ?>" class="avatar-img-lg shadow-sm" style="width: 120px; height: 120px;">
                </div>
                <h3 class="h5 fw-bold text-dark mb-1"><?php echo e($customer['full_name']); ?></h3>
                <div class="mb-2">
                    <span class="badge bg-light text-dark border font-monospace px-2 py-1"><?php echo e($customer['customer_code']); ?></span>
                </div>
                <div class="text-muted small mb-0">
                    <i class="bi bi-briefcase me-1"></i> <?php echo !empty($customer['occupation']) ? e($customer['occupation']) : 'Occupation not specified'; ?>
                </div>
            </div>

            <div class="card-footer bg-light border-top p-3 small text-muted">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Monthly Income:</span>
                    <span class="fw-bold text-success fs-6"><?php echo e($formattedIncome); ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span>Account Status:</span>
                    <span class="badge <?php echo $statusBadge; ?>"><?php echo e(ucfirst($customer['status'])); ?></span>
                </div>
            </div>
        </div>

        <!-- System Audit & Registration Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h4 class="h6 mb-0 fw-bold"><i class="bi bi-info-circle me-2 text-primary"></i> System Registration Details</h4>
            </div>
            <div class="card-body p-3 small">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Registered By:</span>
                    <span class="fw-semibold text-dark">
                        <?php echo !empty($customer['creator_name']) ? e($customer['creator_name']) . ' (' . e(get_role_label($customer['creator_role'] ?? '')) . ')' : '<span class="text-muted">System</span>'; ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Registration Date:</span>
                    <span class="text-dark"><?php echo e($createdDate); ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Last Updated:</span>
                    <span class="text-dark"><?php echo e($updatedDate); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Profile Detail Cards & Future Loan Area -->
    <div class="col-12 col-lg-8">
        <!-- 1. Personal & Contact Information -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h4 class="h6 mb-0 fw-bold"><i class="bi bi-person-lines-fill me-2 text-primary"></i> Personal & Contact Information</h4>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12 col-sm-6">
                        <span class="text-muted small d-block">Full Legal Name</span>
                        <span class="fw-semibold text-dark"><?php echo e($customer['full_name']); ?></span>
                    </div>
                    <div class="col-12 col-sm-6">
                        <span class="text-muted small d-block">Customer Code</span>
                        <span class="fw-semibold font-monospace text-primary"><?php echo e($customer['customer_code']); ?></span>
                    </div>
                    <div class="col-12 col-sm-6">
                        <span class="text-muted small d-block">Primary Telephone</span>
                        <span class="fw-semibold text-dark"><i class="bi bi-telephone me-1 text-muted"></i> <?php echo e($customer['phone']); ?></span>
                    </div>
                    <div class="col-12 col-sm-6">
                        <span class="text-muted small d-block">Email Address</span>
                        <span class="fw-semibold text-dark">
                            <?php echo !empty($customer['email']) ? '<i class="bi bi-envelope me-1 text-muted"></i> ' . e($customer['email']) : '<span class="text-muted fst-italic">Not provided</span>'; ?>
                        </span>
                    </div>
                    <div class="col-12 col-sm-6">
                        <span class="text-muted small d-block">Date of Birth & Age</span>
                        <span class="fw-semibold text-dark"><?php echo e($ageDisplay); ?></span>
                    </div>
                    <div class="col-12 col-sm-6">
                        <span class="text-muted small d-block">Gender</span>
                        <span class="fw-semibold text-dark text-capitalize"><?php echo !empty($customer['gender']) ? e($customer['gender']) : '<span class="text-muted fst-italic">Not specified</span>'; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Address & Financial Profile -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h4 class="h6 mb-0 fw-bold"><i class="bi bi-building me-2 text-primary"></i> Address & Financial Profile</h4>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12 col-sm-8">
                        <span class="text-muted small d-block">Street Address</span>
                        <span class="fw-semibold text-dark"><?php echo !empty($customer['address']) ? e($customer['address']) : '<span class="text-muted fst-italic">Not provided</span>'; ?></span>
                    </div>
                    <div class="col-12 col-sm-4">
                        <span class="text-muted small d-block">City / Municipality</span>
                        <span class="fw-semibold text-dark"><?php echo !empty($customer['city']) ? e($customer['city']) : '<span class="text-muted fst-italic">Not provided</span>'; ?></span>
                    </div>
                    <div class="col-12 col-sm-6">
                        <span class="text-muted small d-block">Occupation / Business</span>
                        <span class="fw-semibold text-dark"><?php echo !empty($customer['occupation']) ? e($customer['occupation']) : '<span class="text-muted fst-italic">Not specified</span>'; ?></span>
                    </div>
                    <div class="col-12 col-sm-6">
                        <span class="text-muted small d-block">Declared Monthly Income</span>
                        <span class="fw-bold text-success fs-6"><?php echo e($formattedIncome); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Emergency Contact / Guarantor -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h4 class="h6 mb-0 fw-bold"><i class="bi bi-shield-check me-2 text-primary"></i> Emergency Contact / Guarantor</h4>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12 col-sm-6">
                        <span class="text-muted small d-block">Contact Person Name</span>
                        <span class="fw-semibold text-dark"><?php echo !empty($customer['emergency_contact_name']) ? e($customer['emergency_contact_name']) : '<span class="text-muted fst-italic">No emergency contact provided</span>'; ?></span>
                    </div>
                    <div class="col-12 col-sm-6">
                        <span class="text-muted small d-block">Contact Person Phone</span>
                        <span class="fw-semibold text-dark">
                            <?php echo !empty($customer['emergency_contact_phone']) ? '<i class="bi bi-telephone me-1 text-muted"></i> ' . e($customer['emergency_contact_phone']) : '<span class="text-muted fst-italic">—</span>'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Loan Portfolio & History -->
        <div class="card shadow-sm mb-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h4 class="h6 mb-0 fw-bold"><i class="bi bi-cash-stack me-2 text-primary"></i> Loan Applications & History</h4>
                <?php if ($isActive && can_create_loans()): ?>
                    <a href="<?php echo url('modules/loans/create.php?customer_id=' . $customer['id']); ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> New Application
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($customerLoans)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-3 py-2.5">Loan #</th>
                                    <th class="py-2.5">Product</th>
                                    <th class="py-2.5 text-end">Amount</th>
                                    <th class="py-2.5">Term</th>
                                    <th class="py-2.5 text-center">Status</th>
                                    <th class="pe-3 py-2.5 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customerLoans as $cl): ?>
                                    <tr>
                                        <td class="ps-3 font-monospace fw-semibold">
                                            <a href="<?php echo url('modules/loans/view.php?id=' . $cl['id']); ?>" class="text-decoration-none">
                                                <?php echo e($cl['loan_number']); ?>
                                            </a>
                                        </td>
                                        <td class="small fw-semibold text-dark">
                                            <?php echo e($cl['product_name'] ?? 'Product'); ?>
                                        </td>
                                        <td class="text-end small fw-bold text-dark">
                                            <?php echo format_currency($cl['requested_amount']); ?>
                                        </td>
                                        <td class="small text-muted">
                                            <?php echo (int)$cl['term'] . ' ' . ucfirst($cl['term_unit']); ?>
                                        </td>
                                        <td class="text-center">
                                            <?php echo get_loan_status_badge($cl['status']); ?>
                                        </td>
                                        <td class="pe-3 text-end">
                                            <a href="<?php echo url('modules/loans/view.php?id=' . $cl['id']); ?>" class="btn btn-sm btn-outline-secondary" title="View Loan Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center">
                        <i class="bi bi-journal-bookmark text-muted display-6 d-block mb-2"></i>
                        <h5 class="fw-semibold text-dark h6">No Loan Applications Originated</h5>
                        <p class="text-muted small mb-3 max-w-md mx-auto">
                            This borrower has not applied for any loan products yet.
                        </p>
                        <?php if ($isActive && can_create_loans()): ?>
                            <a href="<?php echo url('modules/loans/create.php?customer_id=' . $customer['id']); ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-circle me-1"></i> Originate First Loan
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
