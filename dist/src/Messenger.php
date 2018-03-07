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

	public function send($message, $json) {
		//Recipients
    $this->mail->setFrom($message['from']['address'], $message['from']['name']);
    $this->mail->addAddress($json->to);     // Add a recipient
    // $this->mail->addAddress('ellen@example.com');               // Name is optional
    if ($json['reply_to']) $this->mail->addReplyTo($json['reply_to']);
    // $this->mail->addCC('cc@example.com');
    // $this->mail->addBCC('bcc@example.com');

    //Attachments
    // $this->mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
    // $this->mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

    //Content
    $this->mail->isHTML(true);                                  // Set email format to HTML
    $this->mail->Subject = $message['subject'];
    $this->mail->Body    = $message['html'];
    $this->mail->AltBody = $message['text'];
		
		try {
			$this->mail->send();
    		return array("code" => 201, "message" => "Message sent");
		} catch (Exception $e) {
			return array("code" => 500, "message" => 'Message could not be sent. Mailer Error: ' . $this->mail->ErrorInfo);
		}
	}
}