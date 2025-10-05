<?php
// ajax/whatsappHumanTakeover.php

$log_file = __DIR__ . '/debug_whatsapp.log';
function write_log($message) {
    global $log_file;
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($log_file, "[$timestamp] " . print_r($message, true) . "\n", FILE_APPEND);
}

write_log("--- whatsappHumanTakeover.php started ---");
header('Content-Type: application/json');

if (empty($_POST['recipient'])) {
    write_log("Error: 'recipient' is missing from POST data.");
    echo json_encode(['status' => 'error', 'message' => 'Recipient not provided.']);
    exit;
}

$customerPhone = $_POST['recipient'];

$stateFileDir = __DIR__ . '/conv_states/';
if (!file_exists($stateFileDir)) {
    mkdir($stateFileDir, 0777, true);
}
$stateFilePath = $stateFileDir . preg_replace('/[^0-9]/', '', $customerPhone) . '.json';

$newState = ['status' => 'HUMAN_ACTIVE', 'timestamp' => time()];

if (file_put_contents($stateFilePath, json_encode($newState))) {
    write_log("Set state for $customerPhone to HUMAN_ACTIVE.");
    echo json_encode(['status' => 'success', 'message' => "Conversation state for $customerPhone set to HUMAN_ACTIVE."]);
} else {
    write_log("Error: Failed to write state file for $customerPhone.");
    echo json_encode(['status' => 'error', 'message' => 'Failed to update conversation state.']);
}

write_log("--- whatsappHumanTakeover.php finished ---");
?>
