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
	
	public function getFreshStructure($contentPath, $uri)
	{
		# scan the content of the folder
		$pagetree = Folder::scanFolder('content');

		# if there is no content, render an empty page
		if(count($pagetree) == 0)
		{
			return false;
		}

		# get the extended structure files with changes like navigation title or hidden pages
		$yaml = new writeYaml();
		$extended = $yaml->getYaml('cache', 'structure-extended.yaml');

		# create an array of object with the whole content of the folder
		$structure = Folder::getFolderContentDetails($pagetree, $extended, $uri->getBaseUrl(), $uri->getBasePath());

		# now update the extended structure
		if(!$extended)
		{
			$extended = $this->createExtended($contentPath, $yaml, $structure);

			if(!empty($extended))
			{
				$yaml->updateYaml('cache', 'structure-extended.yaml', $extended);

				# we have to update the structure with extended again
				$structure = Folder::getFolderContentDetails($pagetree, $extended, $uri->getBaseUrl(), $uri->getBasePath());
			}
			else
			{
				$extended = false;
			}
		}

		# cache structure
		$this->updateCache('cache', 'structure.txt', 'lastCache.txt', $structure);

		if($extended && $this->containsHiddenPages($extended))
		{
			# generate the navigation (delete empty pages)
			$navigation = $this->createNavigationFromStructure($structure);

			# cache navigation
			$this->updateCache('cache', 'navigation.txt', false, $navigation);
		}
		else
		{
			# make sure no separate navigation file is set
			$this->deleteFileWithPath('cache' . DIRECTORY_SEPARATOR . 'navigation.txt');
		}
		
		# load and return the cached structure, because might be manipulated with navigation....
		$structure = $this->getCachedStructure();

		return $structure;
	}
	
	# creates a file that holds all hide flags and navigation titles 
	# reads all meta-files and creates an array with url => ['hide' => bool, 'navtitle' => 'bla']
	public function createExtended($contentPath, $yaml, $structure, $extended = NULL)
	{
		if(!$extended)
		{
			$extended = [];
		}

		foreach ($structure as $key => $item)
		{
			# $filename = ($item->elementType == 'folder') ? DIRECTORY_SEPARATOR . 'index.yaml' : $item->pathWithoutType . '.yaml';
			$filename = $item->pathWithoutType . '.yaml';

			if(file_exists($contentPath . $filename))
			{				
				# read file
				$meta = $yaml->getYaml('content', $filename);

				$extended[$item->urlRelWoF]['hide'] = isset($meta['meta']['hide']) ? $meta['meta']['hide'] : false;
				$extended[$item->urlRelWoF]['navtitle'] = isset($meta['meta']['navtitle']) ? $meta['meta']['navtitle'] : '';
			}

			if ($item->elementType == 'folder')
			{
				$extended 	= $this->createExtended($contentPath, $yaml, $item->folderContent, $extended);
			}
		}
		return $extended;
	}

	public function createNavigationFromStructure($navigation)
	{
		foreach ($navigation as $key => $element)
		{
			if($element->hide === true)
			{
				unset($navigation[$key]);
			}
			elseif(isset($element->folderContent))
			{
				$navigation[$key]->folderContent = $this->createNavigationFromStructure($element->folderContent);
			}
		}
		
		return $navigation;
	}

	# checks if there is a hidden page, returns true on first find
	protected function containsHiddenPages($extended)
	{
		foreach($extended as $element)
		{
			if(isset($element['hide']) && $element['hide'] === true)
			{
				return true;
			}
		}
		return false;
	}
}