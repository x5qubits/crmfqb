<?php
require_once 'config.php';
require_once 'db.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$response = ['success' => false];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['error'] = 'Sesiune expirată. Te rog să te autentifici.';
    echo json_encode($response);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Check CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $response['error'] = 'Token de securitate invalid.';
    echo json_encode($response);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    $response['error'] = 'Eroare la încărcarea fișierului.';
    echo json_encode($response);
    exit;
}

$file = $_FILES['avatar'];

// Validate file type
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types)) {
    $response['error'] = 'Tip de fișier invalid. Doar JPEG, PNG, GIF și WebP sunt permise.';
    echo json_encode($response);
    exit;
}

// Validate file size (5MB max)
if ($file['size'] > 5 * 1024 * 1024) {
    $response['error'] = 'Fișierul este prea mare. Maxim 5MB.';
    echo json_encode($response);
    exit;
}

// Create uploads directory if it doesn't exist
$upload_dir = __DIR__ . '/uploads/avatars/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'avatar_' . $user_id . '_' . time() . '.' . $extension;
$filepath = $upload_dir . $filename;
$relative_path = 'uploads/avatars/' . $filename;

try {
    // Get old logo path to delete it
    $stmt = $pdo->prepare("SELECT logo FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $old_logo = $stmt->fetchColumn();
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Eroare la salvarea fișierului.');
    }
    
    // Update database
    $stmt = $pdo->prepare("UPDATE users SET logo = ? WHERE id = ?");
    $stmt->execute([$relative_path, $user_id]);
    
    // Delete old logo if exists and is different
    if ($old_logo && $old_logo !== $relative_path) {
        $old_file = __DIR__ . '/' . $old_logo;
        if (file_exists($old_file)) {
            @unlink($old_file);
        }
    }
    
    $response['success'] = true;
    $response['message'] = 'Logo actualizat cu succes!';
    $response['logo_path'] = $relative_path;
    
} catch (Exception $e) {
    // Clean up uploaded file if database update failed
    if (file_exists($filepath)) {
        @unlink($filepath);
    }
    
    $response['error'] = 'Eroare la actualizarea logo-ului: ' . $e->getMessage();
}

echo json_encode($response);