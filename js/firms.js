function toNum(v){ 
  if(v==null)return 0; 
  if(typeof v==='number'&&isFinite(v))return v; 
  v=String(v).replace(',', '.').replace(/[^\d.-]/g,''); 
  const n=parseFloat(v); 
  return isNaN(n)?0:n; 
}

function base64_encode(s) {      
    return btoa(unescape(encodeURIComponent(s)));
}
function base64_decode(s) {      
    return decodeURIComponent(escape(atob(s)));
}

let searchTimer=null, deleteCallback=null, offerItemIndex=0, allOffersData=[];

const companiesTable = $('#companiesTable').DataTable({
  responsive:true, lengthChange:false, autoWidth:false, searching:false, paging:false, info:false,
  order:[[1,'asc']],
  language:{url:"//cdn.datatables.net/plug-ins/1.13.4/i18n/ro.json", emptyTable:"Nu s-au găsit companii."},
  columns:[
    {data:'CUI', render: function (data, type, r) {
        if (type === 'display') return '<a href="#" data-cui="'+r.CUI+'" data-name="'+base64_encode(r.Name)+'" class="view-history">'+data+'</a>';
        return data;
      }},
	{data:'Name', render: function (data, type, r) {
        if (type === 'display') return '<a href="#" data-cui="'+r.CUI+'" data-name="'+base64_encode(r.Name)+'" class="view-contacts">'+data+'</a>';
        return data;
      }},
    {data:null, orderable:false, render:(d)=>{
      return '<button class="btn btn-xs btn-info view-history" data-cui="'+d.CUI+'" data-name="'+base64_encode(d.Name)+'"><i class="fas fa-history"></i></button> '+
        '<button class="btn btn-xs btn-success view-contacts" data-cui="'+d.CUI+'" data-name="'+base64_encode(d.Name)+'"><i class="fas fa-users"></i></button> '+
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


function loadContacts(cui) {
  $('#contactsLoader').show();
  $('#contactsTableBody').html('<tr><td colspan="5" class="text-center">Se încarcă...</td></tr>');
  $.ajax({ url:'api.php?f=get_company_details&user_id='+USER_ID+'', type:'GET', data:{cui}, dataType:'json'
  }).done(resp=>{
    $('#contactsLoader').hide(); $('#contactsTableBody').empty();
    if(resp.success && resp.company && resp.company.contacts && resp.company.contacts.length){
      resp.company.contacts.forEach(c=>{
        const roleText = c.contact_role==1?'Manager':c.contact_role==2?'Director':c.contact_role==3?'Principal':c.contact_role==4?'Secundar':'Nedefinit';
        $('#contactsTableBody').append('<tr><td>'+c.contact_name+'</td><td>'+roleText+'</td><td class="openphoneorwa">'+c.contact_phone+'</td><td class="sendmail">'+c.contact_email+'</td><td><button class="btn btn-xs btn-primary edit-contact" data-id="'+c.contact_id+'"><i class="fas fa-edit"></i></button>  <button class="btn btn-xs btn-success import-contact" data-id="'+c.contact_id+'" data-name="'+base64_encode(c.contact_name)+'" data-email="'+base64_encode(c.contact_email)+'" data-phone="'+base64_encode(c.contact_phone)+'"><i class="fas fa-bell"></i></button> <button class="btn btn-xs btn-danger float-right delete-contact" data-id="'+c.contact_id+'" data-cui="'+cui+'"><i class="fas fa-trash"></i></button></td></tr>');
      });
    } else $('#contactsTableBody').html('<tr><td colspan="5" class="text-center">Nu există contacte.</td></tr>');
  }).fail(()=>{ $('#contactsLoader').hide(); toastr.error('Eroare la preluarea contactelor.'); });
}


$(document).on('click','.view-contacts', function(e){ 
  e.preventDefault();
  currentCompanyCUI=$(this).data('cui'); 
  currentCompanyName=base64_decode($(this).data('name')); 
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
              '<button class="btn btn-xs btn-primary edit-contract-history" data-id="'+ct.id+'" data-cui="'+cui+'" data-name="'+base64_encode(name)+'"><i class="fas fa-edit"></i></button> '+
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
              '<button class="btn btn-xs btn-primary edit-offer-history" data-id="'+of.id+'" data-cui="'+cui+'" data-name="'+base64_encode(name)+'"><i class="fas fa-edit"></i></button> '+
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
  const name = base64_decode($(this).data('name'));
  
  currentCompanyCUI = cui;
  currentCompanyName = name;
  
  $('#historyCompanyName').text(name);
  loadHistoryData(cui, name);
  loadInvoicesForCompany(cui); // LOAD INVOICES - NEW
  
  $('#historyModal').modal('show');
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
function debounce(fn, ms){ let t; return function(){ clearTimeout(t); t=setTimeout(fn.bind(this, ...arguments), ms); }; }

function fillCompanyFromCIV(resp){
  if(!resp) return;
  const d = resp.data || resp;
  const name = d.Name ?? d.name ?? '';
  const reg = d.Reg ?? d.reg ?? d.regcom ?? '';
  const address = d.Adress ?? d.adress ?? d.address ?? '';
  const cui = d.CUI ?? d.cui ?? d.civ ?? '';

  if (name) $('#company_name').val(name);
  if (reg) $('#company_reg').val(reg);
  if (address) $('#company_address').val(address);
  if (cui && !$('#company_cui').val()) $('#company_cui').val(cui);
}
const fetchCIV = debounce(function(){
  const cui = $('#company_cui').val().replace(/\D+/g,'');
  if (cui.length < 2) return;
  $.ajax({
    url: 'getCIV.php',
    type: 'POST',
    dataType: 'json',
    data: { what: cui }
  }).done(function(txt){ fillCompanyFromCIV(txt); });
}, 300);


$(document).on('blur', '#company_cui', function(){ fetchCIV.call(this); });

$(function() {
  let selectedNumber = "";

  // Open modal on click
  $(document).on("click", ".openphoneorwa", function(e) {
    e.preventDefault();
    let rawNumber = $(this).text() || "";

    // Normalize phone: add +4 if missing
    if (!rawNumber.startsWith("+")) {
      if (!rawNumber.startsWith("4")) {
        rawNumber = "+4" + rawNumber;
      } else {
        rawNumber = "+" + rawNumber;
      }
    }

    selectedNumber = rawNumber;
    $("#modalPhoneText").text("Selected number: " + selectedNumber);
    $("#phoneOrWaModal").modal("show");
  });

  // WhatsApp action
  $("#btnWhatsApp").on("click", function() {
    if (selectedNumber) {
      window.open("https://wa.me/" + selectedNumber.replace(/\D/g, ""), "_blank");
    }
	 $("#phoneOrWaModal").modal("hide");
  });

  // Call action
  $("#btnCall").on("click", function() {
    if (selectedNumber) {
      window.location.href = "tel:" + selectedNumber;
    }
	 $("#phoneOrWaModal").modal("hide");
  });
});

$(function () {
    var key = "searchBoxValue";

    // Load saved value on page load
    var saved = localStorage.getItem(key);
    if (saved) {
        setTimeout(function () {
            $("#searchBox").val(saved).trigger("keyup");
        }, 1000);
    }

    // Save on input
    $("#searchBox").on("input", function () {
        localStorage.setItem(key, $(this).val());
    });
});