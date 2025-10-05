<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

// ✅ Only allow admins
if (empty($_SESSION['bot_admin']) || $_SESSION['bot_admin'] !== true) {
    echo json_encode([
        'success' => false,
        'error'   => ['Nu aveți permisiunea să efectuați această acțiune.']
    ]);
    exit;
}

// ✅ Validate user_id
$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
if (!$userId) {
    echo json_encode([
        'success' => false,
        'error'   => ['ID utilizator invalid.']
    ]);
    exit;
}

// ✅ Fetch user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        'success' => false,
        'error'   => ['Utilizatorul nu a fost găsit.']
    ]);
    exit;
}

// ✅ Keep bot_admin session but override user session
$_SESSION['user_id']      = $user['id'];
$_SESSION['user_name']    = $user['name'];
$_SESSION['emag_api']     = $user['emag_api'];
$_SESSION['membersip']    = $user['membersip'];
$_SESSION['membersip_days'] = $user['membersip_days'];
$_SESSION['email']        = $user['email'];
$_SESSION['telefon']      = $user['telefon'];
$_SESSION['isAdmin']    = $user['isAdmin'];
 // Optional flag
$server_ip = getPublicIP();
$_SESSION['$server_ip'] = $server_ip;

// ✅ Respond with success and optional redirect
echo json_encode([
    'success' => true,
    'redirect' => 'dashboard.php'
]);
