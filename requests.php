<?php
// api/requests.php
// Router that includes and executes the right API script based on ?op=...

header('Content-Type: application/json');

// map ops -> files
$routes = [

  'getNext'   => __DIR__ . '/api/next-sms.php',
  'login'   => __DIR__ . '/api/login-sms.php',
  'gotMsg'   => __DIR__ . '/api/send-mail-sms.php',
  'whatsappHumanTakeover'   => __DIR__ . '/api/humanTakeover.php',
  'processWhatsappMsg'   => __DIR__ . '/api/processWhatsappMsg.php',

];

$op = isset($_GET['f']) ? $_GET['f'] : '';
if (!isset($routes[$op])) {
  http_response_code(400);
  echo json_encode(['error' => 'invalid op', 'allowed' => array_keys($routes)]);
  exit;
}

/*
  Passthrough of query params:
  - user_id, date, channel, campaign_id, category_id, id, status, format, etc.
  Included scripts already read from $_GET/$_POST, so nothing else to do.
*/

// Default channel for get-next if missing
if ($op === 'get-next' && empty($_GET['channel'])) {
  $_GET['channel'] = 'SMS';
}

require $routes[$op];
