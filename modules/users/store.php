<?php
/**
 * User Registration POST Handler
 * Loan Management System (loan-mgt) - Phase 8
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';

// 1. Method & Permission Guard
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/users/index.php');
}

require_permission('users.create');

// 2. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('danger', 'Security validation failed (CSRF token mismatch). Please resubmit the form.');
    redirect('modules/users/create.php');
}

$db = get_db_connection();

// 3. Collect & Sanitize Inputs
$name            = trim($_POST['name'] ?? '');
$username        = trim($_POST['username'] ?? '');
$email           = trim($_POST['email'] ?? '');
$phone           = trim($_POST['phone'] ?? '');
$roleId          = (int)($_POST['role_id'] ?? 0);
$status          = trim($_POST['status'] ?? 'active');
$password        = $_POST['password'] ?? '';
$passwordConfirm = $_POST['password_confirm'] ?? '';

// 4. Validate Inputs
$errors = [];

if (empty($name)) {
    $errors[] = 'Full name is required.';
} elseif (mb_strlen($name) > 100) {
    $errors[] = 'Full name cannot exceed 100 characters.';
}

if (empty($username)) {
    $errors[] = 'Username is required.';
} elseif (!preg_match('/^[a-zA-Z0-9_\.]{3,30}$/', $username)) {
    $errors[] = 'Username must be 3-30 characters and contain only letters, numbers, underscores, and periods.';
} else {
    // Check username uniqueness
    $checkUser = $db->prepare('SELECT id FROM users WHERE username = :u LIMIT 1');
    $checkUser->execute([':u' => $username]);
    if ($checkUser->fetch()) {
        $errors[] = 'This username is already taken. Please choose a different username.';
    }
}

if (empty($email)) {
    $errors[] = 'Email address is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please provide a valid email address.';
} else {
    // Check email uniqueness
    $checkEmail = $db->prepare('SELECT id FROM users WHERE email = :e LIMIT 1');
    $checkEmail->execute([':e' => $email]);
    if ($checkEmail->fetch()) {
        $errors[] = 'An account with this email address already exists.';
    }
}

if ($roleId <= 0) {
    $errors[] = 'Please select a valid role for the user.';
} else {
    $roleStmt = $db->prepare("SELECT id, slug, status FROM roles WHERE id = :rid LIMIT 1");
    $roleStmt->execute([':rid' => $roleId]);
    $roleRecord = $roleStmt->fetch();
    if (!$roleRecord || $roleRecord['status'] !== 'active') {
        $errors[] = 'The selected role is inactive or does not exist.';
    }
}

if (!in_array($status, ['active', 'inactive'], true)) {
    $status = 'active';
}

if (empty($password) || mb_strlen($password) < 8) {
    $errors[] = 'Password is required and must be at least 8 characters long.';
} elseif ($password !== $passwordConfirm) {
    $errors[] = 'Password confirmation does not match.';
}

// 5. Handle Avatar Upload (Optional)
$avatarFilename = null;
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['avatar'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Avatar upload encountered an error. Code: ' . $file['error'];
    } elseif ($file['size'] > MAX_AVATAR_SIZE) {
        $errors[] = 'Avatar file size exceeds the maximum limit of 2MB.';
    } else {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($mime, ALLOWED_AVATAR_MIMES, true) || !in_array($ext, ALLOWED_AVATAR_EXTENSIONS, true)) {
            $errors[] = 'Invalid avatar format. Only JPG, PNG, and WEBP images are allowed.';
        } else {
            if (!is_dir(AVATAR_UPLOAD_DIR)) {
                mkdir(AVATAR_UPLOAD_DIR, 0755, true);
            }
            $avatarFilename = 'avatar_' . uniqid('', true) . '.' . $ext;
            $destination = AVATAR_UPLOAD_DIR . DIRECTORY_SEPARATOR . $avatarFilename;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                $errors[] = 'Failed to save avatar image to server disk.';
                $avatarFilename = null;
            }
        }
    }
}

// 6. Handle Validation Errors
if (!empty($errors)) {
    // If avatar was moved, clean it up
    if ($avatarFilename && file_exists(AVATAR_UPLOAD_DIR . DIRECTORY_SEPARATOR . $avatarFilename)) {
        @unlink(AVATAR_UPLOAD_DIR . DIRECTORY_SEPARATOR . $avatarFilename);
    }
    set_flash('danger', implode('<br>', $errors));
    redirect('modules/users/create.php');
}

// 7. Insert User Record
try {
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $roleSlug = $roleRecord['slug'] ?? 'loan_officer';

    $insertStmt = $db->prepare('
        INSERT INTO users (
            name, username, email, phone, password, avatar, role, role_id, status, created_at
        ) VALUES (
            :name, :username, :email, :phone, :password, :avatar, :role, :role_id, :status, NOW()
        )
    ');

    $insertStmt->execute([
        ':name'     => $name,
        ':username' => $username,
        ':email'    => $email,
        ':phone'    => $phone ?: null,
        ':password' => $passwordHash,
        ':avatar'   => $avatarFilename,
        ':role'     => $roleSlug,
        ':role_id'  => $roleId,
        ':status'   => $status,
    ]);

    $newUserId = (int)$db->lastInsertId();

    set_flash('success', "Staff user account for '{$name}' (@{$username}) created successfully.");
    redirect('modules/users/view.php?id=' . $newUserId);

} catch (Exception $e) {
    error_log('User registration error: ' . $e->getMessage());
    if ($avatarFilename && file_exists(AVATAR_UPLOAD_DIR . DIRECTORY_SEPARATOR . $avatarFilename)) {
        @unlink(AVATAR_UPLOAD_DIR . DIRECTORY_SEPARATOR . $avatarFilename);
    }
    set_flash('danger', 'A database error occurred while registering user account: ' . $e->getMessage());
    redirect('modules/users/create.php');
}
