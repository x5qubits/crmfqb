<?php
$action = $_POST['action'] ?? 'add';
$id = (int)($_POST['id'] ?? 0);

$data = [
    'smtp_host' => trim($_POST['smtp_host'] ?? ''),
    'smtp_port' => (int)($_POST['smtp_port'] ?? 587),
    'smtp_username' => trim($_POST['smtp_username'] ?? ''),
    'smtp_encryption' => in_array($_POST['smtp_encryption'] ?? '', ['tls', 'ssl', 'none']) ? $_POST['smtp_encryption'] : 'tls',
    'imap_host' => trim($_POST['imap_host'] ?? ''),
    'imap_port' => (int)($_POST['imap_port'] ?? 993),
    'imap_username' => trim($_POST['imap_username'] ?? ''),
    'imap_encryption' => in_array($_POST['imap_encryption'] ?? '', ['ssl', 'tls', 'none']) ? $_POST['imap_encryption'] : 'ssl',
    'from_email' => trim($_POST['from_email'] ?? ''),
    'from_name' => trim($_POST['from_name'] ?? ''),
    'signature' => $_POST['signature'] ?? '',
    'ai_assistant_enabled' => (int)($_POST['ai_assistant_enabled'] ?? 1)
];

// Validate port/encryption combination
if ($data['imap_port'] == 993 && $data['imap_encryption'] == 'none') {
    $response['error'] = 'Port 993 necesită SSL. Folosiți port 143 pentru conexiuni fără criptare.';
    return;
}
if ($data['smtp_port'] == 465 && $data['smtp_encryption'] != 'ssl') {
    $response['error'] = 'Port 465 necesită SSL.';
    return;
}

// Only update password if provided and not masked
if (!empty($_POST['smtp_password']) && $_POST['smtp_password'] !== '********') {
    $data['smtp_password'] = $_POST['smtp_password'];
}
if (!empty($_POST['imap_password']) && $_POST['imap_password'] !== '********') {
    $data['imap_password'] = $_POST['imap_password'];
}

try {
    if ($action === 'edit' && $id > 0) {
        $fields = [];
        $params = [':user_id' => $user_id, ':id' => $id];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }
        
        $sql = "UPDATE email_settings SET " . implode(', ', $fields) . " 
                WHERE id = :id AND user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $response['success'] = true;
        $response['message'] = 'Cont email actualizat!';
    } else {
        if (empty($_POST['smtp_password']) || empty($_POST['imap_password'])) {
            $response['error'] = 'Parolele sunt obligatorii pentru cont nou!';
            return;
        }
        
        $data['user_id'] = $user_id;
        $data['smtp_password'] = $_POST['smtp_password'];
        $data['imap_password'] = $_POST['imap_password'];
        
        $fields = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO email_settings ($fields) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        
        $response['success'] = true;
        $response['message'] = 'Cont email adăugat!';
        $response['id'] = $pdo->lastInsertId();
    }
} catch (PDOException $e) {
    $response['error'] = 'Eroare: ' . $e->getMessage();
}