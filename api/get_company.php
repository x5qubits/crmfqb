<?php
// runs inside api.php, has $pdo and $user_id

$search = trim($_POST['search'] ?? '');

try {
    $sql = "
        SELECT c.CUI, c.Reg, c.Name, c.Adress
        FROM companies c
        LEFT JOIN contacts co ON co.companie = c.CUI
        WHERE 1 = 1
    ";

    $params = [];

    // Optional: if you still want to restrict to “my” contacts only, uncomment:
    // $sql .= " AND (co.user_id = :uid OR co.user_id IS NULL)";
    // $params[':uid'] = $user_id;

    if ($search !== '') {
        $sql .= "
            AND (
                CAST(c.CUI AS CHAR)   LIKE :q
                OR CAST(c.Reg AS CHAR) LIKE :q
                OR c.Name             LIKE :q
                OR c.Adress           LIKE :q
                OR co.name            LIKE :q
                OR co.email           LIKE :q
                OR co.phone           LIKE :q
            )
        ";
        $params[':q'] = '%'.$search.'%';
    }

    // Prevent duplicates when a company has multiple contacts matching
    $sql .= " GROUP BY c.CUI, c.Reg, c.Name, c.Adress
              ORDER BY c.Name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['data'] = array_map(function($r){
        return [
            'CUI'    => $r['CUI'],
            'Reg'    => $r['Reg'],
            'Name'   => $r['Name'],
            'Adress' => $r['Adress'],
        ];
    }, $rows);
} catch (PDOException $e) {
    $response['error'] = 'DB error: '.$e->getMessage();
}
