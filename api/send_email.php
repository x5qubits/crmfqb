<?php
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$account_id = (int)($_POST['account_id'] ?? 0);
$to = trim($_POST['to'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$body = $_POST['body'] ?? '';
$cc = trim($_POST['cc'] ?? '');
$bcc = trim($_POST['bcc'] ?? '');

if ($account_id <= 0 || empty($to) || empty($subject)) {
    $response['error'] = 'Date incomplete!';
    return;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM email_settings WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $account_id, ':user_id' => $user_id]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$account) {
        $response['error'] = 'Cont negăsit!';
        return;
    }
    
    $mail = new PHPMailer(true);
    
    $mail->isSMTP();
    $mail->Host = $account['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $account['smtp_username'];
    $mail->Password = $account['smtp_password'];
    $mail->SMTPSecure = $account['smtp_encryption'];
    $mail->Port = $account['smtp_port'];
    $mail->CharSet = 'UTF-8';
    
    $mail->setFrom($account['from_email'], $account['from_name']);
    
    foreach (explode(',', $to) as $recipient) {
        $mail->addAddress(trim($recipient));
    }
    
    if (!empty($cc)) {
        foreach (explode(',', $cc) as $recipient) {
            $mail->addCC(trim($recipient));
        }
    }
    
    if (!empty($bcc)) {
        foreach (explode(',', $bcc) as $recipient) {
            $mail->addBCC(trim($recipient));
        }
    }
    
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $body; // EXACT BODY - NO SIGNATURE ADDED HERE
    
    if (isset($_FILES['attachments'])) {
        foreach ($_FILES['attachments']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                $mail->addAttachment($tmp_name, $_FILES['attachments']['name'][$key]);
            }
        }
    }
    
    $mail->send();
    
    // Save to sent folder
    $insert = $pdo->prepare("
        INSERT INTO emails (user_id, from_email, from_name, to_email, cc, bcc,
                           subject, body, is_html, folder, is_read, sent_at, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 'sent', 1, NOW(), NOW())
    ");
    
    $insert->execute([
        $user_id, $account['from_email'], $account['from_name'],
        $to, $cc, $bcc, $subject, $body
    ]);
    
    $response['success'] = true;
    $response['message'] = 'Email trimis cu succes!';
    
} catch (Exception $e) {
    $response['error'] = 'Eroare la trimitere: ' . $mail->ErrorInfo;
}