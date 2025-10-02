<?php
$account_id = (int)($_POST['account_id'] ?? 0);
$folder = $_POST['folder'] ?? 'INBOX';
$limit = (int)($_POST['limit'] ?? 50);

if ($account_id <= 0) {
    $response['error'] = 'Account ID invalid!';
    return;
}

imap_errors();
imap_alerts();

try {
    $stmt = $pdo->prepare("SELECT * FROM email_settings WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $account_id, ':user_id' => $user_id]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$account) {
        $response['error'] = 'Cont negăsit!';
        return;
    }
    
    if (empty($account['imap_encryption']) || $account['imap_encryption'] == 'none') {
        $account['imap_encryption'] = $account['imap_port'] == 993 ? 'ssl' : 'tls';
    }
    
    $flags = '/imap';
    if ($account['imap_encryption'] === 'ssl') $flags .= '/ssl';
    elseif ($account['imap_encryption'] === 'tls') $flags .= '/tls';
    $flags .= '/novalidate-cert';
    
    $mailbox = '{' . $account['imap_host'] . ':' . $account['imap_port'] . $flags . '}' . $folder;
    
    imap_timeout(IMAP_OPENTIMEOUT, 60);
    imap_timeout(IMAP_READTIMEOUT, 60);
    
    $imap = @imap_open($mailbox, $account['imap_username'], $account['imap_password'], 0, 1);
    
    if (!$imap) {
        $response['error'] = 'IMAP Error: ' . implode('; ', imap_errors() ?: ['Connection failed']);
        return;
    }
    
    if (!@imap_ping($imap)) {
        @imap_close($imap);
        $response['error'] = 'Connection lost';
        return;
    }
    
    $total = imap_num_msg($imap);
    if ($total === 0) {
        imap_close($imap);
        $response['success'] = true;
        $response['data'] = [];
        $response['total'] = 0;
        return;
    }
    
    $stmt = $pdo->prepare("SELECT message_id FROM emails WHERE user_id = :uid");
    $stmt->execute([':uid' => $user_id]);
    $existing_ids = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
    
    $start = max(1, $total - $limit + 1);
    $emails = [];
    
    for ($i = $total; $i >= $start && count($emails) < $limit; $i--) {
        if (!@imap_ping($imap)) break;
        
        $header = @imap_headerinfo($imap, $i);
        if (!$header) continue;
        
        $message_id = $header->message_id ?? '';
        if (empty($message_id) || isset($existing_ids[$message_id])) continue;
        
        $from_email = $header->from[0]->mailbox . '@' . $header->from[0]->host;
        $from_name = isset($header->from[0]->personal) ? imap_utf8($header->from[0]->personal) : $from_email;
        $to_email = isset($header->to[0]) ? $header->to[0]->mailbox . '@' . $header->to[0]->host : '';
        $subject = isset($header->subject) ? imap_utf8($header->subject) : '(No Subject)';
        $date = date('Y-m-d H:i:s', $header->udate);
        
        // PROPER BODY DECODING
        $body = '';
        $is_html = 0;
        $structure = @imap_fetchstructure($imap, $i);
        
        if ($structure) {
            if (isset($structure->parts) && count($structure->parts)) {
                // Multipart message
                foreach ($structure->parts as $partNum => $part) {
                    if ($part->subtype === 'HTML' || ($part->subtype === 'PLAIN' && !$body)) {
                        $section = $partNum + 1;
                        $body = @imap_fetchbody($imap, $i, $section);
                        
                        // Decode based on encoding
                        if ($part->encoding == 0 || $part->encoding == 1) {
                            // 7bit or 8bit - no decoding needed
                        } elseif ($part->encoding == 3) {
                            $body = base64_decode($body);
                        } elseif ($part->encoding == 4) {
                            $body = quoted_printable_decode($body);
                        }
                        
                        // Handle charset
                        if (isset($part->parameters)) {
                            foreach ($part->parameters as $param) {
                                if (strtolower($param->attribute) == 'charset') {
                                    $charset = strtoupper($param->value);
                                    if ($charset != 'UTF-8') {
                                        $body = iconv($charset, 'UTF-8//IGNORE', $body);
                                    }
                                    break;
                                }
                            }
                        }
                        
                        $is_html = $part->subtype === 'HTML' ? 1 : 0;
                        if ($part->subtype === 'HTML') break; // Prefer HTML
                    }
                }
            } else {
                // Single part message
                $body = @imap_body($imap, $i);
                
                if ($structure->encoding == 3) {
                    $body = base64_decode($body);
                } elseif ($structure->encoding == 4) {
                    $body = quoted_printable_decode($body);
                }
            }
        }
        
        try {
            $insert = $pdo->prepare("
                INSERT IGNORE INTO emails (user_id, message_id, from_email, from_name, to_email, 
                                   subject, body, is_html, folder, received_at, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'inbox', ?, NOW())
            ");
            
            $insert->execute([$user_id, $message_id, $from_email, $from_name, $to_email, $subject, $body, $is_html, $date]);
            
            $emails[] = ['from' => $from_name, 'subject' => $subject, 'date' => $date];
        } catch (PDOException $e) {
            // Ignore duplicates
        }
    }
    
    @imap_close($imap);
    
    $response['success'] = true;
    $response['data'] = $emails;
    $response['total'] = $total;
    $response['new_emails'] = count($emails);
    
} catch (Exception $e) {
    if (isset($imap) && is_resource($imap)) @imap_close($imap);
    $response['error'] = 'Exception: ' . $e->getMessage();
}