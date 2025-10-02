<?php include_once("WEB-INF/header.php"); ?>
<link rel="stylesheet" href="plugins/toastr/toastr.min.css">
<link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">

<style>
  .section-row .handle { cursor:grab; }
  .autocomplete-suggestions{position:absolute;background:#fff;border:1px solid #ddd;z-index:2000;width:100%;max-height:200px;overflow:auto}
  .autocomplete-suggestion{padding:6px 8px;border-bottom:1px solid #f1f1f1;cursor:pointer}
  .autocomplete-suggestion:hover{background:#f8f9fa}
</style>

<section class="content">
  <div class="container-fluid">

    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0">Constructor Ofertă / Contract</h3>
        <div class="d-flex align-items-center">
          <input id="builderSearchCompany" class="form-control form-control-sm mr-2" placeholder="Caută companie">
          <div id="builderCompanySug" class="autocomplete-suggestions" style="display:none;"></div>
          <input id="builderCompanyCUI" type="number" class="form-control form-control-sm mr-2" placeholder="CUI">
          <button id="builderLoadOffers" class="btn btn-sm btn-outline-secondary">Reîncarcă Oferte</button>
        </div>
      </div>

      <div class="card-body">
        <ul class="nav nav-tabs" id="builderTabs" role="tablist">
          <li class="nav-item">
            <a class="nav-link active" id="offer-tab" data-toggle="tab" href="#offerPane" role="tab">Ofertă</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" id="contract-tab" data-toggle="tab" href="#contractPane" role="tab">Contract</a>
          </li>
        </ul>

        <div class="tab-content pt-3">
          <!-- OFFER -->
          <div class="tab-pane fade show active" id="offerPane" role="tabpanel">
            <form id="offerForm">
              <input type="hidden" name="action" value="add">
              <input type="hidden" name="company_cui" id="offer_company_cui">
              <div class="form-row">
                <div class="form-group col-md-3">
                  <label>Număr ofertă *</label>
                  <input type="text" class="form-control" name="offer_number" id="offer_number" required>
                </div>
                <div class="form-group col-md-3">
                  <label>Data *</label>
                  <input type="date" class="form-control" name="offer_date" id="offer_date" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group col-md-3">
                  <label>Valoare totală</label>
                  <input type="number" step="0.01" class="form-control" name="total_value" id="offer_total_value" value="0.00">
                </div>
                <div class="form-group col-md-3 d-flex align-items-end">
                  <button id="btnAddOfferItem" type="button" class="btn btn-outline-primary btn-block">+ Linie produs</button>
                </div>
              </div>

              <div class="table-responsive">
                <table class="table table-sm" id="offerItemsTable">
                  <thead>
                    <tr><th>Descriere</th><th style="width:120px">Cant.</th><th style="width:160px">Preț unitar</th><th style="width:160px" class="text-right">Subtotal</th><th style="width:50px"></th></tr>
                  </thead>
                  <tbody id="offerItemsTableBody"></tbody>
                </table>
              </div>

              <div class="form-group">
                <label>Detalii suplimentare</label>
                <textarea class="form-control" name="details" id="offer_details" rows="3" placeholder="Note interne sau text suplimentar"></textarea>
              </div>

              <div class="text-right">
                <button type="submit" class="btn btn-primary">Salvează Ofertă</button>
              </div>
            </form>
          </div>

          <!-- CONTRACT -->
          <div class="tab-pane fade" id="contractPane" role="tabpanel">
            <form id="contractForm">
              <input type="hidden" name="action" value="add">
              <input type="hidden" name="company_cui" id="contract_company_cui">

              <div class="form-row">
                <div class="form-group col-md-3">
                  <label>Număr Contract *</label>
                  <input type="text" class="form-control" name="contract_number" id="contract_number" required>
                </div>
                <div class="form-group col-md-3">
                  <label>Data *</label>
                  <input type="date" class="form-control" name="contract_date" id="contract_date" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group col-md-3">
                  <label>Durata (zile) *</label>
                  <input type="number" class="form-control" min="1" name="duration_days" id="contract_duration_days" value="30" required>
                </div>
                <div class="form-group col-md-3">
                  <label>Asociază cu ofertă</label>
                  <select class="form-control" id="contract_offer_id" name="offer_id">
                    <option value="0">— Fără —</option>
                  </select>
                </div>
              </div>

              <!-- Dynamic sections -->
              <div class="d-flex align-items-center mb-2">
                <h6 class="mb-0 mr-2">Secțiuni Contract</h6>
                <button type="button" id="btnAddSection" class="btn btn-sm btn-outline-primary">+ Secțiune</button>
              </div>
              <div id="sectionsWrap"></div>

              <div class="form-row pt-2">
                <div class="form-group col-md-6">
                  <label>Valoare totală</label>
                  <input type="number" step="0.01" class="form-control" name="total_value" id="contract_total_value" value="0.00">
                </div>
                <div class="form-group col-md-6">
                  <label>Clauze speciale</label>
                  <textarea class="form-control" name="special_clauses" id="contract_special_clauses" rows="3"></textarea>
                </div>
              </div>

              <div class="text-right">
                <button type="submit" class="btn btn-primary">Salvează Contract</button>
              </div>
            </form>
          </div>

        </div>
      </div>
    </div>

  </div>
</section>

<!-- Print choice modal -->
<div class="modal fade" id="printChoiceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-2"><h6 class="modal-title">Tipărire?</h6>
        <button type="button" class="close" data-dismiss="modal"><span>×</span></button></div>
      <div class="modal-body">
        <p id="printChoiceText" class="mb-2">Tipărești acum?</p>
        <div class="d-flex justify-content-center gap-2">
          <button type="button" class="btn btn-primary btn-sm" id="printOfferBtn">Ofertă</button>
          <button type="button" class="btn btn-secondary btn-sm" id="printContractBtn">Contract</button>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once("WEB-INF/footer.php"); ?>
<script src="plugins/toastr/toastr.min.js"></script>

<script>
/* ===== Helpers ===== */
const API = 'api.php?user_id=<?= $user_id_js ?>';
function toNum(v){ if(v==null)return 0; if(typeof v==='number'&&isFinite(v))return v; v=String(v).replace(',', '.').replace(/[^\d.-]/g,''); const n=parseFloat(v); return isNaN(n)?0:n; }
function uid(){ return 'x'+Math.random().toString(36).slice(2); }
function sectionTpl(id, title='', text=''){
  return `<div class="card section-row mb-2" data-id="${id}">
    <div class="card-header py-2 d-flex align-items-center justify-content-between">
      <div><span class="handle mr-2"><i class="fas fa-grip-vertical"></i></span>
      <input class="form-control form-control-sm d-inline-block" style="width:320px" placeholder="Titlu secțiune" value="${title}"></div>
      <button type="button" class="btn btn-xs btn-outline-danger rm-section">Șterge</button>
    </div>
    <div class="card-body p-2">
      <textarea class="form-control" rows="4" placeholder="Conținut secțiune">${text}</textarea>
    </div>
  </div>`;
}

/* ===== Company selector ===== */
(function companyAutocomplete(){
  const $i = $('#builderSearchCompany');
  const $s = $('#builderCompanySug');
  let t=null;
  $i.on('input', function(){
    clearTimeout(t);
    const q=this.value.trim(); if(q.length<2){ $s.hide().empty(); return; }
    t=setTimeout(()=>$.ajax({
      url: API+'&f=get_company', type:'POST', dataType:'json', data:{ search:q }
    }).done(r=>{
      $s.empty();
      const rows = r.success && r.data ? r.data : [];
      rows.slice(0,10).forEach(c=>{
        $('<div class="autocomplete-suggestion"></div>')
          .text(`${c.Name} · CUI ${c.CUI}`)
          .data('c', c).appendTo($s);
      });
      if (rows.length) $s.show(); else $s.hide();
    }), 250);
  });
  $(document).on('mousedown','.autocomplete-suggestion',function(e){ e.preventDefault(); });
  $(document).on('click','.autocomplete-suggestion', function(){
    const c=$(this).data('c')||{};
    $('#builderSearchCompany').val(c.Name||'');
    $('#builderCompanyCUI').val(c.CUI||'');
    $('#offer_company_cui').val(c.CUI||'');
    $('#contract_company_cui').val(c.CUI||'');
    $s.hide().empty();
    loadOfferDropdown();
  });
  $(document).on('click', e=>{ if(!$(e.target).closest('#builderSearchCompany,#builderCompanySug').length) $s.hide(); });
})();

/* ===== Offer items ===== */
let offerItemIndex=0;
function addOfferItemRow(item={}){
  const idx=offerItemIndex++, qty=toNum(item.quantity||1), up=toNum(item.unit_price||0), sub=qty*up;
  const id = uid();
  const row = `<tr class="offer-item-row" data-index="${idx}">
    <td style="position:relative;">
      <textarea class="form-control form-control-sm item-description" rows="1" required>${item.description||''}</textarea>
      <div class="autocomplete-suggestions" id="sug-${id}" style="display:none;"></div>
    </td>
    <td><input type="number" class="form-control form-control-sm item-quantity" value="${qty}" min="1" required></td>
    <td><input type="number" step="0.01" class="form-control form-control-sm item-unit-price" value="${up.toFixed(2)}" min="0" required></td>
    <td class="item-subtotal text-right">${sub.toFixed(2)}</td>
    <td><button type="button" class="btn btn-xs btn-danger rm-offer-item"><i class="fas fa-trash"></i></button></td>
  </tr>`;
  $('#offerItemsTableBody').append(row);
  calcOfferTotal();
}
function calcOfferTotal(){
  let total=0;
  $('.offer-item-row').each(function(){
    const r=$(this), q=toNum(r.find('.item-quantity').val()), up=toNum(r.find('.item-unit-price').val()); const s=q*up;
    r.find('.item-subtotal').text(s.toFixed(2)); total+=s;
  });
  $('#offer_total_value').val(total.toFixed(2));
}
$(document).on('click','#btnAddOfferItem', ()=>addOfferItemRow());
$(document).on('click','.rm-offer-item', function(){ $(this).closest('tr').remove(); if($('.offer-item-row').length===0) addOfferItemRow(); calcOfferTotal(); });
$(document).on('input','.item-quantity,.item-unit-price', calcOfferTotal);

/* item-description suggestions from history */
let autoT=null;
$(document).on('input','.item-description', function(){
  const $t=$(this), term=$t.val().trim(), $row=$t.closest('tr');
  let $box=$row.find('.autocomplete-suggestions'); if(!$box.length) return;
  clearTimeout(autoT);
  if(term.length<2){ $box.hide().empty(); return; }
  autoT=setTimeout(()=>$.ajax({
    url: API+'&f=get_item_suggestions', type:'POST', dataType:'json', data:{ q:term }
  }).done(r=>{
    const arr=(r.success && r.data)?r.data:[];
    $box.empty();
    if(!arr.length){ $box.hide(); return; }
    arr.slice(0,10).forEach(it=>{
      $('<div class="autocomplete-suggestion"></div>')
        .text(`${it.description} · ${toNum(it.unit_price).toFixed(2)} RON`)
        .data('p', {description:it.description, unit_price:toNum(it.unit_price)})
        .appendTo($box);
    });
    $box.show();
  }),250);
});
$(document).on('mousedown','.autocomplete-suggestion', e=>e.preventDefault());
$(document).on('click','.autocomplete-suggestion', function(){
  const p=$(this).data('p')||{}; const $r=$(this).closest('td').closest('tr');
  $r.find('.item-description').val(p.description||'');
  if(p.unit_price!=null) $r.find('.item-unit-price').val(toNum(p.unit_price).toFixed(2));
  $(this).parent().hide().empty(); calcOfferTotal();
});
$(document).on('click', e=>{ if(!$(e.target).closest('.item-description,.autocomplete-suggestions').length) $('.autocomplete-suggestions').hide(); });

/* ===== Offer save ===== */
$('#offerForm').on('submit', function(e){
  e.preventDefault();
  const cui = $('#offer_company_cui').val() || $('#builderCompanyCUI').val();
  if(!cui){ toastr.error('Selectați compania.'); return; }
  const items=[]; $('.offer-item-row').each(function(){
    const r=$(this); const d=r.find('.item-description').val().trim(); const q=toNum(r.find('.item-quantity').val()); const up=toNum(r.find('.item-unit-price').val());
    if(d) items.push({description:d, quantity:q, unit_price:up});
  });
  const payload = {
    action:'add',
    company_cui:cui,
    offer_number: $('#offer_number').val(),
    offer_date:   $('#offer_date').val(),
    details:      $('#offer_details').val(),
    total_value:  $('#offer_total_value').val(),
    items_json:   JSON.stringify(items)
  };
  $.ajax({ url: API+'&f=save_offer2', type:'POST', dataType:'json', data:payload })
    .done(r=>{
      if(r.success){
        toastr.success(r.message||'Ofertă salvată.');
        loadOfferDropdown();
        showPrintChoice('offer', r.id||null);
      } else toastr.error(r.error||'Eroare la salvare ofertă.');
    }).fail(()=>toastr.error('Eroare rețea.'));
});

/* ===== Contract sections ===== */
$('#btnAddSection').on('click', function(){
  $('#sectionsWrap').append(sectionTpl(uid(), '', ''));
});
$(document).on('click','.rm-section', function(){ $(this).closest('.section-row').remove(); });

/* populate offer dropdown for selected company */
function loadOfferDropdown(){
  const cui = $('#builderCompanyCUI').val();
  if(!cui){ $('#contract_offer_id').html('<option value="0">— Fără —</option>'); return; }
  $.ajax({ url: API+'&f=get_offers', type:'POST', dataType:'json', data:{ company_cui:cui } })
    .done(r=>{
      const arr = r.success ? (Array.isArray(r.data)?r.data:[r.data]) : [];
      const $dd = $('#contract_offer_id'); $dd.empty().append('<option value="0">— Fără —</option>');
      arr.forEach(o=> $dd.append($('<option></option>').val(o.id).text((o.offer_number||'—')+' · '+(o.offer_date||'')+' · '+toNum(o.total_value).toFixed(2)+' RON')));
    });
}
$('#builderLoadOffers').on('click', loadOfferDropdown);

/* on select offer → fill object (one per line) and total */
$('#contract_offer_id').on('change', function(){
  const id=parseInt($(this).val(),10); if(!id) return;
  $.ajax({ url: API+'&f=get_offers', type:'POST', dataType:'json', data:{ id } })
    .done(resp=>{
      if(!resp.success||!resp.data) return;
      const of = Array.isArray(resp.data)?resp.data[0]:resp.data;
      // Build clean object from items or details
      let obj='';
      if (of.object) obj=of.object;
      else if (of.items && Array.isArray(of.items) && of.items.length) {
        obj = of.items.map(it => String(it.description||'').trim()).filter(Boolean).join('\n');
      } else if (of.details) obj = of.details;
      if (obj) {
        // create/update a first section "Obiect"
        const $first = $('#sectionsWrap .section-row').first();
        if ($first.length===0) $('#sectionsWrap').append(sectionTpl(uid(),'Obiectul Contractului', obj));
        else {
          $first.find('input').val('Obiectul Contractului');
          $first.find('textarea').val(obj);
        }
      }
      if (of.total_value) $('#contract_total_value').val(toNum(of.total_value).toFixed(2));
      if (!$('#contract_number').val() && of.offer_number) $('#contract_number').val('CONTRACT-'+of.offer_number);
      toastr.info('Completat din ofertă.');
    });
});

/* ===== Contract save ===== */
$('#contractForm').on('submit', function(e){
  e.preventDefault();
  const cui = $('#contract_company_cui').val() || $('#builderCompanyCUI').val();
  if(!cui){ toastr.error('Selectați compania.'); return; }
  const sections=[];
  $('#sectionsWrap .section-row').each(function(){
    const title=$(this).find('input').val().trim();
    const text =$(this).find('textarea').val().trim();
    if(title || text) sections.push({title, text});
  });
  const payload = {
    action:'add',
    company_cui:cui,
    contract_number: $('#contract_number').val(),
    contract_date:   $('#contract_date').val(),
    duration_days:   $('#contract_duration_days').val(),
    offer_id:        $('#contract_offer_id').val(),
    total_value:     $('#contract_total_value').val(),
    special_clauses: $('#contract_special_clauses').val(),
    sections_json:   JSON.stringify(sections)
  };
  $.ajax({ url: API+'&f=save_contract2', type:'POST', dataType:'json', data:payload })
    .done(r=>{
      if(r.success){
        toastr.success(r.message||'Contract salvat.');
        showPrintChoice('contract', r.id||null);
      } else toastr.error(r.error||'Eroare la salvare contract.');
    }).fail(()=>toastr.error('Eroare rețea.'));
});

/* ===== Print choice modal ===== */
let _lastSaved = { type:null, id:null };
function showPrintChoice(type,id){ _lastSaved={type,id};
  $('#printOfferBtn').prop('disabled', type!=='offer');
  $('#printContractBtn').prop('disabled', type!=='contract');
  $('#printChoiceText').text(type==='offer'?'Ofertă salvată. Tipărești acum?':type==='contract'?'Contract salvat. Tipărești acum?':'Tipărești acum?');
  $('#printChoiceModal').modal('show');
}
function printOffer(id){ /* TODO */ console.log('printOffer', id); }
function printContract(id){ /* TODO */ console.log('printContract', id); }
$('#printOfferBtn').on('click', ()=>{ $('#printChoiceModal').modal('hide'); printOffer(_lastSaved.id); });
$('#printContractBtn').on('click', ()=>{ $('#printChoiceModal').modal('hide'); printContract(_lastSaved.id); });

/* ===== Init ===== */
addOfferItemRow(); // start with one row
</script>
