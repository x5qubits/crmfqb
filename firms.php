<?php
include_once("config.php");
$pageName = "Companii & Contacte";
$pageId = 1;
$pageIds = isset($_GET['type']) ? (int)$_GET['type']:0;
include_once("WEB-INF/menu.php"); 
$selected_mkp = isset($_SESSION['user_mps']) ? (int)$_SESSION['user_mps'] : 0;
$user_id_js = isset($user_id) ? $user_id : 1;
?>
<link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
<link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">
<link rel="stylesheet" href="plugins/toastr/toastr.min.css"> 
<style>
.dark-mode .bg-fake { background-color:#280000 !important }
.bg-fake { background-color:#ffeded !important }
.offer-item-row input, .offer-item-row textarea { font-size: 0.85rem; padding: 0.3rem 0.5rem; }
.offer-item-row .btn-sm { padding: 0.25rem 0.5rem; }
.autocomplete-suggestions { border: 1px solid #ddd; max-height: 200px; overflow-y: auto; position: fixed; background: white; z-index: 99999; width: 400px; }
.autocomplete-suggestion { padding: 8px; cursor: pointer; }
.autocomplete-suggestion:hover { background-color: #f0f0f0; }
.dark-mode .autocomplete-suggestions { background: #343a40; border-color: #495057; }
.dark-mode .autocomplete-suggestion:hover { background-color: #495057; }
#confirmContactDelete.modal { z-index: 1065; }
.modal-backdrop.confirm-del { z-index: 1060; }
</style>

<div class="row">
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Gestionare Companii</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-primary btn-sm" id="btnAddCompany">
                    <i class="fas fa-plus"></i> Adaugă Companie
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="input-group mb-3">
                <input type="text" id="searchBox" class="form-control" placeholder="Caută companie după CUI, Nume sau Adresă...">
            </div>
            <div id="tableLoader" class="overlay" style="display:none;">
                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
            </div>	
            <table id="companiesTable" class="table table-striped">
                <thead>
                    <tr>
                        <th>CUI</th>
                        <th>Nume</th>
                        <th>Reg.Com.</th>
                        <th>Adresă</th>
                        <th>Acțiuni</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
</div>
<?php include_once("includes/firms_modal.html"); ?>
<?php include_once("includes/invoice_modals.html"); ?>
<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>
<script src="plugins/toastr/toastr.min.js"></script> 

<script>
const USER_ID = <?= $user_id_js ?>;
</script>
<script src="js/firms.js"></script>
<script src="js/firms_invoices.js"></script>

<script>
// Initialize invoice functionality on page load
$(document).ready(function() {
    loadVatRatesForInvoices();
});
</script>

<?php include_once("WEB-INF/footer.php"); ?>