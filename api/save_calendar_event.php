<?php
// api/save_calendar_event.php
$id = (int)($_POST['id'] ?? 0);
$type = $_POST['type'] ?? 'todo';
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$start = $_POST['start'] ?? '';
$end = $_POST['end'] ?? null;
$all_day = isset($_POST['all_day']) ? 1 : 0;
$location = trim($_POST['location'] ?? '');
$attendees = trim($_POST['attendees'] ?? '');
$priority = $_POST['priority'] ?? 'medium';
$recurring = isset($_POST['recurring']) ? 1 : 0;
$recurrence = $_POST['recurrence'] ?? '';
$email_id = (int)($_POST['email_id'] ?? 0) ?: null;

try {
    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE calendar_events SET 
            type=?, title=?, description=?, start=?, end=?, all_day=?, 
            location=?, attendees=?, priority=?, recurring=?, recurrence=?, 
            email_id=?, updated_at=NOW() 
            WHERE id=? AND user_id=?");
        $stmt->execute([$type, $title, $description, $start, $end, $all_day, 
                       $location, $attendees, $priority, $recurring, $recurrence, 
                       $email_id, $id, $user_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO calendar_events 
            (user_id, type, title, description, start, end, all_day, location, 
             attendees, priority, recurring, recurrence, email_id, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $type, $title, $description, $start, $end, $all_day, 
                       $location, $attendees, $priority, $recurring, $recurrence, $email_id]);
        $id = $pdo->lastInsertId();
    }
    
    if ($recurring && $recurrence && $id > 0) {
        generateRecurringEvents($pdo, $id, $user_id, $start, $end, $recurrence);
    }
    
    $response['success'] = true;
    $response['event_id'] = $id;
} catch (PDOException $e) {
    $response['error'] = $e->getMessage();
}

function generateRecurringEvents($pdo, $eventId, $userId, $start, $end, $recurrence) {
    $maxOccurrences = 20;
    $startDate = new DateTime($start);
    $endDate = $end ? new DateTime($end) : null;
    
    $stmt = $pdo->prepare("SELECT * FROM calendar_events WHERE id = ?");
    $stmt->execute([$eventId]);
    $originalEvent = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$originalEvent) return;
    
    for ($i = 1; $i < $maxOccurrences; $i++) {
        $newStart = clone $startDate;
        $newEnd = $endDate ? clone $endDate : null;
        
        switch($recurrence) {
            case 'daily':
                $newStart->add(new DateInterval('P' . $i . 'D'));
                if ($newEnd) $newEnd->add(new DateInterval('P' . $i . 'D'));
                break;
            case 'weekly':
                $newStart->add(new DateInterval('P' . ($i * 7) . 'D'));
                if ($newEnd) $newEnd->add(new DateInterval('P' . ($i * 7) . 'D'));
                break;
            case 'monthly':
                $newStart->add(new DateInterval('P' . $i . 'M'));
                if ($newEnd) $newEnd->add(new DateInterval('P' . $i . 'M'));
                break;
            case 'yearly':
                $newStart->add(new DateInterval('P' . $i . 'Y'));
                if ($newEnd) $newEnd->add(new DateInterval('P' . $i . 'Y'));
                break;
        }
        
        if ($newStart->diff(new DateTime())->days > 365) break;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO calendar_events 
                (user_id, type, title, description, start, end, all_day, location, 
                 attendees, priority, parent_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $userId,
                $originalEvent['type'],
                $originalEvent['title'],
                $originalEvent['description'],
                $newStart->format('Y-m-d H:i:s'),
                $newEnd ? $newEnd->format('Y-m-d H:i:s') : null,
                $originalEvent['all_day'],
                $originalEvent['location'],
                $originalEvent['attendees'],
                $originalEvent['priority'],
                $eventId
            ]);
        } catch (Exception $e) {
            error_log("Error creating recurring event: " . $e->getMessage());
        }
    }
}
?>