<?php
include_once("config.php");
$pageName = "Termene Leads";
$pageId = 19;
include_once("db.php");

function current_user_id(){
    if (isset($_SESSION['user_id'])) return (int)$_SESSION['user_id'];
    if (isset($GLOBALS['user_id'])) return (int)$GLOBALS['user_id'];
    return 1;
}
$UID = current_user_id();

// auto-create schema
if (file_exists(__DIR__ . '/sql/termene_leads.sql')) {
    try { $pdo->exec(file_get_contents(__DIR__ . '/sql/termene_leads.sql')); } catch (Throwable $e) {}
}

include_once("WEB-INF/menu.php");
?>
<link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
<link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">
<link rel="stylesheet" href="plugins/toastr/toastr.min.css">

<style>
  .filter-row .form-group { margin-bottom: .5rem; }
  .lead-selected { background: #fff3cd !important; }
</style>

<?php include_once('includes/termene_leads_html.html'); ?>
<?php include_once('includes/termene_leads_modals.html'); ?>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="plugins/toastr/toastr.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>
<script>
  const USER_ID = <?php echo (int)$UID; ?>;
</script>
<script src="js/termene_leads.js"></script>

<?php include_once("WEB-INF/footer.php"); ?>
