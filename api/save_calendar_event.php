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
    // Validate required fields
    if (empty($title)) {
        throw new Exception('Titlul evenimentului este obligatoriu');
    }
    
    if (empty($start)) {
        throw new Exception('Data de start este obligatorie');
    }
    
    if ($id > 0) {
        // Update existing event
        $stmt = $pdo->prepare("UPDATE calendar_events SET 
            type=?, title=?, description=?, start=?, end=?, all_day=?, 
            location=?, attendees=?, priority=?, recurring=?, recurrence=?, 
            email_id=?, updated_at=NOW() 
            WHERE id=? AND user_id=?");
        $stmt->execute([$type, $title, $description, $start, $end, $all_day, 
                       $location, $attendees, $priority, $recurring, $recurrence, 
                       $email_id, $id, $user_id]);
        
        // If recurring was changed, regenerate child events
        if ($recurring && $recurrence) {
            // Delete old recurring children
            $stmt = $pdo->prepare("DELETE FROM calendar_events WHERE parent_id=? AND user_id=?");
            $stmt->execute([$id, $user_id]);
            
            // Generate new recurring events
            generateRecurringEvents($pdo, $id, $user_id, $start, $end, $recurrence);
        } elseif (!$recurring) {
            // If recurring was disabled, delete all child events
            $stmt = $pdo->prepare("DELETE FROM calendar_events WHERE parent_id=? AND user_id=?");
            $stmt->execute([$id, $user_id]);
        }
    } else {
        // Insert new event
        $stmt = $pdo->prepare("INSERT INTO calendar_events 
            (user_id, type, title, description, start, end, all_day, location, 
             attendees, priority, recurring, recurrence, email_id, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $type, $title, $description, $start, $end, $all_day, 
                       $location, $attendees, $priority, $recurring, $recurrence, $email_id]);
        $id = $pdo->lastInsertId();
        
        // Generate recurring events if needed
        if ($recurring && $recurrence && $id > 0) {
            generateRecurringEvents($pdo, $id, $user_id, $start, $end, $recurrence);
        }
    }
    
    $response['success'] = true;
    $response['event_id'] = $id;
    $response['message'] = 'Eveniment salvat cu succes';
    
} catch (PDOException $e) {
    $response['error'] = 'Eroare bază de date: ' . $e->getMessage();
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

function generateRecurringEvents($pdo, $eventId, $userId, $start, $end, $recurrence) {
    $maxOccurrences = 52; // Maximum number of recurring events (1 year for weekly)
    $startDate = new DateTime($start);
    $endDate = $end ? new DateTime($end) : null;
    
    // Get the original event details
    $stmt = $pdo->prepare("SELECT * FROM calendar_events WHERE id = ?");
    $stmt->execute([$eventId]);
    $originalEvent = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$originalEvent) return;
    
    // Calculate duration if end date exists
    $duration = null;
    if ($endDate) {
        $duration = $startDate->diff($endDate);
    }
    
    // Generate recurring events
    for ($i = 1; $i < $maxOccurrences; $i++) {
        $newStart = clone $startDate;
        $newEnd = null;
        
        switch($recurrence) {
            case 'daily':
                $newStart->add(new DateInterval('P' . $i . 'D'));
                if ($duration) {
                    $newEnd = clone $newStart;
                    $newEnd->add($duration);
                }
                break;
                
            case 'weekly':
                $newStart->add(new DateInterval('P' . ($i * 7) . 'D'));
                if ($duration) {
                    $newEnd = clone $newStart;
                    $newEnd->add($duration);
                }
                break;
                
            case 'monthly':
                $newStart->add(new DateInterval('P' . $i . 'M'));
                if ($duration) {
                    $newEnd = clone $newStart;
                    $newEnd->add($duration);
                }
                break;
                
            case 'yearly':
                $newStart->add(new DateInterval('P' . $i . 'Y'));
                if ($duration) {
                    $newEnd = clone $newStart;
                    $newEnd->add($duration);
                }
                break;
                
            default:
                continue 2; // Skip invalid recurrence types
        }
        
        // Don't generate events too far in the future
        $maxDate = new DateTime('+2 years');
        if ($newStart > $maxDate) {
            break;
        }
        
        // Insert recurring child event
        $stmt = $pdo->prepare("INSERT INTO calendar_events 
            (user_id, parent_id, type, title, description, start, end, all_day, 
             location, attendees, priority, recurring, recurrence, email_id, source, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, '', ?, ?, NOW())");
        
        $stmt->execute([
            $userId,
            $eventId,
            $originalEvent['type'],
            $originalEvent['title'],
            $originalEvent['description'],
            $newStart->format('Y-m-d H:i:s'),
            $newEnd ? $newEnd->format('Y-m-d H:i:s') : null,
            $originalEvent['all_day'],
            $originalEvent['location'],
            $originalEvent['attendees'],
            $originalEvent['priority'],
            $originalEvent['email_id'],
            'recurring'
        ]);
    }
}
?>