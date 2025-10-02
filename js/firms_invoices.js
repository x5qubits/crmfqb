let currentInvoiceId = null;
let availableVatRates = [];
let companyVatSettings = { vat_payer: 1, default_invoice_series: 'FACT', default_proforma_series: 'PROF' };

function loadVatRatesForInvoices() {
    $.get('api_oblio_handlers.php?f=get_oblio_vat_rates&user_id=' + USER_ID, function(resp) {
        if (resp.success && resp.data) {
            availableVatRates = resp.data;
        }
    }, 'json');
}

function loadCompanyVatSettings(cui) {
    $.post('api_oblio_handlers.php?f=get_company_settings&user_id=' + USER_ID, { cui }, function(resp) {
        if (resp.success && resp.data) {
            companyVatSettings = resp.data;
        }
    }, 'json');
}

function loadInvoicesForCompany(cui) {
    $.ajax({
        url: 'api_oblio_handlers.php?f=get_company_invoices&user_id=' + USER_ID,
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
    
    const series = $('#invoice_series');
    const existingSeries = series.find('option').map((i, el) => $(el).val()).get();
    
    invoices.forEach(inv => {
        if (!existingSeries.includes(inv.series)) {
            series.append(`<option value="${inv.series}">${inv.series}</option>`);
            existingSeries.push(inv.series);
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

$(document).on('click', '#btnAddInvoiceHistory', function() {
    $('#invoice_type').val('invoice');
    $('#invoiceModalTitle').text('Generează Factură');
    $('#invoice_series').val(companyVatSettings.default_invoice_series || 'FACT');
    openInvoiceModal();
});

$(document).on('click', '#btnAddProformaHistory', function() {
    $('#invoice_type').val('proforma');
    $('#invoiceModalTitle').text('Generează Proformă');
    $('#invoice_series').val(companyVatSettings.default_proforma_series || 'PROF');
    openInvoiceModal();
});

function openInvoiceModal() {
    $('#invoice_client_cif').val(currentCompanyCUI);
    $('#invoice_client_name').val(currentCompanyName);
    $('#invoice_date').val(new Date().toISOString().split('T')[0]);
    
    const dueDate = new Date();
    dueDate.setDate(dueDate.getDate() + 30);
    $('#invoice_due_date').val(dueDate.toISOString().split('T')[0]);
    
    $('#invoice_source_type').val('manual');
    $('#invoice_source_id').val('');
    
    loadCompanyVatSettings(currentCompanyCUI);
    populateOffersForImport(currentCompanyCUI);
    populateContractsForImport(currentCompanyCUI);
    
    resetInvoiceItems();
    addInvoiceItemRow();
    
    calculateInvoiceTotals();
    $('#invoiceModal').modal('show');
}

function populateOffersForImport(cui) {
    $.post('api.php?f=get_offers&user_id=' + USER_ID, { company_cui: cui }, function(resp) {
        if (resp.success && resp.data) {
            window.availableOffers = Array.isArray(resp.data) ? resp.data : [resp.data];
        }
    }, 'json');
}

function populateContractsForImport(cui) {
    $.post('api.php?f=get_contacts&user_id=' + USER_ID, { company_cui: cui }, function(resp) {
        if (resp.success && resp.data) {
            window.availableContracts = Array.isArray(resp.data) ? resp.data : [resp.data];
        }
    }, 'json');
}

$('#btnImportFromOffer').on('click', function() {
    if (!window.availableOffers || !window.availableOffers.length) {
        toastr.warning('Nu există oferte pentru această companie');
        return;
    }
    
    const select = $('#importSourceSelect');
    select.empty().append('<option value="">Selectează oferta...</option>');
    
    window.availableOffers.forEach(offer => {
        select.append(`<option value="${offer.id}">Oferta ${offer.offer_number} - ${offer.offer_date} (${parseFloat(offer.total_value).toFixed(2)} RON)</option>`);
    });
    
    select.show().off('change').on('change', function() {
        const offerId = $(this).val();
        if (offerId) {
            importFromOffer(parseInt(offerId));
            $(this).hide();
        }
    });
});

$('#btnImportFromContract').on('click', function() {
    if (!window.availableContracts || !window.availableContracts.length) {
        toastr.warning('Nu există contracte pentru această companie');
        return;
    }
    
    const select = $('#importSourceSelect');
    select.empty().append('<option value="">Selectează contractul...</option>');
    
    window.availableContracts.forEach(contract => {
        select.append(`<option value="${contract.id}">Contract ${contract.contract_number} - ${contract.contract_date} (${parseFloat(contract.total_value || 0).toFixed(2)} RON)</option>`);
    });
    
    select.show().off('change').on('change', function() {
        const contractId = $(this).val();
        if (contractId) {
            importFromContract(parseInt(contractId));
            $(this).hide();
        }
    });
});

function importFromOffer(offerId) {
    $.post('api_oblio_handlers.php?f=import_from_offer&user_id=' + USER_ID, { offer_id: offerId }, function(resp) {
        if (resp.success && resp.data) {
            $('#invoice_source_type').val('offer');
            $('#invoice_source_id').val(offerId);
            
            resetInvoiceItems();
            
            if (resp.data.items && resp.data.items.length) {
                resp.data.items.forEach(item => {
                    addInvoiceItemRow(item.description, item.quantity, 'buc', item.price);
                });
            }
            
            calculateInvoiceTotals();
            toastr.success('Date importate din ofertă');
        } else {
            toastr.error(resp.error || 'Eroare la import');
        }
    }, 'json');
}

function importFromContract(contractId) {
    $.post('api_oblio_handlers.php?f=import_from_contract&user_id=' + USER_ID, { contract_id: contractId }, function(resp) {
        if (resp.success && resp.data) {
            $('#invoice_source_type').val('contract');
            $('#invoice_source_id').val(contractId);
            
            resetInvoiceItems();
            
            if (resp.data.items && resp.data.items.length) {
                resp.data.items.forEach(item => {
                    addInvoiceItemRow(item.description, item.quantity, 'buc', item.price);
                });
            }
            
            calculateInvoiceTotals();
            toastr.success('Date importate din contract');
        } else {
            toastr.error(resp.error || 'Eroare la import');
        }
    }, 'json');
}

function resetInvoiceItems() {
    $('#invoiceItemsTable').empty();
}

function addInvoiceItemRow(desc = '', qty = 1, unit = 'buc', price = 0, vat = null) {
    if (vat === null) {
        vat = companyVatSettings.vat_payer == 1 ? 19 : 0;
    }
    
    const row = $(`
        <tr class="invoice-item-row">
            <td><input type="text" class="form-control form-control-sm item-description" placeholder="Descriere" value="${desc}" required></td>
            <td><input type="number" class="form-control form-control-sm item-quantity" min="0.01" step="0.01" value="${qty}" required></td>
            <td>
                <select class="form-control form-control-sm item-unit">
                    <option value="buc" ${unit === 'buc' ? 'selected' : ''}>buc</option>
                    <option value="ore" ${unit === 'ore' ? 'selected' : ''}>ore</option>
                    <option value="zi" ${unit === 'zi' ? 'selected' : ''}>zi</option>
                    <option value="luna" ${unit === 'luna' ? 'selected' : ''}>lună</option>
                    <option value="kg" ${unit === 'kg' ? 'selected' : ''}>kg</option>
                    <option value="m" ${unit === 'm' ? 'selected' : ''}>m</option>
                    <option value="mp" ${unit === 'mp' ? 'selected' : ''}>mp</option>
                    <option value="set" ${unit === 'set' ? 'selected' : ''}>set</option>
                </select>
            </td>
            <td><input type="number" class="form-control form-control-sm item-price" min="0" step="0.01" value="${price}" required></td>
            <td>
                <select class="form-control form-control-sm item-vat">
                    <option value="19" ${vat == 19 ? 'selected' : ''}>19%</option>
                    <option value="9" ${vat == 9 ? 'selected' : ''}>9%</option>
                    <option value="5" ${vat == 5 ? 'selected' : ''}>5%</option>
                    <option value="0" ${vat == 0 ? 'selected' : ''}>0%</option>
                </select>
            </td>
            <td><input type="text" class="form-control form-control-sm item-total" readonly value="0.00"></td>
            <td><button type="button" class="btn btn-xs btn-danger remove-item"><i class="fas fa-times"></i></button></td>
        </tr>
    `);
    
    $('#invoiceItemsTable').append(row);
    calculateInvoiceTotals();
}

$('#addInvoiceItem').on('click', function() {
    addInvoiceItemRow();
});

$(document).on('click', '.remove-item', function() {
    $(this).closest('tr').remove();
    calculateInvoiceTotals();
});

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
            name: desc,
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
    
    const invoiceData = {
        client: {
            cif: 'RO' + $('#invoice_client_cif').val(),
            name: $('#invoice_client_name').val()
        },
        issueDate: $('#invoice_date').val(),
        dueDate: $('#invoice_due_date').val() || undefined,
        seriesName: $('#invoice_series').val(),
        language: 'RO',
        products: items,
        sourceType: $('#invoice_source_type').val(),
        sourceId: $('#invoice_source_id').val() || null
    };
    
    const endpoint = $('#invoice_type').val() === 'proforma' ? 'create_oblio_proforma' : 'create_oblio_invoice';
    
    $.ajax({
        url: 'api_oblio_handlers.php?f=' + endpoint + '&user_id=' + USER_ID,
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
    
    $.post('api_oblio_handlers.php?f=get_invoice_details&user_id=' + USER_ID, { id }, function(resp) {
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
                <td>${item.description || item.name}</td>
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
            `api_oblio_handlers.php?f=download_invoice_pdf&id=${invoice.id}&user_id=` + USER_ID
        ).show();
    } else {
        $('#btnDownloadInvoicePdf').hide();
    }
}

$(document).on('click', '.cancel-invoice', function() {
    const id = $(this).data('id');
    const series = $(this).data('series');
    const number = $(this).data('number');
    
    if (!confirm(`Sigur doriți să anulați factura ${series}-${number}?`)) {
        return;
    }
    
    const btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
    
    $.post('api_oblio_handlers.php?f=cancel_oblio_invoice&user_id=' + USER_ID, {
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

$(document).on('click', '#btnSyncOblioInvoices', function() {
    const btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Se sincronizează...');
    
    $.post('api_oblio_handlers.php?f=sync_invoices_from_oblio&user_id=' + USER_ID, {
        year: new Date().getFullYear()
    }, function(resp) {
        btn.prop('disabled', false).html('<i class="fas fa-sync"></i> Sincronizează din Oblio');
        
        if (resp.success) {
            toastr.success(resp.message || 'Sincronizare completă');
            loadInvoicesForCompany(currentCompanyCUI);
        } else {
            toastr.error(resp.error || 'Eroare la sincronizare');
        }
    }, 'json').fail(() => {
        btn.prop('disabled', false).html('<i class="fas fa-sync"></i> Sincronizează din Oblio');
        toastr.error('Eroare de rețea');
    });
});

$(document).ready(function() {
    loadVatRatesForInvoices();
});