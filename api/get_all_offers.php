<?php
// api/get_all_offers.php
try {
    $sql = "SELECT o.*, cmp.Name as company_name 
            FROM offers o 
            JOIN companies cmp ON o.company_cui = cmp.CUI 
            WHERE o.user_id = :user_id 
            ORDER BY o.offer_date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    $offers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get items for each offer
    if (!empty($offers)) {
        $offer_ids = array_column($offers, 'id');
        $placeholders = implode(',', array_fill(0, count($offer_ids), '?'));
        $sql_items = "SELECT * FROM offer_items WHERE offer_id IN ($placeholders) ORDER BY id ASC";
        $stmt_items = $pdo->prepare($sql_items);
        $stmt_items->execute($offer_ids);
        $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

        $items_grouped = [];
        foreach ($items as $item) {
            $items_grouped[$item['offer_id']][] = $item;
        }

        foreach ($offers as &$offer) {
            $offer['items'] = $items_grouped[$offer['id']] ?? [];
        }
    }

    $response['success'] = true;
    $response['data'] = $offers;
} catch (PDOException $e) {
    $response['error'] = 'Eroare la interogarea bazei de date: ' . $e->getMessage();
}