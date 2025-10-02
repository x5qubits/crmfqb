<?php
global $gemini_api_key;

$email_id = (int)($_POST['email_id'] ?? 0);
$custom_prompt = trim($_POST['prompt'] ?? '');

if ($email_id <= 0) {
    $response['error'] = 'Email ID invalid!';
    return;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM emails WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $email_id, ':user_id' => $user_id]);
    $email = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$email) {
        $response['error'] = 'Email negăsit!';
        return;
    }
    
    $thread_stmt = $pdo->prepare("
        SELECT from_email, from_name, subject, body, received_at
        FROM emails 
        WHERE user_id = :user_id 
          AND (subject = :subject OR subject LIKE CONCAT('Re: ', :subject))
        ORDER BY received_at ASC
        LIMIT 10
    ");
    $thread_stmt->execute([
        ':user_id' => $user_id,
        ':subject' => preg_replace('/^(Re:|Fwd:)\s*/i', '', $email['subject'])
    ]);
    $thread = $thread_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $context = "Tu ești un asistent email. Generează un răspuns profesional.\n\nCONVERSAȚIE:\n";
    
    foreach ($thread as $msg) {
        $clean_body = strip_tags($msg['body']);
        $clean_body = substr($clean_body, 0, 500);
        $context .= "De la: {$msg['from_name']} ({$msg['from_email']})\n";
        $context .= "Subiect: {$msg['subject']}\n";
        $context .= "Mesaj: $clean_body\n\n";
    }
    
    if (!empty($custom_prompt)) {
        $context .= "\nINSTRUCȚIUNI: $custom_prompt\n";
    }
    
    $context .= "\nGenerează un răspuns profesional în format HTML simplu (fără ```html sau code blocks), în aceeași limbă ca emailul original. Nu adăuga semnătură sau salutare finală.";
    
    $api_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $gemini_api_key;
    
    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $context]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 1024,
        ]
    ];
    
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        $response['error'] = 'Gemini API Error (HTTP ' . $http_code . ')';
        return;
    }
    
    $gemini_response = json_decode($result, true);
    
    if (isset($gemini_response['candidates'][0]['content']['parts'][0]['text'])) {
        $reply = $gemini_response['candidates'][0]['content']['parts'][0]['text'];
        
        // Clean up formatting
        $reply = preg_replace('/```html\s*/i', '', $reply);
        $reply = preg_replace('/```\s*$/i', '', $reply);
        $reply = preg_replace('/```/i', '', $reply);
        $reply = trim($reply);
        
        $response['success'] = true;
        $response['reply'] = $reply;
    } else {
        $response['error'] = 'Răspuns invalid de la Gemini';
    }
    
} catch (Exception $e) {
    $response['error'] = 'Eroare: ' . $e->getMessage();
}