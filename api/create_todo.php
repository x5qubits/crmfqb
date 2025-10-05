<?php
// ==================================================
// api/create_todo.php
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

try {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $start = $_POST['start'] ?? '';
    $priority = $_POST['priority'] ?? 'medium';
    $location = trim($_POST['location'] ?? '');
    $all_day = (int)($_POST['all_day'] ?? 0);
    
    // Validation
    if (empty($title)) {
        $response['error'] = 'Titlul este obligatoriu';
        echo json_encode($response);
        exit;
    }
    
    if (empty($start)) {
        $response['error'] = 'Data scadenței este obligatorie';
        echo json_encode($response);
        exit;
    }
    
    // Validate priority
    if (!in_array($priority, ['low', 'medium', 'high', 'urgent'])) {
        $priority = 'medium';
    }
    
    // Validate date
    $startDate = DateTime::createFromFormat('Y-m-d H:i:s', $start);
    if (!$startDate) {
        $response['error'] = 'Format dată invalid';
        echo json_encode($response);
        exit;
    }
    
    // Insert TODO
    $stmt = $pdo->prepare("INSERT INTO calendar_events 
        (user_id, type, title, description, start, priority, location, all_day, status, source, created_at) 
        VALUES (?, 'todo', ?, ?, ?, ?, ?, ?, 'pending', 'manual', NOW())");
    
    $stmt->execute([
        $user_id,
        $title,
        $description,
        $start,
        $priority,
        $location,
        $all_day
    ]);
    
    $response['success'] = true;
    $response['message'] = 'TODO creat cu succes';
    $response['id'] = $pdo->lastInsertId();
    
} catch (PDOException $e) {
    $response['error'] = 'Eroare bază de date: ' . $e->getMessage();
}

echo json_encode($response);
?>