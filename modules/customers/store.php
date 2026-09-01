<?php
/**
 * Store Customer Handler
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
    set_flash('danger', 'Unauthorized: You do not have permissions to register new customers.');
    redirect('modules/customers/index.php');
}

// 2. CSRF Token Verification
if (!verify_csrf_token()) {
    set_flash('danger', 'Security token expired or invalid. Please submit the form again.');
    redirect('modules/customers/create.php');
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
$status                 = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : 'active';

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

// 5. Customer Photo Processing (Optional)
$photoFilename = null;

if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['photo'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Error uploading customer photo (Code: ' . (int)$file['error'] . ').';
    } elseif ($file['size'] > MAX_CUSTOMER_PHOTO_SIZE) {
        $errors[] = 'Customer photo exceeds the maximum permitted file size of 2MB.';
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_CUSTOMER_PHOTO_EXTENSIONS, true)) {
            $errors[] = 'Invalid photo format. Supported formats: JPG, PNG, and WebP.';
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

                // Ensure customer upload directory exists
                if (!is_dir(CUSTOMER_UPLOAD_DIR)) {
                    mkdir(CUSTOMER_UPLOAD_DIR, 0755, true);
                }

                $photoFilename = sprintf(
                    'customer_%d_%s.%s',
                    time(),
                    bin2hex(random_bytes(8)),
                    $safeExt
                );

                $destination = CUSTOMER_UPLOAD_DIR . DIRECTORY_SEPARATOR . $photoFilename;
                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    $errors[] = 'Failed to save customer photo to disk.';
                    $photoFilename = null;
                }
            }
        }
    }
}

// Redirect back if validation failed
if (!empty($errors)) {
    // If photo was saved before failure, cleanup
    if ($photoFilename && file_exists(CUSTOMER_UPLOAD_DIR . DIRECTORY_SEPARATOR . $photoFilename)) {
        @unlink(CUSTOMER_UPLOAD_DIR . DIRECTORY_SEPARATOR . $photoFilename);
    }

    set_flash('danger', implode('<br>', $errors));
    redirect('modules/customers/create.php');
}

try {
    $db = get_db_connection();

    // 6. Generate Server-Side Unique Customer Code
    $customerCode = generate_customer_code($db);

    // 7. Insert Record into Database
    $insertSql = 'INSERT INTO customers (
        customer_code, full_name, phone, email, date_of_birth, gender,
        address, city, occupation, monthly_income, emergency_contact_name,
        emergency_contact_phone, photo, status, created_by, created_at
    ) VALUES (
        :code, :full_name, :phone, :email, :dob, :gender,
        :address, :city, :occupation, :monthly_income, :em_name,
        :em_phone, :photo, :status, :created_by, NOW()
    )';

    $stmt = $db->prepare($insertSql);
    $stmt->execute([
        ':code'           => $customerCode,
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
        ':photo'          => $photoFilename,
        ':status'         => $status,
        ':created_by'     => auth_id()
    ]);

    $customerId = (int)$db->lastInsertId();

    // Clean old input cache
    unset($_SESSION['_old_customer_input']);

    set_flash('success', 'Customer <strong>' . e($fullName) . '</strong> (' . e($customerCode) . ') registered successfully.');
    redirect('modules/customers/view.php?id=' . $customerId);

} catch (Exception $e) {
    error_log('Customer store error: ' . $e->getMessage());

    if ($photoFilename && file_exists(CUSTOMER_UPLOAD_DIR . DIRECTORY_SEPARATOR . $photoFilename)) {
        @unlink(CUSTOMER_UPLOAD_DIR . DIRECTORY_SEPARATOR . $photoFilename);
    }

    set_flash('danger', 'A database error occurred while creating the customer record. Please try again.');
    redirect('modules/customers/create.php');
}
