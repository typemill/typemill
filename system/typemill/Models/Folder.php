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
	public function scanFolderFlat($folderPath)
	{
		$folderItems 	= scandir($folderPath);
		$folderContent 	= array();

		foreach ($folderItems as $key => $item)
		{
			if (!in_array($item, array(".","..")))
			{
				$nameParts 					= $this->getStringParts($item);
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
	public function scanFolder($folderPath)
	{
		$folderItems 	= scandir($folderPath);
		$folderContent 	= array();

		foreach ($folderItems as $key => $item)
		{
			if (!in_array($item, array(".","..")) && substr($item, 0, 1) != '.')
			{
				if (is_dir($folderPath . DIRECTORY_SEPARATOR . $item))
				{

					$subFolder 		 	= $item;
					$folderPublished 	= file_exists($folderPath . DIRECTORY_SEPARATOR . $item . DIRECTORY_SEPARATOR . 'index.md');

					$folderContent[$subFolder] 	= $this->scanFolder($folderPath . DIRECTORY_SEPARATOR . $subFolder);
				}
				else
				{
					$nameParts 					= $this->getStringParts($item);
					$fileType 					= array_pop($nameParts);
					
					if($fileType == 'md')
					{
						$folderContent[] 			= $item;
					}
					
					if($fileType == 'txt')
					{
						if(isset($last) && ($last == implode($nameParts)) )
						{
							array_pop($folderContent);
							$item = $item . 'md';
						}
						$folderContent[] = $item;
					}
					
					# store the name of the last file
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

	public function getFolderContentDetails(array $folderContent, $language, $baseUrl, $fullSlugWithFolder = NULL, $fullSlugWithoutFolder = NULL, $fullPath = NULL, $keyPath = NULL, $chapter = NULL)
	{
		$contentDetails 	= [];
		$iteration 			= 0;
		$chapternr 			= 1;

		foreach($folderContent as $key => $name)
		{
			$item = new \stdClass();

			if(is_array($name))
			{
				$nameParts = $this->getStringParts($key);
				
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
				$item->contains			= $this->getFolderContentType($name, $fullPath . DIRECTORY_SEPARATOR . $key . DIRECTORY_SEPARATOR . 'index.yaml');
				$item->status			= $status;
				$item->fileType			= $fileType;
				$item->order 			= count($nameParts) > 1 ? array_shift($nameParts) : NULL;
				$item->name 			= implode(" ",$nameParts);
				$item->name				= iconv(mb_detect_encoding($item->name, mb_detect_order(), true), "UTF-8", $item->name);
				$item->slug				= implode("-",$nameParts);
				$item->slug				= $this->createSlug($item->slug, $language);
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

				# sort posts in descending order
				if($item->contains == "posts")
				{
					rsort($name);
				}

				$item->folderContent = $this->getFolderContentDetails($name, $language, $baseUrl, $item->urlRel, $item->urlRelWoF, $item->path, $item->keyPath, $item->chapter);
			}
			elseif($name)
			{
				# do not use index files
				if($name == 'index.md' || $name == 'index.txt' || $name == 'index.txtmd' ) continue;

				$nameParts 				= $this->getStringParts($name);
				$fileType 				= array_pop($nameParts);
				$nameWithoutType		= $this->getNameWithoutType($name);
				
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
				$item->slug				= $this->createSlug($item->slug, $language);
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
			}

			$iteration++;
			$chapternr++;
			$contentDetails[]		= $item;
		}
		return $contentDetails;	
	}


	public function getFolderContentType($folder, $yamlpath)
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
			$firstKey 		= array_key_first($folder); 
			$file 			= $folder[$firstKey];
			$nameParts 		= $this->getStringParts($file);
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
			
	public function getStringParts($name)
	{
		return preg_split('/[\-\.\_\=\+\?\!\*\#\(\)\/ ]/',$name);
	}
	
	public function getFileType($fileName)
	{
		$parts = preg_split('/\./',$fileName);
		return end($parts);
	}
	
	public function splitFileName($fileName)
	{
		$parts = preg_split('/\./',$fileName);
		return $parts;
	}

	public function getNameWithoutType($fileName)
	{
		$parts = preg_split('/\./',$fileName);
		return $parts[0];
	}

	public function createSlug($name, $language = null)
	{
		$name 		= iconv(mb_detect_encoding($name, mb_detect_order(), true), "UTF-8", $name);
		$language 	= $language ? $language : "";

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