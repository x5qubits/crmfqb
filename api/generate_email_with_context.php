<?php
global $gemini_api_key;

$email_type = $_POST['email_type'] ?? 'business';
$instructions = trim($_POST['instructions'] ?? '');
$tone = $_POST['tone'] ?? 'professional';
$to = trim($_POST['to'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$reply_to_id = (int)($_POST['reply_to_id'] ?? 0);

try {
    $context = "Tu ești un asistent email profesional în limba română.\n\n";
    
    // Get conversation history if replying
    if ($reply_to_id > 0) {
        $stmt = $pdo->prepare("SELECT subject, body, from_email, from_name FROM emails WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $reply_to_id, ':user_id' => $user_id]);
        $original_email = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($original_email) {
            // Get full thread
            $thread_stmt = $pdo->prepare("
                SELECT from_email, from_name, subject, body, received_at
                FROM emails 
                WHERE user_id = :user_id 
                  AND (subject = :subject OR subject LIKE CONCAT('Re: ', :subject) OR subject LIKE CONCAT('Fwd: ', :subject))
                ORDER BY received_at ASC
                LIMIT 10
            ");
            $clean_subject = preg_replace('/^(Re:|Fwd:)\s*/i', '', $original_email['subject']);
            $thread_stmt->execute([':user_id' => $user_id, ':subject' => $clean_subject]);
            $thread = $thread_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($thread)) {
                $context .= "CONVERSAȚIE ANTERIOARĂ CU CLIENTUL:\n";
                foreach ($thread as $msg) {
                    $clean_body = strip_tags($msg['body']);
                    $clean_body = substr($clean_body, 0, 400);
                    $context .= "---\n";
                    $context .= "De la: {$msg['from_name']} ({$msg['from_email']})\n";
                    $context .= "Subiect: {$msg['subject']}\n";
                    $context .= "Conținut: $clean_body\n\n";
                }
                $context .= "---\n\n";
            }
        }
    }
    
    // Type templates
    $type_prompts = [
        'business' => 'Scrie un email de business formal și profesional',
        'proposal' => 'Scrie o propunere comercială detaliată',
        'followup' => 'Scrie un follow-up profesional',
        'thanks' => 'Scrie un email de mulțumire',
        'info' => 'Scrie un email informativ clar',
        'custom' => ''
    ];
    
    $context .= "SARCINĂ: " . ($type_prompts[$email_type] ?? $type_prompts['business']) . "\n\n";
    
    if ($to) $context .= "Destinatar: $to\n";
    if ($subject) $context .= "Subiect: $subject\n";
    
    $context .= "Ton: $tone\n\n";
    
    if ($instructions) {
        $context .= "INSTRUCȚIUNI SPECIALE:\n$instructions\n\n";
    }
    
    $context .= "IMPORTANT:\n";
    $context .= "- Ține cont de conversația anterioară și continuă discuția în mod natural\n";
    $context .= "- Generează DOAR conținutul emailului în HTML simplu\n";
    $context .= "- NU include ```html sau alte code blocks\n";
    $context .= "- NU adăuga semnătură (se va adăuga automat)\n";
    $context .= "- Păstrează un ton " . $tone . "\n";
    $context .= "- Răspunde la punctele ridicate în conversație\n";
    
    $api_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $gemini_api_key;
    
    $data = [
        'contents' => [['parts' => [['text' => $context]]]],
        'generationConfig' => [
            'temperature' => 0.8,
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
        $response['error'] = 'Gemini API Error';
        return;
    }
    
    $gemini_response = json_decode($result, true);
    
    if (isset($gemini_response['candidates'][0]['content']['parts'][0]['text'])) {
        $content = $gemini_response['candidates'][0]['content']['parts'][0]['text'];
        
        // Clean formatting
        $content = preg_replace('/```html\s*/i', '', $content);
        $content = preg_replace('/```\s*$/i', '', $content);
        $content = preg_replace('/```/i', '', $content);
        $content = trim($content);
        
        $response['success'] = true;
        $response['content'] = $content;
    } else {
        $response['error'] = 'Răspuns invalid';
    }
    
} catch (Exception $e) {
    $response['error'] = 'Eroare: ' . $e->getMessage();
}