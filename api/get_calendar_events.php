<?php
// api/get_calendar_events.php
try {
    $stmt = $pdo->prepare("SELECT 
        id, type, title, description, start, end, all_day, location, 
        attendees, priority, recurring, recurrence, email_id, source, 
        created_at, updated_at 
        FROM calendar_events 
        WHERE user_id = ? 
        ORDER BY start ASC");
    $stmt->execute([$user_id]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['success'] = true;
    $response['data'] = $events;
} catch (PDOException $e) {
    $response['error'] = $e->getMessage();
}
?>