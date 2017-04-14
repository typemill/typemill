<?php

namespace System\Models;

use \URLify;

class Folder
{	
	/*
	* scans content of a folder recursively
	* vars: folder path as string
	* returns: multi-dimensional array with names of folders and files
	*/
	public static function scanFolder($folderPath)
	{
		$folderItems 	= scandir($folderPath);
		$folderContent 	= array();
		
		foreach ($folderItems as $key => $item)
		{
			if (!in_array($item, array(".","..")))
			{
				if (is_dir($folderPath . DIRECTORY_SEPARATOR . $item))
				{
					$subFolder 					= $item;
					$folderContent[$subFolder] 	= self::scanFolder($folderPath . DIRECTORY_SEPARATOR . $subFolder);					
				}
				else
				{
					$file						= $item;
					$folderContent[] 			= $file;
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
	public static function getFolderContentDetails(array $folderContent, $baseUrl, $fullSlug = NULL, $fullPath = NULL, $keyPath = NULL, $chapter = NULL)
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
				$item->name				= iconv('ISO-8859-15', 'UTF-8', $item->name);
				$item->slug				= implode("-",$nameParts);
				$item->slug				= URLify::filter(iconv('ISO-8859-15', 'UTF-8', $item->slug));
				$item->path				= $fullPath . DIRECTORY_SEPARATOR . $key;
				$item->urlRel			= $fullSlug . '/' . $item->slug;
				$item->urlAbs			= $baseUrl . $fullSlug . '/' . $item->slug;
				$item->key				= $iteration;
				$item->keyPath			= $keyPath ? $keyPath . '.' . $iteration : $iteration;
				$item->keyPathArray		= explode('.', $item->keyPath);
				$item->chapter			= $chapter ? $chapter . '.' . $chapternr : $chapternr;
				
				$item->folderContent 	= self::getFolderContentDetails($name, $baseUrl, $item->urlRel, $item->path, $item->keyPath, $item->chapter);
			}
			else
			{
				$nameParts 				= self::getStringParts($name);
				$fileType 				= array_pop($nameParts);
				
				if($name == 'index.md' || $fileType !== 'md' ) break;
												
				$item->originalName 	= $name;
				$item->elementType		= 'file';
				$item->fileType			= $fileType;
				$item->order 			= count($nameParts) > 1 ? array_shift($nameParts) : NULL;
				$item->name 			= implode(" ",$nameParts);
				$item->name				= iconv('ISO-8859-15', 'UTF-8', $item->name);
				$item->slug				= implode("-",$nameParts);
				$item->slug				= URLify::filter(iconv('ISO-8859-15', 'UTF-8', $item->slug));
				$item->path				= $fullPath . DIRECTORY_SEPARATOR . $name;
				$item->key				= $iteration;
				$item->keyPath			= $keyPath . '.' . $iteration;
				$item->keyPathArray		= explode('.',$item->keyPath);
				$item->chapter			= $chapter . '.' . $chapternr;
				$item->urlRel			= $fullSlug . '/' . $item->slug;
				$item->urlAbs			= $baseUrl . $fullSlug . '/' . $item->slug;
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
			$item->nextItem = isset($item->folderContent[0]) ? $item->folderContent[0] : false;
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
}
?>