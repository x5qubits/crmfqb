<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../oblio_config.php';
header('Content-Type: application/json');

try {
    $api = new OblioAPI($pdo);
    if (!method_exists($api,'pushInvoice')) {
        echo json_encode(['success'=>false,'error'=>'OblioAPI::pushInvoice not implemented']); exit;
    }

    $st = $pdo->query("SELECT * FROM invoices WHERE (ext_id IS NULL OR ext_id='') AND (status='draft' OR status='local')");
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $upd = $pdo->prepare("UPDATE invoices SET ext_id=?, status=? WHERE id=?");

    $pushed=0; $errors=0;
    foreach($rows as $r){
        try {
            $res = $api->pushInvoice($r);
            $upd->execute([$res['id']??null,$res['status']??'sent',(int)$r['id']]);
            $pushed++;
        } catch(Throwable $e){ $errors++; }
    }
    echo json_encode(['success'=>true,'data'=>['pushed'=>$pushed,'errors'=>$errors]]);
} catch(Throwable $e){
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
