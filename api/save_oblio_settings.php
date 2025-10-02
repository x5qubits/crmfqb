<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../oblio_config.php';
header('Content-Type: application/json');

try {
    $email   = trim($_POST['oblio_email'] ?? '');
    $key     = trim($_POST['oblio_key'] ?? '');
    $company = trim($_POST['oblio_company'] ?? '');

    if ($email===''||$key===''||$company==='') {
        echo json_encode(['success'=>false,'error'=>'Missing fields']); exit;
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS oblio_settings (
        id TINYINT PRIMARY KEY,
        email VARCHAR(255), api_key VARCHAR(255), company VARCHAR(255),
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $st = $pdo->prepare("REPLACE INTO oblio_settings (id,email,api_key,company) VALUES (1,?,?,?)");
    $st->execute([$email,$key,$company]);

    echo json_encode(['success'=>true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
