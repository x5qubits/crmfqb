<?php
// Get all email accounts for current user
try {
    $stmt = $pdo->prepare("
        SELECT id, smtp_host, smtp_port, smtp_username, smtp_encryption,
               imap_host, imap_port, imap_username, imap_encryption,
               from_email, from_name, signature, ai_assistant_enabled
        FROM email_settings 
        WHERE user_id = :user_id 
        ORDER BY id ASC
    ");
    $stmt->execute([':user_id' => $user_id]);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Don't expose passwords
    foreach ($accounts as &$account) {
        $account['smtp_password'] = '********';
        $account['imap_password'] = '********';
    }
    
    $response['success'] = true;
    $response['data'] = $accounts;
} catch (PDOException $e) {
    $response['error'] = 'Eroare: ' . $e->getMessage();
}