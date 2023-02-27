<?php

namespace Typemill\Static;

use \URLify;

class Slug
{		
	public static function getStringParts($name)
	{
		return preg_split('/[\-\.\_\=\+\?\!\*\#\(\)\/ ]/',$name);
	}
	
	public static function getFileType($fileName)
	{
		$parts = preg_split('/\./',$fileName);
		return end($parts);
	}
	
	public static function splitFileName($fileName)
	{
		$parts = preg_split('/\./',$fileName);
		return $parts;
	}
	public static function getNameWithoutType($fileName)
	{
		$parts = preg_split('/\./',$fileName);
		return $parts[0];
	}

	public static function createSlug($name, $language = 'en')
	{
		$name = iconv(mb_detect_encoding($name, mb_detect_order(), true), "UTF-8", $name);

		return URLify::filter(
						$name,
						$length = 60, 
						$language, 
						$file_name = false, 
						$use_remove_list = false,
						$lower_case = true, 
						$treat_underscore_as_space = true 
					);
	}
}