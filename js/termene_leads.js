/* global USER_ID */
$(function () {
  const $tbl = $('#leadsTable').DataTable({ responsive: true, pageLength: 25, order: [[2,'asc']] });
  const $imp = $('#importedTable').DataTable({ responsive: true, pageLength: 10, order: [[0,'desc']] });

  function normalizePhone(p){ if(!p) return null; p = (''+p).replace(/\s+/g,''); return p.startsWith('+4') ? p : '+4' + p.replace(/^\+?/, ''); }

function rowActionButtons(m){
  const name = (m.nume || '').replace(/"/g,'&quot;');
  return `
    <button class="btn btn-xs btn-primary act-view" data-cui="${m.cui}" data-name="${name}">Detalii</button>
    <button class="btn btn-xs btn-success ml-1 act-import" data-cui="${m.cui}" data-name="${name}">Import</button>
  `;
}

function mapToRow(o){
  const g = o['Date Generale'] || o['dateGenerale'] || o;
  const cui = (g.cui || g.cif || g.CUI || '').toString();
  return {
    select: '',
    cui: cui,
    nume: g.nume || g.denumire || g.firma || '',
    judet: g.judet || g.county || '',
    localitate: g.localitate || g.locality || g.oras || '',
    telefon: g.telefon || g.phone || '',
    caen: g.cod_caen || g.CAEN || '',
    tip_activitate: g.tip_activitate || g.activitate || '',
    cifra: g.cifra_de_afaceri_neta ? parseInt(g.cifra_de_afaceri_neta,10)
          : (g.cifra_afaceri ? parseInt(g.cifra_afaceri,10) : ''),
    statut_fiscal: g.statut_fiscal || g.status || '',
    statut_tva: g.statut_TVA || g.tva || ''
  };
}

  function loadImported(){
    $.getJSON('termene_leads_api.php', { action:'list_imported' }, function(r){
      if(!r.success) return;
      $imp.clear();
      r.data.forEach(x => {
        $imp.row.add([x.created_at, x.cui, x.name, x.phone||'', x.judet||'', x.localitate||'', x.cod_caen||'']);
      });
      $imp.draw();
    });
  }

  $('#btnSearch').on('click', function(){
	const params = {
	  action:'search',
	  nume: $('#f_nume').val().trim(),
	  cod_caen: $('#f_caen').val().trim(),
	  judet: $('#f_judet').val().trim(),
	  cifra_min: $('#f_ca_min').val().trim(),
	  only_active: $('#f_active').is(':checked') ? 1 : 0,
	  only_phone:  $('#f_phone_only').is(':checked') ? 1 : 0
	};
    $.getJSON('termene_leads_api.php', params, function(r){
      if(!r.success){ toastr.error(r.error||'Eroare'); return; }
      $tbl.clear();
      r.data.forEach(item => {
        const m = mapToRow(item);
        $tbl.row.add([
          `<input type="checkbox" class="selLead" data-cui="${m.cui}" data-name="${_.escape(m.nume)}">`,
          m.cui, m.nume, m.judet, m.localitate, m.telefon||'', m.caen, m.tip_activitate,
          m.cifra||'', m.statut_fiscal, m.statut_tva, rowActionButtons(m)
        ]);
      });
      $tbl.draw();
    });
  });

let pendingImport = null;
$('#leadsTable').on('click', '.act-import', function(){
  const cui  = $(this).data('cui');
  const name = $(this).data('name');
  if(!cui){ toastr.error('CUI missing in row'); return; }
  pendingImport = { cui, name };
  $('#imp_name').text(name || '');
  $('#imp_cui').text(cui);
  $('#imp_notes').val('');
  $('#importModal').modal('show');
});

$('#btnDoImport').on('click', function(){
  if(!pendingImport || !pendingImport.cui){ toastr.error('No CUI'); return; }
  $.post('termene_leads_api.php', {
    action: 'import_by_cui',
    cui: pendingImport.cui,
    notes: $('#imp_notes').val()
  }, function(r){
    if(!r.success){ toastr.error(r.error||'Eroare import'); return; }
    toastr.success('Importat');
    $('#importModal').modal('hide');
    loadImported();
  }, 'json');
});

  // bulk import
  $('#btnImportSelected').on('click', function(){
    const list = [];
    $('.selLead:checked').each(function(){
      list.push($(this).data('cui'));
    });
    if(list.length === 0){ toastr.info('Selectează cel puțin o firmă'); return; }
    $.post('termene_leads_api.php', { action:'import_bulk', cuis: JSON.stringify(list) }, function(r){
      if(!r.success){ toastr.error(r.error||'Eroare import'); return; }
      toastr.success(`Importate ${r.imported} / ${list.length}`);
      loadImported();
    }, 'json');
  });

  loadImported();
});
