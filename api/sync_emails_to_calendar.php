<?php
try {
    // Get recent emails
    $stmt = $pdo->prepare("SELECT id, subject, from_email, received_at FROM emails WHERE user_id = :user_id AND received_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY received_at DESC LIMIT 50");
    $stmt->execute([':user_id' => $user_id]);
    $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $synced = 0;
    foreach ($emails as $email) {
        // Check if already in calendar
        $check = $pdo->prepare("SELECT id FROM calendar_events WHERE email_id = ? AND user_id = ?");
        $check->execute([$email['id'], $user_id]);
        if ($check->rowCount() > 0) continue;
        
        $stmt = $pdo->prepare("INSERT INTO calendar_events (user_id, type, title, start, email_id, source) VALUES (?, 'email', ?, ?, ?, 'email')");
        $stmt->execute([$user_id, 'Email: ' . $email['subject'], $email['received_at'], $email['id']]);
        $synced++;
    }
    
    $response['success'] = true;
    $response['message'] = "$synced emailuri sincronizate";
} catch (PDOException $e) {
    $response['error'] = $e->getMessage();
}