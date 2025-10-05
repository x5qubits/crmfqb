<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

try {
    $q      = $_GET['q'] ?? '';
    $type   = $_GET['type'] ?? '';
    $status = $_GET['status'] ?? '';

    $where = []; $params = [];
    if ($q !== '') {
        $where[] = "(series_number LIKE ? OR client LIKE ? OR status LIKE ?)";
        $params = ["%$q%","%$q%","%$q%"];
    }
    if ($type !== '')   { $where[] = "type = ?";   $params[] = $type; }
    if ($status !== '') { $where[] = "status = ?"; $params[] = $status; }

    $sql = "SELECT id, series_number, DATE_FORMAT(date,'%Y-%m-%d') as date,
                   client, type, value, status
            FROM invoices";
    if ($where) $sql .= " WHERE ".implode(" AND ", $where);
    $sql .= " ORDER BY date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$r) {
        $id = (int)$r['id'];
        $r['actions'] =
          '<a href="invoice_view.php?id='.$id.'" class="btn btn-xs btn-primary">Detalii</a> '.
          '<button data-id="'.$id.'" class="btn btn-xs btn-danger js-del-invoice">Șterge</button>';
    }

    echo json_encode(['success'=>true,'data'=>$rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
