<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
$UID = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : (isset($GLOBALS['user_id']) ? (int)$GLOBALS['user_id'] : 1);

$id = (int)($_REQUEST['id'] ?? 0);
$status = strtoupper($_REQUEST['status'] ?? '');
if (!$id || !in_array($status,['QUEUE','SENT','SKIP','ERROR'])) { http_response_code(400); echo json_encode(['error':'id and valid status required']); exit; }

try {
    if ($status==='SENT') {
        $pdo->prepare("UPDATE campains_queue SET status='SENT', sent_at=NOW() WHERE id=? AND user_id=?")->execute([$id,$UID]);
    } else {
        $pdo->prepare("UPDATE campains_queue SET status=?, sent_at=NULL WHERE id=? AND user_id=?")->execute([$status,$id,$UID]);
    }
    echo json_encode(['ok'=>true]);
} catch (Throwable $e) { http_response_code(500); echo json_encode(['error'=>$e->getMessage()]); }
