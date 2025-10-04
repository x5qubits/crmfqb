<?php
// Export handler for campaigns system
$scope = $_GET['scope'] ?? 'items'; 
$format = $_GET['format'] ?? 'json';

$map = [
    'categories'=>"SELECT id,name,description,created_at FROM campains_categories WHERE user_id=$UID ORDER BY id DESC", 
    'campaigns'=>"SELECT id,title,channel,schedule_time,subject,status,created_at FROM campains_campaigns WHERE user_id=$UID ORDER BY id DESC", 
    'items'=>"SELECT id,category_id,label,email,phone,memo,created_at FROM campains_category_items WHERE user_id=$UID ORDER BY id DESC", 
    'queue'=>"SELECT id,campaign_id,category_id,item_id,channel,status,sent_at,created_at FROM campains_queue WHERE user_id=$UID ORDER BY id DESC"
];

$sql = $map[$scope] ?? $map['items']; 
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

if ($format==='csv') {
    header('Content-Type: text/csv'); 
    header('Content-Disposition: attachment; filename="'.$scope.'.csv"');
    if ($rows) {
        echo implode(',', array_keys($rows[0]))."\n";
        foreach ($rows as $r) { 
            echo implode(',', array_map(fn($v)=>'"'.str_replace('"','""',$v).'"', $r))."\n"; 
        }
    }
} else {
    header('Content-Type: application/json'); 
    echo json_encode($rows, JSON_PRETTY_PRINT);
}
