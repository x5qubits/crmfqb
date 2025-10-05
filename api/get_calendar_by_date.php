<?php
// api/get_calendar_by_date.php
$date = $_GET['date'] ?? date('Y-m-d');
$view = $_GET['view'] ?? 'day'; // day, week, month

try {
    $conditions = ["user_id = ?"];
    $params = [$user_id];
    
    switch($view) {
        case 'day':
            $conditions[] = "DATE(start) = ?";
            $params[] = $date;
            break;
            
        case 'week':
            $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($date)));
            $weekEnd = date('Y-m-d', strtotime('sunday this week', strtotime($date)));
            $conditions[] = "DATE(start) BETWEEN ? AND ?";
            $params[] = $weekStart;
            $params[] = $weekEnd;
            break;
            
        case 'month':
            $monthStart = date('Y-m-01', strtotime($date));
            $monthEnd = date('Y-m-t', strtotime($date));
            $conditions[] = "DATE(start) BETWEEN ? AND ?";
            $params[] = $monthStart;
            $params[] = $monthEnd;
            break;
    }
    
    $sql = "SELECT * FROM calendar_events WHERE " . implode(' AND ', $conditions) . " ORDER BY start ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['success'] = true;
    $response['data'] = $events;
    $response['view'] = $view;
    $response['date'] = $date;
    $response['total'] = count($events);
    
} catch (PDOException $e) {
    $response['error'] = $e->getMessage();
}
?>
