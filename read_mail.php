<?php
include_once("config.php");
$pageName = "Citește Email";
$pageId = 3;
$email_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
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
.email-body {
    min-height: 200px;
    padding: 15px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    background: #fff;
}
.ai-reply-box {
    background: #f8f9fa;
    border: 2px dashed #007bff;
    padding: 20px;
    border-radius: 8px;
    margin-top: 20px;
}
.conversation-thread {
    max-height: 400px;
    overflow-y: auto;
}
</style>

<section class="content">
    <div class="row">
        <div class="col-md-3">
            <a href="mailbox" class="btn btn-primary btn-block mb-3">Înapoi la Inbox</a>
        </div>

        <div class="col-md-9">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title" id="emailSubject">Se încarcă...</h3>
                    <div class="card-tools">
                        <a href="#" class="btn btn-tool" id="btnPrevEmail" title="Email Anterior">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <a href="#" class="btn btn-tool" id="btnNextEmail" title="Email Următor">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="mailbox-read-info">
                        <h5 id="emailSubjectFull">Se încarcă...</h5>
                        <h6>
                            <span id="emailFrom"></span>
                            <span class="mailbox-read-time float-right" id="emailDate"></span>
                        </h6>
                    </div>

                    <div class="mailbox-controls with-border text-center">
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm" id="btnDelete">
                                <i class="far fa-trash-alt"></i> Șterge
                            </button>
                            <button type="button" class="btn btn-default btn-sm" id="btnReply">
                                <i class="fas fa-reply"></i> Răspunde
                            </button>
                            <button type="button" class="btn btn-default btn-sm" id="btnForward">
                                <i class="fas fa-share"></i> Forward
                            </button>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" id="btnAiReply">
                            <i class="fas fa-robot"></i> AI Răspuns (Gemini)
                        </button>
                        <button type="button" class="btn btn-default btn-sm" id="btnPrint">
                            <i class="fas fa-print"></i> Printează
                        </button>
                    </div>

                    <div class="mailbox-read-message" id="emailBody">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin fa-3x"></i>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-white" id="attachmentsSection" style="display:none;">
                    <ul class="mailbox-attachments d-flex align-items-stretch clearfix" id="attachmentsList">
                    </ul>
                </div>

                <div class="card-footer">
                    <div class="float-right">
                        <button type="button" class="btn btn-default" id="btnReply2">
                            <i class="fas fa-reply"></i> Răspunde
                        </button>
                        <button type="button" class="btn btn-default" id="btnForward2">
                            <i class="fas fa-share"></i> Forward
                        </button>
                    </div>
                    <button type="button" class="btn btn-default" id="btnDelete2">
                        <i class="far fa-trash-alt"></i> Șterge
                    </button>
                </div>
            </div>

            <!-- AI Reply Box -->
            <div class="card card-info collapsed-card" id="aiReplyCard" style="display:none;">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-robot"></i> Răspuns Generat de AI (Gemini)
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Instrucțiuni pentru AI (opțional)</label>
                        <input type="text" class="form-control" id="aiPrompt" placeholder="Ex: Răspunde formal, menționează termenul de livrare...">
                    </div>
                    <button type="button" class="btn btn-primary" id="btnGenerateReply">
                        <i class="fas fa-magic"></i> Generează Răspuns
                    </button>
                    
                    <div id="aiReplyContent" style="display:none; margin-top: 20px;">
                        <div class="alert alert-info">
                            <strong>Răspuns AI:</strong>
                            <div id="aiGeneratedText" style="margin-top: 10px; white-space: pre-wrap;"></div>
                        </div>
                        <button type="button" class="btn btn-success" id="btnUseAiReply">
                            <i class="fas fa-check"></i> Folosește acest răspuns
                        </button>
                        <button type="button" class="btn btn-secondary" id="btnRegenerateReply">
                            <i class="fas fa-redo"></i> Regenerează
                        </button>
                    </div>
                </div>
            </div>

            <!-- Conversation Thread -->
            <div class="card card-secondary collapsed-card" id="threadCard" style="display:none;">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-comments"></i> Conversație Anterioară (<span id="threadCount">0</span>)
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body conversation-thread" id="threadContent">
                </div>
            </div>
        </div>
    </div>
</section>
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirm deletion</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this email?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Delete</button>
      </div>
    </div>
  </div>
</div>
<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/toastr/toastr.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>

<script>
const emailId = <?= $email_id ?>;
let currentEmail = null;

function loadEmail() {
    $.post('api.php?f=get_email&user_id=<?= $user_id ?>', {id: emailId}, function(resp) {
        if (resp.success) {
            currentEmail = resp.data;
            
            $('#emailSubject').text(currentEmail.subject);
            $('#emailSubjectFull').text(currentEmail.subject);
            $('#emailFrom').html(`De la: <strong>${currentEmail.from_name}</strong> &lt;${currentEmail.from_email}&gt;`);
            $('#emailDate').text(new Date(currentEmail.received_at).toLocaleString('ro-RO'));
            $('#emailBody').html(currentEmail.is_html ? currentEmail.body : '<pre>' + currentEmail.body + '</pre>');
            
            // Navigation buttons
            if (currentEmail.prev_id) {
                $('#btnPrevEmail').attr('href', 'read_mail?id=' + currentEmail.prev_id).show();
            } else {
                $('#btnPrevEmail').hide();
            }
            
            if (currentEmail.next_id) {
                $('#btnNextEmail').attr('href', 'read_mail?id=' + currentEmail.next_id).show();
            } else {
                $('#btnNextEmail').hide();
            }
            
            // Attachments
            if (currentEmail.attachments && currentEmail.attachments.length) {
                $('#attachmentsSection').show();
                currentEmail.attachments.forEach(att => {
                    const icon = getFileIcon(att.mime_type);
                    const sizeMB = (att.file_size / 1024 / 1024).toFixed(2);
                    
                    $('#attachmentsList').append(`
                        <li>
                            <span class="mailbox-attachment-icon">${icon}</span>
                            <div class="mailbox-attachment-info">
                                <a href="#" class="mailbox-attachment-name">
                                    <i class="fas fa-paperclip"></i> ${att.original_name}
                                </a>
                                <span class="mailbox-attachment-size clearfix mt-1">
                                    <span>${sizeMB} MB</span>
                                    <a href="download.php?id=${att.id}" class="btn btn-default btn-sm float-right">
                                        <i class="fas fa-cloud-download-alt"></i>
                                    </a>
                                </span>
                            </div>
                        </li>
                    `);
                });
            }
            
            // Conversation thread
            if (currentEmail.thread && currentEmail.thread.length) {
                $('#threadCard').show();
                $('#threadCount').text(currentEmail.thread.length);
                
                currentEmail.thread.forEach(msg => {
                    $('#threadContent').append(`
                        <div class="card mb-2">
                            <div class="card-body p-2">
                                <strong>${msg.from_name}</strong> &lt;${msg.from_email}&gt;<br>
                                <small class="text-muted">${new Date(msg.received_at).toLocaleString('ro-RO')}</small>
                                <p class="mt-2 mb-0">${msg.preview}...</p>
                                <a href="read_mail?id=${msg.id}" class="btn btn-xs btn-link">Citește complet</a>
                            </div>
                        </div>
                    `);
                });
            }
            
            // Show AI card if account has AI enabled
            $('#aiReplyCard').show();
            
        } else {
            toastr.error(resp.error || 'Email negăsit!');
            setTimeout(() => window.location.href = 'mailbox', 2000);
        }
    }, 'json');
}

function getFileIcon(mimeType) {
    if (mimeType.includes('pdf')) return '<i class="far fa-file-pdf"></i>';
    if (mimeType.includes('word')) return '<i class="far fa-file-word"></i>';
    if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return '<i class="far fa-file-excel"></i>';
    if (mimeType.includes('image')) return '<i class="far fa-file-image"></i>';
    if (mimeType.includes('zip') || mimeType.includes('rar')) return '<i class="far fa-file-archive"></i>';
    return '<i class="far fa-file"></i>';
}

$('#btnReply, #btnReply2').click(function(e) {
    e.preventDefault();
    window.location.href = 'compose?reply_to=' + emailId;
});

$('#btnForward, #btnForward2').click(function(e) {
    e.preventDefault();
    window.location.href = 'compose?forward=' + emailId;
});

$('#btnDelete, #btnDelete2').click(function() {
$('#confirmDeleteModal').modal('show');
});
$('#confirmDeleteBtn').off('click').on('click', function () {
    // call your delete function here
    deleteEmail(emailId);
    $('#confirmDeleteModal').modal('hide');
});
function deleteEmail(id) {
    $.post('api.php?f=move_email&user_id=<?= $user_id ?>', { action: 'deleteEmail', id: id }, function (res) {
        if (res.success) {
            window.location.href = 'mailbox.php';
        }
    }, 'json');
}
$('#btnPrint').click(function() {
    window.print();
});

// AI Reply functionality
$('#btnAiReply').click(function() {
    $('#aiReplyCard').removeClass('collapsed-card').find('.card-body').show();
    $('html, body').animate({
        scrollTop: $('#aiReplyCard').offset().top - 100
    }, 500);
});

$('#btnGenerateReply, #btnRegenerateReply').click(function() {
    const btn = $(this);
    const originalHtml = btn.html();
    
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Se generează...');
    $('#aiReplyContent').hide();
    
    $.post('api.php?f=gemini_reply&user_id=<?= $user_id ?>', {
        email_id: emailId,
        prompt: $('#aiPrompt').val()
    }, function(resp) {
        btn.prop('disabled', false).html(originalHtml);
        
        if (resp.success) {
            $('#aiGeneratedText').html(resp.reply);
            $('#aiReplyContent').slideDown();
        } else {
            toastr.error(resp.error || 'Eroare la generarea răspunsului!');
        }
    }, 'json').fail(function() {
        btn.prop('disabled', false).html(originalHtml);
        toastr.error('Eroare de rețea!');
    });
});

$('#btnUseAiReply').click(function() {
    const aiText = $('#aiGeneratedText').text();
    window.location.href = 'compose?reply_to=' + emailId + '&ai_text=' + encodeURIComponent(aiText);
});

loadEmail();
</script>

<?php include_once("WEB-INF/footer.php"); ?>