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

	/*
	* Formats the numeric order prefix for content files and folders.
	* Pads single digits with a leading zero (01- to 09-), wider numbers
	* are used as they are (10- to 99- and 100+). Natural sorting in
	* Folder::scanFolder() keeps mixed widths in the correct order.
	*/
	public static function getOrderPrefix($index)
	{
		return ($index < 10) ? '0' . $index : (string)$index;
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