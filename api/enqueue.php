<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
$UID = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : (isset($GLOBALS['user_id']) ? (int)$GLOBALS['user_id'] : 1);

$campaign_id = (int)($_REQUEST['campaign_id'] ?? 0);
$category_id = (int)($_REQUEST['category_id'] ?? 0);
if (!$campaign_id || !$category_id) { http_response_code(400); echo json_encode(['error'=>'campaign_id and category_id required']); exit; }

try {
    $ch=$pdo->prepare("SELECT channel FROM campains_campaigns WHERE id=? AND user_id=?");
    $ch->execute([$campaign_id,$UID]);
    $row=$ch->fetch(PDO::FETCH_ASSOC);
    if (!$row) { http_response_code(404); echo json_encode(['error'=>'campaign not found']); exit; }
    $channel=$row['channel'];

    $ins=$pdo->prepare("INSERT INTO campains_queue (user_id,campaign_id,category_id,item_id,channel,status) VALUES (?,?,?,?,?, 'QUEUE')");
    $cnt=0;
    $items=$pdo->prepare("SELECT id,email,phone FROM campains_category_items WHERE category_id=? AND user_id=?");
    $items->execute([$category_id,$UID]);
    while ($it=$items->fetch(PDO::FETCH_ASSOC)) {
        $targets=[];
        if ($channel==='EMAIL' || $channel==='BOTH') { if (!empty($it['email'])) $targets[]='EMAIL'; }
        if ($channel==='SMS' || $channel==='BOTH') { if (!empty($it['phone'])) $targets[]='SMS'; }
        foreach ($targets as $chan) {
            $exists=$pdo->prepare("SELECT id FROM campains_queue WHERE user_id=? AND campaign_id=? AND item_id=? AND channel=? AND status='QUEUE'");
            $exists->execute([$UID,$campaign_id,(int)$it['id'],$chan]);
            if (!$exists->fetch()) { $ins->execute([$UID,$campaign_id,$category_id,(int)$it['id'],$chan]); $cnt++; }
        }
    }
    echo json_encode(['enqueued'=>$cnt]);
} catch (Throwable $e) { http_response_code(500); echo json_encode(['error'=>$e->getMessage()]); }
