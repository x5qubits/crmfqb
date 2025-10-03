<?php 
$response = array();
$response['maxsend'] = 300;
$response['timesec'] = 20;
$response['msg'] = "Wrong user or pass.";

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

if(isset($_POST["username"])){
	$username = Z_Secure(strtolower($_POST['username']));

    if (empty($username) ) {
		print json_encode($response);
		exit;
    }
	

		
    $stmt = $pdo->prepare("SELECT * FROM users WHERE `email` = :uid");
    $stmt->execute([':uid'=>$username]);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
		$response["OK"] = true;
		$response['msg'] = "";
		$response["code"] = $row["id"];
	}
}

print json_encode($response);
?>
