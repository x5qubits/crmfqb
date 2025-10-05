<?php 
//file_put_contents("test", json_encode($_POST));
$response = array();
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/PHPMailer/PHPMailerAutoload.php';
require_once __DIR__ . '/../db.php';

if(isset($_POST['from'])){
$who = $_POST['from'];
if($who === false) 
{
	$d = array();
	//$d['id'] = -1;
	$d['pfrom'] = $_POST['from'];
	$d['pto'] = $_POST['to'];
	$d['text'] = $_POST['content'];
	//AddOrUpdateSMS2($d);
}
$Pageid = 0;
$SMTPUser = "contact@x5qubits.com";
$SMTPPass = "optiplex!(*%";
$SMTPHost = "ssl0.ovh.net";
$SMTPPort = 587;
$SMTPReceiver = $SMTPUser;



$mail_me = '
<html>
<head>
    <style type="text/css">
    </style>
</head>
<body>
<table width="550" border="0" cellspacing="0" cellpadding="15">
    <tr bgcolor="#eeffee">
        <td>Numar Telefon</td>
        <td>'.$_POST['from'].'</td>
    </tr>
    <tr bgcolor="#eeeeff">
        <td>Firma</td>
        <td>'.$who.'</td>
    </tr>
    <tr bgcolor="#eeffee">
        <td>Mesaj</td>
        <td>'.$_POST['content'].'</td>
    </tr>
</table>
</body>
</html>
';
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
		//file_put_contents("test", "da");
	}else{
		//file_put_contents("test", "nu");
	}
}


print json_encode($response);
?>