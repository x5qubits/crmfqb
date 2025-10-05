<?php
// api/task_actions.php
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

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'complete':
            $task_id = (int)($_POST['task_id'] ?? 0);
            
            $stmt = $pdo->prepare("UPDATE calendar_events SET 
                status = 'completed',
                updated_at = NOW() 
                WHERE id = ? AND user_id = ?");
            
            $stmt->execute([$task_id, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Task marcat ca finalizat';
            } else {
                $response['error'] = 'Task nu a fost găsit';
            }
            break;
            
        case 'delete':
            $task_id = (int)($_POST['task_id'] ?? 0);
            
            $stmt = $pdo->prepare("DELETE FROM calendar_events 
                WHERE id = ? AND user_id = ?");
            
            $stmt->execute([$task_id, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Task șters cu succes';
            } else {
                $response['error'] = 'Task nu a fost găsit';
            }
            break;
            
        case 'postpone':
            $task_id = (int)($_POST['task_id'] ?? 0);
            $period = $_POST['period'] ?? '1d';
            $custom_date = $_POST['custom_date'] ?? null;
            
            // Get current task
            $stmt = $pdo->prepare("SELECT start FROM calendar_events WHERE id = ? AND user_id = ?");
            $stmt->execute([$task_id, $user_id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$task) {
                $response['error'] = 'Task nu a fost găsit';
                break;
            }
            
            $newDate = null;
            
            if ($period === 'custom' && $custom_date) {
                $newDate = date('Y-m-d H:i:s', strtotime($custom_date));
            } else {
                $intervals = [
                    '1h' => '+1 hour',
                    '3h' => '+3 hours',
                    '1d' => '+1 day',
                    '2d' => '+2 days',
                    '1w' => '+1 week'
                ];
                
                if (isset($intervals[$period])) {
                    $newDate = date('Y-m-d H:i:s', strtotime($task['start'] . ' ' . $intervals[$period]));
                }
            }
            
            if ($newDate) {
                $stmt = $pdo->prepare("UPDATE calendar_events SET 
                    start = ?,
                    updated_at = NOW() 
                    WHERE id = ? AND user_id = ?");
                
                $stmt->execute([$newDate, $task_id, $user_id]);
                
                if ($stmt->rowCount() > 0) {
                    $response['success'] = true;
                    $response['message'] = 'Task amânat cu succes';
                    $response['new_date'] = $newDate;
                } else {
                    $response['error'] = 'Eroare la amânare';
                }
            } else {
                $response['error'] = 'Dată invalidă';
            }
            break;
            
        case 'uncomplete':
            $task_id = (int)($_POST['task_id'] ?? 0);
            
            $stmt = $pdo->prepare("UPDATE calendar_events SET 
                status = 'pending',
                updated_at = NOW() 
                WHERE id = ? AND user_id = ?");
            
            $stmt->execute([$task_id, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Task marcat ca nefinalizat';
            } else {
                $response['error'] = 'Task nu a fost găsit';
            }
            break;
            
        default:
            $response['error'] = 'Acțiune invalidă';
    }
    
} catch (PDOException $e) {
    $response['error'] = 'Eroare bază de date: ' . $e->getMessage();
}

echo json_encode($response);