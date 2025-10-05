<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
$UID = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : (isset($GLOBALS['user_id']) ? (int)$GLOBALS['user_id'] : 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'POST only']); exit; }

$category_id = (int)($_POST['category_id'] ?? 0);
$format = $_POST['format'] ?? 'json';
if (!$category_id) { http_response_code(400); echo json_encode(['error':'category_id required']); exit; }

$rows=[];
if ($format==='json') {
    $payload = $_POST['items'] ?? file_get_contents('php://input');
    if (is_string($payload)) $rows = json_decode($payload, true) ?: [];
    if (is_array($payload)) $rows = $payload;
} else {
    if (!isset($_FILES['file'])) { http_response_code(400); echo json_encode(['error':'file required']); exit; }
    $csv = array_map('str_getcsv', file($_FILES['file']['tmp_name']));
    if ($csv and count($csv)>1) { $headers = array_map('trim', array_shift($csv)); foreach ($csv as $r) { if (!count($r)) continue; $rows[] = array_combine($headers, $r); } }
}
$ins=$pdo->prepare("INSERT INTO campains_category_items (user_id,category_id,label,email,phone,memo) VALUES (?,?,?,?,?,?)");
$cnt=0; foreach ($rows as $r) { $ins->execute([$UID,$category_id,$r['label']??null,$r['email']??null,$r['phone']??null,$r['memo']??null]); $cnt++; }
echo json_encode(['imported'=>$cnt]);
