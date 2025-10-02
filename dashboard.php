<?php
include_once("config.php");
$pageName = "Dashboard";
$pageId = 0;
$pageIds = 0;
include_once("WEB-INF/menu.php");
$selected_mkp = isset($_SESSION['user_mps']) ? (int)$_SESSION['user_mps'] : 0;
$user_id_js = isset($user_id) ? (int)$user_id : (isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1);
?>
<link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
<link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">
<link rel="stylesheet" href="plugins/toastr/toastr.min.css">

<?php
include_once("config.php");
$pageName = "Dashboard";
$pageId = 0;
$pageIds = 0;
include_once("WEB-INF/menu.php");
$selected_mkp = isset($_SESSION['user_mps']) ? (int)$_SESSION['user_mps'] : 0;
$user_id_js = isset($user_id) ? (int)$user_id : (isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1);

// make sure $pdo exists
if (!isset($pdo)) {
    require_once __DIR__ . '/db.php';
}
$tz = new DateTimeZone('Europe/Bucharest');
$user_id = $user_id_js;

// Unread emails
$stmt = $pdo->prepare("SELECT COUNT(*) FROM emails WHERE user_id=? AND folder='inbox' AND is_read=0");
$stmt->execute([$user_id]);
$unreadCount = (int)$stmt->fetchColumn();

// Companies
$companiesCount = (int)$pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn();

// Contacts
$contactsCount = (int)$pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();

// Contracts
$stmt = $pdo->prepare("SELECT COUNT(*) FROM contracts WHERE user_id=?");
$stmt->execute([$user_id]);
$contractsCount = (int)$stmt->fetchColumn();

// Today events
$todayStart = (new DateTime('today', $tz))->format('Y-m-d H:i:s');
$tomorrowStart = (new DateTime('tomorrow', $tz))->format('Y-m-d H:i:s');
$stmt = $pdo->prepare("
    SELECT id, type, title, `start`, `all_day`
    FROM calendar_events
    WHERE user_id=? AND `start` >= ? AND `start` < ?
    ORDER BY `start` ASC
");
$stmt->execute([$user_id, $todayStart, $tomorrowStart]);
$todayEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Latest 5 unread emails
$stmt = $pdo->prepare("
    SELECT id, subject, COALESCE(from_name,'') as from_name, from_email,
           COALESCE(received_at, created_at) as rcv
    FROM emails
    WHERE user_id=? AND folder='inbox' AND is_read=0
    ORDER BY rcv DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$latestUnread = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contracts expiring in 30 days
$stmt = $pdo->prepare("
    SELECT id, contract_number, contract_date, duration_months,
           DATE_ADD(contract_date, INTERVAL duration_months MONTH) AS end_date
    FROM contracts
    WHERE user_id=?
    HAVING end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY end_date ASC
");
$stmt->execute([$user_id]);
$expiringContracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>


<div class="row">
  <div class="col-lg-3 col-6">
    <div class="small-box bg-info">
      <div class="inner">
        <h3><?= (int)$unreadCount ?></h3>
        <p>Unread Emails</p>
      </div>
      <div class="icon"><i class="fas fa-envelope"></i></div>
      <a href="mailbox" class="small-box-footer">Open Mailbox <i class="fas fa-arrow-circle-right"></i></a>
    </div>
  </div>

  <div class="col-lg-3 col-6">
    <div class="small-box bg-success">
      <div class="inner">
        <h3><?= (int)$companiesCount ?></h3>
        <p>Companies</p>
      </div>
      <div class="icon"><i class="fas fa-building"></i></div>
      <a href="firms" class="small-box-footer">Manage Companies <i class="fas fa-arrow-circle-right"></i></a>
    </div>
  </div>

  <div class="col-lg-3 col-6">
    <div class="small-box bg-warning">
      <div class="inner">
        <h3><?= (int)$contactsCount ?></h3>
        <p>Contacts</p>
      </div>
      <div class="icon"><i class="fas fa-user-friends"></i></div>
      <a href="contacts" class="small-box-footer">View Contacts <i class="fas fa-arrow-circle-right"></i></a>
    </div>
  </div>

  <div class="col-lg-3 col-6">
    <div class="small-box bg-danger">
      <div class="inner">
        <h3><?= (int)$contractsCount ?></h3>
        <p>Contracts</p>
      </div>
      <div class="icon"><i class="fas fa-file-signature"></i></div>
      <a href="contracts" class="small-box-footer">Open Contracts <i class="fas fa-arrow-circle-right"></i></a>
    </div>
  </div>
</div>

<div class="row">
  <!-- Today’s Events -->
  <section class="col-lg-6">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title"><i class="far fa-calendar-alt mr-1"></i> Today’s Events</h3>
      </div>
      <div class="card-body p-0">
        <?php if (empty($todayEvents)): ?>
          <ul class="list-group list-group-flush">
            <li class="list-group-item text-muted">No events today</li>
          </ul>
        <?php else: ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($todayEvents as $ev): ?>
              <?php
                $dt = new DateTime($ev['start'], $tz);
                $label = $ev['all_day'] ? 'All day' : $dt->format('H:i');
                $icon = [
                  'meeting'=>'fa-handshake',
                  'deadline'=>'fa-flag',
                  'reminder'=>'fa-bell',
                  'todo'=>'fa-check',
                  'email'=>'fa-envelope'
                ][$ev['type']] ?? 'fa-calendar';
              ?>
              <li class="list-group-item d-flex align-items-center">
                <i class="fas <?= $icon ?> mr-2"></i>
                <div class="flex-fill">
                  <strong><?= e($label) ?></strong> — <?= e($ev['title'] ?: ucfirst($ev['type'])) ?>
                </div>
                <a class="btn btn-xs btn-outline-primary" href="calendar.php?date=<?= $dt->format('Y-m-d') ?>#event-<?= (int)$ev['id'] ?>">Open</a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Latest Unread Emails -->
  <section class="col-lg-6">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title"><i class="far fa-envelope mr-1"></i> Latest Unread Emails</h3>
      </div>
      <div class="card-body p-0">
        <?php if (empty($latestUnread)): ?>
          <ul class="list-group list-group-flush">
            <li class="list-group-item text-muted">No unread emails</li>
          </ul>
        <?php else: ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($latestUnread as $m): ?>
              <li class="list-group-item d-flex align-items-center">
                <div class="flex-fill">
                  <strong><?= e($m['subject']) ?></strong><br>
                  <small>
                    From: <?= e(trim(($m['from_name'] ?? ''))) ?> <?= e($m['from_email']) ?>
                    | <?= e($m['rcv']) ?>
                  </small>
                </div>
                <a class="btn btn-xs btn-outline-primary" href="read_mail.php?id=<?= (int)$m['id'] ?>">Open</a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>

<div class="row">
  <!-- Contracts expiring in 30 days -->
  <section class="col-lg-12">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title"><i class="far fa-clock mr-1"></i> Contracts Expiring in 30 Days</h3>
      </div>
      <div class="card-body p-0">
        <?php if (empty($expiringContracts)): ?>
          <ul class="list-group list-group-flush">
            <li class="list-group-item text-muted">No contracts expiring soon</li>
          </ul>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped mb-0" id="contractsExpiringTable">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Contract</th>
                  <th>Start</th>
                  <th>Duration (months)</th>
                  <th>Ends</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($expiringContracts as $c): ?>
                <tr>
                  <td><?= (int)$c['id'] ?></td>
                  <td><?= e($c['contract_number']) ?></td>
                  <td><?= e($c['contract_date']) ?></td>
                  <td><?= (int)$c['duration_months'] ?></td>
                  <td><?= e($c['end_date']) ?></td>
                  <td><a class="btn btn-xs btn-outline-primary" href="contracts.php?id=<?= (int)$c['id'] ?>">View</a></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>



<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>

<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="plugins/jszip/jszip.min.js"></script>
<script src="plugins/pdfmake/pdfmake.min.js"></script>
<script src="plugins/pdfmake/vfs_fonts.js"></script>
<script src="plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
<script src="plugins/toastr/toastr.min.js"></script>

<script>
$(function () {
  // DataTable only if table exists
  var $tbl = $('#contractsExpiringTable');
  if ($tbl.length) {
    $tbl.DataTable({
      responsive: true,
      lengthChange: false,
      autoWidth: false,
      pageLength: 10,
      buttons: ["copy", "csv", "excel", "pdf", "print", "colvis"]
    }).buttons().container().appendTo('#contractsExpiringTable_wrapper .col-md-6:eq(0)');
  }
});
</script>
<?php include_once("WEB-INF/footer.php"); ?>