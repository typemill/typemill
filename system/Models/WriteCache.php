<?php

namespace Typemill\Models;

use Typemill\Models\WriteYaml;

class WriteCache extends Write
{
	/**
	 * Validates, if the cache is valid or invalid and has to be refreshed
	 * @param int $duration how many seconds the cache is valid.
	 * @return boolean for an invalid cache (false) and for a valid cache (true).
	 */
	public function validate($folderName, $fileName, $duration)
	{
		if(isset($_SERVER['HTTP_CACHE_CONTROL']) && ( $_SERVER['HTTP_CACHE_CONTROL'] == 'max-age=0' OR $_SERVER['HTTP_CACHE_CONTROL'] == 'no-cache' ))
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
		if($this->writeFile($folderName, $cacheFileName, $sCacheData))
		{
			if($requestFileName)
			{
				$this->writeFile($folderName, $requestFileName, time());
			}

			return true;
		}
		return false;
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

	public function getCachedStructure()
	{
		return $this->getCache('cache', 'structure.txt');
	}
	
    /**
	  * @todo Create a function to clear all cache files
     */		
	public function deleteCacheFiles($dir)
	{
		$iterator 	= new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
		$files 		= new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::CHILD_FIRST);
		
		$error = false;

		foreach($files as $file)
		{
		    if ($file->isDir())
		    {
		    	if(!rmdir($file->getRealPath()))
		    	{
		    		$error = 'Could not delete some folders.';
		    	}
		    }
		    elseif($file->getExtension() !== 'css')
		    {
				if(!unlink($file->getRealPath()) )
				{
					$error = 'Could not delete some files.';
				}
		    }
		}

		return $error;
	}
}