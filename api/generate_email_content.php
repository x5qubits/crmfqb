<?php
global $gemini_api_key;

$prompt = trim($_POST['prompt'] ?? '');

if (empty($prompt)) {
    $response['error'] = 'Instrucțiuni lipsă!';
    return;
}

try {
    $context = "Tu ești un asistent email profesional. Generează conținutul unui email bazat pe următoarele instrucțiuni:\n\n";
    $context .= $prompt;
    $context .= "\n\nGenerează doar conținutul emailului în format HTML simplu, fără ```html, fără code blocks, fără salutări finale sau semnături. Răspunde în română dacă instrucțiunile sunt în română, sau în engleză dacă sunt în engleză.";
    
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
        $response['error'] = 'Gemini API Error (HTTP ' . $http_code . ')';
        return;
    }
    
    $gemini_response = json_decode($result, true);
    
    if (isset($gemini_response['candidates'][0]['content']['parts'][0]['text'])) {
        $content = $gemini_response['candidates'][0]['content']['parts'][0]['text'];
        
        // Clean up the response - remove code blocks and unwanted formatting
        $content = preg_replace('/```html\s*/i', '', $content);
        $content = preg_replace('/```\s*$/i', '', $content);
        $content = preg_replace('/```/i', '', $content);
        $content = trim($content);
        
        // Convert newlines to <br> tags if not already HTML
        if (strpos($content, '<p>') === false && strpos($content, '<br') === false) {
            $content = nl2br($content);
        }
        
        $response['success'] = true;
        $response['content'] = $content;
    } else {
        $response['error'] = 'Răspuns invalid de la Gemini';
    }
    
} catch (Exception $e) {
    $response['error'] = 'Eroare: ' . $e->getMessage();
}