<?php
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
        <h3 class="card-title mb-0">Lista Template-uri Ofertă</h3>
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
  </div>

  <!-- RIGHT: editor -->
  <div class="col-md-7">
    <div class="card">
      <div class="card-header"><h3 class="card-title mb-0">Editor Template Ofertă</h3></div>
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
        body:  $(this).find('.sec-body').summernote('code')
      });
    });
    return arr;
  }

  function loadTable(){
    if(table){ table.ajax.reload(); return; }
    table = $('#tplTable').DataTable({
      ajax:{ url: API+'&f=list_offer_templates', dataSrc:'data' },
      paging:false, searching:false, info:false, autoWidth:false, order:[[2,'desc']],
      columns:[
        {data:'id'},
        {data:'title'},
        {data:'updated_at'},
        {data:null, orderable:false, render:r=>`<button class="btn btn-xs btn-primary edit" data-id="${r.id}"><i class="fas fa-edit"></i></button>
                                                <button class="btn btn-xs btn-danger del" data-id="${r.id}"><i class="fas fa-trash"></i></button>`}
      ]
    });
  }

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
    $.getJSON(API+'&f=get_offer_template&id='+id, function(r){
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
    $.post(API+'&f=delete_offer_template', { id: deleteId }, function(r){
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
    $.post(API+'&f=save_offer_template', payload, function(r){
      if(r && r.success){
        toastr.success('Salvat');
        table.ajax.reload(null,false);
        if(!payload.id){
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

<?php include_once("WEB-INF/footer.php"); ?>