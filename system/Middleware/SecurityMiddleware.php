<?php

namespace Typemill\Middleware;

use Slim\Interfaces\RouterInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Gregwar\Captcha\CaptchaBuilder;

class securityMiddleware
{
	protected $router;
	protected $settings;
	protected $flash;

	public function __construct(RouterInterface $router, $settings, $flash)
	{
		$this->router 		= $router;
		$this->settings 	= $settings;
		$this->flash 		= $flash;
	}
	
	public function __invoke(Request $request, Response $response, $next)
	{
		if($request->isPost())
		{
			$referer = $request->getHeader('HTTP_REFERER');

			# check csrf protection
		    if( $request->getattribute('csrf_result') === false )
		    {
				$this->flash->addMessage('error', 'The form has a timeout. Please try again.');
				return $response->withRedirect($referer[0]);
		    }

			# simple bot check with honeypot
			if( (null !== $request->getParam('personal-honey-mail') ) && ($request->getParam('personal-honey-mail') != '') )
			{
				if(isset($this->settings['securitylog']) && $this->settings['securitylog'])
				{
					\Typemill\Models\Helpers::addLogEntry('honeypot ' . $referer[0]);
				}

				$this->flash->addMessage('notice', 'Hey honey, you made it right!');
				return $response->withRedirect($this->router->pathFor('home'));
			}

		    # check captcha
			if(isset($_SESSION['captcha']))
			{
				# if captcha field was filled correctly
				if( (null !== $request->getParam('captcha')) && \Gregwar\Captcha\PhraseBuilder::comparePhrases($_SESSION['phrase'], $request->getParam('captcha') ) )
				{
					# delete captcha because it is solved and should not show up again
					unset($_SESSION['captcha']);

					# delete phrase because can't use twice
					unset($_SESSION['phrase']);
				}
				else
				{
					# delete phrase because can't use twice, but keep captcha so it shows up again
			        unset($_SESSION['phrase']);

			        # set session to error
				    $_SESSION['captcha'] = 'error';

					if(isset($this->settings['securitylog']) && $this->settings['securitylog'])
					{
						\Typemill\Models\Helpers::addLogEntry('wrong captcha ' . $referer[0]);
					}

			        # and add message that captcha is empty
					$this->flash->addMessage('error', 'Captcha is wrong.');
					return $response->withRedirect($referer[0]);
				}
			}

			#check google recaptcha
			if( null !== $request->getParam('g-recaptcha-response') )
			{
				$recaptchaApi 		= 'https://www.google.com/recaptcha/api/siteverify';
				$settings			= $this->c->get('settings');
				$secret				= isset($settings['plugins'][$pluginName]['recaptcha_secretkey']) ? $settings['plugins'][$pluginName]['recaptcha_secretkey'] : false;
				$recaptchaRequest 	= ['secret' => $secret, 'response' => $request->getParam('g-recaptcha-response')];

				# use key 'http' even if you send the request to https://...
				$options = array(
					'http' => array(
						'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
						'method'  => 'POST',
						'content' => http_build_query($recaptchaRequest),
						'timeout' => 5
					)
				);

				$context  	= stream_context_create($options);
				$result 	= file_get_contents($recaptchaApi, false, $context);
				$result		= json_decode($result);
				
				if ($result === FALSE || $result->success === FALSE)
				{
					if(isset($this->settings['securitylog']) && $this->settings['securitylog'])
					{
						\Typemill\Models\Helpers::addLogEntry('wrong google recaptcha ' . $referer[0]);
					}

			        # and add message that captcha is empty
					$this->flash->addMessage('error', 'Captcha is wrong.');
					return $response->withRedirect($referer[0]);
				}
			}
		}

		return $next($request, $response);
	}
}