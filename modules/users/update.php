<?php
/**
 * User Account Update POST Handler
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

require_permission('users.edit');

// 2. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('danger', 'Security validation failed (CSRF token mismatch). Please resubmit the form.');
    redirect('modules/users/index.php');
}

$db = get_db_connection();

$userId = (int)($_POST['id'] ?? 0);
if ($userId <= 0) {
    set_flash('danger', 'Invalid user account.');
    redirect('modules/users/index.php');
}

// 3. Fetch Existing User Record
$stmt = $db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('danger', 'User account not found.');
    redirect('modules/users/index.php');
}

$currentId = auth_id();
$isSelf    = ($userId === (int)$currentId);

// 4. Collect & Sanitize Inputs
$name     = trim($_POST['name'] ?? '');
$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$roleId   = (int)($_POST['role_id'] ?? $user['role_id']);
$status   = trim($_POST['status'] ?? $user['status']);

// Super Admin / Self-Protection
if ($isSelf) {
    $roleId = (int)$user['role_id'];
    $status = 'active';
}

// 5. Validation
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
    // Check username uniqueness (excluding current user)
    $checkUser = $db->prepare('SELECT id FROM users WHERE username = :u AND id != :id LIMIT 1');
    $checkUser->execute([':u' => $username, ':id' => $userId]);
    if ($checkUser->fetch()) {
        $errors[] = 'This username is already taken by another account.';
    }
}

if (empty($email)) {
    $errors[] = 'Email address is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please provide a valid email address.';
} else {
    // Check email uniqueness (excluding current user)
    $checkEmail = $db->prepare('SELECT id FROM users WHERE email = :e AND id != :id LIMIT 1');
    $checkEmail->execute([':e' => $email, ':id' => $userId]);
    if ($checkEmail->fetch()) {
        $errors[] = 'An account with this email address already exists.';
    }
}

if ($roleId <= 0) {
    $errors[] = 'Please select a valid role.';
} else {
    $roleStmt = $db->prepare("SELECT id, slug, status FROM roles WHERE id = :rid LIMIT 1");
    $roleStmt->execute([':rid' => $roleId]);
    $roleRecord = $roleStmt->fetch();
    if (!$roleRecord) {
        $errors[] = 'The selected role does not exist.';
    }
}

if (!in_array($status, ['active', 'inactive'], true)) {
    $status = 'active';
}

// 6. Handle Avatar Replacement (Optional)
$avatarFilename = $user['avatar'];
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['avatar'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Avatar upload error: ' . $file['error'];
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
            $newAvatarFilename = 'avatar_' . uniqid('', true) . '.' . $ext;
            $destination = AVATAR_UPLOAD_DIR . DIRECTORY_SEPARATOR . $newAvatarFilename;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                // Delete previous avatar file if exists
                if (!empty($user['avatar'])) {
                    $oldPath = AVATAR_UPLOAD_DIR . DIRECTORY_SEPARATOR . $user['avatar'];
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                $avatarFilename = $newAvatarFilename;
            } else {
                $errors[] = 'Failed to save updated avatar image to disk.';
            }
        }
    }
}

// 7. Handle Validation Errors
if (!empty($errors)) {
    set_flash('danger', implode('<br>', $errors));
    redirect('modules/users/edit.php?id=' . $userId);
}

// 8. Execute Database Update
try {
    $roleSlug = $roleRecord['slug'] ?? $user['role'];

    $updateStmt = $db->prepare('
        UPDATE users SET
            name     = :name,
            username = :username,
            email    = :email,
            phone    = :phone,
            role     = :role,
            role_id  = :role_id,
            status   = :status,
            avatar   = :avatar
        WHERE id = :id
    ');

    $updateStmt->execute([
        ':name'     => $name,
        ':username' => $username,
        ':email'    => $email,
        ':phone'    => $phone ?: null,
        ':role'     => $roleSlug,
        ':role_id'  => $roleId,
        ':status'   => $status,
        ':avatar'   => $avatarFilename,
        ':id'       => $userId,
    ]);

    set_flash('success', "Account details for '{$name}' updated successfully.");
    redirect('modules/users/view.php?id=' . $userId);

} catch (Exception $e) {
    error_log('User update error: ' . $e->getMessage());
    set_flash('danger', 'A database error occurred while updating user: ' . $e->getMessage());
    redirect('modules/users/edit.php?id=' . $userId);
}
