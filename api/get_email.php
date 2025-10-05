<?php
// Get single email with full content
$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    $response['error'] = 'ID invalid!';
    return;
}

try {
    $stmt = $pdo->prepare("
        SELECT e.*, 
               (SELECT id FROM emails WHERE user_id = :user_id AND id < :id ORDER BY id DESC LIMIT 1) as prev_id,
               (SELECT id FROM emails WHERE user_id = :user_id AND id > :id ORDER BY id ASC LIMIT 1) as next_id
        FROM emails e
        WHERE e.id = :id AND e.user_id = :user_id
    ");
    $stmt->execute([':id' => $id, ':user_id' => $user_id]);
    $email = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$email) {
        $response['error'] = 'Email negăsit!';
        return;
    }
    
    // Mark as read
    $pdo->prepare("UPDATE emails SET is_read = 1 WHERE id = :id")
        ->execute([':id' => $id]);
    
    // Get attachments
    $attach_stmt = $pdo->prepare("SELECT * FROM email_attachments WHERE email_id = :email_id");
    $attach_stmt->execute([':email_id' => $id]);
    $email['attachments'] = $attach_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get conversation thread (emails with same subject)
    $thread_stmt = $pdo->prepare("
        SELECT id, from_email, from_name, subject, received_at, LEFT(body, 200) as preview
        FROM emails 
        WHERE user_id = :user_id 
          AND (subject = :subject OR subject LIKE CONCAT('Re: ', :subject) OR subject LIKE CONCAT('Fwd: ', :subject))
          AND id != :current_id
        ORDER BY received_at DESC
        LIMIT 10
    ");
    $thread_stmt->execute([
        ':user_id' => $user_id,
        ':subject' => preg_replace('/^(Re:|Fwd:)\s*/i', '', $email['subject']),
        ':current_id' => $id
    ]);
    $email['thread'] = $thread_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['success'] = true;
    $response['data'] = $email;
    
} catch (PDOException $e) {
    $response['error'] = 'Eroare: ' . $e->getMessage();
}