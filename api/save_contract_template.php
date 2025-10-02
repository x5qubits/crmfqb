<?php
// Expects: POST id,title,data; uses $pdo/$conn, $user_id, $response
if (!isset($response) || !is_array($response)) $response = [];
$id    = (int)($_POST['id'] ?? 0);
$title = trim((string)($_POST['title'] ?? ''));
$data  = (string)($_POST['data'] ?? '[]'); // JSON string from UI

if ($title === '') { $response = ['success'=>false,'error'=>'Titlu obligatoriu']; return; }

try {
    if (isset($pdo)) {
        if ($id>0) {
            $st = $pdo->prepare("UPDATE contract_templates SET title=:t, data=:d WHERE id=:id AND user_id=:u");
            $st->execute([':t'=>$title, ':d'=>$data, ':id'=>$id, ':u'=>$user_id]);
            $response = ['success'=>true,'id'=>$id];
        } else {
            $st = $pdo->prepare("INSERT INTO contract_templates (user_id,title,data) VALUES (:u,:t,:d)");
            $st->execute([':u'=>$user_id, ':t'=>$title, ':d'=>$data]);
            $response = ['success'=>true,'id'=>(int)$pdo->lastInsertId()];
        }
    } elseif (isset($conn)) {
        if ($id>0) {
            $t = $conn->real_escape_string($title);
            $d = $conn->real_escape_string($data);
            $ok = $conn->query("UPDATE contract_templates SET title='$t', data='$d' WHERE id=$id AND user_id=$user_id");
            if (!$ok) { $response=['success'=>false,'error'=>$conn->error]; return; }
            $response = ['success'=>true,'id'=>$id];
        } else {
            $t = $conn->real_escape_string($title);
            $d = $conn->real_escape_string($data);
            $ok = $conn->query("INSERT INTO contract_templates (user_id,title,data) VALUES ($user_id,'$t','$d')");
            if (!$ok) { $response=['success'=>false,'error'=>$conn->error]; return; }
            $response = ['success'=>true,'id'=>(int)$conn->insert_id];
        }
    } else { $response=['success'=>false,'error'=>'DB missing']; }
} catch (Throwable $e) { $response=['success'=>false,'error'=>$e->getMessage()]; }
