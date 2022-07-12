<?php 

namespace Typemill\Static;

class Languages
{
  	public static function whichLanguage()
  	{
    	# Check which languages are available
    	$langs = [];
    	$path = __DIR__ . '/author/languages/*.yaml';
    	
    	foreach (glob($path) as $filename) 
    	{
      		$langs[] = basename($filename,'.yaml');
    	}
    
    	# Detect browser language
    	$accept_lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : false;
    	$lang = in_array($accept_lang, $langs) ? $accept_lang : 'en';

    	return $lang;
  	}
}