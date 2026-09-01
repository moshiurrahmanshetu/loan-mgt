<?php
/**
 * Avatar Upload Handler
 * Loan Management System (loan-mgt) - Phase 1
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/flash.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/profile/index.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('danger', 'Security token expired. Please select your avatar image again.');
    redirect('modules/profile/index.php#avatar-card');
}

$userId = auth_id();

// 2. Validate File Upload Presence & Errors
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] === UPLOAD_ERR_NO_FILE) {
    set_flash('danger', 'Please choose an image file to upload as your avatar.');
    redirect('modules/profile/index.php#avatar-card');
}

$file = $_FILES['avatar'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    set_flash('danger', 'An error occurred during file upload (Code: ' . (int)$file['error'] . ').');
    redirect('modules/profile/index.php#avatar-card');
}

// 3. Validate File Size
if ($file['size'] > MAX_AVATAR_SIZE) {
    set_flash('danger', 'The uploaded image exceeds the maximum permitted file size of 2MB.');
    redirect('modules/profile/index.php#avatar-card');
}

// 4. Validate File Extension
$origName = $file['name'];
$extension = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

if (!in_array($extension, ALLOWED_AVATAR_EXTENSIONS, true)) {
    set_flash('danger', 'Invalid file type. Only JPG, PNG, and WebP images are supported.');
    redirect('modules/profile/index.php#avatar-card');
}

// 5. Validate MIME type via FileInfo
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, ALLOWED_AVATAR_MIMES, true)) {
    set_flash('danger', 'The file content is not a valid image format (' . e($mimeType) . ').');
    redirect('modules/profile/index.php#avatar-card');
}

// Map extensions consistently
$extMap = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];
$safeExt = $extMap[$mimeType] ?? 'jpg';

try {
    $db = get_db_connection();

    // Fetch existing avatar to delete old file
    $stmtOld = $db->prepare('SELECT avatar FROM users WHERE id = :id LIMIT 1');
    $stmtOld->execute([':id' => $userId]);
    $oldAvatar = $stmtOld->fetchColumn();

    // 6. Ensure target directory exists and is writable
    if (!is_dir(AVATAR_UPLOAD_DIR)) {
        mkdir(AVATAR_UPLOAD_DIR, 0755, true);
    }

    // 7. Generate Unpredictable Server-Side Filename
    $newFilename = sprintf(
        'avatar_%d_%d_%s.%s',
        $userId,
        time(),
        bin2hex(random_bytes(8)),
        $safeExt
    );

    $destination = AVATAR_UPLOAD_DIR . DIRECTORY_SEPARATOR . $newFilename;

    // 8. Move Uploaded File
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        set_flash('danger', 'Failed to store the uploaded image. Please check directory permissions.');
        redirect('modules/profile/index.php#avatar-card');
    }

    // 9. Update Database Record
    $updateStmt = $db->prepare('UPDATE users SET avatar = :avatar WHERE id = :id');
    $updateStmt->execute([
        ':avatar' => $newFilename,
        ':id'     => $userId
    ]);

    // 10. Update Active Session
    $_SESSION['user_avatar'] = $newFilename;

    // 11. Delete Old Avatar File if it exists and differs
    if (!empty($oldAvatar)) {
        $oldFilePath = AVATAR_UPLOAD_DIR . DIRECTORY_SEPARATOR . $oldAvatar;
        if (file_exists($oldFilePath) && is_file($oldFilePath)) {
            @unlink($oldFilePath);
        }
    }

    set_flash('success', 'Profile photo updated successfully.');
    redirect('modules/profile/index.php');

} catch (Exception $e) {
    error_log('Avatar upload error: ' . $e->getMessage());
    set_flash('danger', 'An unexpected error occurred while saving your avatar.');
    redirect('modules/profile/index.php#avatar-card');
}
