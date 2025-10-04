<?php
/**
 * Cron Job pentru Procesarea Automată a Reminder-urilor
 * Rulează zilnic la 8:00 AM pentru a verifica și trimite reminder-urile
 * 
 * Adaugă în crontab:
 * 0 8 * * * /usr/bin/php /path/to/cron_process_reminders.php
 */

// Include configurația
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting reminder processing...\n";

try {
    // Obține toți utilizatorii activi
    $users = $pdo->query("SELECT DISTINCT user_id FROM campains_reminders WHERE active=1")->fetchAll(PDO::FETCH_COLUMN);
    
    $totalProcessed = 0;
    
    foreach ($users as $UID) {
        echo "Processing reminders for user ID: $UID\n";
        
        // Obține reminder-urile active pentru utilizator
        $reminders = $pdo->prepare("SELECT * FROM campains_reminders WHERE user_id=? AND active=1");
        $reminders->execute([$UID]);
        
        while ($reminder = $reminders->fetch(PDO::FETCH_ASSOC)) {
            echo "  - Processing reminder: {$reminder['title']} (Type: {$reminder['reminder_type']})\n";
            
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
            elseif ($reminder['reminder_type'] === 'CUSTOM_DATE_1') {
                // Găsește contactele cu custom_date_1 în următoarele X zile
                $sql = "SELECT * FROM campains_category_items 
                        WHERE user_id=? AND custom_date_1 IS NOT NULL
                        AND DATEDIFF(custom_date_1, CURDATE()) = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$UID, $reminder['days_before']]);
                $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            elseif ($reminder['reminder_type'] === 'CUSTOM_DATE_2') {
                // Găsește contactele cu custom_date_2 în următoarele X zile
                $sql = "SELECT * FROM campains_category_items 
                        WHERE user_id=? AND custom_date_2 IS NOT NULL
                        AND DATEDIFF(custom_date_2, CURDATE()) = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$UID, $reminder['days_before']]);
                $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            echo "    Found " . count($contacts) . " contacts to process\n";
            
            // Procesează fiecare contact
            foreach ($contacts as $contact) {
                // Verifică dacă nu a fost deja trimis astăzi
                $check = $pdo->prepare("SELECT id FROM campains_reminders_log WHERE reminder_id=? AND contact_id=? AND sent_date=CURDATE()");
                $check->execute([$reminder['id'], $contact['id']]);
                
                if (!$check->fetch()) {
                    // Creează campanie temporară pentru acest reminder
                    $temp_campaign = $pdo->prepare("INSERT INTO campains_campaigns (user_id,title,channel,schedule_time,body_template,status) VALUES (?,?,?,NOW(),?,'ACTIVE')");
                    
                    // Înlocuiește variabilele în șablonul de mesaj
                    $message = str_replace(
                        ['{label}', '{email}', '{phone}'],
                        [$contact['label'], $contact['email'], $contact['phone']],
                        $reminder['message_template']
                    );
                    
                    $temp_campaign->execute([$UID, $reminder['title'], $reminder['channel'], $message]);
                    $campaign_id = $pdo->lastInsertId();
                    
                    // Adaugă în coadă bazat pe canal
                    if (($reminder['channel'] === 'EMAIL' || $reminder['channel'] === 'BOTH') && !empty($contact['email'])) {
                        $pdo->prepare("INSERT INTO campains_queue (user_id,campaign_id,category_id,item_id,channel,status) VALUES (?,?,?,?,'EMAIL','QUEUE')")
                            ->execute([$UID, $campaign_id, $contact['category_id'], $contact['id']]);
                        echo "      Added EMAIL to queue for: {$contact['label']}\n";
                    }
                    
                    if (($reminder['channel'] === 'SMS' || $reminder['channel'] === 'BOTH') && !empty($contact['phone'])) {
                        $pdo->prepare("INSERT INTO campains_queue (user_id,campaign_id,category_id,item_id,channel,status) VALUES (?,?,?,?,'SMS','QUEUE')")
                            ->execute([$UID, $campaign_id, $contact['category_id'], $contact['id']]);
                        echo "      Added SMS to queue for: {$contact['label']}\n";
                    }
                    
                    // Salvează în log
                    $pdo->prepare("INSERT INTO campains_reminders_log (user_id,reminder_id,contact_id,sent_date,channel,status) VALUES (?,?,?,CURDATE(),?,'PENDING')")
                        ->execute([$UID, $reminder['id'], $contact['id'], $reminder['channel']]);
                    
                    $totalProcessed++;
                } else {
                    echo "      Skipped {$contact['label']} (already sent today)\n";
                }
            }
        }
    }
    
    echo "\n[" . date('Y-m-d H:i:s') . "] Processing complete. Total reminders processed: $totalProcessed\n";
    
} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}

exit(0);