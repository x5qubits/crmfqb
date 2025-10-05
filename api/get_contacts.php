<?php
// $response este deja definit ca array (success: false)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_cui = isset($_POST['company_cui']) ? (int)$_POST['company_cui'] : 0;
    $contract_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    $params = [':user_id' => $user_id];
    $where = 'WHERE c.user_id = :user_id';

    // Caută un singur contract după ID
    if ($contract_id > 0) {
        $where .= ' AND c.id = :id';
        $params[':id'] = $contract_id;
    // Caută contracte după CUI (pentru lista din modal)
    } elseif ($company_cui > 0) {
        $where .= ' AND c.company_cui = :cui';
        $params[':cui'] = $company_cui;
    } else {
        // Dacă nu e specificat, nu returnăm nimic sau poți implementa o căutare generală
        $response['error'] = 'CUI sau ID Contract invalid.';
        return;
    }

    try {
        $sql = "SELECT c.*, cmp.Name as company_name 
                FROM contracts c 
                JOIN companies cmp ON c.company_cui = cmp.CUI 
                $where 
                ORDER BY c.contract_date DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if ($contract_id > 0) {
            $response['data'] = $stmt->fetch(PDO::FETCH_ASSOC); // Un singur rezultat
        } else {
            $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC); // Listă de rezultate
        }
        
        $response['success'] = true;
    } catch (PDOException $e) {
        $response['error'] = 'Eroare la interogarea bazei de date: ' . $e->getMessage();
    }
} else {
    $response['error'] = 'Metodă de cerere invalidă.';
}
