<?php
include_once("config.php");
$pageName = "Campaign Manager";
$pageId = 1;
$pageIds = isset($_GET['type']) ? (int)$_GET['type']:0;
$selected_mkp = isset($_SESSION['user_mps']) ? (int)$_SESSION['user_mps'] : 0;
$user_id_js = isset($user_id) ? $user_id : 1;
include_once("db.php");
session_start();

function current_user_id(){
    if (isset($_SESSION['user_id'])) return (int)$_SESSION['user_id'];
    if (isset($GLOBALS['user_id'])) return (int)$GLOBALS['user_id'];
    return 1;
}
$UID = current_user_id();
$schema = __DIR__ . '/sql/campains_schema.sql';
if (file_exists($schema)) { $pdo->exec(file_get_contents($schema)); }
function q($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function json_response($data){ header('Content-Type: application/json'); echo json_encode($data); exit; }

if (!empty($_POST['ajax']) || !empty($_GET['ajax'])) {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    if ($action === 'get_campaigns') {
        $rows = $pdo->query("SELECT * FROM campains_campaigns WHERE user_id=".$UID." ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        json_response(['success'=>true, 'data'=>$rows]);
    }
    if ($action === 'get_campaign') {
        $id = (int)$_GET['id'];
        $row = $pdo->prepare("SELECT * FROM campains_campaigns WHERE id=? AND user_id=?");
        $row->execute([$id, $UID]);
        json_response(['success'=>true, 'data'=>$row->fetch(PDO::FETCH_ASSOC)]);
    }
    if ($action === 'get_categories') {
        $rows = $pdo->query("SELECT c.*, COUNT(i.id) as item_count FROM campains_categories c LEFT JOIN campains_category_items i ON i.category_id=c.id AND i.user_id=c.user_id WHERE c.user_id=".$UID." GROUP BY c.id ORDER BY c.id DESC")->fetchAll(PDO::FETCH_ASSOC);
        json_response(['success'=>true, 'data'=>$rows]);
    }
    if ($action === 'get_items') {
        $rows = $pdo->query("SELECT i.*, c.name AS cat_name FROM campains_category_items i JOIN campains_categories c ON c.id=i.category_id WHERE i.user_id=".$UID." ORDER BY i.id DESC")->fetchAll(PDO::FETCH_ASSOC);
        json_response(['success'=>true, 'data'=>$rows]);
    }
    if ($action === 'get_queue') {
        $rows = $pdo->query("SELECT q.*, cp.title, cat.name AS cat_name, it.label, it.email, it.phone FROM campains_queue q JOIN campains_campaigns cp ON cp.id=q.campaign_id JOIN campains_category_items it ON it.id=q.item_id LEFT JOIN campains_categories cat ON cat.id=q.category_id WHERE q.user_id=".$UID." ORDER BY q.id DESC")->fetchAll(PDO::FETCH_ASSOC);
        json_response(['success'=>true, 'data'=>$rows]);
    }
    if ($action === 'get_stats') {
        $stats = ['campaigns' => $pdo->query("SELECT COUNT(*) FROM campains_campaigns WHERE user_id=".$UID)->fetchColumn(), 'categories' => $pdo->query("SELECT COUNT(*) FROM campains_categories WHERE user_id=".$UID)->fetchColumn(), 'items' => $pdo->query("SELECT COUNT(*) FROM campains_category_items WHERE user_id=".$UID)->fetchColumn(), 'queued' => $pdo->query("SELECT COUNT(*) FROM campains_queue WHERE user_id=".$UID." AND status='QUEUE'")->fetchColumn()];
        json_response(['success'=>true, 'data'=>$stats]);
    }
    if ($action === 'get_categories_list') {
        $rows = $pdo->query("SELECT id, name FROM campains_categories WHERE user_id=".$UID." ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        json_response(['success'=>true, 'data'=>$rows]);
    }
    if ($action === 'get_campaigns_list') {
        $rows = $pdo->query("SELECT id, title, channel FROM campains_campaigns WHERE user_id=".$UID." ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        json_response(['success'=>true, 'data'=>$rows]);
    }
    if ($action === 'add_campaign') {
        $stmt=$pdo->prepare("INSERT INTO campains_campaigns (user_id,title,channel,schedule_time,subject,body_template,status) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$UID, $_POST['title'], $_POST['channel'], $_POST['schedule_time'], $_POST['subject']??null, $_POST['body_template']??null, $_POST['status']??'DRAFT']);
        json_response(['success'=>true, 'id'=>$pdo->lastInsertId()]);
    }
    if ($action === 'update_campaign') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("UPDATE campains_campaigns SET title=?, channel=?, schedule_time=?, subject=?, body_template=?, status=? WHERE id=? AND user_id=?");
        $stmt->execute([$_POST['title'], $_POST['channel'], $_POST['schedule_time'], $_POST['subject']??null, $_POST['body_template']??null, $_POST['status'], $id, $UID]);
        json_response(['success'=>true]);
    }
    if ($action === 'delete_campaign') {
        $pdo->prepare("DELETE FROM campains_campaigns WHERE id=? AND user_id=?")->execute([(int)$_POST['id'],$UID]);
        json_response(['success'=>true]);
    }
    if ($action === 'add_category') {
        $pdo->prepare("INSERT INTO campains_categories (user_id,name,description) VALUES (?,?,?)")->execute([$UID, $_POST['name'], $_POST['description']??null]);
        json_response(['success'=>true, 'id'=>$pdo->lastInsertId()]);
    }
    if ($action === 'update_category') {
        $id = (int)$_POST['id']; $field = $_POST['field']; $value = $_POST['value'];
        if (in_array($field, ['name','description'])) {
            $pdo->prepare("UPDATE campains_categories SET $field=? WHERE id=? AND user_id=?")->execute([$value, $id, $UID]);
            json_response(['success'=>true]);
        }
        json_response(['success'=>false, 'error'=>'Invalid field']);
    }
    if ($action === 'delete_category') {
        $pdo->prepare("DELETE FROM campains_categories WHERE id=? AND user_id=?")->execute([(int)$_POST['id'],$UID]);
        json_response(['success'=>true]);
    }
    if ($action === 'add_item') {
        $pdo->prepare("INSERT INTO campains_category_items (user_id,category_id,label,email,phone,memo) VALUES (?,?,?,?,?,?)")->execute([$UID,(int)$_POST['category_id'], $_POST['label']??null, $_POST['email']??null, $_POST['phone']??null, $_POST['memo']??null]);
        json_response(['success'=>true, 'id'=>$pdo->lastInsertId()]);
    }
    if ($action === 'update_item') {
        $id = (int)$_POST['id']; $field = $_POST['field']; $value = $_POST['value'];
        $allowed = ['category_id','label','email','phone','memo'];
        if (in_array($field, $allowed)) {
            $pdo->prepare("UPDATE campains_category_items SET $field=? WHERE id=? AND user_id=?")->execute([$value, $id, $UID]);
            json_response(['success'=>true]);
        }
        json_response(['success'=>false, 'error'=>'Invalid field']);
    }
    if ($action === 'delete_item') {
        $pdo->prepare("DELETE FROM campains_category_items WHERE id=? AND user_id=?")->execute([(int)$_POST['id'],$UID]);
        json_response(['success'=>true]);
    }
    if ($action === 'bulk_import_items') {
        $cat = (int)$_POST['category_id']; $fmt = $_POST['format'] ?? 'json'; $tmp = $_FILES['file']['tmp_name'] ?? '';
        if ($cat && $tmp) {
            $rows=[];
            if ($fmt==='json') { $rows = json_decode(file_get_contents($tmp), true) ?: []; }
            else {
                $csv = array_map('str_getcsv', file($tmp));
                if ($csv && count($csv)>1) {
                    $headers = array_map('trim', array_shift($csv));
                    foreach ($csv as $r) { if (!count($r)) continue; $rows[] = array_combine($headers, $r); }
                }
            }
            $ins=$pdo->prepare("INSERT INTO campains_category_items (user_id,category_id,label,email,phone,memo) VALUES (?,?,?,?,?,?)");
            foreach ($rows as $r) { $ins->execute([$UID,$cat,$r['label']??null,$r['email']??null,$r['phone']??null,$r['memo']??null]); }
            json_response(['success'=>true, 'count'=>count($rows)]);
        }
        json_response(['success'=>false, 'error'=>'Invalid data']);
    }
    if ($action === 'enqueue') {
        $campaign_id = (int)($_POST['campaign_id'] ?? 0); $category_ids = $_POST['category_ids'] ?? []; $count = 0;
        if ($campaign_id && is_array($category_ids) && count($category_ids)) {
            $ch=$pdo->prepare("SELECT channel FROM campains_campaigns WHERE id=? AND user_id=?");
            $ch->execute([$campaign_id,$UID]); $row=$ch->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $campaign_channel = $row['channel'];
                $ins=$pdo->prepare("INSERT INTO campains_queue (user_id,campaign_id,category_id,item_id,channel,status) VALUES (?,?,?,?,?, 'QUEUE')");
                foreach ($category_ids as $catId) {
                    $catId=(int)$catId;
                    $items=$pdo->prepare("SELECT id,email,phone FROM campains_category_items WHERE category_id=? AND user_id=?");
                    $items->execute([$catId,$UID]);
                    while ($it=$items->fetch(PDO::FETCH_ASSOC)) {
                        $targets=[];
                        if ($campaign_channel==='EMAIL' || $campaign_channel==='BOTH') { if (!empty($it['email'])) $targets[]='EMAIL'; }
                        if ($campaign_channel==='SMS' || $campaign_channel==='BOTH') { if (!empty($it['phone'])) $targets[]='SMS'; }
                        foreach ($targets as $chan) {
                            $exists=$pdo->prepare("SELECT id FROM campains_queue WHERE user_id=? AND campaign_id=? AND item_id=? AND channel=? AND status='QUEUE'");
                            $exists->execute([$UID,$campaign_id,(int)$it['id'],$chan]);
                            if (!$exists->fetch()) { $ins->execute([$UID,$campaign_id,$catId,(int)$it['id'],$chan]); $count++; }
                        }
                    }
                }
            }
        }
        json_response(['success'=>true, 'count'=>$count]);
    }
    if ($action === 'update_queue_status') {
        $qid=(int)$_POST['id']; $status=$_POST['status'];
        if (in_array($status,['QUEUE','SENT','SKIP','ERROR'])) {
            if ($status==='SENT') $pdo->prepare("UPDATE campains_queue SET status='SENT', sent_at=NOW() WHERE id=? AND user_id=?")->execute([$qid,$UID]);
            else $pdo->prepare("UPDATE campains_queue SET status=?, sent_at=NULL WHERE id=? AND user_id=?")->execute([$status,$qid,$UID]);
            json_response(['success'=>true]);
        }
        json_response(['success'=>false, 'error'=>'Invalid status']);
    }
    if ($action === 'delete_queue') {
        $pdo->prepare("DELETE FROM campains_queue WHERE id=? AND user_id=?")->execute([(int)$_POST['id'],$UID]);
        json_response(['success'=>true]);
    }
    json_response(['success'=>false, 'error'=>'Unknown action']);
}

if (isset($_GET['export'])) {
    $scope = $_GET['scope'] ?? 'items'; $format = $_GET['format'] ?? 'json';
    $map = ['categories'=>"SELECT id,name,description,created_at FROM campains_categories WHERE user_id=$UID ORDER BY id DESC", 'campaigns'=>"SELECT id,title,channel,schedule_time,subject,status,created_at FROM campains_campaigns WHERE user_id=$UID ORDER BY id DESC", 'items'=>"SELECT id,category_id,label,email,phone,memo,created_at FROM campains_category_items WHERE user_id=$UID ORDER BY id DESC", 'queue'=>"SELECT id,campaign_id,category_id,item_id,channel,status,sent_at,created_at FROM campains_queue WHERE user_id=$UID ORDER BY id DESC"];
    $sql = $map[$scope] ?? $map['items']; $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    if ($format==='csv') {
        header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename="'.$scope.'.csv"');
        $out=fopen('php://output','w'); if ($rows) fputcsv($out, array_keys($rows[0])); foreach ($rows as $r) fputcsv($out,$r); fclose($out);
    } else { header('Content-Type: application/json'); echo json_encode($rows, JSON_UNESCAPED_UNICODE); }
    exit;
}
include_once("WEB-INF/menu.php"); 
?>
<link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">
<link rel="stylesheet" href="plugins/toastr/toastr.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
.nav-tabs .nav-link {
  cursor: pointer;
}

.nav-tabs .nav-link.active {
  font-weight: bold;
  background: #000;
  border-bottom-color: #fff;
}

.editable {
  cursor: pointer;
  border-bottom: 1px dashed #999;
  padding: 2px 4px;
  display: inline-block;
  min-width: 60px;
}

.editable:hover {
  background: #000;
  border-radius: 3px;
}

.stats-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 10px;
  padding: 20px;
  margin-bottom: 20px;
  transition: transform .2s;
  box-shadow: 0 4px 6px rgba(0, 0, 0, .1);
}

.stats-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 6px 12px rgba(0, 0, 0, .15);
}

.stats-card h3 {
  margin: 0;
  font-size: 2.5em;
  font-weight: bold;
}

.stats-card p {
  margin: 5px 0 0 0;
  opacity: .95;
  font-size: 1.1em;
}

.form-card {
  background: #000;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, .1);
  margin-bottom: 20px;
}

.form-card h5 {
  margin-bottom: 15px;
  color: #fff;
  font-weight: bold;
}

.tab-pane {
  display: none;
}

.tab-pane.active {
  display: block;
}

.modal-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.modal-header .close {
  color: white;
  opacity: .8;
}

.modal-header .close:hover {
  opacity: 1;
}

.btn-action {
  margin: 2px;
}

table.dataTable tbody tr {
  transition: background-color .2s;
}

.badge-channel {
  font-size: .85em;
  padding: .3em .6em;
}

/* Status styles */
.status-QUEUE {
  background: #ffc107;
  color: #000;
}

.status-SENT {
  background: #28a745;
  color: #fff;
}

.status-SKIP {
  background: #6c757d;
  color: #fff;
}

.status-ERROR {
  background: #dc3545;
  color: #fff;
}

.status-DRAFT {
  background: #6c757d;
  color: #fff;
}

.status-ACTIVE {
  background: #28a745;
  color: #fff;
}

.status-PAUSED {
  background: #ffc107;
  color: #000;
}

.status-DONE {
  background: #17a2b8;
  color: #fff;
}

.dataTables_wrapper .dataTables_filter input {
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 5px 10px;
  margin-left: 10px;
}

.dataTables_wrapper .dataTables_length select {
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 5px;
}
</style>
<div class="content" style="min-height:100vh">

<div class="content"><div class="container-fluid">
<div class="row" id="statsCards">
<div class="col-md-3"><div class="stats-card" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%)"><h3 id="stat-campaigns">0</h3><p><i class="fas fa-bullhorn"></i> Total Campaigns</p></div></div>
<div class="col-md-3"><div class="stats-card" style="background:linear-gradient(135deg,#f093fb 0%,#f5576c 100%)"><h3 id="stat-categories">0</h3><p><i class="fas fa-folder"></i> Categories</p></div></div>
<div class="col-md-3"><div class="stats-card" style="background:linear-gradient(135deg,#4facfe 0%,#00f2fe 100%)"><h3 id="stat-items">0</h3><p><i class="fas fa-users"></i> Total Contacts</p></div></div>
<div class="col-md-3"><div class="stats-card" style="background:linear-gradient(135deg,#43e97b 0%,#38f9d7 100%)"><h3 id="stat-queued">0</h3><p><i class="fas fa-clock"></i> Queued Messages</p></div></div>
</div>
<ul class="nav nav-tabs" role="tablist">
<li class="nav-item"><a class="nav-link active" data-tab="campaigns"><i class="fas fa-bullhorn"></i> Campaigns</a></li>
<li class="nav-item"><a class="nav-link" data-tab="categories"><i class="fas fa-folder"></i> Categories</a></li>
<li class="nav-item"><a class="nav-link" data-tab="items"><i class="fas fa-users"></i> Contacts</a></li>
<li class="nav-item"><a class="nav-link" data-tab="queue"><i class="fas fa-list"></i> Queue</a></li>
<li class="nav-item ml-auto"><div class="btn-group"><button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown"><i class="fas fa-download"></i> Export</button><div class="dropdown-menu dropdown-menu-right">
<a class="dropdown-item" href="?export=1&scope=campaigns&format=json" target="_blank"><i class="fas fa-file-code"></i> Campaigns (JSON)</a>
<a class="dropdown-item" href="?export=1&scope=campaigns&format=csv" target="_blank"><i class="fas fa-file-csv"></i> Campaigns (CSV)</a><div class="dropdown-divider"></div>
<a class="dropdown-item" href="?export=1&scope=categories&format=json" target="_blank"><i class="fas fa-file-code"></i> Categories (JSON)</a>
<a class="dropdown-item" href="?export=1&scope=categories&format=csv" target="_blank"><i class="fas fa-file-csv"></i> Categories (CSV)</a><div class="dropdown-divider"></div>
<a class="dropdown-item" href="?export=1&scope=items&format=json" target="_blank"><i class="fas fa-file-code"></i> Contacts (JSON)</a>
<a class="dropdown-item" href="?export=1&scope=items&format=csv" target="_blank"><i class="fas fa-file-csv"></i> Contacts (CSV)</a><div class="dropdown-divider"></div>
<a class="dropdown-item" href="?export=1&scope=queue&format=json" target="_blank"><i class="fas fa-file-code"></i> Queue (JSON)</a>
<a class="dropdown-item" href="?export=1&scope=queue&format=csv" target="_blank"><i class="fas fa-file-csv"></i> Queue (CSV)</a>
</div></div></li>
</ul>
<div class="tab-content mt-3">
<div class="tab-pane active" id="campaigns-tab">
<div class="form-card"><h5><i class="fas fa-plus-circle"></i> Create New Campaign</h5>
<form id="campaignForm"><div class="row">
<div class="col-md-3"><input class="form-control" name="title" placeholder="Campaign Title" required></div>
<div class="col-md-2"><select class="form-control" name="channel" required><option value="EMAIL">Email</option><option value="SMS">SMS</option><option value="BOTH">Both</option></select></div>
<div class="col-md-3"><input class="form-control" type="date" name="schedule_time" required></div>
<div class="col-md-2"><input class="form-control" name="subject" placeholder="Email Subject"></div>
<div class="col-md-2"><select class="form-control" name="status"><option>DRAFT</option><option>ACTIVE</option><option>PAUSED</option><option>DONE</option></select></div>
</div>
<textarea class="form-control mt-2" name="body_template" rows="3" placeholder="Message template (use {label}, {email}, {phone} as placeholders)"></textarea>
<button class="btn btn-success mt-2" type="submit"><i class="fas fa-save"></i> Create Campaign</button>
</form></div>
<div class="card"><div class="card-body">
<table class="table table-hover table-sm" id="campaignsTable"><thead><tr><th>ID</th><th>Title</th><th>Channel</th><th>Schedule</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead><tbody></tbody></table>
</div></div>
</div>
<div class="tab-pane" id="categories-tab">
<div class="form-card"><h5><i class="fas fa-plus-circle"></i> Create Category</h5>
<form id="categoryForm" class="form-inline">
<input class="form-control mr-2" name="name" placeholder="Category Name" required style="width:250px">
<input class="form-control mr-2" name="description" placeholder="Description" style="width:350px">
<button class="btn btn-success" type="submit"><i class="fas fa-save"></i> Create</button>
</form></div>
<div class="card"><div class="card-body">
<table class="table table-hover table-sm" id="categoriesTable"><thead><tr><th>ID</th><th>Name</th><th>Description</th><th>Items Count</th><th>Created</th><th>Actions</th></tr></thead><tbody></tbody></table>
</div></div>
</div>
<div class="tab-pane" id="items-tab">
<div class="row">
<div class="col-md-6"><div class="form-card"><h5><i class="fas fa-user-plus"></i> Add Contact</h5>
<form id="itemForm"><div class="row">
<div class="col-md-12 mb-2"><select class="form-control" name="category_id" id="itemCategorySelect" required><option value="">Select Category</option></select></div>
<div class="col-md-6"><input class="form-control mb-2" name="label" placeholder="Name/Label"></div>
<div class="col-md-6"><input class="form-control mb-2" name="email" placeholder="Email" type="email"></div>
<div class="col-md-6"><input class="form-control mb-2" name="phone" placeholder="Phone"></div>
<div class="col-md-6"><input class="form-control mb-2" name="memo" placeholder="Notes"></div>
</div>
<button class="btn btn-success btn-block" type="submit"><i class="fas fa-save"></i> Add Contact</button>
</form></div></div>
<div class="col-md-6"><div class="form-card"><h5><i class="fas fa-file-upload"></i> Bulk Import</h5>
<form id="bulkImportForm" enctype="multipart/form-data">
<select class="form-control mb-2" name="category_id" id="bulkCategorySelect" required><option value="">Select Target Category</option></select>
<select class="form-control mb-2" name="format"><option value="json">JSON Format</option><option value="csv">CSV Format</option></select>
<input class="form-control mb-2" type="file" name="file" required>
<small class="text-muted">CSV headers: label, email, phone, memo</small>
<button class="btn btn-primary btn-block mt-2" type="submit"><i class="fas fa-upload"></i> Import Contacts</button>
</form></div></div>
</div>
<div class="card"><div class="card-body">
<table class="table table-hover table-sm" id="itemsTable"><thead><tr><th>ID</th><th>Category</th><th>Name</th><th>Email</th><th>Phone</th><th>Notes</th><th>Actions</th></tr></thead><tbody></tbody></table>
</div></div>
</div>
<div class="tab-pane" id="queue-tab">
<div class="form-card"><h5><i class="fas fa-plus-circle"></i> Build Queue</h5>
<form id="queueForm"><div class="row">
<div class="col-md-4"><label>Select Campaign</label><select class="form-control" name="campaign_id" id="queueCampaignSelect" required><option value="">Choose...</option></select></div>
<div class="col-md-8"><label>Select Categories to Include</label><div id="queueCategoriesCheckboxes" style="max-height:200px;overflow:auto;border:1px solid #ddd;border-radius:4px;padding:10px;background:#000"></div></div>
</div>
<button class="btn btn-success mt-3" type="submit"><i class="fas fa-list-ul"></i> Add to Queue</button>
</form></div>
<div class="card"><div class="card-header"><h5 class="mb-0"><i class="fas fa-list"></i> Message Queue</h5></div>
<div class="card-body">
<table class="table table-hover table-sm" id="queueTable"><thead><tr><th>ID</th><th>Campaign</th><th>Category</th><th>Contact</th><th>Channel</th><th>Status</th><th>Sent At</th><th>Actions</th></tr></thead><tbody></tbody></table>
</div>
<div class="card-footer"><small class="text-muted"><i class="fas fa-info-circle"></i> API endpoints: /api/next-email.php, /api/next-sms.php, /api/next.php?channel=EMAIL|SMS</small></div>
</div>
</div>
</div></div></div></div>

<!-- Edit Campaign Modal -->
<div class="modal fade" id="editCampaignModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Campaign</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form id="editCampaignForm">
        <div class="modal-body">
          <input type="hidden" name="id" id="edit_campaign_id">
          
          <div class="form-group">
            <label>Campaign Title</label>
            <input class="form-control" name="title" id="edit_title" required>
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Channel</label>
                <select class="form-control" name="channel" id="edit_channel" required>
                  <option value="EMAIL">Email</option>
                  <option value="SMS">SMS</option>
                  <option value="BOTH">Both</option>
                </select>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="form-group">
                <label>Status</label>
                <select class="form-control" name="status" id="edit_status">
                  <option value="DRAFT">DRAFT</option>
                  <option value="ACTIVE">ACTIVE</option>
                  <option value="PAUSED">PAUSED</option>
                  <option value="DONE">DONE</option>
                </select>
              </div>
            </div>
          </div>
          
          <div class="form-group">
            <label>Schedule Date</label>
            <input class="form-control" type="text" name="schedule_time" id="edit_schedule_time" required>
          </div>
          
          <div class="form-group">
            <label>Email Subject</label>
            <input class="form-control" name="subject" id="edit_subject" placeholder="Email Subject">
          </div>
          
          <div class="form-group">
            <label>Message Template</label>
            <textarea class="form-control" name="body_template" id="edit_body_template" rows="5" placeholder="Message template (use {label}, {email}, {phone} as placeholders)"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Campaign</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Action</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
<div class="modal-body"><p id="confirmMessage">Are you sure you want to perform this action?</p></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Cancel</button><button type="button" class="btn btn-danger" id="confirmButton"><i class="fas fa-check"></i> Confirm</button></div>
</div></div></div>
<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="plugins/toastr/toastr.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>
<script>
$(document).ready(function(){let currentTab='campaigns',campaignsTable,categoriesTable,itemsTable,queueTable,confirmCallback=null;toastr.options={closeButton:!0,progressBar:!0,positionClass:"toast-top-right",timeOut:"3000"};function notify(m,t='success'){toastr[t](m)}function ajaxPost(d,s,e){d.ajax='1';$.ajax({url:window.location.pathname,method:'POST',data:d,dataType:'json',success:function(r){r.success?s&&s(r):(notify(r.error||'Operation failed','error'),e&&e(r))},error:function(){notify('Network error','error');e&&e()}})}function ajaxGet(a,s,e){$.ajax({url:window.location.pathname+'?ajax=1&action='+a,method:'GET',dataType:'json',success:function(r){r.success?s&&s(r):(notify(r.error||'Failed to load data','error'),e&&e(r))},error:function(){notify('Network error','error');e&&e()}})}function showConfirmModal(m,c){$('#confirmMessage').text(m);confirmCallback=c;$('#confirmModal').modal('show')}$('#confirmButton').click(function(){$('#confirmModal').modal('hide');if(confirmCallback){confirmCallback();confirmCallback=null}});function loadStats(){ajaxGet('get_stats',function(r){$('#stat-campaigns').text(r.data.campaigns);$('#stat-categories').text(r.data.categories);$('#stat-items').text(r.data.items);$('#stat-queued').text(r.data.queued)})}$('.nav-link[data-tab]').click(function(e){e.preventDefault();switchTab($(this).data('tab'))});function switchTab(t){currentTab=t;$('.nav-link').removeClass('active');$('.nav-link[data-tab="'+t+'"]').addClass('active');$('.tab-pane').removeClass('active');$('#'+t+'-tab').addClass('active');if(t==='campaigns')loadCampaigns();else if(t==='categories')loadCategories();else if(t==='items')loadItems();else if(t==='queue')loadQueue()}function loadCampaigns(){ajaxGet('get_campaigns',function(r){if(campaignsTable){campaignsTable.clear().destroy()}const tb=$('#campaignsTable tbody');tb.empty();r.data.forEach(function(d){const sc='status-'+d.status;const tr=$('<tr>').attr('data-id',d.id);tr.append($('<td>').text(d.id));tr.append($('<td>').text(d.title));tr.append($('<td>').html('<span class="badge badge-primary badge-channel">'+d.channel+'</span>'));tr.append($('<td>').text(d.schedule_time));tr.append($('<td>').html('<span class="badge '+sc+'">'+d.status+'</span>'));tr.append($('<td>').html('<small>'+escapeHtml(d.created_at)+'</small>'));tr.append($('<td>').html('<button class="btn btn-info btn-sm btn-action btn-edit-campaign" title="Edit"><i class="fas fa-edit"></i></button> <button class="btn btn-danger btn-sm btn-delete" data-type="campaign"><i class="fas fa-trash"></i></button>'));tb.append(tr)});campaignsTable=$('#campaignsTable').DataTable({responsive:!0,pageLength:25,order:[[0,'desc']],language:{search:"_INPUT_",searchPlaceholder:"Search campaigns..."}})})}$('#campaignForm').submit(function(e){e.preventDefault();const fd=$(this).serializeArray().reduce((o,i)=>{o[i.name]=i.value;return o},{});fd.action='add_campaign';ajaxPost(fd,function(){notify('Campaign created successfully');loadCampaigns();loadStats();$('#campaignForm')[0].reset()})});

// Edit Campaign Modal
$(document).on('click', '.btn-edit-campaign', function(){
  const id = $(this).closest('tr').data('id');
  $.ajax({
    url: window.location.pathname + '?ajax=1&action=get_campaign&id=' + id,
    method: 'GET',
    dataType: 'json',
    success: function(r){
      if(r.success && r.data){
        $('#edit_campaign_id').val(r.data.id);
        $('#edit_title').val(r.data.title);
        $('#edit_channel').val(r.data.channel);
        $('#edit_status').val(r.data.status);
        $('#edit_schedule_time').val(r.data.schedule_time);
        $('#edit_subject').val(r.data.subject);
        $('#edit_body_template').val(r.data.body_template);
        $('#editCampaignModal').modal('show');
      }
    }
  });
});

$('#editCampaignForm').submit(function(e){
  e.preventDefault();
  const fd = $(this).serializeArray().reduce((o,i)=>{o[i.name]=i.value;return o},{});
  fd.action = 'update_campaign';
  ajaxPost(fd, function(){
    notify('Campaign updated successfully');
    $('#editCampaignModal').modal('hide');
    loadCampaigns();
  });
});

function loadCategories(){ajaxGet('get_categories',function(r){if(categoriesTable){categoriesTable.clear().destroy()}const tb=$('#categoriesTable tbody');tb.empty();r.data.forEach(function(d){const tr=$('<tr>').attr('data-id',d.id);tr.append($('<td>').text(d.id));tr.append($('<td>').html('<span class="editable" data-field="name">'+escapeHtml(d.name)+'</span>'));tr.append($('<td>').html('<span class="editable" data-field="description">'+escapeHtml(d.description)+'</span>'));tr.append($('<td>').html('<span class="badge badge-info">'+d.item_count+' contacts</span>'));tr.append($('<td>').html('<small>'+escapeHtml(d.created_at)+'</small>'));tr.append($('<td>').html('<button class="btn btn-danger btn-sm btn-delete" data-type="category"><i class="fas fa-trash"></i></button>'));tb.append(tr)});categoriesTable=$('#categoriesTable').DataTable({responsive:!0,pageLength:25,order:[[0,'desc']],language:{search:"_INPUT_",searchPlaceholder:"Search categories..."}});loadCategorySelects()})}$('#categoryForm').submit(function(e){e.preventDefault();const fd=$(this).serializeArray().reduce((o,i)=>{o[i.name]=i.value;return o},{});fd.action='add_category';ajaxPost(fd,function(){notify('Category created successfully');loadCategories();loadStats();$('#categoryForm')[0].reset()})});function loadItems(){ajaxGet('get_items',function(r){if(itemsTable){itemsTable.clear().destroy()}const tb=$('#itemsTable tbody');tb.empty();r.data.forEach(function(d){const tr=$('<tr>').attr('data-id',d.id);tr.append($('<td>').text(d.id));tr.append($('<td>').html('<span class="badge badge-secondary">'+escapeHtml(d.cat_name)+'</span>'));tr.append($('<td>').html('<span class="editable" data-field="label">'+escapeHtml(d.label)+'</span>'));tr.append($('<td>').html('<span class="editable" data-field="email">'+escapeHtml(d.email)+'</span>'));tr.append($('<td>').html('<span class="editable" data-field="phone">'+escapeHtml(d.phone)+'</span>'));tr.append($('<td>').html('<span class="editable" data-field="memo">'+escapeHtml(d.memo)+'</span>'));tr.append($('<td>').html('<button class="btn btn-danger btn-sm btn-delete" data-type="item"><i class="fas fa-trash"></i></button>'));tb.append(tr)});itemsTable=$('#itemsTable').DataTable({responsive:!0,pageLength:50,order:[[0,'desc']],language:{search:"_INPUT_",searchPlaceholder:"Search contacts..."}})})}$('#itemForm').submit(function(e){e.preventDefault();const fd=$(this).serializeArray().reduce((o,i)=>{o[i.name]=i.value;return o},{});fd.action='add_item';ajaxPost(fd,function(){notify('Contact added successfully');loadItems();loadStats();$('#itemForm')[0].reset()})});$('#bulkImportForm').submit(function(e){e.preventDefault();const fd=new FormData(this);fd.append('ajax','1');fd.append('action','bulk_import_items');$.ajax({url:window.location.pathname,method:'POST',data:fd,processData:!1,contentType:!1,dataType:'json',success:function(r){if(r.success){notify('Successfully imported '+r.count+' contacts');loadItems();loadStats();$('#bulkImportForm')[0].reset()}else{notify(r.error||'Import failed','error')}},error:function(){notify('Network error during import','error')}})});function loadQueue(){ajaxGet('get_queue',function(r){if(queueTable){queueTable.clear().destroy()}const tb=$('#queueTable tbody');tb.empty();r.data.forEach(function(d){const sc='status-'+d.status;const ci=d.channel==='EMAIL'?d.email:d.phone;const tr=$('<tr>').attr('data-id',d.id);tr.append($('<td>').text(d.id));tr.append($('<td>').text(d.title));tr.append($('<td>').html('<span class="badge badge-secondary">'+escapeHtml(d.cat_name)+'</span>'));tr.append($('<td>').html('<strong>'+escapeHtml(d.label)+'</strong><br><small class="text-muted">'+escapeHtml(ci)+'</small>'));tr.append($('<td>').html('<span class="badge badge-info badge-channel">'+d.channel+'</span>'));tr.append($('<td>').html('<span class="badge '+sc+'">'+d.status+'</span>'));tr.append($('<td>').html('<small>'+escapeHtml(d.sent_at)+'</small>'));tr.append($('<td>').html('<button class="btn btn-outline-warning btn-xs btn-action btn-queue-status" data-status="QUEUE" title="Reset to Queue"><i class="fas fa-undo"></i></button>'+'<button class="btn btn-outline-success btn-xs btn-action btn-queue-status" data-status="SENT" title="Mark as Sent"><i class="fas fa-check"></i></button>'+'<button class="btn btn-outline-secondary btn-xs btn-action btn-queue-status" data-status="SKIP" title="Skip"><i class="fas fa-forward"></i></button>'+'<button class="btn btn-danger btn-xs btn-action btn-delete" data-type="queue" title="Delete"><i class="fas fa-trash"></i></button>'));tb.append(tr)});queueTable=$('#queueTable').DataTable({responsive:!0,pageLength:50,order:[[0,'desc']],language:{search:"_INPUT_",searchPlaceholder:"Search queue..."}})});loadCampaignSelects()}$('#queueForm').submit(function(e){e.preventDefault();const cids=[];$('#queueCategoriesCheckboxes input:checked').each(function(){cids.push($(this).val())});if(cids.length===0){notify('Please select at least one category','warning');return}const fd={action:'enqueue',campaign_id:$('#queueCampaignSelect').val(),'category_ids[]':cids};ajaxPost(fd,function(r){notify('Successfully queued '+r.count+' messages');loadQueue();loadStats();$('#queueForm')[0].reset();$('#queueCategoriesCheckboxes input').prop('checked',!1)})});function loadCategorySelects(){ajaxGet('get_categories_list',function(r){const sel=$('#itemCategorySelect, #bulkCategorySelect');sel.find('option:not(:first)').remove();r.data.forEach(function(c){sel.append($('<option>').val(c.id).text(c.name))});const cb=$('#queueCategoriesCheckboxes');cb.empty();r.data.forEach(function(c){const l=$('<label>').addClass('d-block mb-1');const ch=$('<input>').attr({type:'checkbox',name:'category_ids[]',value:c.id});l.append(ch).append(' <strong>'+escapeHtml(c.name)+'</strong>');cb.append(l)})})}function loadCampaignSelects(){ajaxGet('get_campaigns_list',function(r){const sel=$('#queueCampaignSelect');sel.find('option:not(:first)').remove();r.data.forEach(function(c){sel.append($('<option>').val(c.id).text(c.title+' ['+c.channel+']'))})})}$(document).on('click','.editable',function(){const $t=$(this);const f=$t.data('field');const cv=$t.text();const $r=$t.closest('tr');const id=$r.data('id');let type='campaign';if(currentTab==='categories')type='category';else if(currentTab==='items')type='item';const $i=$('<input>').addClass('form-control form-control-sm').val(cv).css({width:'100%',display:'inline-block'}).blur(function(){const nv=$(this).val();if(nv!==cv&&nv.trim()!==''){ajaxPost({action:'update_'+type,id:id,field:f,value:nv},function(){$t.text(nv);notify('Updated successfully')},function(){$t.text(cv)})}else{$t.text(cv)}}).keypress(function(e){if(e.which===13){$(this).blur()}}).keyup(function(e){if(e.which===27){$t.text(cv)}});$t.html($i);$i.focus().select()});$(document).on('click','.btn-delete',function(){const type=$(this).data('type');const id=$(this).closest('tr').data('id');showConfirmModal('Are you sure you want to delete this '+type+'?',function(){ajaxPost({action:'delete_'+type,id:id},function(){notify('Deleted successfully');if(type==='campaign')loadCampaigns();else if(type==='category')loadCategories();else if(type==='item')loadItems();else if(type==='queue')loadQueue();loadStats()})})});$(document).on('click','.btn-queue-status',function(){const s=$(this).data('status');const id=$(this).closest('tr').data('id');ajaxPost({action:'update_queue_status',id:id,status:s},function(){notify('Status updated to '+s);loadQueue();loadStats()})});function escapeHtml(t){if(!t)return '';const m={'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'};return t.toString().replace(/[&<>"']/g,function(c){return m[c]})}loadStats();loadCampaigns();loadCategorySelects()});
</script>
<?php include_once("WEB-INF/footer.php"); ?>