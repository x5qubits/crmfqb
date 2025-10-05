<?php
$pageName = "Template-uri documente";
require_once __DIR__.'/config.php';
require_once __DIR__.'/db.php';
include_once("WEB-INF/menu.php"); 
$USER_ID = isset($user_id_js) ? (int)$user_id_js : (isset($_SESSION['id'])?(int)$_SESSION['id']:0);

?>

<link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
<link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">
<link rel="stylesheet" href="plugins/summernote/summernote-bs4.min.css">
<!-- Adaugă Toastr CSS -->
<link rel="stylesheet" href="plugins/toastr/toastr.min.css"> 


<style>
  .section-block .handle{cursor:grab}
  .section-block{border:1px solid var(--secondary); border-radius:.5rem}
  .section-block .card-header{padding:.5rem .75rem}
  .section-block .card-body{padding:.5rem .75rem}
</style>


    <div class="row">
      <!-- LEFT: list -->
      <div class="col-md-5">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Lista Template-uri</h3>
            <button type="button" class="btn btn-primary btn-sm" id="btnNewTpl">+ Adaugă Template</button>
          </div>
          <div class="card-body p-0">
            <table id="tplTable" class="table table-hover table-striped mb-0">
              <thead>
                <tr>
                  <th style="width:60px">ID</th>
                  <th>Titlu</th>
                  <th style="width:160px">Actualizat</th>
                  <th style="width:90px">Acțiuni</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
		
<!-- Card Variabile Template Contract -->
<div class="card shadow mb-4">
    <div class="card-header py-3" style="cursor: pointer;" data-toggle="collapse" data-target="#variablesTableCollapse" aria-expanded="false" aria-controls="variablesTableCollapse">
        <h6 class="m-0 font-weight-bold">
            <i class="fas fa-chevron-down mr-2" id="collapseIcon"></i>
            Variabile Disponibile pentru Template-uri Contract
        </h6>
    </div>
    <div id="variablesTableCollapse" class="collapse">
        <div class="card-body">
            <p class="text-muted mb-3">Click pe variabilă pentru a o copia în clipboard.</p>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th width="30%">Variabilă</th>
                            <th width="35%">Descriere</th>
                            <th width="35%">Categorie</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Informații Contract -->
                        <tr>
                            <td><code class="copy-variable" style="cursor: pointer;" data-variable="{{CONTRACT.NUMBER}}">{{CONTRACT.NUMBER}}</code></td>
                            <td>Numărul contractului</td>
                            <td><span class="badge badge-primary">Contract</span></td>
                        </tr>
                        <tr>
                            <td><code class="copy-variable" style="cursor: pointer;" data-variable="{{DOC.DATE}}">{{DOC.DATE}}</code></td>
                            <td>Data documentului (ziua curentă)</td>
                            <td><span class="badge badge-primary">Contract</span></td>
                        </tr>
                        <tr>
                            <td><code class="copy-variable" style="cursor: pointer;" data-variable="{{CONTRACT.DATE}}">{{CONTRACT.DATE}}</code></td>
                            <td>Data contractului</td>
                            <td><span class="badge badge-primary">Contract</span></td>
                        </tr>
                        <tr>
                            <td><code class="copy-variable" style="cursor: pointer;" data-variable="{{CONTRACT.TIME}}">{{CONTRACT.TIME}}</code></td>
                            <td>Durata contractului în luni</td>
                            <td><span class="badge badge-primary">Contract</span></td>
                        </tr>
                        <tr>
                            <td><code class="copy-variable" style="cursor: pointer;" data-variable="{{PROJECT.NAME}}">{{PROJECT.NAME}}</code></td>
                            <td>Numele proiectului / Clauze speciale</td>
                            <td><span class="badge badge-primary">Contract</span></td>
                        </tr>
                        <tr>
                            <td><code class="copy-variable" style="cursor: pointer;" data-variable="{{CONTRACT.TOTAL}}">{{CONTRACT.TOTAL}}</code></td>
                            <td>Valoarea totală (cu TVA) în Lei</td>
                            <td><span class="badge badge-primary">Contract</span></td>
                        </tr>
                        <tr>
                            <td><code class="copy-variable" style="cursor: pointer;" data-variable="{{CONTRACT.SUBTOTAL}}">{{CONTRACT.SUBTOTAL}}</code></td>
                            <td>Subtotal (fără TVA) în Lei</td>
                            <td><span class="badge badge-primary">Contract</span></td>
                        </tr>
                        <tr>
                            <td><code class="copy-variable" style="cursor: pointer;" data-variable="{{CONTRACT.DISCOUNT}}">{{CONTRACT.DISCOUNT}}</code></td>
                            <td>Valoarea reducerii în Lei</td>
                            <td><span class="badge badge-primary">Contract</span></td>
                        </tr>
                        
                        <!-- Date Prestator -->
                        <tr>
                            <td><code class="copy-variable" style="cursor: pointer;" data-variable="{{PRESTATOR.NAME}}">{{PRESTATOR.NAME}}</code></td>
                            <td>Denumirea companiei prestator</td>
                            <td><span class="badge badge-success">Prestator</span></td>
                        </tr>
                        <tr>
                            <td><code class="copy-variable" style="cursor: pointer;" data-variable="{{PRESTATOR.ADDRESS}}">{{PRESTATOR.ADDRESS}}</code></td>
                            <td>Adresa sediului social</td>
                            <td><span class="badge badge-success">Prestator</span></td>
                        </tr>
                        <tr>
                            <td><code class="copy-variable" style="cursor: pointer;" data-variable="{{PRESTATOR.REG}}">{{PRESTATOR.REG}}</code></td>
                            <td>Număr Registrul Comerțului</td>
                            <td><span class="badge badge-success">Prestator</span></td>
                        </tr>
                        <tr>
                            <td><code class="copy-variable" style="cursor: pointer;" data-variable="{{PRESTATOR.CUI}}">{{PRESTATOR.CUI}}</code></td>
                            <td>Cod Unic de Înregistrare (CUI)</td>
                            <td><span class="badge badge-success">Prestator</span></td>
                        </tr>
                        <tr>
                            <td><code class="copy-variable" style="cursor: pointer;" data-variable="{{PRESTATOR.IBAN}}">{{PRESTATOR.IBAN}}</code></td>
                            <td>IBAN cont bancar</td>
                            <td><span class="badge badge-success">Prestator</span></td>
                        </tr>
                        <tr>
                            <td><code class="copy-variable" style="cursor: pointer;" data-variable="{{PRESTATOR.BANK}}">{{PRESTATOR.BANK}}</code></td>
                            <td>Denumirea băncii</td>
                            <td><span class="badge badge-success">Prestator</span></td>
                        </tr>
                        <tr>
                            <td><code class="copy-variable" style="cursor: pointer;" data-variable="{{PRESTATOR.REP}}">{{PRESTATOR.REP}}</code></td>
                            <td>Nume reprezentant legal</td>
                            <td><span class="badge badge-success">Prestator</span></td>
                        </tr>
                        
                        <!-- Date Beneficiar -->
                        <tr>
                            <td><code class="copy-variable" style="cursor: pointer;" data-variable="{{BENEFICIAR.NAME}}">{{BENEFICIAR.NAME}}</code></td>
                            <td>Denumirea/Numele clientului</td>
                            <td><span class="badge badge-info">Beneficiar</span></td>
                        </tr>
                        <tr>
                            <td><code class="copy-variable" style="cursor: pointer;" data-variable="{{BENEFICIAR.ADDRESS}}">{{BENEFICIAR.ADDRESS}}</code></td>
                            <td>Adresa clientului</td>
                            <td><span class="badge badge-info">Beneficiar</span></td>
                        </tr>
                        <tr>
                            <td><code class="copy-variable" style="cursor: pointer;" data-variable="{{BENEFICIAR.REG}}">{{BENEFICIAR.REG}}</code></td>
                            <td>Număr Registrul Comerțului</td>
                            <td><span class="badge badge-info">Beneficiar</span></td>
                        </tr>
                        <tr>
                            <td><code class="copy-variable" style="cursor: pointer;" data-variable="{{BENEFICIAR.CUI}}">{{BENEFICIAR.CUI}}</code></td>
                            <td>Cod Unic de Înregistrare (CUI)</td>
                            <td><span class="badge badge-info">Beneficiar</span></td>
                        </tr>
                        <tr>
                            <td><code class="copy-variable" style="cursor: pointer;" data-variable="{{BENEFICIAR.REP}}">{{BENEFICIAR.REP}}</code></td>
                            <td>Nume reprezentant legal</td>
                            <td><span class="badge badge-info">Beneficiar</span></td>
                        </tr>
                        
                        <!-- Obiect Contract -->
                        <tr>
                            <td><code class="copy-variable" style="cursor: pointer;" data-variable="{{OBJECT.LIST}}">{{OBJECT.LIST}}</code></td>
                            <td>Lista completă cu servicii/produse (format HTML)</td>
                            <td><span class="badge badge-warning">Obiect</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="alert alert-info mt-3" role="alert">
                <i class="fas fa-info-circle"></i> <strong>Notă:</strong> Click pe variabilă pentru a o copia automat. Inserați variabilele copiate în documentul Word/HTML al template-ului.
            </div>
        </div>
    </div>
</div>

<!-- Toast pentru confirmare copiere -->
<div class="position-fixed bottom-0 right-0 p-3" style="z-index: 5; right: 0; bottom: 0;">
    <div id="copyToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true" data-delay="2000">
        <div class="toast-header bg-success text-white">
            <i class="fas fa-check-circle mr-2"></i>
            <strong class="mr-auto">Copiat!</strong>
            <button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="toast-body">
            Variabila <strong id="copiedVariable"></strong> a fost copiată în clipboard.
        </div>
    </div>
</div>



<style>
.copy-variable:hover {
    background-color: #e3f2fd;
    padding: 2px 5px;
    border-radius: 3px;
    transition: all 0.2s;
}

#collapseIcon {
    transition: transform 0.3s ease;
}
</style>		
		
		
      </div>

      <!-- RIGHT: editor -->
      <div class="col-md-7">
        <div class="card">
          <div class="card-header"><h3 class="card-title mb-0">Editor Template</h3></div>
          <div class="card-body">
            <form id="tplForm" autocomplete="off">
              <input type="hidden" id="tpl_id" name="id">
              <div class="form-group">
                <label for="tpl_title">Titlu</label>
                <input type="text" class="form-control" id="tpl_title" name="title" required>
              </div>

              <div class="d-flex align-items-center mb-2">
                <h6 class="mb-0 mr-2">Secțiuni</h6>
                <button type="button" class="btn btn-sm btn-outline-info" id="btnAddSection">+ Secțiune</button>
              </div>

              <div id="sectionsWrap"></div>

              <div class="mt-3">
                <button type="submit" class="btn btn-success">Salvează</button>
                <button type="button" class="btn btn-danger" id="btnDeleteTpl">Șterge</button>
                <button type="button" class="btn btn-secondary" id="btnResetTpl">Nou</button>
              </div>
            </form>
          </div>
        </div>
      </div>

    </div>



<!-- Delete confirm -->
<div class="modal fade" id="confirmTplDelete" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-2"><h6 class="modal-title">Ștergere template</h6>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
      <div class="modal-body">Ștergi acest template?</div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Nu</button>
        <button type="button" class="btn btn-danger btn-sm" id="confirmTplDeleteBtn">Da</button>
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
<!-- Adaugă Toastr JS -->
<script src="plugins/toastr/toastr.min.js"></script> 

<script src="plugins/summernote/summernote-bs4.min.js"></script>
<script src="plugins/Sortable/Sortable.min.js"></script>
<script>
(function(){
  const USER_ID = <?= json_encode($USER_ID) ?>;
  const API = 'api.php?user_id='+USER_ID;
  let table, deleteId=null;

  function sectionHtml(id, title='', body=''){
    return `
      <div class="section-block card mb-2" data-id="${id}">
        <div class="card-header d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center" style="gap:.5rem">
            <span class="handle"><i class="fas fa-grip-vertical"></i></span>
            <input type="text" class="form-control form-control-sm sec-title" placeholder="Titlu secțiune" value="${title}">
          </div>
          <button type="button" class="btn btn-xs btn-outline-danger rm-sec">Șterge</button>
        </div>
        <div class="card-body">
          <textarea class="summernote sec-body">${body}</textarea>
        </div>
      </div>`;
  }

  function initSummernote($ctx){
    ($ctx||$(document)).find('.summernote').summernote({
      height:140,
      toolbar: [
        ['style', ['bold','italic','underline','clear']],
        ['para', ['ul','ol','paragraph']],
        ['insert',['link']],
        ['view', ['codeview']]
      ]
    });
  }

  function gatherSections(){
    const arr=[];
    $('#sectionsWrap .section-block').each(function(){
      arr.push({
        title: $(this).find('.sec-title').val(),
        body:  $(this).find('.sec-body').val()
      });
    });
    return arr;
  }

  function loadTable(){
    if(table){ table.ajax.reload(); return; }
    table = $('#tplTable').DataTable({
      ajax:{ url: API+'&f=list_contract_templates', dataSrc:'data' },
      paging:false, searching:false, info:false, autoWidth:false, order:[[2,'desc']],
      columns:[
        {data:'id'},
        {data:'title'},
        {data:'updated_at'},
		{data:null, orderable:false, render:r=>`
		  <button class="btn btn-xs btn-primary edit" data-id="${r.id}"><i class="fas fa-edit"></i></button>
		  <button class="btn btn-xs btn-info duplicate" data-id="${r.id}" title="Duplică"><i class="fas fa-copy"></i></button>
		  <button class="btn btn-xs btn-danger del" data-id="${r.id}"><i class="fas fa-trash"></i></button>`
		}
	  ]
    });
  }
$(document).on('click','.duplicate', function(){
  const id = $(this).data('id');
  $.getJSON(API+'&f=get_contract_template&id='+id, function(r){
    if(!r || !r.success || !r.data){ toastr.error('Nu s-a găsit template-ul'); return; }
    resetForm();
    $('#tpl_title').val((r.data.title || '') + ' (Copie)');
    let secs = [];
    try { secs = JSON.parse(r.data.data || '[]'); } catch(e){}
    if(!Array.isArray(secs)) secs=[];
    secs.forEach(s=>{
      const html = sectionHtml('s'+Math.random().toString(36).slice(2), s.title||'', s.body||'');
      $('#sectionsWrap').append(html);
      initSummernote($('#sectionsWrap').children().last());
    });
    if(secs.length===0){ $('#btnAddSection').click(); }
    toastr.info('Template duplicat - editează și salvează');
  });
});
  function resetForm(){
    $('#tpl_id').val('');
    $('#tpl_title').val('');
    $('#sectionsWrap').empty();
  }

  // init
  loadTable();

  // sortable
  new Sortable(document.getElementById('sectionsWrap'), { handle:'.handle', animation:150 });

  // add section
  $('#btnAddSection').on('click', function(){
    const id = 's'+Math.random().toString(36).slice(2);
    $('#sectionsWrap').append(sectionHtml(id,'',''));
    initSummernote($('#sectionsWrap').children().last());
  });

  // remove section
  $(document).on('click','.rm-sec', function(){ $(this).closest('.section-block').remove(); });

  // new
  $('#btnNewTpl, #btnResetTpl').on('click', resetForm);

  // edit
  $(document).on('click','.edit', function(){
    const id = $(this).data('id');
    $.getJSON(API+'&f=get_contract_template&id='+id, function(r){
      if(!r || !r.success || !r.data){ toastr.error('Nu s-a găsit template-ul'); return; }
      resetForm();
      $('#tpl_id').val(r.data.id);
      $('#tpl_title').val(r.data.title || '');
      let secs = [];
      try { secs = JSON.parse(r.data.data || '[]'); } catch(e){}
      if(!Array.isArray(secs)) secs=[];
      secs.forEach(s=>{
        const html = sectionHtml('s'+Math.random().toString(36).slice(2), s.title||'', s.body||'');
        $('#sectionsWrap').append(html);
        initSummernote($('#sectionsWrap').children().last());
      });
      if(secs.length===0){ $('#btnAddSection').click(); }
    });
  });

  // open delete modal
  $(document).on('click','.del', function(){
    deleteId = $(this).data('id')||null;
    $('#confirmTplDelete').appendTo('body').modal('show');
  });
  // confirm delete
  $('#confirmTplDeleteBtn').on('click', function(){
    if(!deleteId){ $('#confirmTplDelete').modal('hide'); return; }
    $.post(API+'&f=delete_contract_template', { id: deleteId }, function(r){
      $('#confirmTplDelete').modal('hide');
      if(r && r.success){
        toastr.success('Șters');
        if($('#tpl_id').val()==String(deleteId)) resetForm();
        table.ajax.reload(null,false);
      } else {
        toastr.error((r&&r.error)||'Eroare la ștergere');
      }
      deleteId=null;
    }, 'json');
  });

  // save
  $('#tplForm').on('submit', function(e){
    e.preventDefault();
    const payload = {
      id: $('#tpl_id').val(),
      title: $('#tpl_title').val(),
      data: JSON.stringify(gatherSections())
    };
    if(!payload.title){ toastr.error('Titlul este obligatoriu'); return; }
    $.post(API+'&f=save_contract_template', payload, function(r){
      if(r && r.success){
        toastr.success('Salvat');
        table.ajax.reload(null,false);
        if(!payload.id){ // first save → fetch last row id
          setTimeout(()=>$('.edit').first().click(),200);
        }
      } else {
        toastr.error((r&&r.error)||'Eroare la salvare');
      }
    }, 'json');
  });

  // ensure at least one section to start
  if($('#sectionsWrap').children().length===0) $('#btnAddSection').click();
})();
</script>
<script>
$(document).ready(function() {
    // Funcție pentru copierea în clipboard
    $('.copy-variable').on('click', function() {
        var variableToCopy = $(this).data('variable');
        
        // Creează un element temporar pentru copiere
        var tempInput = document.createElement('input');
        tempInput.value = variableToCopy;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        
        // Afișează toast-ul de confirmare
        $('#copiedVariable').text(variableToCopy);
        $('#copyToast').toast('show');
        
        // Efect vizual pe element
        $(this).parent().addClass('table-success');
        setTimeout(() => {
            $(this).parent().removeClass('table-success');
        }, 500);
    });
    
    // Hover effect
    $('.copy-variable').hover(
        function() {
            $(this).addClass('text-primary font-weight-bold');
        },
        function() {
            $(this).removeClass('text-primary font-weight-bold');
        }
    );
    
    // Schimbă iconița când se deschide/închide
    $('#variablesTableCollapse').on('show.bs.collapse', function () {
        $('#collapseIcon').removeClass('fa-chevron-down').addClass('fa-chevron-up');
    });
    
    $('#variablesTableCollapse').on('hide.bs.collapse', function () {
        $('#collapseIcon').removeClass('fa-chevron-up').addClass('fa-chevron-down');
    });
});
</script>
<?php include_once("WEB-INF/footer.php"); ?>