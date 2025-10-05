function populateOfferDropdown(cui, selectedOfferId=null){
  const $dd=$('#contract_offer_id'); $dd.empty().append('<option value="0">--- Fără Ofertă Asociată ---</option>');
  function fill(list){
    const offers=(list||[]).filter(o=>String(o.company_cui)===String(cui));
    offers.sort((a,b)=>String(a.offer_date).localeCompare(String(b.offer_date)));
    offers.forEach(o=> $dd.append($('<option></option>').val(o.id).text((o.offer_number||'—')+' · '+(o.offer_date||'')+' · '+toNum(o.total_value).toFixed(2)+' RON')));
    if(selectedOfferId) $dd.val(String(selectedOfferId));
  }
  if(allOffersData.length){ fill(allOffersData); return; }
  $.ajax({ url:'api.php?f=get_offers&user_id='+USER_ID+'', type:'POST', dataType:'json', data:{company_cui:cui}
  }).done(r=>fill(r&&r.success?(Array.isArray(r.data)?r.data:[r.data]):[]));
}

$('#contract_offer_id').off('change').on('change', function(){
  const offerId = parseInt($(this).val(), 10);
  if (!offerId) return;

  $.ajax({
    url: 'api.php?f=get_offers&user_id='+USER_ID+'',
    type: 'POST',
    dataType: 'json',
    data: { id: offerId }
  }).done(function(resp){
    if (!resp.success || !resp.data) return;
    const of = Array.isArray(resp.data) ? resp.data[0] : resp.data;

    let obj = '';
    if (of.details) obj = of.details;
    else if (of.object) obj = of.object;
    else if (of.items && Array.isArray(of.items) && of.items.length) {
      obj = of.items.map(it => (it.description || '').trim()).filter(s => s.length > 0).join('\n');
    }

    if (obj) $('#contract_object').val(obj);
    if (of.total_value) $('#contract_total_value').val(toNum(of.total_value).toFixed(2));
    if (!$('#contract_number').val() && of.offer_number) $('#contract_number').val('#' + of.offer_number);
  });
});

function openContractModal(cui,name,contractData=null){
  $('#contract_action').val(contractData?'edit':'add');
  $('#contract_id').val(contractData?contractData.id:'');
  $('#contract_company_cui').val(cui);
  if(contractData){
    $('#contract_number').val(contractData.contract_number);
    $('#contract_date').val(contractData.contract_date);
    $('#contract_object').val(contractData.object);
    $('#contract_special_clauses').val(contractData.special_clauses||'');
    $('#contract_total_value').val(toNum(contractData.total_value).toFixed(2));
    $('#contract_duration_months').val(contractData.duration_months||12);
  } else {
    $('#contract_date').val(new Date().toISOString().slice(0,10));
    $('#contract_object').val('');
    $('#contract_total_value').val('0.00');
    $('#contract_duration_months').val(30);
  }
  populateOfferDropdown(cui, contractData?contractData.offer_id:null);

  $('#contractModal').modal('show');
}

$('#contractForm').on('submit', function(e){
  e.preventDefault();
  $.ajax({ url:'api.php?f=save_contract&user_id='+USER_ID+'', type:'POST', dataType:'json', data:$(this).serialize()
  }).done(r=>{
	if (r.success){
	  const newId = r.id || $('#contract_id').val();
	  $('#contractModal').modal('hide');
	  toastr.success(r.message||'Contract salvat.');
	  showPrintChoice('contract', newId);
	  loadHistoryData(currentCompanyCUI, currentCompanyCUI);
	} else {
	  toastr.error(r.error||'Eroare la salvare.');
	}
  });
});
$(document).on('click','.edit-contract-history', function(){
  const id=$(this).data('id'), cui=$(this).data('cui'), name=base64_decode($(this).data('name'));
  $.ajax({ url:'api.php?f=get_contacts&user_id='+USER_ID+'', type:'POST', data:{ id }, dataType:'json'
  }).done(resp=>{
    if(resp.success && resp.data){
      const ct = Array.isArray(resp.data)?resp.data[0]:resp.data;
      openContractModal(cui,name,ct);
    } else toastr.error('Eroare la preluarea contractului.');
  });
});
$(document).on('click','.delete-contract-history', function(){
  const id=$(this).data('id');
  $('#deleteMessage').text('Ștergi contractul?');
  deleteCallback=function(){
    $.ajax({ url:'api.php?f=delete_contract&user_id='+USER_ID+'', type:'POST', dataType:'json', data:{id}
    }).done(r=>{ if(r.success){ loadHistoryData(currentCompanyCUI,currentCompanyName); toastr.success('Contract șters.'); } else toastr.error(r.error||'Eroare la ștergere.'); });
  };
  $('#deleteModal').modal('show');
});
$('#btnAddContractHistory').on('click', function(){ openContractModal(currentCompanyCUI,currentCompanyName); });
// open modal
$(document).on("click", ".import-contact", function () {
  let btn = $(this);

  // prefill decoded data
  $("#labelField").val(base64_decode(btn.data("name")));
  $("#emailField").val(base64_decode(btn.data("email")));
  let phone = base64_decode(btn.data("phone"));
  if (phone && phone[0] !== '0' && phone[0] !== '+') phone = '+4' + phone;
  $("#phoneField").val(phone);

  // reset other fields
  $("#memoField").val('');
  $("#birthdateField").val('');

  // load campaigns
  $.getJSON("campaigns?ajax=1&action=get_categories_list", function(res){
    if(res.success){
      let $sel = $("#campaignSelect");
      $sel.empty();
      res.data.forEach(c => {
        $sel.append('<option value="'+c.id+'">'+c.name+'</option>');
      });
    }
  });

  // show modal
  $("#importModal").modal("show");
});

// submit form
$("#importForm").on("submit", function(e){
  e.preventDefault();
  let data = $(this).serializeArray();
  data.push({name: "action", value: "add_item"});
  data.push({name: "ajax", value: USER_ID});

  $.post("campaigns.php", data, function(resp){
    console.log("Response:", resp);
    $("#importModal").modal("hide");
  });
});