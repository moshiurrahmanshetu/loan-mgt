<?php
/**
 * Edit Customer View
 * Loan Management System (loan-mgt) - Phase 2
 */

$pageTitle = 'Edit Customer';
$activeNav = 'customers';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Authorization Check: Only Admin, Manager, and Loan Officer can edit customers
if (!can_manage_customers()) {
    set_flash('danger', 'Unauthorized: You do not have permissions to edit customer profiles.');
    redirect('modules/customers/index.php');
}

$customerId = (int)($_GET['id'] ?? 0);
if ($customerId <= 0) {
    set_flash('danger', 'Invalid customer specified.');
    redirect('modules/customers/index.php');
}

$db = get_db_connection();

$stmt = $db->prepare('SELECT * FROM customers WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $customerId]);
$customer = $stmt->fetch();

if (!$customer) {
    set_flash('danger', 'Customer record not found.');
    redirect('modules/customers/index.php');
}

// Check for old input flashed after validation error
$old = $_SESSION['_old_customer_input'] ?? [];
unset($_SESSION['_old_customer_input']);

// Use old values if present, else fallback to database record
$data = !empty($old) ? $old : $customer;
$photoUrl = get_customer_photo_url($customer['photo'], $customer['full_name']);

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/customers/index.php'); ?>" class="text-decoration-none text-muted">Customers</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/customers/view.php?id=' . $customer['id']); ?>" class="text-decoration-none text-muted"><?php echo e($customer['customer_code']); ?></a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Edit Profile</li>
            </ol>
        </nav>
        <h2 class="h4 fw-bold text-dark mb-0">Edit Customer: <?php echo e($customer['full_name']); ?></h2>
    </div>

    <div class="d-flex gap-2">
        <a href="<?php echo url('modules/customers/view.php?id=' . $customer['id']); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-eye me-1"></i> View Details
        </a>
        <a href="<?php echo url('modules/customers/index.php'); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>
</div>

<form action="<?php echo url('modules/customers/update.php'); ?>" method="POST" enctype="multipart/form-data" autocomplete="off">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="id" value="<?php echo (int)$customer['id']; ?>">

    <div class="row g-4">
        <!-- Left Column: Form Details -->
        <div class="col-12 col-lg-8">
            <!-- 1. Personal & Contact Information -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-person-fill me-2 text-primary"></i> 1. Personal & Contact Information</h3>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Customer Code (Fixed)</label>
                            <input type="text" class="form-control bg-light font-monospace" value="<?php echo e($customer['customer_code']); ?>" readonly disabled>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo e($data['full_name'] ?? ''); ?>" required maxlength="100">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="phone" class="form-label">Primary Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo e($data['phone'] ?? ''); ?>" required maxlength="30">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo e($data['email'] ?? ''); ?>" maxlength="150">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?php echo e($data['date_of_birth'] ?? ''); ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-select" id="gender" name="gender">
                                <option value="">Select Gender</option>
                                <option value="male" <?php echo ($data['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($data['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo ($data['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Residential Address -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-geo-alt-fill me-2 text-primary"></i> 2. Residential Address</h3>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-8">
                            <label for="address" class="form-label">Street Address</label>
                            <input type="text" class="form-control" id="address" name="address" value="<?php echo e($data['address'] ?? ''); ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="city" class="form-label">City / District</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?php echo e($data['city'] ?? ''); ?>" maxlength="50">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Professional & Financial Details -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-briefcase-fill me-2 text-primary"></i> 3. Professional & Financial Profile</h3>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="occupation" class="form-label">Occupation / Profession</label>
                            <input type="text" class="form-control" id="occupation" name="occupation" value="<?php echo e($data['occupation'] ?? ''); ?>" maxlength="100">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="monthly_income" class="form-label">Monthly Income (USD / Local)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">$</span>
                                <input type="number" step="0.01" min="0" class="form-control" id="monthly_income" name="monthly_income" value="<?php echo e($data['monthly_income'] ?? '0.00'); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Emergency Contact / Guarantor -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-shield-shaded me-2 text-primary"></i> 4. Emergency Contact / Reference</h3>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="emergency_contact_name" class="form-label">Contact Person Name</label>
                            <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name" value="<?php echo e($data['emergency_contact_name'] ?? ''); ?>" maxlength="100">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="emergency_contact_phone" class="form-label">Contact Person Phone</label>
                            <input type="text" class="form-control" id="emergency_contact_phone" name="emergency_contact_phone" value="<?php echo e($data['emergency_contact_phone'] ?? ''); ?>" maxlength="30">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Photo & Action Controls -->
        <div class="col-12 col-lg-4">
            <!-- Photo Management Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-camera-fill me-2 text-primary"></i> Profile Photo</h3>
                </div>
                <div class="card-body p-4 text-center">
                    <div class="mb-3">
                        <img src="<?php echo e($photoUrl); ?>" alt="Customer Photo" id="photoPreview" class="avatar-img-lg shadow-sm" style="width: 110px; height: 110px;">
                    </div>
                    <div class="mb-2">
                        <label for="photo" class="form-label small text-muted">Upload new photo (JPG, PNG, WebP &bull; Max 2MB)</label>
                        <input class="form-control form-control-sm" type="file" id="photo" name="photo" accept=".jpg,.jpeg,.png,.webp" onchange="previewImage(this)">
                    </div>
                    <?php if (!empty($customer['photo'])): ?>
                        <div class="form-check text-start mt-2">
                            <input class="form-check-input" type="checkbox" id="remove_photo" name="remove_photo" value="1">
                            <label class="form-check-label small text-danger" for="remove_photo">
                                Remove current photo
                            </label>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Status Settings Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-toggle-on me-2 text-primary"></i> Customer Status</h3>
                </div>
                <div class="card-body p-3">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="status" id="statusActive" value="active" <?php echo ($data['status'] ?? 'active') === 'active' ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-semibold text-success" for="statusActive">
                            <i class="bi bi-check-circle-fill me-1"></i> Active Customer
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="status" id="statusInactive" value="inactive" <?php echo ($data['status'] ?? '') === 'inactive' ? 'checked' : ''; ?>>
                        <label class="form-check-label text-muted" for="statusInactive">
                            <i class="bi bi-dash-circle me-1"></i> Inactive (Dormant)
                        </label>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="card shadow-sm mb-0">
                <div class="card-body p-3 d-grid gap-2">
                    <button type="submit" class="btn btn-primary py-2.5 fw-semibold">
                        <i class="bi bi-check-lg me-1"></i> Save Changes
                    </button>
                    <a href="<?php echo url('modules/customers/view.php?id=' . $customer['id']); ?>" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('photoPreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
