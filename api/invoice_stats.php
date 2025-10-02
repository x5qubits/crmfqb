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

    $sqlCnt = "SELECT COUNT(*) FROM invoices";
    $sqlSum = "SELECT COALESCE(SUM(value),0) FROM invoices";
    if ($where) {
        $w = " WHERE ".implode(" AND ", $where);
        $sqlCnt .= $w; $sqlSum .= $w;
    }

    $st1 = $pdo->prepare($sqlCnt); $st1->execute($params);
    $st2 = $pdo->prepare($sqlSum); $st2->execute($params);

    echo json_encode([
        'success'=>true,
        'data'=>[
            'total'=>(int)$st1->fetchColumn(),
            'totalValue'=>number_format((float)$st2->fetchColumn(),2,'.','')
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
