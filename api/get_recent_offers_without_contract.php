<?php
try {
    // Get offers from last 30 days without contracts
    $stmt = $pdo->prepare("
        SELECT o.*, c.Name as company_name
        FROM offers o
        INNER JOIN companies c ON o.company_cui = c.CUI
        LEFT JOIN contracts ct ON ct.offer_id = o.id
        WHERE o.user_id = :user_id
        AND o.offer_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        AND ct.id IS NULL
        ORDER BY o.offer_date DESC
    ");
    $stmt->execute([':user_id' => $user_id]);
    $offers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get items for each offer
    foreach ($offers as &$offer) {
        $items_stmt = $pdo->prepare("SELECT * FROM offer_items WHERE offer_id = ?");
        $items_stmt->execute([$offer['id']]);
        $offer['items'] = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $response['success'] = true;
    $response['data'] = $offers;
} catch (PDOException $e) {
    $response['error'] = $e->getMessage();
}