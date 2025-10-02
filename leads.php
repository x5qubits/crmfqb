<?php
include_once("config.php");
$pageName = "Lead-uri & Oportunități";
$pageId = 2;
include_once("WEB-INF/menu.php"); 

$user_id_js = isset($user_id) ? $user_id : 1;
?>
<link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="plugins/toastr/toastr.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">

<style>
.offer-card {
    transition: transform 0.2s;
}
.offer-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}
</style>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-warning">
                <h3 class="card-title">Oferte din Ultima Lună (fără Contract)</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-light btn-sm" id="refreshLeads">
                        <i class="fas fa-sync-alt"></i> Reîmprospătează
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" id="searchLeads" class="form-control" placeholder="Caută companie, număr ofertă...">
                    </div>
                    <div class="col-md-3">
                        <select id="sortLeads" class="form-control">
                            <option value="date_desc">Dată descrescător</option>
                            <option value="date_asc">Dată crescător</option>
                            <option value="value_desc">Valoare descrescător</option>
                            <option value="value_asc">Valoare crescător</option>
                        </select>
                    </div>
                    <div class="col-md-5 text-right">
                        <span class="badge badge-info p-2">
                            Total: <span id="totalLeads">0</span>
                        </span>
                        <span class="badge badge-success p-2 ml-2">
                            Valoare: <span id="totalValue">0.00</span> RON
                        </span>
                    </div>
                </div>
                
                <div id="leadsLoader" class="text-center py-5" style="display:none;">
                    <i class="fas fa-3x fa-sync-alt fa-spin text-warning"></i>
                    <p class="mt-2">Se încarcă...</p>
                </div>
                
                <div id="leadsContainer" class="row"></div>
                
                <div id="noLeadsMessage" class="text-center py-5" style="display:none;">
                    <i class="fas fa-5x fa-check-circle text-success mb-3"></i>
                    <h4>Toate ofertele au contracte!</h4>
                    <p class="text-muted">Nu există lead-uri în ultima lună.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Offer Modal -->
<div class="modal fade" id="viewOfferModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title">Detalii Ofertă</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="viewOfferContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Închide</button>
                <button type="button" class="btn btn-success" id="printViewedOffer"><i class="fas fa-print"></i> Printează</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Offer Modal -->
<div class="modal fade" id="editOfferModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">Editează Ofertă</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="editOfferForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_offer_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Număr Ofertă</label>
                                <input type="text" class="form-control" id="edit_offer_number" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Dată Ofertă</label>
                                <input type="date" class="form-control" id="edit_offer_date" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Companie</label>
                        <input type="text" class="form-control" id="edit_company_name" readonly>
                    </div>

                    <div class="form-group">
                        <label>Detalii</label>
                        <textarea class="form-control" id="edit_details" rows="2"></textarea>
                    </div>

                    <h6>Articole</h6>
                    <div id="editItemsContainer"></div>
                    <button type="button" class="btn btn-sm btn-success mb-3" id="addEditItem">
                        <i class="fas fa-plus"></i> Adaugă Articol
                    </button>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Tip Discount</label>
                                <select class="form-control" id="edit_discount_type">
                                    <option value="percent">Procent (%)</option>
                                    <option value="fixed">Valoare Fixă (RON)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Valoare Discount</label>
                                <input type="number" class="form-control" id="edit_discount_amount" min="0" step="0.01" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mt-4 pt-2">
                                <strong>Subtotal: <span id="editSubtotalDisplay">0.00</span> RON</strong><br>
                                <strong class="text-danger">Discount: -<span id="editDiscountDisplay">0.00</span> RON</strong><br>
                                <h5 class="text-success">Total: <span id="editTotalDisplay">0.00</span> RON</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Închide</button>
                    <button type="submit" class="btn btn-primary">Salvează</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation -->
<div class="modal fade" id="deleteOfferModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title">Confirmare</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                Sigur ștergi această ofertă?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Nu</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteOffer">Da, Șterge</button>
            </div>
        </div>
    </div>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/toastr/toastr.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>

<script>
let allLeads = [];
let deleteOfferId = null;
let editItemIndex = 0;
let currentViewedOffer = null;

$(function() {
    loadLeads();
});

function loadLeads() {
    $("#leadsLoader").show();
    $("#leadsContainer").empty();
    $("#noLeadsMessage").hide();

    $.post("api.php?f=get_recent_offers_without_contract&user_id=<?= $user_id_js ?>", {}, function(resp) {
        $("#leadsLoader").hide();

        if (resp.success && Array.isArray(resp.data)) {
            allLeads = resp.data;
            
            if (allLeads.length === 0) {
                $("#noLeadsMessage").show();
            } else {
                displayLeads(allLeads);
                updateStats(allLeads);
            }
        } else {
            toastr.error(resp.error || "Eroare la încărcare");
        }
    }, 'json').fail(() => {
        $("#leadsLoader").hide();
        toastr.error("Eroare de rețea");
    });
}

function displayLeads(leads) {
    $("#leadsContainer").empty();

    leads.forEach(lead => {
        const d = new Date(lead.offer_date);
        const daysOld = Math.floor((Date.now() - d.getTime()) / (1000 * 60 * 60 * 24));
        const urgencyClass = daysOld > 20 ? "danger" : (daysOld > 10 ? "warning" : "success");
        
        const value = Number(lead.total_value) || 0;
        const discountAmount = Number(lead.discount_amount) || 0;
        const discountType = lead.discount_type || 'percent';

        let discountBadge = '';
        if (discountAmount > 0) {
            if (discountType === 'percent') {
                discountBadge = `<span class="badge badge-danger ml-1">-${discountAmount.toFixed(2)}%</span>`;
            } else {
                discountBadge = `<span class="badge badge-danger ml-1">-${discountAmount.toFixed(2)} RON</span>`;
            }
        }

        const card = `
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="card offer-card h-100">
                <div class="card-header bg-${urgencyClass} text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-file-invoice"></i> ${lead.offer_number}
                    </h5>
                </div>
                <div class="card-body">
                    <h6 class="text-primary">${lead.company_name}</h6>
                    <p class="mb-2">
                        <small class="text-muted">
                            <i class="far fa-calendar"></i> ${lead.offer_date}
                            <span class="badge badge-${urgencyClass} ml-2">${daysOld} zile</span>
                        </small>
                    </p>
                    <h4 class="text-success">${value.toFixed(2)} RON ${discountBadge}</h4>
                </div>
                <div class="card-footer">
                    <button class="btn btn-sm btn-info btn-block view-offer" data-id="${lead.id}">
                        <i class="fas fa-eye"></i> Vezi Oferta
                    </button>
                    <button class="btn btn-sm btn-success btn-block generate-contract" data-id="${lead.id}">
                        <i class="fas fa-file-contract"></i> Generează Contract
                    </button>
                    <div class="btn-group btn-block mt-2">
                        <button class="btn btn-sm btn-primary edit-offer" data-id="${lead.id}">
                            <i class="fas fa-edit"></i> Editează
                        </button>
                        <button class="btn btn-sm btn-danger delete-offer" data-id="${lead.id}">
                            <i class="fas fa-trash"></i> Șterge
                        </button>
                    </div>
                </div>
            </div>
        </div>`;
        $("#leadsContainer").append(card);
    });
}

function updateStats(leads) {
    const total = leads.reduce((sum, l) => sum + (Number(l.total_value) || 0), 0);
    $("#totalLeads").text(leads.length);
    $("#totalValue").text(total.toFixed(2));
}

// View Offer
$(document).on("click", ".view-offer", function() {
    const offerId = $(this).data("id");

    $.post("api.php?f=get_offers&user_id=<?= $user_id_js ?>", {id: offerId}, function(resp) {
        if (resp.success && resp.data) {
            currentViewedOffer = Array.isArray(resp.data) ? resp.data[0] : resp.data;
            displayOfferDetails(currentViewedOffer);
            $("#viewOfferModal").modal("show");
        }
    }, 'json');
});

function displayOfferDetails(offer) {
    const discountType = offer.discount_type || 'percent';
    const discountAmount = Number(offer.discount_amount) || 0;
    const discountValue = Number(offer.discount_value) || 0;
    
    let subtotal = 0;
    let itemsHtml = "";

    if (offer.items && offer.items.length) {
        offer.items.forEach(item => {
            const qty = Number(item.quantity) || 0;
            const price = Number(item.unit_price) || 0;
            const sub = qty * price;
            subtotal += sub;

            itemsHtml += `
            <tr>
                <td>${item.description}</td>
                <td class="text-center">${qty}</td>
                <td class="text-right">${price.toFixed(2)}</td>
                <td class="text-right">${sub.toFixed(2)}</td>
            </tr>`;
        });
    }

    const total = subtotal - discountValue;

    const html = `
    <div class="invoice p-3">
        <div class="row">
            <div class="col-12">
                <h4>
                    <i class="fas fa-file-invoice"></i> Oferta ${offer.offer_number}
                    <small class="float-right">Data: ${offer.offer_date}</small>
                </h4>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-sm-6">
                <strong>Către:</strong><br>
                <address>
                    <strong>${offer.company_name}</strong><br>
                    CUI: ${offer.company_cui}
                </address>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12 table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Descriere</th>
                            <th class="text-center">Cantitate</th>
                            <th class="text-right">Preț Unitar</th>
                            <th class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>${itemsHtml}</tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-right">Subtotal:</th>
                            <th class="text-right">${subtotal.toFixed(2)} RON</th>
                        </tr>
                        ${discountAmount > 0 ? `
                        <tr>
                            <th colspan="3" class="text-right">
                                Discount ${discountType === 'percent' ? '(' + discountAmount.toFixed(2) + '%)' : '(fix)'}:
                            </th>
                            <th class="text-right text-danger">-${discountValue.toFixed(2)} RON</th>
                        </tr>` : ''}
                        <tr class="bg-light">
                            <th colspan="3" class="text-right">TOTAL:</th>
                            <th class="text-right text-success">${total.toFixed(2)} RON</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        ${offer.details ? `
        <div class="row mt-3">
            <div class="col-12">
                <p><strong>Detalii:</strong></p>
                <p>${offer.details}</p>
            </div>
        </div>` : ''}
    </div>`;

    $("#viewOfferContent").html(html);
}

$("#printViewedOffer").click(function() {
    if (!currentViewedOffer) return;
    window.open("print_offer.php?offer_id=" + currentViewedOffer.id, '_blank');
});

// Search & Sort
$("#searchLeads").on("keyup", function() {
    const q = $(this).val().toLowerCase();
    const filtered = allLeads.filter(l =>
        (l.company_name || "").toLowerCase().includes(q) ||
        (l.offer_number || "").toLowerCase().includes(q)
    );
    displayLeads(filtered);
    updateStats(filtered);
});

$("#sortLeads").on("change", function() {
    const sortType = $(this).val();
    const sorted = [...allLeads];

    switch(sortType) {
        case "date_desc": sorted.sort((a,b) => new Date(b.offer_date) - new Date(a.offer_date)); break;
        case "date_asc": sorted.sort((a,b) => new Date(a.offer_date) - new Date(b.offer_date)); break;
        case "value_desc": sorted.sort((a,b) => (Number(b.total_value)||0) - (Number(a.total_value)||0)); break;
        case "value_asc": sorted.sort((a,b) => (Number(a.total_value)||0) - (Number(b.total_value)||0)); break;
    }

    displayLeads(sorted);
    updateStats(sorted);
});

// Generate Contract
$(document).on("click", ".generate-contract", function() {
    const offerId = $(this).data("id");
    const btn = $(this).prop("disabled", true);

    $.post("api.php?f=generate_contract_from_offer&user_id=<?= $user_id_js ?>", {offer_id: offerId}, function(resp) {
        if (resp.success && resp.contract_id) {
            window.open("print_contract.php?id=" + resp.contract_id, '_blank');
            toastr.success("Contract generat!");
            setTimeout(loadLeads, 1000);
        } else {
            toastr.error(resp.error || "Eroare!");
            btn.prop("disabled", false);
        }
    }, 'json');
});

// Edit Offer
$(document).on("click", ".edit-offer", function() {
    const offerId = $(this).data("id");

    $.post("api.php?f=get_offers&user_id=<?= $user_id_js ?>", {id: offerId}, function(resp) {
        if (resp.success && resp.data) {
            const offer = Array.isArray(resp.data) ? resp.data[0] : resp.data;
            
            $("#edit_offer_id").val(offer.id);
            $("#edit_offer_number").val(offer.offer_number);
            $("#edit_offer_date").val(offer.offer_date);
            $("#edit_company_name").val(offer.company_name);
            $("#edit_details").val(offer.details || "");
            $("#edit_discount_type").val(offer.discount_type || 'percent');
            $("#edit_discount_amount").val(offer.discount_amount || 0);

            $("#editItemsContainer").empty();
            editItemIndex = 0;

            if (offer.items && offer.items.length) {
                offer.items.forEach(item => addEditItemRow(item));
            } else {
                addEditItemRow();
            }

            calculateEditTotal();
            $("#editOfferModal").modal("show");
        }
    }, 'json');
});

function addEditItemRow(item = {}) {
    const idx = editItemIndex++;
    const row = `
    <div class="row mb-2 edit-item-row" data-idx="${idx}">
        <div class="col-5">
            <input type="text" class="form-control form-control-sm item-desc" placeholder="Descriere" value="${item.description||''}" required>
        </div>
        <div class="col-2">
            <input type="number" class="form-control form-control-sm item-qty" placeholder="Cant" value="${item.quantity||1}" min="1" required>
        </div>
        <div class="col-3">
            <input type="number" step="0.01" class="form-control form-control-sm item-price" placeholder="Preț" value="${item.unit_price||0}" min="0" required>
        </div>
        <div class="col-2">
            <button type="button" class="btn btn-sm btn-danger remove-edit-item"><i class="fas fa-trash"></i></button>
        </div>
    </div>`;
    $("#editItemsContainer").append(row);
}

$("#addEditItem").click(() => addEditItemRow());

$(document).on("click", ".remove-edit-item", function() {
    $(this).closest(".edit-item-row").remove();
    calculateEditTotal();
});

$(document).on("input change", ".item-qty, .item-price, #edit_discount_amount, #edit_discount_type", calculateEditTotal);

function calculateEditTotal() {
    let subtotal = 0;
    $(".edit-item-row").each(function() {
        const qty = Number($(this).find(".item-qty").val()) || 0;
        const price = Number($(this).find(".item-price").val()) || 0;
        subtotal += qty * price;
    });

    const discountType = $("#edit_discount_type").val() || 'percent';
    const discountAmount = Number($("#edit_discount_amount").val()) || 0;
    
    let discountValue = 0;
    if (discountType === 'percent') {
        discountValue = subtotal * (discountAmount / 100);
    } else {
        discountValue = Math.min(discountAmount, subtotal);
    }
    
    const total = Math.max(0, subtotal - discountValue);

    $("#editSubtotalDisplay").text(subtotal.toFixed(2));
    $("#editDiscountDisplay").text(discountValue.toFixed(2));
    $("#editTotalDisplay").text(total.toFixed(2));
}

$("#editOfferForm").submit(function(e) {
    e.preventDefault();

    const items = [];
    $(".edit-item-row").each(function() {
        items.push({
            description: $(this).find(".item-desc").val(),
            quantity: Number($(this).find(".item-qty").val()),
            unit_price: Number($(this).find(".item-price").val())
        });
    });

    const data = {
        id: $("#edit_offer_id").val(),
        offer_date: $("#edit_offer_date").val(),
        details: $("#edit_details").val(),
        discount_type: $("#edit_discount_type").val(),
        discount_amount: $("#edit_discount_amount").val(),
        items: JSON.stringify(items)
    };

    $.post("api.php?f=update_offer&user_id=<?= $user_id_js ?>", data, function(resp) {
        if (resp.success) {
            toastr.success("Ofertă actualizată!");
            $("#editOfferModal").modal("hide");
            loadLeads();
        } else {
            toastr.error(resp.error || "Eroare!");
        }
    }, 'json');
});

// Delete
$(document).on("click", ".delete-offer", function() {
    deleteOfferId = $(this).data("id");
    $("#deleteOfferModal").modal("show");
});

$("#confirmDeleteOffer").click(function() {
    if (!deleteOfferId) return;

    $.post("api.php?f=delete_offer&user_id=<?= $user_id_js ?>", {id: deleteOfferId}, function(resp) {
        if (resp.success) {
            toastr.success("Ofertă ștearsă!");
            $("#deleteOfferModal").modal("hide");
            loadLeads();
        } else {
            toastr.error(resp.error || "Eroare!");
        }
    }, 'json');
});

$("#refreshLeads").click(loadLeads);
</script>

<?php include_once("WEB-INF/footer.php"); ?>