<?php

namespace Typemill\Static;

## MOVE TO MIDDLEWARE IN 2.2.0

class Urlinfo
{
	# we need to get urlinfos to use in frontend and to inject into assets and container before middleware starts and request-object is available.
	public static function getUrlInfo($basepath, $uri, $settings)
	{
		# remove basic auth credentials
		$uri 			= $uri->withUserInfo('');

		# remove standard ports to fix csp error
		# alternatively add ports to csp header
		$uri 			= self::removeStandardPorts($uri);

		$currentpath 	= $uri->getPath();
		$route 			= $currentpath;
		if(strpos($currentpath, $basepath) === 0)
		{
			$route 		= substr_replace($currentpath, '', 0, strlen($basepath));
		}

		$query 			= $uri->getQuery();
		parse_str($query, $params);

		# proxy detection
		if(isset($settings['proxy']) && $settings['proxy'])
		{
			$trustedProxies	= ( isset($settings['trustedproxies']) && !empty($settings['trustedproxies']) ) ? explode(",", $settings['trustedproxies']) : [];

			if(self::checkIp($trustedProxies))
			{
				$uri 		= self::updateHost($uri);

				$uri 		= self::updateProto($uri);

				$uri  		= self::updatePort($uri);

				$basepath 	= self::updateBasepath($basepath);
			}
		}

		$scheme 		= $uri->getScheme();
		$authority 		= $uri->getAuthority();
		$protocol 		= ($scheme ? $scheme . ':' : '') . ($authority ? '//' . $authority : '');
		$baseurl 		= $protocol . $basepath;
		$currenturl 	= $baseurl . $route;

		return [
			'basepath' 		=> $basepath,
			'currentpath' 	=> $currentpath,
			'route' 		=> $route,
			'scheme' 		=> $scheme,
			'authority' 	=> $authority,
			'protocol' 		=> $protocol,
			'baseurl' 		=> $baseurl,
			'baseurlWithoutProxy' => false, # add the base url without proxy maybe needed for license?
			'currenturl' 	=> $currenturl,
			'params' 		=> $params
		];
	}

	private static function removeStandardPorts($uri)
	{
		$port = $uri->getPort();
		if (!$port || $port == 80 || $port == 443)
		{
			$uri = $uri->withPort(null);
		}

		return $uri;
	}

	private static function updateProto($uri)
	{
		# get host from proxy
		$host 	= $_SERVER['HTTP_X_FORWARDED_HOST'] ?? null;
		if (
			$host
		)
		{
			$host = trim(current(explode(',', $host)));

			$pos = strpos($host, ':');
			if ($pos !== false) 
			{
				$host = strstr($host, ':', true);
			}
			$uri = $uri->withHost($host);
		}

		return $uri;
	}

	private static function updateHost($uri)
	{
		# get scheme from proxy
		$scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;
		if (
			$scheme
			&& in_array($scheme, ['http', 'https'])
		)
		{
			$uri = $uri->withScheme($scheme);
		}

		return $uri;
	}

	private static function updateBasepath($basepath)
	{
		$basepath = "";

		# if proxy has basepath, then
		if (isset($_SERVER['HTTP_X_FORWARDED_PREFIX']))
		{
			# Use X-Forwarded-Prefix if available
			$basepath = rtrim($_SERVER['HTTP_X_FORWARDED_PREFIX'], '/') . '/';
		}

		return $basepath;				
	}

    protected static function updatePort($uri)
    {
		$port = $_SERVER['HTTP_X_FORWARDED_PORT'] ?? null;

        if ($port)
        {
            $port = trim(current(explode(',', $port)));

            if (preg_match('/^\d+\z/', $port))
            {
                return $uri->withPort((int) $port);
            }
        }
        
        return $uri;
    }

	private static function checkIp($trustedProxies)
	{
		# optionally check trusted proxies
		$ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
		if (
			$ipAddress 
			&& !empty($trustedProxies)
			&& !in_array($ipAddress, $trustedProxies)
		)
		{            
			return false;
		}

		return true;
	}
}