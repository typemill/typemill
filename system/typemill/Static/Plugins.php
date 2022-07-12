<?php

namespace Typemill\Static;

class Plugins
{
	public static function loadPlugins($rootpath)
	{
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
			
			# multi-dimensional or simple array of routes
			if(isset($pluginRoutes[0]))
			{
				# if they are properly formatted, add them to routes array
				foreach($pluginRoutes as $pluginRoute)
				{
					if(self::checkRouteArray($routes,$pluginRoute))
					{
						$pluginRoute['route'] 	= strtolower($pluginRoute['route']);
						$routes[] 				= $pluginRoute;
					}
				}
			}
			elseif(is_array($routes))
			{
				if(self::checkRouteArray($routes,$pluginRoutes))
				{
					$pluginRoutes['route'] 		= strtolower($pluginRoutes['route']);
					$routes[] 					= $pluginRoutes;
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
	
	private static function checkRouteArray($routes,$route)
	{
		if( 
			isset($route['httpMethod']) AND in_array($route['httpMethod'], array('get','post','put','delete','head','patch','options'))
			AND isset($route['route']) AND is_string($route['route'])
			AND isset($route['class']) AND is_string($route['class']))
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