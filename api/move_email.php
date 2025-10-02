<?php
$id = (int)($_POST['id'] ?? 0);
$folder = $_POST['folder'] ?? 'trash';

$allowed_folders = ['inbox', 'sent', 'drafts', 'trash', 'junk'];
if (!in_array($folder, $allowed_folders)) {
    $response['error'] = 'Folder invalid!';
    return;
}

try {
    $stmt = $pdo->prepare("UPDATE emails SET folder = :folder WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $id, ':folder' => $folder, ':user_id' => $user_id]);
    
    $response['success'] = true;
    $response['message'] = 'Email mutat!';
} catch (PDOException $e) {
    $response['error'] = 'Eroare: ' . $e->getMessage();
}