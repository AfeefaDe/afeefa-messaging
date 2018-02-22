<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

/**
 * Singleton class
 */
final class Messenger
{

	/**
	 * private ctor
	 */
	private function __construct()
	{

	}

	/**
	 * @return Messenger
	 */
	public static function Instance()
	{
		static $inst = null;
		if ($inst === null) {
			$inst = new Messenger();
		}
		return $inst;
	}

	/////////////
	// MAILING //
	/////////////

	/**
	 * @param $user
	 * @return String
	 */
	public static function createOrgaSignature($user)
	{
		$s = "-- \n" . $user->orga->name . "\n" . $user->orga->address . ", " . $user->orga->addition . ", " . $user->orga->zip . " " . $user->orga->city;
		if (isset($user->orga->web) && $user->orga->web != '') $s .= "\nWeb: " . $user->orga->web;
		if (isset($user->phone) && $user->phone != '') $s .= "\nTel.: " . $user->phone;
		return $s;
	}

	public static function createAfeefaHtmlHeader()
	{
		$html = '<div style="height: 3em; width: 100%; background-image: url(https://afeefa.de/_Resources/Static/Packages/DDFA.dresdenfueralleDe/DDFA/img/afeefa.png); background-size: contain; background-position: right; background-repeat: no-repeat;"></div>';
		return $html;
	}

	/**
	 * @param $orgaUser
	 */
	public static function sendUserEnabled($orgaUser)
	{
		MessageCenter::sendMail(
			$orgaUser->email,
			'ichhelfe.jetzt: Zugang wurde freigeschaltet',
			MessageCenter::personalizeMsg('userEnabled', [
				'1' => $orgaUser->firstname . ' ' . $orgaUser->lastname,
				'2' => $orgaUser->orga->name
			]) . MessageCenter::getIHJSignature('Orga')
		);
	}

	/**
	 * @param $to
	 * @param $subject
	 * @param $msg
	 * @param null $replyTo
	 * @return bool|int
	 */
	public static function sendMail($from, $to, $subject, $msg, $replyTo = null, $htmlMsg = null, $showHtmlHeader = false)
	{
		if ($replyTo == null)
			$replyTo = $from;;

		$conf = parse_ini_file('smtpconf.ini');
		if (!$conf) die("Could not establish smtp connection.");

		$mailer = Swift_Mailer::newInstance(
			Swift_SmtpTransport::newInstance($conf['host'], $conf['port'], $conf['security'])
				->setUsername($conf['user'])
				->setPassword($conf['password'])
		);

		$message = Swift_Message::newInstance($subject, $msg)
			->setFrom($from)
			->setReplyTo($replyTo)
			->setTo(array($to));

		// Optionally add an alternative body
		if (isset($htmlMsg)) {
			$contentHTML = $showHtmlHeader ? MessageCenter::createAfeefaHtmlHeader() : '';
			$contentHTML .= nl2br($htmlMsg);
			$message->addPart($contentHTML, 'text/html');
		}

		return $mailer->send($message);
	}

	/**
	 * @param $msg
	 * @param array $variables
	 * @return mixed
	 */
	public static function personalizeMsg($msg, array $variables)
	{
		$file = './res/msg/' . $msg . '.txt';
		if (file_exists($file))
			return MessageCenter::parse(file_get_contents($file), $variables);
		else
			return "Error: The template named '" . $msg . "' does not exist.";
	}

	/**
	 * @param $string
	 * @param array $variables
	 * @param null $errPlaceholder
	 * @return mixed
	 */
	public static function parse($string, array $variables, $errPlaceholder = null)
	{
		$escapeChar = '@';
		$esc = preg_quote($escapeChar);
		$expr = "/
			$esc$esc(?=$esc*+{)
		  | $esc{
		  | {(\w+)}
		/x";

		$callback = function ($match) use ($variables, $escapeChar, $errPlaceholder) {
			switch ($match[0]) {
				case $escapeChar . $escapeChar:
					return $escapeChar;

				case $escapeChar . '{':
					return '{';

				default:
					if (isset($variables[$match[1]])) {
						return $variables[$match[1]];
					}

					return isset($errPlaceholder) ? $errPlaceholder : $match[0];
			}
		};

		return preg_replace_callback($expr, $callback, $string);
	}

	/**
	 * @param string $version
	 * @return string
	 */
	private static function getIHJSignature($version = 'User')
	{
		$file = ucfirst(strtolower($version)) . '.txt';
		$path = './res/msg/signature' . $file;
		if (file_exists($path))
			return file_get_contents($path);
		else
			return 'Error: The signature file signature' . $file . '.txt does not exist.';
	}

	/**
	 * @param $orgaUser
	 */
	public static function sendRestPass($orgaUser)
	{
		MessageCenter::sendMail(
			$orgaUser->email,
			'ichhelfe.jetzt: Neues Kennwort erstellen',
			MessageCenter::personalizeMsg('resetPass', [
				'1' => $orgaUser->firstname . ' ' . $orgaUser->lastname,
				'2' => $orgaUser->orga->name,
				'3' => URL . '/newPass/' . $orgaUser->pwreset_token
			]) . MessageCenter::getIHJSignature('Orga')
		);
	}

	/**
	 * @param $donator
	 * @param $donations
	 */
	public static function sendDonatorVerification($donator, $donations)
	{

		$places = array_values(array_values($donations)[0]->sharedPlaceList);
		$place = $places[0]['name'];

		$verificationLink = URL . '/manage/donator/' . $donator->id . '?action=verify' . '&utoken=' . $donator->token;
		$verificationLink_html = '<a href="' . $verificationLink . '">' . $verificationLink . '/</a>';
		$deactivationLink = URL . '/manage/donator/' . $donator->id . '?action=deactivate' . '&utoken=' . $donator->token;
		$deactivationLink_html = '<a href="' . $deactivationLink . '">' . $deactivationLink . '/</a>';

		$actions = [];

		$content_plain = MessageCenter::personalizeMsg('donatorVerification', [
				'1' => $donator->firstname,
				'2' => $place,
				'3' => MessageCenter::generateDonationList($donations, $actions)["plain"],
				'4' => $verificationLink,
				'5' => $deactivationLink
			]) . MessageCenter::getIHJSignature();

		$content_html = MessageCenter::personalizeMsg('donatorVerification', [
				'1' => $donator->firstname,
				'2' => $place,
				'3' => MessageCenter::generateDonationList($donations, $actions)["html"],
				'4' => $verificationLink_html,
				'5' => $deactivationLink_html
			]) . MessageCenter::getIHJSignature();
		$content_html = nl2br($content_html);

		$success = MessageCenter::sendMail(
			$donator->email,
			'Dein Hilfsangebot auf ichhelfe.jetzt',
			$content_plain,
			null,
			$content_html
		);

		if ($success) {
			$donator['sent_verification_mail'] = R::isoDateTime();
			R::store($donator);
		}

	}

	/**
	 * @param $donator
	 * @param $donations
	 */
	public static function sendDonatorDeactivation($donator)
	{

		$content_plain = MessageCenter::personalizeMsg('donatorDeactivation', [
				'1' => $donator->firstname
			]) . MessageCenter::getIHJSignature();

		$content_html = MessageCenter::personalizeMsg('donatorDeactivation', [
				'1' => $donator->firstname
			]) . MessageCenter::getIHJSignature();
		$content_html = nl2br($content_html);

		$success = MessageCenter::sendMail(
			$donator->email,
			'Dein Account wurde gelöscht',
			$content_plain,
			null,
			$content_html
		);

	}

	/**
	 * @param $donation
	 */
	public static function sendDonationDeactivation($donation)
	{

		$actions = [];

		$content_plain = MessageCenter::personalizeMsg('donationDeactivation', [
				'1' => $donation->donator->firstname,
				'2' => MessageCenter::generateDonationList([$donation], $actions)["plain"]
			]) . MessageCenter::getIHJSignature();

		$content_html = MessageCenter::personalizeMsg('donationDeactivation', [
				'1' => $donation->donator->firstname,
				'2' => MessageCenter::generateDonationList([$donation], $actions)["html"]
			]) . MessageCenter::getIHJSignature();
		$content_html = nl2br($content_html);

		$success = MessageCenter::sendMail(
			$donation->donator->email,
			'Deine Spende wurde gelöscht',
			$content_plain,
			null,
			$content_html
		);

	}

	public static function generateDonationList($donations, $actions, $purpose = null)
	{
		$string_plain = "--------------";
		$string_html = "--------------";

		foreach ($donations as $donation) {

			$places = array_values($donation->sharedPlaceList);
			$place = $places[0]['name'];

			// title
			$string_plain .= "\n\n ★ " . $donation->category->title . ": " . $donation->description . " (in " . $place . ")";
			$string_html .= "<br><br>" . $donation->category->title . ": " . $donation->description . " (in " . $place . ")<br>";

			// action links
			foreach ($actions as $action => $label) {

				if ($action == 'extend' && $donation->state == 'ACTIVE' && $purpose != "DEM") continue;

				$actionLink = URL . '/manage/' . $donation->getMeta('type') . '/' . $donation->id . '?action=' . $action . '&utoken=' . $donation->donator->token;

				$string_plain .= "\n" . $label . ": " . $actionLink;

				$string_html .= '<span style="font-size: .6em">▸ <a href="' . $actionLink . '">' . $label . '</a></span>&nbsp;&nbsp;&nbsp;&nbsp;';
			}

		}

		$string_plain .= "\n\n--------------";
		$string_html .= "<br><br>--------------";

		return array("plain" => $string_plain, "html" => $string_html);
	}

	public static function sendDonationFeedbackRequest($donator, $donations)
	{

		$actions = [
			"extend" => 'Hilfsangebot ist noch aktuell',
			"deactivate" => 'Hilfsangebot löschen'
		];

		$deactivationLink = URL . '/manage/donator/' . $donator->id . '?action=deactivate' . '&utoken=' . $donator->token;
		$deactivationLink_html = '<a href="' . $deactivationLink . '">' . $deactivationLink . '/</a>';

		$content_plain = MessageCenter::personalizeMsg('donationFeedbackRequest', [
				'1' => $donator->firstname,
				'2' => MessageCenter::generateDonationList($donations, $actions)["plain"],
				'3' => $deactivationLink
			]) . MessageCenter::getIHJSignature();

		$content_html = MessageCenter::personalizeMsg('donationFeedbackRequest', [
				'1' => $donator->firstname,
				'2' => MessageCenter::generateDonationList($donations, $actions)["html"],
				'3' => $deactivationLink_html
			]) . MessageCenter::getIHJSignature();
		$content_html = nl2br($content_html);

		$success = MessageCenter::sendMail(
			$donator->email,
			'Was ist aus deinen Hilfsangeboten geworden?',
			$content_plain,
			null,
			$content_html
		);

		if ($success) {
			// remember when last mail sent
			foreach ($donations as $donation) {
				$donation['sent_donation_feedback_mail'] = R::isoDateTime();
				R::store($donation);
			}
		}

	}

	public static function sendDonationExtensionMail($donator, $donations)
	{

		$actions = [
			"extend" => 'Hilfsangebot ist noch aktuell',
			"deactivate" => 'Hilfsangebot löschen'
		];

		$deactivationLink = URL . '/manage/donator/' . $donator->id . '?action=deactivate' . '&utoken=' . $donator->token;
		$deactivationLink_html = '<a href="' . $deactivationLink . '">' . $deactivationLink . '/</a>';

		$content_plain = MessageCenter::personalizeMsg('donationExtensionRequest', [
				'1' => $donator->firstname,
				'2' => MessageCenter::generateDonationList($donations, $actions, 'DEM')["plain"],
				'3' => $deactivationLink_html
			]) . MessageCenter::getIHJSignature();

		$content_html = MessageCenter::personalizeMsg('donationExtensionRequest', [
				'1' => $donator->firstname,
				'2' => MessageCenter::generateDonationList($donations, $actions, 'DEM')["html"],
				'3' => $deactivationLink_html
			]) . MessageCenter::getIHJSignature();
		$content_html = nl2br($content_html);

		$success = MessageCenter::sendMail(
			$donator->email,
			'Bitte bestätige die Aktualität deiner Hilfsangebote',
			$content_plain,
			null,
			$content_html
		);

		if ($success) {
			// remember when last mail sent
			foreach ($donations as $donation) {
				$donation['sent_donation_extension_mail'] = R::isoDateTime();
			}
			R::store($donation);
		}

	}

	public static function sendDonationsOverview($donator, $donations)
	{

		$actions = [
			"extend" => 'Spende ist noch zu haben',
			"deactivate" => 'Spende löschen'
		];

		$deactivationLink = URL . '/manage/donator/' . $donator->id . '?action=deactivate' . '&utoken=' . $donator->token;
		$deactivationLink_html = '<a href="' . $deactivationLink . '">' . $deactivationLink . '/</a>';

		$content_plain = MessageCenter::personalizeMsg('donationOverview', [
				'1' => $donator->firstname,
				'2' => MessageCenter::generateDonationList($donations, $actions)["plain"],
				'3' => $deactivationLink
			]) . MessageCenter::getIHJSignature();

		$content_html = MessageCenter::personalizeMsg('donationOverview', [
				'1' => $donator->firstname,
				'2' => MessageCenter::generateDonationList($donations, $actions)["html"],
				'3' => $deactivationLink_html
			]) . MessageCenter::getIHJSignature();
		$content_html = nl2br($content_html);

		$success = MessageCenter::sendMail(
			$donator->email,
			'Übersicht deiner Hilfsangebote auf ichhelfe.jetzt',
			$content_plain,
			null,
			$content_html
		);

		if ($success) {
			$donator['sent_donation_overview_mail'] = R::isoDateTime();
			R::store($donator);
		}

	}

	public static function sendOrgaRegistered($orgaUser)
	{
		MessageCenter::sendMail(
			$orgaUser->email,
			'Registrierung auf ichhelfe.jetzt',
			MessageCenter::personalizeMsg('orgaRegistered', [
				'1' => $orgaUser->firstname . ' ' . $orgaUser->lastname,
				'2' => $orgaUser->orga->name,
				'3' => URL . '/verifyMail/' . $orgaUser->token
			]) . MessageCenter::getIHJSignature('Orga')
		);
	}

	/**
	 * generates personalized pdf file with activation code from template
	 * @param $orga
	 */
	public static function generateLetter($orga)
	{
		$orgaUser = array_values($orga
			->withCondition(' role = ? ', ['orga_owner'])
			->ownUserList)[0];

		if (!isset($orgaUser))
			exit('No orga user specified.');

		$pdf = new FPDF();
		$pdf->AddFont('opensans', '', 'OpenSans.php');

		$pdf->SetMargins(0, 0);
		$pdf->SetAutoPageBreak(false);
		$pdf->AddPage();

		//template:
		$pdf->Image("res/img/letter.png", 0, 0, 210);

		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('opensans', '', 10);

		$line = 68;
		$step = 5;

		$pdf->Text(20, $line, utf8_decode($orga->name));
		$line += $step;
		$pdf->Text(20, $line, utf8_decode($orgaUser->firstname . ' ' . $orgaUser->lastname));
		$line += $step;
		$pdf->Text(20, $line, utf8_decode($orga->address));
		$line += $step;
		if (isset($orga->addition) && $orga->addition != '') {
			$pdf->Text(20, $line, utf8_decode($orga->addition));
			$line += $step;
		}
		$pdf->Text(20, $line, $orga->zip . ' ' . utf8_decode($orga->city));


		$pdf->Text(20, 124, 'Guten Tag ' . utf8_decode($orgaUser->firstname . ' ' . $orgaUser->lastname) . ',');

		$pdf->SetTextColor(126, 55, 89);
		$pdf->SetFont('opensans', '', 16);
		$pdf->Text(30, 163, 'http://ichhelfe.jetzt/verifizieren');
		$pdf->Text(30, 183, $orga->code);

		$filename = '1001000000000-ihj_' . time() . '#DPtb#.pdf';

		// make sure you have write permissions in the destination directory!
		$pdf->Output('tmp/' . $filename, 'F');

		$orga->activationFile = $filename;
		R::store($orga);

		MessageCenter::sftpFile('tmp/' . $filename);
	}

	/**
	 * moves generated PDF to stfp-location and deletes it locally
	 * @param $filename
	 */
	private static function sftpFile($filename)
	{
		$sftp = new Net_SFTP('api.onlinebrief24.de');
		if (!$sftp->login('johannes.bittner@verbicur.de', 'mPIV5XBkgVgord6y')) {
			exit('Login Failed');
		}

		$sftp->chdir('upload/api');
		if ($sftp->put(basename($filename), $filename, NET_SFTP_LOCAL_FILE)) {
			unlink($filename);
		}
	}
}