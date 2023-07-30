<?php

namespace Typemill\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Typemill\Static\Session;
use Typemill\Models\User;

class SessionMiddleware implements MiddlewareInterface
{
    protected $segments;

    protected $route;
    
    public function __construct($segments, $route)
    {
        $this->segments = $segments;

        $this->route = $route;
    }
    
	public function process(Request $request, RequestHandler $handler) :response
    {
        # start session
        Session::startSessionForSegments($this->segments, $this->route);

        $authenticated = ( 
                (isset($_SESSION['username'])) && 
                (isset($_SESSION['login'])) 
            )
            ? true : false;

        if($authenticated)
        {
            # add userdata to the request for later use
            $user = new User();

            if($user->setUser($_SESSION['username']))
            {
                $userdata = $user->getUserData();

                $request = $request->withAttribute('c_username', $userdata['username']);
                $request = $request->withAttribute('c_userrole', $userdata['userrole']);
            }
        }


		$response = $handler->handle($request);
	
		return $response;
    }
}