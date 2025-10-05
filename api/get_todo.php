<?php
// ==================================================
// api/get_todo.php
// ==================================================
session_start();
require_once '../config.php';
require_once '../db.php';

header('Content-Type: application/json');

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$response = ['success' => false];

if (!$user_id) {
    $response['error'] = 'Neautorizat';
    echo json_encode($response);
    exit;
}

$todo_id = (int)($_GET['id'] ?? 0);

if ($todo_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM calendar_events 
            WHERE id = ? AND user_id = ? AND type = 'todo'");
        $stmt->execute([$todo_id, $user_id]);
        
        $todo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($todo) {
            $response['success'] = true;
            $response['data'] = $todo;
        } else {
            $response['error'] = 'TODO nu a fost găsit';
        }
    } catch (PDOException $e) {
        $response['error'] = 'Eroare bază de date: ' . $e->getMessage();
    }
} else {
    $response['error'] = 'ID invalid';
}

echo json_encode($response);
?>