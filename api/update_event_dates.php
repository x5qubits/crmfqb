<?php
// api/update_event_dates.php
$id = (int)($_POST['id'] ?? 0);
$start = $_POST['start'] ?? '';
$end = $_POST['end'] ?? null;

try {
    $stmt = $pdo->prepare("UPDATE calendar_events SET start=?, end=?, updated_at=NOW() WHERE id=? AND user_id=?");
    $stmt->execute([$start, $end, $id, $user_id]);
    
    $response['success'] = true;
} catch (PDOException $e) {
    $response['error'] = $e->getMessage();
}
?>