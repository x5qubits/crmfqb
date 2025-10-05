function addOfferItemRow(item={}){
  const index=offerItemIndex++, qty=toNum(item.quantity||1), price=toNum(item.unit_price||0), sub=qty*price;
  const row =
    '<tr class="offer-item-row" data-index="'+index+'">'+
      '<td style="position:relative;">'+
        '<textarea class="form-control form-control-sm item-description" rows="1" required>'+(item.description||'')+'</textarea>'+
        '<div class="autocomplete-suggestions" id="suggestions-'+index+'" style="display:none;"></div>'+
      '</td>'+
      '<td><input type="number" class="form-control form-control-sm item-quantity" value="'+qty+'" min="1" required></td>'+
      '<td><input type="number" step="0.01" class="form-control form-control-sm item-unit-price" value="'+price.toFixed(2)+'" min="0" required></td>'+
      '<td class="item-subtotal text-right">'+sub.toFixed(2)+'</td>'+
      '<td><button type="button" class="btn btn-xs btn-danger remove-offer-item"><i class="fas fa-trash"></i></button></td>'+
    '</tr>';
  $('#offerItemsTableBody').append(row);
  calculateOfferTotal();
}

function calculateOfferTotal(){
  let subtotal = 0;
  $('.offer-item-row').each(function(){
    const row = $(this);
    const q = toNum(row.find('.item-quantity').val());
    const up = toNum(row.find('.item-unit-price').val());
    const s = q * up;
    row.find('.item-subtotal').text(s.toFixed(2));
    subtotal += s;
  });

  const discountType = $('#offer_discount_type').val() || 'percent';
  const discountAmount = toNum($('#offer_discount_amount').val());
  
  let discountValue = 0;
  if (discountType === 'percent') {
    discountValue = subtotal * (discountAmount / 100);
  } else {
    discountValue = Math.min(discountAmount, subtotal);
  }
  
  const total = Math.max(0, subtotal - discountValue);
  
  $('#offer_subtotal_display').text(subtotal.toFixed(2));
  $('#offer_discount_display').text(discountValue.toFixed(2));
  $('#offer_total_display_main').text(total.toFixed(2));
  $('#offer_total_value').val(total.toFixed(2));
}

$(document).on('click','.remove-offer-item', function(){ 
  $(this).closest('.offer-item-row').remove(); 
  if($('.offer-item-row').length===0) addOfferItemRow(); 
  calculateOfferTotal(); 
});

$(document).on('input','.item-quantity, .item-unit-price', calculateOfferTotal);
$(document).on('input change', '#offer_discount_amount, #offer_discount_type', calculateOfferTotal);

/* autocomplete */
let autocompleteTimer=null;
$(document).on('input','.item-description', function(){
  const $t=$(this), row=$t.closest('.offer-item-row'), index=row.data('index'), $sug=$('#suggestions-'+index), term=$t.val().trim();
  clearTimeout(autocompleteTimer);
  if(term.length<2){ $sug.hide().empty(); return; }
  autocompleteTimer=setTimeout(function(){
    $.ajax({
      url:'api.php?f=get_item_suggestions&user_id='+USER_ID+'',
      type:'POST', dataType:'json', data:{ q: term }
    }).done(resp=>{
      if(!resp.success||!resp.data||!resp.data.length){ $sug.hide().empty(); return; }
      $sug.empty();
	  resp.data.forEach(function(it){
		  const price=toNum(it.unit_price).toFixed(2);
		  const $div=$('<div class="autocomplete-suggestion"></div>')
			.text(it.description+' - '+price+' RON')
			.attr('data-index', row.data('index'))
			.data('product', { description: it.description, unit_price: price });
		  $sug.append($div);
		});
      $sug.show();
    }).fail(()=>{ $sug.hide().empty(); });
  },250);
});

$(document).on('mousedown','.autocomplete-suggestion', function(e){ e.preventDefault(); });
$(document).on('click','.autocomplete-suggestion', function(){
  const p=$(this).data('product')||{}, idx=$(this).attr('data-index'); if(!idx) return;
  const row=$('.offer-item-row[data-index="'+idx+'"]');
  row.find('.item-description').val(p.description||'');
  row.find('.item-unit-price').val(toNum(p.unit_price).toFixed(2));
  $('#suggestions-'+idx).hide().empty();
  calculateOfferTotal();
});

$(document).on('click', function(e){ 
  if(!$(e.target).closest('.item-description, .autocomplete-suggestions').length){ $('.autocomplete-suggestions').hide(); } 
});

function generateOfferNumber(cb){
  $.ajax({ url:'api.php?f=generate_offer_number&user_id='+USER_ID+'', type:'POST', dataType:'json'
  }).done(r=>cb(r.success&&r.offer_number?r.offer_number:('EROARE/'+new Date().getFullYear()))).fail(()=>cb('EROARE/'+new Date().getFullYear()));
}

function openOfferModal(cui, name, offerData = null) {
  $('#offer_action').val(offerData ? 'edit' : 'add');
  $('#offer_id').val(offerData ? offerData.id : '');
  $('#offer_company_cui').val(cui);
  $('#offer_company_name').val(name);
  offerItemIndex = 0;
  $('#offerItemsTableBody').empty();

  if (offerData) {
    $('#offer_number').val(offerData.offer_number);
    $('#offer_date').val(offerData.offer_date);
    $('#offer_details').val(offerData.details || '');
    $('#offer_discount_type').val(offerData.discount_type || 'percent');
    $('#offer_discount_amount').val(offerData.discount_amount || 0);
    
    if (offerData.items && Array.isArray(offerData.items) && offerData.items.length) {
      offerData.items.forEach(it => {
        it.quantity = toNum(it.quantity);
        it.unit_price = toNum(it.unit_price);
        addOfferItemRow(it);
      });
    } else {
      addOfferItemRow();
    }
  } else {
    generateOfferNumber(nr => $('#offer_number').val(nr));
    $('#offer_date').val(new Date().toISOString().slice(0, 10));
    $('#offer_discount_type').val('percent');
    $('#offer_discount_amount').val(0);
    addOfferItemRow();
  }
  
  calculateOfferTotal();
  $('#historyModal').modal('hide');
  $('#offerModal').modal('show');
}

$('#offerForm').on('submit', function(e){
  e.preventDefault();
  const items=[]; let ok=true;
  $('.offer-item-row').each(function(){
    const r=$(this), d=r.find('.item-description').val().trim(), q=toNum(r.find('.item-quantity').val()), up=toNum(r.find('.item-unit-price').val());
    if(d && q>0 && up>=0) items.push({ description:d, quantity:q, unit_price:up }); else ok=false;
  });
  if(!ok){ toastr.error('Completați corect toate liniile.'); return; }
  $('#offer_items_json').val(JSON.stringify(items));
  $.ajax({ url:'api.php?f=save_offer&user_id='+USER_ID+'', type:'POST', dataType:'json', data:$(this).serialize()
  }).done(r=>{
	if (r.success){
	  const newId = r.id || $('#offer_id').val();
	  $('#offerModal').modal('hide');
	  toastr.success(r.message||'Ofertă salvată.');
	  loadAllOffers();
	  showPrintChoice('offer', newId);
	} else {
	  toastr.error(r.error||'Eroare la salvare.');
	}
  });
});

$('#btnAddOfferHistory').on('click', function(){ openOfferModal(currentCompanyCUI,currentCompanyName); });

$(document).on('click','.delete-offer-history', function(){
  const id=$(this).data('id');
  $('#deleteMessage').text('Ștergi oferta? Toate articolele vor fi șterse.');
  deleteCallback=function(){
    $.ajax({ url:'api.php?f=delete_offer&user_id='+USER_ID+'', type:'POST', dataType:'json', data:{id}
    }).done(r=>{ if(r.success){ loadHistoryData(currentCompanyCUI,currentCompanyName); loadAllOffers(); toastr.success('Ofertă ștearsă.'); } else toastr.error(r.error||'Eroare la ștergere.'); });
  };
  $('#deleteModal').modal('show');
});

$(document).on('click','.edit-offer-history', function(){
  const id=$(this).data('id'), cui=$(this).data('cui'), name=base64_decode($(this).data('name'));
  $.ajax({ url:'api.php?f=get_offers&user_id='+USER_ID+'', type:'POST', data:{id}, dataType:'json'
  }).done(resp=>{
    if(resp.success && resp.data){
      const of = Array.isArray(resp.data)?resp.data[0]:resp.data;
      openOfferModal(cui,name,of);
    } else toastr.error('Eroare la preluarea ofertei.');
  });
});

$(document).on('click', '#addItemButton', function(e){ e.preventDefault(); addOfferItemRow(); });
