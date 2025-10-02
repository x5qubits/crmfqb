<?php
// Input: POST q
// Output: { success, data:[{description, unit_price, freq}] }
$response['success'] = false;

$q = isset($_POST['q']) ? trim($_POST['q']) : '';
$limit = 15;

try {
    // history from offer_items
    $sql = "
        SELECT oi.description,
               ROUND(AVG(oi.unit_price), 2) AS unit_price,
               COUNT(*) AS freq
        FROM offer_items oi
        " . ($q !== '' ? "WHERE oi.description LIKE :q" : "") . "
        GROUP BY oi.description
        ORDER BY freq DESC
        LIMIT :lim
    ";
    $stmt = $pdo->prepare($sql);
    if ($q !== '') $stmt->bindValue(':q', '%'.$q.'%', PDO::PARAM_STR);
    $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // optional: top-up with products if not enough
    if (count($rows) < $limit) {
        $remain = $limit - count($rows);
        $psql = "
            SELECT p.Description AS description,
                   ROUND(p.value, 2) AS unit_price,
                   1 AS freq
            FROM products p
            " . ($q !== '' ? "WHERE p.Description LIKE :pq" : "") . "
            LIMIT :remain
        ";
        $pstmt = $pdo->prepare($psql);
        if ($q !== '') $pstmt->bindValue(':pq', '%'.$q.'%', PDO::PARAM_STR);
        $pstmt->bindValue(':remain', (int)$remain, PDO::PARAM_INT);
        $pstmt->execute();
        $prows = $pstmt->fetchAll(PDO::FETCH_ASSOC);

        // merge unique by description
        $seen = [];
        foreach ($rows as $r) $seen[mb_strtolower($r['description'])] = true;
        foreach ($prows as $r) {
            $k = mb_strtolower($r['description']);
            if (!isset($seen[$k])) $rows[] = $r;
        }
    }

    $response['success'] = true;
    $response['data'] = $rows;
} catch (Throwable $e) {
    $response['error'] = 'DB error: '.$e->getMessage();
}
