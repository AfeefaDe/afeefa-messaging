<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Messenger
{
	public $mail;

	public function __construct()
	{
		$this->mail = new PHPMailer(true);

		// read smtp config
		$conf = parse_ini_file('config/smtpconf.ini');

		//Server settings
		$this->mail->SMTPDebug = 2;                                 // Enable verbose debug output
		$this->mail->isSMTP();                                      // Set mailer to use SMTP
		$this->mail->Host = $conf['host'];  // Specify main and backup SMTP servers
		$this->mail->SMTPAuth = true;                               // Enable SMTP authentication
		$this->mail->Username = $conf['user'];                 // SMTP username
		$this->mail->Password = $conf['password'];                           // SMTP password
		$this->mail->SMTPSecure = $conf['security'];                            // Enable TLS encryption, `ssl` also accepted
		$this->mail->Port = $conf['port'];                                    // TCP port to connect to
	}

	public function sendMail($message, $template_key, $json) {
		
		// Sender
    $this->mail->setFrom($GLOBALS["tvars"]["mail"][$template_key]['from']['address'], $GLOBALS["tvars"]["mail"][$template_key]['from']['name']);
    if (isset($json['reply_to'])) $this->mail->addReplyTo($json['reply_to']);
		
		//Recipients
		if (isset($json->to)) {
			$this->mail->addAddress($json->to);     // Add a recipient
		}
		else if (isset($GLOBALS["tvars"]["mail"][$template_key]['to'])) {
			$addresses = explode(',', $GLOBALS["tvars"]["mail"][$template_key]['to']);
			foreach ($addresses as $i=>$address) {
				$this->mail->addBCC($address);
			}
		}
		
    //Content
    $this->mail->isHTML(true);                                  // Set email format to HTML
    $this->mail->Subject = $GLOBALS["tvars"]["mail"][$template_key]['subject'];
    $this->mail->Body    = $message;
    $this->mail->AltBody = $message;
		
		try {
			$this->mail->send();
			return array("code" => 201, "message" => "Message sent");
		} catch (Exception $e) {
			return array("code" => 500, "message" => 'Message could not be sent. Mailer Error: ' . $this->mail->ErrorInfo);
		}
	}
}