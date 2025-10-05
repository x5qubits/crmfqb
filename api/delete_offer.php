<?php
// $response este deja definit ca array (success: false)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id <= 0) {
        $response['error'] = 'ID Ofertă invalid.';
    } else {
        try {
            // Ștergerea ofertei va șterge automat și articolele aferente (offer_items) datorită ON DELETE CASCADE
            $sql = "DELETE FROM offers WHERE id = :id AND user_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id, ':user_id' => $user_id]);

            if ($stmt->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Oferta și articolele aferente au fost șterse cu succes.';
            } else {
                $response['error'] = 'Oferta nu a fost găsită sau nu aveți permisiunea să o ștergeți.';
            }
        } catch (PDOException $e) {
            $response['error'] = 'Eroare la ștergerea ofertei: ' . $e->getMessage();
        }
    }
} else {
    $response['error'] = 'Metodă de cerere invalidă.';
}
