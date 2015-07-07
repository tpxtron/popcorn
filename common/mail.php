<?php

class mailer
{
	private $mail;
	private $twig;

	public function __construct() {
	}

	public function sendActivationMail($email, $key) {
		$this->init();

		$this->mail->setFrom("noreply@rollercoder.de", "popcorn.sipgate.net");
		$this->mail->addAddress($email);

		$this->mail->isHTML(true);

		$this->mail->Subject = "[popcorn.sipgate.net] Freischaltung";

		$this->mail->Body = $this->twig->render('mail_signup.html.twig',array("key"=>$key));
		$this->mail->AltBody = $this->twig->render('mail_signup.text.twig',array("key"=>$key));

		return $this->mail->send();
	}

	public function sendNewPasswordMail($email, $password) {
		$this->init();

		$this->mail->setFrom("noreply@rollercoder.de", "popcorn.sipgate.net");
		$this->mail->addAddress($email);

		$this->mail->isHTML(true);

		$this->mail->Subject = "[popcorn.sipgate.net] Neues Passwort";

		$this->mail->Body = $this->twig->render('mail_newpassword.html.twig',array("password"=>$password));
		$this->mail->AltBody = $this->twig->render('mail_newpassword.text.twig',array("password"=>$password));

		return $this->mail->send();
	}

	private function init() {
		$this->mail = new PHPMailer();
		$this->twig = new Twig_Environment(new Twig_Loader_Filesystem(dirname(__FILE__).'/templates'));
	}
}
