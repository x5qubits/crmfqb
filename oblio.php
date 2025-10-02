<?php
include_once("config.php");
require_once("oblio_api.php");

$pageName = "Setări Oblio";
$pageId = 6;
include_once("WEB-INF/menu.php"); 

$oblio = new OblioAPI($pdo);
$oblioConfigured = $oblio->isConfigured();
$user_id = $_SESSION['user_id'];
?>

<link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
<link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">
<link rel="stylesheet" href="plugins/toastr/toastr.min.css"> 

<div class="row">
    <!-- Settings Card -->
    <div class="col-md-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cog"></i> Configurare Oblio API</h3>
            </div>
            <div class="card-body">
                <?php if ($oblioConfigured): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Conectat cu succes la Oblio
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> Oblio nu este configurat
                    </div>
                <?php endif; ?>

                <form id="oblioSettingsForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="oblioEmail">Email Oblio <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="oblioEmail" name="oblio_email" 
                                       placeholder="email@example.com" required>
                                <small class="form-text text-muted">Email-ul cu care vă autentificați în Oblio</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="oblioSecret">API Secret <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="oblioSecret" name="oblio_secret" 
                                       placeholder="Secret API" required>
                                <small class="form-text text-muted">
                                    Găsiți în cont la <strong>Setări > Date Cont</strong>
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 
                                Pentru a selecta firma și CIF-ul, salvați mai întâi credențialele de mai sus.
                            </div>
                        </div>
                    </div>
                    
                    <div id="firmSelection" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="oblioCompany">Selectează Firma <span class="text-danger">*</span></label>
                                    <select class="form-control" id="oblioCompany" name="oblio_company" required>
                                        <option value="">-- Selectează firma --</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="oblioCif">CIF Firmă <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="oblioCif" name="oblio_cif" 
                                           placeholder="RO12345678" readonly required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Salvează Setări
                            </button>
                            <button type="button" id="btnTestConnection" class="btn btn-info" style="display: none;">
                                <i class="fas fa-plug"></i> Testează Conexiunea
                            </button>
                            <button type="button" id="btnLoadFirms" class="btn btn-success" style="display: none;">
                                <i class="fas fa-building"></i> Încarcă Firme
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- VAT Rates Card -->
    <div class="col-md-6">
        <div class="card card-info" id="vatRatesCard" style="display: none;">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-percent"></i> Cote TVA Disponibile</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" id="btnRefreshVat">
                        <i class="fas fa-sync"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Denumire</th>
                                <th>Procent</th>
                                <th>Implicit</th>
                            </tr>
                        </thead>
                        <tbody id="vatRatesTable">
                            <tr>
                                <td colspan="3" class="text-center">
                                    <i class="fas fa-spinner fa-spin"></i> Se încarcă...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Series Card -->
    <div class="col-md-6">
        <div class="card card-success" id="seriesCard" style="display: none;">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list-ol"></i> Serii Documente</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" id="btnRefreshSeries">
                        <i class="fas fa-sync"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Tip</th>
                                <th>Serie</th>
                                <th>Start</th>
                                <th>Următor</th>
                                <th>Implicit</th>
                            </tr>
                        </thead>
                        <tbody id="seriesTable">
                            <tr>
                                <td colspan="5" class="text-center">
                                    <i class="fas fa-spinner fa-spin"></i> Se încarcă...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sync Section -->
<div class="row">
    <div class="col-md-12">
        <div class="card card-warning" id="syncCard" style="display: none;">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-sync-alt"></i> Sincronizare Date</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-box bg-light">
                            <span class="info-box-icon"><i class="fas fa-users"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Clienți</span>
                                <span class="info-box-number">Sincronizează baza de date</span>
                                <button type="button" class="btn btn-sm btn-primary mt-2" id="btnSyncClients">
                                    <i class="fas fa-download"></i> Sincronizează Clienți
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box bg-light">
                            <span class="info-box-icon"><i class="fas fa-file-invoice"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Facturi</span>
                                <span class="info-box-number">Importă facturi din Oblio</span>
                                <div class="row mt-2">
                                    <div class="col-6">
                                        <select class="form-control form-control-sm" id="syncYear">
                                            <?php 
                                            $currentYear = date('Y');
                                            for ($y = $currentYear; $y >= $currentYear - 5; $y--): 
                                            ?>
                                                <option value="<?= $y ?>"><?= $y ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <button type="button" class="btn btn-sm btn-primary btn-block" id="btnSyncInvoices">
                                            <i class="fas fa-download"></i> Sincronizează
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>
<script src="plugins/toastr/toastr.min.js"></script> 

<script>
const userId = <?= (int)$user_id ?>;

$(document).ready(function() {
    loadSettings();
});

// Load existing settings
function loadSettings() {
    $.get('api_oblio_handlers.php?f=get_oblio_settings&user_id=' + userId, function(resp) {
        if (resp.success && resp.data) {
            $('#oblioEmail').val(resp.data.email);
            $('#oblioCompany').val(resp.data.company);
            $('#oblioCif').val(resp.data.cif);
            
            if (resp.data.configured) {
                $('#btnTestConnection').show();
                $('#btnLoadFirms').show();
                $('#firmSelection').show();
                loadVatRates();
                loadSeries();
                $('#vatRatesCard').show();
                $('#seriesCard').show();
                $('#syncCard').show();
            }
        }
    }, 'json');
}

// Save settings
$('#oblioSettingsForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = $(this).serialize();
    
    $.post('api_oblio_handlers.php?f=save_oblio_settings&user_id=' + userId, formData, function(resp) {
        if (resp.success) {
            toastr.success(resp.message || 'Setări salvate cu succes');
            $('#btnTestConnection').show();
            $('#btnLoadFirms').show();
            $('#firmSelection').show();
            loadVatRates();
            loadSeries();
            $('#vatRatesCard').show();
            $('#seriesCard').show();
            $('#syncCard').show();
        } else {
            toastr.error(resp.error || 'Eroare la salvare');
        }
    }, 'json').fail(function() {
        toastr.error('Eroare de rețea');
    });
});

// Load firms from Oblio
$('#btnLoadFirms').on('click', function() {
    const btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Se încarcă...');
    
    $.get('api_oblio_handlers.php?f=get_oblio_companies&user_id=' + userId, function(resp) {
        btn.prop('disabled', false).html('<i class="fas fa-building"></i> Încarcă Firme');
        
        if (resp.success && resp.data) {
            const select = $('#oblioCompany');
            select.empty().append('<option value="">-- Selectează firma --</option>');
            
            resp.data.forEach(function(company) {
                select.append(
                    $('<option></option>')
                        .val(company.company)
                        .text(company.company + ' (' + company.cif + ')')
                        .data('cif', company.cif.replace(/^RO/i, ''))
                );
            });
            
            toastr.success('Firme încărcate: ' + resp.data.length);
        } else {
            toastr.error(resp.error || 'Eroare la încărcare');
        }
    }, 'json').fail(function() {
        btn.prop('disabled', false).html('<i class="fas fa-building"></i> Încarcă Firme');
        toastr.error('Eroare de rețea');
    });
});

// Update CIF when company is selected and auto-save
$('#oblioCompany').on('change', function() {
    const selectedOption = $(this).find('option:selected');
    const cif = selectedOption.data('cif');
    const company = selectedOption.val();
    
    $('#oblioCif').val(cif);
    
    // Auto-save when company is selected
    if (company && cif) {
        const formData = $('#oblioSettingsForm').serialize();
        
        $.post('api_oblio_handlers.php?f=save_oblio_settings&user_id=' + userId, formData, function(resp) {
            if (resp.success) {
                toastr.success('Firmă selectată și salvată: ' + company);
                loadVatRates();
                loadSeries();
                $('#vatRatesCard').show();
                $('#seriesCard').show();
                $('#syncCard').show();
            } else {
                toastr.error(resp.error || 'Eroare la salvare');
            }
        }, 'json').fail(function() {
            toastr.error('Eroare de rețea');
        });
    }
});

// Test connection
$('#btnTestConnection').on('click', function() {
    const btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Testare...');
    
    $.get('api_oblio_handlers.php?f=get_oblio_companies&user_id=' + userId, function(resp) {
        btn.prop('disabled', false).html('<i class="fas fa-plug"></i> Testează Conexiunea');
        
        if (resp.success) {
            toastr.success('Conexiune reușită! ' + (resp.data ? resp.data.length + ' firme găsite' : ''));
        } else {
            toastr.error(resp.error || 'Conexiune eșuată');
        }
    }, 'json').fail(function() {
        btn.prop('disabled', false).html('<i class="fas fa-plug"></i> Testează Conexiunea');
        toastr.error('Eroare de rețea');
    });
});

// Load VAT rates
function loadVatRates() {
    $.get('api_oblio_handlers.php?f=get_oblio_vat_rates&user_id=' + userId, function(resp) {
        const tbody = $('#vatRatesTable');
        tbody.empty();
        
        if (resp.success && resp.data && resp.data.length > 0) {
            resp.data.forEach(function(vat) {
                tbody.append(`
                    <tr>
                        <td>${vat.name}</td>
                        <td>${vat.percent}%</td>
                        <td>
                            ${vat.default ? '<span class="badge badge-success">Da</span>' : '<span class="badge badge-secondary">Nu</span>'}
                        </td>
                    </tr>
                `);
            });
        } else {
            tbody.html('<tr><td colspan="3" class="text-center">Nu sunt date disponibile</td></tr>');
        }
    }, 'json').fail(function() {
        $('#vatRatesTable').html('<tr><td colspan="3" class="text-center text-danger">Eroare la încărcare</td></tr>');
    });
}

$('#btnRefreshVat').on('click', loadVatRates);

// Load document series
function loadSeries() {
    $.get('api_oblio_handlers.php?f=get_oblio_series&user_id=' + userId, function(resp) {
        const tbody = $('#seriesTable');
        tbody.empty();
        
        if (resp.success && resp.data && resp.data.length > 0) {
            resp.data.forEach(function(series) {
                tbody.append(`
                    <tr>
                        <td>${series.type}</td>
                        <td><strong>${series.name}</strong></td>
                        <td>${series.start}</td>
                        <td>${series.next}</td>
                        <td>
                            ${series.default ? '<span class="badge badge-success">Da</span>' : '<span class="badge badge-secondary">Nu</span>'}
                        </td>
                    </tr>
                `);
            });
        } else {
            tbody.html('<tr><td colspan="5" class="text-center">Nu sunt date disponibile</td></tr>');
        }
    }, 'json').fail(function() {
        $('#seriesTable').html('<tr><td colspan="5" class="text-center text-danger">Eroare la încărcare</td></tr>');
    });
}

$('#btnRefreshSeries').on('click', loadSeries);

// Sync clients
$('#btnSyncClients').on('click', function() {
    const btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sincronizare...');
    
    $.post('api_oblio_handlers.php?f=sync_clients_from_oblio&user_id=' + userId, {}, function(resp) {
        btn.prop('disabled', false).html('<i class="fas fa-download"></i> Sincronizează Clienți');
        
        if (resp.success) {
            toastr.success(resp.message || 'Sincronizare reușită');
        } else {
            toastr.error(resp.error || 'Eroare la sincronizare');
        }
    }, 'json').fail(function() {
        btn.prop('disabled', false).html('<i class="fas fa-download"></i> Sincronizează Clienți');
        toastr.error('Eroare de rețea');
    });
});

// Sync invoices
$('#btnSyncInvoices').on('click', function() {
    const btn = $(this);
    const year = $('#syncYear').val();
    
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
    
    $.post('api_oblio_handlers.php?f=sync_invoices_from_oblio&user_id=' + userId, {
        year: year
    }, function(resp) {
        btn.prop('disabled', false).html('<i class="fas fa-download"></i> Sincronizează');
        
        if (resp.success) {
            toastr.success(resp.message || 'Sincronizare reușită');
        } else {
            toastr.error(resp.error || 'Eroare la sincronizare');
        }
    }, 'json').fail(function() {
        btn.prop('disabled', false).html('<i class="fas fa-download"></i> Sincronizează');
        toastr.error('Eroare de rețea');
    });
});
</script>

<?php include_once("WEB-INF/footer.php"); ?>