<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$channel = strtoupper($_GET['channel'] ?? 'SMS');
if (!in_array($channel,['SMS','EMAIL'])) { http_response_code(400); echo json_encode(['error'=>'channel must be SMS or EMAIL']); exit; }

try {
    $pdo->beginTransaction();
    $field = $channel==='EMAIL' ? 'email' : 'phone';
    $sql = "SELECT q.id, q.item_id, q.campaign_id, it.label, it.$field AS target, cp.title, cp.schedule_time
            FROM campains_queue q
            JOIN campains_category_items it ON it.id=q.item_id
            JOIN campains_campaigns cp ON cp.id=q.campaign_id
            WHERE q.status='QUEUE' AND q.channel=:ch AND it.$field IS NOT NULL AND it.$field<>''
            ORDER BY cp.schedule_time ASC, q.id ASC
            FOR UPDATE LIMIT 1";
    $stmt=$pdo->prepare($sql);
    $stmt->execute([':ch'=>$channel]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $pdo->prepare("UPDATE campains_queue SET status='SENT', sent_at=NOW() WHERE id=?")->execute([(int)$row['id']]);
        $pdo->commit();
        echo json_encode(['queue_id'=>(int)$row['id'],'campaign_id'=>(int)$row['campaign_id'],'title'=>$row['title'],'label'=>$row['label'],'target'=>$row['target'],'channel'=>$channel,'schedule_time'=>$row['schedule_time'],'status'=>'SENT'], JSON_UNESCAPED_UNICODE);
    } else { $pdo->commit(); echo json_encode([]); }
} catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); http_response_code(500); echo json_encode(['error'=>$e->getMessage()]); }