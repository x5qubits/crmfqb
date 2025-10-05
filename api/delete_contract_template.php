<?php
// Expects: POST id; uses $pdo/$conn, $user_id, $response
if (!isset($response) || !is_array($response)) $response = [];
$id = (int)($_POST['id'] ?? 0);
if ($id<=0) { $response = ['success'=>false,'error'=>'ID invalid']; return; }

try {
    if (isset($pdo)) {
        $st = $pdo->prepare("DELETE FROM contract_templates WHERE id=? AND user_id=?");
        $st->execute([$id,$user_id]);
        $response = ['success'=>true];
    } elseif (isset($conn)) {
        $ok = $conn->query("DELETE FROM contract_templates WHERE id=$id AND user_id=$user_id");
        if (!$ok) { $response=['success'=>false,'error'=>$conn->error]; return; }
        $response = ['success'=>true];
    } else { $response=['success'=>false,'error'=>'DB missing']; }
} catch (Throwable $e) { $response=['success'=>false,'error'=>$e->getMessage()]; }
