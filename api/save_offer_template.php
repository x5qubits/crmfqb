<?php
 // Save or update offer template
    if (!isset($response) || !is_array($response)) $response = [];
    $id    = (int)($_POST['id'] ?? 0);
    $title = trim((string)($_POST['title'] ?? ''));
    $data  = (string)($_POST['data'] ?? '[]');

    if ($title === '') { 
        $response = ['success'=>false, 'error'=>'Titlu obligatoriu']; 
       return;
    }

    try {
        if (isset($pdo)) {
            if ($id > 0) {
                $st = $pdo->prepare("UPDATE offer_templates SET title=:t, data=:d WHERE id=:id AND user_id=:u");
                $st->execute([':t'=>$title, ':d'=>$data, ':id'=>$id, ':u'=>$user_id]);
                $response = ['success'=>true, 'id'=>$id];
            } else {
                $st = $pdo->prepare("INSERT INTO offer_templates (user_id, title, data) VALUES (:u, :t, :d)");
                $st->execute([':u'=>$user_id, ':t'=>$title, ':d'=>$data]);
                $response = ['success'=>true, 'id'=>(int)$pdo->lastInsertId()];
            }
        } elseif (isset($conn)) {
            $t = $conn->real_escape_string($title);
            $d = $conn->real_escape_string($data);
            if ($id > 0) {
                $ok = $conn->query("UPDATE offer_templates SET title='$t', data='$d' WHERE id=$id AND user_id=$user_id");
                if (!$ok) { 
                    $response=['success'=>false, 'error'=>$conn->error]; 
                    return; 
                }
                $response = ['success'=>true, 'id'=>$id];
            } else {
                $ok = $conn->query("INSERT INTO offer_templates (user_id, title, data) VALUES ($user_id, '$t', '$d')");
                if (!$ok) { 
                    $response=['success'=>false, 'error'=>$conn->error]; 
                    return; 
                }
                $response = ['success'=>true, 'id'=>(int)$conn->insert_id];
            }
        } else { 
            $response=['success'=>false, 'error'=>'DB missing']; 
        }
    } catch (Throwable $e) { 
        $response=['success'=>false, 'error'=>$e->getMessage()]; 
    }