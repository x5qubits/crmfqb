<?php
// api/get_event_details.php
$id = (int)($_GET['id'] ?? 0);

try {
    $stmt = $pdo->prepare("SELECT 
        e.*,
        CASE 
            WHEN e.parent_id IS NOT NULL THEN 'recurring_child'
            WHEN e.recurring = 1 THEN 'recurring_parent'
            ELSE 'single'
        END as event_type,
        (SELECT COUNT(*) FROM calendar_events WHERE parent_id = e.id) as recurring_count
        FROM calendar_events e 
        WHERE e.id = ? AND e.user_id = ?");
    
    $stmt->execute([$id, $user_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        throw new Exception('Eveniment nu a fost găsit');
    }
    
    $response['success'] = true;
    $response['data'] = $event;
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}
?>