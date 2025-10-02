<?php
// api/get_calendar_stats.php
try {
    $today = date('Y-m-d');
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $weekEnd = date('Y-m-d', strtotime('sunday this week'));
    $monthStart = date('Y-m-01');
    $monthEnd = date('Y-m-t');
    
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as total_events,
        SUM(CASE WHEN DATE(start) = ? THEN 1 ELSE 0 END) as today_events,
        SUM(CASE WHEN DATE(start) BETWEEN ? AND ? THEN 1 ELSE 0 END) as week_events,
        SUM(CASE WHEN DATE(start) BETWEEN ? AND ? THEN 1 ELSE 0 END) as month_events,
        SUM(CASE WHEN type = 'todo' THEN 1 ELSE 0 END) as todo_count,
        SUM(CASE WHEN type = 'meeting' THEN 1 ELSE 0 END) as meeting_count,
        SUM(CASE WHEN type = 'deadline' THEN 1 ELSE 0 END) as deadline_count,
        SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent_count,
        SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_count
        FROM calendar_events 
        WHERE user_id = ?");
    
    $stmt->execute([$today, $weekStart, $weekEnd, $monthStart, $monthEnd, $user_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $response['success'] = true;
    $response['data'] = $stats;
    
} catch (PDOException $e) {
    $response['error'] = $e->getMessage();
}
?>
