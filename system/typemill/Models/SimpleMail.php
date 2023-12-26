<?php

namespace Typemill\Models;

class SimpleMail
{
	private $from = false;

	private $reply = false;

	public $error;

	public function __construct($settings)
	{
		if(isset($settings['mailfrom']) && $settings['mailfrom'] != '')
		{
			$this->from = trim($settings['mailfrom']);

			if(isset($settings['mailfromname']) && $settings['mailfromname'] != '')
			{
				$this->from = '=?UTF-8?B?' . base64_encode($settings['mailfromname']) . '?= <' . trim($settings['mailfrom']) . '>';
			}
		}

		if(isset($settings['mailreply']) && $settings['mailreply'] != '')
		{
			$this->reply = trim($settings['mailreply']);
		}
	}

	public function sendEmail(string $to, string $subject, string $message)
	{
		if(!$this->from)
		{
			$this->error = 'You need to add a email address into the settings.';

			return false;
		}

		# 'Reply-To: webmaster@example.com' . "\r\n" .

		$headers 		= 'Content-Type: text/html; charset=utf-8' . "\r\n";
		$headers 		.= 'Content-Transfer-Encoding: base64' . "\r\n";
		$headers 		.= 'From: ' . $this->from . "\r\n";
		if($this->$reply)
		{
			$headers 		.= 'Reply-To: base64' . $this->reply . "\r\n";
		}
		$headers 		.= 'X-Mailer: PHP/' . phpversion();

		$subject 		= '=?UTF-8?B?' . base64_encode($subject) . '?=';
		$message 		= base64_encode($message);
		
		$send = mail($to, $subject, $message, $headers);

		if($send !== true)
		{
			$this->error = error_get_last()['message'];
		}

		return $send;
	}
}