<?php
$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    $response['error'] = 'ID invalid!';
    return;
}

try {
    $stmt = $pdo->prepare("UPDATE emails SET is_starred = NOT is_starred WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $id, ':user_id' => $user_id]);
    
    $response['success'] = true;
} catch (PDOException $e) {
    $response['error'] = 'Eroare: ' . $e->getMessage();
}