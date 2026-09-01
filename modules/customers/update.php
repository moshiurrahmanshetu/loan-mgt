<?php
/**
 * Update Customer Handler
 * Loan Management System (loan-mgt) - Phase 2
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/flash.php';

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/customers/index.php');
}

// 1. Authorization Guard
if (!can_manage_customers()) {
    set_flash('danger', 'Unauthorized: You do not have permissions to modify customer profiles.');
    redirect('modules/customers/index.php');
}

$customerId = (int)($_POST['id'] ?? 0);
if ($customerId <= 0) {
    set_flash('danger', 'Invalid customer specified.');
    redirect('modules/customers/index.php');
}

// 2. CSRF Verification
if (!verify_csrf_token()) {
    set_flash('danger', 'Security token expired or invalid. Please try saving changes again.');
    redirect('modules/customers/edit.php?id=' . $customerId);
}

$db = get_db_connection();

// Fetch existing customer record
$stmtExisting = $db->prepare('SELECT * FROM customers WHERE id = :id LIMIT 1');
$stmtExisting->execute([':id' => $customerId]);
$customer = $stmtExisting->fetch();

if (!$customer) {
    set_flash('danger', 'Customer record not found.');
    redirect('modules/customers/index.php');
}

// 3. Extract and Sanitize Inputs
$fullName               = trim($_POST['full_name'] ?? '');
$phone                  = trim($_POST['phone'] ?? '');
$email                  = trim($_POST['email'] ?? '');
$dob                    = trim($_POST['date_of_birth'] ?? '');
$gender                 = trim($_POST['gender'] ?? '');
$address                = trim($_POST['address'] ?? '');
$city                   = trim($_POST['city'] ?? '');
$occupation             = trim($_POST['occupation'] ?? '');
$monthlyIncomeRaw       = trim($_POST['monthly_income'] ?? '0.00');
$emergencyContactName   = trim($_POST['emergency_contact_name'] ?? '');
$emergencyContactPhone  = trim($_POST['emergency_contact_phone'] ?? '');
$status                 = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : $customer['status'];
$removePhoto            = !empty($_POST['remove_photo']);

// Save input for form repopulation if errors occur
$_SESSION['_old_customer_input'] = $_POST;

// 4. Server-Side Validation
$errors = [];

if (empty($fullName) || mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
    $errors[] = 'Full Name is required and must be between 2 and 100 characters.';
}

if (empty($phone) || mb_strlen($phone) < 5 || mb_strlen($phone) > 30) {
    $errors[] = 'Primary Phone number is required and must be between 5 and 30 characters.';
}

if (!empty($email)) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
        $errors[] = 'Please provide a valid email address.';
    }
}

if (!empty($dob)) {
    $d = DateTime::createFromFormat('Y-m-d', $dob);
    if (!$d || $d->format('Y-m-d') !== $dob) {
        $errors[] = 'Date of birth must be a valid date in YYYY-MM-DD format.';
    }
}

if (!empty($gender) && !in_array($gender, ['male', 'female', 'other'], true)) {
    $errors[] = 'Invalid gender selected.';
}

$monthlyIncome = 0.00;
if ($monthlyIncomeRaw !== '') {
    if (!is_numeric($monthlyIncomeRaw) || (float)$monthlyIncomeRaw < 0) {
        $errors[] = 'Monthly income must be a valid non-negative number.';
    } else {
        $monthlyIncome = (float)$monthlyIncomeRaw;
    }
}

if (!empty($emergencyContactName) && mb_strlen($emergencyContactName) > 100) {
    $errors[] = 'Emergency contact name must not exceed 100 characters.';
}

if (!empty($emergencyContactPhone) && mb_strlen($emergencyContactPhone) > 30) {
    $errors[] = 'Emergency contact phone must not exceed 30 characters.';
}

// 5. Photo Replacement / Removal Handling
$newPhotoFilename = null;
$oldPhotoFilename = $customer['photo'];
$finalPhoto = $oldPhotoFilename;

if ($removePhoto) {
    $finalPhoto = null;
}

if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['photo'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Error uploading photo file (Code: ' . (int)$file['error'] . ').';
    } elseif ($file['size'] > MAX_CUSTOMER_PHOTO_SIZE) {
        $errors[] = 'Photo exceeds the maximum permitted size of 2MB.';
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_CUSTOMER_PHOTO_EXTENSIONS, true)) {
            $errors[] = 'Invalid photo format. Only JPG, PNG, and WebP images are supported.';
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, ALLOWED_CUSTOMER_PHOTO_MIMES, true)) {
                $errors[] = 'The uploaded file is not a valid image format (' . e($mimeType) . ').';
            } else {
                $extMap = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                ];
                $safeExt = $extMap[$mimeType] ?? 'jpg';

                if (!is_dir(CUSTOMER_UPLOAD_DIR)) {
                    mkdir(CUSTOMER_UPLOAD_DIR, 0755, true);
                }

                $newPhotoFilename = sprintf(
                    'customer_%d_%s.%s',
                    time(),
                    bin2hex(random_bytes(8)),
                    $safeExt
                );

                $destination = CUSTOMER_UPLOAD_DIR . DIRECTORY_SEPARATOR . $newPhotoFilename;
                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    $errors[] = 'Failed to store new photo to disk.';
                    $newPhotoFilename = null;
                } else {
                    $finalPhoto = $newPhotoFilename;
                }
            }
        }
    }
}

// Check for errors
if (!empty($errors)) {
    // If a new photo was uploaded prior to error, clean it up
    if ($newPhotoFilename && file_exists(CUSTOMER_UPLOAD_DIR . DIRECTORY_SEPARATOR . $newPhotoFilename)) {
        @unlink(CUSTOMER_UPLOAD_DIR . DIRECTORY_SEPARATOR . $newPhotoFilename);
    }

    set_flash('danger', implode('<br>', $errors));
    redirect('modules/customers/edit.php?id=' . $customerId);
}

try {
    // 6. Execute Prepared Update
    $updateSql = 'UPDATE customers SET 
        full_name = :full_name,
        phone = :phone,
        email = :email,
        date_of_birth = :dob,
        gender = :gender,
        address = :address,
        city = :city,
        occupation = :occupation,
        monthly_income = :monthly_income,
        emergency_contact_name = :em_name,
        emergency_contact_phone = :em_phone,
        photo = :photo,
        status = :status,
        updated_at = NOW()
    WHERE id = :id';

    $stmt = $db->prepare($updateSql);
    $stmt->execute([
        ':full_name'      => $fullName,
        ':phone'          => $phone,
        ':email'          => $email ?: null,
        ':dob'            => $dob ?: null,
        ':gender'         => $gender ?: null,
        ':address'        => $address ?: null,
        ':city'           => $city ?: null,
        ':occupation'     => $occupation ?: null,
        ':monthly_income' => $monthlyIncome,
        ':em_name'        => $emergencyContactName ?: null,
        ':em_phone'       => $emergencyContactPhone ?: null,
        ':photo'          => $finalPhoto,
        ':status'         => $status,
        ':id'             => $customerId
    ]);

    // 7. Cleanup Old Photo File If Replaced or Removed
    if (($newPhotoFilename || $removePhoto) && !empty($oldPhotoFilename)) {
        $oldFilePath = CUSTOMER_UPLOAD_DIR . DIRECTORY_SEPARATOR . $oldPhotoFilename;
        if (file_exists($oldFilePath) && is_file($oldFilePath)) {
            @unlink($oldFilePath);
        }
    }

    unset($_SESSION['_old_customer_input']);

    set_flash('success', 'Customer profile for <strong>' . e($fullName) . '</strong> updated successfully.');
    redirect('modules/customers/view.php?id=' . $customerId);

} catch (Exception $e) {
    error_log('Customer update error: ' . $e->getMessage());

    if ($newPhotoFilename && file_exists(CUSTOMER_UPLOAD_DIR . DIRECTORY_SEPARATOR . $newPhotoFilename)) {
        @unlink(CUSTOMER_UPLOAD_DIR . DIRECTORY_SEPARATOR . $newPhotoFilename);
    }

    set_flash('danger', 'A database error occurred while updating the customer profile.');
    redirect('modules/customers/edit.php?id=' . $customerId);
}
