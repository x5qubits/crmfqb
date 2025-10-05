<?php
session_start();
$response = array();
if(isset($_POST['what'])){

header('Content-Type: application/json');

$response["OK"] = true;
try {
require_once 'vendor/autoload.php'; 

$_POST['what'] = str_replace("ro", "", $_POST['what']);
$_POST['what'] = str_replace("RO", "", $_POST['what']);
$_POST['what'] = str_replace("rO", "", $_POST['what']);
$_POST['what'] = str_replace("Ro", "", $_POST['what']);
// Get data about more CIF
$anaf = new \Itrack\Anaf\Client();
$dataVerificare = date("YYYY-MM-DD");
$anaf->addCif($_POST['what'], $dataVerificare);
$company = $anaf->first();

$response["name"] = $company->getName();
$response["civ"] = $company->getCIF();
$response["regcom"] = $company->getRegCom();
$response["name"] = $company->getPhone();
$response["adress"] = $company->getAddress()->getCounty();
$response["adress"] .=" ".  $company->getAddress()->getCounty();
$response["adress"] .=" ". $company->getAddress()->getStreet();
$response["adress"] .=" ".  $company->getAddress()->getStreetNumber();
} catch (Exception $e) {
	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,"https://api.openapi.ro/api/companies/".$_POST['what']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$headers = [
			'x-api-key: Jxm9gMDA8FDcsj7ESW-8dYzGk3LnTYpkBfG-hwpThL4uQbKysQ'
		];

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$server_output = curl_exec ($ch);
		$server_output = json_decode($server_output, true);

		curl_close ($ch);
		try {
			if($server_output['cif'] == null)
				throw new Exception('Division by zero.');		
			$response["civ"] = $server_output['cif'];
			$response["name"] = $server_output['denumire'];
			$response["adress"] = $server_output['adresa'];
			$response["telefon"] = $server_output['telefon'];
			$response["regcom"] = $server_output['numar_reg_com'];
		$response["OK"] = true;
	} catch (Exception $e) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,"https://api.openapi.ro/api/companies/".$_POST['what']);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$headers = [
				'x-api-key: EFj9Tb9NnJpeErqbe7eiqe4rMEJ8BMYqgpTiVrwFufHbczKyEQ'
			];

			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$server_output = curl_exec ($ch);
			$server_output = json_decode($server_output, true);

			curl_close ($ch);
			try {
				if($server_output['cif'] == null)
					throw new Exception('Division by zero.');
				
				$response["civ"] = $server_output['cif'];
				$response["name"] = $server_output['denumire'];
				$response["adress"] = $server_output['adresa'];
				$response["telefon"] = $server_output['telefon'];
				$response["regcom"] = $server_output['numar_reg_com'];
			$response["OK"] = true;
		} catch (Exception $e) {
			$response["msg"] = 'Caught exception: '.  $e->getMessage();
			$response["OK"] = false;
		}
	}
}
}
print json_encode($response);
?>