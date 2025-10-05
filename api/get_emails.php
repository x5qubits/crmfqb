<?php
// Get emails from database
$folder = $_POST['folder'] ?? 'inbox';
$limit = (int)($_POST['limit'] ?? 50);
$offset = (int)($_POST['offset'] ?? 0);

try {
    $stmt = $pdo->prepare("
        SELECT id, message_id, from_email, from_name, subject, 
               is_read, is_starred, received_at, created_at,
               LEFT(body, 150) as preview
        FROM emails 
        WHERE user_id = :user_id AND folder = :folder
        ORDER BY received_at DESC
        LIMIT :limit OFFSET :offset
    ");
    
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':folder', $folder, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM emails WHERE user_id = :user_id AND folder = :folder");
    $count_stmt->execute([':user_id' => $user_id, ':folder' => $folder]);
    $total = $count_stmt->fetchColumn();
    
    $response['success'] = true;
    $response['data'] = $emails;
    $response['total'] = $total;
    
} catch (PDOException $e) {
    $response['error'] = 'Eroare: ' . $e->getMessage();
}