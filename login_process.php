<?php
// login_process.php (REFACTORAT PENTRU JSON)

// 1) Load config (starts session, defines DB credentials)
require_once __DIR__ . '/config.php';

// 2) Load the PDO wrapper
require_once __DIR__ . '/db.php';

// 3) Pregătim răspunsul
$errors = [];
$success = false;

// 4) Validăm datele
$email    = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    $errors[] = "Email și parolă obligatorii.";
} else {
    // 5) Căutăm userul
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password']) || $user && $password == $user['password']) {
		if($user['is_active'] == 0){
			$errors[] = "Contul dvs. este in verificare!<br><small>Va rugam reveniti mai tarziu.</small>";
			
		} else if($user['is_active'] == 5){
			$errors[] = "Cont blocat!<br><small>Va rugam sa ne contactati.</small>";
			
		} else {
			// 6) Logăm autentificarea
			$logStmt = $pdo->prepare(
				"INSERT INTO login_logs (user_id, ip_address, user_agent)
				 VALUES (:uid, :ip, :ua)"
			);
			$logStmt->execute([
				'uid' => $user['id'],
				'ip'  => $_SERVER['REMOTE_ADDR']     ?? 'unknown',
				'ua'  => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
			]);

			// 7) Setăm sesiunea
			$_SESSION['user_id']      = $user['id'];
			$_SESSION['user_name']    = $user['name'];
			$_SESSION['emag_api']     = "";
			$_SESSION['bot_admin']    = $user['isAdmin'] == 1;
			$_SESSION['isAdmin']    = $user['isAdmin'];
			$_SESSION['membersip']    = $user['membersip'];
			$_SESSION['membersip_days']    = $user['membersip_days'];
			$_SESSION['email']    = $user['email'];
			$_SESSION['telefon']    = $user['telefon'];
			$server_ip = getPublicIP();
			$_SESSION['$server_ip'] = $server_ip;
			$_SESSION['is_impersonating'] = $user['id'];
			$success = true;
		}
		
		
    } else {
        $errors[] = "Email sau parolă incorecte.";
    }
}

// 8) Răspuns JSON
$response = [
    'success' => $success,
    'error'   => $errors
];

header('Content-Type: application/json');
echo json_encode($response);
