<?php
include_once("config.php");
$pageName = "Scrie Email";
$pageId = 3;
include_once("WEB-INF/menu.php"); 

$reply_to_id = isset($_GET['reply_to']) ? (int)$_GET['reply_to'] : 0;
?>

<link rel="stylesheet" href="plugins/summernote/summernote-bs4.min.css">
<link rel="stylesheet" href="plugins/toastr/toastr.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3">
                <a href="mailbox" class="btn btn-primary btn-block mb-3">
                    <i class="fas fa-arrow-left"></i> Înapoi la Inbox
                </a>
            </div>

            <div class="col-md-9">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Compune Email Nou</h3>
                    </div>

                    <form id="composeForm" enctype="multipart/form-data">
                        <input type="hidden" id="reply_to_id" value="<?= $reply_to_id ?>">
                        <div class="card-body">
                            <div class="form-group">
                                <label>Din contul:</label>
                                <select class="form-control" id="account_id" name="account_id" required>
                                    <option value="">Selectează cont...</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <input class="form-control" placeholder="Către:" name="to" id="to" required>
                            </div>

                            <div class="form-group">
                                <input class="form-control" placeholder="CC:" name="cc" id="cc">
                            </div>

                            <div class="form-group">
                                <input class="form-control" placeholder="BCC:" name="bcc" id="bcc">
                            </div>

                            <div class="form-group">
                                <input class="form-control" placeholder="Subiect:" name="subject" id="subject" required>
                            </div>

                            <div class="form-group">
                                <textarea id="compose-textarea" name="body" style="height: 300px"></textarea>
                            </div>

                            <div class="form-group">
                                <div class="btn btn-default btn-file">
                                    <i class="fas fa-paperclip"></i> Atașamente
                                    <input type="file" name="attachments[]" multiple id="attachments">
                                </div>
                                <p class="help-block">Max. 25MB per fișier</p>
                                <div id="attachmentsList"></div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <div class="float-right">
                                <button type="button" class="btn btn-info" id="btnAiAssist">
                                    <i class="fas fa-robot"></i> Asistent AI
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="far fa-envelope"></i> Trimite
                                </button>
                            </div>
                            <button type="button" class="btn btn-default" id="btnDiscard">
                                <i class="fas fa-times"></i> Renunță
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- AI Assistant Modal -->
<div class="modal fade" id="aiModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title">
                    <i class="fas fa-robot"></i> Asistent AI (Gemini)
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> AI-ul va analiza conversația anterioară și va genera un răspuns contextual.
                </div>

                <div class="form-group">
                    <label>Tip Email</label>
                    <select class="form-control" id="aiEmailType">
                        <option value="business" selected>Email de Business (Formal)</option>
                        <option value="proposal">Propunere Comercială</option>
                        <option value="followup">Follow-up</option>
                        <option value="thanks">Mulțumire</option>
                        <option value="info">Email Informativ</option>
                        <option value="custom">Personalizat</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Instrucțiuni Speciale (opțional)</label>
                    <textarea class="form-control" id="aiInstructions" rows="3" placeholder="Ex: Menționează termenul de livrare de 30 zile, include prețul de 5000 EUR..."></textarea>
                </div>

                <div class="form-group">
                    <label>Ton</label>
                    <select class="form-control" id="aiTone">
                        <option value="professional" selected>Profesional</option>
                        <option value="friendly">Prietenos</option>
                        <option value="formal">Formal</option>
                    </select>
                </div>

                <div id="aiResult" style="display:none;">
                    <hr>
                    <h6>Conținut Generat:</h6>
                    <div id="aiGeneratedContent" class="border p-3 bg-light" style="max-height: 300px; overflow-y: auto;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Închide</button>
                <button type="button" class="btn btn-info" id="btnGenerateAi">
                    <i class="fas fa-magic"></i> Generează
                </button>
                <button type="button" class="btn btn-success" id="btnUseAiContent" style="display:none;">
                    <i class="fas fa-check"></i> Folosește
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Discard Modal -->
<div class="modal fade" id="discardModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Confirmare</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                Sigur doriți să renunțați? Toate modificările vor fi pierdute.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Nu</button>
                <button type="button" class="btn btn-warning" id="confirmDiscard">Da</button>
            </div>
        </div>
    </div>
</div>

<!-- Send Modal -->
<div class="modal fade" id="sendModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">Confirmare</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                Trimiteți emailul?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Nu</button>
                <button type="button" class="btn btn-primary" id="confirmSend">
                    <i class="far fa-envelope"></i> Trimite
                </button>
            </div>
        </div>
    </div>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/summernote/summernote-bs4.min.js"></script>
<script src="plugins/toastr/toastr.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>

<script>
let generatedAiContent = '';
let currentSignature = '';
let allAccounts = [];

$(function() {
    $('#compose-textarea').summernote({
        height: 300,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'clear']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link']],
            ['view', ['fullscreen', 'codeview']]
        ]
    });
});

// Load accounts and select first one
$.post('api.php?f=get_email_accounts&user_id=<?= $user_id ?>', {}, function(resp) {
    if (resp.success && resp.data) {
        allAccounts = resp.data;
        resp.data.forEach((acc, index) => {
            $('#account_id').append(`<option value="${acc.id}">${acc.from_name} &lt;${acc.from_email}&gt;</option>`);
        });
        
        // Select first account by default
        if (resp.data.length > 0) {
            $('#account_id').val(resp.data[0].id).trigger('change');
        }
    }
}, 'json');

// When account changes, update signature
$('#account_id').on('change', function() {
    const accountId = $(this).val();
    const account = allAccounts.find(a => a.id == accountId);
    
    if (account && account.signature) {
        currentSignature = '<br><br>---<br>' + account.signature;
        appendSignature();
    } else {
        currentSignature = '';
    }
});

function appendSignature() {
    const currentContent = $('#compose-textarea').summernote('code');
    // Remove old signature if exists
    const withoutSig = currentContent.replace(/<br><br>---<br>[\s\S]*$/, '');
    $('#compose-textarea').summernote('code', withoutSig + currentSignature);
}

$('#attachments').on('change', function() {
    const files = this.files;
    $('#attachmentsList').empty();
    for (let i = 0; i < files.length; i++) {
        const size = (files[i].size / 1024 / 1024).toFixed(2);
        $('#attachmentsList').append(`<div class="mt-1"><i class="fas fa-paperclip"></i> ${files[i].name} (${size} MB)</div>`);
    }
});

// AI Assistant - with conversation context
$('#btnAiAssist').click(function() {
    $('#aiModal').modal('show');
});

$('#btnGenerateAi').click(function() {
    const emailType = $('#aiEmailType').val();
    const instructions = $('#aiInstructions').val().trim();
    const tone = $('#aiTone').val();
    const to = $('#to').val();
    const subject = $('#subject').val();
    const replyToId = $('#reply_to_id').val();
    
    const btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Se generează...');
    
    $.post('api.php?f=generate_email_with_context&user_id=<?= $user_id ?>', {
        email_type: emailType,
        instructions: instructions,
        tone: tone,
        to: to,
        subject: subject,
        reply_to_id: replyToId
    }, function(resp) {
        btn.prop('disabled', false).html('<i class="fas fa-magic"></i> Generează');
        
        if (resp.success) {
            generatedAiContent = resp.content;
            $('#aiGeneratedContent').html(resp.content);
            $('#aiResult').slideDown();
            $('#btnUseAiContent').show();
        } else {
            toastr.error(resp.error || 'Eroare la generare!');
        }
    }, 'json').fail(function() {
        btn.prop('disabled', false).html('<i class="fas fa-magic"></i> Generează');
        toastr.error('Eroare de rețea!');
    });
});

$('#btnUseAiContent').click(function() {
    // Replace content but keep signature
    const contentWithoutSig = generatedAiContent;
    $('#compose-textarea').summernote('code', contentWithoutSig + currentSignature);
    $('#aiModal').modal('hide');
    toastr.success('Conținut adăugat!');
});

$('#composeForm').submit(function(e) {
    e.preventDefault();
    $('#sendModal').modal('show');
});

$('#confirmSend').click(function() {
    $('#sendModal').modal('hide');
    const formData = new FormData($('#composeForm')[0]);
    
    $.ajax({
        url: 'api.php?f=send_email&user_id=<?= $user_id ?>',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(resp) {
            if (resp.success) {
                toastr.success(resp.message);
                setTimeout(() => window.location.href = 'mailbox', 1500);
            } else {
                toastr.error(resp.error);
            }
        }
    });
});

$('#btnDiscard').click(function() {
    $('#discardModal').modal('show');
});

$('#confirmDiscard').click(function() {
    window.location.href = 'mailbox';
});

// Pre-fill if reply
const replyTo = <?= $reply_to_id ?>;
if (replyTo > 0) {
    $.post('api.php?f=get_email&user_id=<?= $user_id ?>', {id: replyTo}, function(resp) {
        if (resp.success) {
            const email = resp.data;
            $('#to').val(email.from_email);
            $('#subject').val('Re: ' + email.subject.replace(/^Re:\s*/i, ''));
            
            const quoted = `
                <br><br>
                <div style="border-left: 2px solid #ccc; padding-left: 10px; margin-top: 10px;">
                    <p><strong>De la:</strong> ${email.from_name} &lt;${email.from_email}&gt;<br>
                    <strong>Data:</strong> ${email.received_at}<br>
                    <strong>Subiect:</strong> ${email.subject}</p>
                    ${email.body}
                </div>
            `;
            
            // Wait for signature to load, then add content
            setTimeout(() => {
                const sig = currentSignature;
                $('#compose-textarea').summernote('code', quoted + sig);
            }, 500);
        }
    }, 'json');
}
</script>

<?php include_once("WEB-INF/footer.php"); ?>