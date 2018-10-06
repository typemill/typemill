# Add New Routes

You can add your own routes to TYPEMILL with plugin. Simply use the public static method `addNewRoutes()` like this: 

````
public static function addNewRoutes()
{
	return array(
		'httpMethod'	=> 'get', 
		'route' 		=> '/myroute', 
		'class' 		=> 'Plugins\Myplugin\MypluginController:index'
	);
}
````

The method returns an array with three values:

* **httpMethod**: Values can be 'get', 'post', 'put', 'delete', 'head', 'patch' or 'options'.
* **route**: Value can be a valid route like '/this/is/my/route'. Please refer to the [slim documentation](https://www.slimframework.com/docs/v3/objects/router.html) to find out, which routes are accepted.
* **class**: This is the class that should be called with the route. It accepts the fully classified namespace of the class followed by a colon and the method within the class, that should be called.

You can also add multiple routes with a multi-dimensional array like this:

````
public static function addNewRoutes()
{
	return array(
		array(
			'httpMethod'	=> 'get', 
			'route' 		=> '/myroute', 
			'class' 		=> 'Plugins\Myplugin\MypluginController:index'
		),
		array(
			'httpMethod'	=> 'post',
			'route' 		=> '/myroute', 
			'class' 		=> 'Plugins\Myplugin\MypluginController:save'
		)
	);
}
````

To get your new route working, you have to create a php-file in your plugin with the name `MypluginCotroller.php` and a class like that: 

````
<?php

namespace Plugins\Myplugin;

class MypluginController
{
	public function index()
	{
		return die('I am the new plugin controller');
	}
}
````