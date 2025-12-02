<?php

namespace Typemill\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Views\Twig;

class OldInputMiddleware
{
	protected $view;
	
	public function __construct(Twig $view)
	{
		$this->view = $view;
	}
	
	public function __invoke(Request $request, RequestHandler $handler)
	{
		if(isset($_SESSION) && isset($_SESSION['old']))
		{
			$this->view->getEnvironment()->addGlobal('old', $_SESSION['old']);
		}

		$response = $handler->handle($request);

		# unset old values after the request is processed. This keeps old values also if there is a redirect to another page and before the page is rendered but removes the values on page refresh.
		if(isset($_SESSION))
		{
			unset($_SESSION['old']);

            $oldinput = $request->getParsedBody();
            if (!empty($oldinput) && is_array($oldinput))
 			{
                $oldinput = $this->sanitizeRecursive($oldinput);

				$_SESSION['old'] = $oldinput;
 			}
		}

		return $response;
	}

	private function sanitizeRecursive(array $oldinput): array
	{
	    $output = [];

	    foreach ($oldinput as $key => $value)
	    {
	        # Skip sensitive keys (passwords, tokens, etc.)
	        if ( stripos($key, 'pass') !== false || 
	        	 stripos($key, 'token') !== false ||
	        	 stripos($key, 'user') !== false
	        	)
	        {
	            continue;
	        }

	        # Clean key name to avoid weird characters
	        $safeKey = preg_replace('/[^\w\-\.]/u', '', (string)$key);

	        # If it's a nested array (e.g. field groups), sanitize recursively
	        if (is_array($value))
	        {
	            $output[$safeKey] = $this->sanitizeRecursive($value);
	            continue;
	        }

	        # Sanitize string values
	        if (is_string($value))
	        {
	            # 1) Remove HTML tags
	            $value = strip_tags($value);

	            # 2) Remove control chars (null bytes, etc.)
	            $value = preg_replace('/[\x00-\x1F\x7F]+/u', '', $value);

	            # 3) Remove dangerous characters
	            $value = str_replace(['<', '>', '`', "\x00", "\r", "\n"], '', $value);

	            # 4) Optionally remove quotes to prevent attribute injection
	            $value = str_replace(['"', "'"], '', $value);

	            # 5) Trim & limit length
	            $value = trim($value);
	            if (mb_strlen($value) > 10000)
	            {
	                $value = mb_substr($value, 0, 10000);
	            }
	        }

	        $output[$safeKey] = $value;
	    }

	    return $output;
	}
}