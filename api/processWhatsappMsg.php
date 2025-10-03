<?php
// ajax/processWhatsappMsg.php

// --- Basic Setup ---
$log_file = __DIR__ . '/debug_whatsapp.log';
function write_log($message) {
    global $log_file;
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($log_file, "[$timestamp] " . print_r($message, true) . "\n", FILE_APPEND);
}




function sendHumanReviewEmail($from, $message, $aiContext) {
    write_log("Attempting to send human review email to contact@x5qubits.com");
    $who = function_exists('GetRecord') ? GetRecord($from) : 'Unknown';

    $mail_me = '
    <html><body>
    <p>The AI assistant has flagged a conversation that may require human attention.</p>
    <table width="550" border="0" cellspacing="0" cellpadding="15">
        <tr bgcolor="#eeffee"><td>Phone Number</td><td>'.$from.'</td></tr>
        <tr bgcolor="#eeeeff"><td>Company (if known)</td><td>'.$who.'</td></tr>
        <tr bgcolor="#eeffee"><td>User Message</td><td>'.htmlspecialchars($message).'</td></tr>
        <tr bgcolor="#eeeeff"><td>AI Context/Reply</td><td>'.htmlspecialchars($aiContext).'</td></tr>
    </table>
    </body></html>';
	$SMTPUser = "contact@x5qubits.com";
	$SMTPPass = "optiplex!(*%";
	$SMTPHost = "ssl0.ovh.net";
	$SMTPPort = 587;
	$SMTPReceiver = $SMTPUser;
    try {
		$mail = new PHPMailer();
		$mail->CharSet = "UTF-8";
		$mail->IsSMTP();
		$mail->SMTPDebug = 0;
		$mail->isSMTP();
		$mail->SMTPAuth = true;
		$mail->Host = $SMTPHost;
		$mail->Port = $SMTPPort; // or 587
		$mail->Username = $SMTPUser;
		$mail->Password =  $SMTPPass;
		$mail->From = $SMTPReceiver;
		$mail->FromName = "Five Quantum Bits";
		$mail->AddAddress("contact@x5qubits.com");
		$mail->IsHTML(true);
		$mail->Subject = "Ai primit un sms de la: ".$_POST['from'];
		
		$mail->Body = $mail_me;
		$mail->AltBody = $mail_me;

		if (!$mail->Send()) {
			write_log("Human review email sent successfully.");
		}else{
			write_log("Human review email sent NO successfully.");
		}
        
    } catch (Exception $e) {
        write_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}


// --- Main Script Execution ---

write_log("--- processWhatsappMsg.php started ---");
write_log("POST DATA: " . json_encode($_POST));
header('Content-Type: application/json');

if (empty($_POST['from']) || empty($_POST['content'])) {
    write_log("Error: 'from' or 'content' is missing.");
    echo json_encode(['status' => 'error', 'reply' => 'Missing required parameters.']);
    exit;
}

$customerPhone = $_POST['from'];
$userMessage = $_POST['content'];
write_log("Received message from: $customerPhone | Message: $userMessage");

// --- State Management & Conversation History ---
$stateFileDir = __DIR__ . '/conv_states/';
if (!file_exists($stateFileDir)) {
    mkdir($stateFileDir, 0777, true);
}
$stateFilePath = $stateFileDir . preg_replace('/[^0-9]/', '', $customerPhone) . '.json';
$takeoverDuration = 1800;

$currentState = ['status' => 'AI_ACTIVE', 'timestamp' => 0, 'history' => []];
if (file_exists($stateFilePath)) {
    $currentState = json_decode(file_get_contents($stateFilePath), true);
    if (!isset($currentState['history'])) $currentState['history'] = [];
}

if ($currentState['status'] === 'HUMAN_ACTIVE' && (time() - $currentState['timestamp']) < $takeoverDuration) {
    write_log("Human is active for $customerPhone. AI will not reply.");
    echo json_encode(['status' => 'human_active', 'reply' => null]);
    exit;
}
write_log("AI is active for $customerPhone.");

// --- Gemini API Interaction ---
$geminiApiKey = 'AIzaSyCkTiu9BimPOjVOiCONC8PvSjkJ1dlpaTE';
$geminiApiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $geminiApiKey;

$systemPrompt = "
**ROLE & GOAL:**
You are Andrei, a professional, helpful, and friendly Assistant for **Five Quantum Bits (FQB)**.
Your primary mission is to provide excellent first-line customer support on WhatsApp, answer initial questions, and qualify leads.

**COMPANY CONTEXT:**
- **Company Name:** Five Quantum Bits (FQB)
- **Specialization:** Custom software solutions (web and mobile apps).
- **Websites:** 5qb.ro (Romanian), 5qb.us (international).

**PERSONA & TONE:**
- **Professional & Friendly:** Positive, helpful, professional tone.
- **Concise:** Clear and to the point.
- **Language:** **Crucial:** Reply in the exact same language the user uses, default language is RO.
- **Who created you:** You are made by FQB.

**STRICT RULES & INSTRUCTIONS:**
1.  **Pricing Inquiries:** If a user asks for a price, NEVER give a number. Explain that prices depend on complexity and ask for more details about their project.
2.  **Human Takeover Request:** If the user asks to speak to a human, reply ONLY with: 'Of course. One of our team members will be with you shortly.'
3.  **Software License Inquiries:** State that for that they should contact us on (+40724627057).
4.  **Stay On Topic:** Only discuss custom software development. If you don't know an answer, say: 'That's a great question. Let me connect you with a specialist.'

**!! NEW CRITICAL RULE - HUMAN REVIEW FLAG !!**
5.  **Flag for Review:** After your normal response, you MUST silently append the special flag `[FLAG_FOR_REVIEW]` if the user's message meets ANY of the following criteria:
    - They ask for a price or a quote.
    - They express strong interest in starting a project.
    - They seem frustrated, angry, or unhappy with a previous response.
    - They ask a complex technical question that you cannot fully answer.
    - **Example:** If the user says 'how much for an app?', your full output should be: 'That's a great question! To give you an accurate price, could you tell me a little more about your project requirements?[FLAG_FOR_REVIEW]'
";

// --- Build the payload with conversation history ---
$apiContents = [];
foreach ($currentState['history'] as $turn) {
    $apiContents[] = $turn;
}
$apiContents[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];

$payload = json_encode([
    'contents' => $apiContents,
    'systemInstruction' => ['parts' => [['text' => $systemPrompt]]]
]);
write_log("Payload sending to Gemini: " . $payload);

// --- cURL Request ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $geminiApiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$apiResponse = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

// --- Process Response ---
if ($curlError) {
    write_log("cURL Error: " . $curlError);
    echo json_encode(['status' => 'error', 'reply' => 'Error contacting AI service.']);
    exit;
}

write_log("Raw response from Gemini: " . $apiResponse);
$responseDecoded = json_decode($apiResponse, true);

if (isset($responseDecoded['candidates'][0]['content']['parts'][0]['text'])) {
    $rawAiReply = $responseDecoded['candidates'][0]['content']['parts'][0]['text'];
    write_log("Extracted raw AI reply: " . $rawAiReply);

    // --- Check for the human review flag ---
    $flag = '[FLAG_FOR_REVIEW]';
    if (strpos($rawAiReply, $flag) !== false) {
        write_log("Human review flag DETECTED. Triggering email.");
        $aiReply = str_replace($flag, '', $rawAiReply);
        sendHumanReviewEmail($customerPhone, $userMessage, $aiReply);
    } else {
        $aiReply = $rawAiReply;
    }

    // --- Save new turn to history ---
    $currentState['history'][] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];
    $currentState['history'][] = ['role' => 'model', 'parts' => [['text' => $aiReply]]];
    if (count($currentState['history']) > 20) {
        $currentState['history'] = array_slice($currentState['history'], -20);
    }
    file_put_contents($stateFilePath, json_encode($currentState));
    write_log("Saved updated history to state file.");

    echo json_encode(['status' => 'success', 'reply' => $aiReply]);
} else {
    write_log("Error: Could not extract reply text from Gemini response.");
    echo json_encode(['status' => 'error', 'reply' => 'AI service returned an unexpected response.']);
}

write_log("--- processWhatsappMsg.php finished ---");
?>

