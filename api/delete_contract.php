<?php
// $response este deja definit ca array (success: false)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id <= 0) {
        $response['error'] = 'ID Contract invalid.';
    } else {
        try {
            $sql = "DELETE FROM contracts WHERE id = :id AND user_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id, ':user_id' => $user_id]);

            if ($stmt->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Contractul a fost șters cu succes.';
            } else {
                $response['error'] = 'Contractul nu a fost găsit sau nu aveți permisiunea să-l ștergeți.';
            }
        } catch (PDOException $e) {
            $response['error'] = 'Eroare la ștergerea contractului: ' . $e->getMessage();
        }
    }
} else {
    $response['error'] = 'Metodă de cerere invalidă.';
}
