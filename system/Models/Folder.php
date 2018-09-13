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
		
		foreach ($folderItems as $key => $item)
		{
			if (!in_array($item, array(".","..")))
			{
				if (is_dir($folderPath . DIRECTORY_SEPARATOR . $item))
				{
					/* TODO: if folder is empty or folder has only txt files, continue */
					$subFolder 					= $item;
					$folderContent[$subFolder] 	= self::scanFolder($folderPath . DIRECTORY_SEPARATOR . $subFolder, $draft);					
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
	public static function getFolderContentDetails(array $folderContent, $baseUrl, $fullSlugWithFolder = NULL, $fullSlugWithoutFolder = NULL, $fullPath = NULL, $keyPath = NULL, $chapter = NULL)
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
				
				$item->originalName 	= $key;
				$item->elementType		= 'folder';
				$item->index			= array_search('index.md', $name) === false ? false : true;
				$item->order 			= count($nameParts) > 1 ? array_shift($nameParts) : NULL;
				$item->name 			= implode(" ",$nameParts);
				$item->name				= iconv(mb_detect_encoding($item->name, mb_detect_order(), true), "UTF-8", $item->name);
				$item->slug				= implode("-",$nameParts);
				$item->slug				= URLify::filter(iconv(mb_detect_encoding($item->slug, mb_detect_order(), true), "UTF-8", $item->slug));				
				$item->path				= $fullPath . DIRECTORY_SEPARATOR . $key;
				$item->urlRelWoF		= $fullSlugWithoutFolder . '/' . $item->slug;
				$item->urlRel			= $fullSlugWithFolder . '/' . $item->slug;
				$item->urlAbs			= $baseUrl . $fullSlugWithoutFolder . '/' . $item->slug;
				$item->key				= $iteration;
				$item->keyPath			= isset($keyPath) ? $keyPath . '.' . $iteration : $iteration;
				$item->keyPathArray		= explode('.', $item->keyPath);
				$item->chapter			= $chapter ? $chapter . '.' . $chapternr : $chapternr;
				$item->active			= false;
				$item->activeParent		= false;
				
				$item->folderContent 	= self::getFolderContentDetails($name, $baseUrl, $item->urlRel, $item->urlRelWoF, $item->path, $item->keyPath, $item->chapter);
			}
			else
			{
				$nameParts 				= self::getStringParts($name);
				$fileType 				= array_pop($nameParts);
				
				# if($name == 'index.md' || $fileType !== 'md' ) continue;
				if($name == 'index.md' || $name == 'index.txt' ) continue;
													
				$item->originalName 	= $name;
				$item->elementType		= 'file';
				$item->fileType			= $fileType;
				$item->order 			= count($nameParts) > 1 ? array_shift($nameParts) : NULL;
				$item->name 			= implode(" ",$nameParts);
				$item->name				= iconv(mb_detect_encoding($item->name, mb_detect_order(), true), "UTF-8", $item->name);				
				$item->slug				= implode("-",$nameParts);
				$item->slug				= URLify::filter(iconv(mb_detect_encoding($item->slug, mb_detect_order(), true), "UTF-8", $item->slug));				
				$item->path				= $fullPath . DIRECTORY_SEPARATOR . $name;
				$item->key				= $iteration;
				$item->keyPath			= $keyPath . '.' . $iteration;
				$item->keyPathArray		= explode('.',$item->keyPath);
				$item->chapter			= $chapter . '.' . $chapternr;
				$item->urlRelWoF		= $fullSlugWithoutFolder . '/' . $item->slug;
				$item->urlRel			= $fullSlugWithFolder . '/' . $item->slug;
				$item->urlAbs			= $baseUrl . $fullSlugWithoutFolder . '/' . $item->slug;
				$item->active			= false;
				$item->activeParent		= false;
			}
			$iteration++;
			$chapternr++;
			$contentDetails[]		= $item;
		}
		return $contentDetails;	
	}

	public static function getItemForUrl($folderContentDetails, $url, $result = NULL)
	{
		foreach($folderContentDetails as $key => $item)
		{
			if($item->urlRel === $url)
			{
				$item->active = true;
				$result = $item;
			}
			elseif($item->elementType === "folder")
			{
				$result = self::getItemForUrl($item->folderContent, $url, $result);
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
			/* get the first element in the folder */
			$item->nextItem = isset($item->folderContent[0]) ? clone($item->folderContent[0]) : false;
		}
		
		if(!$item->nextItem)
		{
			$nextItemArray[$keyPos]++;
			$item->nextItem = self::getItemWithKeyPath($content, $nextItemArray);
		}
		
		while(!$item->nextItem)
		{
			array_pop($nextItemArray);
			if(empty($nextItemArray)) break; 
			$newKeyPos = count($nextItemArray)-1;
			$nextItemArray[$newKeyPos]++;
			$item->nextItem = self::getItemWithKeyPath($content, $nextItemArray);
		}

		/************************
		* 	ADD PREVIOUS ITEM	*
		************************/
		
		if($prevItemArray[$keyPos] > 0)
		{
			$prevItemArray[$keyPos]--;
			$item->prevItem = self::getItemWithKeyPath($content, $prevItemArray);
			
			if($item->prevItem && $item->prevItem->elementType == 'folder' && !empty($item->prevItem->folderContent))
			{
				/* get last item in folder */
				$item->prevItem = self::getLastItemOfFolder($item->prevItem);
			}
		}
		else
		{
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
	
				
	/* get breadcrumb as copied array, set elements active in original and mark parent element in original */
	public static function getBreadcrumb($content, $searchArray, $i = NULL, $breadcrumb = NULL)
	{
		if(!$i){ $i = 0; $breadcrumb = array();}
		
		while($i < count($searchArray))
		{
			$item = $content[$searchArray[$i]];
			$item->active = true;
			if($i == count($searchArray)-2)
			{
				$item->activeParent = true; 
			}

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
}