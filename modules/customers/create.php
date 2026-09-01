<?php
/**
 * Create Customer View
 * Loan Management System (loan-mgt) - Phase 2
 */

$pageTitle = 'Add Customer';
$activeNav = 'customers';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// Authorization Check: Only Admin, Manager, and Loan Officer can create customers
if (!can_manage_customers()) {
    set_flash('danger', 'You do not have administrative permissions to register new customers.');
    redirect('modules/customers/index.php');
}

// Retrieve flash input data in case of validation errors
$old = $_SESSION['_old_customer_input'] ?? [];
unset($_SESSION['_old_customer_input']);

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?php echo url('modules/customers/index.php'); ?>" class="text-decoration-none text-muted">Customers</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Add Customer</li>
            </ol>
        </nav>
        <h2 class="h4 fw-bold text-dark mb-0">Register New Customer</h2>
    </div>

    <div>
        <a href="<?php echo url('modules/customers/index.php'); ?>" class="btn btn-outline-secondary d-inline-flex align-items-center">
            <i class="bi bi-arrow-left me-1"></i> Back to Customer List
        </a>
    </div>
</div>

<form action="<?php echo url('modules/customers/store.php'); ?>" method="POST" enctype="multipart/form-data" autocomplete="off">
    <?php echo csrf_field(); ?>

    <div class="row g-4">
        <!-- Left Column: Form Fields -->
        <div class="col-12 col-lg-8">
            <!-- 1. Personal & Contact Information -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-person-fill me-2 text-primary"></i> 1. Personal & Contact Information</h3>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo e($old['full_name'] ?? ''); ?>" placeholder="e.g. Rahim Uddin" required maxlength="100">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="phone" class="form-label">Primary Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo e($old['phone'] ?? ''); ?>" placeholder="+1 (555) 000-0000" required maxlength="30">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo e($old['email'] ?? ''); ?>" placeholder="customer@example.com" maxlength="150">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?php echo e($old['date_of_birth'] ?? ''); ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-select" id="gender" name="gender">
                                <option value="">Select Gender</option>
                                <option value="male" <?php echo ($old['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($old['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo ($old['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
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
                            <input type="text" class="form-control" id="address" name="address" value="<?php echo e($old['address'] ?? ''); ?>" placeholder="House #, Street, Area...">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="city" class="form-label">City / District</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?php echo e($old['city'] ?? ''); ?>" placeholder="e.g. Dhaka" maxlength="50">
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
                            <input type="text" class="form-control" id="occupation" name="occupation" value="<?php echo e($old['occupation'] ?? ''); ?>" placeholder="e.g. Business Owner, Engineer..." maxlength="100">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="monthly_income" class="form-label">Monthly Income (USD / Local)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">$</span>
                                <input type="number" step="0.01" min="0" class="form-control" id="monthly_income" name="monthly_income" value="<?php echo e($old['monthly_income'] ?? '0.00'); ?>" placeholder="0.00">
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
                            <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name" value="<?php echo e($old['emergency_contact_name'] ?? ''); ?>" placeholder="Guarantor / Spouse / Relative name" maxlength="100">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="emergency_contact_phone" class="form-label">Contact Person Phone</label>
                            <input type="text" class="form-control" id="emergency_contact_phone" name="emergency_contact_phone" value="<?php echo e($old['emergency_contact_phone'] ?? ''); ?>" placeholder="+1 (555) 000-0000" maxlength="30">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Photo & Actions -->
        <div class="col-12 col-lg-4">
            <!-- Photo Upload Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-camera-fill me-2 text-primary"></i> Customer Photo</h3>
                </div>
                <div class="card-body p-4 text-center">
                    <div class="mb-3">
                        <img src="<?php echo asset('images/default-avatar.svg'); ?>" alt="Customer Photo Preview" id="photoPreview" class="avatar-img-lg shadow-sm" style="width: 110px; height: 110px;">
                    </div>
                    <div class="mb-2">
                        <label for="photo" class="form-label small text-muted">Upload profile photo (JPG, PNG, WebP &bull; Max 2MB)</label>
                        <input class="form-control form-control-sm" type="file" id="photo" name="photo" accept=".jpg,.jpeg,.png,.webp" onchange="previewImage(this)">
                    </div>
                </div>
            </div>

            <!-- Account Status Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-toggle-on me-2 text-primary"></i> Account Status</h3>
                </div>
                <div class="card-body p-3">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="status" id="statusActive" value="active" <?php echo ($old['status'] ?? 'active') === 'active' ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-semibold text-success" for="statusActive">
                            <i class="bi bi-check-circle-fill me-1"></i> Active (Eligible for Loans)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="status" id="statusInactive" value="inactive" <?php echo ($old['status'] ?? '') === 'inactive' ? 'checked' : ''; ?>>
                        <label class="form-check-label text-muted" for="statusInactive">
                            <i class="bi bi-dash-circle me-1"></i> Inactive (Dormant)
                        </label>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="card shadow-sm mb-0">
                <div class="card-body p-3 d-grid gap-2">
                    <button type="submit" class="btn btn-primary py-2.5 fw-semibold">
                        <i class="bi bi-check-circle me-1"></i> Register Customer
                    </button>
                    <a href="<?php echo url('modules/customers/index.php'); ?>" class="btn btn-outline-secondary">
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
