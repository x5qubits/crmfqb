<?php
include_once("config.php");
$pageName = "Campaign Manager";
$pageId = 8;
include_once("db.php");

function current_user_id(){
    if (isset($_SESSION['user_id'])) return (int)$_SESSION['user_id'];
    if (isset($GLOBALS['user_id'])) return (int)$GLOBALS['user_id'];
    return 1;
}
$UID = current_user_id();
$schema = __DIR__ . '/sql/campains_schema.sql';
if (file_exists($schema)) { $pdo->exec(file_get_contents($schema)); }

function json_response($data){ 
    header('Content-Type: application/json'); 
    echo json_encode($data); 
    exit; 
}

// Include API handlers
if (!empty($_POST['ajax']) || !empty($_GET['ajax'])) {
    include_once('campaigns_api.php');
    exit;
}

// Include export handlers
if (isset($_GET['export'])) {
    include_once('campaigns_export.php');
    exit;
}

include_once("WEB-INF/menu.php");
?>
<link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
<link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">
<link rel="stylesheet" href="plugins/toastr/toastr.min.css"> 
<style>
.stats-card {
  border-radius: 15px;
  padding: 30px 20px;
  color: white;
  text-align: center;
  margin-bottom: 20px;
  box-shadow: 0 8px 16px rgba(0,0,0,0.3);
  transition: transform 0.2s;
}
.stats-card:hover { transform: translateY(-5px); }
.stats-card h3 { font-size: 2.5rem; margin: 0; font-weight: bold; }
.stats-card p { margin: 10px 0 0; font-size: 0.9rem; opacity: 0.9; }

.status-DRAFT { background: #6c757d; color: #fff; }
.status-ACTIVE { background: #28a745; color: #fff; }
.status-PAUSED { background: #ffc107; color: #000; }
.status-DONE { background: #17a2b8; color: #fff; }
.status-QUEUE { background: #007bff; color: #fff; }
.status-SENT { background: #28a745; color: #fff; }
.status-SKIP { background: #6c757d; color: #fff; }
.status-ERROR { background: #dc3545; color: #fff; }

.nav-item { cursor: pointer; }

.filter-controls {
  background: #000;
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 15px;
  border: 1px solid #dee2e6;
}

.csv-mapping-container {
  background: #000;
  border-radius: 8px;
  padding: 15px;
  margin: 15px 0;
}
.csv-field-row {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
  gap: 10px;
}
.csv-field-row label {
  min-width: 150px;
  margin: 0;
  font-weight: 600;
}
.sample-data {
  background: #000;
  padding: 10px;
  border-radius: 4px;
  margin-top: 15px;
  border: 1px solid #dee2e6;
}
.sample-data-table {
  width: 100%;
  font-size: 0.85rem;
}
.sample-data-table th {
  background: #212121;
  padding: 8px;
  border: 1px solid #dee2e6;
}
.sample-data-000 td {
  background: #ffffff;
  padding: 8px;
  border: 1px solid #dee2e6;
}
.btn-action { margin-right: 3px; }
</style>

<?php include_once('includes/campaigns_html.html'); ?>
<?php include_once('includes/campaigns_modals.html'); ?>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>
<script src="plugins/toastr/toastr.min.js"></script>
<script src="js/campaigns_scripts.js"></script>

<?php include_once("WEB-INF/footer.php"); ?>