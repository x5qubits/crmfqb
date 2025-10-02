<?php
// $response este deja definit ca array (success: false)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_cui = isset($_POST['company_cui']) ? (int)$_POST['company_cui'] : 0;
    $offer_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    $params = [':user_id' => $user_id];
    $where = 'WHERE o.user_id = :user_id';
    $single_result = false;

    // 1. Interogare Ofertă Principală
    $sql_main = "SELECT o.*, cmp.Name as company_name 
                 FROM offers o 
                 JOIN companies cmp ON o.company_cui = cmp.CUI ";

    if ($offer_id > 0) {
        $where .= ' AND o.id = :id';
        $params[':id'] = $offer_id;
        $single_result = true;
    } elseif ($company_cui > 0) {
        $where .= ' AND o.company_cui = :cui';
        $params[':cui'] = $company_cui;
    } else {
        $response['error'] = 'CUI sau ID Ofertă invalid.';
        return;
    }

    try {
        // Obține ofertele
        $sql_main .= "$where ORDER BY o.offer_date DESC";
        $stmt_main = $pdo->prepare($sql_main);
        $stmt_main->execute($params);

        $offers = $single_result ? [$stmt_main->fetch(PDO::FETCH_ASSOC)] : $stmt_main->fetchAll(PDO::FETCH_ASSOC);
        
        // 2. Obține Articolele pentru fiecare Ofertă
        $offer_ids = array_column($offers, 'id');
        
        if (!empty($offer_ids)) {
            $placeholders = implode(',', array_fill(0, count($offer_ids), '?'));
            $sql_items = "SELECT * FROM offer_items WHERE offer_id IN ($placeholders) ORDER BY id ASC";
            $stmt_items = $pdo->prepare($sql_items);
            
            // PDO nu suportă direct array-uri în IN(), trebuie să trecem id-urile individual
            $stmt_items->execute($offer_ids);
            $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

            // Group items by offer_id
            $items_grouped = [];
            foreach ($items as $item) {
                $items_grouped[$item['offer_id']][] = $item;
            }

            // Atașează articolele la ofertele corespunzătoare
            foreach ($offers as &$offer) {
                $offer['items'] = $items_grouped[$offer['id']] ?? [];
            }
            unset($offer); // Eliberează referința
        }

        $response['success'] = true;
        $response['data'] = $single_result ? $offers[0] : $offers;

    } catch (PDOException $e) {
        $response['error'] = 'Eroare la interogarea bazei de date: ' . $e->getMessage();
    }
} else {
    $response['error'] = 'Metodă de cerere invalidă.';
}
