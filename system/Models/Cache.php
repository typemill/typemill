<?php

namespace System\Models;

class Cache
{
	private $cachePath;

	public function __construct()
	{
		$cachePath = getcwd() . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
		if(!is_dir($cachePath)){ 
			mkdir($cachePath, 0774, true) or die('Please create a cache folder in your root and make it writable.');
		}
		is_writable($cachePath) or die('Your cache folder is not writable.');
		$this->cachePath = $cachePath;
	}
	
	public function validate()
	{
		if(isset($_SERVER['HTTP_CACHE_CONTROL']) && $_SERVER['HTTP_CACHE_CONTROL'] == 'max-age=0')
		{
			return false;
		}
		
		$requestFile = $this->cachePath.'request.txt';
		if(!file_exists($requestFile))
		{
			$this->writeFile($requestFile, time());
			return false;
		}
		
		$lastRequest = file_get_contents($requestFile);
		if(time() - $lastRequest > 600)
		{
			return false;
		}

		return true;
	}
	
	public function refresh($data, $name)
	{
		$sData 			= serialize($data);
		$dataFile 		= $this->cachePath.$name.'.txt';
		$requestFile	= $this->cachePath.'request.txt';
		
		$this->writeFile($dataFile, $sData);
		$this->writeFile($requestFile, time());		
	}
		
	public function getData($name)
	{
		if (file_exists($this->cachePath.$name.'.txt'))
		{
			$data = file_get_contents($this->cachePath.$name.'.txt');
			$data = unserialize($data);
			return $data;
		}
		return false;
	}
	
	public function clearData($name)
	{
		/* todo */
	}
	
	public function clearAll()
	{
		/* todo */
	}
	
	public function writeFile($file, $data)
	{
		$fp = fopen($file, "w");
		fwrite($fp, $data);
		fclose($fp);
	}
}

?>