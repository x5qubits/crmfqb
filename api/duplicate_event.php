<?php
// api/duplicate_event.php
$id = (int)($_POST['id'] ?? 0);
$newDate = $_POST['new_date'] ?? '';

try {
    $stmt = $pdo->prepare("SELECT * FROM calendar_events WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        throw new Exception('Eveniment nu a fost găsit');
    }
    
    unset($event['id']);
    $event['title'] = 'Copy of ' . $event['title'];
    $event['created_at'] = date('Y-m-d H:i:s');
    $event['updated_at'] = date('Y-m-d H:i:s');
    
    if (!empty($newDate)) {
        $originalStart = new DateTime($event['start']);
        $originalEnd = $event['end'] ? new DateTime($event['end']) : null;
        $newStart = new DateTime($newDate . ' ' . $originalStart->format('H:i:s'));
        
        $event['start'] = $newStart->format('Y-m-d H:i:s');
        
        if ($originalEnd) {
            $duration = $originalStart->diff($originalEnd);
            $newEnd = clone $newStart;
            $newEnd->add($duration);
            $event['end'] = $newEnd->format('Y-m-d H:i:s');
        }
    }
    
    $columns = array_keys($event);
    $placeholders = ':' . implode(', :', $columns);
    $columnsList = implode(', ', $columns);
    
    $stmt = $pdo->prepare("INSERT INTO calendar_events ($columnsList) VALUES ($placeholders)");
    $stmt->execute($event);
    
    $newId = $pdo->lastInsertId();
    
    $response['success'] = true;
    $response['new_id'] = $newId;
    $response['message'] = 'Eveniment duplicat cu succes';
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}
?>
