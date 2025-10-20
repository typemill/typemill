<?php

namespace Typemill\Models;

use Typemill\Models\StorageWrapper;
use Typemill\Models\Folder;
use Typemill\Events\OnSystemnaviLoaded;

class Navigation extends Folder
{
	private $storage = NULL;

	private $naviFolder = NULL;

	private $project = NULL; 

	private $draftNaviName = NULL;

	private $DS = NULL;

	public function __construct()
	{
		$this->storage 				= new StorageWrapper('\Typemill\Models\Storage');

		$this->naviFolder 			= 'navigation';

		$this->draftNaviName 		= 'draft-navi';

		$this->DS 					= DIRECTORY_SEPARATOR;
	}

	# set the current project from url and initialize project folders
	public function setProject($settings, $url)
	{
		$project = $this->getProjectFromUrl($url, $settings);
	
		if($project)
		{
			$this->project 	= strtolower($project);

			$projectPath = '_' . $this->project;
			$projectNaviPath =  $this->naviFolder . $this->DS . $projectPath;

			# create folder of navigation
			if(!$this->storage->checkFolder('dataFolder', $projectNaviPath))
			{
				$this->storage->createFolder('dataFolder', $projectNaviPath);
			}

			# create startpage with initial content
			if(!$this->storage->checkFolder('contentFolder', $projectPath))
			{
				$this->storage->createFolder('contentFolder', $projectPath);

				$content = "# Welcome\n\nContent";
				$this->storage->writeFile('contentFolder', $projectPath, 'index.md', $content);
			}
		}
	}

	public function getProject()
	{
		return $this->project;
	}

	# /_name for content files and navigation files
	public function getProjectFolder()
	{
		if($this->project)
		{
			return $this->DS . '_' . $this->project;
		}

		return '';
	}

	# RENAME: getPathForNavi
	private function getNaviFolderPath()
	{
		$dataPath 	= $this->storage->getFolderPath('dataFolder');
		$naviPath 	= $dataPath . DIRECTORY_SEPARATOR . $this->naviFolder;

		return $naviPath;
	}

	# RENAME getProjectPathForNavi
	private function getProjectFolderPath()
	{
		$dataPath 	= $this->storage->getFolderPath('dataFolder');
		$naviPath 	= $dataPath . DIRECTORY_SEPARATOR . $this->naviFolder;
		if($this->project)
		{
			$naviPath .= DIRECTORY_SEPARATOR . '_' . $this->project;
		}

		return $naviPath;
	}

	# this is wrong? _ is missing???
	private function getNaviFolder()
	{
		$folder = $this->naviFolder;
		if($this->project)
		{
			$folder .= DIRECTORY_SEPARATOR . $this->project; 
		}
		return $folder;
	}

	public function getUrlSegments($url)
	{
		return explode('/', trim($url, '/'));
	}

	public function getPathSegments($path)
	{
		#normalize
		return explode($this->DS, trim($path, $this->DS));
	}


  	# used by getPageInfoForUrl()
	public function getStartUrl($urlSegments)
	{
		$startUrl = '/';
		
		if(is_array($urlSegments) && isset($urlSegments[0]))
		{
			# use the first segment per default, so /page
			$startUrl .= $urlSegments[0];

			# use the first two segments if a lang or project is active, so /de/page
			if($this->project && isset($urlSegments[1]))
			{
				$startUrl .= '/' . $urlSegments[1];
			}
		}

		return $startUrl;
	}

	/* NOT IN USE
	public function getStartPath($pathSegments)
	{
		$startPath = $this->DS;

		if(is_array($pathSegments) && isset($pathSegments[0]))
		{
			# use the first segment per default, so /00-page
			$startPath .= $pathSegments[0];

			# use the first two segments if a lang or project is active, so /_de/00-page
			if($this->project && isset($pathSegments[1]))
			{
				$startPath .= '/' . $pathSegments[1];
			}
		}

		return $startPath;
	}
	*/

	# used to show projects in frontend and interface
	public function getAllProjects($settings)
	{
		if($this->checkProjectSettings($settings))
		{
			$projects =[];
			$projects[] = [
				'id' 		=> $settings['baseprojectid'], 
				'label' 	=> $settings['baseprojectlabel'],
				'active' 	=> ($this->project == $settings['baseprojectid']) ? true : false,
				'base'		=> true
			];

			foreach($settings['projectinstances'] as $id => $label)
			{
				$projects[] = [
					'id' 		=> $id, 
					'label' 	=> $label,
					'active' 	=> ($this->project == $id) ? true : false,
					'base'		=> false
				];
			}

			return $projects;
		}

		return false;
	}

	# get the project from the url
	public function getProjectFromUrl($url, $settings)
	{
	    $project = null;

	    if ($this->checkProjectSettings($settings))
	    {
	        $url           = $this->removeEditorFromUrl($url);
	        $segments      = explode('/', trim($url, '/'));
	        $firstSegment  = $segments[0] ?? null;

	        $projects      = array_keys($settings['projectinstances']);
	        if ($firstSegment && in_array($firstSegment, $projects))
	        {
	            $project = $firstSegment;
	        }
	    }

	    return $project;
	}

    public function checkProjectSettings($settings): bool
    {
	    if (
	        empty($settings['projects']) ||
	        $settings['projects'] == 'standard' ||
	        empty($settings['baseprojectid']) ||
	        empty($settings['baseprojectlabel']) ||
	        empty($settings['projectinstances']) ||
	        !is_array($settings['projectinstances'])
	    ) {
	        return false;
	    }
	    return true;
	}

	public function isHome($url)
	{
		if($url == '/')
		{
			return true;
		}

		if($this->project && $url == '/' . $this->project)
		{
			return true;
		}

		return false;
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

		# we want to clear only the folder for the current project or lang
		$naviPath = $this->getProjectFolderPath();

		if(!is_dir($naviPath))
		{
			return false;
		}

		$naviFiles 	= array_values(array_diff(scandir($naviPath), ['.', '..']));

		if($this->project)
		{
			foreach($naviFiles as &$value)
			{
				$value = '_' . $this->project . $this->DS . $value;
			}
		}
		
		# filter only specific items
		if($deleteItems)
		{
			foreach ($deleteItems as &$value)
			{
				# replace '' (base item) with the name of the base navigation
			    if ($value === '/')
			    {
			        $value = $this->draftNaviName;
			    }
			    else
			    {
			    	$value = trim($value, $this->DS);
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

	public function getItemsForSlug($slug, $urlinfo, $langattr)
	{
		$draftNavigation = $this->getFullDraftNavigation($urlinfo, $langattr);
		
		if(!$draftNavigation)
		{
			return false;
		}

		$items = $this->findItemsWithSlug($draftNavigation, $slug);

		return $items;
	}

	public function getItemForUrl($url, $urlinfo, $langattr)
	{
		$url = $this->removeEditorFromUrl($url);

		$home = $this->project ? '/' . $this->project : '/';

		if($url == $home)
		{
			return $this->getHomepageItem($urlinfo['baseurl']);
		}

		$pageinfo = $this->getPageInfoForUrl($url, $urlinfo, $langattr);

		if(!$pageinfo)
		{
			return false;
		}

		# pageinfo['path'] has project or lang segment like /_de/01-page
		$foldername = $this->getNaviFileNameForPath($pageinfo['path']);

		$draftNavigation = $this->getFullDraftNavigation($urlinfo, $langattr, $foldername);
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
		# fix for pages like /system/
		$url = '/' . trim($url, '/');

		# get the first level navigation
		$itempath = $this->project ? '_' . $this->project : $this->DS;
		$firstLevelExtended = $this->getExtendedNavigation($urlinfo, $langattr, $itempath);

		$urlSegments 	= $this->getUrlSegments($url);
		$startUrl 		= $this->getStartUrl($urlSegments);

		$pageinfo 		= $firstLevelExtended[$startUrl] ?? false;

		# first level does not exist
		if(!$pageinfo)
		{
			return false;
		}

		# url is first level
		if($url == $startUrl)
		{
			return $pageinfo;
		}

# ???? can be page or de/page
		$startFolder = trim($pageinfo['path'], $this->DS);

		$extendedNavigation = $this->getExtendedNavigation($urlinfo, $langattr, $startFolder);

		$pageinfo = $extendedNavigation[$url] ?? false;
		if(!$pageinfo)
		{
			return false;
		}

		return $pageinfo;
	}

	/* 
	* params: an itempath (with or without a language or project segment)
	* returns: 
	* * the name of the base segment, if there are more segments, so /_en/00-firstfolder
	* * or the name of the first-folder navigation cache, so /_en/draft-navi
	* usage: add .txt or -extended.txt to get the cached navigation file 
	*/

	# which navi to delete
	public function getNaviFileNameForPath($itempath)
	{
		return $this->whichNaviToDelete($itempath);
	}

	public function whichNaviToDelete($itempath)
	{
		$pathSegments = $this->getPathSegments($itempath);

		if($this->project)
		{
			$projectPath = $this->DS . $pathSegments[0] . $this->DS;
			if(isset($pathSegments[2]))
			{
				return $projectPath . $pathSegments[1]; # /_de/getting-started
			}

			return $projectPath . $this->draftNaviName; # /_de/draft-navi
		}

		if(isset($pathSegments[1]))
		{
			return $this->DS . $pathSegments[0]; # /getting-started
		}

		return $this->DS . $this->draftNaviName; # /draft-navi		
	}

	# which navi to load
	public function whichNaviToLoad($itempath)
	{
		$pathSegments = $this->getPathSegments($itempath);

		if($this->project)
		{
			$projectPath = $this->DS . $pathSegments[0] . $this->DS;
			if(isset($pathSegments[1]))
			{
				return $projectPath . $pathSegments[1]; # /_de/getting-started
			}

			return $projectPath . $this->draftNaviName; # /_de/draft-navi
		}

		if(isset($pathSegments[0]) && $pathSegments[0] != '')
		{
			return $this->DS . $pathSegments[0]; # /getting-started
		}

		return $this->DS . $this->draftNaviName; # /draft-navi
	}


	public function removeEditorFromUrl($url)
	{
		$url = trim($url, '/');

		$url = str_replace('tm/content/visual', '', $url);
		$url = str_replace('tm/content/raw', '', $url);

		$url = trim($url, '/');

		return '/' . $url;
	}

	public function getLiveNavigation($urlinfo, $langattr)
	{
		$draftNavigation = $this->getFullDraftNavigation($urlinfo, $langattr);

		$liveNavigation = $this->generateLiveNavigationFromDraft($draftNavigation);

		$liveNavigation = $this->removePages($liveNavigation, $hidden = true, $restricted = false);

		return $liveNavigation;
	}

	# ASK FOR THE FULL DRAFT NAVIGATION AND MERGE ALL SEPARATED NAVIGATIONS
	public function getFullDraftNavigation($urlinfo, $language, $userrole = null, $username = null)
	{
		# generate the item path for the default draft navigation
		$itempath = $this->DS;
		if($this->project)
		{
			$itempath .= '_' . $this->project;
		}

		$draftNavigation = $this->getDraftNavigation($urlinfo, $language, $itempath);
		if(!$draftNavigation)
		{
			return false;
		}

		foreach($draftNavigation as $key => $item)
		{
			if($item->elementType == 'folder')
			{
				$subfolder = $this->getDraftNavigation($urlinfo, $language, $item->path);

				$draftNavigation[$key]->folderContent = $subfolder[$key]->folderContent;
			}
		}

		return $draftNavigation;
	}

	public function getFullExtendedNavigation($urlinfo, $langattr)
	{
		# generate the item path for the default draft navigation
		$itempath = $this->DS;
		if($this->project)
		{
			$itempath .= '_' . $this->project;
		}

		$firstLevelExtended = $this->getExtendedNavigation($urlinfo, $langattr, $itempath);

		$extended = [];

		foreach($firstLevelExtended as $key => $item)
		{
		    if(!$item['path'])
		    {
		        continue;
		    }
		    $extension = pathinfo($item['path'], PATHINFO_EXTENSION);
		    if($extension)
		    {
		    	# skip files
		        continue;
		    }

			$folderContent 	= $this->getExtendedNavigation($urlinfo, $langattr, $item['path']);
			$extended 		= $extended + $folderContent;
		}

		return $extended;
	}

	# ASK FOR A STATIC DRAFT NAVIGATION AND CREATE ONE IF NOT THERE
	public function getDraftNavigation($urlinfo, $language, $itempath)
	{
		$navipath 		= $this->whichNaviToLoad($itempath);

		$draftNavigation 	= $this->storage->getFile('dataFolder', $this->naviFolder, $navipath . '.txt', 'unserialize');
		if($draftNavigation)
		{
			return $draftNavigation;
		}

		$rawDraftNavigation = $this->generateRawDraftNavigation($urlinfo, $language, $itempath);
		if(!$rawDraftNavigation)
		{
			return false;
		}

		$extendedNavigation 	= $this->storage->getFile('dataFolder', $this->naviFolder, $navipath . '-extended.txt', 'unserialize');
		if(!$extendedNavigation)
		{
			$extendedNavigation = $this->generateExtendedFromDraft($rawDraftNavigation);

			if(!$extendedNavigation)
			{
				return false;
			}
			
			$this->storeStaticNavigation($navipath . '-extended.txt', $extendedNavigation);
		}

		$draftNavigation = $this->mergeExtendedWithDraft($rawDraftNavigation, $extendedNavigation);
		if(!$draftNavigation)
		{
			return false;
		}

		$this->storeStaticNavigation($navipath . '.txt', $draftNavigation);

		return $draftNavigation;
	}

	public function getExtendedNavigation($urlinfo, $language, $itempath)
	{
		$navipath 				= $this->whichNaviToLoad($itempath);

		$extendedNavigation 	= $this->storage->getFile('dataFolder', $this->naviFolder, $navipath . '-extended.txt', 'unserialize');

		if($extendedNavigation)
		{
			return $extendedNavigation;
		}

		$draftNavigation 		= $this->storage->getFile('dataFolder', $this->naviFolder, $navipath . '.txt', 'unserialize');
		if(!$draftNavigation)
		{
			# we have to create and store extended and draft in this case 

			$rawDraftNavigation = $this->generateRawDraftNavigation($urlinfo, $language, $itempath);
			if(!$rawDraftNavigation)
			{
				return false;
			}
		
			$extendedNavigation = $this->generateExtendedFromDraft($rawDraftNavigation);
			if(!$extendedNavigation)
			{
				return false;
			}
			
			$this->storeStaticNavigation($navipath . '-extended.txt', $extendedNavigation);

			$draftNavigation = $this->mergeExtendedWithDraft($rawDraftNavigation, $extendedNavigation);
			if(!$draftNavigation)
			{
				return false;
			}

			$this->storeStaticNavigation($navipath . '.txt', $draftNavigation);
			
			return $extendedNavigation;
		}

		# we only have to create and store extended in this case

		$extendedNavigation = $this->generateExtendedFromDraft($draftNavigation);

		if(!$extendedNavigation)
		{
			return false;
		}
		
		$this->storeStaticNavigation($navipath . '-extended.txt', $extendedNavigation);

		return $extendedNavigation;
	}

	# generates a raw draft navigation 
	private function generateRawDraftNavigation($urlinfo, $language, $filepath)
	{
		# this works, but it is horrible!!!
		# alternative: always base folder (flat = true) and subfolder, then merge both

		$filepath = trim($filepath, $this->DS);

		$contentFolder = $this->storage->getFolderPath('contentFolder');

		$flat = $filepath; # scan the whole folder
		if($filepath == '')
		{
			$flat = true; # scan only the first level of the folder
		}
		if($this->project)
		{
			$contentFolder = $this->storage->getFolderPath('contentFolder') . '_' . $this->project;

			if($filepath == '_' . $this->project)
			{
				$flat = true; # scan only the first level of the project folder
			}
			else
			{
				# the item will be scanned in folder _/de but will not have prefix _de, so 
				$flat = trim(str_replace('_'. $this->project, '', $filepath), $this->DS);
			}
		}

#		$contentFolder = $this->storage->getFolderPath('contentFolder') . $filepath;

		# scan the content of the folder
		$draftContentTree = $this->scanFolder($contentFolder, $flat);

		# if there is content, then get the content details
		if(count($draftContentTree) > 0)
		{
			if($this->project)
			{
				$draftNavigation = $this->getFolderContentDetails(
					$draftContentTree, 
					$language, 
					$baseurl 			= $urlinfo['baseurl'], 
					$slugWithFolder 	= $urlinfo['basepath'] . '/' . $this->project,
					$slugWithoutFolder 	= '/' . $this->project,
					$fullPath 			= DIRECTORY_SEPARATOR . '_' . $this->project
				);
			}
			else
			{
				$draftNavigation = $this->getFolderContentDetails(
					$draftContentTree, 
					$language, 
					$urlinfo['baseurl'], 
					$urlinfo['basepath']
				);
			}

			return $draftNavigation;
		}

		return false;
	}

	public function generateLiveNavigationFromDraft($draftNavigation)
	{
		if(!$draftNavigation OR empty($draftNavigation))
		{
			return [];
		}

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

	private function storeStaticNavigation($filepath, $data)
	{
# Maybe Remove
		if($filepath == '.txt' OR $filepath == '-extended.txt')
		{
			return false;
		}

		if($this->storage->writeFile('dataFolder', $this->naviFolder, $filepath, $data, 'serialize'))
		{
			return true;
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
			$extended[$item->urlRelWoF]['path']			= $item->path;
			$extended[$item->urlRelWoF]['keyPath']		= $item->keyPath;

			if(isset($meta['meta']['hide']) && $meta['meta']['hide'])
			{
				$extended[$item->urlRelWoF]['hide'] = $meta['meta']['hide'];
			}
			if(isset($meta['meta']['noindex']) && $meta['meta']['noindex'])
			{
				$extended[$item->urlRelWoF]['noindex'] 	= $meta['meta']['noindex'];
			}
			if(isset($meta['meta']['allowedrole']) && $meta['meta']['allowedrole'] )
			{
				$extended[$item->urlRelWoF]['allowedrole'] 	= $meta['meta']['allowedrole'];
			}
			if(isset($meta['meta']['alloweduser']) && $meta['meta']['alloweduser'] )
			{
				$extended[$item->urlRelWoF]['alloweduser'] 	= $meta['meta']['alloweduser'];
			}

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
				if(isset($extendedNavigation[$item->urlRelWoF]['hide']) && $extendedNavigation[$item->urlRelWoF]['hide'] === true)
				{
					$item->hide	= true;
				}
				if(isset($extendedNavigation[$item->urlRelWoF]['noindex']) && $extendedNavigation[$item->urlRelWoF]['noindex'] === true)
				{
					$item->noindex	= true;
				}
				if(isset($extendedNavigation[$item->urlRelWoF]['allowedrole']) && $extendedNavigation[$item->urlRelWoF]['allowedrole'])
				{
					$item->allowedrole = $extendedNavigation[$item->urlRelWoF]['allowedrole'];
				}
				if(isset($extendedNavigation[$item->urlRelWoF]['alloweduser']) && $extendedNavigation[$item->urlRelWoF]['alloweduser'])
				{
					$item->alloweduser = $extendedNavigation[$item->urlRelWoF]['alloweduser'];
				}
			}

			if($item->elementType == 'folder')
			{
				$item->folderContent = $this->mergeExtendedWithDraft($item->folderContent, $extendedNavigation);
			}

			$mergedNavigation[$key] = $item;
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
		$slug 		= '';
		$path 		= '';
		if($this->project)
		{
			$slug = $this->project;
			$path = $this->DS . '_' . $this->project;
		}

		$draft 	= $this->storage->getFile('contentFolder', $path, 'index.txt');

		# return a standard item-object
		$item 					= new \stdClass;

		$item->status 			= $draft ? 'modified' : 'published';
		$item->originalName 	= 'home';
		$item->elementType 		= 'folder';
		$item->fileType			= $draft ? 'mdtxt' : 'md';
		$item->order 			= false;
		$item->name 			= 'home';
		$item->slug				= $slug;
		$item->path				= $path;
		$item->pathWithoutType	= $path . DIRECTORY_SEPARATOR . 'index';
		$item->key				= false;
		$item->keyPath			= '';
		$item->keyPathArray		= [''];
		$item->chapter			= false;
		$item->urlRel			= '/' . $slug;
		$item->urlRelWoF		= '/' . $slug;
		$item->urlAbs			= trim($baseUrl, '/') . '/' . $slug;
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
# UPDATE
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

	public function removePages($liveNavigation, $hidden, $restricted)
	{
		foreach($liveNavigation as $key => $item)
		{
			$removed = false;

			if($hidden && (isset($item->hide) && $item->hide == true))
			{
				unset($liveNavigation[$key]);
				$removed = true;
			}

			if($restricted && !$removed)
			{
				if(isset($item->alloweduser) && $item->alloweduser)
				{
					# if user is logged in
					if(is_array($restricted) && isset($restricted['username']) && $restricted['username'])
					{
						$alloweduser = array_map('trim', explode(",", $item->alloweduser));
						if(!in_array($restricted['username'], $alloweduser))
						{
							# user has no access to page
							unset($liveNavigation[$key]);
							$removed = true;
						}						
					}
					else
					{
						# user is not logged in so should never have access
						unset($liveNavigation[$key]);
						$removed = true;
					}
				}
				elseif(isset($item->allowedrole))
				{
					# if user is logged in
					if(
						is_array($restricted) 
						&& isset($restricted['userrole']) 
						&& $restricted['userrole']
						&& isset($restricted['acl'])
						&& $restricted['acl']
					)
					{
						$userrole = $restricted['userrole'];
						$acl = $restricted['acl'];

						if(
							$userrole !== 'administrator' 
							AND $userrole !== $item->allowedrole
							AND !$acl->inheritsRole($userrole, $item->allowedrole)
						)
						{
							# user has no access to page
							unset($liveNavigation[$key]);
							$removed = true;
						}						
					}
					else
					{
						unset($liveNavigation[$key]);
						$removed = true;
					}
				}
			}

			if(!$removed && ($item->elementType == 'folder') && !empty($item->folderContent))
			{
				$item->folderContent = $this->removePages($item->folderContent, $hidden, $restricted);
			}
		}

		return $liveNavigation;
	}

	public function checkFolderAccess($url, $folderaccess)
	{
		if(!$folderaccess or $folderaccess == '' OR $url == '/')
		{
			return true;
		}

        # normalize allowed folders
        $folderlist = array_map(function($f)
        {
            return trim(explode('/', trim($f, '/'))[0]);
        }, explode(',', $folderaccess));

        # get first segment of current url
        $segments   = explode('/', trim($url, '/'));
        $firstSegment = $segments[0] ?? '';

        # check if first segment is explicitly allowed
        if(in_array($firstSegment, $folderlist, true))
        {
            return true;
        }

        return false;
	}

	public function getAllowedFolders($navigation, $folderaccess, $frontend = false)
	{
		if(!$folderaccess or $folderaccess == '')
		{
			return true;
		}

        # normalize allowed folders
        $folderlist = array_map(function($f)
        {
            return trim(explode('/', trim($f, '/'))[0]);
        }, explode(',', $folderaccess));

        $allowedNavigation = [];

        foreach($navigation as $key => $item)
        {
	        # get first segment of current url
	        $segments   = explode('/', trim($item->urlRelWoF, '/'));
	        $firstSegment = $segments[0] ?? '';

	        # check if first segment is explicitly allowed
	        if(in_array($firstSegment, $folderlist, true))
	        {
	        	# in frontend we need to keep the keys to use findItemWithKeyPath
	        	if($frontend)
	        	{
		            $allowedNavigation[$key] = $item;
	        	}
	        	else
	        	{
		            $allowedNavigation[] = $item;	        		
	        	}
	        }
        }

        return $allowedNavigation;
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
		if(!$item)
		{
			return $item;
		}
		
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

		$itemkey = isset($flat[0]) ? $flat[0] : false;

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

	# only used by public api
	public function findItemsWithSlug($navigation, $slug, $result = NULL)
	{
		foreach($navigation as $key => $item)
		{
			# set item active, needed to move item in navigation
			if($item->slug === $slug)
			{
				$result[] = $item;
			}
			elseif($item->elementType === "folder")
			{
				$result = self::findItemsWithSlug($item->folderContent, $slug, $result);
			}
		}

		return $result;
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