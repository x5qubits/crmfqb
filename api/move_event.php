<?php
// api/move_event.php
$id = (int)($_POST['id'] ?? 0);
$newStart = $_POST['new_start'] ?? '';
$newEnd = $_POST['new_end'] ?? '';
$allDay = isset($_POST['all_day']) ? 1 : 0;

try {
    if (empty($newStart)) {
        throw new Exception('Data de început este obligatorie');
    }
    
    $stmt = $pdo->prepare("UPDATE calendar_events SET 
        start = ?, 
        end = ?, 
        all_day = ?,
        updated_at = NOW() 
        WHERE id = ? AND user_id = ?");
    
    $stmt->execute([$newStart, $newEnd, $allDay, $id, $user_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Eveniment nu a fost găsit sau nu a fost modificat');
    }
    
    $response['success'] = true;
    $response['message'] = 'Eveniment mutat cu succes';
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}
?>