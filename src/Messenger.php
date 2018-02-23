<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// require 'vendor/phpmailer/phpmailer/src/Exception.php';
// require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
// require 'vendor/phpmailer/phpmailer/src/SMTP.php';

// require 'vendor/autoload.php';

class Messenger
{
	public $mail;

	public function __construct()
	{
		$this->mail = new PHPMailer(true);

		// read smtp config
		$conf = parse_ini_file('config/smtpconf.ini');
		// if (!$conf) die("smtp config missing");

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
    // $this->mail->addReplyTo('info@example.com', 'Information');
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
    	// echo 'Message has been sent';
		} catch (Exception $e) {
			echo 'Message could not be sent. Mailer Error: ', $this->mail->ErrorInfo;
		}
	}

	// /**
	//  * @param $to
	//  * @param $subject
	//  * @param $msg
	//  * @param null $replyTo
	//  * @return bool|int
	//  */
	// public static function sendMail($from, $to, $subject, $msg, $replyTo = null, $htmlMsg = null, $showHtmlHeader = false)
	// {
	// 	if ($replyTo == null)
	// 		$replyTo = $from;;

	// 	$conf = parse_ini_file('smtpconf.ini');
	// 	if (!$conf) die("Could not establish smtp connection.");

	// 	$mailer = Swift_Mailer::newInstance(
	// 		Swift_SmtpTransport::newInstance($conf['host'], $conf['port'], $conf['security'])
	// 			->setUsername($conf['user'])
	// 			->setPassword($conf['password'])
	// 	);

	// 	$message = Swift_Message::newInstance($subject, $msg)
	// 		->setFrom($from)
	// 		->setReplyTo($replyTo)
	// 		->setTo(array($to));

	// 	// Optionally add an alternative body
	// 	if (isset($htmlMsg)) {
	// 		$contentHTML = $showHtmlHeader ? MessageCenter::createAfeefaHtmlHeader() : '';
	// 		$contentHTML .= nl2br($htmlMsg);
	// 		$message->addPart($contentHTML, 'text/html');
	// 	}

	// 	return $mailer->send($message);
	// }
}