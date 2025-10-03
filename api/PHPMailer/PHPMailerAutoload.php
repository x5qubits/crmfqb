<?php
class PHPMailer {
    public $to;
    public $Subject;
    public $Body;
    public $headers;

    public $SMTPDebug;
    public $SMTPAuth;
    public $Host;
    public $Port;
    public $Username;
    public $Password;
    public $From;
    public $FromName;
    public $CharSet;
    public $Encoding;

    public function IsHTML($isHtml) {
        // Placeholder for future implementation if needed
    }

    public function AddAddress($to) {
        $this->to = $to;
    }

    public function IsSMTP() {
        // Placeholder for future implementation if needed
    }

    public function setTo($to) {
        $this->to = $to;
    }
	
    public function setFrom($subject) {
        $this->From = $subject;
    }
	
    public function setSubject($subject) {
        $this->Subject = $subject;
    }

    public function setBody($body) {
        $this->Body = $body;
    }

    public function setHeaders($headers) {
        $this->headers = $headers;
    }

    public function send() {
        $headers = $this->headers;

        // Set additional headers if needed
		$headers  = "From: ".$this->From."\r\n";
		$headers .= "Reply-To: ".$this->to."\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        if (mail($this->to, $this->Subject, $this->Body, $headers)) {
            return true;
        } else {
            return false;
        }
    }
}
