<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
$response = array();
$response['content'] ="";
$response['contact'] = "";

$UID = (isset($_GET['user_id']) && ctype_digit($_GET['user_id']) && (int)$_GET['user_id']>0)
    ? (int)$_GET['user_id']
    : (isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : (isset($GLOBALS['user_id']) ? (int)$GLOBALS['user_id'] : 1));

$day = (isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'])) ? $_GET['date'] : date('Y-m-d');

try {
    $pdo->beginTransaction();

    $sql = "
        SELECT q.id, q.item_id, q.campaign_id,
               it.label, it.phone,
               cp.title, cp.schedule_time, cp.body_template
        FROM campains_queue q
        JOIN campains_category_items it ON it.id = q.item_id
        JOIN campains_campaigns cp ON cp.id = q.campaign_id
        WHERE q.user_id = :uid
          AND q.status = 'QUEUE'
          AND q.channel = 'SMS'
          AND it.phone IS NOT NULL AND it.phone <> ''
          AND DATE(cp.schedule_time) = :day
        ORDER BY cp.schedule_time ASC, q.id ASC
        LIMIT 1 FOR UPDATE
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':uid'=>$UID, ':day'=>$day]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $pdo->prepare("UPDATE campains_queue SET status='SENT', sent_at=NOW() WHERE id=? AND user_id=?")
            ->execute([(int)$row['id'], $UID]);
        $pdo->commit();
        echo json_encode([
			'id'      => $row['id'],
            'content'         => str_replace("{{name}}",$row['label'], $row['body_template']),
            'contact'         => $row['phone'],
			'sim'         => "SIM2",
            'OK'         => true
           


        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo->commit();
    echo json_encode($response);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error'=>$e->getMessage()]);
}
