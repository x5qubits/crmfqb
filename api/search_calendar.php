<?php
// api/search_calendar.php
$query = trim($_GET['q'] ?? $_POST['q'] ?? '');
$type = $_GET['type'] ?? '';
$priority = $_GET['priority'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

try {
    $conditions = ["user_id = ?"];
    $params = [$user_id];
    
    if (!empty($query)) {
        $conditions[] = "(title LIKE ? OR description LIKE ? OR location LIKE ?)";
        $searchQuery = '%' . $query . '%';
        $params[] = $searchQuery;
        $params[] = $searchQuery;
        $params[] = $searchQuery;
    }
    
    if (!empty($type)) {
        $conditions[] = "type = ?";
        $params[] = $type;
    }
    
    if (!empty($priority)) {
        $conditions[] = "priority = ?";
        $params[] = $priority;
    }
    
    if (!empty($dateFrom)) {
        $conditions[] = "DATE(start) >= ?";
        $params[] = $dateFrom;
    }
    
    if (!empty($dateTo)) {
        $conditions[] = "DATE(start) <= ?";
        $params[] = $dateTo;
    }
    
    $sql = "SELECT * FROM calendar_events WHERE " . implode(' AND ', $conditions) . " ORDER BY start ASC LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['success'] = true;
    $response['data'] = $events;
    $response['total'] = count($events);
    
} catch (PDOException $e) {
    $response['error'] = $e->getMessage();
}
?>