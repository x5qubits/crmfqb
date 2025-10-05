<?php
include_once("config.php");
$pageName = "Dashboard Evenimente";
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
// Get filter from URL
$filter = $_GET['filter'] ?? 'all';

// Fetch TODO items based on filter
$whereClause = "user_id = ? AND type = 'todo'";
$params = [$user_id];

switch ($filter) {
    case 'pending':
        $whereClause .= " AND status = 'pending'";
        break;
    case 'completed':
        $whereClause .= " AND status = 'completed'";
        break;
    case 'overdue':
        $whereClause .= " AND start < NOW() AND status != 'completed'";
        break;
    case 'today':
        $whereClause .= " AND DATE(start) = CURDATE()";
        break;
    case 'urgent':
        $whereClause .= " AND priority IN ('urgent', 'high') AND status != 'completed'";
        break;
}

$stmt = $pdo->prepare("SELECT * FROM calendar_events 
    WHERE $whereClause 
    ORDER BY FIELD(status, 'pending', 'completed'), 
             FIELD(priority, 'urgent', 'high', 'medium', 'low'),
             start ASC");
$stmt->execute($params);
$todos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN start < NOW() AND status != 'completed' THEN 1 ELSE 0 END) as overdue,
    SUM(CASE WHEN DATE(start) = CURDATE() THEN 1 ELSE 0 END) as today,
    SUM(CASE WHEN priority IN ('urgent', 'high') AND status != 'completed' THEN 1 ELSE 0 END) as urgent
    FROM calendar_events WHERE user_id = ? AND type = 'todo'");
$stmt->execute([$user_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

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
<section class="content" >
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
                    <a href="calendar" class="small-box-footer">
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
                    <a href="calendar?filter=urgent" class="small-box-footer">
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
        <!-- TODO LIST -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list"></i> 
                            <?php
                            $filterTitles = [
                                'all' => 'Toate TODO-urile',
                                'pending' => 'TODO-uri în Așteptare',
                                'completed' => 'TODO-uri Completate',
                                'overdue' => 'TODO-uri Întârziate',
                                'today' => 'TODO-uri pentru Astăzi',
                                'urgent' => 'TODO-uri Urgente'
                            ];
                            echo $filterTitles[$filter] ?? 'Toate TODO-urile';
                            ?>
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-primary"><?= count($todos) ?> rezultate</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($todos)): ?>
                            <div class="p-4 text-center text-muted">
                                <i class="fas fa-clipboard-check fa-3x mb-3"></i>
                                <p>Nu există TODO-uri pentru acest filtru.</p>
                                <button class="btn btn-primary" id="btnAddTodoEmpty">
                                    <i class="fas fa-plus"></i> Adaugă primul TODO
                                </button>
                            </div>
                        <?php else: ?>
                            <ul class="todo-list" data-widget="todo-list" id="todoList">
                                <?php foreach ($todos as $todo): 
                                    $dt = new DateTime($todo['start'], $tz);
                                    $isOverdue = strtotime($todo['start']) < time() && $todo['status'] != 'completed';
                                    $isCompleted = $todo['status'] == 'completed';
                                    $priorityClass = 'priority-' . $todo['priority'];
                                ?>
                                <li class="todo-item <?= $priorityClass ?> <?= $isCompleted ? 'completed' : '' ?>" 
                                    data-id="<?= $todo['id'] ?>">
                                    <span class="handle">
                                        <i class="fas fa-ellipsis-v"></i>
                                        <i class="fas fa-ellipsis-v"></i>
                                    </span>
                                    
                                    <div class="icheck-primary d-inline ml-2">
                                        <input type="checkbox" 
                                               value="<?= $todo['id'] ?>" 
                                               name="todo<?= $todo['id'] ?>" 
                                               id="todoCheck<?= $todo['id'] ?>"
                                               <?= $isCompleted ? 'checked' : '' ?>
                                               onchange="toggleTodoStatus(<?= $todo['id'] ?>, this.checked)">
                                        <label for="todoCheck<?= $todo['id'] ?>"></label>
                                    </div>
                                    
                                    <span class="text <?= $isCompleted ? 'text-decoration-line-through' : '' ?>">
                                        <?= e($todo['title']) ?>
                                    </span>
                                    
                                    <?php if ($isOverdue): ?>
                                        <small class="badge badge-danger">
                                            <i class="far fa-clock"></i> ÎNTÂRZIAT
                                        </small>
                                    <?php else: ?>
                                        <small class="badge badge-<?php
                                            $colors = ['urgent' => 'danger', 'high' => 'warning', 'medium' => 'info', 'low' => 'secondary'];
                                            echo $colors[$todo['priority']] ?? 'info';
                                        ?>">
                                            <?= $dt->format('d.m.Y H:i') ?>
                                        </small>
                                    <?php endif; ?>
                                    
                                    <div class="tools">
                                        <button class="btn btn-sm btn-info" onclick="viewTodo(<?= $todo['id'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-primary" onclick="editTodo(<?= $todo['id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteTodo(<?= $todo['id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer clearfix">
                        <button type="button" class="btn btn-primary float-right" id="btnAddTodoFooter">
                            <i class="fas fa-plus"></i> Adaugă TODO
                        </button>
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
                                        <a href="calendar?date=<?= $dt->format('Y-m-d') ?>" class="btn btn-xs btn-outline-primary">
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
                                        <a href="calendar" class="btn btn-xs btn-outline-success">
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
        <div class="row" style="display:none">
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
                                        <a href="read_mail?id=<?= (int)$mail['id'] ?>" class="btn btn-xs btn-primary">
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
                                        <a href="calendar" class="btn btn-xs btn-outline-<?= $priorityBadge ?>">
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


            <!-- RECENT COMPANIES -->
            <div class="col-lg-4" style="display:none">
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
		            <div class="col-lg-6">
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
                                                <a href="contracts?id=<?= (int)$contract['id'] ?>" class="btn btn-xs btn-primary">
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
            <div class="col-lg-6">
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
<!-- ADD/EDIT TODO MODAL -->
<div class="modal fade" id="todoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white" id="todoModalTitle">
                    <i class="fas fa-plus"></i> Adaugă TODO
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="todoForm">
                <div class="modal-body">
                    <input type="hidden" id="todo_id" name="todo_id">
                    
                    <div class="form-group">
                        <label for="todo_title">Titlu <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="todo_title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="todo_description">Descriere</label>
                        <textarea class="form-control" id="todo_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="todo_start">Data și Ora Scadență <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="todo_start" name="start" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="todo_priority">Prioritate</label>
                                <select class="form-control" id="todo_priority" name="priority">
                                    <option value="low">Scăzută</option>
                                    <option value="medium" selected>Medie</option>
                                    <option value="high">Ridicată</option>
                                    <option value="urgent">Urgentă</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="todo_location">Locație</label>
                        <input type="text" class="form-control" id="todo_location" name="location">
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="todo_all_day" name="all_day">
                            <label class="custom-control-label" for="todo_all_day">Toată ziua</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Anulează
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvează
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- VIEW TODO MODAL -->
<div class="modal fade" id="viewTodoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white">
                    <i class="fas fa-eye"></i> Detalii TODO
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="viewTodoContent">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Închide</button>
                <button type="button" class="btn btn-primary" onclick="editTodoFromView()">
                    <i class="fas fa-edit"></i> Editează
                </button>
            </div>
        </div>
    </div>
</div>



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
<script>
let currentViewTodoId = null;

$(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Add TODO button handlers
    $('#btnAddTodo, #btnAddTodoEmpty, #btnAddTodoFooter').on('click', function() {
        openTodoModal();
    });
    
    // Form submission
    $('#todoForm').on('submit', function(e) {
        e.preventDefault();
        saveTodo();
    });
});

function filterTodos(filter) {
    window.location.href = 'todos.php?filter=' + filter;
}

function openTodoModal(todoId = null) {
    if (todoId) {
        // Load TODO data for editing
        $.get('api/get_todo.php', { id: todoId }, function(response) {
            if (response.success) {
                const todo = response.data;
                $('#todoModalTitle').html('<i class="fas fa-edit"></i> Editează TODO');
                $('#todo_id').val(todo.id);
                $('#todo_title').val(todo.title);
                $('#todo_description').val(todo.description || '');
                $('#todo_start').val(todo.start.replace(' ', 'T'));
                $('#todo_priority').val(todo.priority);
                $('#todo_location').val(todo.location || '');
                $('#todo_all_day').prop('checked', todo.all_day == 1);
                $('#todoModal').modal('show');
            } else {
                toastr.error(response.error || 'Eroare la încărcare');
            }
        }, 'json');
    } else {
        // New TODO
        $('#todoModalTitle').html('<i class="fas fa-plus"></i> Adaugă TODO');
        $('#todoForm')[0].reset();
        $('#todo_id').val('');
        
        // Set default date to now
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        $('#todo_start').val(now.toISOString().slice(0, 16));
        
        $('#todoModal').modal('show');
    }
}

function saveTodo() {
    const formData = {
        id: $('#todo_id').val(),
        title: $('#todo_title').val(),
        description: $('#todo_description').val(),
        start: $('#todo_start').val().replace('T', ' ') + ':00',
        priority: $('#todo_priority').val(),
        location: $('#todo_location').val(),
        all_day: $('#todo_all_day').is(':checked') ? 1 : 0,
        type: 'todo'
    };
    
    const url = formData.id ? 'api/update_todo.php' : 'api/create_todo.php';
    
    $.post(url, formData, function(response) {
        if (response.success) {
            toastr.success(formData.id ? 'TODO actualizat cu succes!' : 'TODO adăugat cu succes!');
            $('#todoModal').modal('hide');
            setTimeout(() => location.reload(), 1000);
        } else {
            toastr.error(response.error || 'Eroare la salvare');
        }
    }, 'json').fail(function() {
        toastr.error('Eroare de comunicare cu serverul');
    });
}

function editTodo(id) {
    openTodoModal(id);
}

function viewTodo(id) {
    currentViewTodoId = id;
    
    $.get('api/get_todo.php', { id: id }, function(response) {
        if (response.success) {
            const todo = response.data;
            const priorityColors = {
                urgent: 'danger',
                high: 'warning',
                medium: 'info',
                low: 'secondary'
            };
            
            const html = `
                <div class="row">
                    <div class="col-md-12">
                        <h4>${todo.title}</h4>
                        <hr>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <strong>Prioritate:</strong><br>
                        <span class="badge badge-${priorityColors[todo.priority]}">${todo.priority.toUpperCase()}</span>
                    </div>
                    <div class="col-md-6">
                        <strong>Status:</strong><br>
                        <span class="badge badge-${todo.status === 'completed' ? 'success' : 'warning'}">
                            ${todo.status === 'completed' ? 'COMPLETAT' : 'ÎN AȘTEPTARE'}
                        </span>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <strong>Scadență:</strong><br>
                        ${new Date(todo.start).toLocaleString('ro-RO')}
                    </div>
                    <div class="col-md-6">
                        <strong>Creat la:</strong><br>
                        ${new Date(todo.created_at).toLocaleString('ro-RO')}
                    </div>
                </div>
                
                ${todo.location ? `
                <div class="row mt-3">
                    <div class="col-md-12">
                        <strong>Locație:</strong><br>
                        <i class="fas fa-map-marker-alt"></i> ${todo.location}
                    </div>
                </div>
                ` : ''}
                
                ${todo.description ? `
                <div class="row mt-3">
                    <div class="col-md-12">
                        <strong>Descriere:</strong><br>
                        <div class="border p-3 bg-light rounded">${todo.description}</div>
                    </div>
                </div>
                ` : ''}
            `;
            
            $('#viewTodoContent').html(html);
            $('#viewTodoModal').modal('show');
        } else {
            toastr.error(response.error || 'Eroare la încărcare');
        }
    }, 'json');
}

function editTodoFromView() {
    $('#viewTodoModal').modal('hide');
    setTimeout(() => editTodo(currentViewTodoId), 300);
}

function toggleTodoStatus(id, isCompleted) {
    $.post('api/task_actions.php', {
        action: isCompleted ? 'complete' : 'uncomplete',
        task_id: id
    }, function(response) {
        if (response.success) {
            toastr.success(response.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            toastr.error(response.error || 'Eroare');
            location.reload();
        }
    }, 'json');
}

function deleteTodo(id) {
    if (confirm('Sigur dorești să ștergi acest TODO?')) {
        $.post('api/task_actions.php', {
            action: 'delete',
            task_id: id
        }, function(response) {
            if (response.success) {
                toastr.success('TODO șters cu succes!');
                setTimeout(() => location.reload(), 1000);
            } else {
                toastr.error(response.error || 'Eroare la ștergere');
            }
        }, 'json');
    }
}
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
<script>
$(function() {
    // Show smart notifications
    <?php if ($overdueCount > 0): ?>
    setTimeout(() => {
        toastr.error('Ai <?= $overdueCount ?> task-uri întârziate!', 'Atenție', {
            timeOut: 7000,
            closeButton: true
        });
    }, 1000);
    <?php endif; ?>

    <?php if ($emailStats['unread_today'] > 5): ?>
    setTimeout(() => {
        toastr.info('Ai <?= $emailStats['unread_today'] ?> email-uri noi astăzi', 'Email-uri', {
            timeOut: 5000,
            closeButton: true
        });
    }, 2000);
    <?php endif; ?>

    <?php if (count($nextEvents) > 0 && !empty($nextEvents[0])): 
        $nextEvent = $nextEvents[0];
        $minutesUntil = round((strtotime($nextEvent['start']) - time()) / 60);
        if ($minutesUntil <= 30 && $minutesUntil > 0):
    ?>
    setTimeout(() => {
        toastr.warning('<?= e($nextEvent['title']) ?> începe în <?= $minutesUntil ?> minute!', 'Eveniment Aproape', {
            timeOut: 10000,
            closeButton: true
        });
    }, 3000);
    <?php endif; endif; ?>


});
</script>

<?php include_once("WEB-INF/footer.php"); ?>