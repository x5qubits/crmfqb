<?php
// API handlers for campaigns system
$action = $_POST['action'] ?? $_GET['action'] ?? '';

function normalizePhone($phone) {
    if (!$phone) return null;
    $phone = trim((string)$phone);

    // cleanup unwanted chars
    $phone = preg_replace('/\s+/', '', $phone);

        if (strpos($phone, '+4') !== 0) {
            $phone = '+4' . ltrim($phone, '+');
        }

    return $phone;
}

// CAMPAIGNS
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

// CATEGORIES
if ($action === 'get_categories') {
    $rows = $pdo->query("SELECT c.*, COUNT(i.id) as item_count FROM campains_categories c LEFT JOIN campains_category_items i ON i.category_id=c.id AND i.user_id=c.user_id WHERE c.user_id=".$UID." GROUP BY c.id ORDER BY c.id DESC")->fetchAll(PDO::FETCH_ASSOC);
    json_response(['success'=>true, 'data'=>$rows]);
}

if ($action === 'get_category') {
    $id = (int)$_GET['id'];
    $row = $pdo->prepare("SELECT * FROM campains_categories WHERE id=? AND user_id=?");
    $row->execute([$id, $UID]);
    json_response(['success'=>true, 'data'=>$row->fetch(PDO::FETCH_ASSOC)]);
}

if ($action === 'add_category') {
    $pdo->prepare("INSERT INTO campains_categories (user_id,name,description) VALUES (?,?,?)")->execute([$UID, $_POST['name'], $_POST['description']??null]);
    json_response(['success'=>true, 'id'=>$pdo->lastInsertId()]);
}

if ($action === 'update_category_full') {
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare("UPDATE campains_categories SET name=?, description=? WHERE id=? AND user_id=?");
    $stmt->execute([$_POST['name'], $_POST['description']??null, $id, $UID]);
    json_response(['success'=>true]);
}

if ($action === 'delete_category') {
    $pdo->prepare("DELETE FROM campains_categories WHERE id=? AND user_id=?")->execute([(int)$_POST['id'],$UID]);
    json_response(['success'=>true]);
}

// ITEMS/CONTACTS - Server-side DataTables
if ($action === 'get_items_datatable') {
    $draw = (int)($_GET['draw'] ?? 1);
    $start = (int)($_GET['start'] ?? 0);
    $length = (int)($_GET['length'] ?? 50);
    $searchValue = $_GET['search']['value'] ?? '';
    $orderColumn = (int)($_GET['order'][0]['column'] ?? 0);
    $orderDir = $_GET['order'][0]['dir'] ?? 'desc';
    
    $columns = ['i.id', 'c.name', 'i.label', 'i.email', 'i.phone', 'i.memo'];
    $orderBy = $columns[$orderColumn] ?? 'i.id';
    
    $sql = "SELECT i.*, c.name AS cat_name FROM campains_category_items i JOIN campains_categories c ON c.id=i.category_id WHERE i.user_id=".$UID;
    
    if (!empty($_GET['category_id'])) {
        $sql .= " AND i.category_id = " . (int)$_GET['category_id'];
    }
    
    if (!empty($searchValue)) {
        $search = $pdo->quote('%' . $searchValue . '%');
        $sql .= " AND (i.label LIKE $search OR i.email LIKE $search OR i.phone LIKE $search)";
    }
    
    $totalFiltered = $pdo->query(str_replace('SELECT i.*, c.name AS cat_name', 'SELECT COUNT(*)', $sql))->fetchColumn();
    $sql .= " ORDER BY $orderBy $orderDir LIMIT $start, $length";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $totalRecords = $pdo->query("SELECT COUNT(*) FROM campains_category_items WHERE user_id=".$UID)->fetchColumn();
    
    json_response(['draw' => $draw, 'recordsTotal' => $totalRecords, 'recordsFiltered' => $totalFiltered, 'data' => $rows]);
}

if ($action === 'get_item') {
    $id = (int)$_GET['id'];
    $row = $pdo->prepare("SELECT * FROM campains_category_items WHERE id=? AND user_id=?");
    $row->execute([$id, $UID]);
    json_response(['success'=>true, 'data'=>$row->fetch(PDO::FETCH_ASSOC)]);
}

if ($action === 'add_item') {
    $pdo->prepare("INSERT INTO campains_category_items 
        (user_id,category_id,label,email,phone,memo,birthdate) 
        VALUES (?,?,?,?,?,?,?)")
    ->execute([
        $UID,
        (int)$_POST['category_id'],
        $_POST['label'] ?? null,
        $_POST['email'] ?? null,
        normalizePhone($_POST['phone'] ?? null),
        $_POST['memo'] ?? null,
        $_POST['birthdate'] ?? null
    ]);
    json_response(['success'=>true, 'id'=>$pdo->lastInsertId()]);
}

if ($action === 'update_item_full') {
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare("UPDATE campains_category_items 
        SET category_id=?, label=?, email=?, phone=?, birthdate=?, memo=? 
        WHERE id=? AND user_id=?");
    $stmt->execute([
        (int)$_POST['category_id'],
        $_POST['label'] ?? null,
        $_POST['email'] ?? null,
        normalizePhone($_POST['phone'] ?? null),
        $_POST['birthdate'] ?? null,
        $_POST['memo'] ?? null,
        $id,
        $UID
    ]);
    json_response(['success'=>true, "test"=> normalizePhone($_POST['phone'] ?? null)]);
}

if ($action === 'delete_item') {
    $pdo->prepare("DELETE FROM campains_category_items WHERE id=? AND user_id=?")->execute([(int)$_POST['id'],$UID]);
    json_response(['success'=>true]);
}

if ($action === 'bulk_delete_by_category') {
    $cat_id = (int)$_POST['category_id'];
    if ($cat_id) {
        $stmt = $pdo->prepare("DELETE FROM campains_category_items WHERE category_id=? AND user_id=?");
        $stmt->execute([$cat_id, $UID]);
        json_response(['success'=>true, 'count'=>$stmt->rowCount()]);
    }
    json_response(['success'=>false, 'error'=>'Invalid category']);
}

// CSV IMPORT
if ($action === 'get_csv_preview') {
    $tmp = $_FILES['file']['tmp_name'] ?? '';
    if ($tmp) {
        $csv = array_map('str_getcsv', file($tmp));
        if ($csv && count($csv) > 0) {
            $headers = array_map('trim', $csv[0]);
            $sample = array_slice($csv, 1, 5);
            json_response(['success'=>true, 'headers'=>$headers, 'sample'=>$sample]);
        }
    }
    json_response(['success'=>false, 'error'=>'Invalid file']);
}

if ($action === 'bulk_import_items') {
    $cat = (int)$_POST['category_id']; 
    $fmt = $_POST['format'] ?? 'json'; 
    $tmp = $_FILES['file']['tmp_name'] ?? '';
    
    if ($cat && $tmp) {
        $rows=[];
        if ($fmt==='json') { 
            $rows = json_decode(file_get_contents($tmp), true) ?: []; 
        } else {
            $mapping = json_decode($_POST['field_mapping'] ?? '{}', true);
            $csv = array_map('str_getcsv', file($tmp));
            if ($csv && count($csv)>1) {
                $headers = array_map('trim', array_shift($csv));
                foreach ($csv as $r) { 
                    if (!count($r)) continue;
                    $combined = array_combine($headers, $r);
                    if (!empty($mapping)) {
                        $mapped = [];
                        foreach ($mapping as $csvField => $dbField) {
                            $mapped[$dbField] = $combined[$csvField] ?? null;
                        }
                        $rows[] = $mapped;
                    } else {
                        $rows[] = $combined;
                    }
                }
            }
        }
        $ins=$pdo->prepare("INSERT INTO campains_category_items (user_id,category_id,label,email,phone,memo) VALUES (?,?,?,?,?,?)");
        foreach ($rows as $r) { 
            $ins->execute([$UID,$cat,$r['label']??null,$r['email']??null,$r['phone']??null,$r['memo']??null]); 
        }
        json_response(['success'=>true, 'count'=>count($rows)]);
    }
    json_response(['success'=>false, 'error'=>'Invalid data']);
}

// QUEUE
if ($action === 'get_queue') {
    $rows = $pdo->query("SELECT q.*, cp.title, cat.name AS cat_name, it.label, it.email, it.phone FROM campains_queue q JOIN campains_campaigns cp ON cp.id=q.campaign_id JOIN campains_category_items it ON it.id=q.item_id LEFT JOIN campains_categories cat ON cat.id=q.category_id WHERE q.user_id=".$UID." ORDER BY q.id DESC")->fetchAll(PDO::FETCH_ASSOC);
    json_response(['success'=>true, 'data'=>$rows]);
}

if ($action === 'enqueue') {
    $campaign_id = (int)($_POST['campaign_id'] ?? 0); 
    $category_ids = $_POST['category_ids'] ?? []; 
    $count = 0;
    if ($campaign_id && is_array($category_ids) && count($category_ids)) {
        $ch=$pdo->prepare("SELECT channel FROM campains_campaigns WHERE id=? AND user_id=?");
        $ch->execute([$campaign_id,$UID]); 
        $row=$ch->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $campaign_channel = $row['channel'];
            $ins=$pdo->prepare("INSERT INTO campains_queue (user_id,campaign_id,category_id,item_id,channel,status) VALUES (?,?,?,?,?, 'QUEUE')");
            foreach ($category_ids as $catId) {
                $catId=(int)$catId;
                $items=$pdo->prepare("SELECT id,email,phone FROM campains_category_items WHERE category_id=? AND user_id=?");
                $items->execute([$catId,$UID]);
                while ($it=$items->fetch(PDO::FETCH_ASSOC)) {
                    $targets=[];
                    if ($campaign_channel==='EMAIL' || $campaign_channel==='BOTH') { 
                        if (!empty($it['email'])) $targets[]='EMAIL'; 
                    }
                    if ($campaign_channel==='SMS' || $campaign_channel==='BOTH') { 
                        if (!empty($it['phone'])) $targets[]='SMS'; 
                    }
                    foreach ($targets as $chan) {
                        $exists=$pdo->prepare("SELECT id FROM campains_queue WHERE user_id=? AND campaign_id=? AND item_id=? AND channel=? AND status='QUEUE'");
                        $exists->execute([$UID,$campaign_id,(int)$it['id'],$chan]);
                        if (!$exists->fetch()) { 
                            $ins->execute([$UID,$campaign_id,$catId,(int)$it['id'],$chan]); 
                            $count++; 
                        }
                    }
                }
            }
        }
    }
    json_response(['success'=>true, 'count'=>$count]);
}

if ($action === 'send_custom_message') {
    $contact_id = (int)($_POST['contact_id'] ?? 0);
    $channel = $_POST['channel'] ?? 'SMS';
    $message = $_POST['message'] ?? '';
    
    if ($contact_id && $message) {
        $contact = $pdo->prepare("SELECT * FROM campains_category_items WHERE id=? AND user_id=?");
        $contact->execute([$contact_id, $UID]);
        $c = $contact->fetch(PDO::FETCH_ASSOC);
        
        if ($c) {
            $temp_campaign = $pdo->prepare("INSERT INTO campains_campaigns (user_id,title,channel,schedule_time,body_template,status) VALUES (?,?,?,NOW(),?,'ACTIVE')");
            $temp_campaign->execute([$UID, 'Custom Message - ' . date('Y-m-d H:i:s'), $channel, $message]);
            $campaign_id = $pdo->lastInsertId();
            
            $ins = $pdo->prepare("INSERT INTO campains_queue (user_id,campaign_id,category_id,item_id,channel,status) VALUES (?,?,?,?,?,'QUEUE')");
            $ins->execute([$UID, $campaign_id, $c['category_id'], $contact_id, $channel]);
            
            json_response(['success'=>true, 'message'=>'Message queued successfully']);
        }
    }
    json_response(['success'=>false, 'error'=>'Invalid data']);
}

if ($action === 'update_queue_status') {
    $qid=(int)$_POST['id']; 
    $status=$_POST['status'];
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

if ($action === 'empty_queue') {
    $deleted = $pdo->prepare("DELETE FROM campains_queue WHERE user_id=? AND status='QUEUE'");
    $deleted->execute([$UID]);
    json_response(['success'=>true, 'count'=>$deleted->rowCount()]);
}

// LISTS
if ($action === 'get_stats') {
    $stats = [
        'campaigns' => $pdo->query("SELECT COUNT(*) FROM campains_campaigns WHERE user_id=".$UID)->fetchColumn(), 
        'categories' => $pdo->query("SELECT COUNT(*) FROM campains_categories WHERE user_id=".$UID)->fetchColumn(), 
        'items' => $pdo->query("SELECT COUNT(*) FROM campains_category_items WHERE user_id=".$UID)->fetchColumn(), 
        'queued' => $pdo->query("SELECT COUNT(*) FROM campains_queue WHERE user_id=".$UID." AND status='QUEUE'")->fetchColumn()
    ];
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

// REMINDERS
if ($action === 'get_reminders') {
    $rows = $pdo->query("SELECT * FROM campains_reminders WHERE user_id=".$UID." ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    json_response(['success'=>true, 'data'=>$rows]);
}

if ($action === 'get_reminder') {
    $id = (int)$_GET['id'];
    $row = $pdo->prepare("SELECT * FROM campains_reminders WHERE id=? AND user_id=?");
    $row->execute([$id, $UID]);
    json_response(['success'=>true, 'data'=>$row->fetch(PDO::FETCH_ASSOC)]);
}

if ($action === 'add_reminder') {
    $stmt=$pdo->prepare("INSERT INTO campains_reminders (user_id,title,reminder_type,days_before,channel,message_template,active) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$UID, $_POST['title'], $_POST['reminder_type'], (int)$_POST['days_before'], $_POST['channel'], $_POST['message_template'], isset($_POST['active']) ? 1 : 0]);
    json_response(['success'=>true, 'id'=>$pdo->lastInsertId()]);
}

if ($action === 'update_reminder') {
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare("UPDATE campains_reminders SET title=?, reminder_type=?, days_before=?, channel=?, message_template=?, active=? WHERE id=? AND user_id=?");
    $stmt->execute([$_POST['title'], $_POST['reminder_type'], (int)$_POST['days_before'], $_POST['channel'], $_POST['message_template'], isset($_POST['active']) ? 1 : 0, $id, $UID]);
    json_response(['success'=>true]);
}

if ($action === 'delete_reminder') {
    $pdo->prepare("DELETE FROM campains_reminders WHERE id=? AND user_id=?")->execute([(int)$_POST['id'],$UID]);
    json_response(['success'=>true]);
}

if ($action === 'toggle_reminder') {
    $id = (int)$_POST['id'];
    $pdo->prepare("UPDATE campains_reminders SET active = NOT active WHERE id=? AND user_id=?")->execute([$id, $UID]);
    json_response(['success'=>true]);
}

if ($action === 'get_upcoming_birthdays') {
    // Returnează contactele care au ziua de naștere în următoarele 30 de zile
    $sql = "SELECT id, label, email, phone, birthdate, 
            DATEDIFF(
                DATE_ADD(
                    birthdate, 
                    INTERVAL (YEAR(CURDATE()) - YEAR(birthdate) + IF(DAYOFYEAR(CURDATE()) > DAYOFYEAR(birthdate), 1, 0)) YEAR
                ), 
                CURDATE()
            ) as days_until
            FROM campains_category_items 
            WHERE user_id=? AND birthdate IS NOT NULL
            HAVING days_until >= 0 AND days_until <= 30
            ORDER BY days_until ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$UID]);
    json_response(['success'=>true, 'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

if ($action === 'process_reminders') {
    // Procesează reminder-urile automate (apelat de un cron job sau manual)
    $reminders = $pdo->query("SELECT * FROM campains_reminders WHERE user_id=".$UID." AND active=1")->fetchAll(PDO::FETCH_ASSOC);
    $processed = 0;
    
    foreach ($reminders as $reminder) {
        $contacts = [];
        
        if ($reminder['reminder_type'] === 'BIRTHDAY') {
            // Găsește contactele cu ziua de naștere în următoarele X zile
            $sql = "SELECT * FROM campains_category_items 
                    WHERE user_id=? AND birthdate IS NOT NULL
                    AND DATEDIFF(
                        DATE_ADD(birthdate, INTERVAL (YEAR(CURDATE()) - YEAR(birthdate) + IF(DAYOFYEAR(CURDATE()) > DAYOFYEAR(birthdate), 1, 0)) YEAR), 
                        CURDATE()
                    ) = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$UID, $reminder['days_before']]);
            $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Adaugă în coadă pentru fiecare contact
        foreach ($contacts as $contact) {
            // Verifică dacă nu a fost deja trimis
            $check = $pdo->prepare("SELECT id FROM campains_reminders_log WHERE reminder_id=? AND contact_id=? AND sent_date=CURDATE()");
            $check->execute([$reminder['id'], $contact['id']]);
            if (!$check->fetch()) {
                // Creează campanie temporară
                $temp_campaign = $pdo->prepare("INSERT INTO campains_campaigns (user_id,title,channel,schedule_time,body_template,status) VALUES (?,?,?,NOW(),?,'ACTIVE')");
                $message = str_replace(['{label}', '{email}', '{phone}'], [$contact['label'], $contact['email'], $contact['phone']], $reminder['message_template']);
                $temp_campaign->execute([$UID, $reminder['title'], $reminder['channel'], $message]);
                $campaign_id = $pdo->lastInsertId();
                
                // Adaugă în coadă
                if ($reminder['channel'] === 'EMAIL' || $reminder['channel'] === 'BOTH') {
                    if (!empty($contact['email'])) {
                        $pdo->prepare("INSERT INTO campains_queue (user_id,campaign_id,category_id,item_id,channel,status) VALUES (?,?,?,?,'EMAIL','QUEUE')")->execute([$UID, $campaign_id, $contact['category_id'], $contact['id']]);
                    }
                }
                if ($reminder['channel'] === 'SMS' || $reminder['channel'] === 'BOTH') {
                    if (!empty($contact['phone'])) {
                        $pdo->prepare("INSERT INTO campains_queue (user_id,campaign_id,category_id,item_id,channel,status) VALUES (?,?,?,?,'SMS','QUEUE')")->execute([$UID, $campaign_id, $contact['category_id'], $contact['id']]);
                    }
                }
                
                // Log
                $pdo->prepare("INSERT INTO campains_reminders_log (user_id,reminder_id,contact_id,sent_date,channel,status) VALUES (?,?,?,CURDATE(),?,'PENDING')")->execute([$UID, $reminder['id'], $contact['id'], $reminder['channel']]);
                $processed++;
            }
        }
    }
    
    json_response(['success'=>true, 'processed'=>$processed]);
}

json_response(['success'=>false, 'error'=>'Unknown action']);