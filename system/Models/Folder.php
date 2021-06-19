<?php

namespace Typemill\Models;

use \URLify;

class Folder
{

	/*
	* scans content of a folder (without recursion)
	* vars: folder path as string
	* returns: one-dimensional array with names of folders and files
	*/
	public static function scanFolderFlat($folderPath)
	{
		$folderItems 	= scandir($folderPath);
		$folderContent 	= array();

		foreach ($folderItems as $key => $item)
		{
			if (!in_array($item, array(".","..")))
			{
				$nameParts 					= self::getStringParts($item);
				$fileType 					= array_pop($nameParts);
				
				if($fileType == 'md' OR $fileType == 'txt' )
				{
					$folderContent[] 			= $item;						
				}
			}
		}
		return $folderContent;
	}
	
	/*
	* scans content of a folder recursively
	* vars: folder path as string
	* returns: multi-dimensional array with names of folders and files
	*/
	public static function scanFolder($folderPath, $draft = false)
	{
		$folderItems 	= scandir($folderPath);
		$folderContent 	= array();

		# if it is the live version and if it is a folder that is not published, then do not show the folder and its content.
		if(!$draft && !in_array('index.md', $folderItems)){ return false; }

		foreach ($folderItems as $key => $item)
		{
			if (!in_array($item, array(".","..")))
			{
				if (is_dir($folderPath . DIRECTORY_SEPARATOR . $item))
				{

					$subFolder 		 	= $item;
					$folderPublished 	= file_exists($folderPath . DIRECTORY_SEPARATOR . $item . DIRECTORY_SEPARATOR . 'index.md');

					# scan that folder only if it is a draft or if the folder is published (contains index.md)
					if($draft OR $folderPublished)
					{
						$folderContent[$subFolder] 	= self::scanFolder($folderPath . DIRECTORY_SEPARATOR . $subFolder, $draft);
					}
				}
				else
				{
					$nameParts 					= self::getStringParts($item);
					$fileType 					= array_pop($nameParts);
					
					if($fileType == 'md')
					{
						$folderContent[] 			= $item;					
					}
					
					if($draft === true && $fileType == 'txt')
					{
						if(isset($last) && ($last == implode($nameParts)) )
						{
							array_pop($folderContent);
							$item = $item . 'md';
						}
						$folderContent[] = $item;
					}
					
					/* store the name of the last file */
					$last = implode($nameParts);
				}
			}
		}
		return $folderContent;
	}
	

	/*
	* Transforms array of folder item into an array of item-objects with additional information for each item
	* vars: multidimensional array with folder- and file-names
	* returns: array of objects. Each object contains information about an item (file or folder).
	*/

	public static function getFolderContentDetails(array $folderContent, $extended, $baseUrl, $fullSlugWithFolder = NULL, $fullSlugWithoutFolder = NULL, $fullPath = NULL, $keyPath = NULL, $chapter = NULL)
	{
		$contentDetails 	= [];
		$iteration 			= 0;
		$chapternr 			= 1;

		foreach($folderContent as $key => $name)
		{
			$item = new \stdClass();

			if(is_array($name))
			{
				$nameParts = self::getStringParts($key);
				
				$fileType = '';
				if(in_array('index.md', $name))
				{
					$fileType 		= 'md';
					$status 		= 'published';
				}
				if(in_array('index.txt', $name))
				{
					$fileType 		= 'txt';
					$status 		= 'unpublished';
				}
				if(in_array('index.txtmd', $name))
				{
					$fileType 		= 'txt';
					$status 		= 'modified';
				}

				$item->originalName 	= $key;
				$item->elementType		= 'folder';
				$item->contains			= self::getFolderContentType($name, $fullPath . DIRECTORY_SEPARATOR . $key . DIRECTORY_SEPARATOR . 'index.yaml');				
				$item->status			= $status;
				$item->fileType			= $fileType;
				$item->order 			= count($nameParts) > 1 ? array_shift($nameParts) : NULL;
				$item->name 			= implode(" ",$nameParts);
				$item->name				= iconv(mb_detect_encoding($item->name, mb_detect_order(), true), "UTF-8", $item->name);
				$item->slug				= implode("-",$nameParts);
				$item->slug				= URLify::filter(iconv(mb_detect_encoding($item->slug, mb_detect_order(), true), "UTF-8", $item->slug));
				$item->path				= $fullPath . DIRECTORY_SEPARATOR . $key;
				$item->pathWithoutType	= $fullPath . DIRECTORY_SEPARATOR . $key . DIRECTORY_SEPARATOR . 'index';
				$item->urlRelWoF		= $fullSlugWithoutFolder . '/' . $item->slug;
				$item->urlRel			= $fullSlugWithFolder . '/' . $item->slug;
				$item->urlAbs			= $baseUrl . $fullSlugWithoutFolder . '/' . $item->slug;
				$item->key				= $iteration;
				$item->keyPath			= isset($keyPath) ? $keyPath . '.' . $iteration : $iteration;
				$item->keyPathArray		= explode('.', $item->keyPath);
				$item->chapter			= $chapter ? $chapter . '.' . $chapternr : $chapternr;
				$item->active			= false;
				$item->activeParent		= false;
				$item->hide 			= false;

				# check if there are extended information
				if($extended && isset($extended[$item->urlRelWoF]))
				{
					$item->name = ($extended[$item->urlRelWoF]['navtitle'] != '') ? $extended[$item->urlRelWoF]['navtitle'] : $item->name;
					$item->hide = ($extended[$item->urlRelWoF]['hide'] === true) ? true : false;
				}

				# sort posts in descending order
				if($item->contains == "posts")
				{
					rsort($name);
				}

				$item->folderContent = self::getFolderContentDetails($name, $extended, $baseUrl, $item->urlRel, $item->urlRelWoF, $item->path, $item->keyPath, $item->chapter);
			}
			elseif($name)
			{
				# do not use files in base folder (only folders are allowed)
				# if(!isset($keyPath)) continue;

				# do not use index files
				if($name == 'index.md' || $name == 'index.txt' || $name == 'index.txtmd' ) continue;

				$nameParts 				= self::getStringParts($name);
				$fileType 				= array_pop($nameParts);
				$nameWithoutType		= self::getNameWithoutType($name);

				if($fileType == 'md')
				{
					$status = 'published';
				}
				elseif($fileType == 'txt')
				{
					$status = 'unpublished';
				}
				else
				{
					$fileType = 'txt';
					$status = 'modified';
				}

				$item->originalName 	= $name;
				$item->elementType		= 'file';
				$item->status 			= $status;
				$item->fileType			= $fileType;
				$item->order 			= count($nameParts) > 1 ? array_shift($nameParts) : NULL;
				$item->name 			= implode(" ",$nameParts);
				$item->name				= iconv(mb_detect_encoding($item->name, mb_detect_order(), true), "UTF-8", $item->name);				
				$item->slug				= implode("-",$nameParts);
				$item->slug				= URLify::filter(iconv(mb_detect_encoding($item->slug, mb_detect_order(), true), "UTF-8", $item->slug));
				$item->path				= $fullPath . DIRECTORY_SEPARATOR . $name;
				$item->pathWithoutType	= $fullPath . DIRECTORY_SEPARATOR . $nameWithoutType;
				$item->key				= $iteration;
				$item->keyPath			= isset($keyPath) ? $keyPath . '.' . $iteration : $iteration;
				$item->keyPathArray		= explode('.',$item->keyPath);
				$item->chapter			= $chapter . '.' . $chapternr;
				$item->urlRelWoF		= $fullSlugWithoutFolder . '/' . $item->slug;
				$item->urlRel			= $fullSlugWithFolder . '/' . $item->slug;
				$item->urlAbs			= $baseUrl . $fullSlugWithoutFolder . '/' . $item->slug;
				$item->active			= false;
				$item->activeParent		= false;
				$item->hide 			= false;

				# check if there are extended information
				if($extended && isset($extended[$item->urlRelWoF]))
				{
					$item->name = ($extended[$item->urlRelWoF]['navtitle'] != '') ? $extended[$item->urlRelWoF]['navtitle'] : $item->name;
					$item->hide = ($extended[$item->urlRelWoF]['hide'] === true) ? true : false;
				}
			}

			$iteration++;
			$chapternr++;
			$contentDetails[]		= $item;
		}
		return $contentDetails;	
	}

	public static function getFolderContentType($folder, $yamlpath)
	{
		# check if folder is empty or has only index.yaml-file. This is a rare case so make it quick and dirty
		if(count($folder) == 1)
		{
			# check if in folder yaml file contains "posts", then return posts
			$folderyamlpath = getcwd() . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . $yamlpath;
			
			$fileContent = false;
			if(file_exists($folderyamlpath))
			{
				$fileContent = file_get_contents($folderyamlpath);
			}

			if($fileContent && strpos($fileContent, 'contains: posts') !== false)
			{
				return 'posts';
			}
			return 'pages';
		}
		else
		{
			$file 			= $folder[0];
			$nameParts 		= self::getStringParts($file);
			$order 			= count($nameParts) > 1 ? array_shift($nameParts) : NULL;
			$order 			= substr($order, 0, 7);
			
			if(\DateTime::createFromFormat('Ymd', $order) !== FALSE)
			{
				return "posts";
			}
			else
			{
				return "pages";
			}
		}
	}

	public static function getItemForUrl($folderContentDetails, $url, $baseUrl, $result = NULL, $home = NULL )
	{

		# if we are on the homepage
		if($home)
		{
			# return a standard item-object
			$item 					= new \stdClass;
			$item->status 			= 'published';
			$item->originalName 	= 'home';
			$item->elementType 		= 'folder';
			$item->fileType			= 'md';
			$item->order 			= false;
			$item->name 			= 'home';
			$item->slug				= '';
			$item->path				= '';
			$item->pathWithoutType	= DIRECTORY_SEPARATOR . 'index';
			$item->key				= false;
			$item->keyPath			= false;
			$item->keyPathArray		= false;
			$item->chapter			= false;
			$item->urlRel			= '/';
			$item->urlRelWoF		= '/';
			$item->urlAbs			= $baseUrl;
			$item->name 			= 'home';
			$item->active			= false;
			$item->activeParent		= false;
			$item->hide 			= false;

			return $item;
		}

		foreach($folderContentDetails as $key => $item)
		{
			# set item active, needed to move item in navigation
			if($item->urlRel === $url)
			{
				$item->active = true;
				$result = $item;
			}
			elseif($item->elementType === "folder")
			{
				$result = self::getItemForUrl($item->folderContent, $url, $baseUrl, $result);
			}
		}

		return $result;
	}

	public static function getItemForUrlFrontend($folderContentDetails, $url, $result = NULL)
	{
		foreach($folderContentDetails as $key => $item)
		{
			# set item active, needed to move item in navigation
			if($item->urlRelWoF === $url)
			{
				$item->active = true;
				$result = $item;
			}
			elseif($item->elementType === "folder")
			{
				$result = self::getItemForUrlFrontend($item->folderContent, $url, $result);
			}
		}

		return $result;
	}	

	public static function getPagingForItem($content, $item)
	{
		$keyPos 			= count($item->keyPathArray)-1;
		$thisChapArray		= $item->keyPathArray;
		$nextItemArray 		= $item->keyPathArray;
		$prevItemArray 		= $item->keyPathArray;
		
		$item->thisChapter 	= false;
		$item->prevItem 	= false;
		$item->nextItem 	= false;
		
		
		/************************
		* 	ADD THIS CHAPTER 	*
		************************/

		if($keyPos > 0)
		{
			array_pop($thisChapArray);
			$item->thisChapter = self::getItemWithKeyPath($content, $thisChapArray);
		}
		
		/************************
		* 	ADD NEXT ITEM	 	*
		************************/
				
		if($item->elementType == 'folder')
		{
			# get the first element in the folder
			$item->nextItem = isset($item->folderContent[0]) ? clone($item->folderContent[0]) : false;
		}
		
		# the item is a file or an empty folder
		if(!$item->nextItem)
		{
			# walk to the next file in the same hierarchy
			$nextItemArray[$keyPos]++;

			# get the key of the last element in this hierarchy level
			# if there is no chapter, then it is probably an empty first-level-folder. Count content to get the number of first level items
			$lastKey = $item->thisChapter ? array_key_last($item->thisChapter->folderContent) : count($content);

			# as long as the nextItemArray is smaller than the last key in this hierarchy level, search for the next item
			# this ensures that it does not stop if key is missing (e.g. if the next page is hidden)
			while( ($nextItemArray[$keyPos] <= $lastKey) && !$item->nextItem = self::getItemWithKeyPath($content, $nextItemArray) )
			{
				$nextItemArray[$keyPos]++;
			}
		}
		
		# there is no next file or folder in this level, so walk up the hierarchy to the next folder or file
		while(!$item->nextItem)
		{
			# delete the array level with the current item, so you are in the parent folder
			array_pop($nextItemArray);

			# if the array is empty now, then you where in the base level already, so break
			if(empty($nextItemArray)) break; 

			# define the key position where you are right now
			$newKeyPos = count($nextItemArray)-1;

			# go to the next position
			$nextItemArray[$newKeyPos]++;

			# search for 5 items in case there are some hidden elements
			$maxlength = $nextItemArray[$newKeyPos]+5;
			while( ($nextItemArray[$newKeyPos] <= $maxlength) && !$item->nextItem = self::getItemWithKeyPath($content, $nextItemArray) )
			{
				$nextItemArray[$newKeyPos]++;
			}
		}

		/************************
		* 	ADD PREVIOUS ITEM	*
		************************/
		
		# check if element is the first in the array
		$first = ($prevItemArray[$keyPos] == 0) ? true : false;

		if(!$first)
		{
			$prevItemArray[$keyPos]--;
			
			while($prevItemArray[$keyPos] >= 0 && !$item->prevItem = self::getItemWithKeyPath($content, $prevItemArray))
			{
				$prevItemArray[$keyPos]--;
			}
			
			# if no item is found, then all previous items are hidden, so set first item to true and it will walk up the array later
			if(!$item->prevItem)
			{
				$first = true;
			}
			elseif($item->prevItem && $item->prevItem->elementType == 'folder' && !empty($item->prevItem->folderContent))
			{
				# if the previous item is a folder, the get the last item of that folder
				$item->prevItem = self::getLastItemOfFolder($item->prevItem);
			}
		}

		# if it is the first item in the folder (or all other files are hidden)
		if($first)
		{
			# then the previous item is the containing chapter
			$item->prevItem = $item->thisChapter;
		}
		
		if($item->prevItem && $item->prevItem->elementType == 'folder'){ unset($item->prevItem->folderContent); }
		if($item->nextItem && $item->nextItem->elementType == 'folder'){ unset($item->nextItem->folderContent); }
		if($item->thisChapter){unset($item->thisChapter->folderContent); }
		
		return $item;
	}

	/*
	 * Gets a copy of an item with a key
	 * @param array $content with the full structure of the content as multidimensional array
	 * @param array $searchArray with the key as a one-dimensional array like array(0,3,4)
	 * @return array $item
	 */
	 
	public static function getItemWithKeyPath($content, array $searchArray)
	{
		$item = false;

		foreach($searchArray as $key => $itemKey)
		{
			$item = isset($content[$itemKey]) ? clone($content[$itemKey]) : false;
			
			unset($searchArray[$key]);
			if(!empty($searchArray) && $item)
			{
				return self::getItemWithKeyPath($item->folderContent, $searchArray);
			}
		}
		return $item;
	}

	# https://www.quora.com/Learning-PHP-Is-there-a-way-to-get-the-value-of-multi-dimensional-array-by-specifying-the-key-with-a-variable
	# NOT IN USE
	public static function getItemWithKeyPathNew($array, array $keys)
	{
		$item = $array;
		
        foreach ($keys as $key)
		{
			$item = isset($item[$key]->folderContent) ? $item[$key]->folderContent : $item[$key];
		}
		
		return $item;
    }

	/*
	 * Extracts an item with a key https://stackoverflow.com/questions/52097092/php-delete-value-of-array-with-dynamic-key
	 * @param array $content with the full structure of the content as multidimensional array
	 * @param array $searchArray with the key as a one-dimensional array like array(0,3,4)
	 * @return array $item
	 * NOT IN USE ??
	 */
	 
	public static function extractItemWithKeyPath($structure, array $keys)
	{
		$result = &$structure;
		$last = array_pop($keys);

		foreach ($keys as $key) {
			if(isset($result[$key]->folderContent))
			{
				$result = &$result[$key]->folderContent;
			}
			else
			{
				$result = &$result[$key];
			}
		}

		$item = $result[$last];
		unset($result[$last]);
		
		return array('structure' => $structure, 'item' => $item);
	}

	# NOT IN USE
	public static function deleteItemWithKeyPath($structure, array $keys)
	{
		$result = &$structure;
		$last = array_pop($keys);

		foreach ($keys as $key)
		{
			if(isset($result[$key]->folderContent))
			{
				$result = &$result[$key]->folderContent;
			}
			else
			{
				$result = &$result[$key];
			}
		}

		$item = $result[$last];
		unset($result[$last]);
		
		return $structure;
	}
	
	# get breadcrumb as copied array, 
	# set elements active in original 
	# mark parent element in original
	public static function getBreadcrumb($content, $searchArray, $i = NULL, $breadcrumb = NULL)
	{
		# if it is the first round, create an empty array
		if(!$i){ $i = 0; $breadcrumb = array();}

		if(!$searchArray){ return $breadcrumb;}

		while($i < count($searchArray))
		{
			if(!isset($content[$searchArray[$i]])){ return false; }
			$item = $content[$searchArray[$i]];

			if($i == count($searchArray)-1)
			{
				$item->active = true;
			}
			else
			{
				$item->activeParent = true;
			}

			/*
			$item->active = true;
			if($i == count($searchArray)-2)
			{
				$item->activeParent = true; 
			}
			*/

			$copy = clone($item);
			if($copy->elementType == 'folder')
			{
				unset($copy->folderContent);
				$content = $item->folderContent;
			}
			$breadcrumb[] = $copy;
			
			$i++;
			return self::getBreadcrumb($content, $searchArray, $i++, $breadcrumb);
		}
		return $breadcrumb;
	}
	
	public static function getParentItem($content, $searchArray, $iteration = NULL)
	{
		if(!$iteration){ $iteration = 0; }
		while($iteration < count($searchArray)-2)
		{
			$content = $content[$searchArray[$iteration]]->folderContent;
			$iteration++;
			return self::getParentItem($content, $searchArray, $iteration);
		}
		return $content[$searchArray[$iteration]];
	}
	
	private static function getLastItemOfFolder($folder)
	{	
		$lastItem = end($folder->folderContent);
		if(is_object($lastItem) && $lastItem->elementType == 'folder' && !empty($lastItem->folderContent))
		{
			return self::getLastItemOfFolder($lastItem);
		}
		return $lastItem;
	}
		
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
}