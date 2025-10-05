
// REMINDER-URI
function loadReminders() {
    ajaxGet('get_reminders', function(r) {
        const tb = $('#remindersTable tbody');
        tb.empty();
        r.data.forEach(function(d) {
            const typeLabels = {BIRTHDAY: 'Zi Naștere', CUSTOM_DATE_1: 'Dată Custom 1', CUSTOM_DATE_2: 'Dată Custom 2'};
            const tr = $('<tr>').attr('data-id', d.id);
            tr.append($('<td>').text(d.id));
            tr.append($('<td>').text(d.title));
            tr.append($('<td>').html('<span class="badge badge-info">' + typeLabels[d.reminder_type] + '</span>'));
            tr.append($('<td>').text(d.days_before + ' zile'));
            tr.append($('<td>').html('<span class="badge badge-primary">' + d.channel + '</span>'));
            tr.append($('<td>').html('<span class="badge ' + (d.active == 1 ? 'badge-success' : 'badge-secondary') + '">' + (d.active == 1 ? 'Activ' : 'Inactiv') + '</span>'));
            tr.append($('<td>').html(
                '<button class="btn btn-sm btn-warning btn-action btn-toggle-reminder" title="Activează/Dezactivează"><i class="fas fa-toggle-' + (d.active == 1 ? 'on' : 'off') + '"></i></button> ' +
                '<button class="btn btn-sm btn-info btn-action btn-edit-reminder" title="Editează"><i class="fas fa-edit"></i></button> ' +
                '<button class="btn btn-sm btn-danger btn-delete" data-type="reminder"><i class="fas fa-trash"></i></button>'
            ));
            tb.append(tr);
        });
    });
}
    loadUpcomingBirthdays();// Campaign Manager Scripts - Versiune Completă în Română
let currentTab = 'campaigns';
let campaignsTable, categoriesTable, itemsTable, queueTable;
let confirmCallback = null;
let csvHeaders = [];
let csvSampleData = [];
let currentCategoryFilter = '';

// Funcții Utilitare
function notify(msg, type = 'success') {
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        timeOut: 3000
    };
    if (type === 'error') toastr.error(msg);
    else if (type === 'warning') toastr.warning(msg);
    else if (type === 'info') toastr.info(msg);
    else toastr.success(msg);
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

function ajaxPost(data, success, error) {
    data.ajax = '1';
    $.ajax({
        url: window.location.pathname,
        method: 'POST',
        data: data,
        dataType: 'json',
        success: function(r) {
            if (r.success) {
                success && success(r);
            } else {
                notify(r.error || 'Operațiune eșuată', 'error');
                error && error(r);
            }
        },
        error: function() {
            notify('Eroare de rețea', 'error');
            error && error();
        }
    });
}

function ajaxGet(action, success, error) {
    $.ajax({
        url: window.location.pathname + '?ajax=1&action=' + action,
        method: 'GET',
        dataType: 'json',
        success: function(r) {
            if (r.success) {
                success && success(r);
            } else {
                notify(r.error || 'Eșec la încărcarea datelor', 'error');
                error && error(r);
            }
        },
        error: function() {
            notify('Eroare de rețea', 'error');
            error && error();
        }
    });
}

function showConfirmModal(message, callback) {
    $('#confirmMessage').text(message);
    confirmCallback = callback;
    $('#confirmModal').modal('show');
}

$('#confirmButton').click(function() {
    $('#confirmModal').modal('hide');
    if (confirmCallback) {
        confirmCallback();
        confirmCallback = null;
    }
});

// Managementul Tab-urilor
$('.nav-link[data-tab]').click(function(e) {
    e.preventDefault();
    switchTab($(this).data('tab'));
});

function switchTab(tab) {
    currentTab = tab;
    $('.nav-link').removeClass('active');
    $('.nav-link[data-tab="' + tab + '"]').addClass('active');
    $('.tab-pane').removeClass('active');
    $('#' + tab + '-tab').addClass('active');
    
    if (tab === 'campaigns') loadCampaigns();
    else if (tab === 'categories') loadCategories();
    else if (tab === 'items') loadItems();
    else if (tab === 'reminders') loadReminders();
    else if (tab === 'queue') loadQueue();
}

// Încărcare Statistici
function loadStats() {
    ajaxGet('get_stats', function(r) {
        $('#stat-campaigns').text(r.data.campaigns);
        $('#stat-categories').text(r.data.categories);
        $('#stat-items').text(r.data.items);
        $('#stat-queued').text(r.data.queued);
    });
}

// CAMPANII
function loadCampaigns() {
    ajaxGet('get_campaigns', function(r) {
        if (campaignsTable) {
            campaignsTable.clear().destroy();
        }
        const tb = $('#campaignsTable tbody');
        tb.empty();
        r.data.forEach(function(d) {
            const sc = 'status-' + d.status;
            const tr = $('<tr>').attr('data-id', d.id);
            tr.append($('<td>').text(d.id));
            tr.append($('<td>').text(d.title));
            tr.append($('<td>').html('<span class="badge badge-primary">' + d.channel + '</span>'));
            tr.append($('<td>').text(d.schedule_time));
            tr.append($('<td>').html('<span class="badge ' + sc + '">' + d.status + '</span>'));
            tr.append($('<td>').html('<small>' + escapeHtml(d.created_at) + '</small>'));
            tr.append($('<td>').html('<button class="btn btn-info btn-sm btn-action btn-edit-campaign" title="Editează"><i class="fas fa-edit"></i></button> <button class="btn btn-danger btn-sm btn-delete" data-type="campaign"><i class="fas fa-trash"></i></button>'));
            tb.append(tr);
        });
        campaignsTable = $('#campaignsTable').DataTable({
            responsive: true,
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                search: "Caută:",
                lengthMenu: "Afișează _MENU_ înregistrări",
                info: "Afișare _START_ la _END_ din _TOTAL_ înregistrări",
                infoEmpty: "Nicio înregistrare disponibilă",
                infoFiltered: "(filtrat din _MAX_ înregistrări totale)",
                paginate: {first: "Primul", last: "Ultimul", next: "Următorul", previous: "Precedentul"},
                zeroRecords: "Nu s-au găsit înregistrări"
            }
        });
    });
}

$('#campaignForm').submit(function(e) {
    e.preventDefault();
    const fd = $(this).serializeArray().reduce((o, i) => { o[i.name] = i.value; return o; }, {});
    fd.action = 'add_campaign';
    ajaxPost(fd, function() {
        notify('Campanie creată cu succes');
        loadCampaigns();
        loadStats();
        $('#campaignForm')[0].reset();
    });
});

$(document).on('click', '.btn-edit-campaign', function() {
    const id = $(this).closest('tr').data('id');
    $.ajax({
        url: window.location.pathname + '?ajax=1&action=get_campaign&id=' + id,
        method: 'GET',
        dataType: 'json',
        success: function(r) {
            if (r.success && r.data) {
                $('#edit_campaign_id').val(r.data.id);
                $('#edit_title').val(r.data.title);
                $('#edit_channel').val(r.data.channel);
                $('#edit_status').val(r.data.status);
                $('#edit_schedule_time').val(r.data.schedule_time);
                $('#edit_subject').val(r.data.subject);
                $('#edit_body_template').val(r.data.body_template);
                $('#editCampaignModal').modal('show');
            }
        }
    });
});

$('#editCampaignForm').submit(function(e) {
    e.preventDefault();
    const fd = $(this).serializeArray().reduce((o, i) => { o[i.name] = i.value; return o; }, {});
    fd.action = 'update_campaign';
    ajaxPost(fd, function() {
        notify('Campanie actualizată cu succes');
        $('#editCampaignModal').modal('hide');
        loadCampaigns();
    });
});

// CATEGORII
function loadCategories() {
    ajaxGet('get_categories', function(r) {
        if (categoriesTable) { categoriesTable.clear().destroy(); }
        const tb = $('#categoriesTable tbody');
        tb.empty();
        r.data.forEach(function(d) {
            const tr = $('<tr>').attr('data-id', d.id);
            tr.append($('<td>').text(d.id));
            tr.append($('<td>').text(d.name));
            tr.append($('<td>').text(d.description || ''));
            tr.append($('<td>').html('<span class="badge badge-info">' + d.item_count + ' contacte</span>'));
            tr.append($('<td>').html('<small>' + escapeHtml(d.created_at) + '</small>'));
            tr.append($('<td>').html('<button class="btn btn-info btn-sm btn-action btn-edit-category" title="Editează"><i class="fas fa-edit"></i></button> <button class="btn btn-danger btn-sm btn-delete" data-type="category"><i class="fas fa-trash"></i></button>'));
            tb.append(tr);
        });
        categoriesTable = $('#categoriesTable').DataTable({
            responsive: true, pageLength: 25, order: [[0, 'desc']],
            language: {
                search: "Caută:", lengthMenu: "Afișează _MENU_ înregistrări",
                info: "Afișare _START_ la _END_ din _TOTAL_ înregistrări",
                infoEmpty: "Nicio înregistrare disponibilă",
                infoFiltered: "(filtrat din _MAX_ înregistrări totale)",
                paginate: {first: "Primul", last: "Ultimul", next: "Următorul", previous: "Precedentul"},
                zeroRecords: "Nu s-au găsit înregistrări"
            }
        });
        loadCategorySelects();
    });
}

$('#categoryForm').submit(function(e) {
    e.preventDefault();
    const fd = $(this).serializeArray().reduce((o, i) => { o[i.name] = i.value; return o; }, {});
    fd.action = 'add_category';
    ajaxPost(fd, function() {
        notify('Categorie creată cu succes');
        loadCategories();
        loadStats();
        $('#categoryForm')[0].reset();
    });
});

$(document).on('click', '.btn-edit-category', function() {
    const id = $(this).closest('tr').data('id');
    $.ajax({
        url: window.location.pathname + '?ajax=1&action=get_category&id=' + id,
        method: 'GET', dataType: 'json',
        success: function(r) {
            if (r.success && r.data) {
                $('#edit_category_id').val(r.data.id);
                $('#edit_category_name').val(r.data.name);
                $('#edit_category_description').val(r.data.description);
                $('#editCategoryModal').modal('show');
            }
        }
    });
});

$('#editCategoryForm').submit(function(e) {
    e.preventDefault();
    const fd = $(this).serializeArray().reduce((o, i) => { o[i.name] = i.value; return o; }, {});
    fd.action = 'update_category_full';
    ajaxPost(fd, function() {
        notify('Categorie actualizată cu succes');
        $('#editCategoryModal').modal('hide');
        loadCategories();
    });
});

// CONTACTE
function loadItems() {
    if (itemsTable) { itemsTable.destroy(); }
    itemsTable = $('#itemsTable').DataTable({
        processing: true, serverSide: true,
        ajax: {
            url: window.location.pathname,
            data: function(d) {
                d.ajax = '1';
                d.action = 'get_items_datatable';
                if (currentCategoryFilter) d.category_id = currentCategoryFilter;
            }
        },
        columns: [
            {data: 'id'},
            {data: 'cat_name', render: function(data) { return '<span class="badge badge-secondary">' + escapeHtml(data) + '</span>'; }},
            {data: 'label', defaultContent: ''},
            {data: 'email', defaultContent: ''},
            {data: 'phone', defaultContent: ''},
            {data: 'memo', defaultContent: ''},
            {data: null, orderable: false, render: function() {
                return '<button class="btn btn-info btn-sm btn-action btn-edit-item" title="Editează"><i class="fas fa-edit"></i></button> ' +
                       '<button class="btn btn-warning btn-sm btn-action btn-send-custom" title="Trimite Mesaj"><i class="fas fa-paper-plane"></i></button> ' +
                       '<button class="btn btn-danger btn-sm btn-delete" data-type="item"><i class="fas fa-trash"></i></button>';
            }}
        ],
        pageLength: 50, order: [[0, 'desc']], responsive: true,
        language: {
            search: "Caută:", lengthMenu: "Afișează _MENU_ înregistrări",
            info: "Afișare _START_ la _END_ din _TOTAL_ înregistrări",
            infoEmpty: "Nicio înregistrare disponibilă",
            infoFiltered: "(filtrat din _MAX_ înregistrări totale)",
            paginate: {first: "Primul", last: "Ultimul", next: "Următorul", previous: "Precedentul"},
            zeroRecords: "Nu s-au găsit înregistrări", processing: "Se procesează..."
        }
    });
}

$('#btnApplyFilters').click(function() {
    currentCategoryFilter = $('#filterCategory').val();
    loadItems();
});

$('#btnClearFilters').click(function() {
    $('#filterCategory').val('');
    currentCategoryFilter = '';
    loadItems();
});

$('#btnBulkDelete').click(function() {
    const cat_id = $('#bulkDeleteCategory').val();
    if (!cat_id) { notify('Te rog selectează o categorie', 'warning'); return; }
    const cat_name = $('#bulkDeleteCategory option:selected').text();
    showConfirmModal('Sigur vrei să ștergi TOATE contactele din categoria "' + cat_name + '"? Această acțiune nu poate fi anulată!', function() {
        ajaxPost({action: 'bulk_delete_by_category', category_id: cat_id}, function(r) {
            notify('Au fost șterse ' + r.count + ' contacte din categoria "' + cat_name + '"');
            $('#bulkDeleteCategory').val('');
            loadItems(); loadStats(); loadCategories();
        });
    });
});

$('#itemForm').submit(function(e) {
    e.preventDefault();
    const fd = $(this).serializeArray().reduce((o, i) => { o[i.name] = i.value; return o; }, {});
    fd.action = 'add_item';
    ajaxPost(fd, function() {
        notify('Contact adăugat cu succes');
        loadItems(); loadStats();
        $('#itemForm')[0].reset();
    });
});

$(document).on('click', '.btn-edit-item', function() {
    const row = itemsTable.row($(this).closest('tr')).data();
    $.ajax({
        url: window.location.pathname + '?ajax=1&action=get_item&id=' + row.id,
        method: 'GET', dataType: 'json',
        success: function(r) {
            if (r.success && r.data) {
                $('#edit_item_id').val(r.data.id);
                $('#edit_item_category').val(r.data.category_id);
                $('#edit_item_label').val(r.data.label);
                $('#edit_item_email').val(r.data.email);
                $('#edit_item_phone').val(r.data.phone);
                $('#edit_item_memo').val(r.data.memo);
                $('#editItemModal').modal('show');
            }
        }
    });
});

$('#editItemForm').submit(function(e) {
    e.preventDefault();
    const fd = $(this).serializeArray().reduce((o, i) => { o[i.name] = i.value; return o; }, {});
    fd.action = 'update_item_full';
    ajaxPost(fd, function() {
        notify('Contact actualizat cu succes');
        $('#editItemModal').modal('hide');
        loadItems();
    });
});

// IMPORT CSV
$('#bulkFormat').change(function() {
    if ($(this).val() === 'csv') $('#btnPreviewCSV').show();
    else $('#btnPreviewCSV').hide();
});

$('#btnPreviewCSV').click(function() {
    const file = $('#bulkFile')[0].files[0];
    if (!file) { notify('Te rog selectează mai întâi un fișier', 'warning'); return; }
    const fd = new FormData();
    fd.append('file', file); fd.append('ajax', '1'); fd.append('action', 'get_csv_preview');
    $.ajax({
        url: window.location.pathname, method: 'POST', data: fd,
        processData: false, contentType: false, dataType: 'json',
        success: function(r) {
            if (r.success) { csvHeaders = r.headers; csvSampleData = r.sample; showCSVMapping(); }
            else notify(r.error || 'Eșec la previzualizarea CSV', 'error');
        },
        error: function() { notify('Eroare de rețea la previzualizare', 'error'); }
    });
});

function showCSVMapping() {
    const fields = ['label', 'email', 'phone', 'memo'];
    const fieldLabels = {label: 'Nume/Etichetă', email: 'Adresă Email', phone: 'Număr Telefon', memo: 'Notițe/Memo'};
    let html = '<div class="csv-mapping-container">';
    fields.forEach(field => {
        html += '<div class="csv-field-row"><label>' + fieldLabels[field] + ':</label>';
        html += '<select class="form-control csv-field-select" data-field="' + field + '"><option value="">-- Omite --</option>';
        csvHeaders.forEach(header => {
            const selected = header.toLowerCase().includes(field) || field.includes(header.toLowerCase()) ? ' selected' : '';
            html += '<option value="' + escapeHtml(header) + '"' + selected + '>' + escapeHtml(header) + '</option>';
        });
        html += '</select></div>';
    });
    html += '</div>';
    let sampleHtml = '<h6 class="text-muted">Previzualizare Date Exemplu:</h6><table class="sample-data-table table table-sm table-bordered"><tr>';
    csvHeaders.forEach(h => sampleHtml += '<th>' + escapeHtml(h) + '</th>');
    sampleHtml += '</tr>';
    csvSampleData.forEach(row => {
        sampleHtml += '<tr>';
        row.forEach(cell => sampleHtml += '<td>' + escapeHtml(cell) + '</td>');
        sampleHtml += '</tr>';
    });
    sampleHtml += '</table>';
    $('#csvMappingFields').html(html);
    $('#csvSampleData').html(sampleHtml);
    $('#csvMappingModal').modal('show');
}

$('#btnConfirmMapping').click(function() {
    const mapping = {};
    $('.csv-field-select').each(function() {
        const field = $(this).data('field'), csvColumn = $(this).val();
        if (csvColumn) mapping[csvColumn] = field;
    });
    $('#csvMappingModal').modal('hide');
    const fd = new FormData($('#bulkImportForm')[0]);
    fd.append('ajax', '1'); fd.append('action', 'bulk_import_items'); fd.append('field_mapping', JSON.stringify(mapping));
    $.ajax({
        url: window.location.pathname, method: 'POST', data: fd,
        processData: false, contentType: false, dataType: 'json',
        success: function(r) {
            if (r.success) {
                notify('Au fost importate cu succes ' + r.count + ' contacte');
                loadItems(); loadStats();
                $('#bulkImportForm')[0].reset(); $('#btnPreviewCSV').hide();
            } else notify(r.error || 'Import eșuat', 'error');
        },
        error: function() { notify('Eroare de rețea la import', 'error'); }
    });
});

$('#bulkImportForm').submit(function(e) {
    e.preventDefault();
    if ($('#bulkFormat').val() === 'csv' && $('#btnPreviewCSV').is(':visible'))
        notify('Sugestie: Folosește "Previzualizare & Mapare Câmpuri" pentru control mai bun', 'info');
    const fd = new FormData(this);
    fd.append('ajax', '1'); fd.append('action', 'bulk_import_items');
    $.ajax({
        url: window.location.pathname, method: 'POST', data: fd,
        processData: false, contentType: false, dataType: 'json',
        success: function(r) {
            if (r.success) {
                notify('Au fost importate cu succes ' + r.count + ' contacte');
                loadItems(); loadStats();
                $('#bulkImportForm')[0].reset(); $('#btnPreviewCSV').hide();
            } else notify(r.error || 'Import eșuat', 'error');
        },
        error: function() { notify('Eroare de rețea la import', 'error'); }
    });
});

$('#reminderForm').submit(function(e) {
    e.preventDefault();
    const fd = $(this).serializeArray().reduce((o, i) => { o[i.name] = i.value; return o; }, {});
    fd.action = 'add_reminder';
    if ($('#reminder_active').is(':checked')) fd.active = 1;
    ajaxPost(fd, function() {
        notify('Reminder creat cu succes');
        loadReminders();
        $('#reminderForm')[0].reset();
    });
});

$(document).on('click', '.btn-edit-reminder', function() {
    const id = $(this).closest('tr').data('id');
    $.ajax({
        url: window.location.pathname + '?ajax=1&action=get_reminder&id=' + id,
        method: 'GET', dataType: 'json',
        success: function(r) {
            if (r.success && r.data) {
                $('#edit_reminder_id').val(r.data.id);
                $('#edit_reminder_title').val(r.data.title);
                $('#edit_reminder_type').val(r.data.reminder_type);
                $('#edit_reminder_days').val(r.data.days_before);
                $('#edit_reminder_channel').val(r.data.channel);
                $('#edit_reminder_active').prop('checked', r.data.active == 1);
                $('#edit_reminder_template').val(r.data.message_template);
                $('#editReminderModal').modal('show');
            }
        }
    });
});

$('#editReminderForm').submit(function(e) {
    e.preventDefault();
    const fd = $(this).serializeArray().reduce((o, i) => { o[i.name] = i.value; return o; }, {});
    fd.action = 'update_reminder';
    if ($('#edit_reminder_active').is(':checked')) fd.active = 1;
    ajaxPost(fd, function() {
        notify('Reminder actualizat cu succes');
        $('#editReminderModal').modal('hide');
        loadReminders();
    });
});

$(document).on('click', '.btn-toggle-reminder', function() {
    const id = $(this).closest('tr').data('id');
    ajaxPost({action: 'toggle_reminder', id: id}, function() {
        notify('Status reminder actualizat');
        loadReminders();
    });
});

$('#btnProcessReminders').click(function() {
    const btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesare...');
    ajaxPost({action: 'process_reminders'}, function(r) {
        notify('Au fost procesate ' + r.processed + ' reminder-uri și adăugate în coadă');
        btn.prop('disabled', false).html('<i class="fas fa-sync"></i> Procesează Acum');
        loadQueue();
        loadStats();
    }, function() {
        btn.prop('disabled', false).html('<i class="fas fa-sync"></i> Procesează Acum');
    });
});

function loadUpcomingBirthdays() {
    ajaxGet('get_upcoming_birthdays', function(r) {
        const container = $('#upcomingBirthdays');
        if (r.data.length === 0) {
            container.html('<p class="text-muted text-center">Nu există zile de naștere în următoarele 30 de zile.</p>');
            return;
        }
        let html = '<div class="list-group">';
        r.data.forEach(function(d) {
            const daysText = d.days_until == 0 ? 'ASTĂZI!' : d.days_until == 1 ? 'Mâine' : 'În ' + d.days_until + ' zile';
            const badgeClass = d.days_until == 0 ? 'badge-danger' : d.days_until <= 7 ? 'badge-warning' : 'badge-info';
            html += '<div class="list-group-item d-flex justify-content-between align-items-center">';
            html += '<div><strong>' + escapeHtml(d.label) + '</strong><br><small class="text-muted">' + escapeHtml(d.phone || d.email) + ' - ' + d.birthdate + '</small></div>';
            html += '<span class="badge ' + badgeClass + '">' + daysText + '</span>';
            html += '</div>';
        });
        html += '</div>';
        container.html(html);
    });
}

// Actualizare formular editare contact
$(document).on('click', '.btn-edit-item', function() {
    const row = itemsTable.row($(this).closest('tr')).data();
    $.ajax({
        url: window.location.pathname + '?ajax=1&action=get_item&id=' + row.id,
        method: 'GET', dataType: 'json',
        success: function(r) {
            if (r.success && r.data) {
                $('#edit_item_id').val(r.data.id);
                $('#edit_item_category').val(r.data.category_id);
                $('#edit_item_label').val(r.data.label);
                $('#edit_item_email').val(r.data.email);
                $('#edit_item_phone').val(r.data.phone);
                $('#edit_item_memo').val(r.data.memo);
                $('#edit_item_birthdate').val(r.data.birthdate);
                $('#editItemModal').modal('show');
            }
        }
    });
});

// MESAJ PERSONALIZAT
$(document).on('click', '.btn-send-custom', function() {
    const row = itemsTable.row($(this).closest('tr')).data();
    $('#custom_contact_id').val(row.id);
    $('#custom_contact_name').val(row.label + ' (' + (row.phone || row.email) + ')');
    $('#custom_message').val('');
    $('#customMessageModal').modal('show');
});

$('#customMessageForm').submit(function(e) {
    e.preventDefault();
    const data = {
        action: 'send_custom_message',
        contact_id: $('#custom_contact_id').val(),
        channel: $('#custom_channel').val(),
        message: $('#custom_message').val()
    };
    ajaxPost(data, function(r) {
        notify(r.message || 'Mesaj adăugat în coadă cu succes');
        $('#customMessageModal').modal('hide');
        loadQueue(); loadStats();
    });
});

// COADĂ
function loadQueue() {
    ajaxGet('get_queue', function(r) {
        if (queueTable) { queueTable.clear().destroy(); }
        const tb = $('#queueTable tbody');
        tb.empty();
        r.data.forEach(function(d) {
            const sc = 'status-' + d.status, ci = d.channel === 'EMAIL' ? d.email : d.phone;
            const tr = $('<tr>').attr('data-id', d.id);
            tr.append($('<td>').text(d.id));
            tr.append($('<td>').text(d.title));
            tr.append($('<td>').html('<span class="badge badge-secondary">' + escapeHtml(d.cat_name) + '</span>'));
            tr.append($('<td>').html('<strong>' + escapeHtml(d.label) + '</strong><br><small class="text-muted">' + escapeHtml(ci) + '</small>'));
            tr.append($('<td>').html('<span class="badge badge-info">' + d.channel + '</span>'));
            tr.append($('<td>').html('<span class="badge ' + sc + '">' + d.status + '</span>'));
            tr.append($('<td>').html('<small>' + escapeHtml(d.sent_at || '') + '</small>'));
            tr.append($('<td>').html(
                '<button class="btn btn-outline-warning btn-sm btn-action btn-queue-status" data-status="QUEUE" title="Resetează în Coadă"><i class="fas fa-undo"></i></button> ' +
                '<button class="btn btn-outline-success btn-sm btn-action btn-queue-status" data-status="SENT" title="Marchează ca Trimis"><i class="fas fa-check"></i></button> ' +
                '<button class="btn btn-outline-secondary btn-sm btn-action btn-queue-status" data-status="SKIP" title="Omite"><i class="fas fa-forward"></i></button> ' +
                '<button class="btn btn-danger btn-sm btn-action btn-delete" data-type="queue" title="Șterge"><i class="fas fa-trash"></i></button>'
            ));
            tb.append(tr);
        });
        queueTable = $('#queueTable').DataTable({
            responsive: true, pageLength: 50, order: [[0, 'desc']],
            language: {
                search: "Caută:", lengthMenu: "Afișează _MENU_ înregistrări",
                info: "Afișare _START_ la _END_ din _TOTAL_ înregistrări",
                infoEmpty: "Nicio înregistrare disponibilă",
                infoFiltered: "(filtrat din _MAX_ înregistrări totale)",
                paginate: {first: "Primul", last: "Ultimul", next: "Următorul", previous: "Precedentul"},
                zeroRecords: "Nu s-au găsit înregistrări"
            }
        });
    });
    loadCampaignSelects();
}

$('#queueForm').submit(function(e) {
    e.preventDefault();
    const cids = [];
    $('#queueCategoriesCheckboxes input:checked').each(function() { cids.push($(this).val()); });
    if (cids.length === 0) { notify('Te rog selectează cel puțin o categorie', 'warning'); return; }
    const fd = { action: 'enqueue', campaign_id: $('#queueCampaignSelect').val(), 'category_ids[]': cids };
    ajaxPost(fd, function(r) {
        notify('Au fost adăugate în coadă ' + r.count + ' mesaje cu succes');
        loadQueue(); loadStats();
        $('#queueForm')[0].reset();
        $('#queueCategoriesCheckboxes input').prop('checked', false);
    });
});

$('#btnEmptyQueue').click(function() {
    showConfirmModal('Sigur vrei să golești întreaga coadă? Aceasta va șterge toate mesajele în așteptare.', function() {
        ajaxPost({action: 'empty_queue'}, function(r) {
            notify('Coadă golită. Au fost șterse ' + r.count + ' mesaje.');
            loadQueue(); loadStats();
        });
    });
});

$(document).on('click', '.btn-queue-status', function() {
    const id = $(this).closest('tr').data('id'), status = $(this).data('status');
    ajaxPost({action: 'update_queue_status', id: id, status: status}, function() {
        notify('Status actualizat');
        loadQueue(); loadStats();
    });
});

// SELECTARE CATEGORII
function loadCategorySelects() {
    ajaxGet('get_categories_list', function(r) {
        const sel = $('#itemCategorySelect, #bulkCategorySelect, #filterCategory, #bulkDeleteCategory, #edit_item_category');
        sel.find('option:not(:first)').remove();
        r.data.forEach(function(c) { sel.append($('<option>').val(c.id).text(c.name)); });
        const cb = $('#queueCategoriesCheckboxes');
        cb.empty();
        r.data.forEach(function(c) {
            const l = $('<label>').addClass('d-block mb-1');
            const ch = $('<input>').attr({type: 'checkbox', name: 'category_ids[]', value: c.id});
            l.append(ch).append(' <strong>' + escapeHtml(c.name) + '</strong>');
            cb.append(l);
        });
    });
}

function loadCampaignSelects() {
    ajaxGet('get_campaigns_list', function(r) {
        const sel = $('#queueCampaignSelect');
        sel.find('option:not(:first)').remove();
        r.data.forEach(function(c) {
            sel.append($('<option>').val(c.id).text(c.title + ' [' + c.channel + ']'));
        });
    });
}

// ȘTERGERE
$(document).on('click', '.btn-delete', function() {
    const type = $(this).data('type');
    let id;
    if (type === 'item') {
        const row = itemsTable.row($(this).closest('tr')).data();
        id = row.id;
    } else {
        id = $(this).closest('tr').data('id');
    }
    const typeRo = {'campaign': 'campania', 'category': 'categoria', 'item': 'contactul', 'queue': 'mesajul din coadă', 'reminder': 'reminder-ul'};
    showConfirmModal('Sigur vrei să ștergi ' + typeRo[type] + '?', function() {
        ajaxPost({action: 'delete_' + type, id: id}, function() {
            notify('Șters cu succes');
            if (type === 'campaign') loadCampaigns();
            else if (type === 'category') loadCategories();
            else if (type === 'item') loadItems();
            else if (type === 'reminder') loadReminders();
            else if (type === 'queue') loadQueue();
            loadStats();
        });
    });
});

// INIȚIALIZARE
$(document).ready(function() {
    loadStats();
    loadCampaigns();
    loadCategorySelects();
    loadCampaignSelects();
});