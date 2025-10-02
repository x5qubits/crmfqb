<?php
// api/get_contact.php
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if (empty($id)) {
    $response['error'] = 'ID contact invalid!';
    return;
}

try {
    $sql = "SELECT id, user_id, companie, role, name, phone, email 
            FROM contacts 
            WHERE id = :id AND user_id = :user_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $id,
        ':user_id' => $user_id
    ]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($contact) {
        $response['success'] = true;
        $response['data'] = $contact;
    } else {
        $response['error'] = 'Contactul nu a fost găsit!';
    }
} catch (Exception $e) {
    $response['error'] = 'Eroare la încărcarea contactului: ' . $e->getMessage();
}