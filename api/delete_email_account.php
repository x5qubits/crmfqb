<?php
$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    $response['error'] = 'ID invalid!';
    return;
}

try {
    $stmt = $pdo->prepare("DELETE FROM email_settings WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $id, ':user_id' => $user_id]);
    
    if ($stmt->rowCount() > 0) {
        $response['success'] = true;
        $response['message'] = 'Cont șters!';
    } else {
        $response['error'] = 'Contul nu a fost găsit!';
    }
} catch (PDOException $e) {
    $response['error'] = 'Eroare: ' . $e->getMessage();
}