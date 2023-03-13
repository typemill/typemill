<?php

namespace Typemill\Models;

use Typemill\Models\StorageWrapper;
use Typemill\Models\Folder;

class Navigation
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

	public function __construct()
	{
		$this->storage 				= new StorageWrapper('\Typemill\Models\Storage');

		$this->naviFolder 			= 'data' . DIRECTORY_SEPARATOR . 'navigation';

		$this->liveNaviName 		= 'navi-live.txt';

		$this->draftNaviName 		= 'navi-draft.txt';

		$this->extendedNaviName 	= 'navi-extended.txt';
	}

	public function getMainNavigation($userrole, $acl, $urlinfo, $editor)
	{
		$mainnavi 		= $this->storage->getYaml('system/typemill/settings', 'mainnavi.yaml');

		$allowedmainnavi = [];

		foreach($mainnavi as $name => $naviitem)
		{
			if($acl->isAllowed($userrole, $naviitem['aclresource'], $naviitem['aclprivilege']))
			{
				# set the navi of current route active
				$thisRoute = '/tm/' . $name;
				if(strpos($urlinfo['route'], $thisRoute) !== false)
				{
					$naviitem['active'] = true;
				}

				$allowedmainnavi[$name] = $naviitem;
			}
		}

		# if system is there, then we do not need the account item
		if(isset($allowedmainnavi['system']))
		{
			unset($allowedmainnavi['account']);
		}

		# set correct editor mode according to user settings
		if(isset($allowedmainnavi['content']) && $editor == 'raw')
		{
			$allowedmainnavi['content']['routename'] = "content.raw";
		}

		return $allowedmainnavi;
	}


	# get the navigation with draft files for author environment
	public function getDraftNavigation($urlinfo, $language, $userrole = null, $username = null)
	{
		# todo: filter for userrole or username 

		$this->draftNavigation = $this->storage->getFile($this->naviFolder, $this->draftNaviName, 'unserialize');

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
		$this->storage->writeFile($this->naviFolder, $this->draftNaviName, $draftNavigation, 'serialize');

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
		$folder = new Folder();

		# scan the content of the folder
		$draftContentTree = $folder->scanFolder($this->storage->getStorageInfo('contentFolder'), $draft = true);

		# if there is content, then get the content details
		if(count($draftContentTree) > 0)
		{
			$draftNavigation = $folder->getFolderContentDetails($draftContentTree, $language, $urlinfo['baseurl'], $urlinfo['basepath']);
			
			return $draftNavigation;
		}

		return false;
	}

	# get the extended navigation with additional infos from the meta-files like title or hidden pages
	public function getExtendedNavigation($urlinfo, $language)
	{
		if(!$this->extendedNavigation)
		{
			# read the extended navi file
			$this->extendedNavigation = $this->storage->getYaml($this->naviFolder, $this->extendedNaviName);
		}

		if(!$this->extendedNavigation)
		{
			$basicDraftNavigation = $this->getBasicDraftNavigation($urlinfo, $language);

			$this->extendedNavigation = $this->createExtendedNavigation($basicDraftNavigation, $extended = NULL);
		
			# cache it
			$this->storage->updateYaml($this->naviFolder, $this->extendedNaviName, $this->extendedNavigation);
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

		$contentFolder = $this->storage->getStorageInfo('contentFolder');

		foreach ($navigation as $key => $item)
		{
			# $filename = ($item->elementType == 'folder') ? DIRECTORY_SEPARATOR . 'index.yaml' : $item->pathWithoutType . '.yaml';
			$filename = $item->pathWithoutType . '.yaml';

			if(file_exists($contentFolder . $filename))
			{
				# read file
				$meta = $this->storage->getYaml($contentFolder, $filename);

				$extended[$item->urlRelWoF]['navtitle'] 	= isset($meta['meta']['navtitle']) ? $meta['meta']['navtitle'] : '';
				$extended[$item->urlRelWoF]['hide'] 		= isset($meta['meta']['hide']) ? $meta['meta']['hide'] : false;
				$extended[$item->urlRelWoF]['noindex'] 		= isset($meta['meta']['noindex']) ? $meta['meta']['noindex'] : false;
				$extended[$item->urlRelWoF]['path']			= $item->path;
				$extended[$item->urlRelWoF]['keyPath']		= $item->keyPath;
			}

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

	public function getItemWithKeyPath($navigation, array $searchArray)
	{
		$item = false;

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

	# reads the cached structure with published pages
	public function getLiveNavigation()
	{
		# get the cached navi
		$liveNavi = $this->storage->getFile($this->naviFolder, $this->liveNaviName, 'unserialize');

		# if there is no cached structure
		if(!$liveNavi)
		{
			return $this->createNewLiveNavigation();
		}

		return $liveNavi;
	}

	# creates a fresh structure with published pages
	private function createNewLiveNavigation($urlinfo, $language)
	{
		$folder = new Folder();

		# scan the content of the folder
		$draftNavi = $folder->scanFolder($this->storage->contentFolder, $draft = false);

		# if there is content, then get the content details
		if($draftNavi && count($draftNavi) > 0)
		{
			# get the extended structure files with changes like navigation title or hidden pages
			$extended = $this->getExtendedNavi();

			# create an array of object with the whole content of the folder and changes from extended file
			$liveNavi = $folder->getFolderContentDetails($liveNavi, $extended, $this->settings, $this->uri->getBaseUrl(), $this->uri->getBasePath());
			
			# cache structure live
			$this->storage->writeFile($this->naviFolder, $this->liveNaviName, $liveNavi, 'serialize');

			return $liveNavi;
		}

		return false;
	}










	# only backoffice
	protected function renameExtended($item, $newFolder)
	{
		# get the extended structure files with changes like navigation title or hidden pages
		$yaml = new writeYaml();
		$extended = $yaml->getYaml('cache', 'structure-extended.yaml');

		if(isset($extended[$item->urlRelWoF]))
		{
			$newUrl = $newFolder->urlRelWoF . '/' . $item->slug;

			$entry = $extended[$item->urlRelWoF];
			
			unset($extended[$item->urlRelWoF]);
			
			$extended[$newUrl] = $entry;
			$yaml->updateYaml('cache', 'structure-extended.yaml', $extended);
		}

		return true;
	}

	# only backoffice
	protected function deleteFromExtended()
	{
		# get the extended structure files with changes like navigation title or hidden pages
		$yaml = new writeYaml();
		$extended = $yaml->getYaml('cache', 'structure-extended.yaml');

		if($this->item->elementType == "file" && isset($extended[$this->item->urlRelWoF]))
		{
			unset($extended[$this->item->urlRelWoF]);
			$yaml->updateYaml('cache', 'structure-extended.yaml', $extended);
		}

		if($this->item->elementType == "folder")
		{
			$changed = false;

			# delete all entries with that folder url
			foreach($extended as $url => $entries)
			{
				if( strpos($url, $this->item->urlRelWoF) !== false )
				{
					$changed = true;
					unset($extended[$url]);
				}
			}

			if($changed)
			{
				$yaml->updateYaml('cache', 'structure-extended.yaml', $extended);
			}
		}
	}
}