<?php
// api/get_products.php
$search = isset($_POST['search']) ? trim($_POST['search']) : '';

try {
    $sql = "SELECT id, Name as description, value as unit_price 
            FROM products 
            WHERE user_id = :user_id";
    $params = [':user_id' => $user_id];
    
    if (!empty($search)) {
        $sql .= " AND Name LIKE :search";
        $params[':search'] = '%' . $search . '%';
    }
    
    $sql .= " ORDER BY Name ASC LIMIT 20";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['success'] = true;
    $response['data'] = $products;
} catch (Exception $e) {
    $response['error'] = 'Eroare la încărcarea produselor: ' . $e->getMessage();
}