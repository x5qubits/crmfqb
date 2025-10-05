<?php
// ==================================================
// api/update_todo.php
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
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $start = $_POST['start'] ?? '';
    $priority = $_POST['priority'] ?? 'medium';
    $location = trim($_POST['location'] ?? '');
    $all_day = (int)($_POST['all_day'] ?? 0);
    
    // Validation
    if (!$id) {
        $response['error'] = 'ID invalid';
        echo json_encode($response);
        exit;
    }
    
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
    
    // Check if TODO exists and belongs to user
    $stmt = $pdo->prepare("SELECT id FROM calendar_events 
        WHERE id = ? AND user_id = ? AND type = 'todo'");
    $stmt->execute([$id, $user_id]);
    
    if (!$stmt->fetch()) {
        $response['error'] = 'TODO nu a fost găsit';
        echo json_encode($response);
        exit;
    }
    
    // Update TODO
    $stmt = $pdo->prepare("UPDATE calendar_events SET 
        title = ?,
        description = ?,
        start = ?,
        priority = ?,
        location = ?,
        all_day = ?,
        updated_at = NOW()
        WHERE id = ? AND user_id = ?");
    
    $stmt->execute([
        $title,
        $description,
        $start,
        $priority,
        $location,
        $all_day,
        $id,
        $user_id
    ]);
    
    $response['success'] = true;
    $response['message'] = 'TODO actualizat cu succes';
    
} catch (PDOException $e) {
    $response['error'] = 'Eroare bază de date: ' . $e->getMessage();
}

echo json_encode($response);
?>