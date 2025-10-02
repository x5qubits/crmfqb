<?php
try {
    $stmt = $pdo->prepare("
        SELECT * FROM calendar_events 
        WHERE user_id = :user_id 
        ORDER BY start ASC
    ");
    $stmt->execute([':user_id' => $user_id]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['success'] = true;
    $response['data'] = $events;
} catch (PDOException $e) {
    $response['error'] = $e->getMessage();
}