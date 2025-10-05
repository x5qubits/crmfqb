<?php
require 'config.php';
require 'db.php';
$response = array();
$response['success'] = false;
 
$f = "";
 
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : -1;


		
if (isset($_GET['f'])) {
    $f = Z_Secure($_GET['f'], 0);
}

if($user_id != -1){
	$files = scandir('api');
	unset($files[0]);
	unset($files[1]);

	if (file_exists('api/' . $f . '.php') && in_array($f . '.php', $files)) {
		include 'api/' . $f . '.php';
	}
}


header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
?>
