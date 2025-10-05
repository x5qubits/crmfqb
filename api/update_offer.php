<?php
/**
 * api/save_offer.php
 * Save or update offer with discount support
 */

$action = $_POST['action'] ?? 'add';
$id = (int)($_POST['id'] ?? 0);
$company_cui = (int)($_POST['company_cui'] ?? 0);
$offer_number = trim($_POST['offer_number'] ?? '');
$offer_date = $_POST['offer_date'] ?? date('Y-m-d');
$details = trim($_POST['details'] ?? '');
$discount_type = in_array($_POST['discount_type'] ?? '', ['percent', 'fixed']) ? $_POST['discount_type'] : 'percent';
$discount_amount = (float)($_POST['discount_amount'] ?? 0);
$items_json = $_POST['offer_items_json'] ?? '[]';

// Validate required fields
if (empty($company_cui) || empty($offer_number)) {
    $response['error'] = 'Date incomplete! CUI și număr ofertă sunt obligatorii.';
    return;
}

// Parse items
$items = json_decode($items_json, true);
if (!is_array($items)) {
    $response['error'] = 'Format articole invalid!';
    return;
}

if (empty($items)) {
    $response['error'] = 'Adăugați cel puțin un articol!';
    return;
}

try {
    $pdo->beginTransaction();
    
    // Calculate subtotal from items
    $subtotal = 0;
    foreach ($items as $item) {
        $qty = (int)($item['quantity'] ?? 0);
        $price = (float)($item['unit_price'] ?? 0);
        
        if ($qty <= 0 || $price < 0) {
            throw new Exception('Cantitate sau preț invalid!');
        }
        
        $subtotal += $qty * $price;
    }
    
    // Calculate discount value
    $discount_value = 0;
    if ($discount_amount > 0) {
        if ($discount_type === 'percent') {
            // Percentage discount
            if ($discount_amount > 100) {
                throw new Exception('Discount procentual nu poate depăși 100%!');
            }
            $discount_value = $subtotal * ($discount_amount / 100);
        } else {
            // Fixed discount
            $discount_value = min($discount_amount, $subtotal); // Can't discount more than subtotal
        }
    }
    
    // Calculate final total
    $total_value = max(0, $subtotal - $discount_value);
    
    if ($action === 'edit' && $id > 0) {
        // UPDATE existing offer
        $stmt = $pdo->prepare("
            UPDATE offers 
            SET offer_date = ?, 
                details = ?, 
                discount_type = ?, 
                discount_amount = ?, 
                discount_value = ?, 
                total_value = ?
            WHERE id = ? AND user_id = ?
        ");
        
        $stmt->execute([
            $offer_date, 
            $details, 
            $discount_type, 
            $discount_amount, 
            $discount_value, 
            $total_value, 
            $id, 
            $user_id
        ]);
        
        // Delete old items
        $del = $pdo->prepare("DELETE FROM offer_items WHERE offer_id = ?");
        $del->execute([$id]);
        
        $offer_id = $id;
        
    } else {
        // INSERT new offer
        
        // Check if offer number already exists
        $check = $pdo->prepare("SELECT id FROM offers WHERE offer_number = ? AND user_id = ?");
        $check->execute([$offer_number, $user_id]);
        if ($check->fetch()) {
            throw new Exception('Numărul ofertei există deja!');
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO offers (
                user_id, company_cui, offer_number, offer_date, 
                total_value, details, 
                discount_type, discount_amount, discount_value,
                created_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $user_id,
            $company_cui,
            $offer_number,
            $offer_date,
            $total_value,
            $details,
            $discount_type,
            $discount_amount,
            $discount_value
        ]);
        
        $offer_id = $pdo->lastInsertId();
    }
    
    // Insert items
    $insert = $pdo->prepare("
        INSERT INTO offer_items (offer_id, description, quantity, unit_price) 
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($items as $item) {
        $insert->execute([
            $offer_id,
            trim($item['description']),
            (int)$item['quantity'],
            (float)$item['unit_price']
        ]);
    }
    
    $pdo->commit();
    
    $response['success'] = true;
    $response['id'] = $offer_id;
    $response['message'] = $action === 'edit' ? 'Ofertă actualizată cu succes!' : 'Ofertă salvată cu succes!';
    $response['total_value'] = $total_value;
    $response['discount_value'] = $discount_value;
    
} catch (Exception $e) {
    $pdo->rollBack();
    $response['error'] = 'Eroare: ' . $e->getMessage();
} catch (PDOException $e) {
    $pdo->rollBack();
    $response['error'] = 'Eroare bază de date: ' . $e->getMessage();
}