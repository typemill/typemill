# Add New Middleware

If you are not familiar with the concept of middleware, please read the [documentation of slim](https://www.slimframework.com/docs/v3/concepts/middleware.html) first. With middleware you can add some logic that is added to the live cycle of the application. Some examples for middleware are:

* Authenticate a user.
* Add a CSFR protection for input fields (already exists in TYPEMILL).
* Validate user input (already exists in TYPEMILL).
* Add an error handler (already exists in TYPEMILL).

The concept of middleware is a bit harder to understand, but to add middleware with a plugin is pretty easy with the method `addNewMiddleware()`:

````
public static function addNewMiddleware()
{
	return array(
		'classname' => 'Plugins\MyPlugin\MyMiddleware', 
		'params' => false
	);
}

````

The method returns and array again and accepts to values:

* **classname**: The fully qualified name of the class, that should be called.
* **params**: False or an array.

You can create a new file `MyMiddleware.php` in your plugin and add a middleware logic like this:

````
<?php

namespace Plugins\MyPlugin;

class MyMiddleware 
{	
    public function __invoke(\Slim\Http\Request $request, \Slim\Http\Response $response, $next)
	{
		// do something here 
     	return $next($request, $response);		
    }
}
````

The support of middleware in TYPEMILL is pretty basic right now and it has some limitations. The most important limitation:

* Right now you can only add global middleware. You cannot add middleware only to specific routes.
* The order, when the middleware is executed, is fixed and you cannot manipulate it. This means, that all the plugin middleware is executed after the TYPEMILL middleware. And the order depends on when your plugin get's loaded.

The middleware-support in TYPEMILL will be improved in future. For now, only use it if you really know what you wanna do. You can also add a new issue in github, if you miss anything.