<?php

namespace Typemill\Static;

class Plugins
{
	public static function loadPlugins()
	{
		$rootpath 			= getcwd();
		$pluginFolder 		= self::scanPluginFolder($rootpath);
		$classNames 		= [];
		
		# iterate over plugin folders
		foreach($pluginFolder as $plugin)
		{
			$className = '\\Plugins\\' . $plugin . '\\' . $plugin;
			
			# if plugin-class exists, add classname to array
			if(class_exists($className))
			{
				$classNames[]	= ['className' => $className, 'name' => $plugin];
			}
		}

		return $classNames;
	}

	public static function scanPluginFolder($rootpath)
	{
		$pluginsDir = $rootpath . '/plugins';
		
		# check if plugins directory exists
		if(!is_dir($pluginsDir)){ return array(); }
		
		# get all plugin folders
		$plugins = array_diff(scandir($pluginsDir), array('..', '.'));
		
		return $plugins;
	}

	public static function getNewRoutes($className, $routes)
	{
		# if route-method exists in plugin-class
		if(method_exists($className, 'addNewRoutes'))
		{
			# add the routes
			$pluginRoutes = $className::addNewRoutes();

			foreach($pluginRoutes as $pluginRoute)
			{
				if(self::checkRouteArray($routes,$pluginRoute))
				{
					$routeType  			= (substr($pluginRoute['route'], 0,5) == '/api/') ? 'api' : 'web';
					$pluginRoute['route'] 	= strtolower($pluginRoute['route']);
					$routes[$routeType][] 	= $pluginRoute;
				}
			}
		}
		
		return $routes;
	}
	
	public static function getNewMiddleware($className, $middleware)
	{
		if(method_exists($className, 'addNewMiddleware'))
		{
			$pluginMiddleware = $className::addNewMiddleware();
			
			if($pluginMiddleware)
			{
				$middleware[] = $pluginMiddleware;				
			}
		}
		
		return $middleware;
	}

	public static function getPremiumLicense($className)
	{
		$premiumlist = [
			'\Plugins\html\html' => 'MAKER'
		];

		if(isset($premiumList['className']))
		{
			return $premiumList['className'];
		}

		if(method_exists($className, 'setPremiumLicense'))
		{
			return $className::setPremiumLicense();			
		}
		
		return false;
	}
	
	private static function checkRouteArray($routes,$route)
	{
		if( 
			isset($route['httpMethod']) AND in_array($route['httpMethod'], array('get','post','put','delete','head','patch','options'))
			AND isset($route['route']) AND is_string($route['route'])
			AND isset($route['class']) AND is_string($route['class'])
			AND isset($route['name']) AND is_string($route['name'])
		)
		{
			return true;
		}
		return false;
	}
	
	private function in_array_r($needle, $haystack, $strict = false) 
	{		
		foreach ($haystack as $item)
		{
			if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && $this->in_array_r($needle, $item, $strict)))
			{
				return true;
			}
		}
		return false;		
	}	
}