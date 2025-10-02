<?php
// Expects: id (GET), $pdo/$conn, $user_id, $response
if (!isset($response) || !is_array($response)) $response = [];
$id = (int)($_GET['id'] ?? 0);
try {
    if ($id<=0) { $response=['success'=>false,'error'=>'ID invalid']; return; }
    if (isset($pdo)) {
        $st = $pdo->prepare("SELECT * FROM contract_templates WHERE id=? AND user_id=?");
        $st->execute([$id,$user_id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        $response = ['success'=>true,'data'=>$row?:null];
    } elseif (isset($conn)) {
        $sql = "SELECT * FROM contract_templates WHERE id=$id AND user_id=$user_id";
        $res = $conn->query($sql);
        $row = $res ? $res->fetch_assoc() : null;
        $response = ['success'=>true,'data'=>$row];
    } else { $response=['success'=>false,'error'=>'DB missing']; }
} catch (Throwable $e) { $response=['success'=>false,'error'=>$e->getMessage()]; }
