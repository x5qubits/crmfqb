<?php
/**
 * notifications_api.php - Smart Notifications API
 * Returns aggregated notifications from all system modules
 */

header('Content-Type: application/json');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$notifications = [];
$total_count = 0;

try {
    // 1. UNREAD EMAILS
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM emails WHERE user_id = ? AND is_read = 0 AND folder = 'inbox'");
    $stmt->execute([$user_id]);
    $unread_emails = (int)$stmt->fetchColumn();
    if ($unread_emails > 0) {
        $notifications[] = [
            'type' => 'email',
            'icon' => 'fas fa-envelope',
            'color' => 'info',
            'title' => "$unread_emails email" . ($unread_emails > 1 ? '-uri' : '') . " necitit" . ($unread_emails > 1 ? 'e' : ''),
            'link' => './mailbox',
            'time' => 'recent',
            'priority' => 1
        ];
        $total_count += $unread_emails;
    }

    // 2. TODAY'S CALENDAR EVENTS
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM calendar_events WHERE user_id = ? AND DATE(start) = CURDATE() AND status != 'completed'");
    $stmt->execute([$user_id]);
    $today_events = (int)$stmt->fetchColumn();
    if ($today_events > 0) {
        $notifications[] = [
            'type' => 'calendar',
            'icon' => 'fas fa-calendar-day',
            'color' => 'warning',
            'title' => "$today_events eveniment" . ($today_events > 1 ? 'e' : '') . " astăzi",
            'link' => './calendar',
            'time' => 'today',
            'priority' => 2
        ];
        $total_count += $today_events;
    }

    // 3. URGENT/HIGH PRIORITY EVENTS (Next 7 days)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM calendar_events WHERE user_id = ? AND priority IN ('urgent', 'high') AND start BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY) AND status != 'completed'");
    $stmt->execute([$user_id]);
    $urgent_events = (int)$stmt->fetchColumn();
    if ($urgent_events > 0) {
        $notifications[] = [
            'type' => 'urgent',
            'icon' => 'fas fa-exclamation-triangle',
            'color' => 'danger',
            'title' => "$urgent_events task" . ($urgent_events > 1 ? '-uri' : '') . " urgent" . ($urgent_events > 1 ? 'e' : ''),
            'link' => './calendar',
            'time' => 'this week',
            'priority' => 0
        ];
        $total_count += $urgent_events;
    }

    // 4. UPCOMING REMINDERS
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT cr.id) FROM calendar_reminders cr INNER JOIN calendar_events ce ON cr.event_id = ce.id WHERE ce.user_id = ? AND cr.sent = 0 AND DATE_SUB(ce.start, INTERVAL cr.minutes_before MINUTE) <= NOW() AND ce.start > NOW()");
    $stmt->execute([$user_id]);
    $pending_reminders = (int)$stmt->fetchColumn();
    if ($pending_reminders > 0) {
        $notifications[] = [
            'type' => 'reminder',
            'icon' => 'fas fa-bell',
            'color' => 'warning',
            'title' => "$pending_reminders reminder" . ($pending_reminders > 1 ? '-uri' : '') . " active",
            'link' => './calendar',
            'time' => 'soon',
            'priority' => 1
        ];
        $total_count += $pending_reminders;
    }

    // 5. EXPIRING CONTRACTS (Next 30 days)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM contracts WHERE user_id = ? AND DATE_ADD(contract_date, INTERVAL duration_months MONTH) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
    $stmt->execute([$user_id]);
    $expiring_contracts = (int)$stmt->fetchColumn();
    if ($expiring_contracts > 0) {
        $notifications[] = [
            'type' => 'contract',
            'icon' => 'fas fa-file-contract',
            'color' => 'danger',
            'title' => "$expiring_contracts contract" . ($expiring_contracts > 1 ? 'e' : '') . " expiră în curând",
            'link' => './contracts?filter=expiring',
            'time' => 'next 30 days',
            'priority' => 0
        ];
        $total_count += $expiring_contracts;
    }

    // 6. CONTRACTS WITHOUT INVOICES (check by CUI)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM contracts c LEFT JOIN invoices i ON i.client LIKE CONCAT('%', c.company_cui, '%') WHERE c.user_id = ? AND i.id IS NULL");
    $stmt->execute([$user_id]);
    $contracts_no_invoices = (int)$stmt->fetchColumn();
    if ($contracts_no_invoices > 0) {
        $notifications[] = [
            'type' => 'contract_no_invoice',
            'icon' => 'fas fa-file-excel',
            'color' => 'secondary',
            'title' => "$contracts_no_invoices contract" . ($contracts_no_invoices > 1 ? 'e' : '') . " fără facturi",
            'link' => './contracts?filter=no_invoices',
            'time' => 'check',
            'priority' => 1
        ];
        $total_count += $contracts_no_invoices;
    }

    // 7. PENDING CAMPAIGN MESSAGES
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM campains_queue WHERE user_id = ? AND status = 'QUEUE'");
    $stmt->execute([$user_id]);
    $pending_campaigns = (int)$stmt->fetchColumn();
    if ($pending_campaigns > 0) {
        $notifications[] = [
            'type' => 'campaign',
            'icon' => 'fas fa-paper-plane',
            'color' => 'info',
            'title' => "$pending_campaigns mesaj" . ($pending_campaigns > 1 ? 'e' : '') . " în coadă",
            'link' => './campaigns',
            'time' => 'pending',
            'priority' => 3
        ];
        $total_count += $pending_campaigns;
    }

    // 8. DEADLINES (Next 3 days)
    $stmt = $pdo->prepare("SELECT id, title, start FROM calendar_events WHERE user_id = ? AND type = 'deadline' AND start BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY) AND status != 'completed' ORDER BY start ASC LIMIT 3");
    $stmt->execute([$user_id]);
    $deadlines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($deadlines as $deadline) {
        $time_diff = strtotime($deadline['start']) - time();
        $hours = round($time_diff / 3600);
        $time_str = $hours < 24 ? "$hours ore" : round($hours / 24) . " zile";
        $notifications[] = [
            'type' => 'deadline',
            'icon' => 'fas fa-clock',
            'color' => 'danger',
            'title' => "Deadline: " . substr($deadline['title'], 0, 40),
            'link' => './calendar',
            'time' => "în $time_str",
            'priority' => 0
        ];
    }

    // 9. DRAFT EMAILS
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM emails WHERE user_id = ? AND is_draft = 1");
    $stmt->execute([$user_id]);
    $draft_emails = (int)$stmt->fetchColumn();
    if ($draft_emails > 0) {
        $notifications[] = [
            'type' => 'draft',
            'icon' => 'fas fa-edit',
            'color' => 'secondary',
            'title' => "$draft_emails draft" . ($draft_emails > 1 ? '-uri' : '') . " salvat" . ($draft_emails > 1 ? 'e' : ''),
            'link' => './mailbox?folder=drafts',
            'time' => 'saved',
            'priority' => 4
        ];
    }

    // 10. RECENT ACTIVITY
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_log WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stmt->execute([$user_id]);
    $recent_activity = (int)$stmt->fetchColumn();

    // Sort and limit
    usort($notifications, function($a, $b) { return $a['priority'] - $b['priority']; });
    $notifications = array_slice($notifications, 0, 10);

    echo json_encode(['success' => true, 'notifications' => $notifications, 'total_count' => $total_count, 'recent_activity' => $recent_activity]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
