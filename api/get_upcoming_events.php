<?php
// api/get_upcoming_events.php
$limit = (int)($_GET['limit'] ?? 10);
$days = (int)($_GET['days'] ?? 7);

try {
    $endDate = date('Y-m-d H:i:s', strtotime("+$days days"));
    
    $stmt = $pdo->prepare("SELECT 
        id, type, title, description, start, end, all_day, location, priority
        FROM calendar_events 
        WHERE user_id = ? 
        AND start >= NOW() 
        AND start <= ?
        ORDER BY start ASC 
        LIMIT ?");
    
    $stmt->execute([$user_id, $endDate, $limit]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['success'] = true;
    $response['data'] = $events;
    $response['total'] = count($events);
    
} catch (PDOException $e) {
    $response['error'] = $e->getMessage();
}
?>