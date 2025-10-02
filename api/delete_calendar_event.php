<?php
// api/delete_calendar_event.php
$id = (int)($_POST['id'] ?? 0);

try {
    // Check if this is a recurring event parent
    $stmt = $pdo->prepare("SELECT recurring FROM calendar_events WHERE id=? AND user_id=?");
    $stmt->execute([$id, $user_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($event && $event['recurring']) {
        // Delete all child recurring events
        $stmt = $pdo->prepare("DELETE FROM calendar_events WHERE parent_id=? AND user_id=?");
        $stmt->execute([$id, $user_id]);
    }
    
    // Delete the main event
    $stmt = $pdo->prepare("DELETE FROM calendar_events WHERE id=? AND user_id=?");
    $stmt->execute([$id, $user_id]);
    
    $response['success'] = true;
} catch (PDOException $e) {
    $response['error'] = $e->getMessage();
}
?>