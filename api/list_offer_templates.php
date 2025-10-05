<?php
// List all offer templates for user
    if (!isset($response) || !is_array($response)) $response = [];
    try {
        if (isset($pdo)) {
            $st = $pdo->prepare("SELECT id, title, updated_at FROM offer_templates WHERE user_id=? ORDER BY updated_at DESC");
            $st->execute([$user_id]);
            $response = ['success'=>true, 'data'=>$st->fetchAll(PDO::FETCH_ASSOC)];
        } elseif (isset($conn)) {
            $data = [];
            $sql = "SELECT id, title, updated_at FROM offer_templates WHERE user_id=$user_id ORDER BY updated_at DESC";
            if ($res = $conn->query($sql)) { 
                while($r=$res->fetch_assoc()) $data[]=$r; 
            }
            $response = ['success'=>true, 'data'=>$data];
        } else { 
            $response = ['success'=>false, 'error'=>'DB missing']; 
        }
    } catch (Throwable $e) { 
        $response = ['success'=>false, 'error'=>$e->getMessage()]; 
    }