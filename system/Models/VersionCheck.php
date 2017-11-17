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
		
		try {
			$version = file_get_contents('http://typemill.net/tma1/checkversion', false, $context);

			if ($version)
			{
				$version = json_decode($version);			
				return $version->version;
			}
		} catch (Exception $e) {
			return false;
		}
	}
}