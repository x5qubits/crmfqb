<?php
include_once("config.php");
$pageName = "Contracte";
$pageId = 5;
$pageIds = 3;
include_once("WEB-INF/menu.php");

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
if (!isset($pdo)) {
    require_once __DIR__ . '/db.php';
}

function e($s) { 
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); 
}

// Get filter
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query based on filter
$whereClause = "user_id = ?";
$params = [$user_id];

switch ($filter) {
    case 'active':
        $whereClause .= " AND DATE_ADD(contract_date, INTERVAL duration_months MONTH) >= CURDATE()";
        break;
    case 'expired':
        $whereClause .= " AND DATE_ADD(contract_date, INTERVAL duration_months MONTH) < CURDATE()";
        break;
    case 'expiring':
        $whereClause .= " AND DATE_ADD(contract_date, INTERVAL duration_months MONTH) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)";
        break;
}

if (!empty($search)) {
    $whereClause .= " AND (contract_number LIKE ? OR object LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Fetch contracts
$stmt = $pdo->prepare("SELECT c.*, co.Name as company_name, co.CUI as company_cui_full, co.Adress as company_address,
    DATE_ADD(c.contract_date, INTERVAL c.duration_months MONTH) as end_date,
    DATEDIFF(DATE_ADD(c.contract_date, INTERVAL c.duration_months MONTH), CURDATE()) as days_remaining
    FROM contracts c
    LEFT JOIN companies co ON c.company_cui = co.CUI
    WHERE $whereClause
    ORDER BY c.contract_date DESC");
$stmt->execute($params);
$contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN DATE_ADD(contract_date, INTERVAL duration_months MONTH) >= CURDATE() THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN DATE_ADD(contract_date, INTERVAL duration_months MONTH) < CURDATE() THEN 1 ELSE 0 END) as expired,
    SUM(CASE WHEN DATE_ADD(contract_date, INTERVAL duration_months MONTH) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY) THEN 1 ELSE 0 END) as expiring_soon,
    SUM(total_value) as total_value
    FROM contracts WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all companies for dropdown
$companiesStmt = $pdo->query("SELECT CUI, Name, Reg, Adress FROM companies ORDER BY Name ASC");
$companies = $companiesStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.contract-card {
    transition: all 0.3s;
    border-left: 4px solid #007bff;
}
.contract-card:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}
.contract-card.expired {
    border-left-color: #dc3545;
    opacity: 0.8;
}
.contract-card.expiring {
    border-left-color: #ffc107;
}
.contract-card.active {
    border-left-color: #28a745;
}
.filter-badge {
    cursor: pointer;
    transition: transform 0.2s;
}
.filter-badge:hover {
    transform: scale(1.05);
}
.filter-badge.active {
    box-shadow: 0 0 0 3px rgba(0,123,255,0.25);
}
</style>
<link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="plugins/chart.js/Chart.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">
<link rel="stylesheet" href="plugins/toastr/toastr.min.css">

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-file-contract"></i> Contracte</h1>
            </div>
            <div class="col-sm-6">
                <button class="btn btn-primary float-right" id="btnAddContract">
                    <i class="fas fa-plus"></i> Adaugă Contract
                </button>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        
        <!-- STATISTICS ROW -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info filter-badge <?= $filter == 'all' ? 'active' : '' ?>" 
                     onclick="filterContracts('all')">
                    <div class="inner">
                        <h3><?= $stats['total'] ?></h3>
                        <p>Total Contracte</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-file-contract"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success filter-badge <?= $filter == 'active' ? 'active' : '' ?>" 
                     onclick="filterContracts('active')">
                    <div class="inner">
                        <h3><?= $stats['active'] ?></h3>
                        <p>Active</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning filter-badge <?= $filter == 'expiring' ? 'active' : '' ?>" 
                     onclick="filterContracts('expiring')">
                    <div class="inner">
                        <h3><?= $stats['expiring_soon'] ?></h3>
                        <p>Expiră în 60 zile</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger filter-badge <?= $filter == 'expired' ? 'active' : '' ?>" 
                     onclick="filterContracts('expired')">
                    <div class="inner">
                        <h3><?= $stats['expired'] ?></h3>
                        <p>Expirate</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEARCH BAR -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="contracts.php" class="form-inline">
                            <input type="hidden" name="filter" value="<?= e($filter) ?>">
                            <div class="input-group input-group-lg" style="width: 100%;">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Caută după număr contract sau obiect..." 
                                       value="<?= e($search) ?>">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Caută
                                    </button>
                                    <?php if (!empty($search)): ?>
                                    <a href="contracts.php?filter=<?= e($filter) ?>" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Resetează
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- CONTRACTS LIST -->
        <div class="row">
            <?php if (empty($contracts)): ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center p-5">
                        <i class="fas fa-file-contract fa-4x text-muted mb-3"></i>
                        <h4>Nu există contracte</h4>
                        <p class="text-muted">Nu am găsit contracte pentru acest filtru.</p>
                        <button class="btn btn-primary" id="btnAddContractEmpty">
                            <i class="fas fa-plus"></i> Adaugă primul contract
                        </button>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($contracts as $contract): 
                $isExpired = $contract['days_remaining'] < 0;
                $isExpiring = $contract['days_remaining'] >= 0 && $contract['days_remaining'] <= 60;
                $isActive = $contract['days_remaining'] > 60;
                $cardClass = $isExpired ? 'expired' : ($isExpiring ? 'expiring' : 'active');
            ?>
            <div class="col-lg-6 col-md-12">
                <div class="card contract-card <?= $cardClass ?>">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-file-signature"></i> 
                            <?= e($contract['contract_number']) ?>
                        </h5>
                        <div class="card-tools">
                            <?php if ($isExpired): ?>
                                <span class="badge badge-danger">EXPIRAT</span>
                            <?php elseif ($isExpiring): ?>
                                <span class="badge badge-warning">Expiră în <?= abs($contract['days_remaining']) ?> zile</span>
                            <?php else: ?>
                                <span class="badge badge-success">ACTIV</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <strong><i class="fas fa-building"></i> Client:</strong>
                                <p class="mb-2"><?= e($contract['company_name']) ?></p>
                                
                                <strong><i class="fas fa-info-circle"></i> Obiect:</strong>
                                <p class="mb-2"><?= e(substr($contract['object'], 0, 100)) ?><?= strlen($contract['object']) > 100 ? '...' : '' ?></p>
                                
                                <div class="row">
                                    <div class="col-6">
                                        <strong><i class="far fa-calendar"></i> Data:</strong><br>
                                        <?= date('d.m.Y', strtotime($contract['contract_date'])) ?>
                                    </div>
                                    <div class="col-6">
                                        <strong><i class="fas fa-clock"></i> Durată:</strong><br>
                                        <?= $contract['duration_months'] ?> luni
                                    </div>
                                </div>
                                
                                <?php if ($contract['total_value'] > 0): ?>
                                <div class="mt-2">
                                    <strong><i class="fas fa-money-bill-wave"></i> Valoare:</strong>
                                    <span class="badge badge-success">
                                        <?= number_format($contract['total_value'], 2, ',', '.') ?> RON
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-sm btn-info" onclick="viewContract(<?= $contract['id'] ?>)">
                            <i class="fas fa-eye"></i> Vizualizează
                        </button>
                        <button class="btn btn-sm btn-primary" onclick="editContract(<?= $contract['id'] ?>)">
                            <i class="fas fa-edit"></i> Editează
                        </button>
                        <a href="print_contract.php?id=<?= $contract['id'] ?>" target="_blank" class="btn btn-sm btn-success">
                            <i class="fas fa-print"></i> Printează
                        </a>
                        <button class="btn btn-sm btn-danger" onclick="deleteContract(<?= $contract['id'] ?>)">
                            <i class="fas fa-trash"></i> Șterge
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ADD/EDIT CONTRACT MODAL -->
<div class="modal fade" id="contractModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white" id="contractModalTitle">
                    <i class="fas fa-plus"></i> Adaugă Contract
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="contractForm">
                <div class="modal-body">
                    <input type="hidden" id="contract_id" name="contract_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="contract_number">Număr Contract <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="contract_number" name="contract_number" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="contract_date">Data Contract <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="contract_date" name="contract_date" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="company_cui">Companie <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="company_cui" name="company_cui" required>
                            <option value="">Selectează compania...</option>
                            <?php foreach ($companies as $company): ?>
                            <option value="<?= $company['CUI'] ?>" 
                                    data-name="<?= e($company['Name']) ?>"
                                    data-address="<?= e($company['Adress']) ?>"
                                    data-reg="<?= e($company['Reg']) ?>">
                                <?= e($company['Name']) ?> (CUI: <?= $company['CUI'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">
                            <span id="selected_company_info"></span>
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="object">Obiect Contract <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="object" name="object" rows="4" required 
                                  placeholder="Descrierea obiectului contractului..."></textarea>
                    </div>
                    
          <div class="form-group">
            <label for="special_clauses">Obiectul Proiectului</label>
			<input type="text" class="form-control" id="special_clauses" name="special_clauses" placeholder="Exemplu folosit la proces verbal predare-primire">
          </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="total_value">Valoare Totală (RON)</label>
                                <input type="number" step="0.01" class="form-control" id="total_value" name="total_value">
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="duration_months">Durată (luni) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="duration_months" name="duration_months" value="12" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="vat_series">Serie Factură</label>
                                <input type="text" class="form-control" id="vat_series" name="vat_series" value="FACT">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="offer_id">ID Ofertă Asociată</label>
                        <input type="number" class="form-control" id="offer_id" name="offer_id" 
                               placeholder="Opțional - ID-ul ofertei din care provine">
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

<!-- VIEW CONTRACT MODAL -->
<div class="modal fade" id="viewContractModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white">
                    <i class="fas fa-file-contract"></i> Detalii Contract
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="viewContractContent">
                <!-- Content loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Închide</button>
                <button type="button" class="btn btn-primary" onclick="editContractFromView()">
                    <i class="fas fa-edit"></i> Editează
                </button>
                <button type="button" class="btn btn-success" onclick="printContractFromView()">
                    <i class="fas fa-print"></i> Printează
                </button>
            </div>
        </div>
    </div>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/select2/js/select2.full.min.js"></script>
<script src="plugins/toastr/toastr.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>

<script>
let currentViewContractId = null;

$(function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });
    
    // Add contract button handlers
    $('#btnAddContract, #btnAddContractEmpty').on('click', function() {
        openContractModal();
    });
    
    // Form submission
    $('#contractForm').on('submit', function(e) {
        e.preventDefault();
        saveContract();
    });
    
    // Show company info when selected
    $('#company_cui').on('change', function() {
        const selected = $(this).find('option:selected');
        if (selected.val()) {
            const info = `${selected.data('name')} - ${selected.data('address')} (J${selected.data('reg')})`;
            $('#selected_company_info').html(`<i class="fas fa-info-circle"></i> ${info}`);
        } else {
            $('#selected_company_info').html('');
        }
    });
    
    // Set default date to today
    const today = new Date().toISOString().split('T')[0];
    $('#contract_date').val(today);
});

function filterContracts(filter) {
    window.location.href = 'contracts.php?filter=' + filter;
}

function openContractModal(contractId = null) {
    if (contractId) {
        // Load contract data for editing
        $.get('api/contracts_api.php', { action: 'get', id: contractId }, function(response) {
            if (response.success) {
                const contract = response.data;
                $('#contractModalTitle').html('<i class="fas fa-edit"></i> Editează Contract');
                $('#contract_id').val(contract.id);
                $('#contract_number').val(contract.contract_number);
                $('#contract_date').val(contract.contract_date);
                $('#company_cui').val(contract.company_cui).trigger('change');
                $('#object').val(contract.object);
                $('#special_clauses').val(contract.special_clauses || '');
                $('#total_value').val(contract.total_value);
                $('#duration_months').val(contract.duration_months);
                $('#vat_series').val(contract.vat_series);
                $('#offer_id').val(contract.offer_id || '');
                $('#contractModal').modal('show');
            } else {
                toastr.error(response.error || 'Eroare la încărcare');
            }
        }, 'json');
    } else {
        // New contract
        $('#contractModalTitle').html('<i class="fas fa-plus"></i> Adaugă Contract');
        $('#contractForm')[0].reset();
        $('#contract_id').val('');
        $('#company_cui').val('').trigger('change');
        
        // Set default date
        const today = new Date().toISOString().split('T')[0];
        $('#contract_date').val(today);
        
        $('#contractModal').modal('show');
    }
}

function saveContract() {
    const formData = {
        action: $('#contract_id').val() ? 'update' : 'create',
        id: $('#contract_id').val(),
        contract_number: $('#contract_number').val(),
        contract_date: $('#contract_date').val(),
        company_cui: $('#company_cui').val(),
        object: $('#object').val(),
        special_clauses: $('#special_clauses').val(),
        total_value: $('#total_value').val(),
        duration_months: $('#duration_months').val(),
        vat_series: $('#vat_series').val(),
        offer_id: $('#offer_id').val()
    };
    
    $.post('api/contracts_api.php', formData, function(response) {
        if (response.success) {
            toastr.success(formData.action === 'update' ? 'Contract actualizat cu succes!' : 'Contract adăugat cu succes!');
            $('#contractModal').modal('hide');
            setTimeout(() => location.reload(), 1000);
        } else {
            toastr.error(response.error || 'Eroare la salvare');
        }
    }, 'json').fail(function() {
        toastr.error('Eroare de comunicare cu serverul');
    });
}

function viewContract(id) {
    currentViewContractId = id;
    
    $.get('api/contracts_api.php', { action: 'get', id: id }, function(response) {
        if (response.success) {
            const c = response.data;
            const endDate = new Date(c.contract_date);
            endDate.setMonth(endDate.getMonth() + parseInt(c.duration_months));
            
            const today = new Date();
            const daysRemaining = Math.floor((endDate - today) / (1000 * 60 * 60 * 24));
            
            let statusBadge = '';
            if (daysRemaining < 0) {
                statusBadge = '<span class="badge badge-danger">EXPIRAT</span>';
            } else if (daysRemaining <= 60) {
                statusBadge = `<span class="badge badge-warning">Expiră în ${daysRemaining} zile</span>`;
            } else {
                statusBadge = '<span class="badge badge-success">ACTIV</span>';
            }
            
            const html = `
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-file-signature"></i> ${c.contract_number} ${statusBadge}</h5>
                        <hr>
                        <strong>Data Contract:</strong> ${new Date(c.contract_date).toLocaleDateString('ro-RO')}<br>
                        <strong>Durată:</strong> ${c.duration_months} luni<br>
                        <strong>Data Expirare:</strong> ${endDate.toLocaleDateString('ro-RO')}<br>
                        ${c.total_value > 0 ? `<strong>Valoare:</strong> ${parseFloat(c.total_value).toFixed(2)} RON<br>` : ''}
                        <strong>Serie Factură:</strong> ${c.vat_series || 'FACT'}
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-building"></i> Client</h5>
                        <hr>
                        <strong>${c.company_name || 'N/A'}</strong><br>
                        CUI: ${c.company_cui}<br>
                        ${c.company_address ? c.company_address + '<br>' : ''}
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h5><i class="fas fa-info-circle"></i> Obiect Contract</h5>
                        <hr>
                        <div class="border p-3 bg-light rounded">
                            ${c.object.replace(/\n/g, '<br>')}
                        </div>
                    </div>
                </div>
                
                ${c.special_clauses ? `
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h5><i class="fas fa-file-alt"></i> Clauze Speciale</h5>
                        <hr>
                        <div class="border p-3 bg-light rounded">
                            ${c.special_clauses.replace(/\n/g, '<br>')}
                        </div>
                    </div>
                </div>
                ` : ''}
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <small class="text-muted">
                            <i class="far fa-clock"></i> Creat la: ${new Date(c.created_at).toLocaleString('ro-RO')}
                            ${c.offer_id ? `| Bazat pe oferta #${c.offer_id}` : ''}
                        </small>
                    </div>
                </div>
            `;
            
            $('#viewContractContent').html(html);
            $('#viewContractModal').modal('show');
        } else {
            toastr.error(response.error || 'Eroare la încărcare');
        }
    }, 'json');
}

function editContract(id) {
    openContractModal(id);
}

function editContractFromView() {
    $('#viewContractModal').modal('hide');
    setTimeout(() => editContract(currentViewContractId), 300);
}

function printContractFromView() {
    window.open('print_contract.php?id=' + currentViewContractId, '_blank');
}

function deleteContract(id) {
    if (confirm('Sigur dorești să ștergi acest contract? Această acțiune este ireversibilă!')) {
        $.post('api/contracts_api.php', {
            action: 'delete',
            id: id
        }, function(response) {
            if (response.success) {
                toastr.success('Contract șters cu succes!');
                setTimeout(() => location.reload(), 1000);
            } else {
                toastr.error(response.error || 'Eroare la ștergere');
            }
        }, 'json');
    }
}
</script>

<link rel="stylesheet" href="plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">

<?php include_once("WEB-INF/footer.php"); ?>