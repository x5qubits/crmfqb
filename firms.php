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
                        <th>Reg. Com.</th>
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

<!-- Modal Add/Edit Company -->
<div class="modal fade" id="companyModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="companyModalTitle">Adaugă Companie</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="companyForm">
                <div class="modal-body">
                    <input type="hidden" id="company_action" name="action" value="add">
                    <input type="hidden" id="company_cui_old" name="cui_old" value="">
                    
                    <div class="form-group">
                        <label>CUI *</label>
                        <input type="number" class="form-control" id="company_cui" name="cui" required>
                        <small class="form-text text-muted">Introduceți CUI pentru a auto-completa datele.</small>
                    </div>
                    <div class="form-group">
                        <label>Reg. Com.</label>
                        <input type="text" class="form-control" id="company_reg" name="reg">
                    </div>
                    <div class="form-group">
                        <label>Nume *</label>
                        <input type="text" class="form-control" id="company_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Adresă *</label>
                        <textarea class="form-control" id="company_address" name="address" required></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Salvează</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Add/Edit Contract -->
<div class="modal fade" id="contractModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="contractModalTitle">Generează Contract</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>

      <form id="contractForm">
        <div class="modal-body">
          <input type="hidden" id="contract_action" name="action" value="add">
          <input type="hidden" id="contract_id" name="id">
          <input type="hidden" id="contract_company_cui" name="company_cui">

          <div class="form-group">
            <label for="contract_offer_id">Asociază cu Oferta</label>
            <select class="form-control" id="contract_offer_id" name="offer_id">
              <option value="0">--- Fără Ofertă Asociată ---</option>
            </select>
            <small class="form-text text-muted">Obiectul și valoarea pot fi preluate automat.</small>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="contract_number">Număr Contract *</label>
              <input type="text" class="form-control" id="contract_number" name="contract_number" required>
            </div>
            <div class="form-group col-md-6">
              <label for="contract_date">Data *</label>
              <input type="date" class="form-control" id="contract_date" name="contract_date" required value="<?= date('Y-m-d') ?>">
            </div>
          </div>

          <div class="form-group">
            <label for="contract_object">Obiectul Contractului *</label>
            <textarea class="form-control" id="contract_object" name="object" rows="3" required></textarea>
          </div>

          <div class="form-group">
            <label for="contract_special_clauses">Clauze Speciale</label>
            <textarea class="form-control" id="contract_special_clauses" name="special_clauses" rows="3"></textarea>
          </div>

          <div class="form-row">
            <div class="form-group col-md-4">
              <label for="contract_duration_months">Durata (zile) *</label>
              <input type="number" min="1" class="form-control" id="contract_duration_months" name="duration_months" value="12" required>
            </div>
            <div class="form-group col-md-4">
              <label for="contract_total_value">Valoare Totală</label>
              <input type="number" step="0.01" class="form-control" id="contract_total_value" name="total_value" value="0.00">
            </div>
            <div class="form-group col-md-4 d-flex align-items-end">
              <small class="form-text text-muted">Lăsați 0.00 dacă valoarea se va calcula ulterior.</small>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Anulează</button>
          <button type="submit" class="btn btn-primary">Salvează Contract</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Add/Edit Offer -->
<div class="modal fade" id="offerModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="offerModalTitle">Generează Ofertă</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="offerForm">
                <div class="modal-body">
                    <input type="hidden" id="offer_action" name="action" value="add">
                    <input type="hidden" id="offer_id" name="id" value="">
                    <input type="hidden" id="offer_company_cui" name="company_cui" value="">
                    <input type="hidden" id="offer_items_json" name="offer_items_json" value="[]">

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Companie</label>
                            <input type="text" class="form-control" id="offer_company_name" disabled>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Număr Ofertă *</label>
                            <input type="text" class="form-control" id="offer_number" name="offer_number" required readonly> 
                            <small class="form-text text-muted">Generat automat.</small>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Data Ofertă *</label>
                            <input type="date" class="form-control" id="offer_date" name="offer_date" required value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>

                    <h6 class="mt-3">Articole Produs/Serviciu</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr class="bg-light">
                                    <th style="width: 45%;">Descriere *</th>
                                    <th style="width: 15%;">Cantitate *</th>
                                    <th style="width: 20%;">Preț Unitar *</th>
                                    <th style="width: 15%;">Subtotal</th>
                                    <th style="width: 5%;"></th>
                                </tr>
                            </thead>
                            <tbody id="offerItemsTableBody"></tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-success" id="addItemButton"><i class="fas fa-plus"></i> Adaugă Articol</button>

                    <div class="row mt-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Tip Discount</label>
                                <select class="form-control form-control-sm" id="offer_discount_type" name="discount_type">
                                    <option value="percent">Procent (%)</option>
                                    <option value="fixed">Valoare Fixă (RON)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Valoare Discount</label>
                                <input type="number" step="0.01" class="form-control form-control-sm" id="offer_discount_amount" name="discount_amount" value="0" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="pt-4">
                                <div><strong>Subtotal:</strong> <span id="offer_subtotal_display">0.00</span> RON</div>
                                <div class="text-danger"><strong>Discount:</strong> -<span id="offer_discount_display">0.00</span> RON</div>
                                <div class="text-success"><strong>TOTAL:</strong> <span id="offer_total_display_main">0.00</span> RON</div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="offer_total_value" name="total_value" value="0.00">

                    <div class="form-group mt-3">
                        <label>Detalii Suplimentare</label>
                        <textarea class="form-control" id="offer_details" name="details" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-warning">Salvează Ofertă</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Contacte -->
<div class="modal fade" id="contactsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Contacte pentru <span id="contactsCompanyName"></span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <button type="button" class="btn btn-success btn-sm mb-3" id="btnAddContact">
                    <i class="fas fa-plus"></i> Adaugă Contact
                </button>
                <div id="contactsLoader" class="overlay" style="display:none;">
                    <i class="fas fa-2x fa-sync-alt fa-spin"></i>
                </div>
                <table id="contactsTable" class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Nume</th>
                            <th>Rol</th>
                            <th>Telefon</th>
                            <th>Email</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody id="contactsTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Contact -->
<div class="modal fade" id="contactModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contactModalTitle">Adaugă Contact</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="contactForm">
                <div class="modal-body">
                    <input type="hidden" id="contact_action" name="action" value="add">
                    <input type="hidden" id="contact_id" name="id" value="">
                    <input type="hidden" id="contact_company" name="companie" value="">
                    
                    <div class="form-group">
                        <label>Nume *</label>
                        <input type="text" class="form-control" id="contact_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Rol</label>
                        <select class="form-control" id="contact_role" name="role">
                            <option value="0">Nedefinit</option>
                            <option value="1">Manager</option>
                            <option value="2">Director</option>
                            <option value="3">Contact Principal</option>
                            <option value="4">Contact Secundar</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Telefon *</label>
                        <input type="text" class="form-control" id="contact_phone" name="phone">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" id="contact_email" name="email">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Salvează</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Istoric -->
<div class="modal fade" id="historyModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h5 class="modal-title">Istoric pentru <span id="historyCompanyName"></span></h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="historyTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="contracts-tab" data-toggle="tab" href="#contracts-list" role="tab">Contracte (<span id="contractCount">0</span>)</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="offers-tab" data-toggle="tab" href="#offers-list" role="tab">Oferte (<span id="offerCount">0</span>)</a>
                    </li>
					<li class="nav-item">
						<a class="nav-link" id="invoices-tab" data-toggle="tab" href="#invoices-list" role="tab">Facturi & Proforma (<span id="invoiceCount">0</span>)</a>
					</li>
                </ul>
                <div class="tab-content pt-3" id="historyTabContent">
                    <div class="tab-pane fade show active" id="contracts-list" role="tabpanel">
                        <button type="button" class="btn btn-sm btn-success mb-3" id="btnAddContractHistory"><i class="fas fa-plus"></i> Adaugă Contract</button>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Nr. Contract</th>
                                        <th>Data</th>
                                        <th>Obiect</th>
                                        <th>Valoare</th>
                                        <th>Acțiuni</th>
                                    </tr>
                                </thead>
                                <tbody id="contractsTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="offers-list" role="tabpanel">
                        <button type="button" class="btn btn-sm btn-warning mb-3" id="btnAddOfferHistory"><i class="fas fa-plus"></i> Adaugă Ofertă</button>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Nr. Ofertă</th>
                                        <th>Data</th>
                                        <th>Detalii</th>
                                        <th>Valoare</th>
                                        <th>Acțiuni</th>
                                    </tr>
                                </thead>
                                <tbody id="offersTableBody"></tbody>
                            </table>
                        </div>
                    </div>
					<!-- In the <div class="tab-content" id="historyTabContent"> section, add: -->
					<div class="tab-pane fade" id="invoices-list" role="tabpanel">
						<div class="mb-3">
							<button type="button" class="btn btn-sm btn-success" id="btnAddInvoiceHistory">
								<i class="fas fa-plus"></i> Generează Factură
							</button>
							<button type="button" class="btn btn-sm btn-info" id="btnAddProformaHistory">
								<i class="fas fa-plus"></i> Generează Proformă
							</button>
							<button type="button" class="btn btn-sm btn-primary" id="btnSyncOblioInvoices">
								<i class="fas fa-sync"></i> Sincronizează din Oblio
							</button>
						</div>
						<div class="table-responsive">
							<table class="table table-bordered table-sm table-striped">
								<thead>
									<tr>
										<th>Tip</th>
										<th>Serie/Nr.</th>
										<th>Data</th>
										<th>Scadență</th>
										<th>Total</th>
										<th>Status</th>
										<th>Acțiuni</th>
									</tr>
								</thead>
								<tbody id="invoicesTableBody"></tbody>
							</table>
						</div>
					</div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal Generate Invoice/Proforma - COMPLETE VERSION -->
<div class="modal fade" id="invoiceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title" id="invoiceModalTitle">Generează Factură</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="invoiceForm">
                <div class="modal-body">
                    <input type="hidden" id="invoice_type" name="type" value="invoice">
                    <input type="hidden" id="invoice_cui" name="cui" value="">
                    
                    <!-- Associate with Offer -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="invoice_offer_id">Asociază cu Oferta (opțional)</label>
                                <select class="form-control" id="invoice_offer_id">
                                    <option value="">--- Fără Ofertă Asociată ---</option>
                                </select>
                                <small class="form-text text-muted">
                                    Selectați o ofertă pentru a încărca automat produsele și serviciile
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Serie *</label>
								<select class="form-control" id="invoice_series">
									
								</select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Data *</label>
                                <input type="date" class="form-control" id="invoice_date" name="date" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Data Scadență</label>
                                <input type="date" class="form-control" id="invoice_due_date" name="due_date">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Client *</label>
                                <input type="text" class="form-control" id="invoice_client_name" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    <h6>Produse/Servicii</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th style="width:30%">Descriere</th>
                                    <th style="width:10%">Cant.</th>
                                    <th style="width:10%">U.M.</th>
                                    <th style="width:15%">Preț</th>
                                    <th style="width:15%">TVA</th>
                                    <th style="width:15%">Total</th>
                                    <th style="width:5%"></th>
                                </tr>
                            </thead>
                            <tbody id="invoiceItemsTable">
                                <!-- Items will be added here dynamically -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="7">
                                        <button type="button" class="btn btn-sm btn-secondary" id="addInvoiceItem">
                                            <i class="fas fa-plus"></i> Adaugă rând
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-right"><strong>Subtotal:</strong></td>
                                    <td colspan="2"><input type="text" class="form-control form-control-sm" id="invoice_subtotal" readonly value="0.00"></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-right"><strong>TVA:</strong></td>
                                    <td colspan="2"><input type="text" class="form-control form-control-sm" id="invoice_vat" readonly value="0.00"></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-right"><strong>Total:</strong></td>
                                    <td colspan="2"><input type="text" class="form-control form-control-sm font-weight-bold" id="invoice_total" readonly value="0.00"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-success" id="btnSaveInvoice">
                        <i class="fas fa-paper-plane"></i> Emite în Oblio
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal View Invoice Details -->
<div class="modal fade" id="viewInvoiceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title">Detalii Factură</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="invoiceDetailsContent">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p>Se încarcă...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Închide</button>
                <a href="#" target="_blank" class="btn btn-primary" id="btnDownloadInvoicePdf">
                    <i class="fas fa-download"></i> Descarcă PDF
                </a>
            </div>
        </div>
    </div>
</div>
<!-- Print Choice Modal -->
<div class="modal fade" id="printChoiceModal" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title">Tipărire?</h6>
        <button type="button" class="close" data-dismiss="modal"><span>×</span></button>
      </div>
      <div class="modal-body">
        <p class="mb-2" id="printChoiceText">Doriți să tipăriți?</p>
        <div class="d-flex justify-content-center gap-2">
          <button type="button" class="btn btn-primary btn-sm mr-2" id="printOfferBtn">Printează Ofertă</button>
          <button type="button" class="btn btn-secondary btn-sm" id="printContractBtn">Printează Contract</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title">Confirmare Ștergere</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p id="deleteMessage">Ești sigur că vrei să ștergi?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Șterge</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmContactDelete" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title">Ștergere contact</h6>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">Sigur doriți să ștergeți acest contact?</div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Nu</button>
        <button type="button" class="btn btn-danger btn-sm" id="confirmContactDeleteBtn">Da</button>
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
/* ===== Helpers ===== */
function toNum(v){ if(v==null)return 0; if(typeof v==='number'&&isFinite(v))return v; v=String(v).replace(',', '.').replace(/[^\d.-]/g,''); const n=parseFloat(v); return isNaN(n)?0:n; }
let searchTimer=null, deleteCallback=null, offerItemIndex=0, allOffersData=[], currentCompanyCUI=null, currentCompanyName=null;

/* ===== DataTable ===== */
const companiesTable = $('#companiesTable').DataTable({
  responsive:true, lengthChange:false, autoWidth:false, searching:false, paging:false, info:false,
  order:[[1,'asc']],
  language:{url:"//cdn.datatables.net/plug-ins/1.13.4/i18n/ro.json", emptyTable:"Nu s-au găsit companii."},
  data:[],
  columns:[
    {data:'CUI', render: function (data, type, r) {
        if (type === 'display') return '<a href="#" data-cui="'+r.CUI+'" data-name="'+r.Name+'" class="view-history">'+data+'</a>';
        return data;
      }},
	{data:'Name', render: function (data, type, r) {
        if (type === 'display') return '<a href="#" data-cui="'+r.CUI+'" data-name="'+r.Name+'" class="view-history">'+data+'</a>';
        return data;
      }},
	{data:'Reg'},
	{data:'Adress'},
    {data:null, orderable:false, render:r =>
      '<button class="btn btn-xs btn-info view-contacts mb-1" data-cui="'+r.CUI+'" data-name="'+r.Name+'"><i class="fas fa-users"></i> Contacte</button> '+
      '<button class="btn btn-xs btn-primary edit-company mb-1" data-cui="'+r.CUI+'"><i class="fas fa-edit"></i></button> '+
      '<button class="btn btn-xs btn-danger delete-company mb-1" data-cui="'+r.CUI+'"><i class="fas fa-trash"></i></button><br>'+
      '<button class="btn btn-xs btn-dark view-history mt-1" data-cui="'+r.CUI+'" data-name="'+r.Name+'"><i class="fas fa-history"></i> Istoric</button>'}
  ]
});

/* ===== Companies ===== */
function loadCompanies(search=''){
  $('#tableLoader').show();
  $.ajax({
    url:'api.php?f=get_company&user_id=<?= $user_id_js ?>', type:'POST', data:{search}, dataType:'json'
  }).done(resp=>{
    $('#tableLoader').hide();
    if(resp.success){
      const data = resp.data||[];
      companiesTable.clear().rows.add(data).draw(false);
      if(!data.length) $('#companiesTable tbody').html('<tr><td colspan="5" class="text-center">Nu s-au găsit companii.</td></tr>');
    } else { toastr.error(resp.error||'Eroare la preluare.'); companiesTable.clear().draw(); }
  }).fail(()=>{ $('#tableLoader').hide(); toastr.error('Eroare de rețea.'); companiesTable.clear().draw(); });
}

function loadAllOffers(){
  $.ajax({ url:'api.php?f=get_offers&user_id=<?= $user_id_js ?>', type:'POST', dataType:'json'
  }).done(r=>{ allOffersData=(r.success&&r.data)?(Array.isArray(r.data)?r.data:[r.data]):[]; }).fail(()=>{ allOffersData=[]; });
}

$('#searchBox').on('keyup', function(){ clearTimeout(searchTimer); const v=$(this).val(); searchTimer=setTimeout(()=>loadCompanies(v),300); });

$('#btnAddCompany').on('click', function(){
  $('#companyModalTitle').text('Adaugă Companie');
  $('#company_action').val('add');
  $('#companyForm')[0].reset();
  $('#company_cui_old').val('');
  $('#companyModal').modal('show');
});

$(document).on('click','.edit-company', function(){
  const row = companiesTable.row($(this).parents('tr')).data();
  if(!row){ toastr.error('Nu s-au putut prelua datele.'); return; }
  $('#companyModalTitle').text('Editează Companie: '+row.Name);
  $('#company_action').val('edit');
  $('#company_cui_old').val(row.CUI);
  $('#company_cui').val(row.CUI);
  $('#company_reg').val(row.Reg);
  $('#company_name').val(row.Name);
  $('#company_address').val(row.Adress);
  $('#companyModal').modal('show');
});

$(document).on('click','.delete-company', function(){
  const cui=$(this).data('cui');
  $('#deleteMessage').text('Ștergi compania și istoricul asociat?');
  deleteCallback=function(){
    $.ajax({ url:'api.php?f=delete_company&user_id=<?= $user_id_js ?>', type:'POST', dataType:'json', data:{cui}
    }).done(r=>{ if(r.success){ loadCompanies($('#searchBox').val()); toastr.success('Companie ștearsă.'); } else { toastr.error(r.error||'Eroare la ștergere.'); }});
  };
  $('#deleteModal').modal('show');
});

$('#confirmDelete').on('click', function(){ if(deleteCallback) deleteCallback(); deleteCallback=null; $('#deleteModal').modal('hide'); });

$('#companyForm').off('submit').on('submit', function(e){
  e.preventDefault();
  $.ajax({
    url: 'api.php?f=save_company&user_id=<?= $user_id_js ?>',
    type: 'POST',
    dataType: 'json',
    data: $(this).serialize()
  }).done(function(resp){
    if (resp.success) {
      $('#companyModal').modal('hide');
      toastr.success(resp.message || 'Companie salvată.');
      loadCompanies($('#searchBox').val());
    } else {
      toastr.error(resp.error || 'Eroare la salvare.');
    }
  }).fail(function(){ toastr.error('Eroare rețea.'); });
});

/* ===== Offer modal ===== */
function addOfferItemRow(item={}){
  const index=offerItemIndex++, qty=toNum(item.quantity||1), price=toNum(item.unit_price||0), sub=qty*price;
  const row =
    '<tr class="offer-item-row" data-index="'+index+'">'+
      '<td style="position:relative;">'+
        '<textarea class="form-control form-control-sm item-description" rows="1" required>'+(item.description||'')+'</textarea>'+
        '<div class="autocomplete-suggestions" id="suggestions-'+index+'" style="display:none;"></div>'+
      '</td>'+
      '<td><input type="number" class="form-control form-control-sm item-quantity" value="'+qty+'" min="1" required></td>'+
      '<td><input type="number" step="0.01" class="form-control form-control-sm item-unit-price" value="'+price.toFixed(2)+'" min="0" required></td>'+
      '<td class="item-subtotal text-right">'+sub.toFixed(2)+'</td>'+
      '<td><button type="button" class="btn btn-xs btn-danger remove-offer-item"><i class="fas fa-trash"></i></button></td>'+
    '</tr>';
  $('#offerItemsTableBody').append(row);
  calculateOfferTotal();
}

function calculateOfferTotal(){
  let subtotal = 0;
  $('.offer-item-row').each(function(){
    const row = $(this);
    const q = toNum(row.find('.item-quantity').val());
    const up = toNum(row.find('.item-unit-price').val());
    const s = q * up;
    row.find('.item-subtotal').text(s.toFixed(2));
    subtotal += s;
  });

  const discountType = $('#offer_discount_type').val() || 'percent';
  const discountAmount = toNum($('#offer_discount_amount').val());
  
  let discountValue = 0;
  if (discountType === 'percent') {
    discountValue = subtotal * (discountAmount / 100);
  } else {
    discountValue = Math.min(discountAmount, subtotal);
  }
  
  const total = Math.max(0, subtotal - discountValue);
  
  $('#offer_subtotal_display').text(subtotal.toFixed(2));
  $('#offer_discount_display').text(discountValue.toFixed(2));
  $('#offer_total_display_main').text(total.toFixed(2));
  $('#offer_total_value').val(total.toFixed(2));
}

$(document).on('click','.remove-offer-item', function(){ 
  $(this).closest('.offer-item-row').remove(); 
  if($('.offer-item-row').length===0) addOfferItemRow(); 
  calculateOfferTotal(); 
});

$(document).on('input','.item-quantity, .item-unit-price', calculateOfferTotal);
$(document).on('input change', '#offer_discount_amount, #offer_discount_type', calculateOfferTotal);

/* autocomplete */
let autocompleteTimer=null;
$(document).on('input','.item-description', function(){
  const $t=$(this), row=$t.closest('.offer-item-row'), index=row.data('index'), $sug=$('#suggestions-'+index), term=$t.val().trim();
  clearTimeout(autocompleteTimer);
  if(term.length<2){ $sug.hide().empty(); return; }
  autocompleteTimer=setTimeout(function(){
    $.ajax({
      url:'api.php?f=get_item_suggestions&user_id=<?= $user_id_js ?>',
      type:'POST', dataType:'json', data:{ q: term }
    }).done(resp=>{
      if(!resp.success||!resp.data||!resp.data.length){ $sug.hide().empty(); return; }
      $sug.empty();
	  resp.data.forEach(function(it){
		  const price=toNum(it.unit_price).toFixed(2);
		  const $div=$('<div class="autocomplete-suggestion"></div>')
			.text(it.description+' - '+price+' RON')
			.attr('data-index', row.data('index'))
			.data('product', { description: it.description, unit_price: price });
		  $sug.append($div);
		});
      $sug.show();
    }).fail(()=>{ $sug.hide().empty(); });
  },250);
});

$(document).on('mousedown','.autocomplete-suggestion', function(e){ e.preventDefault(); });
$(document).on('click','.autocomplete-suggestion', function(){
  const p=$(this).data('product')||{}, idx=$(this).attr('data-index'); if(!idx) return;
  const row=$('.offer-item-row[data-index="'+idx+'"]');
  row.find('.item-description').val(p.description||'');
  row.find('.item-unit-price').val(toNum(p.unit_price).toFixed(2));
  $('#suggestions-'+idx).hide().empty();
  calculateOfferTotal();
});

$(document).on('click', function(e){ 
  if(!$(e.target).closest('.item-description, .autocomplete-suggestions').length){ $('.autocomplete-suggestions').hide(); } 
});

function generateOfferNumber(cb){
  $.ajax({ url:'api.php?f=generate_offer_number&user_id=<?= $user_id_js ?>', type:'POST', dataType:'json'
  }).done(r=>cb(r.success&&r.offer_number?r.offer_number:('EROARE/'+new Date().getFullYear()))).fail(()=>cb('EROARE/'+new Date().getFullYear()));
}

function openOfferModal(cui, name, offerData = null) {
  $('#offer_action').val(offerData ? 'edit' : 'add');
  $('#offer_id').val(offerData ? offerData.id : '');
  $('#offer_company_cui').val(cui);
  $('#offer_company_name').val(name);
  offerItemIndex = 0;
  $('#offerItemsTableBody').empty();

  if (offerData) {
    $('#offer_number').val(offerData.offer_number);
    $('#offer_date').val(offerData.offer_date);
    $('#offer_details').val(offerData.details || '');
    $('#offer_discount_type').val(offerData.discount_type || 'percent');
    $('#offer_discount_amount').val(offerData.discount_amount || 0);
    
    if (offerData.items && Array.isArray(offerData.items) && offerData.items.length) {
      offerData.items.forEach(it => {
        it.quantity = toNum(it.quantity);
        it.unit_price = toNum(it.unit_price);
        addOfferItemRow(it);
      });
    } else {
      addOfferItemRow();
    }
  } else {
    generateOfferNumber(nr => $('#offer_number').val(nr));
    $('#offer_date').val(new Date().toISOString().slice(0, 10));
    $('#offer_discount_type').val('percent');
    $('#offer_discount_amount').val(0);
    addOfferItemRow();
  }
  
  calculateOfferTotal();
  $('#historyModal').modal('hide');
  $('#offerModal').modal('show');
}

$('#offerForm').on('submit', function(e){
  e.preventDefault();
  const items=[]; let ok=true;
  $('.offer-item-row').each(function(){
    const r=$(this), d=r.find('.item-description').val().trim(), q=toNum(r.find('.item-quantity').val()), up=toNum(r.find('.item-unit-price').val());
    if(d && q>0 && up>=0) items.push({ description:d, quantity:q, unit_price:up }); else ok=false;
  });
  if(!ok){ toastr.error('Completați corect toate liniile.'); return; }
  $('#offer_items_json').val(JSON.stringify(items));
  $.ajax({ url:'api.php?f=save_offer&user_id=<?= $user_id_js ?>', type:'POST', dataType:'json', data:$(this).serialize()
  }).done(r=>{
	if (r.success){
	  const newId = r.id || $('#offer_id').val();
	  $('#offerModal').modal('hide');
	  toastr.success(r.message||'Ofertă salvată.');
	  loadAllOffers();
	  showPrintChoice('offer', newId);
	} else {
	  toastr.error(r.error||'Eroare la salvare.');
	}
  });
});

/* ===== Contract modal ===== */
function populateOfferDropdown(cui, selectedOfferId=null){
  const $dd=$('#contract_offer_id'); $dd.empty().append('<option value="0">--- Fără Ofertă Asociată ---</option>');
  function fill(list){
    const offers=(list||[]).filter(o=>String(o.company_cui)===String(cui));
    offers.sort((a,b)=>String(a.offer_date).localeCompare(String(b.offer_date)));
    offers.forEach(o=> $dd.append($('<option></option>').val(o.id).text((o.offer_number||'—')+' · '+(o.offer_date||'')+' · '+toNum(o.total_value).toFixed(2)+' RON')));
    if(selectedOfferId) $dd.val(String(selectedOfferId));
  }
  if(allOffersData.length){ fill(allOffersData); return; }
  $.ajax({ url:'api.php?f=get_offers&user_id=<?= $user_id_js ?>', type:'POST', dataType:'json', data:{company_cui:cui}
  }).done(r=>fill(r&&r.success?(Array.isArray(r.data)?r.data:[r.data]):[]));
}

$('#contract_offer_id').off('change').on('change', function(){
  const offerId = parseInt($(this).val(), 10);
  if (!offerId) return;

  $.ajax({
    url: 'api.php?f=get_offers&user_id=<?= $user_id_js ?>',
    type: 'POST',
    dataType: 'json',
    data: { id: offerId }
  }).done(function(resp){
    if (!resp.success || !resp.data) return;
    const of = Array.isArray(resp.data) ? resp.data[0] : resp.data;

    let obj = '';
    if (of.details) obj = of.details;
    else if (of.object) obj = of.object;
    else if (of.items && Array.isArray(of.items) && of.items.length) {
      obj = of.items.map(it => (it.description || '').trim()).filter(s => s.length > 0).join('\n');
    }

    if (obj) $('#contract_object').val(obj);
    if (of.total_value) $('#contract_total_value').val(toNum(of.total_value).toFixed(2));
    if (!$('#contract_number').val() && of.offer_number) $('#contract_number').val('CONTRACT-' + of.offer_number);
  });
});

function openContractModal(cui,name,contractData=null){
  $('#contract_action').val(contractData?'edit':'add');
  $('#contract_id').val(contractData?contractData.id:'');
  $('#contract_company_cui').val(cui);
  if(contractData){
    $('#contract_number').val(contractData.contract_number);
    $('#contract_date').val(contractData.contract_date);
    $('#contract_object').val(contractData.object);
    $('#contract_special_clauses').val(contractData.special_clauses||'');
    $('#contract_total_value').val(toNum(contractData.total_value).toFixed(2));
    $('#contract_duration_months').val(contractData.duration_months||12);
  } else {
    $('#contract_date').val(new Date().toISOString().slice(0,10));
    $('#contract_object').val('');
    $('#contract_total_value').val('0.00');
    $('#contract_duration_months').val(30);
  }
  populateOfferDropdown(cui, contractData?contractData.offer_id:null);
  $('#historyModal').modal('hide');
  $('#contractModal').modal('show');
}

$('#contractForm').on('submit', function(e){
  e.preventDefault();
  $.ajax({ url:'api.php?f=save_contract&user_id=<?= $user_id_js ?>', type:'POST', dataType:'json', data:$(this).serialize()
  }).done(r=>{
	if (r.success){
	  const newId = r.id || $('#contract_id').val();
	  $('#contractModal').modal('hide');
	  toastr.success(r.message||'Contract salvat.');
	  showPrintChoice('contract', newId);
	} else {
	  toastr.error(r.error||'Eroare la salvare.');
	}
  });
});

/* ===== History modal ===== */
function loadHistoryData(cui,name){
  $.ajax({ url:'api.php?f=get_contacts&user_id=<?= $user_id_js ?>', type:'POST', data:{company_cui:cui}, dataType:'json'
  }).done(resp=>{
    $('#contractsTableBody').empty();
    if(resp.success && resp.data && resp.data.length){
      $('#contractCount').text(resp.data.length);
      resp.data.forEach(ct=>{
        $('#contractsTableBody').append(
          '<tr>'+
            '<td>'+ct.contract_number+'</td>'+
            '<td>'+ct.contract_date+'</td>'+
            '<td>'+ (ct.object?ct.object.substring(0,50)+'.':'') +'</td>'+
            '<td>'+ toNum(ct.total_value).toFixed(2)+' RON</td>'+
            '<td>'+
              '<button class="btn btn-xs btn-primary edit-contract-history" data-id="'+ct.id+'" data-cui="'+cui+'" data-name="'+name+'"><i class="fas fa-edit"></i></button> '+
              '<button class="btn btn-xs btn-success print-contract-history" data-id="'+ct.id+'"><i class="fas fa-print"></i></button>'+
			  '<button class="btn btn-xs btn-danger delete-contract-history float-right" data-id="'+ct.id+'"><i class="fas fa-trash"></i></button>'+
            '</td>'+
          '</tr>');
      });
    } else { $('#contractCount').text('0'); $('#contractsTableBody').html('<tr><td colspan="5" class="text-center">Nu există contracte.</td></tr>'); }
  });

  $.ajax({ url:'api.php?f=get_offers&user_id=<?= $user_id_js ?>', type:'POST', data:{company_cui:cui}, dataType:'json'
  }).done(resp=>{
    $('#offersTableBody').empty();
    const offers = (resp.success && resp.data) ? (Array.isArray(resp.data)?resp.data:[resp.data]) : [];
    if(offers.length){
      $('#offerCount').text(offers.length);
      offers.forEach(of=>{
        $('#offersTableBody').append(
          '<tr>'+
            '<td>'+of.offer_number+'</td>'+
            '<td>'+of.offer_date+'</td>'+
            '<td>'+ (of.details?of.details.substring(0,50)+'.':'Fără detalii') +'</td>'+
            '<td>'+ toNum(of.total_value).toFixed(2)+' RON</td>'+
            '<td>'+
              '<button class="btn btn-xs btn-primary edit-offer-history" data-id="'+of.id+'" data-cui="'+cui+'" data-name="'+name+'"><i class="fas fa-edit"></i></button> '+
              '<button class="btn btn-xs btn-success print-offer-history" data-id="'+of.id+'"><i class="fas fa-print"></i></button>'+
              '<button class="btn btn-xs btn-danger delete-offer-history float-right" data-id="'+of.id+'"><i class="fas fa-trash"></i></button>'+
            '</td>'+
          '</tr>');
      });
    } else { $('#offerCount').text('0'); $('#offersTableBody').html('<tr><td colspan="5" class="text-center">Nu există oferte.</td></tr>'); }
  });
}

$(document).on('click','.view-history', function(e){ 
  e.preventDefault(); 
  currentCompanyCUI=$(this).data('cui'); 
  currentCompanyName=$(this).data('name'); 
  $('#historyCompanyName').text(currentCompanyName); 
  loadHistoryData(currentCompanyCUI,currentCompanyName); 
  $('#historyModal').modal('show'); 
});

$('#btnAddContractHistory').on('click', function(){ openContractModal(currentCompanyCUI,currentCompanyName); });
$('#btnAddOfferHistory').on('click', function(){ openOfferModal(currentCompanyCUI,currentCompanyName); });

$(document).on('click','.edit-contract-history', function(){
  const id=$(this).data('id'), cui=$(this).data('cui'), name=$(this).data('name');
  $.ajax({ url:'api.php?f=get_contacts&user_id=<?= $user_id_js ?>', type:'POST', data:{ id }, dataType:'json'
  }).done(resp=>{
    if(resp.success && resp.data){
      const ct = Array.isArray(resp.data)?resp.data[0]:resp.data;
      openContractModal(cui,name,ct);
    } else toastr.error('Eroare la preluarea contractului.');
  });
});

$(document).on('click','.delete-contract-history', function(){
  const id=$(this).data('id');
  $('#deleteMessage').text('Ștergi contractul?');
  deleteCallback=function(){
    $.ajax({ url:'api.php?f=delete_contract&user_id=<?= $user_id_js ?>', type:'POST', dataType:'json', data:{id}
    }).done(r=>{ if(r.success){ loadHistoryData(currentCompanyCUI,currentCompanyName); toastr.success('Contract șters.'); } else toastr.error(r.error||'Eroare la ștergere.'); });
  };
  $('#deleteModal').modal('show');
});

$(document).on('click','.edit-offer-history', function(){
  const id=$(this).data('id'), cui=$(this).data('cui'), name=$(this).data('name');
  $.ajax({ url:'api.php?f=get_offers&user_id=<?= $user_id_js ?>', type:'POST', data:{id}, dataType:'json'
  }).done(resp=>{
    if(resp.success && resp.data){
      const of = Array.isArray(resp.data)?resp.data[0]:resp.data;
      openOfferModal(cui,name,of);
    } else toastr.error('Eroare la preluarea ofertei.');
  });
});

$(document).on('click','.delete-offer-history', function(){
  const id=$(this).data('id');
  $('#deleteMessage').text('Ștergi oferta? Toate articolele vor fi șterse.');
  deleteCallback=function(){
    $.ajax({ url:'api.php?f=delete_offer&user_id=<?= $user_id_js ?>', type:'POST', dataType:'json', data:{id}
    }).done(r=>{ if(r.success){ loadHistoryData(currentCompanyCUI,currentCompanyName); loadAllOffers(); toastr.success('Ofertă ștearsă.'); } else toastr.error(r.error||'Eroare la ștergere.'); });
  };
  $('#deleteModal').modal('show');
});

/* ===== Contacts ===== */
function loadContacts(cui){
  $('#contactsLoader').show();
  $('#contactsTableBody').html('<tr><td colspan="5" class="text-center">Se încarcă...</td></tr>');
  $.ajax({ url:'api.php?f=get_company_details&user_id=<?= $user_id_js ?>', type:'GET', data:{cui}, dataType:'json'
  }).done(resp=>{
    $('#contactsLoader').hide(); $('#contactsTableBody').empty();
    if(resp.success && resp.company && resp.company.contacts && resp.company.contacts.length){
      resp.company.contacts.forEach(c=>{
        const roleText = c.contact_role==1?'Manager':c.contact_role==2?'Director':c.contact_role==3?'Principal':c.contact_role==4?'Secundar':'Nedefinit';
        $('#contactsTableBody').append('<tr><td>'+c.contact_name+'</td><td>'+roleText+'</td><td>'+c.contact_phone+'</td><td>'+c.contact_email+'</td><td><button class="btn btn-xs btn-primary edit-contact" data-id="'+c.contact_id+'"><i class="fas fa-edit"></i></button> <button class="btn btn-xs btn-danger delete-contact" data-id="'+c.contact_id+'" data-cui="'+cui+'"><i class="fas fa-trash"></i></button></td></tr>');
      });
    } else $('#contactsTableBody').html('<tr><td colspan="5" class="text-center">Nu există contacte.</td></tr>');
  }).fail(()=>{ $('#contactsLoader').hide(); toastr.error('Eroare la preluarea contactelor.'); });
}

$(document).on('click','.view-contacts', function(){ 
  currentCompanyCUI=$(this).data('cui'); 
  currentCompanyName=$(this).data('name'); 
  $('#contactsCompanyName').text(currentCompanyName); 
  $('#contactsModal').data('cui', currentCompanyCUI);
  loadContacts(currentCompanyCUI); 
  $('#contactsModal').modal('show'); 
});

$(document).on('click', '#btnAddContact', function(){
  const cui = $('#contactsModal').data('cui');
  $('#contactForm')[0].reset();
  $('#contact_action').val('add');
  $('#contact_id').val('');
  $('#contact_company').val(cui);
  $('#contact_role').val('1');
  $('#contactModalTitle').text('Adaugă Contact');
  $('#contactModal').modal('show');
});

$(document).on('click', '.edit-contact', function(){
  const tr=$(this).closest('tr'), tds=tr.children('td');
  const cui = $('#contactsModal').data('cui');
  $('#contact_action').val('edit');
  $('#contact_id').val($(this).data('id'));
  $('#contact_company').val(cui);
  $('#contact_name').val(tds.eq(0).text().trim());
  const rt=tds.eq(1).text().trim();
  const rid=rt==='Manager'?1:rt==='Director'?2:rt==='Principal'?3:rt==='Secundar'?4:0;
  $('#contact_role').val(String(rid||0));
  $('#contact_phone').val(tds.eq(2).text().trim());
  $('#contact_email').val(tds.eq(3).text().trim());
  $('#contactModalTitle').text('Editează Contact');
  $('#contactModal').modal('show');
});

$('#contactForm').on('submit', function(e){
  e.preventDefault();
  const data = $(this).serializeArray().reduce((a,x)=>{a[x.name]=x.value; return a;}, {});
  data.companie = data.companie || $('#contactsModal').data('cui');
  data.phone = String(data.phone||'').replace(/(?!^\+)\D+/g,'');

  $.ajax({
    url: 'api.php?user_id=<?= $user_id_js ?>&f=save_contact',
    type: 'POST',
    dataType: 'json',
    data
  }).done(function(r){
    if (r && r.success) {
      $('#contactModal').modal('hide');
      loadContacts($('#contactsModal').data('cui'));
      toastr.success(r.message || 'Contact salvat.');
    } else {
      toastr.error((r && r.error) || 'Eroare la salvare.');
    }
  });
});

let _del = { id:null, cui:null };
$(document).on('click', '.delete-contact', function(){
  _del = { id: $(this).data('id'), cui: $(this).data('cui') };
  $('#confirmContactDelete').modal('show');
});

$('#confirmContactDeleteBtn').on('click', function(){
  if(!_del.id) { $('#confirmContactDelete').modal('hide'); return; }
  $.ajax({
    url: 'api.php?user_id=<?= $user_id_js ?>&f=delete_contact',
    type: 'POST',
    dataType: 'json',
    data: { id: _del.id, contact_id: _del.id }
  }).done(function(r){
    $('#confirmContactDelete').modal('hide');
    if (r && r.success) {
      loadContacts(_del.cui);
      toastr.success('Contact șters.');
    } else {
      toastr.error((r && r.error) || 'Eroare.');
    }
    _del = { id:null, cui:null };
  });
});

/* ===== CUI Autocomplete ===== */
const $name = $('#company_name');
if (!$('#companyNameSug').length) $name.after('<div id="companyNameSug" class="autocomplete-suggestions" style="display:none; position:absolute; z-index:2000;"></div>');

let compTimer=null;
$name.on('input', function(){
  const term = $(this).val().trim();
  clearTimeout(compTimer);
  if (term.length < 2) { $('#companyNameSug').hide().empty(); return; }
  compTimer = setTimeout(function(){
    $.ajax({
      url: 'api.php?f=get_company&user_id=<?= $user_id_js ?>',
      type: 'POST',
      dataType: 'json',
      data: { search: term }
    }).done(function(r){
      const box = $('#companyNameSug');
      box.empty();
      if (!r.success || !r.data || !r.data.length) { box.hide(); return; }
      r.data.slice(0,10).forEach(function(c){
        const $it = $('<div class="autocomplete-suggestion"></div>')
          .text(`${c.Name} · CUI ${c.CUI} · Reg ${c.Reg}`)
          .data('c', c);
        box.append($it);
      });
      box.show();
    });
  }, 250);
});

$(document).on('mousedown', '#companyNameSug .autocomplete-suggestion', function(e){
  e.preventDefault();
  const c = $(this).data('c');
  $('#company_name').val(c.Name || '');
  $('#company_cui').val(c.CUI || '');
  $('#company_reg').val(c.Reg || '');
  $('#company_address').val(c.Adress || '');
  $('#companyNameSug').hide().empty();
});

$(document).on('click', function(e){
  if (!$(e.target).closest('#company_name, #companyNameSug').length) $('#companyNameSug').hide();
});

function debounce(fn, ms){ let t; return function(){ clearTimeout(t); t=setTimeout(fn.bind(this, ...arguments), ms); }; }

function fillCompanyFromCIV(resp){
  if(!resp) return;
  const d = resp.data || resp;
  const name = d.Name ?? d.name ?? '';
  const reg = d.Reg ?? d.reg ?? d.regcom ?? '';
  const address = d.Adress ?? d.adress ?? d.address ?? '';
  const cui = d.CUI ?? d.cui ?? d.civ ?? '';

  if (name) $('#company_name').val(name);
  if (reg) $('#company_reg').val(reg);
  if (address) $('#company_address').val(address);
  if (cui && !$('#company_cui').val()) $('#company_cui').val(cui);
}

const fetchCIV = debounce(function(){
  const cui = $('#company_cui').val().replace(/\D+/g,'');
  if (cui.length < 2) return;
  $.ajax({
    url: 'getCIV.php',
    type: 'POST',
    dataType: 'json',
    data: { what: cui }
  }).done(function(txt){ fillCompanyFromCIV(txt); });
}, 300);

$(document).on('blur', '#company_cui', function(){ fetchCIV.call(this); });

$(document).on('click', '#addItemButton', function(e){ e.preventDefault(); addOfferItemRow(); });

/* ===== Print Choice ===== */
let _lastSaved = { type:null, id:null };

function showPrintChoice(type, id){
  _lastSaved = { type, id };
  $('#printChoiceText').text(
    type==='offer' ? 'Ofertă salvată. Tipăriți acum?' :
    type==='contract' ? 'Contract salvat. Tipăriți acum?' : 'Tipăriți acum?'
  );
  $('#printOfferBtn').prop('disabled', type!=='offer');
  $('#printContractBtn').prop('disabled', type!=='contract');
  $('#printChoiceModal').modal('show');
}

function printOffer(id){ window.open('print_offer.php?offer_id='+id,'_blank'); }
function printContract(id){ window.open('print_contract.php?id='+id,'_blank'); }

$('#printOfferBtn').on('click', function(){ $('#printChoiceModal').modal('hide'); printOffer(_lastSaved.id); });
$('#printContractBtn').on('click', function(){ $('#printChoiceModal').modal('hide'); printContract(_lastSaved.id); });

$(document).on('click', '.print-contract-history', function(){ printContract($(this).data('id')); });
$(document).on('click', '.print-offer-history', function(){ printOffer($(this).data('id')); });

loadCompanies();
loadAllOffers();

// Add this JavaScript to your firms.php <script> section

/* ===== COMPLETE INVOICE FUNCTIONALITY FOR FIRMS.PHP ===== */

let currentInvoiceId = null;
let availableVatRates = [];

/* ===== Load VAT Rates on page load ===== */
function loadVatRatesForInvoices() {
    $.get('api_oblio_handlers.php?f=get_oblio_vat_rates&user_id=<?= $user_id_js ?>', function(resp) {
        if (resp.success && resp.data) {
            availableVatRates = resp.data;
        }
    }, 'json');
}

// Load VAT rates when page loads
$(document).ready(function() {
    loadVatRatesForInvoices();
});

/* ===== Load Invoices for Company ===== */
function loadInvoicesForCompany(cui) {
    $.ajax({
        url: 'api_oblio_handlers.php?f=get_company_invoices&user_id=<?= $user_id_js ?>',
        type: 'POST',
        data: { company_cui: cui },
        dataType: 'json'
    }).done(resp => {
        if (resp.success) {
            const invoices = resp.data || [];
            $('#invoiceCount').text(invoices.length);
            renderInvoicesTable(invoices);
        } else {
            toastr.error(resp.error || 'Eroare la încărcarea facturilor');
            $('#invoicesTableBody').html('<tr><td colspan="7" class="text-center">Eroare la încărcare</td></tr>');
        }
    }).fail(() => {
        toastr.error('Eroare de rețea');
        $('#invoicesTableBody').html('<tr><td colspan="7" class="text-center">Eroare de rețea</td></tr>');
    });
}

function renderInvoicesTable(invoices) {
    const tbody = $('#invoicesTableBody');
    tbody.empty();
    
    if (!invoices.length) {
        tbody.html('<tr><td colspan="7" class="text-center">Nu există facturi</td></tr>');
        return;
    }
    let series = [];
    invoices.forEach(inv => {
		if (!series.includes(inv.series)) {
			$("#invoice_series").append('<option value="' + inv.series + '">' + inv.series + '</option>');
			series.push(inv.series);
		}

        
		const typeLabel = inv.type === 'proforma' 
            ? '<span class="badge badge-info">Proformă</span>' 
            : '<span class="badge badge-success">Factură</span>';
        
        const statusBadges = {
            'draft': 'secondary',
            'sent': 'info',
            'paid': 'success',
            'cancelled': 'danger'
        };
        
        const statusLabel = `<span class="badge badge-${statusBadges[inv.status] || 'secondary'}">${inv.status}</span>`;
        
        const actions = inv.status === 'cancelled' 
            ? '<span class="text-muted">Anulată</span>'
            : `
                <button class="btn btn-xs btn-info view-invoice" data-id="${inv.id}">
                    <i class="fas fa-eye"></i> Vezi
                </button>
                <button class="btn btn-xs btn-danger cancel-invoice" data-id="${inv.id}" data-series="${inv.series}" data-number="${inv.number}">
                    <i class="fas fa-ban"></i> Anulează
                </button>
            `;
        
        tbody.append(`
            <tr>
                <td>${typeLabel}</td>
                <td>${inv.series}-${inv.number}</td>
                <td>${inv.date}</td>
                <td>${inv.due_date || '-'}</td>
                <td class="text-right">${parseFloat(inv.total).toFixed(2)} RON</td>
                <td>${statusLabel}</td>
                <td>${actions}</td>
            </tr>
        `);
    });
}

/* ===== Open Invoice Modal ===== */
$(document).on('click', '#btnAddInvoiceHistory', function() {
    $('#invoice_type').val('invoice');
    $('#invoiceModalTitle').text('Generează Factură');
    $('#invoice_series').val('FCT');
    openInvoiceModal();
});

$(document).on('click', '#btnAddProformaHistory', function() {
    $('#invoice_type').val('proforma');
    $('#invoiceModalTitle').text('Generează Proformă');
    $('#invoice_series').val('PROF');
    openInvoiceModal();
});

function openInvoiceModal() {
    $('#invoice_cui').val(currentCompanyCUI);
    $('#invoice_client_name').val(currentCompanyName);
    $('#invoice_date').val(new Date().toISOString().split('T')[0]);
    
    // Set due date to 30 days from now
    const dueDate = new Date();
    dueDate.setDate(dueDate.getDate() + 3);
    $('#invoice_due_date').val(dueDate.toISOString().split('T')[0]);
    
    // Load offers for this company
    populateOfferDropdownForInvoice(currentCompanyCUI);
    
    // Reset items table
    resetInvoiceItems();
    
    calculateInvoiceTotals();
    $('#invoiceModal').modal('show');
}

/* ===== Populate Offer Dropdown ===== */
function populateOfferDropdownForInvoice(cui) {
    const $dd = $('#invoice_offer_id');
    $dd.empty().append('<option value="">--- Fără Ofertă Asociată ---</option>');
    
    $.ajax({
        url: 'api.php?f=get_offers&user_id=<?= $user_id_js ?>',
        type: 'POST',
        data: { company_cui: cui },
        dataType: 'json'
    }).done(resp => {
        const offers = (resp.success && resp.data) ? (Array.isArray(resp.data) ? resp.data : [resp.data]) : [];
        offers.forEach(o => {
            $dd.append($('<option></option>')
                .val(o.id)
                .text(`${o.offer_number} · ${o.offer_date} · ${toNum(o.total_value).toFixed(2)} RON`)
                .data('offer', o)
            );
        });
    });
}

/* ===== Load Offer Items into Invoice ===== */
$('#invoice_offer_id').on('change', function() {
    const offerId = $(this).val();
    if (!offerId) return;
    
    const offer = $(this).find('option:selected').data('offer');
    if (!offer || !offer.items || !offer.items.length) return;
    
    // Clear current items
    $('#invoiceItemsTable').empty();
    
    // Add offer items to invoice
    offer.items.forEach(item => {
        addInvoiceItemRow({
            description: item.description,
            quantity: item.quantity,
            unit: 'buc',
            price: item.unit_price,
            vat: 19
        });
    });
    
    calculateInvoiceTotals();
    toastr.success('Produse încărcate din ofertă');
});

/* ===== Reset Invoice Items ===== */
function resetInvoiceItems() {
    $('#invoiceItemsTable').html('');
    addInvoiceItemRow();
}

/* ===== Add Invoice Item Row ===== */
function addInvoiceItemRow(item = {}) {
    const vatOptions = availableVatRates.map(vat => 
        `<option value="${vat.percent}" ${vat.default ? 'selected' : ''}>${vat.name} (${vat.percent}%)</option>`
    ).join('');
    
    const defaultVat = availableVatRates.find(v => v.default)?.percent || 19;
    
    const row = `
        <tr class="invoice-item-row">
            <td><input type="text" class="form-control form-control-sm item-description" placeholder="Descriere produs/serviciu" value="${item.description || ''}" required></td>
            <td><input type="number" class="form-control form-control-sm item-quantity" value="${item.quantity || 1}" min="0" step="0.01" required></td>
            <td>
                <select class="form-control form-control-sm item-unit">
                    <option value="buc" ${item.unit === 'buc' ? 'selected' : ''}>buc</option>
                    <option value="ore" ${item.unit === 'ore' ? 'selected' : ''}>ore</option>
                    <option value="kg" ${item.unit === 'kg' ? 'selected' : ''}>kg</option>
                    <option value="m" ${item.unit === 'm' ? 'selected' : ''}>m</option>
                    <option value="set" ${item.unit === 'set' ? 'selected' : ''}>set</option>
                </select>
            </td>
            <td><input type="number" class="form-control form-control-sm item-price" value="${item.price || 0}" min="0" step="0.01" required></td>
            <td>
                <select class="form-control form-control-sm item-vat" required>
                    ${vatOptions || `<option value="${item.vat || defaultVat}">${item.vat || defaultVat}%</option>`}
                </select>
            </td>
            <td><input type="text" class="form-control form-control-sm item-total" readonly value="0.00"></td>
            <td><button type="button" class="btn btn-sm btn-danger remove-item"><i class="fas fa-times"></i></button></td>
        </tr>
    `;
    
    $('#invoiceItemsTable').append(row);
    calculateInvoiceTotals();
}

/* ===== Add/Remove Invoice Items ===== */
$(document).on('click', '#addInvoiceItem', function() {
    addInvoiceItemRow();
});

$(document).on('click', '.remove-item', function() {
    if ($('.invoice-item-row').length > 1) {
        $(this).closest('tr').remove();
        calculateInvoiceTotals();
    } else {
        toastr.warning('Trebuie să existe cel puțin un produs');
    }
});

/* ===== Calculate Totals ===== */
$(document).on('input change', '.item-quantity, .item-price, .item-vat', function() {
    calculateInvoiceTotals();
});

function calculateInvoiceTotals() {
    let subtotal = 0;
    let totalVat = 0;
    
    $('.invoice-item-row').each(function() {
        const qty = parseFloat($(this).find('.item-quantity').val()) || 0;
        const price = parseFloat($(this).find('.item-price').val()) || 0;
        const vat = parseFloat($(this).find('.item-vat').val()) || 0;
        
        const itemSubtotal = qty * price;
        const itemVat = itemSubtotal * (vat / 100);
        const itemTotal = itemSubtotal + itemVat;
        
        $(this).find('.item-total').val(itemTotal.toFixed(2));
        
        subtotal += itemSubtotal;
        totalVat += itemVat;
    });
    
    const total = subtotal + totalVat;
    
    $('#invoice_subtotal').val(subtotal.toFixed(2));
    $('#invoice_vat').val(totalVat.toFixed(2));
    $('#invoice_total').val(total.toFixed(2));
}

/* ===== Submit Invoice ===== */
$('#invoiceForm').on('submit', function(e) {
    e.preventDefault();
    
    const items = [];
    let valid = true;
    
    $('.invoice-item-row').each(function() {
        const desc = $(this).find('.item-description').val().trim();
        const qty = parseFloat($(this).find('.item-quantity').val());
        const unit = $(this).find('.item-unit').val();
        const price = parseFloat($(this).find('.item-price').val());
        const vat = parseFloat($(this).find('.item-vat').val());
        
        if (!desc || qty <= 0 || price < 0) {
            valid = false;
            return false;
        }
        
        items.push({
            description: desc,
            quantity: qty,
            measuringUnit: unit,
            price: price,
            vatPercentage: vat
        });
    });
    
    if (!valid) {
        toastr.error('Vă rugăm completați corect toate câmpurile produselor');
        return;
    }
    
    if (items.length === 0) {
        toastr.error('Adăugați cel puțin un produs');
        return;
    }
    
    const btn = $('#btnSaveInvoice');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Se emite...');
    
    // Build Oblio API format
    const invoiceData = {
        cif: 'RO' + $('#invoice_cui').val(),
        client: {
            cif: 'RO' + currentCompanyCUI,
            name: currentCompanyName
        },
        issueDate: $('#invoice_date').val(),
        dueDate: $('#invoice_due_date').val() || undefined,
        seriesName: $('#invoice_series').val(),
        type: $('#invoice_type').val(),
        language: 'RO',
        products: items
    };
    
    const endpoint = $('#invoice_type').val() === 'proforma' ? 'create_oblio_proforma' : 'create_oblio_invoice';
    
    $.ajax({
        url: 'api_oblio_handlers.php?f=' + endpoint + '&user_id=<?= $user_id_js ?>',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(invoiceData),
        dataType: 'json'
    }).done(function(resp) {
        btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Emite în Oblio');
        
        if (resp.success) {
            toastr.success('Factură emisă cu succes în Oblio!');
            $('#invoiceModal').modal('hide');
            loadInvoicesForCompany(currentCompanyCUI);
        } else {
            toastr.error(resp.error || 'Eroare la emitere');
            console.error('Invoice error:', resp);
        }
    }).fail(function(xhr, status, error) {
        btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Emite în Oblio');
        toastr.error('Eroare de rețea: ' + error);
        console.error('AJAX error:', xhr.responseText);
    });
});

/* ===== View Invoice ===== */
$(document).on('click', '.view-invoice', function() {
    const id = $(this).data('id');
    currentInvoiceId = id;
    
    $('#invoiceDetailsContent').html(`
        <div class="text-center">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p>Se încarcă...</p>
        </div>
    `);
    
    $('#viewInvoiceModal').modal('show');
    
    $.post('api_oblio_handlers.php?f=get_invoice_details&user_id=<?= $user_id_js ?>', { id }, function(resp) {
        if (resp.success && resp.data) {
            displayInvoiceDetails(resp.data);
        } else {
            $('#invoiceDetailsContent').html('<div class="alert alert-danger">Eroare la încărcarea facturii</div>');
        }
    }, 'json');
});

function displayInvoiceDetails(invoice) {
    const items = typeof invoice.items === 'string' ? JSON.parse(invoice.items) : invoice.items;
    
    let itemsHtml = '';
    if (items && items.length) {
        itemsHtml = items.map(item => `
            <tr>
                <td>${item.description}</td>
                <td class="text-center">${item.quantity}</td>
                <td class="text-center">${item.unit || item.measuringUnit || 'buc'}</td>
                <td class="text-right">${parseFloat(item.price).toFixed(2)} RON</td>
                <td class="text-center">${item.vat || item.vatPercentage}%</td>
                <td class="text-right">${(item.quantity * item.price * (1 + (item.vat || item.vatPercentage)/100)).toFixed(2)} RON</td>
            </tr>
        `).join('');
    }
    
    const html = `
        <div class="invoice-view">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h6>Tip Factură</h6>
                    <p>${invoice.type === 'proforma' ? 'Proformă' : 'Factură'}</p>
                </div>
                <div class="col-md-6 text-right">
                    <h6>Serie/Număr</h6>
                    <p class="h5">${invoice.series}-${invoice.number}</p>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <h6>Client</h6>
                    <p>${invoice.client_name}<br>CIF: ${invoice.client_cif}</p>
                </div>
                <div class="col-md-6">
                    <h6>Date Factură</h6>
                    <p>Data emitere: ${invoice.date}<br>
                    ${invoice.due_date ? 'Scadență: ' + invoice.due_date : ''}</p>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>Descriere</th>
                            <th class="text-center">Cantitate</th>
                            <th class="text-center">U.M.</th>
                            <th class="text-right">Preț</th>
                            <th class="text-center">TVA</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>${itemsHtml}</tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="text-right"><strong>Subtotal:</strong></td>
                            <td class="text-right">${parseFloat(invoice.subtotal).toFixed(2)} RON</td>
                        </tr>
                        <tr>
                            <td colspan="5" class="text-right"><strong>TVA:</strong></td>
                            <td class="text-right">${parseFloat(invoice.vat).toFixed(2)} RON</td>
                        </tr>
                        <tr class="table-active">
                            <td colspan="5" class="text-right"><strong>TOTAL:</strong></td>
                            <td class="text-right"><strong>${parseFloat(invoice.total).toFixed(2)} RON</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="alert alert-info mt-3">
                <strong>Status:</strong> ${invoice.status}
            </div>
        </div>
    `;
    
    $('#invoiceDetailsContent').html(html);
    
    if (invoice.oblio_id) {
        $('#btnDownloadInvoicePdf').attr('href', 
            `api_oblio_handlers.php?f=download_invoice_pdf&id=${invoice.id}&user_id=<?= $user_id_js ?>`
        ).show();
    } else {
        $('#btnDownloadInvoicePdf').hide();
    }
}

/* ===== Cancel Invoice ===== */
$(document).on('click', '.cancel-invoice', function() {
    const id = $(this).data('id');
    const series = $(this).data('series');
    const number = $(this).data('number');
    
    if (!confirm(`Sigur doriți să anulați factura ${series}-${number}?`)) {
        return;
    }
    
    const btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
    
    $.post('api_oblio_handlers.php?f=cancel_oblio_invoice&user_id=<?= $user_id_js ?>', {
        series: series,
        number: number
    }, function(resp) {
        if (resp.success) {
            toastr.success('Factură anulată cu succes');
            loadInvoicesForCompany(currentCompanyCUI);
        } else {
            toastr.error(resp.error || 'Eroare la anulare');
            btn.prop('disabled', false).html('<i class="fas fa-ban"></i> Anulează');
        }
    }, 'json').fail(() => {
        toastr.error('Eroare de rețea');
        btn.prop('disabled', false).html('<i class="fas fa-ban"></i> Anulează');
    });
});

/* ===== Sync from Oblio ===== */
$(document).on('click', '#btnSyncOblioInvoices', function() {
    const btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sincronizare...');
    
    $.post('api_oblio_handlers.php?f=sync_invoices_from_oblio&user_id=<?= $user_id_js ?>', {
        year: new Date().getFullYear()
    }, function(resp) {
        btn.prop('disabled', false).html('<i class="fas fa-sync"></i> Sincronizează din Oblio');
        
        if (resp.success) {
            toastr.success(`Sincronizate ${resp.synced || 0} facturi din Oblio`);
            loadInvoicesForCompany(currentCompanyCUI);
        } else {
            toastr.error(resp.error || 'Eroare la sincronizare');
        }
    }, 'json').fail(() => {
        btn.prop('disabled', false).html('<i class="fas fa-sync"></i> Sincronizează din Oblio');
        toastr.error('Eroare de rețea');
    });
});

/* ===== Update view-history to load invoices ===== */
$(document).on('click', '.view-history', function(e) {
    e.preventDefault();
    const cui = $(this).data('cui');
    const name = $(this).data('name');
    
    currentCompanyCUI = cui;
    currentCompanyName = name;
    
    $('#historyCompanyName').text(name);
    loadHistoryData(cui, name);
    loadInvoicesForCompany(cui);
    
    $('#historyModal').modal('show');
});

</script>

<style>
  #confirmContactDelete.modal { z-index: 1065; }
  .modal-backdrop.confirm-del { z-index: 1060; }
</style>

<?php include_once("WEB-INF/footer.php"); ?>