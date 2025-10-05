<?php
$offer_id = (int)($_POST['offer_id'] ?? 0);

if ($offer_id <= 0) {
    $response['error'] = 'Offer ID invalid!';
    return;
}

try {
    // Get offer with discount
    $stmt = $pdo->prepare("SELECT * FROM offers WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $offer_id, ':user_id' => $user_id]);
    $offer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$offer) {
        $response['error'] = 'Oferta nu a fost găsită!';
        return;
    }
    
    // Get items
    $items_stmt = $pdo->prepare("SELECT * FROM offer_items WHERE offer_id = :offer_id");
    $items_stmt->execute([':offer_id' => $offer_id]);
    $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate contract number
    $contract_number = '#' . $offer['offer_number'];
    
    // Build object
    //$object = "Conform ofertei " . $offer['offer_number'] . ":\n\n";
    foreach ($items as $item) {
        $object .= "" . $item['description'] . "\n";
    }
    
    // Add discount info if applicable
    //$discountAmount = (float)($offer['discount_amount'] ?? 0);
    //if ($discountAmount > 0) {
    //    $discountType = $offer['discount_type'] ?? 'percent';
    //    if ($discountType === 'percent') {
    //        $object .= "\nDiscount aplicat: " . number_format($discountAmount, 2) . "%";
    //    } else {
    //        $object .= "\nDiscount fix aplicat: " . number_format($discountAmount, 2) . " RON";
    //    }
    //}
    
    // Create contract
    $insert = $pdo->prepare("
        INSERT INTO contracts 
        (user_id, company_cui, offer_id, contract_number, contract_date, object, total_value, duration_months, created_at)
        VALUES (?, ?, ?, ?, NOW(), ?, ?, 30, NOW())
    ");
    
    $insert->execute([
        $user_id,
        $offer['company_cui'],
        $offer_id,
        $contract_number,
        $object,
        $offer['total_value'] // already includes discount
    ]);
    
    $contract_id = $pdo->lastInsertId();
    
    $response['success'] = true;
    $response['contract_id'] = $contract_id;
    $response['message'] = 'Contract generat!';
    
} catch (PDOException $e) {
    $response['error'] = 'Eroare: ' . $e->getMessage();
}