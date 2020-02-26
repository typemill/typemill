<?php

namespace Typemill\Models;

# this check is not in use anymore (was in use to check and store latest version in user settings on page refresh)

class VersionCheck
{
	function checkVersion($url)
	{
		$opts = array(
			'http'=>array(
				'method' 	=> "GET",
				'header'	=> "Referer: $url\r\n"
			)
		);
		
		$context = stream_context_create($opts);
		
		if(false === ($version = @file_get_contents('https://typemill.net/api/v1/checkversion', false, $context)))
		{
			return false;
		}
		$version = json_decode($version);
		die();

		return $version->system->typemill;		
	}
}