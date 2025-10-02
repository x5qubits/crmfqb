<?php
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_lifetime', 3600);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);

if (!class_exists('ValueError')) {
    class ValueError extends Exception {}
}


// Config baza de date
$sql_db_host = "localhost";
$sql_db_user = "qb_crm";
$sql_db_pass = "ONWd*G4MS5t0BmEM";
$sql_db_name = "qb_crm";
$sql_db_port = 3306;
$HostingCompany = "";
$AppName = "FQB";
$contact_email = "";
$contact_phone = "";
$contact_adress = "";
$server_ip = '164.132.167.164';
$MainPage = "Dashboard";
$MainPageh = "dashboard";
$hidden_users = array("32");
$gemini_api_key = "AIzaSyCkTiu9BimPOjVOiCONC8PvSjkJ1dlpaTE";
$bot_allow = array(
"1",
"2",
"3",
"13",
"30",
"32",
"33"
);
$isAdmin = isset($_SESSION['bot_admin']) && $_SESSION['bot_admin'] == 1 ? true:false;
$plans = [
	[
		"name" => "Pro", 
		"badge" => "Pro",
		"watch_list" => 20, 
		"pnk_change" => 5,
		"email" => true,
		"sms" => false,
		"advanceview" => false,
		"manual_orders_update" => 30,
		"price" => 500,
	],
	[
		"name" => "Bussiness", 
		"badge" => "Bussiness",
		"watch_list" => 100, 
		"pnk_change" => 10,
		"email" => true,
		"sms" => false,
		"advanceview" => false,
		"manual_orders_update" => 50,
		"price" => 2000,
	],
	[
		"name" => "Enterprise", 
		"badge" => "Enterprise",
		"watch_list" => 200, 
		"pnk_change" => 50,
		"email" => true,
		"sms" => true,
		"advanceview" => false,
		"manual_orders_update" => 75,
		"price" => 2500,
	],
	[
		"name" => "Ultimate", 
		"badge" => "Ultimate",
		"watch_list" => 1500, 
		"pnk_change" => 80,
		"email" => true,
		"sms" => true,
		"advanceview" => true,
		"manual_orders_update" => 150,
		"price" => 3000,
	]
];

function GetMembership($index){
	global $plans;
	
	if(count($plans) > $index){
		return $plans[$index];
	}else{
		return $plans[0];
	}
}

function GetBadgeColor($index) {
    $colors = [
        0 => 'secondary',  // gray - start membership
        1 => 'info',       // light blue
        2 => 'primary',    // blue
        3 => 'success',    // green
        4 => 'warning',    // yellow/orange
        5 => 'danger',     // red
        6 => 'purple',     // deep purple (AdminLTE 3 has this)
    ];

    return isset($colors[$index]) ? $colors[$index] : 'dark'; // fallback if out of range
}

function Z_Secure($string) {
    return $string = preg_replace("/&#?[a-z0-9]+;/i", "", $string);
}
function CanViewAdvanced(){
	global $plans, $_SESSION;
	$membersip = GetMembership($_SESSION['membersip']);
	$membersip_days = $_SESSION['membersip_days'];
	if($membersip_days > 0) {
		return $membersip['advanceview'];
	}
	return false;

}

function CanAddToWatchList($currentProductsCount){
	global $plans, $_SESSION;
	$membersip = GetMembership($_SESSION['membersip']);
	$membersip_days = $_SESSION['membersip_days'];
	if($membersip_days > 0) {
		return $membersip['watch_list'] > $currentProductsCount;
	}
	return false;

}
function CanScheduleUpdates($currentProductsCount){
	global $plans, $_SESSION;
	$membersip = GetMembership($_SESSION['membersip']);
	$membersip_days = $_SESSION['membersip_days'];
	//var_dump($membersip['manual_orders_update']);
	if($membersip_days > 0) {
		return $membersip['manual_orders_update'] > $currentProductsCount;
	}
	return false;

}

function CanAddToPNK($currentProductsCount){
	global $plans, $_SESSION;
	$membersip = GetMembership($_SESSION['membersip']);
	$membersip_days = $_SESSION['membersip_days'];
	if($membersip_days > 0) {
		return $membersip['pnk_change'] > $currentProductsCount;
	}
	return false;

}
// Alte setări
define('BASE_URL', 'http://localhost/emag-orders'); // modifică după caz
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function GetApiById($api){
	switch($api){
		case 1: return "https://marketplace-api.emag.bg/api-3";
		case 2: return "https://marketplace-api.emag.hu/api-3";
	}
	return "https://marketplace-api.emag.ro/api-3";
}

function GetApiIdByUrl($api){
	switch($api){
		case "https://marketplace-api.emag.bg/api-3": return 1;
		case "https://marketplace-api.emag.hu/api-3": return 2;
	}
	return 0;
}
function getPublicIP() {
    $ch = curl_init('https://api.ipify.org');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $ip = curl_exec($ch);
    curl_close($ch);
    return $ip ?: 'IP indisponibil';
}
function GetMknameById($api){
	switch($api){
		case 1: return "Bulgaria";
		case 2: return "Ungaria";
	}
	return "Romania";
}

function isMobile() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'];

    // Common mobile device keywords
    $mobileAgents = [
        'Android', 'iPhone', 'iPad', 'iPod', 'BlackBerry', 'Opera Mini',
        'IEMobile', 'Mobile', 'Windows Phone', 'webOS', 'Kindle', 'Silk',
        'Opera Mobi', 'Fennec'
    ];

    foreach ($mobileAgents as $device) {
        if (stripos($userAgent, $device) !== false) {
            return true;
        }
    }

    return false;
}

function zIsAdmin(){
	global $hidden_users, $_SESSION;
	//return false;
	return isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] == 1;
}

function xHideOrder($user_id){
	global $hidden_users, $_SESSION;
	if(zIsAdmin()) return false;
	$user_id = (int)$user_id;
	$id = (int)$_SESSION['user_id'];
	
	if($id != $user_id && in_array($user_id, $hidden_users)) {
		return true;
	}
	return false;
}