<?php
require_once 'config.php';
require_once 'db.php';

header('Content-Type: application/json');

// Verifică dacă utilizatorul este autentificat
$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Utilizator neautorizat']);
    exit;
}

// Listează câmpurile permise la update
$allowed_fields = ['name',
 'telefon', 
 'iban', 
 'banc_name', 
 'company_site', 
 'contact_email', 
 'company_cif', 
 'oblio_user', 'oblio_apikey','company_name', 'cui', 'billing_address'];

// Filtrează doar câmpurile permise din $_POST
$fields_to_update = [];
$params = [':user_id' => $user_id];

foreach ($allowed_fields as $field) {
    if (isset($_POST[$field])) {
        $fields_to_update[] = "$field = :$field";
        $params[":$field"] = trim($_POST[$field]);
    }
}

if (empty($fields_to_update)) {
    echo json_encode(['error' => 'Niciun câmp valid de actualizat.']);
    exit;
}

// Creează query dinamic
$sql = "UPDATE users SET " . implode(', ', $fields_to_update) . " WHERE id = :user_id";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :email");
    $stmt->execute(['email' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
	$_SESSION['user_id']      = $user['id'];
	$_SESSION['user_name']    = $user['name'];
	$_SESSION['emag_api']     = $user['emag_api'];
	$_SESSION['bot_allowed']  = $user['bot_allowed'];
	$_SESSION['bot_admin']    = $user['id'] == 1;
	$_SESSION['membersip']    = $user['membersip'];
	$_SESSION['membersip_days']    = $user['membersip_days'];
	$_SESSION['email']    = $user['email'];
	$_SESSION['telefon']    = $user['telefon'];
	
    echo json_encode(['success' => true, 'message' => 'Datele au fost actualizate cu succes.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Eroare la actualizare: ' . $e->getMessage()]);
}

