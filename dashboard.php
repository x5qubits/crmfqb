<?php
include_once("config.php");
$pageName = "Dashboard";
$pageId = 0;
$pageIds = 1;
include_once("WEB-INF/menu.php");

// Get user ID
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;

// Make sure $pdo exists
if (!isset($pdo)) {
    require_once __DIR__ . '/db.php';
}

$tz = new DateTimeZone('Europe/Bucharest');

// Helper function
function e($s) { 
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); 
}

// ============================================
// COLLECT ALL STATISTICS
// ============================================

// 1. EMAIL STATISTICS
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_emails,
    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_emails,
    SUM(CASE WHEN folder = 'inbox' THEN 1 ELSE 0 END) as inbox_count,
    SUM(CASE WHEN folder = 'sent' THEN 1 ELSE 0 END) as sent_count,
    SUM(CASE WHEN folder = 'spam' THEN 1 ELSE 0 END) as spam_count,
    SUM(CASE WHEN DATE(received_at) = CURDATE() THEN 1 ELSE 0 END) as today_emails
    FROM emails WHERE user_id = ?");
$stmt->execute([$user_id]);
$emailStats = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. CALENDAR STATISTICS
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_events,
    SUM(CASE WHEN DATE(start) = CURDATE() THEN 1 ELSE 0 END) as today_events,
    SUM(CASE WHEN DATE(start) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as week_events,
    SUM(CASE WHEN DATE(start) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as month_events,
    SUM(CASE WHEN type = 'todo' THEN 1 ELSE 0 END) as todo_count,
    SUM(CASE WHEN type = 'meeting' THEN 1 ELSE 0 END) as meeting_count,
    SUM(CASE WHEN type = 'deadline' THEN 1 ELSE 0 END) as deadline_count,
    SUM(CASE WHEN type = 'reminder' THEN 1 ELSE 0 END) as reminder_count,
    SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent_count,
    SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_count,
    SUM(CASE WHEN recurring = 1 THEN 1 ELSE 0 END) as recurring_count
    FROM calendar_events WHERE user_id = ?");
$stmt->execute([$user_id]);
$calendarStats = $stmt->fetch(PDO::FETCH_ASSOC);

// 3. COMPANIES & CONTACTS
$companiesCount = (int)$pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn();
$contactsCount = (int)$pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();

// 4. CONTRACTS STATISTICS
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_contracts,
    SUM(CASE WHEN DATE_ADD(contract_date, INTERVAL duration_months MONTH) >= CURDATE() THEN 1 ELSE 0 END) as active_contracts,
    SUM(CASE WHEN DATE_ADD(contract_date, INTERVAL duration_months MONTH) < CURDATE() THEN 1 ELSE 0 END) as expired_contracts,
    SUM(CASE WHEN DATE_ADD(contract_date, INTERVAL duration_months MONTH) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as expiring_soon
    FROM contracts WHERE user_id = ?");
$stmt->execute([$user_id]);
$contractStats = $stmt->fetch(PDO::FETCH_ASSOC);

// 5. UPCOMING EVENTS (Next 7 days)
$stmt = $pdo->prepare("SELECT id, type, title, start, end, priority, location
    FROM calendar_events 
    WHERE user_id = ? AND start >= NOW() AND start <= DATE_ADD(NOW(), INTERVAL 7 DAY)
    ORDER BY start ASC LIMIT 10");
$stmt->execute([$user_id]);
$upcomingEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 6. TODAY'S EVENTS
$stmt = $pdo->prepare("SELECT id, type, title, start, end, all_day, priority
    FROM calendar_events 
    WHERE user_id = ? AND DATE(start) = CURDATE()
    ORDER BY start ASC");
$stmt->execute([$user_id]);
$todayEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 7. LATEST UNREAD EMAILS
$stmt = $pdo->prepare("SELECT id, subject, from_name, from_email, received_at
    FROM emails 
    WHERE user_id = ? AND folder = 'inbox' AND is_read = 0
    ORDER BY received_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$latestUnread = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 8. RECENT SENT EMAILS
$stmt = $pdo->prepare("SELECT id, subject, to_email, sent_at
    FROM emails 
    WHERE user_id = ? AND folder = 'sent'
    ORDER BY sent_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recentSent = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 9. URGENT TASKS/EVENTS
$stmt = $pdo->prepare("SELECT id, type, title, start, priority, location
    FROM calendar_events 
    WHERE user_id = ? AND priority IN ('urgent', 'high') AND start >= NOW()
    ORDER BY start ASC LIMIT 10");
$stmt->execute([$user_id]);
$urgentTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 10. CONTRACTS EXPIRING SOON
$stmt = $pdo->prepare("SELECT id, contract_number, contract_date, duration_months,
           DATE_ADD(contract_date, INTERVAL duration_months MONTH) AS end_date,
           DATEDIFF(DATE_ADD(contract_date, INTERVAL duration_months MONTH), CURDATE()) as days_left
    FROM contracts
    WHERE user_id = ?
    HAVING end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
    ORDER BY end_date ASC LIMIT 10");
$stmt->execute([$user_id]);
$expiringContracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 11. RECENT COMPANIES
$stmt = $pdo->query("SELECT CUI, Reg, Name, Adress FROM companies ORDER BY Name ASC LIMIT 5");
$recentCompanies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 12. EVENT TYPE DISTRIBUTION (for chart)
$stmt = $pdo->prepare("SELECT type, COUNT(*) as count 
    FROM calendar_events 
    WHERE user_id = ? 
    GROUP BY type");
$stmt->execute([$user_id]);
$eventTypeDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 13. EMAIL ACTIVITY BY DAY (Last 7 days)
$stmt = $pdo->prepare("SELECT DATE(received_at) as date, COUNT(*) as count
    FROM emails
    WHERE user_id = ? AND received_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(received_at)
    ORDER BY date ASC");
$stmt->execute([$user_id]);
$emailActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 14. MONTHLY EVENT TRENDS (Last 6 months)
$stmt = $pdo->prepare("SELECT DATE_FORMAT(start, '%Y-%m') as month, COUNT(*) as count
    FROM calendar_events
    WHERE user_id = ? AND start >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month ASC");
$stmt->execute([$user_id]);
$monthlyTrends = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- CSS -->
<link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="plugins/chart.js/Chart.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">
<link rel="stylesheet" href="plugins/toastr/toastr.min.css">

<style>
.stat-card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}
.priority-urgent { border-left: 4px solid #dc3545; }
.priority-high { border-left: 4px solid #fd7e14; }
.priority-medium { border-left: 4px solid #ffc107; }
.priority-low { border-left: 4px solid #28a745; }
.event-type-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}
.chart-container {
    position: relative;
    height: 300px;
}
.activity-item {
    padding: 10px;
    border-left: 3px solid #007bff;
    margin-bottom: 10px;
    background: #f8f9fa;
    border-radius: 4px;
}
.activity-item:hover {
    background: #e9ecef;
}
</style>

<!-- Page Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-tachometer-alt"></i> Dashboard Evenimente</h1>
            </div>
            <div class="col-sm-6">
                <div class="float-right">
                    <button class="btn btn-sm btn-primary" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt"></i> Actualizează
                    </button>
                    <button class="btn btn-sm btn-info" onclick="window.print()">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<section class="content">
    <div class="container-fluid">
        
        <!-- STATISTICS CARDS ROW 1 -->
        <div class="row">
            <!-- Emails -->
            <div class="col-lg-3 col-md-6">
                <div class="small-box bg-info stat-card">
                    <div class="inner">
                        <h3><?= (int)$emailStats['unread_emails'] ?></h3>
                        <p>Email-uri necitite</p>
                        <small><?= (int)$emailStats['today_emails'] ?> primite azi</small>
                    </div>
                    <div class="icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <a href="mailbox" class="small-box-footer">
                        Deschide Mailbox <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <!-- Calendar Events -->
            <div class="col-lg-3 col-md-6">
                <div class="small-box bg-success stat-card">
                    <div class="inner">
                        <h3><?= (int)$calendarStats['today_events'] ?></h3>
                        <p>Evenimente astăzi</p>
                        <small><?= (int)$calendarStats['week_events'] ?> în această săptămână</small>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <a href="calendar.php" class="small-box-footer">
                        Vizualizează Calendar <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <!-- Urgent Tasks -->
            <div class="col-lg-3 col-md-6">
                <div class="small-box bg-warning stat-card">
                    <div class="inner">
                        <h3><?= (int)$calendarStats['urgent_count'] ?></h3>
                        <p>Task-uri urgente</p>
                        <small><?= (int)$calendarStats['high_count'] ?> prioritate înaltă</small>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <a href="calendar.php?filter=urgent" class="small-box-footer">
                        Vezi Task-uri <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <!-- Contracts -->
            <div class="col-lg-3 col-md-6">
                <div class="small-box bg-danger stat-card">
                    <div class="inner">
                        <h3><?= (int)$contractStats['expiring_soon'] ?></h3>
                        <p>Contracte expiră în 30 zile</p>
                        <small><?= (int)$contractStats['active_contracts'] ?> active</small>
                    </div>
                    <div class="icon">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <a href="contracts" class="small-box-footer">
                        Gestionare Contracte <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- STATISTICS CARDS ROW 2 -->
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="info-box stat-card">
                    <span class="info-box-icon bg-purple"><i class="fas fa-building"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Companii</span>
                        <span class="info-box-number"><?= $companiesCount ?></span>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="info-box stat-card">
                    <span class="info-box-icon bg-teal"><i class="fas fa-user-friends"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Contacte</span>
                        <span class="info-box-number"><?= $contactsCount ?></span>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="info-box stat-card">
                    <span class="info-box-icon bg-primary"><i class="fas fa-tasks"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">TODO-uri</span>
                        <span class="info-box-number"><?= (int)$calendarStats['todo_count'] ?></span>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="info-box stat-card">
                    <span class="info-box-icon bg-secondary"><i class="fas fa-handshake"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Întâlniri</span>
                        <span class="info-box-number"><?= (int)$calendarStats['meeting_count'] ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN CONTENT ROW -->
        <div class="row">
            <!-- TODAY'S EVENTS -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calendar-day"></i> Evenimente astăzi
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-primary"><?= count($todayEvents) ?></span>
                        </div>
                    </div>
                    <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                        <?php if (empty($todayEvents)): ?>
                            <div class="p-3 text-center text-muted">
                                <i class="fas fa-calendar-check fa-3x mb-2"></i>
                                <p>Nu există evenimente programate pentru astăzi</p>
                            </div>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($todayEvents as $ev): 
                                    $dt = new DateTime($ev['start'], $tz);
                                    $label = $ev['all_day'] ? 'Toată ziua' : $dt->format('H:i');
                                    $priorityClass = 'priority-' . ($ev['priority'] ?? 'medium');
                                    $typeIcons = [
                                        'meeting' => 'fa-handshake',
                                        'deadline' => 'fa-flag',
                                        'reminder' => 'fa-bell',
                                        'todo' => 'fa-check',
                                        'email' => 'fa-envelope'
                                    ];
                                    $icon = $typeIcons[$ev['type']] ?? 'fa-calendar';
                                ?>
                                <li class="list-group-item <?= $priorityClass ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="flex-fill">
                                            <i class="fas <?= $icon ?> mr-2"></i>
                                            <strong><?= $label ?></strong> — <?= e($ev['title']) ?>
                                            <br>
                                            <small class="text-muted">
                                                <span class="badge badge-<?= $ev['priority'] === 'urgent' ? 'danger' : ($ev['priority'] === 'high' ? 'warning' : 'secondary') ?>">
                                                    <?= ucfirst($ev['priority'] ?? 'medium') ?>
                                                </span>
                                                <span class="badge badge-info"><?= ucfirst($ev['type']) ?></span>
                                            </small>
                                        </div>
                                        <a href="calendar.php?date=<?= $dt->format('Y-m-d') ?>" class="btn btn-xs btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- UPCOMING EVENTS (7 days) -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-arrow-right"></i> Evenimente următoare (7 zile)
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-success"><?= count($upcomingEvents) ?></span>
                        </div>
                    </div>
                    <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                        <?php if (empty($upcomingEvents)): ?>
                            <div class="p-3 text-center text-muted">
                                <i class="fas fa-check-circle fa-3x mb-2"></i>
                                <p>Nu există evenimente programate în următoarele 7 zile</p>
                            </div>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($upcomingEvents as $ev): 
                                    $dt = new DateTime($ev['start'], $tz);
                                    $priorityClass = 'priority-' . ($ev['priority'] ?? 'medium');
                                ?>
                                <li class="list-group-item <?= $priorityClass ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="flex-fill">
                                            <strong><?= $dt->format('d.m.Y H:i') ?></strong> — <?= e($ev['title']) ?>
                                            <?php if ($ev['location']): ?>
                                                <br><small class="text-muted">
                                                    <i class="fas fa-map-marker-alt"></i> <?= e($ev['location']) ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <a href="calendar.php" class="btn btn-xs btn-outline-success">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- EMAILS & URGENT TASKS ROW -->
        <div class="row">
            <!-- LATEST UNREAD EMAILS -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-envelope-open-text"></i> Email-uri necitite
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-danger"><?= (int)$emailStats['unread_emails'] ?></span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($latestUnread)): ?>
                            <div class="p-3 text-center text-muted">
                                <i class="fas fa-inbox fa-3x mb-2"></i>
                                <p>Toate email-urile au fost citite</p>
                            </div>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($latestUnread as $mail): ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-fill">
                                            <strong><?= e($mail['subject']) ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                De la: <?= e($mail['from_name'] ?: $mail['from_email']) ?>
                                                <br>
                                                <i class="far fa-clock"></i> <?= date('d.m.Y H:i', strtotime($mail['received_at'])) ?>
                                            </small>
                                        </div>
                                        <a href="read_mail.php?id=<?= (int)$mail['id'] ?>" class="btn btn-xs btn-primary">
                                            <i class="fas fa-envelope-open"></i> Citește
                                        </a>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if ($emailStats['unread_emails'] > 5): ?>
                            <div class="card-footer text-center">
                                <a href="mailbox" class="btn btn-sm btn-primary">
                                    Vezi toate (<?= (int)$emailStats['unread_emails'] ?>) <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- URGENT TASKS -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-exclamation-circle"></i> Task-uri urgente
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-warning"><?= count($urgentTasks) ?></span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($urgentTasks)): ?>
                            <div class="p-3 text-center text-muted">
                                <i class="fas fa-smile fa-3x mb-2"></i>
                                <p>Nu există task-uri urgente</p>
                            </div>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($urgentTasks as $task): 
                                    $dt = new DateTime($task['start'], $tz);
                                    $priorityBadge = $task['priority'] === 'urgent' ? 'danger' : 'warning';
                                ?>
                                <li class="list-group-item priority-<?= $task['priority'] ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-fill">
                                            <span class="badge badge-<?= $priorityBadge ?>"><?= strtoupper($task['priority']) ?></span>
                                            <strong><?= e($task['title']) ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <i class="far fa-calendar"></i> <?= $dt->format('d.m.Y H:i') ?>
                                                <?php if ($task['location']): ?>
                                                | <i class="fas fa-map-marker-alt"></i> <?= e($task['location']) ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <a href="calendar.php" class="btn btn-xs btn-outline-<?= $priorityBadge ?>">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- CHARTS ROW -->
        <div class="row">
            <!-- EVENT TYPE DISTRIBUTION -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-pie"></i> Distribuție evenimente pe tip
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="eventTypeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EMAIL ACTIVITY -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line"></i> Activitate email (ultimele 7 zile)
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="emailActivityChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CONTRACTS & COMPANIES ROW -->
        <div class="row">
            <!-- EXPIRING CONTRACTS -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-clock"></i> Contracte care expiră în 60 zile
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-danger"><?= count($expiringContracts) ?></span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($expiringContracts)): ?>
                            <div class="p-3 text-center text-muted">
                                <i class="fas fa-thumbs-up fa-3x mb-2"></i>
                                <p>Nu există contracte care expiră în curând</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Contract</th>
                                            <th>Data start</th>
                                            <th>Data expirare</th>
                                            <th>Zile rămase</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($expiringContracts as $contract): 
                                            $daysLeft = (int)$contract['days_left'];
                                            $urgencyClass = $daysLeft <= 7 ? 'table-danger' : ($daysLeft <= 30 ? 'table-warning' : '');
                                        ?>
                                        <tr class="<?= $urgencyClass ?>">
                                            <td><?= (int)$contract['id'] ?></td>
                                            <td><strong><?= e($contract['contract_number']) ?></strong></td>
                                            <td><?= date('d.m.Y', strtotime($contract['contract_date'])) ?></td>
                                            <td><?= date('d.m.Y', strtotime($contract['end_date'])) ?></td>
                                            <td>
                                                <span class="badge badge-<?= $daysLeft <= 7 ? 'danger' : ($daysLeft <= 30 ? 'warning' : 'info') ?>">
                                                    <?= $daysLeft ?> zile
                                                </span>
                                            </td>
                                            <td>
                                                <a href="contracts.php?id=<?= (int)$contract['id'] ?>" class="btn btn-xs btn-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- RECENT COMPANIES -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-building"></i> Companii recente
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recentCompanies)): ?>
                            <div class="p-3 text-center text-muted">
                                <i class="fas fa-building fa-3x mb-2"></i>
                                <p>Nu există companii înregistrate</p>
                            </div>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($recentCompanies as $company): ?>
                                <li class="list-group-item">
                                    <strong><?= e($company['Name']) ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        CUI: <?= e($company['CUI']) ?>
                                        <?php if (!empty($company['Adress'])): ?>
                                        | <i class="fas fa-map-marker-alt"></i> <?= e($company['Adress']) ?>
                                        <?php endif; ?>
                                    </small>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="card-footer text-center">
                                <a href="firms" class="btn btn-sm btn-primary">
                                    Vezi toate companiile <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- MONTHLY TRENDS -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-area"></i> Tendințe lunare evenimente (ultimele 6 luni)
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 250px;">
                            <canvas id="monthlyTrendsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SUMMARY STATISTICS -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-bar"></i> Statistici detaliate
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon"><i class="fas fa-envelope"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Email-uri</span>
                                        <span class="info-box-number"><?= (int)$emailStats['total_emails'] ?></span>
                                        <div class="progress">
                                            <div class="progress-bar" style="width: <?= $emailStats['total_emails'] > 0 ? round(($emailStats['inbox_count'] / $emailStats['total_emails']) * 100) : 0 ?>%"></div>
                                        </div>
                                        <span class="progress-description">
                                            <?= (int)$emailStats['inbox_count'] ?> în Inbox
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon"><i class="fas fa-calendar"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Evenimente</span>
                                        <span class="info-box-number"><?= (int)$calendarStats['total_events'] ?></span>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" style="width: <?= $calendarStats['total_events'] > 0 ? round(($calendarStats['recurring_count'] / $calendarStats['total_events']) * 100) : 0 ?>%"></div>
                                        </div>
                                        <span class="progress-description">
                                            <?= (int)$calendarStats['recurring_count'] ?> recurente
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon"><i class="fas fa-file-contract"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Contracte</span>
                                        <span class="info-box-number"><?= (int)$contractStats['total_contracts'] ?></span>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" style="width: <?= $contractStats['total_contracts'] > 0 ? round(($contractStats['active_contracts'] / $contractStats['total_contracts']) * 100) : 0 ?>%"></div>
                                        </div>
                                        <span class="progress-description">
                                            <?= (int)$contractStats['active_contracts'] ?> active
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon"><i class="fas fa-flag"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Deadline-uri</span>
                                        <span class="info-box-number"><?= (int)$calendarStats['deadline_count'] ?></span>
                                        <div class="progress">
                                            <div class="progress-bar bg-warning" style="width: 75%"></div>
                                        </div>
                                        <span class="progress-description">
                                            <?= (int)$calendarStats['reminder_count'] ?> reminder-e setate
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <h5><i class="fas fa-info-circle"></i> Rezumat rapid</h5>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success"></i> <strong><?= (int)$calendarStats['today_events'] ?></strong> evenimente programate astăzi</li>
                                    <li><i class="fas fa-check text-success"></i> <strong><?= (int)$calendarStats['week_events'] ?></strong> evenimente în această săptămână</li>
                                    <li><i class="fas fa-check text-info"></i> <strong><?= (int)$emailStats['unread_emails'] ?></strong> email-uri necitite necesită atenție</li>
                                    <li><i class="fas fa-check text-warning"></i> <strong><?= (int)$calendarStats['urgent_count'] ?></strong> task-uri urgente</li>
                                    <li><i class="fas fa-check text-danger"></i> <strong><?= (int)$contractStats['expiring_soon'] ?></strong> contracte expiră în 30 zile</li>
                                    <li><i class="fas fa-check text-primary"></i> <strong><?= $companiesCount ?></strong> companii și <strong><?= $contactsCount ?></strong> contacte în baza de date</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>



<!-- Scripts -->
<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/chart.js/Chart.min.js"></script>
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="plugins/toastr/toastr.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>

<script>
const userId = <?= $user_id ?>;

// Prepare chart data
const eventTypeData = <?= json_encode($eventTypeDistribution) ?>;
const emailActivityData = <?= json_encode($emailActivity) ?>;
const monthlyTrendsData = <?= json_encode($monthlyTrends) ?>;

$(document).ready(function() {
    initCharts();
    
    // Auto refresh every 5 minutes
    setInterval(refreshDashboard, 300000);
});

function initCharts() {
    // Event Type Distribution Pie Chart
    if (eventTypeData && eventTypeData.length > 0) {
        const typeLabels = eventTypeData.map(d => d.type.charAt(0).toUpperCase() + d.type.slice(1));
        const typeCounts = eventTypeData.map(d => parseInt(d.count));
        const colors = [
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 99, 132, 0.8)',
            'rgba(255, 206, 86, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(153, 102, 255, 0.8)',
            'rgba(255, 159, 64, 0.8)'
        ];

        const ctx1 = document.getElementById('eventTypeChart');
        if (ctx1) {
            new Chart(ctx1, {
                type: 'doughnut',
                data: {
                    labels: typeLabels,
                    datasets: [{
                        data: typeCounts,
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        title: {
                            display: false
                        }
                    }
                }
            });
        }
    }

    // Email Activity Line Chart
    if (emailActivityData && emailActivityData.length > 0) {
        const activityLabels = emailActivityData.map(d => {
            const date = new Date(d.date);
            return date.toLocaleDateString('ro-RO', { day: '2-digit', month: '2-digit' });
        });
        const activityCounts = emailActivityData.map(d => parseInt(d.count));

        const ctx2 = document.getElementById('emailActivityChart');
        if (ctx2) {
            new Chart(ctx2, {
                type: 'line',
                data: {
                    labels: activityLabels,
                    datasets: [{
                        label: 'Email-uri primite',
                        data: activityCounts,
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
    }

    // Monthly Trends Bar Chart
    if (monthlyTrendsData && monthlyTrendsData.length > 0) {
        const monthLabels = monthlyTrendsData.map(d => {
            const [year, month] = d.month.split('-');
            const date = new Date(year, month - 1);
            return date.toLocaleDateString('ro-RO', { month: 'short', year: 'numeric' });
        });
        const monthCounts = monthlyTrendsData.map(d => parseInt(d.count));

        const ctx3 = document.getElementById('monthlyTrendsChart');
        if (ctx3) {
            new Chart(ctx3, {
                type: 'bar',
                data: {
                    labels: monthLabels,
                    datasets: [{
                        label: 'Evenimente',
                        data: monthCounts,
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
    }
}

function refreshDashboard() {
    toastr.info('Actualizare dashboard...', '', {timeOut: 1000});
    setTimeout(() => {
        location.reload();
    }, 1000);
}

// Add some interactivity
$('.stat-card').hover(
    function() {
        $(this).addClass('shadow-lg');
    },
    function() {
        $(this).removeClass('shadow-lg');
    }
);

// Show notification for urgent tasks
<?php if ($calendarStats['urgent_count'] > 0): ?>
setTimeout(() => {
    toastr.warning('Aveți <?= (int)$calendarStats['urgent_count'] ?> task-uri urgente!', 'Atenție', {
        timeOut: 5000,
        closeButton: true
    });
}, 1000);
<?php endif; ?>

// Show notification for expiring contracts
<?php if ($contractStats['expiring_soon'] > 0): ?>
setTimeout(() => {
    toastr.error('<?= (int)$contractStats['expiring_soon'] ?> contracte vor expira în 30 zile!', 'Important', {
        timeOut: 7000,
        closeButton: true
    });
}, 2000);
<?php endif; ?>

// Show welcome message
setTimeout(() => {
    const hour = new Date().getHours();
    let greeting = 'Bună ziua';
    if (hour < 12) greeting = 'Bună dimineața';
    else if (hour >= 18) greeting = 'Bună seara';
    
    toastr.success(`${greeting}! Dashboard-ul a fost actualizat.`, '', {
        timeOut: 3000,
        closeButton: false
    });
}, 500);
</script>

<style>
@media print {
    .main-sidebar, .main-header, .content-header .float-right, 
    .card-tools, .btn, .small-box-footer {
        display: none !important;
    }
    .content-wrapper {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    .card {
        page-break-inside: avoid;
    }
}
</style>
<?php include_once("WEB-INF/footer.php"); ?>