<?php
/**
 * System Configuration & Operational Settings View
 * Loan Management System (loan-mgt) - Phase 8
 */

$pageTitle = 'System Settings';
$activeNav = 'settings';

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// 1. Permission Guard
require_permission('settings.view');

$db = get_db_connection();

// 2. Fetch All Stored Settings
$settings = [];
$stmt = $db->query('SELECT setting_key, setting_value FROM settings');
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$canEdit = has_permission('settings.edit');
$logoUrl = get_system_logo_url();

// Supported Timezones
$timezones = [
    'America/New_York'    => 'Eastern Time (US & Canada) — UTC-5/UTC-4',
    'America/Chicago'     => 'Central Time (US & Canada) — UTC-6/UTC-5',
    'America/Denver'      => 'Mountain Time (US & Canada) — UTC-7/UTC-6',
    'America/Los_Angeles' => 'Pacific Time (US & Canada) — UTC-8/UTC-7',
    'Europe/London'       => 'London (GMT / BST) — UTC+0/UTC+1',
    'Asia/Dhaka'          => 'Dhaka (BST) — UTC+6',
    'Asia/Dubai'          => 'Dubai (GST) — UTC+4',
    'Asia/Kolkata'        => 'India Standard Time (IST) — UTC+5:30',
    'Asia/Singapore'      => 'Singapore (SGT) — UTC+8',
    'Asia/Tokyo'          => 'Tokyo (JST) — UTC+9',
    'UTC'                 => 'Coordinated Universal Time (UTC)',
];

$currentTimezone = $settings['timezone'] ?? 'America/New_York';
$currentDateFormat = $settings['date_format'] ?? 'M d, Y';

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?php echo url('modules/dashboard/index.php'); ?>" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">System Settings</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h2 class="h4 fw-bold text-dark mb-0">System Configuration & Preferences</h2>
            <span class="badge bg-primary">Configuration</span>
        </div>
    </div>
</div>

<form action="<?php echo url('modules/settings/update.php'); ?>" method="POST" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>

    <div class="row g-4 mb-4">
        <!-- Left Column: Company & Branding Settings -->
        <div class="col-12 col-lg-6">
            <!-- 1. General Organization Info Card -->
            <div class="card shadow-sm mb-4 h-100">
                <div class="card-header bg-white py-3">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-building me-2 text-primary"></i> Organization & Company Profile</h3>
                </div>
                <div class="card-body p-4">
                    <!-- Company Name -->
                    <div class="mb-3">
                        <label for="company_name" class="form-label small fw-semibold">Company / Organization Legal Name <span class="text-danger">*</span></label>
                        <input type="text" name="company_name" id="company_name" class="form-control" required value="<?php echo e($settings['company_name'] ?? APP_NAME); ?>" <?php echo !$canEdit ? 'disabled' : ''; ?>>
                    </div>

                    <!-- System Short Name -->
                    <div class="mb-3">
                        <label for="system_name" class="form-label small fw-semibold">Application Brand / Short Name <span class="text-danger">*</span></label>
                        <input type="text" name="system_name" id="system_name" class="form-control" required value="<?php echo e($settings['system_name'] ?? APP_SHORT_NAME); ?>" <?php echo !$canEdit ? 'disabled' : ''; ?>>
                        <div class="form-text">Displayed on sidebar header, navbar, and print outputs.</div>
                    </div>

                    <!-- Company Email -->
                    <div class="mb-3">
                        <label for="company_email" class="form-label small fw-semibold">Official Contact Email <span class="text-danger">*</span></label>
                        <input type="email" name="company_email" id="company_email" class="form-control" required value="<?php echo e($settings['company_email'] ?? 'info@loanmgt.com'); ?>" <?php echo !$canEdit ? 'disabled' : ''; ?>>
                    </div>

                    <!-- Company Phone -->
                    <div class="mb-3">
                        <label for="company_phone" class="form-label small fw-semibold">Official Contact Phone</label>
                        <input type="text" name="company_phone" id="company_phone" class="form-control" value="<?php echo e($settings['company_phone'] ?? ''); ?>" placeholder="+1 (555) 019-2834" <?php echo !$canEdit ? 'disabled' : ''; ?>>
                    </div>

                    <!-- Physical Address -->
                    <div class="mb-0">
                        <label for="company_address" class="form-label small fw-semibold">Headquarters Physical Address</label>
                        <textarea name="company_address" id="company_address" class="form-control" rows="2" <?php echo !$canEdit ? 'disabled' : ''; ?>><?php echo e($settings['company_address'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Regional & Branding -->
        <div class="col-12 col-lg-6">
            <!-- 2. Regional & Currency Settings Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-globe me-2 text-primary"></i> Regional & Localization Preferences</h3>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <!-- Currency Symbol -->
                        <div class="col-12 col-md-6">
                            <label for="currency_symbol" class="form-label small fw-semibold">Currency Symbol <span class="text-danger">*</span></label>
                            <input type="text" name="currency_symbol" id="currency_symbol" class="form-control font-monospace" required value="<?php echo e($settings['currency_symbol'] ?? '$'); ?>" maxlength="5" <?php echo !$canEdit ? 'disabled' : ''; ?>>
                            <div class="form-text">e.g. $, ৳, €, £, ₹, ¥</div>
                        </div>

                        <!-- Currency Code -->
                        <div class="col-12 col-md-6">
                            <label for="currency_code" class="form-label small fw-semibold">Currency Code (ISO)</label>
                            <input type="text" name="currency_code" id="currency_code" class="form-control font-monospace" value="<?php echo e($settings['currency_code'] ?? 'USD'); ?>" maxlength="5" <?php echo !$canEdit ? 'disabled' : ''; ?>>
                            <div class="form-text">e.g. USD, BDT, EUR, GBP</div>
                        </div>

                        <!-- Timezone -->
                        <div class="col-12">
                            <label for="timezone" class="form-label small fw-semibold">System Timezone <span class="text-danger">*</span></label>
                            <select name="timezone" id="timezone" class="form-select" required <?php echo !$canEdit ? 'disabled' : ''; ?>>
                                <?php foreach ($timezones as $tzKey => $tzLabel): ?>
                                    <option value="<?php echo e($tzKey); ?>" <?php echo $currentTimezone === $tzKey ? 'selected' : ''; ?>>
                                        <?php echo e($tzLabel); ?> (<?php echo e($tzKey); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Date Format -->
                        <div class="col-12">
                            <label for="date_format" class="form-label small fw-semibold">Display Date Format <span class="text-danger">*</span></label>
                            <select name="date_format" id="date_format" class="form-select" required <?php echo !$canEdit ? 'disabled' : ''; ?>>
                                <option value="M d, Y" <?php echo $currentDateFormat === 'M d, Y' ? 'selected' : ''; ?>>Aug 15, 2026 (M d, Y)</option>
                                <option value="F j, Y" <?php echo $currentDateFormat === 'F j, Y' ? 'selected' : ''; ?>>August 15, 2026 (F j, Y)</option>
                                <option value="d-m-Y" <?php echo $currentDateFormat === 'd-m-Y' ? 'selected' : ''; ?>>15-08-2026 (d-m-Y)</option>
                                <option value="Y-m-d" <?php echo $currentDateFormat === 'Y-m-d' ? 'selected' : ''; ?>>2026-08-15 (Y-m-d - ISO)</option>
                                <option value="m/d/Y" <?php echo $currentDateFormat === 'm/d/Y' ? 'selected' : ''; ?>>08/15/2026 (m/d/Y - US)</option>
                            </select>
                            <div class="form-text">Controls date rendering across tables, receipts, and reports.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. System Logo & Branding Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-image me-2 text-primary"></i> System Logo & Branding</h3>
                </div>
                <div class="card-body p-4">
                    <div class="row align-items-center g-3">
                        <div class="col-12 col-sm-4 text-center">
                            <?php if (!empty($logoUrl)): ?>
                                <img src="<?php echo e($logoUrl); ?>" alt="System Logo" class="img-thumbnail p-2 shadow-sm" style="max-height: 80px; object-fit: contain;">
                                <?php if ($canEdit): ?>
                                    <div class="form-check text-start mt-2 d-inline-block">
                                        <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="remove_logo">
                                        <label class="form-check-label small text-danger" for="remove_logo">Remove logo</label>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="bg-light p-3 rounded border text-muted">
                                    <i class="bi bi-bank2 fs-2 d-block text-primary"></i>
                                    <span class="small">Default Vector Icon</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-12 col-sm-8">
                            <label for="system_logo" class="form-label small fw-semibold">Upload Custom Logo</label>
                            <input type="file" name="system_logo" id="system_logo" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp" <?php echo !$canEdit ? 'disabled' : ''; ?>>
                            <div class="form-text">Supported: PNG, JPG, WEBP. Max file size: 2MB. Recommended dimensions: 200x50px.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($canEdit): ?>
        <div class="d-flex justify-content-end gap-2 pt-3 border-top mb-5">
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check-circle me-1"></i> Save Configuration Settings
            </button>
        </div>
    <?php endif; ?>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
