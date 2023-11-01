<?php

namespace Typemill\Middleware;

#use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Psr7\Response;
use Slim\Routing\RouteParser;
use Gregwar\Captcha\CaptchaBuilder;

class securityMiddleware implements MiddlewareInterface
{
	protected $router;

	protected $settings;
	
#	protected $flash;

	public function __construct(RouteParser $router, $settings)
	{
		$this->router 		= $router;
		
		$this->settings 	= $settings;
		
#		$this->flash 		= $flash;
	}
	
	public function process(Request $request, RequestHandler $handler) :Response
	{
		if($request->getMethod() == 'POST')
		{
			$params 	= $request->getParsedBody();
			$referer 	= $request->getHeader('HTTP_REFERER');

			if(!$referer OR $referer == '')
			{
				$response = new Response();
				
				return $response->withHeader('Location', $this->router->urlFor('auth.login'))->withStatus(302);
			}

			# simple bot check with honeypot
			if( 
				(isset($params['personal-honey-mail']))
				&& (null !== $params['personal-honey-mail']) 
				&& ($params['personal-honey-mail'] != '') 
			)
			{
				if(isset($this->settings['securitylog']) && $this->settings['securitylog'])
				{
					\Typemill\Static\Helpers::addLogEntry('honeypot ' . $referer[0]);
				}

				$response = new Response();
				
				return $response->withHeader('Location', $referer[0])->withStatus(302);
			}

		    # check captcha
			if(isset($_SESSION['captcha']))
			{
				# if captcha field was filled correctly
				if( 
					(isset($params['captcha']))
					&& (null !== $params['captcha']) 
					&& \Gregwar\Captcha\PhraseBuilder::comparePhrases($_SESSION['phrase'], $params['captcha'] )
				)
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

					if(
						isset($this->settings['securitylog']) 
						&& $this->settings['securitylog']
					)
					{
						\Typemill\Static\Helpers::addLogEntry('wrong captcha ' . $referer[0]);
					}

			        # and add message that captcha is empty
#					$this->flash->addMessage('error', 'Captcha is wrong.');

					$response = new Response();
					
					return $response->withHeader('Location', $referer[0])->withStatus(302);
				}
			}

/*
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
*/			
		}
		
		$response = $handler->handle($request);

		return $response;		
	}
}