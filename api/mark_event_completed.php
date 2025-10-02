<?php
// api/mark_event_completed.php
$id = (int)($_POST['id'] ?? 0);
$completed = isset($_POST['completed']) ? $_POST['completed'] : '1';

try {
    $status = $completed === '1' ? 'completed' : 'pending';
    
    $stmt = $pdo->prepare("UPDATE calendar_events SET 
        status = ?,
        updated_at = NOW() 
        WHERE id = ? AND user_id = ?");
    
    $stmt->execute([$status, $id, $user_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Eveniment nu a fost găsit');
    }
    
    $response['success'] = true;
    $response['message'] = $status === 'completed' ? 'Eveniment marcat ca finalizat' : 'Eveniment marcat ca nefinalizat';
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}
?>