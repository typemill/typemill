<?php

namespace Typemill\Models;

class WriteCache extends Write
{
	/**
	 * Validates, if the cache is valid or invalid and has to be refreshed
	 * @param int $duration how many seconds the cache is valid.
	 * @return boolean for an invalid cache (false) and for a valid cache (true).
	 */
	public function validate($folderName, $fileName, $duration)
	{		
		if(isset($_SERVER['HTTP_CACHE_CONTROL']) && $_SERVER['HTTP_CACHE_CONTROL'] == 'max-age=0')
		{
			return false;
		}

		if(!$this->checkPath($folderName))
		{
			return false;
		}
		
		if(!$this->checkFile($folderName, $fileName))
		{
			$this->writeFile($folderName, $fileName, time());
			return false;
		}
		
		$lastRefresh = file_get_contents($folderName . DIRECTORY_SEPARATOR . $fileName);
		
		if(time() - $lastRefresh > $duration)
		{
			return false;
		}
		
		return true;
	}

	/**
	 * Updates a cache file.
	 * Serializes an object and writes it to the cache file together with a file that holds the last refresh time.
	 * @param object $cacheData has to be an object (e.g. navigation object).
	 * @param string $cacheFile has to be the name of the file you want to update (in case there are more than one cache files.
	 */
	public function updateCache($folderName, $cacheFileName, $requestFileName, $cacheData)
	{
		$sCacheData = serialize($cacheData);
		$this->writeFile($folderName, $cacheFileName, $sCacheData);
		if($requestFileName)
		{
			$this->writeFile($folderName, $requestFileName, time());
		}
	}

	/**
	 * Get the recent cache.
	 * Takes a filename, gets the file and unserializes the cache into an object.
	 * @param string $fileName is the name of the cache file.
	 */
	public function getCache($folderName, $cacheFileName)
	{
		$sCacheData = $this->getFile($folderName, $cacheFileName);
		if($sCacheData)
		{
			return unserialize($sCacheData);
		}
		return false;		
	}

    /**
	  * @todo Create a function to clear a specific cache file
     */	
	public function clearCache($name)
	{
	}
	
    /**
	  * @todo Create a function to clear all cache files
     */		
	public function clearAllCacheFiles()
	{
	}
}