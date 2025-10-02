<?php
$id = (int)($_POST['id'] ?? 0);
try {
    $stmt = $pdo->prepare("DELETE FROM calendar_events WHERE id=? AND user_id=?");
    $stmt->execute([$id, $user_id]);
    $response['success'] = true;
} catch (PDOException $e) {
    $response['error'] = $e->getMessage();
}