<?php
include_once("config.php");
$pageName = "Dashboard Financiar";
$pageId = 0;
$pageIds = 0;
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

function formatMoney($val) {
    return number_format((float)$val, 2, ',', '.');
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
    SUM(CASE WHEN DATE(received_at) = CURDATE() THEN 1 ELSE 0 END) as today_emails
    FROM emails WHERE user_id = ?");
$stmt->execute([$user_id]);
$emailStats = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. CALENDAR STATISTICS
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_events,
    SUM(CASE WHEN DATE(start) = CURDATE() THEN 1 ELSE 0 END) as today_events,
    SUM(CASE WHEN DATE(start) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as week_events,
    SUM(CASE WHEN type = 'todo' THEN 1 ELSE 0 END) as todo_count,
    SUM(CASE WHEN type = 'meeting' THEN 1 ELSE 0 END) as meeting_count,
    SUM(CASE WHEN type = 'deadline' THEN 1 ELSE 0 END) as deadline_count,
    SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent_count,
    SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_count
    FROM calendar_events WHERE user_id = ?");
$stmt->execute([$user_id]);
$calendarStats = $stmt->fetch(PDO::FETCH_ASSOC);

// 3. COMPANIES & CONTACTS
$companiesCount = (int)$pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn();
$contactsCount = (int)$pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();

// 4. CONTRACTS STATISTICS WITH FINANCIAL DATA
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_contracts,
    COALESCE(SUM(total_value), 0) as total_contracts_value,
    COALESCE(SUM(CASE WHEN YEAR(contract_date) = YEAR(CURDATE()) THEN total_value ELSE 0 END), 0) as this_year_contracts,
    SUM(CASE WHEN DATE_ADD(contract_date, INTERVAL duration_months MONTH) >= CURDATE() THEN 1 ELSE 0 END) as active_contracts,
    SUM(CASE WHEN DATE_ADD(contract_date, INTERVAL duration_months MONTH) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as expiring_soon
    FROM contracts WHERE user_id = ?");
$stmt->execute([$user_id]);
$contractStats = $stmt->fetch(PDO::FETCH_ASSOC);

// 5. OFFERS STATISTICS WITH FINANCIAL DATA
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_offers,
    COALESCE(SUM(total_value), 0) as total_offers_value,
    COALESCE(SUM(CASE WHEN YEAR(offer_date) = YEAR(CURDATE()) THEN total_value ELSE 0 END), 0) as this_year_offers,
    SUM(CASE WHEN YEAR(offer_date) = YEAR(CURDATE()) THEN 1 ELSE 0 END) as this_year_offers_count
    FROM offers WHERE user_id = ?");
$stmt->execute([$user_id]);
$offerStats = $stmt->fetch(PDO::FETCH_ASSOC);

// 6. INVOICES STATISTICS (if oblio_invoices table exists)
$invoiceStats = ['total_invoices' => 0, 'total_invoices_value' => 0, 'this_year_invoices' => 0];
try {
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as total_invoices,
        COALESCE(SUM(total), 0) as total_invoices_value,
        COALESCE(SUM(CASE WHEN YEAR(date) = YEAR(CURDATE()) THEN total ELSE 0 END), 0) as this_year_invoices
        FROM oblio_invoices WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $invoiceStats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table might not exist
}

// 7. MONTHLY FINANCIAL TRENDS (Last 12 months)
$monthlyFinancial = [];
try {
    $stmt = $pdo->prepare("SELECT 
        DATE_FORMAT(contract_date, '%Y-%m') as month,
        COALESCE(SUM(total_value), 0) as contracts_value
        FROM contracts
        WHERE user_id = ? AND contract_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY month
        ORDER BY month ASC");
    $stmt->execute([$user_id]);
    $contractsMonthly = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $stmt = $pdo->prepare("SELECT 
        DATE_FORMAT(offer_date, '%Y-%m') as month,
        COALESCE(SUM(total_value), 0) as offers_value
        FROM offers
        WHERE user_id = ? AND offer_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY month
        ORDER BY month ASC");
    $stmt->execute([$user_id]);
    $offersMonthly = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Merge data
    $allMonths = array_unique(array_merge(array_keys($contractsMonthly), array_keys($offersMonthly)));
    sort($allMonths);
    foreach ($allMonths as $month) {
        $monthlyFinancial[] = [
            'month' => $month,
            'contracts' => $contractsMonthly[$month] ?? 0,
            'offers' => $offersMonthly[$month] ?? 0
        ];
    }
} catch (PDOException $e) {
    $monthlyFinancial = [];
}

// 8. UPCOMING EVENTS
$stmt = $pdo->prepare("SELECT id, type, title, start, priority
    FROM calendar_events 
    WHERE user_id = ? AND start >= NOW() AND start <= DATE_ADD(NOW(), INTERVAL 7 DAY)
    ORDER BY start ASC LIMIT 10");
$stmt->execute([$user_id]);
$upcomingEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 9. TODAY'S EVENTS
$stmt = $pdo->prepare("SELECT id, type, title, start, all_day, priority
    FROM calendar_events 
    WHERE user_id = ? AND DATE(start) = CURDATE()
    ORDER BY start ASC");
$stmt->execute([$user_id]);
$todayEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 10. URGENT TASKS
$stmt = $pdo->prepare("SELECT id, type, title, start, priority
    FROM calendar_events 
    WHERE user_id = ? AND priority IN ('urgent', 'high') AND start >= NOW()
    ORDER BY start ASC LIMIT 10");
$stmt->execute([$user_id]);
$urgentTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 11. RECENT COMPANIES
$recentCompanies = $pdo->query("SELECT CUI, Name, Adress FROM companies ORDER BY Name ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// 12. CONTRACTS/OFFERS BY STATUS
$stmt = $pdo->prepare("SELECT 
    SUM(CASE WHEN MONTH(contract_date) = MONTH(CURDATE()) THEN 1 ELSE 0 END) as this_month_contracts,
    SUM(CASE WHEN MONTH(contract_date) = MONTH(CURDATE()) - 1 THEN 1 ELSE 0 END) as last_month_contracts
    FROM contracts WHERE user_id = ?");
$stmt->execute([$user_id]);
$contractMonthly = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!-- CSS -->
<link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="plugins/chart.js/Chart.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">
<link rel="stylesheet" href="plugins/toastr/toastr.min.css">


<style>
.stat-card {
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: move;
}
.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}
.sortable-ghost {
    opacity: 0.4;
    background: #f4f6f9;
}
.chart-container {
    position: relative;
    height: 300px;
}
.financial-highlight {
    font-size: 1.8rem;
    font-weight: bold;
    color: #28a745;
}
@media print {
    .main-sidebar, .main-header, .content-header .float-right, 
    .card-tools, .btn {
        display: none !important;
    }
}
</style>

<!-- Page Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-tachometer-alt"></i> Dashboard Financiar</h1>
            </div>
            <div class="col-sm-6">
                <div class="float-right">
                    <button class="btn btn-sm btn-warning" id="resetDashboard">
                        <i class="fas fa-undo"></i> Resetează Layout
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="location.reload()">
                        <i class="fas fa-sync-alt"></i> Actualizează
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<section class="content">
    <div class="container-fluid">
        
        <!-- SORTABLE DASHBOARD ROW -->
        <div id="dashboardRow" class="row">
            
            <!-- PANEL: FINANCIAL OVERVIEW -->
            <div class="col-lg-12" data-xid="panel-financial">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line"></i> Situație Financiară
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="info-box bg-success">
                                    <span class="info-box-icon"><i class="fas fa-file-contract"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Contracte Total</span>
                                        <span class="info-box-number"><?= formatMoney($contractStats['total_contracts_value']) ?> RON</span>
                                        <div class="progress"><div class="progress-bar" style="width: 100%"></div></div>
                                        <span class="progress-description">
                                            <?= (int)$contractStats['total_contracts'] ?> contracte
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="info-box bg-info">
                                    <span class="info-box-icon"><i class="fas fa-handshake"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Oferte Total</span>
                                        <span class="info-box-number"><?= formatMoney($offerStats['total_offers_value']) ?> RON</span>
                                        <div class="progress"><div class="progress-bar" style="width: 100%"></div></div>
                                        <span class="progress-description">
                                            <?= (int)$offerStats['total_offers'] ?> oferte
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="info-box bg-warning">
                                    <span class="info-box-icon"><i class="fas fa-calendar-alt"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Contracte <?= date('Y') ?></span>
                                        <span class="info-box-number"><?= formatMoney($contractStats['this_year_contracts']) ?> RON</span>
                                        <div class="progress"><div class="progress-bar bg-warning" style="width: <?= $contractStats['total_contracts_value'] > 0 ? round(($contractStats['this_year_contracts'] / $contractStats['total_contracts_value']) * 100) : 0 ?>%"></div></div>
                                        <span class="progress-description">
                                            <?= round(($contractStats['total_contracts_value'] > 0 ? ($contractStats['this_year_contracts'] / $contractStats['total_contracts_value']) * 100 : 0), 1) ?>% din total
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="info-box bg-danger">
                                    <span class="info-box-icon"><i class="fas fa-receipt"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Facturi <?= date('Y') ?></span>
                                        <span class="info-box-number"><?= formatMoney($invoiceStats['this_year_invoices']) ?> RON</span>
                                        <div class="progress"><div class="progress-bar bg-danger" style="width: 70%"></div></div>
                                        <span class="progress-description">
                                            <?= (int)$invoiceStats['total_invoices'] ?> facturi emise
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>



            <!-- PANEL: FINANCIAL CHARTS -->
            <div class="col-lg-6" data-xid="panel-monthly-trends">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-bar"></i> Tendințe Lunare (Ultimele 12 luni)
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="monthlyFinancialChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PANEL: FINANCIAL BREAKDOWN PIE -->
            <div class="col-lg-6" data-xid="panel-breakdown">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-pie"></i> Distribuție Venituri
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="revenueBreakdownChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PANEL: TODAY'S EVENTS -->
            <div class="col-lg-6" data-xid="panel-today-events">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calendar-day"></i> Evenimente astăzi
                        </h3>
                    </div>
                    <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                        <?php if (empty($todayEvents)): ?>
                            <div class="p-3 text-center text-muted">
                                <i class="fas fa-calendar-check fa-3x mb-2"></i>
                                <p>Nu există evenimente astăzi</p>
                            </div>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($todayEvents as $ev): 
                                    $dt = new DateTime($ev['start'], $tz);
                                    $label = $ev['all_day'] ? 'Toată ziua' : $dt->format('H:i');
                                ?>
                                <li class="list-group-item">
                                    <strong><?= $label ?></strong> — <?= e($ev['title']) ?>
                                    <span class="badge badge-<?= $ev['priority'] === 'urgent' ? 'danger' : 'secondary' ?> float-right">
                                        <?= ucfirst($ev['type']) ?>
                                    </span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- PANEL: UPCOMING EVENTS -->
            <div class="col-lg-6" data-xid="panel-upcoming">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-arrow-right"></i> Următoarele 7 zile
                        </h3>
                    </div>
                    <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                        <?php if (empty($upcomingEvents)): ?>
                            <div class="p-3 text-center text-muted">
                                <i class="fas fa-check-circle fa-3x mb-2"></i>
                                <p>Nu există evenimente programate</p>
                            </div>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($upcomingEvents as $ev): 
                                    $dt = new DateTime($ev['start'], $tz);
                                ?>
                                <li class="list-group-item">
                                    <strong><?= $dt->format('d.m.Y H:i') ?></strong> — <?= e($ev['title']) ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- PANEL: STATISTICS SUMMARY -->
            <div class="col-lg-12" data-xid="panel-summary">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle"></i> Rezumat Statistic
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h5><i class="fas fa-euro-sign text-success"></i> Situație Financiară</h5>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success"></i> <strong><?= formatMoney($contractStats['this_year_contracts']) ?> RON</strong> contracte anul acesta</li>
                                    <li><i class="fas fa-check text-info"></i> <strong><?= formatMoney($offerStats['this_year_offers']) ?> RON</strong> oferte anul acesta</li>
                                    <li><i class="fas fa-check text-warning"></i> <strong><?= (int)$offerStats['this_year_offers_count'] ?></strong> oferte trimise</li>
                                    <li><i class="fas fa-check text-danger"></i> <strong><?= formatMoney($invoiceStats['this_year_invoices']) ?> RON</strong> facturi emise</li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <h5><i class="fas fa-calendar text-primary"></i> Evenimente</h5>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-primary"></i> <strong><?= (int)$calendarStats['today_events'] ?></strong> astăzi</li>
                                    <li><i class="fas fa-check text-success"></i> <strong><?= (int)$calendarStats['week_events'] ?></strong> săptămâna aceasta</li>
                                    <li><i class="fas fa-check text-warning"></i> <strong><?= (int)$calendarStats['urgent_count'] ?></strong> urgente</li>
                                    <li><i class="fas fa-check text-info"></i> <strong><?= (int)$calendarStats['todo_count'] ?></strong> TODO-uri</li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <h5><i class="fas fa-database text-secondary"></i> Bază de Date</h5>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check"></i> <strong><?= $companiesCount ?></strong> companii</li>
                                    <li><i class="fas fa-check"></i> <strong><?= $contactsCount ?></strong> contacte</li>
                                    <li><i class="fas fa-check"></i> <strong><?= (int)$contractStats['total_contracts'] ?></strong> contracte</li>
                                    <li><i class="fas fa-check"></i> <strong><?= (int)$emailStats['unread_emails'] ?></strong> email-uri necitite</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /#dashboardRow -->

    </div>
</section>

<!-- Scripts -->
<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/chart.js/Chart.min.js"></script>
<script src="plugins/toastr/toastr.min.js"></script>
<script src="plugins/Sortable/Sortable.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>

<script>
const userId = <?= $user_id ?>;
const monthlyFinancialData = <?= json_encode($monthlyFinancial) ?>;

// Sortable Dashboard with localStorage
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('dashboardRow');
    const resetButton = document.getElementById('resetDashboard');
    
    const defaultPanels = [
        { id: 'panel-financial', visible: true },
        { id: 'panel-emails', visible: true },
        { id: 'panel-events', visible: true },
        { id: 'panel-urgent', visible: true },
        { id: 'panel-contracts', visible: true },
        { id: 'panel-monthly-trends', visible: true },
        { id: 'panel-breakdown', visible: true },
        { id: 'panel-today-events', visible: true },
        { id: 'panel-upcoming', visible: true },
        { id: 'panel-summary', visible: true }
    ];
    
    if (!container) {
        console.error('Dashboard container not found!');
        return;
    }
    
    // Reset button
    if (resetButton) {
        resetButton.addEventListener('click', function () {
            localStorage.removeItem('dashboardPanels');
            location.reload();
        });
    }
    
    // Load saved state or use defaults
    const savedPanels = JSON.parse(localStorage.getItem('dashboardPanels')) || defaultPanels;
    
    savedPanels.forEach(({ id, visible }) => {
        const el = document.querySelector(`[data-xid="${id}"]`);
        if (!el) return;
        
        // Append in saved order
        container.appendChild(el);
        
        // Show/hide
        el.style.display = visible ? '' : 'none';
        
        // Add toggle button
        let header = el.querySelector('.card-header') || el.querySelector('.small-box');
        if (header && !header.querySelector('.toggle-visibility')) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-sm btn-outline-light float-right toggle-visibility';
            btn.textContent = visible ? 'Ascunde' : 'Arată';
            btn.style.marginLeft = '10px';
            btn.addEventListener('click', () => {
                const currentlyVisible = el.style.display !== 'none';
                el.style.display = currentlyVisible ? 'none' : '';
                btn.textContent = currentlyVisible ? 'Arată' : 'Ascunde';
                saveState();
            });
            header.appendChild(btn);
        }
    });
    

    
    function saveState() {
        const panels = [];
        container.querySelectorAll('[data-xid]').forEach(el => {
            panels.push({
                id: el.getAttribute('data-xid'),
                visible: el.style.display !== 'none'
            });
        });
        localStorage.setItem('dashboardPanels', JSON.stringify(panels));
    }
    
    // Initialize charts after layout is set
    setTimeout(initCharts, 100);
});

// Initialize Charts
function initCharts() {
    // Monthly Financial Trends Chart
    if (monthlyFinancialData && monthlyFinancialData.length > 0) {
        const labels = monthlyFinancialData.map(d => {
            const [year, month] = d.month.split('-');
            const date = new Date(year, month - 1);
            return date.toLocaleDateString('ro-RO', { month: 'short', year: 'numeric' });
        });
        const contractsData = monthlyFinancialData.map(d => parseFloat(d.contracts || 0));
        const offersData = monthlyFinancialData.map(d => parseFloat(d.offers || 0));
        
        const ctx = document.getElementById('monthlyFinancialChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Contracte (RON)',
                            data: contractsData,
                            backgroundColor: 'rgba(40, 167, 69, 0.7)',
                            borderColor: 'rgba(40, 167, 69, 1)',
                            borderWidth: 2
                        },
                        {
                            label: 'Oferte (RON)',
                            data: offersData,
                            backgroundColor: 'rgba(23, 162, 184, 0.7)',
                            borderColor: 'rgba(23, 162, 184, 1)',
                            borderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString('ro-RO') + ' RON';
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + 
                                           context.parsed.y.toLocaleString('ro-RO', {
                                               minimumFractionDigits: 2,
                                               maximumFractionDigits: 2
                                           }) + ' RON';
                                }
                            }
                        }
                    }
                }
            });
        }
    }
    
    // Revenue Breakdown Pie Chart
    const contractsValue = <?= (float)$contractStats['this_year_contracts'] ?>;
    const offersValue = <?= (float)$offerStats['this_year_offers'] ?>;
    const invoicesValue = <?= (float)$invoiceStats['this_year_invoices'] ?>;
    
    const ctx2 = document.getElementById('revenueBreakdownChart');
    if (ctx2 && (contractsValue > 0 || offersValue > 0 || invoicesValue > 0)) {
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Contracte', 'Oferte', 'Facturi'],
                datasets: [{
                    data: [contractsValue, offersValue, invoicesValue],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(23, 162, 184, 0.8)',
                        'rgba(220, 53, 69, 0.8)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(23, 162, 184, 1)',
                        'rgba(220, 53, 69, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return label + ': ' + value.toLocaleString('ro-RO', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }) + ' RON (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
}

// Show notifications
<?php if ($calendarStats['urgent_count'] > 0): ?>
setTimeout(() => {
    toastr.warning('Aveți <?= (int)$calendarStats['urgent_count'] ?> task-uri urgente!', 'Atenție', {
        timeOut: 5000,
        closeButton: true
    });
}, 1000);
<?php endif; ?>

<?php if ($contractStats['expiring_soon'] > 0): ?>
setTimeout(() => {
    toastr.error('<?= (int)$contractStats['expiring_soon'] ?> contracte vor expira în 30 zile!', 'Important', {
        timeOut: 7000,
        closeButton: true
    });
}, 2000);
<?php endif; ?>

// Welcome message with financial summary
setTimeout(() => {
    const hour = new Date().getHours();
    let greeting = 'Bună ziua';
    if (hour < 12) greeting = 'Bună dimineața';
    else if (hour >= 18) greeting = 'Bună seara';
    
    const totalRevenue = <?= (float)$contractStats['this_year_contracts'] + (float)$offerStats['this_year_offers'] ?>;
    toastr.success(`${greeting}! Venituri <?= date('Y') ?>: ${totalRevenue.toLocaleString('ro-RO', {minimumFractionDigits: 2})} RON`, 'Dashboard Actualizat', {
        timeOut: 4000,
        closeButton: true
    });
}, 500);
</script>

<?php include_once("WEB-INF/footer.php"); ?>