<?php
require_once 'config.php';

try {
    $pdo = new PDO(
        "mysql:host=$sql_db_host;port=$sql_db_port;dbname=$sql_db_name;charset=utf8mb4",
        $sql_db_user,
        $sql_db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Conexiunea a eșuat: " . $e->getMessage());
}
?>
