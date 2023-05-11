<?php

namespace Typemill\Models;

use Typemill\Models\StorageWrapper;
use Typemill\Models\Folder;

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

	public function getSystemNavigation($userrole, $acl, $urlinfo)
	{
		$systemnavi 	= $this->storage->getYaml('systemSettings', '', 'systemnavi.yaml');
#		$systemnavi 	= $this->c->get('dispatcher')->dispatch(new OnSystemnaviLoaded($systemnavi), 'onSystemnaviLoaded')->getData();

		$allowedsystemnavi = [];

		foreach($systemnavi as $name => $naviitem)
		{
			# check if the navi-item is active (e.g if segments like "content" or "system" is in current url)
			# a bit fragile because url-segment and name/key in systemnavi.yaml and plugins have to be the same
			if(strpos($urlinfo['route'], 'tm/' . $name))
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

#		$this->clearNavigation(['extended' => true, 'draft' => true, 'live' => true]);

		$this->draftNavigation = $this->storage->getFile('dataFolder', $this->naviFolder, $this->draftNaviName, 'unserialize');



/*		echo '<pre>';
		$draftContentTree = $this->scanFolder($this->storage->getFolderPath('contentFolder'), true);
		$draftNavigation = $this->getFolderContentDetails($draftContentTree, $language, $urlinfo['baseurl'], $urlinfo['basepath']);
		print_r($draftNavigation);
		die();
*/

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

		$contentFolder = $this->storage->getFolderPath('contentFolder');

		foreach ($navigation as $key => $item)
		{
			# $filename = ($item->elementType == 'folder') ? DIRECTORY_SEPARATOR . 'index.yaml' : $item->pathWithoutType . '.yaml';
			$filename = $item->pathWithoutType . '.yaml';

			if(file_exists($contentFolder . $filename))
			{
				# read file
				$meta = $this->storage->getYaml($contentFolder, '', $filename);
			}
			else
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

	public function setActiveNaviItems($navigation, array $searchArray)
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
					$navigation[$itemKey]->folderContent = $this->setActiveNaviItems($navigation[$itemKey]->folderContent, $searchArray);
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
		$item->active			= true;
		$item->activeParent		= false;
		$item->hide 			= false;

		return $item;
	}






############################## TODO
	# reads the cached structure with published pages
	public function getLiveNavigation()
	{
		# get the cached navi
		$liveNavi = $this->storage->getFile('naviFolder', $this->naviFolder, $this->liveNaviName, 'unserialize');

		# if there is no cached structure
		if(!$liveNavi)
		{
			return $this->createNewLiveNavigation();
		}

		return $liveNavi;
	}


################################## TODO
	# creates a fresh structure with published pages
	private function createNewLiveNavigation($urlinfo, $language)
	{
		# scan the content of the folder
		$draftNavi = $this->scanFolder($this->storage->contentFolder, $draft = false);

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
		$extended = $yaml->getYaml('cacheFolder', '', 'structure-extended.yaml');

		if(isset($extended[$item->urlRelWoF]))
		{
			$newUrl = $newFolder->urlRelWoF . '/' . $item->slug;

			$entry = $extended[$item->urlRelWoF];
			
			unset($extended[$item->urlRelWoF]);
			
			$extended[$newUrl] = $entry;
			$yaml->updateYaml('cacheFolder', '', 'structure-extended.yaml', $extended);
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
			$yaml->updateYaml('cacheFolder', '', 'structure-extended.yaml', $extended);
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
				$yaml->updateYaml('cacheFolder', '', 'structure-extended.yaml', $extended);
			}
		}
	}
}