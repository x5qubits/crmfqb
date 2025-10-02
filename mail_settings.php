<?php
include_once("config.php");
$pageName = "Setări Email";
$pageId = 2;
include_once("WEB-INF/menu.php"); 
?>

<link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
<link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">
<!-- Adaugă Toastr CSS -->
<link rel="stylesheet" href="plugins/toastr/toastr.min.css"> 
<link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
<link rel="stylesheet" href="plugins/toastr/toastr.min.css">
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Conturi Email</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-primary btn-sm" id="btnAddAccount">
                        <i class="fas fa-plus"></i> Adaugă Cont
                    </button>
                </div>
            </div>
            <div class="card-body">
                <table id="accountsTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Nume</th>
                            <th>SMTP</th>
                            <th>IMAP</th>
                            <th>AI Asistent</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Add/Edit Account -->
<div class="modal fade" id="accountModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accountModalTitle">Adaugă Cont Email</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="accountForm">
                <div class="modal-body">
                    <input type="hidden" id="account_action" name="action" value="add">
                    <input type="hidden" id="account_id" name="id">
                    
                    <h6 class="border-bottom pb-2 mb-3">Informații Generale</h6>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>Adresa Email *</label>
                            <input type="email" class="form-control" id="from_email" name="from_email" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Nume Afișat *</label>
                            <input type="text" class="form-control" id="from_name" name="from_name" required>
                        </div>
                    </div>
                    
                    <h6 class="border-bottom pb-2 mb-3 mt-4">Setări SMTP (Trimitere)</h6>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>Server SMTP *</label>
                            <input type="text" class="form-control" id="smtp_host" name="smtp_host" placeholder="smtp.gmail.com" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Port *</label>
                            <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="587" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Criptare *</label>
                            <select class="form-control" id="smtp_encryption" name="smtp_encryption">
                                <option value="tls">TLS</option>
                                <option value="ssl">SSL</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>Utilizator SMTP *</label>
                            <input type="text" class="form-control" id="smtp_username" name="smtp_username" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Parolă SMTP *</label>
                            <input type="password" class="form-control" id="smtp_password" name="smtp_password">
                            <small class="form-text text-muted">Lăsați gol pentru a păstra parola actuală</small>
                        </div>
                    </div>
                    
                    <h6 class="border-bottom pb-2 mb-3 mt-4">Setări IMAP (Primire)</h6>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>Server IMAP *</label>
                            <input type="text" class="form-control" id="imap_host" name="imap_host" placeholder="imap.gmail.com" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Port *</label>
                            <input type="number" class="form-control" id="imap_port" name="imap_port" value="993" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Criptare *</label>
                            <select class="form-control" id="imap_encryption" name="imap_encryption">
                                <option value="ssl">SSL</option>
                                <option value="tls">TLS</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>Utilizator IMAP *</label>
                            <input type="text" class="form-control" id="imap_username" name="imap_username" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Parolă IMAP *</label>
                            <input type="password" class="form-control" id="imap_password" name="imap_password">
                            <small class="form-text text-muted">Lăsați gol pentru a păstra parola actuală</small>
                        </div>
                    </div>
                    
                    <h6 class="border-bottom pb-2 mb-3 mt-4">Setări Avansate</h6>
                    <div class="form-group">
                        <label>Semnătură Email</label>
                        <textarea class="form-control" id="signature" name="signature" rows="3"></textarea>
                    </div>
                    
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="ai_assistant_enabled" name="ai_assistant_enabled" value="1" checked>
                        <label class="custom-control-label" for="ai_assistant_enabled">
                            Activează asistentul AI (Gemini) pentru acest cont
                        </label>
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

<!-- Quick Setup Presets -->
<div class="row mt-3">
    <div class="col-12">
        <div class="card card-outline card-info collapsed-card">
            <div class="card-header">
                <h3 class="card-title">Configurări Rapide</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <button class="btn btn-outline-primary btn-block" data-preset="gmail">
                            <i class="fab fa-google"></i> Gmail
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-outline-info btn-block" data-preset="outlook">
                            <i class="fab fa-microsoft"></i> Outlook / Hotmail
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-outline-warning btn-block" data-preset="yahoo">
                            <i class="fab fa-yahoo"></i> Yahoo
                        </button>
                    </div>
                </div>
                <div class="alert alert-info mt-3 mb-0">
                    <i class="fas fa-info-circle"></i> <strong>Gmail:</strong> Pentru Gmail, trebuie să folosiți o "Parolă de aplicație" în loc de parola obișnuită. 
                    <a href="https://myaccount.google.com/apppasswords" target="_blank">Generați una aici</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="plugins/toastr/toastr.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>

<script>
const presets = {
    gmail: {
        smtp_host: 'smtp.gmail.com',
        smtp_port: 587,
        smtp_encryption: 'tls',
        imap_host: 'imap.gmail.com',
        imap_port: 993,
        imap_encryption: 'ssl'
    },
    outlook: {
        smtp_host: 'smtp-mail.outlook.com',
        smtp_port: 587,
        smtp_encryption: 'tls',
        imap_host: 'outlook.office365.com',
        imap_port: 993,
        imap_encryption: 'ssl'
    },
    yahoo: {
        smtp_host: 'smtp.mail.yahoo.com',
        smtp_port: 587,
        smtp_encryption: 'tls',
        imap_host: 'imap.mail.yahoo.com',
        imap_port: 993,
        imap_encryption: 'ssl'
    }
};

const table = $('#accountsTable').DataTable({
    language: {url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/ro.json"},
    order: [[0, 'asc']],
    columnDefs: [{orderable: false, targets: -1}]
});

function loadAccounts() {
    $.post('api.php?f=get_email_accounts&user_id=<?= $user_id ?>', {}, function(resp) {
        table.clear();
        if (resp.success && resp.data) {
            resp.data.forEach(acc => {
                table.row.add([
                    acc.from_email,
                    acc.from_name,
                    `${acc.smtp_host}:${acc.smtp_port}`,
                    `${acc.imap_host}:${acc.imap_port}`,
                    acc.ai_assistant_enabled ? '<span class="badge badge-success">Da</span>' : '<span class="badge badge-secondary">Nu</span>',
                    `<button class="btn btn-xs btn-info edit-account" data-id="${acc.id}"><i class="fas fa-edit"></i></button>
                     <button class="btn btn-xs btn-danger delete-account" data-id="${acc.id}"><i class="fas fa-trash"></i></button>`
                ]);
            });
        }
        table.draw();
    }, 'json');
}

$('#btnAddAccount').click(function() {
    $('#accountModalTitle').text('Adaugă Cont Email');
    $('#account_action').val('add');
    $('#accountForm')[0].reset();
    $('#account_id').val('');
    $('#accountModal').modal('show');
});

$(document).on('click', '.edit-account', function() {
    const id = $(this).data('id');
    $.post('api.php?f=get_email_accounts&user_id=<?= $user_id ?>', {}, function(resp) {
        if (resp.success) {
            const acc = resp.data.find(a => a.id == id);
            if (acc) {
                $('#accountModalTitle').text('Editează Cont Email');
                $('#account_action').val('edit');
                $('#account_id').val(acc.id);
                $('#from_email').val(acc.from_email);
                $('#from_name').val(acc.from_name);
                $('#smtp_host').val(acc.smtp_host);
                $('#smtp_port').val(acc.smtp_port);
                $('#smtp_encryption').val(acc.smtp_encryption);
                $('#smtp_username').val(acc.smtp_username);
                $('#imap_host').val(acc.imap_host);
                $('#imap_port').val(acc.imap_port);
                $('#imap_encryption').val(acc.imap_encryption);
                $('#imap_username').val(acc.imap_username);
                $('#signature').val(acc.signature);
                $('#ai_assistant_enabled').prop('checked', acc.ai_assistant_enabled == 1);
                $('#accountModal').modal('show');
            }
        }
    }, 'json');
});

$(document).on('click', '.delete-account', function() {
    const id = $(this).data('id');
    if (confirm('Sigur doriți să ștergeți acest cont?')) {
        $.post('api.php?f=delete_email_account&user_id=<?= $user_id ?>', {id}, function(resp) {
            if (resp.success) {
                toastr.success(resp.message);
                loadAccounts();
            } else {
                toastr.error(resp.error);
            }
        }, 'json');
    }
});

$('#accountForm').submit(function(e) {
    e.preventDefault();
    $.post('api.php?f=save_email_account&user_id=<?= $user_id ?>', $(this).serialize(), function(resp) {
        if (resp.success) {
            toastr.success(resp.message);
            $('#accountModal').modal('hide');
            loadAccounts();
        } else {
            toastr.error(resp.error);
        }
    }, 'json');
});

$('[data-preset]').click(function() {
    const preset = presets[$(this).data('preset')];
    Object.keys(preset).forEach(key => {
        $(`#${key}`).val(preset[key]);
    });
    $('#btnAddAccount').click();
});

loadAccounts();
</script>

<?php include_once("WEB-INF/footer.php"); ?>