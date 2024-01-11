<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;
use Typemill\Models\User;
use Typemill\Models\SimpleMail;
use Typemill\Static\Translations;

class ControllerApiTestmail extends Controller
{	
	public function send(Request $request, Response $response)
	{
		if(!isset($this->settings['mailfrom']) or !filter_var($this->settings['mailfrom'], FILTER_VALIDATE_EMAIL))
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('The from mail is missing or it is not a valid e-mail address.')
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$user 			= new User();
		$username 		= $request->getAttribute('c_username');

		if(!$user->setUser($username))
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('We did not find the a user or usermail.')
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$userdata 		= $user->getUserData();

		$mail 			= new SimpleMail($this->settings);

		$subject 		= Translations::translate('Testmail from Typemill');
		$message		= Translations::translate('This is a testmail from Typemill and if you read this e-mail, then everything works fine.');

		$send 			= $mail->send($userdata['email'], $subject, $message);

		if(!$send)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('We could not send the testmail to your e-mail address. Reason: ') . $mail->error
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
		}

		$response->getBody()->write(json_encode([
			'message' => Translations::translate('The testmail has been send, please check your inbox and your spam-folder to varify that you received the mail.')
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}
}