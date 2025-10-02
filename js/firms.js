function toNum(v){ 
  if(v==null)return 0; 
  if(typeof v==='number'&&isFinite(v))return v; 
  v=String(v).replace(',', '.').replace(/[^\d.-]/g,''); 
  const n=parseFloat(v); 
  return isNaN(n)?0:n; 
}

let searchTimer=null, deleteCallback=null, offerItemIndex=0, allOffersData=[], currentCompanyCUI=null, currentCompanyName=null;

const companiesTable = $('#companiesTable').DataTable({
  responsive:true, lengthChange:false, autoWidth:false, searching:false, paging:false, info:false,
  order:[[1,'asc']],
  language:{url:"//cdn.datatables.net/plug-ins/1.13.4/i18n/ro.json", emptyTable:"Nu s-au găsit companii."},
  columns:[
    {data:'CUI'},
    {data:'Name'},
    {data:'Reg'},
    {data:'Adress'},
    {data:null, orderable:false, render:(d)=>{
      return '<button class="btn btn-xs btn-info view-history" data-cui="'+d.CUI+'" data-name="'+d.Name+'"><i class="fas fa-history"></i></button> '+
        '<button class="btn btn-xs btn-success view-contacts" data-cui="'+d.CUI+'" data-name="'+d.Name+'"><i class="fas fa-users"></i></button> '+
        '<button class="btn btn-xs btn-primary edit-company"><i class="fas fa-edit"></i></button> '+
        '<button class="btn btn-xs btn-danger delete-company" data-cui="'+d.CUI+'"><i class="fas fa-trash"></i></button>';
    }}
  ]
});

function loadCompanies(term=''){
  $('#tableLoader').show();
  $.ajax({ url:'api.php?f=search_companies&user_id='+USER_ID, type:'POST', data:{term}, dataType:'json'
  }).done(r=>{ $('#tableLoader').hide();
    if(r.success && r.data){ companiesTable.clear().rows.add(r.data).draw(); }
    else { toastr.error(r.error||'Eroare la încărcare.'); companiesTable.clear().draw(); }
  }).fail(()=>{ $('#tableLoader').hide(); toastr.error('Eroare de rețea.'); companiesTable.clear().draw(); });
}

function loadAllOffers(){
  $.ajax({ url:'api.php?f=get_offers&user_id='+USER_ID, type:'POST', dataType:'json'
  }).done(r=>{ allOffersData=(r.success&&r.data)?(Array.isArray(r.data)?r.data:[r.data]):[]; }).fail(()=>{ allOffersData=[]; });
}

$('#searchBox').on('keyup', function(){ 
  clearTimeout(searchTimer); 
  const v=$(this).val(); 
  searchTimer=setTimeout(()=>loadCompanies(v),300); 
});

$('#btnAddCompany').on('click', function(){
  $('#companyModalTitle').text('Adaugă Companie');
  $('#company_action').val('add');
  $('#companyForm')[0].reset();
  $('#company_cui_old').val('');
  $('#companyModal').modal('show');
});

$(document).on('click','.edit-company', function(){
  const row = companiesTable.row($(this).parents('tr')).data();
  if(!row){ toastr.error('Nu s-au putut prelua datele.'); return; }
  $('#companyModalTitle').text('Editează Companie: '+row.Name);
  $('#company_action').val('edit');
  $('#company_cui_old').val(row.CUI);
  $('#company_cui').val(row.CUI);
  $('#company_reg').val(row.Reg);
  $('#company_name').val(row.Name);
  $('#company_address').val(row.Adress);
  $('#companyModal').modal('show');
});

$(document).on('click','.delete-company', function(){
  const cui=$(this).data('cui');
  $('#deleteMessage').text('Ștergi compania și istoricul asociat?');
  deleteCallback=function(){
    $.ajax({ url:'api.php?f=delete_company&user_id='+USER_ID, type:'POST', dataType:'json', data:{cui}
    }).done(r=>{ if(r.success){ loadCompanies($('#searchBox').val()); toastr.success('Companie ștearsă.'); } else { toastr.error(r.error||'Eroare la ștergere.'); }});
  };
  $('#deleteModal').modal('show');
});

$('#confirmDelete').on('click', function(){ 
  if(deleteCallback) deleteCallback(); 
  deleteCallback=null; 
  $('#deleteModal').modal('hide'); 
});

$('#companyForm').off('submit').on('submit', function(e){
  e.preventDefault();
  $.ajax({
    url: 'api.php?f=save_company&user_id='+USER_ID,
    type: 'POST',
    dataType: 'json',
    data: $(this).serialize()
  }).done(function(resp){
    if (resp.success) {
      $('#companyModal').modal('hide');
      toastr.success(resp.message || 'Companie salvată.');
      loadCompanies($('#searchBox').val());
    } else {
      toastr.error(resp.error || 'Eroare la salvare.');
    }
  }).fail(function(){ toastr.error('Eroare rețea.'); });
});

loadCompanies();
loadAllOffers();

function loadContacts(cui) {
  $('#contactsLoader').show();
  $.ajax({
    url: 'api.php?f=get_contacts&user_id='+USER_ID,
    type: 'POST',
    data: { company_cui: cui },
    dataType: 'json'
  }).done(resp => {
    $('#contactsLoader').hide();
    const tbody = $('#contactsTableBody');
    tbody.empty();
    
    if (resp.success && resp.data && resp.data.length) {
      resp.data.forEach(c => {
        const roleMap = {0:'Nedefinit',1:'Manager',2:'Director',3:'Principal',4:'Secundar'};
        tbody.append(`
          <tr>
            <td>${c.name}</td>
            <td>${roleMap[c.role]||'Nedefinit'}</td>
            <td>${c.phone}</td>
            <td>${c.email}</td>
            <td>
              <button class="btn btn-xs btn-primary edit-contact" data-id="${c.id}"><i class="fas fa-edit"></i></button>
              <button class="btn btn-xs btn-danger delete-contact" data-id="${c.id}" data-cui="${cui}"><i class="fas fa-trash"></i></button>
            </td>
          </tr>
        `);
      });
    } else {
      tbody.html('<tr><td colspan="5" class="text-center">Nu există contacte.</td></tr>');
    }
  }).fail(() => {
    $('#contactsLoader').hide();
    toastr.error('Eroare la încărcarea contactelor.');
  });
}

$(document).on('click','.view-contacts', function(){ 
  currentCompanyCUI=$(this).data('cui'); 
  currentCompanyName=$(this).data('name'); 
  $('#contactsCompanyName').text(currentCompanyName); 
  $('#contactsModal').data('cui', currentCompanyCUI);
  loadContacts(currentCompanyCUI); 
  $('#contactsModal').modal('show'); 
});

$(document).on('click', '#btnAddContact', function(){
  const cui = $('#contactsModal').data('cui');
  $('#contactForm')[0].reset();
  $('#contact_action').val('add');
  $('#contact_id').val('');
  $('#contact_company').val(cui);
  $('#contact_role').val('1');
  $('#contactModalTitle').text('Adaugă Contact');
  $('#contactModal').modal('show');
});

$(document).on('click', '.edit-contact', function(){
  const tr=$(this).closest('tr'), tds=tr.children('td');
  const cui = $('#contactsModal').data('cui');
  $('#contact_action').val('edit');
  $('#contact_id').val($(this).data('id'));
  $('#contact_company').val(cui);
  $('#contact_name').val(tds.eq(0).text().trim());
  const rt=tds.eq(1).text().trim();
  const rid=rt==='Manager'?1:rt==='Director'?2:rt==='Principal'?3:rt==='Secundar'?4:0;
  $('#contact_role').val(String(rid||0));
  $('#contact_phone').val(tds.eq(2).text().trim());
  $('#contact_email').val(tds.eq(3).text().trim());
  $('#contactModalTitle').text('Editează Contact');
  $('#contactModal').modal('show');
});

$('#contactForm').on('submit', function(e){
  e.preventDefault();
  const data = $(this).serializeArray().reduce((a,x)=>{a[x.name]=x.value; return a;}, {});
  data.companie = data.companie || $('#contactsModal').data('cui');
  data.phone = String(data.phone||'').replace(/(?!^\+)\D+/g,'');

  $.ajax({
    url: 'api.php?user_id='+USER_ID+'&f=save_contact',
    type: 'POST',
    dataType: 'json',
    data
  }).done(function(r){
    if (r && r.success) {
      $('#contactModal').modal('hide');
      loadContacts($('#contactsModal').data('cui'));
      toastr.success(r.message || 'Contact salvat.');
    } else {
      toastr.error((r && r.error) || 'Eroare la salvare.');
    }
  });
});

let _del = { id:null, cui:null };
$(document).on('click', '.delete-contact', function(){
  _del = { id: $(this).data('id'), cui: $(this).data('cui') };
  $('#confirmContactDelete').modal('show');
});

$('#confirmContactDeleteBtn').on('click', function(){
  if(!_del.id) { $('#confirmContactDelete').modal('hide'); return; }
  $.ajax({
    url: 'api.php?user_id='+USER_ID+'&f=delete_contact',
    type: 'POST',
    dataType: 'json',
    data: { id: _del.id, contact_id: _del.id }
  }).done(function(r){
    $('#confirmContactDelete').modal('hide');
    if (r && r.success) {
      loadContacts(_del.cui);
      toastr.success('Contact șters.');
    } else {
      toastr.error((r && r.error) || 'Eroare.');
    }
  });
});

/* ===== HISTORY MODAL FUNCTIONALITY ===== */

function loadHistoryData(cui,name){
  // Load Contracts
  $.ajax({ 
    url:'api.php?f=get_contacts&user_id='+USER_ID, 
    type:'POST', 
    data:{company_cui:cui}, 
    dataType:'json'
  }).done(resp=>{
    $('#contractsTableBody').empty();
    if(resp.success && resp.data && resp.data.length){
      $('#contractCount').text(resp.data.length);
      resp.data.forEach(ct=>{
        $('#contractsTableBody').append(
          '<tr>'+
            '<td>'+ct.contract_number+'</td>'+
            '<td>'+ct.contract_date+'</td>'+
            '<td>'+ (ct.object?ct.object.substring(0,50)+'...':'') +'</td>'+
            '<td>'+ toNum(ct.total_value).toFixed(2)+' RON</td>'+
            '<td>'+
              '<button class="btn btn-xs btn-primary edit-contract-history" data-id="'+ct.id+'" data-cui="'+cui+'" data-name="'+name+'"><i class="fas fa-edit"></i></button> '+
              '<button class="btn btn-xs btn-success print-contract-history" data-id="'+ct.id+'"><i class="fas fa-print"></i></button> '+
              '<button class="btn btn-xs btn-danger delete-contract-history float-right" data-id="'+ct.id+'"><i class="fas fa-trash"></i></button>'+
            '</td>'+
          '</tr>');
      });
    } else { 
      $('#contractCount').text('0'); 
      $('#contractsTableBody').html('<tr><td colspan="5" class="text-center">Nu există contracte.</td></tr>'); 
    }
  });

  // Load Offers
  $.ajax({ 
    url:'api.php?f=get_offers&user_id='+USER_ID, 
    type:'POST', 
    data:{company_cui:cui}, 
    dataType:'json'
  }).done(resp=>{
    $('#offersTableBody').empty();
    const offers = (resp.success && resp.data) ? (Array.isArray(resp.data)?resp.data:[resp.data]) : [];
    if(offers.length){
      $('#offerCount').text(offers.length);
      offers.forEach(of=>{
        $('#offersTableBody').append(
          '<tr>'+
            '<td>'+of.offer_number+'</td>'+
            '<td>'+of.offer_date+'</td>'+
            '<td>'+ (of.details?of.details.substring(0,50)+'...':'') +'</td>'+
            '<td>'+ toNum(of.total_value).toFixed(2)+' RON</td>'+
            '<td>'+
              '<button class="btn btn-xs btn-primary edit-offer-history" data-id="'+of.id+'" data-cui="'+cui+'" data-name="'+name+'"><i class="fas fa-edit"></i></button> '+
              '<button class="btn btn-xs btn-success print-offer-history" data-id="'+of.id+'"><i class="fas fa-print"></i></button> '+
              '<button class="btn btn-xs btn-danger delete-offer-history float-right" data-id="'+of.id+'"><i class="fas fa-trash"></i></button>'+
            '</td>'+
          '</tr>');
      });
    } else { 
      $('#offerCount').text('0'); 
      $('#offersTableBody').html('<tr><td colspan="5" class="text-center">Nu există oferte.</td></tr>'); 
    }
  });
}

$(document).on('click', '.view-history', function(e) {
  e.preventDefault();
  const cui = $(this).data('cui');
  const name = $(this).data('name');
  
  currentCompanyCUI = cui;
  currentCompanyName = name;
  
  $('#historyCompanyName').text(name);
  loadHistoryData(cui, name);
  loadInvoicesForCompany(cui); // LOAD INVOICES - NEW
  
  $('#historyModal').modal('show');
});

/* ===== CONTRACT FUNCTIONALITY (EXISTING) ===== */

$(document).on('click', '#btnAddContractHistory', function(){
  $('#contractModalTitle').text('Generează Contract');
  $('#contract_action').val('add');
  $('#contractForm')[0].reset();
  $('#contract_id').val('');
  $('#contract_company_cui').val(currentCompanyCUI);
  $('#contract_date').val(new Date().toISOString().split('T')[0]);
  
  populateOfferDropdown(currentCompanyCUI);
  loadContractTemplates();
  
  $('#contractModal').modal('show');
});

function populateOfferDropdown(cui) {
  const select = $('#contract_offer_id');
  select.empty().append('<option value="0">--- Fără Ofertă Asociată ---</option>');
  
  const relevantOffers = allOffersData.filter(o => o.company_cui == cui);
  relevantOffers.forEach(of => {
    select.append(`<option value="${of.id}">Oferta ${of.offer_number} - ${of.offer_date} (${toNum(of.total_value).toFixed(2)} RON)</option>`);
  });
}

function loadContractTemplates() {
  $.get('api.php?f=get_contract_templates&user_id='+USER_ID, function(resp) {
    const select = $('#contract_template');
    select.empty().append('<option value="">---</option>');
    if (resp.success && resp.data) {
      resp.data.forEach(tpl => {
        select.append(`<option value="${tpl.id}">${tpl.title}</option>`);
      });
    }
  }, 'json');
}

$('#contractForm').on('submit', function(e){
  e.preventDefault();
  const formData = new FormData(this);
  
  $.ajax({
    url: 'api.php?f=save_contract&user_id='+USER_ID,
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    dataType: 'json'
  }).done(r => {
    if (r.success) {
      $('#contractModal').modal('hide');
      toastr.success('Contract salvat.');
      loadHistoryData(currentCompanyCUI, currentCompanyName);
      if (r.id) {
        showPrintChoice('contract', r.id);
      }
    } else {
      toastr.error(r.error || 'Eroare la salvare.');
    }
  });
});

/* ===== OFFER FUNCTIONALITY (EXISTING) ===== */

$(document).on('click', '#btnAddOfferHistory', function(){
  $('#offerModalTitle').text('Generează Ofertă');
  $('#offer_action').val('add');
  $('#offerForm')[0].reset();
  $('#offer_id').val('');
  $('#offer_company_cui').val(currentCompanyCUI);
  $('#offer_date').val(new Date().toISOString().split('T')[0]);
  
  $('#offerItemsTable').empty();
  offerItemIndex = 0;
  addOfferItemRow();
  
  loadOfferTemplates();
  calculateOfferTotals();
  
  $('#offerModal').modal('show');
});

function loadOfferTemplates() {
  $.get('api.php?f=get_offer_templates&user_id='+USER_ID, function(resp) {
    const select = $('#offer_template');
    select.empty().append('<option value="">---</option>');
    if (resp.success && resp.data) {
      resp.data.forEach(tpl => {
        select.append(`<option value="${tpl.id}">${tpl.title}</option>`);
      });
    }
  }, 'json');
}

function addOfferItemRow(desc='', qty=1, price=0) {
  const idx = offerItemIndex++;
  const row = $(`
    <tr class="offer-item-row" data-index="${idx}">
      <td><input type="text" class="form-control form-control-sm item-desc" name="items[${idx}][description]" value="${desc}" required></td>
      <td><input type="number" class="form-control form-control-sm item-qty" name="items[${idx}][quantity]" value="${qty}" min="1" required></td>
      <td><input type="number" class="form-control form-control-sm item-price" name="items[${idx}][unit_price]" value="${price}" step="0.01" min="0" required></td>
      <td><input type="text" class="form-control form-control-sm item-subtotal" readonly value="0.00"></td>
      <td><button type="button" class="btn btn-xs btn-danger remove-offer-item"><i class="fas fa-times"></i></button></td>
    </tr>
  `);
  $('#offerItemsTable').append(row);
  calculateOfferTotals();
}

$('#addOfferItem').on('click', function(){ addOfferItemRow(); });

$(document).on('click', '.remove-offer-item', function(){
  $(this).closest('tr').remove();
  calculateOfferTotals();
});

$(document).on('input', '.item-qty, .item-price, #offer_discount_value', function(){
  calculateOfferTotals();
});

$(document).on('change', '#offer_discount_type', function(){
  calculateOfferTotals();
});

function calculateOfferTotals() {
  let subtotal = 0;
  
  $('.offer-item-row').each(function(){
    const qty = toNum($(this).find('.item-qty').val());
    const price = toNum($(this).find('.item-price').val());
    const itemSub = qty * price;
    $(this).find('.item-subtotal').val(itemSub.toFixed(2));
    subtotal += itemSub;
  });
  
  const discountType = $('#offer_discount_type').val();
  const discountValue = toNum($('#offer_discount_value').val());
  
  let discountAmount = 0;
  if (discountType === 'percent') {
    discountAmount = subtotal * (discountValue / 100);
  } else {
    discountAmount = discountValue;
  }
  
  const total = subtotal - discountAmount;
  
  $('#offerSubtotal').text(subtotal.toFixed(2) + ' RON');
  $('#offerDiscountAmount').text(discountAmount.toFixed(2) + ' RON');
  $('#offerTotal').text(total.toFixed(2) + ' RON');
}

$('#offerForm').on('submit', function(e){
  e.preventDefault();
  const formData = new FormData(this);
  
  $.ajax({
    url: 'api.php?f=save_offer&user_id='+USER_ID,
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    dataType: 'json'
  }).done(r => {
    if (r.success) {
      $('#offerModal').modal('hide');
      toastr.success('Ofertă salvată.');
      loadHistoryData(currentCompanyCUI, currentCompanyName);
      loadAllOffers();
      if (r.id) {
        showPrintChoice('offer', r.id);
      }
    } else {
      toastr.error(r.error || 'Eroare la salvare.');
    }
  });
});

/* ===== PRINT FUNCTIONALITY ===== */

let _lastSaved = { type: '', id: 0 };

function showPrintChoice(type, id) {
  _lastSaved = { type, id };
  $('#printChoiceText').text(
    type === 'offer' ? 'Ofertă salvată. Tipăriți acum?' :
    type === 'contract' ? 'Contract salvat. Tipăriți acum?' : 'Tipăriți acum?'
  );
  $('#printOfferBtn').prop('disabled', type !== 'offer');
  $('#printContractBtn').prop('disabled', type !== 'contract');
  $('#printChoiceModal').modal('show');
}

function printOffer(id){ window.open('print_offer.php?offer_id='+id,'_blank'); }
function printContract(id){ window.open('print_contract.php?id='+id,'_blank'); }

$('#printOfferBtn').on('click', function(){ 
  $('#printChoiceModal').modal('hide'); 
  printOffer(_lastSaved.id); 
});

$('#printContractBtn').on('click', function(){ 
  $('#printChoiceModal').modal('hide'); 
  printContract(_lastSaved.id); 
});

$(document).on('click', '.print-contract-history', function(){ 
  printContract($(this).data('id')); 
});

$(document).on('click', '.print-offer-history', function(){ 
  printOffer($(this).data('id')); 
});