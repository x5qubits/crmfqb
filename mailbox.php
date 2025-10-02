<?php
include_once("config.php");
$pageName = "Inbox";
$pageId = 3;
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

<style>
.mailbox-messages table tbody tr:hover {
    background-color: rgba(0,0,0,.05);
    cursor: pointer;
}
.mailbox-name a { font-weight: 600; }
.mailbox-subject { color: #6c757d; }
.unread { font-weight: bold; }
.unread .mailbox-subject { color: #212529; }
</style>

<section class="content">
    <div class="row">
        <div class="col-md-3">
            <a href="compose" class="btn btn-primary btn-block mb-3">
                <i class="fas fa-pencil-alt"></i> Scrie Email
            </a>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Conturi Email</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="nav nav-pills flex-column" id="accountsList">
                        <li class="nav-item">
                            <a href="#" class="nav-link active" data-account="all">
                                <i class="fas fa-inbox"></i> Toate Conturile
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Foldere</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="nav nav-pills flex-column">
                        <li class="nav-item">
                            <a href="#" class="nav-link folder-link active" data-folder="inbox">
                                <i class="fas fa-inbox"></i> Inbox
                                <span class="badge bg-primary float-right" id="inbox-count">0</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link folder-link" data-folder="sent">
                                <i class="far fa-envelope"></i> Trimise
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link folder-link" data-folder="drafts">
                                <i class="far fa-file-alt"></i> Ciorne
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link folder-link" data-folder="junk">
                                <i class="fas fa-filter"></i> Spam
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link folder-link" data-folder="trash">
                                <i class="far fa-trash-alt"></i> Coș
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Inbox</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" id="btnSyncEmails" title="Sincronizează">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button type="button" class="btn btn-tool" onclick="location.href='mail_settings'">
                            <i class="fas fa-cog"></i>
                        </button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="mailbox-controls">
                        <button type="button" class="btn btn-default btn-sm checkbox-toggle">
                            <i class="far fa-square"></i>
                        </button>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm" id="btnDeleteSelected">
                                <i class="far fa-trash-alt"></i>
                            </button>
                            <button type="button" class="btn btn-default btn-sm" id="btnMarkRead">
                                <i class="fas fa-envelope-open"></i>
                            </button>
                        </div>
                        <button type="button" class="btn btn-default btn-sm" id="btnRefresh">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>

                    <div class="table-responsive mailbox-messages">
                        <table class="table table-hover">
                            <tbody id="emailsList"></tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer p-0">
                    <div class="mailbox-controls">
                        <div class="float-right">
                            <span id="emailsInfo">0-0/0</span>
                            <div class="btn-group">
                                <button type="button" class="btn btn-default btn-sm" id="btnPrevPage">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button type="button" class="btn btn-default btn-sm" id="btnNextPage">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/toastr/toastr.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>

<script>
let currentFolder = 'inbox';
let currentAccount = 'all';
let currentPage = 0;
const pageSize = 50;

function loadAccounts() {
    $.post('api.php?f=get_email_accounts&user_id=<?= $user_id ?>', {}, function(resp) {
        if (resp.success && resp.data && resp.data.length) {
            resp.data.forEach(acc => {
                $('#accountsList').append(`
                    <li class="nav-item">
                        <a href="#" class="nav-link account-link" data-account="${acc.id}">
                            <i class="far fa-envelope"></i> ${acc.from_email}
                        </a>
                    </li>
                `);
            });
        }
    }, 'json');
}

function loadEmails() {
    $.post('api.php?f=get_emails&user_id=<?= $user_id ?>', {
        folder: currentFolder,
        limit: pageSize,
        offset: currentPage * pageSize
    }, function(resp) {
        $('#emailsList').empty();
        
        if (resp.success && resp.data && resp.data.length) {
            resp.data.forEach(email => {
                const date = new Date(email.received_at);
                const readClass = email.is_read == 0 ? 'unread' : '';
                const starred = email.is_starred == 1 ? 'fas fa-star text-warning' : 'far fa-star';
                
                $('#emailsList').append(`
                    <tr class="email-row ${readClass}" data-id="${email.id}">
                        <td>
                            <div class="icheck-primary">
                                <input type="checkbox" value="${email.id}" id="check${email.id}">
                                <label for="check${email.id}"></label>
                            </div>
                        </td>
                        <td class="mailbox-star">
                            <a href="#" class="star-toggle" data-id="${email.id}">
                                <i class="${starred}"></i>
                            </a>
                        </td>
                        <td class="mailbox-name">
                            <a href="read_mail?id=${email.id}">${email.from_name || email.from_email}</a>
                        </td>
                        <td class="mailbox-subject">
                            <b>${email.subject}</b> - ${email.preview}
                        </td>
                        <td class="mailbox-date">${formatDate(date)}</td>
                    </tr>
                `);
            });
            
            const start = currentPage * pageSize + 1;
            const end = Math.min(start + resp.data.length - 1, resp.total);
            $('#emailsInfo').text(`${start}-${end}/${resp.total}`);
            $('#inbox-count').text(resp.total);
        } else {
            $('#emailsList').append('<tr><td colspan="5" class="text-center">Nu există emailuri</td></tr>');
            $('#emailsInfo').text('0-0/0');
        }
    }, 'json');
}

function formatDate(date) {
    const now = new Date();
    const diff = now - date;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);
    
    if (minutes < 60) return minutes + ' min';
    if (hours < 24) return hours + ' ore';
    if (days < 7) return days + ' zile';
    return date.toLocaleDateString('ro-RO');
}

$('.folder-link').click(function(e) {
    e.preventDefault();
    $('.folder-link').removeClass('active');
    $(this).addClass('active');
    currentFolder = $(this).data('folder');
    currentPage = 0;
    loadEmails();
});

$(document).on('click', '.account-link', function(e) {
    e.preventDefault();
    $('.account-link').removeClass('active');
    $(this).addClass('active');
    currentAccount = $(this).data('account');
    loadEmails();
});

$(document).on('click', '.email-row td:not(:first-child):not(:nth-child(2))', function() {
    const id = $(this).closest('tr').data('id');
    window.location.href = 'read_mail?id=' + id;
});

$(document).on('click', '.star-toggle', function(e) {
    e.preventDefault();
    e.stopPropagation();
    const id = $(this).data('id');
    const icon = $(this).find('i');
    const isStarred = icon.hasClass('fas');
    
    $.post('api.php?f=toggle_star&user_id=<?= $user_id ?>', {id}, function(resp) {
        if (resp.success) {
            icon.toggleClass('fas far');
        }
    }, 'json');
});

$('#btnSyncEmails').click(function() {
    const btn = $(this);
    btn.prop('disabled', true).find('i').addClass('fa-spin');
    
    $.post('api.php?f=get_email_accounts&user_id=<?= $user_id ?>', {}, function(resp) {
        if (resp.success && resp.data) {
            let completed = 0;
            resp.data.forEach(acc => {
                $.post('api.php?f=fetch_emails&user_id=<?= $user_id ?>', {
                    account_id: acc.id
                }, function() {
                    completed++;
                    if (completed === resp.data.length) {
                        btn.prop('disabled', false).find('i').removeClass('fa-spin');
                        loadEmails();
                        toastr.success('Emailuri sincronizate!');
                    }
                }, 'json');
            });
        }
    }, 'json');
});

$('#btnRefresh').click(function() {
    loadEmails();
});

$('#btnNextPage').click(function() {
    currentPage++;
    loadEmails();
});

$('#btnPrevPage').click(function() {
    if (currentPage > 0) {
        currentPage--;
        loadEmails();
    }
});

$('.checkbox-toggle').click(function() {
    const checks = $('.mailbox-messages input[type="checkbox"]');
    checks.prop('checked', !checks.first().prop('checked'));
});

loadAccounts();
loadEmails();
</script>

<?php include_once("WEB-INF/footer.php"); ?>