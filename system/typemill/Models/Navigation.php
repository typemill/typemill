<?php

namespace Typemill\Models;

use Typemill\Models\StorageWrapper;
use Typemill\Models\Folder;
use Typemill\Events\OnSystemnaviLoaded;

class Navigation extends Folder
{
	private $storage;

	private $naviFolder;

	private $liveNaviName;

	private $draftNaviName;

	private $extendedNaviName;

	private $extendedNavigation = false;

	private $draftNavigation = false;

	private $basicDraftNavigation = false;

	private $liveNavigation = false;

	private $basicLiveNavigation = false;

	public $activeNavigation = false;

	public function __construct()
	{
		$this->storage 				= new StorageWrapper('\Typemill\Models\Storage');

		$this->naviFolder 			= 'navigation';

		$this->liveNaviName 		= 'navi-live.txt';

		$this->draftNaviName 		= 'navi-draft.txt';

		$this->extendedNaviName 	= 'navi-extended.txt';
	}

	# use array ['extended' => true, 'draft' => true, 'live' => true] to clear files
	public function clearNavigation(array $deleteitems = NULL )
	{
		$result = false;

		# clear cache 
		$this->extendedNavigation 		= false;
		$this->draftNavigation 			= false;
		$this->basicDraftNavigation 	= false;
		$this->liveNavigation 			= false;
		$this->basicLiveNavigation 		= false;

		$navifiles = [
			'extended' 	=> $this->extendedNaviName,
			'draft' 	=> $this->draftNaviName,
			'live'		=> $this->liveNaviName
		];

		if($deleteitems)
		{
			$navifiles = array_intersect_key($navifiles, $deleteitems);
		}

		foreach($navifiles as $navifile)
		{
			$result = $this->storage->deleteFile('dataFolder', $this->naviFolder, $navifile);
		}

		return $result;
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

	# get the navigation with draft files for author environment
	public function getDraftNavigation($urlinfo, $language, $userrole = null, $username = null)
	{
		# todo: filter for userrole or username 

		$this->draftNavigation = $this->storage->getFile('dataFolder', $this->naviFolder, $this->draftNaviName, 'unserialize');

		if($this->draftNavigation)
		{
			return $this->draftNavigation;
		}

		# if there is no cached navi, create a basic new draft navi
		$basicDraftNavigation = $this->getBasicDraftNavigation($urlinfo, $language);

		# get the extended navigation with additional infos from the meta-files like title or hidden pages
		$extendedNavigation = $this->getExtendedNavigation($urlinfo, $language);

		# merge the basic draft navi with the extended infos from meta-files
		$draftNavigation = $this->mergeNavigationWithExtended($basicDraftNavigation, $extendedNavigation);

		# cache it
		$this->storage->writeFile('dataFolder', $this->naviFolder, $this->draftNaviName, $draftNavigation, 'serialize');

		return $draftNavigation;
	}

	public function getBasicDraftNavigation($urlinfo, $language)
	{
		if(!$this->basicDraftNavigation)
		{
			$this->basicDraftNavigation = $this->createBasicDraftNavigation($urlinfo, $language);
		}
		return $this->basicDraftNavigation;
	}

	# creates a fresh structure with published and non-published pages for the author
	public function createBasicDraftNavigation($urlinfo, $language)
	{
		# scan the content of the folder
		$draftContentTree = $this->scanFolder($this->storage->getFolderPath('contentFolder'), $draft = true);

		# if there is content, then get the content details
		if(count($draftContentTree) > 0)
		{
			$draftNavigation = $this->getFolderContentDetails($draftContentTree, $language, $urlinfo['baseurl'], $urlinfo['basepath']);
			
			return $draftNavigation;
		}

		return false;
	}

	# get the navigation with draft files for author environment
	public function getLiveNavigation($urlinfo, $language, $userrole = null, $username = null)
	{
		# todo: filter for userrole or username 

		$this->liveNavigation = $this->storage->getFile('dataFolder', $this->naviFolder, $this->liveNaviName, 'unserialize');

		if($this->liveNavigation)
		{
			return $this->liveNavigation;
		}

		# if there is no cached navi, create a basic new draft navi
		$basicLiveNavigation = $this->getBasicLiveNavigation($urlinfo, $language);

		# get the extended navigation with additional infos from the meta-files like title or hidden pages
		$extendedNavigation = $this->getExtendedNavigation($urlinfo, $language);

		# merge the basic draft navi with the extended infos from meta-files
		$liveNavigation = $this->mergeNavigationWithExtended($basicLiveNavigation, $extendedNavigation);

		# cache it
		$this->storage->writeFile('dataFolder', $this->naviFolder, $this->liveNaviName, $liveNavigation, 'serialize');

		return $liveNavigation;
	}

	public function getBasicLiveNavigation($urlinfo, $language)
	{
		if(!$this->basicLiveNavigation)
		{
			$this->basicLiveNavigation = $this->createBasicLiveNavigation($urlinfo, $language);
		}
		return $this->basicLiveNavigation;
	}

	public function createBasicLiveNavigation($urlinfo, $language)
	{
		# scan the content of the folder
		$liveContentTree = $this->scanFolder($this->storage->getFolderPath('contentFolder'), $draft = false);

		# if there is content, then get the content details
		if(count($liveContentTree) > 0)
		{
			$liveNavigation = $this->getFolderContentDetails($liveContentTree, $language, $urlinfo['baseurl'], $urlinfo['basepath']);
			
			return $liveNavigation;
		}

		return false;
	}

	# get the extended navigation with additional infos from the meta-files like title or hidden pages
	public function getExtendedNavigation($urlinfo, $language)
	{
		if(!$this->extendedNavigation)
		{
			# read the extended navi file
			$this->extendedNavigation = $this->storage->getYaml('dataFolder', $this->naviFolder, $this->extendedNaviName);
		}

		if(!$this->extendedNavigation)
		{
			$basicDraftNavigation = $this->getBasicDraftNavigation($urlinfo, $language);

			$this->extendedNavigation = $this->createExtendedNavigation($basicDraftNavigation, $extended = NULL);
		
			# cache it
			$this->storage->updateYaml('dataFolder', $this->naviFolder, $this->extendedNaviName, $this->extendedNavigation);
		}

		return $this->extendedNavigation;
	}

	# reads all meta-files and creates an array with url => ['hide' => bool, 'navtitle' => 'bla']
	public function createExtendedNavigation($navigation, $extended = NULL)
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
				$extended = $this->createExtendedNavigation($item->folderContent, $extended);
			}
		}

		return $extended;
	}

	# merge a basic navigation (live or draft) with extended information from meta
	public function mergeNavigationWithExtended($navigation, $extended)
	{
		$mergedNavigation = [];

		foreach($navigation as $key => $item)
		{
			if($extended && isset($extended[$item->urlRelWoF]))
			{
				$item->name 		= ($extended[$item->urlRelWoF]['navtitle'] != '') ? $extended[$item->urlRelWoF]['navtitle'] : $item->name;
				$item->hide 		= ($extended[$item->urlRelWoF]['hide'] === true) ? true : false;
				$item->noindex		= (isset($extended[$item->urlRelWoF]['noindex']) && $extended[$item->urlRelWoF]['noindex'] === true) ? true : false;
			}

			if($item->elementType == 'folder')
			{
				$item->folderContent = $this->mergeNavigationWithExtended($item->folderContent, $extended);
			}

			$mergedNavigation[] = $item;
		}

		return $mergedNavigation;
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

	# only used for folders to get items from live-navigation without hidden and unpublished pages
	public function getItemWithUrl($navigation, $url, $result = NULL)
	{
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
	

	# used with scan folder that generates own indexes for live version
	public function setActiveNaviItems($navigation, $breadcrumb)
	{
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

	public function getLastItemOfFolder($folder)
	{
		$lastItem = end($folder->folderContent);
		if(is_object($lastItem) && $lastItem->elementType == 'folder' && !empty($lastItem->folderContent))
		{
			return $this->getLastItemOfFolder($lastItem);
		}
		return $lastItem;
	}

}