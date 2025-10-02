<?php
// api/get_companies.php
$search = isset($_POST['search']) ? trim($_POST['search']) : '';

if (empty($search)) {
    $response['success'] = true;
    $response['data'] = [];
    return;
}

try {
    $sql = "SELECT CUI, Reg, Name, Adress FROM companies WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (CUI LIKE :search OR Name LIKE :search OR Reg LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    $sql .= " ORDER BY Name ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['success'] = true;
    $response['data'] = $companies;
} catch (Exception $e) {
    $response['error'] = 'Eroare la încărcarea companiilor: ' . $e->getMessage();
}