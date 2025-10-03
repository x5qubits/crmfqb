<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
$UID = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : (isset($GLOBALS['user_id']) ? (int)$GLOBALS['user_id'] : 1);

try {
    $pdo->beginTransaction();
    $sql = "
        SELECT q.id, q.item_id, q.campaign_id,
               it.label, it.email,
               cp.title, cp.schedule_time
        FROM campains_queue q
        JOIN campains_category_items it ON it.id=q.item_id
        JOIN campains_campaigns cp ON cp.id=q.campaign_id
        WHERE q.user_id=? AND q.status='QUEUE' AND q.channel='EMAIL' AND it.email IS NOT NULL AND it.email<>''
        ORDER BY cp.schedule_time ASC, q.id ASC
        LIMIT 1 FOR UPDATE
    ";
    $stmt=$pdo->prepare($sql); $stmt->execute([$UID]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $pdo->prepare("UPDATE campains_queue SET status='SENT', sent_at=NOW() WHERE id=? AND user_id=?")
            ->execute([(int)$row['id'],$UID]);
        $pdo->commit();
        echo json_encode([
            'queue_id'=>(int)$row['id'],
            'campaign_id'=>(int)$row['campaign_id'],
            'title'=>$row['title'],
            'label'=>$row['label'],
            'email'=>$row['email'],
            'schedule_time'=>$row['schedule_time'],
            'status'=>'SENT'
        ], JSON_UNESCAPED_UNICODE);
    } else { $pdo->commit(); echo json_encode([]); }
} catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); http_response_code(500); echo json_encode(['error'=>$e->getMessage()]); }
