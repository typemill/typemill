<?php

namespace Typemill\Models;

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
		
		if(false === ($version = @file_get_contents('http://typemill.net/api/v1/checkversion', false, $context)))
		{
			return false;
		}
		$version = json_decode($version);
		return $version->version;		
	}
}