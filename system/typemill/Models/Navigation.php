<?php

namespace Typemill\Models;

use Typemill\Models\StorageWrapper;
use Typemill\Models\Folder;
use Typemill\Events\OnSystemnaviLoaded;

class Navigation extends Folder
{
	private $storage;

	private $naviFolder;

	private $draftNaviName;

	private $DS;

	public function __construct()
	{
		$this->storage 				= new StorageWrapper('\Typemill\Models\Storage');

		$this->naviFolder 			= 'navigation';

		$this->draftNaviName 		= 'draft-navi';

		$this->DS 					= DIRECTORY_SEPARATOR;
	}

	public function getMainNavigation($userrole, $acl, $urlinfo, $editor)
	{
		$mainnavi 		= $this->storage->getYaml('systemSettings', '', 'mainnavi.yaml');

		$allowedmainnavi = [];

		$activeitem = false;

		foreach($mainnavi as $name => $naviitem)
		{
			if($acl->isAllowed($userrole, $naviitem['aclresource'], $naviitem['aclprivilege']))
			{
				# set the navi of current route active
				$thisRoute = '/tm/' . $name;

				if(strpos($urlinfo['route'], $thisRoute) !== false)
				{
					$naviitem['active'] = true;
					$activeitem = true;
				}

				$allowedmainnavi[$name] = $naviitem;
			}
		}

		# if system is there, then we do not need the account item
		if(isset($allowedmainnavi['system']))
		{
			unset($allowedmainnavi['account']);
			
			# if no active item has been found, then it is submenu under system
			if(!$activeitem)
			{
				$allowedmainnavi['system']['active'] = true;
			}
		}

		# set correct editor mode according to user settings
		if(isset($allowedmainnavi['content']) && $editor == 'raw')
		{
			$allowedmainnavi['content']['routename'] = "content.raw";
		}

		return $allowedmainnavi;
	}

	public function getSystemNavigation($userrole, $acl, $urlinfo, $dispatcher, $routeparser)
	{
		$systemnavi 		= $this->storage->getYaml('systemSettings', '', 'systemnavi.yaml');
		$systemnavi 		= $dispatcher->dispatch(new OnSystemnaviLoaded($systemnavi), 'onSystemnaviLoaded')->getData();

		$allowedsystemnavi 	= [];

		$route 				= trim($urlinfo['route'], '/');

		foreach($systemnavi as $name => $naviitem)
		{
			$naviitem['url'] 	= $routeparser->urlFor($naviitem['routename']);
			$itemurl 			= trim($naviitem['url'], '/');

			if(strpos( $itemurl, $route ) !== false)
			{
				$naviitem['active'] = true;
			}

			if($acl->isAllowed($userrole, $naviitem['aclresource'], $naviitem['aclprivilege']))
			{
				$allowedsystemnavi[$name] = $naviitem;
			}
		}
		
		return $allowedsystemnavi;
	}


	# use array ['extended' => true, 'draft' => true, 'live' => true] to clear files
	public function clearNavigation($deleteItems = NULL)
	{
		$result = false;

		$dataPath 			= $this->storage->getFolderPath('dataFolder');
		$naviPath 			= $dataPath . DIRECTORY_SEPARATOR . $this->naviFolder;
		$naviFiles 			= scandir($naviPath);

		if($deleteItems)
		{
			# replace the placeholder '/' for a base-item with the cached base navigation
			foreach ($deleteItems as &$value)
			{
			    if ($value === '/')
			    {
			        $value = $this->draftNaviName;
			    }
			    else
			    {
			    	$value .= '.txt';
			    }
			}

			$naviFiles = array_intersect($naviFiles, $deleteItems);
		}

		foreach($naviFiles as $naviFile)
		{
			if (!in_array($naviFile, array(".","..")) && substr($naviFile, 0, 1) != '.')
			{
				$result = $this->storage->deleteFile('dataFolder', $this->naviFolder, $naviFile);
			}
		}

		return $result;
	}


	public function getItemForUrl($url, $urlinfo, $langattr)
	{
		$url = $this->removeEditorFromUrl($url);

		if($url == '/')
		{
			return $this->getHomepageItem($urlinfo['baseurl']);
		}

		$pageinfo = $this->getPageInfoForUrl($url, $urlinfo, $langattr);

		if(!$pageinfo)
		{
			return false;
		}

		$foldername = $this->getFirstUrlSegment($pageinfo['path']);

		$draftNavigation = $this->getDraftNavigation($urlinfo, $langattr, $foldername);
		if(!$draftNavigation)
		{
			return false;
		}

		$keyPathArray = explode(".", $pageinfo['keyPath']);
		$item = $this->getItemWithKeyPath($draftNavigation, $keyPathArray);

		return $item;
	}

	public function getPageInfoForUrl($url, $urlinfo, $langattr)
	{
		# get the first level navigation
		$firstLevelExtended = $this->getExtendedNavigation($urlinfo, $langattr, '/');

		$firstUrlSegment = $this->getFirstUrlSegment($url);
		$firstUrlSegment = '/' . $firstUrlSegment;

		$pageinfo = $firstLevelExtended[$firstUrlSegment] ?? false;

		# first level does not exist
		if(!$pageinfo)
		{
			return false;
		}

		# url is first level
		if($url == $firstUrlSegment)
		{
			return $pageinfo;
		}

		$foldername = trim($pageinfo['path'], $this->DS);

		$extendedNavigation = $this->getExtendedNavigation($urlinfo, $langattr, $foldername);

		$pageinfo = $extendedNavigation[$url] ?? false;
		if(!$pageinfo)
		{
			return false;
		}

		return $pageinfo;
	}

	private function removeEditorFromUrl($url)
	{
		$url = trim($url, '/');

		$url = str_replace('tm/content/visual', '', $url);
		$url = str_replace('tm/content/raw', '', $url);

		$url = trim($url, '/');

		return '/' . $url;
	}

	public function getFirstUrlSegment($url)
	{
		$segments = explode('/', $url);

		if(isset($segments[1]))
		{
			return $segments[1];
		}

		return '';
	}

	public function getNaviFileNameForPath($path)
	{
		$segments = explode($this->DS, $path);

		# navi-file-name for a base-folder is draftNaviName where first level items are cached.
		if(isset($segments[2]))
		{
			return $segments[1];
		}

		return $this->draftNaviName;
	}



	public function getLiveNavigation($urlinfo, $langattr)
	{
		$draftNavigation = $this->getFullDraftNavigation($urlinfo, $langattr);

		$liveNavigation = $this->generateLiveNavigationFromDraft($draftNavigation);

		$liveNavigation = $this->removeHiddenPages($liveNavigation);

		return $liveNavigation;
	}

	# ASK FOR THE FULL DRAFT NAVIGATION AND MERGE ALL SEPARATED NAVIGATIONS
	public function getFullDraftNavigation($urlinfo, $language, $userrole = null, $username = null)
	{
		# get first level
		$draftNavigation = $this->getDraftNavigation($urlinfo, $language, '/');

		foreach($draftNavigation as $key => $item)
		{
			if($item->elementType == 'folder')
			{
				$subfolder = $this->getDraftNavigation($urlinfo, $language, $item->originalName);

				$draftNavigation[$key]->folderContent = $subfolder[$key]->folderContent;
			}
		}

		return $draftNavigation;
	}

	# ASK FOR A STATIC DRAFT NAVIGATION AND CREATE ONE IF NOT THERE
	public function getDraftNavigation($urlinfo, $language, $foldername)
	{
		$draftFileName 		= $this->getDraftFileName($foldername);
		$extendedFileName 	= $this->getExtendedFileName($foldername);

		$draftNavigation = $this->getDraftNavigationFile($draftFileName);

		if($draftNavigation)
		{
			return $draftNavigation;
		}

		$rawDraftNavigation = $this->generateRawDraftNavigation($urlinfo, $language, $foldername);
		if(!$rawDraftNavigation)
		{
			return false;
		}

		$extendedNavigation = $this->getExtendedNavigationFile($extendedFileName);
		if(!$extendedNavigation)
		{
			$extendedNavigation = $this->generateExtendedFromDraft($rawDraftNavigation);

			if(!$extendedNavigation)
			{
				return false;
			}
			
			$this->storeStaticNavigation($extendedFileName, $extendedNavigation);
		}

		$draftNavigation = $this->mergeExtendedWithDraft($rawDraftNavigation, $extendedNavigation);
		if(!$draftNavigation)
		{
			return false;
		}
		
		$this->storeStaticNavigation($draftFileName, $draftNavigation);

		return $draftNavigation;
	}

	public function getExtendedNavigation($urlinfo, $language, $foldername)
	{
		$draftFileName 		= $this->getDraftFileName($foldername);
		$extendedFileName 	= $this->getExtendedFileName($foldername);

		$extendedNavigation = $this->getExtendedNavigationFile($extendedFileName);
		if($extendedNavigation)
		{
			return $extendedNavigation;
		}

		$draftNavigation 	= $this->getDraftNavigationFile($draftFileName);
		if(!$draftNavigation)
		{
			# we have to create and store extended and draft in this case 

			$rawDraftNavigation = $this->generateRawDraftNavigation($urlinfo, $language, $foldername);

			if(!$rawDraftNavigation)
			{
				return false;
			}
		
			$extendedNavigation = $this->generateExtendedFromDraft($rawDraftNavigation);

			if(!$extendedNavigation)
			{
				return false;
			}
			
			$this->storeStaticNavigation($extendedFileName, $extendedNavigation);

			$draftNavigation = $this->mergeExtendedWithDraft($rawDraftNavigation, $extendedNavigation);
			if(!$draftNavigation)
			{
				return false;
			}
			
			$this->storeStaticNavigation($draftFileName, $draftNavigation);

			return $extendedNavigation;
		}

		# we only have to create and store extended in this case

		$extendedNavigation = $this->generateExtendedFromDraft($draftNavigation);

		if(!$extendedNavigation)
		{
			return false;
		}
		
		$this->storeStaticNavigation($extendedFileName, $extendedNavigation);

		return $extendedNavigation;
	}

	public function generateLiveNavigationFromDraft($draftNavigation)
	{
		foreach($draftNavigation as $key => $item)
		{
			if($item->status == 'unpublished')
			{
				unset($draftNavigation[$key]);
			}
			else
			{
				if($item->status == 'modified')
				{
					$draftNavigation[$key]->fileType = 'md';
					$draftNavigation[$key]->path = $draftNavigation[$key]->pathWithoutType . '.md';
				}

				if(isset($item->folderContent) && $item->folderContent)
				{
					$item->folderContent = $this->generateLiveNavigationFromDraft($item->folderContent);
				}
			}
		}

		return $draftNavigation;
	}

	private function storeStaticNavigation($filename, $data)
	{
		if($filename == '.txt' OR $filename == '-extended.txt')
		{
			return false;
		}

		if($this->storage->writeFile('dataFolder', $this->naviFolder, $filename, $data, 'serialize'))
		{
			return true;
		}

		return false;
	}

	# gets the cached draft navigation of a folder or of the first level
	private function getDraftNavigationFile($filename)
	{
		$draftNavigation = $this->storage->getFile('dataFolder', $this->naviFolder, $filename, 'unserialize');

		if($draftNavigation)
		{
			return $draftNavigation;
		}

		return false;
	}

	# generates a raw draft navigation 
	private function generateRawDraftNavigation($urlinfo, $language, $foldername = false)
	{
		# convert basefolder '/' to true
		$flat = ($foldername == '/') ? true : $foldername;

		# scan the content of the folder
		$draftContentTree = $this->scanFolder($this->storage->getFolderPath('contentFolder'), $flat);

		# if there is content, then get the content details
		if(count($draftContentTree) > 0)
		{
			$draftNavigation = $this->getFolderContentDetails($draftContentTree, $language, $urlinfo['baseurl'], $urlinfo['basepath']);
			
			return $draftNavigation;
		}

		return false;
	}

	# get the extended Navigation file for a folder or base 
	private function getExtendedNavigationFile($filename)
	{
		$extendedNavigation = $this->storage->getFile('dataFolder', $this->naviFolder, $filename, 'unserialize');

		if($extendedNavigation)
		{
			return $extendedNavigation;
		}

		return false;
	}

	# reads all meta-files and creates an array with url => ['hide' => bool, 'navtitle' => 'bla']
	private function generateExtendedFromDraft($navigation, $extended = NULL)
	{
		if(!$extended)
		{
			$extended = [];
		}

		foreach ($navigation as $key => $item)
		{
			# $filename = ($item->elementType == 'folder') ? DIRECTORY_SEPARATOR . 'index.yaml' : $item->pathWithoutType . '.yaml';
			$filename = $item->pathWithoutType . '.yaml';

			# read file
			$meta = $this->storage->getYaml('contentFolder', '', $filename);

			if(!$meta)
			{
				# create initial yaml
				$meta = [];
				$meta['meta']['navtitle'] = $item->name;

				$this->storage->updateYaml('contentFolder', '', $filename, $meta);
			}

			$extended[$item->urlRelWoF]['navtitle'] 	= isset($meta['meta']['navtitle']) ? $meta['meta']['navtitle'] : '';
			$extended[$item->urlRelWoF]['hide'] 		= isset($meta['meta']['hide']) ? $meta['meta']['hide'] : false;
			$extended[$item->urlRelWoF]['noindex'] 		= isset($meta['meta']['noindex']) ? $meta['meta']['noindex'] : false;
			$extended[$item->urlRelWoF]['path']			= $item->path;
			$extended[$item->urlRelWoF]['keyPath']		= $item->keyPath;

			if ($item->elementType == 'folder')
			{
				$extended = $this->generateExtendedFromDraft($item->folderContent, $extended);
			}
		}

		return $extended;
	}

	# takes a draft navigation and extended navigation and merges both
	private function mergeExtendedWithDraft($draftNavigation, $extendedNavigation)
	{
		$mergedNavigation = [];

		foreach($draftNavigation as $key => $item)
		{
			if($extendedNavigation && isset($extendedNavigation[$item->urlRelWoF]))
			{
				$item->name 		= ($extendedNavigation[$item->urlRelWoF]['navtitle'] != '') ? $extendedNavigation[$item->urlRelWoF]['navtitle'] : $item->name;
				$item->hide 		= ($extendedNavigation[$item->urlRelWoF]['hide'] === true) ? true : false;
				$item->noindex		= (isset($extendedNavigation[$item->urlRelWoF]['noindex']) && $extendedNavigation[$item->urlRelWoF]['noindex'] === true) ? true : false;
			}

			if($item->elementType == 'folder')
			{
				$item->folderContent = $this->mergeExtendedWithDraft($item->folderContent, $extendedNavigation);
			}

			$mergedNavigation[$key] = $item;
		}

		return $mergedNavigation;
	}

	protected function getDraftFileName($foldername)
	{
		$draftFileName = $foldername;

		if($draftFileName == '/')
		{
			$draftFileName = $this->draftNaviName;
		}

		return $draftFileName . '.txt';
	}

	protected function getExtendedFileName($foldername)
	{
		$draftFileName = $foldername;

		if($draftFileName == '/')
		{
			$draftFileName = $this->draftNaviName;
		}

		return $draftFileName . '-extended.txt';
	}

	public function getItemWithKeyPath($navigation, array $searchArray, $baseUrl = null)
	{
		$item = false;

		# if it is the homepage
		if(isset($searchArray[0]) && $searchArray[0] == '')
		{
			return $this->getHomepageItem($baseUrl);
		}

		foreach($searchArray as $key => $itemKey)
		{
			$item = isset($navigation[$itemKey]) ? clone($navigation[$itemKey]) : false;

			unset($searchArray[$key]);
			if(!empty($searchArray) && $item)
			{
				return $this->getItemWithKeyPath($item->folderContent, $searchArray);
			}
		}

		return $item;
	}

	# used with scan folder that keeps index from draft version
	public function setActiveNaviItemsWithKeyPath($navigation, array $searchArray)
	{
		foreach($searchArray as $key => $itemKey)
		{
			if(isset($navigation[$itemKey]))
			{
				unset($searchArray[$key]);

				# active, if there are no more subitems
				if(empty($searchArray))
				{
					$navigation[$itemKey]->active = true;
				}

				# activeParent, if there are more subitems
				if(!empty($searchArray) && isset($navigation[$itemKey]->folderContent))
				{
					$navigation[$itemKey]->activeParent = true;
					$navigation[$itemKey]->folderContent = $this->setActiveNaviItemsWithKeyPath($navigation[$itemKey]->folderContent, $searchArray);
				}
				
				# break to avoid other items with that key are set active
				break;
			}
		}

		return $navigation;
	}

	public function getHomepageItem($baseUrl)
	{
#		$live 	= $this->storage->getFile('contentFolder', '', 'index.md');
		$draft 	= $this->storage->getFile('contentFolder', '', 'index.txt');

		# return a standard item-object
		$item 					= new \stdClass;

		$item->status 			= $draft ? 'modified' : 'published';
		$item->originalName 	= 'home';
		$item->elementType 		= 'folder';
		$item->fileType			= $draft ? 'mdtxt' : 'md';
		$item->order 			= false;
		$item->name 			= 'home';
		$item->slug				= '';
		$item->path				= '';
		$item->pathWithoutType	= DIRECTORY_SEPARATOR . 'index';
		$item->key				= false;
		$item->keyPath			= '';
		$item->keyPathArray		= [''];
		$item->chapter			= false;
		$item->urlRel			= '/';
		$item->urlRelWoF		= '/';
		$item->urlAbs			= $baseUrl;
		$item->active			= false;
		$item->activeParent		= false;
		$item->hide 			= false;

		return $item;
	}

	public function renameItem($item, $newslug)
	{
		$folder 	= str_replace($item->originalName, '', $item->path);
		$oldname 	= $item->order . '-' . $item->slug;
		$newname 	= $item->order . '-' . $newslug;
		$result 	= true;

		if($item->elementType == 'folder')
		{
			$result = $this->storage->renameFile('contentFolder', $folder, $oldname, $newname);
		}

		if($item->elementType == 'file')
		{
			$filetypes 	= array('md', 'txt', 'yaml');
			$result 	= true;
			foreach($filetypes as $filetype)
			{
				$oldfilename = $oldname . '.' . $filetype;
				$newfilename = $newname . '.' . $filetype;

				$result = $this->storage->renameFile('contentFolder', $folder, $oldfilename, $newfilename);
			}
		}
		
		return $result;
	}

	public function getCurrentPage($args)
	{
		if(isset($args['route']))
		{
			$argSegments = explode("/", $args['route']);

			# check if the last url segment is a number
			$pageNumber = array_pop($argSegments);
			if(is_numeric($pageNumber) && $pageNumber < 10000)
			{
				# then check if the segment before the page is a "p" that indicates a paginator
				$pageIndicator = array_pop($argSegments);
				if($pageIndicator == "p")
				{
					return $pageNumber;
				}
			}
		}

		return false;		
	}

	public function removeHiddenPages($liveNavigation)
	{
		foreach($liveNavigation as $key => $item)
		{
			if(isset($item->hide) && $item->hide == true)
			{
				unset($liveNavigation[$key]);
			}
			elseif($item->elementType == 'folder' && !empty($item->folderContent))
			{
				$item->folderContent = $this->removeHiddenPages($item->folderContent);
			}
		}

		return $liveNavigation;
	}

	public function getBreadcrumb($navigation, $searchArray, $i = NULL, $breadcrumb = NULL)
	{
		# if it is the first round, create an empty array
		if(!$i){ $i = 0; $breadcrumb = array();}

		if(!$searchArray){ return $breadcrumb;}

		while($i < count($searchArray))
		{
			if(!isset($navigation[$searchArray[$i]])){ return false; }
			$item = $navigation[$searchArray[$i]];


			if($i == count($searchArray)-1)
			{
				$item->active = true;
			}
			else
			{
				$item->activeParent = true;
			}

			$copy = clone($item);
			if($copy->elementType == 'folder')
			{
				unset($copy->folderContent);
				$navigation = $item->folderContent;
			}
			$breadcrumb[] = $copy;
			
			$i++;
			return $this->getBreadcrumb($navigation, $searchArray, $i++, $breadcrumb);
		}

		return $breadcrumb;
	}

	public function getPagingForItem($navigation, $item)
	{		
		# if page is home
		if(trim($item->pathWithoutType, DIRECTORY_SEPARATOR) == 'index')
		{
			return $item;
		}

		$keyPos 			= count($item->keyPathArray)-1;
		$thisChapArray		= $item->keyPathArray;
		
		$item->thisChapter 	= false;
		$item->prevItem 	= false;
		$item->nextItem 	= false;
		
		if($keyPos > 0)
		{
			array_pop($thisChapArray);
			$item->thisChapter = $this->getItemWithKeyPath($navigation, $thisChapArray);
		}

		$flat = $this->flatten($navigation, $item->urlRel);

		$itemkey = $flat[0];

		# if no previous or next is found (e.g. hidden page)
		if(!is_int($itemkey))
		{
			return $item;
		}

		if($itemkey > 1)
		{
			$item->prevItem = $flat[$itemkey-1];
		}
		if(isset($flat[$itemkey+1]))
		{
			$item->nextItem = $flat[$itemkey+1];
		}

		return $item;
	}

	public function flatten($navigation, $urlRel, $flat = [])
	{
		foreach($navigation as $key => $item)
		{
			$flat[] = clone($item);

			if($item->urlRel == $urlRel)
			{
				array_unshift($flat, count($flat));
			}

			if($item->elementType == 'folder' && !empty($item->folderContent))
			{
				$last = array_key_last($flat);
				unset($flat[$last]->folderContent);
				$flat = $this->flatten($item->folderContent, $urlRel, $flat);
			}
		}

		return $flat;
	}

	# NOT IN USE ANYMORE BUT KEEP IT
	public function getItemWithUrl($navigation, $url, $result = NULL)
	{
		die('getItemWithURL in navigation model not in use.');

		foreach($navigation as $key => $item)
		{
			# set item active, needed to move item in navigation
			if($item->urlRelWoF === $url)
			{
				$result = $item;
				break;
			}
			elseif($item->elementType === "folder")
			{
				$result = self::getItemWithUrl($item->folderContent, $url, $result);

				if($result)
				{
					break;
				}
			}
		}

		return $result;
	}	
	

	# NOT IN USE ANYMORE BUT KEEP IT
	public function setActiveNaviItems($navigation, $breadcrumb)
	{
		die('setActiveNaviItems in navigation model not in use.');

		if($breadcrumb)
		{
			foreach($breadcrumb as $crumbkey => $page)
			{
				foreach($navigation as $itemkey => $item)
				{
					if($page->urlRelWoF == $item->urlRelWoF)
					{
						unset($breadcrumb[$crumbkey]);

						if(empty($breadcrumb))
						{
							$navigation[$itemkey]->active = true;
						}
						elseif(isset($navigation[$itemkey]->folderContent))
						{
							$navigation[$itemkey]->activeParent = true;
							$navigation[$itemkey]->folderContent = $this->setActiveNaviItems($navigation[$itemkey]->folderContent, $breadcrumb);
						}

						break;
					}
				}
			}
		}

		return $navigation;
	}

	# NOT IN USE ANYMORE
	public function getLastItemOfFolder($folder)
	{
		die('getLastItemOfFolder in navimodel not in use.');

		$lastItem = end($folder->folderContent);
		if(is_object($lastItem) && $lastItem->elementType == 'folder' && !empty($lastItem->folderContent))
		{
			return $this->getLastItemOfFolder($lastItem);
		}
		return $lastItem;
	}

}