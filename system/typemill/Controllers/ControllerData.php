<?php

namespace Typemill\Controllers;

use Typemill\Models\StorageWrapper;
use Typemill\Events\OnSystemnaviLoaded;

# this controller handels data for web and api
# web will use data for twig output
# api will use data for json output
# data controller will provide neutral data

class ControllerData extends Controller
{
	protected $errors = [];

	protected function getMainNavigation($userrole)
	{
		$storage 		= new StorageWrapper('\Typemill\Models\Storage');

		$mainnavi 		= $storage->getYaml('system/typemill/settings', 'mainnavi.yaml');

		$allowedmainnavi = [];

		$acl 			= $this->c->get('acl');

		foreach($mainnavi as $name => $naviitem)
		{
			if($acl->isAllowed($userrole, $naviitem['aclresource'], $naviitem['aclprivilege']))
			{
				# not nice: check if the navi-item is active (e.g if segments like "content" or "system" is in current url)
				if($name == 'content' && strpos($this->c->get('urlinfo')['route'], 'tm/content'))
				{
					$naviitem['active'] = true;
				}
				elseif($name == 'account' && strpos($this->c->get('urlinfo')['route'], 'tm/account'))
				{
					$naviitem['active'] = true;
				}
				elseif($name == 'system')
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
		if(isset($allowedmainnavi['content']) && $this->settings['editor'] == 'raw')
		{
			$allowedmainnavi['content']['routename'] = "content.raw";
		}

		return $allowedmainnavi;
	}

	protected function getSystemNavigation($userrole)
	{
		$storage 		= new StorageWrapper('\Typemill\Models\Storage');

		$systemnavi 	= $storage->getYaml('system/typemill/settings', 'systemnavi.yaml');
		$systemnavi 	= $this->c->get('dispatcher')->dispatch(new OnSystemnaviLoaded($systemnavi), 'onSystemnaviLoaded')->getData();

		$allowedsystemnavi = [];

		$acl 			= $this->c->get('acl');

		foreach($systemnavi as $name => $naviitem)
		{
			# check if the navi-item is active (e.g if segments like "content" or "system" is in current url)
			# a bit fragile because url-segment and name/key in systemnavi.yaml and plugins have to be the same
			if(strpos($this->c->get('urlinfo')['route'], 'tm/' . $name))
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

	protected function getThemeDetails()
	{
		$themes = $this->getThemes();

		$themeDetails = [];
		foreach($themes as $themeName)
		{
			$themeDetails[$themeName] = $this->getThemeDefinition($themeName);
		}

		return $themeDetails;
	}

	protected function getThemes()
	{
		$themeFolder 	= $this->c->get('settings')['rootPath'] . DIRECTORY_SEPARATOR . $this->c->get('settings')['themeFolder'];
		$themeFolderC 	= scandir($themeFolder);
		$themes 		= [];
		foreach ($themeFolderC as $key => $theme)
		{
			if (!in_array($theme, [".",".."]))
			{
				if (is_dir($themeFolder . DIRECTORY_SEPARATOR . $theme))
				{
					$themes[] = $theme;
				}
			}
		}

		return $themes;
	}

	protected function getThemeDefinition($themeName)
	{
		$storage 			= new StorageWrapper('\Typemill\Models\Storage');

		$themeSettings 		= $storage->getYaml('themes' . DIRECTORY_SEPARATOR . $themeName, $themeName . '.yaml');

		# add standard-textarea for custom css
		$themeSettings['forms']['fields']['customcss'] = [
			'type' 			=> 'codearea', 
			'label' 		=> 'Custom CSS', 
			'class' 		=> 'codearea', 
			'description' 	=> 'You can overwrite the theme-css with your own css here.'
		];

		# add image preview file 
		$themeSettings['preview'] = 'http://localhost/typemill/themes/' . $themeName . '/' . $themeName . '.png';

		return $themeSettings;
	}

	protected function getPluginDetails()
	{
		$plugins = $this->getPlugins();

		$pluginDetails = [];
		foreach($plugins as $pluginName)
		{
			$pluginDetails[$pluginName] = $this->getPluginDefinition($pluginName);
		}

		return $pluginDetails;
	}

	protected function getPlugins()
	{
		$pluginFolder 	= $this->c->get('settings')['rootPath'] . DIRECTORY_SEPARATOR . $this->c->get('settings')['pluginFolder'];
		$pluginFolderC 	= scandir($pluginFolder);
		$plugins 		= [];
		foreach ($pluginFolderC as $key => $plugin)
		{
			if (!in_array($plugin, [".",".."]))
			{
				if (is_dir($pluginFolder . DIRECTORY_SEPARATOR . $plugin))
				{
					$plugins[] = $plugin;
				}
			}
		}

		return $plugins;
	}

	protected function getPluginDefinition($pluginName)
	{
		$storage 			= new StorageWrapper('\Typemill\Models\Storage');

		$pluginSettings 	= $storage->getYaml('plugins' . DIRECTORY_SEPARATOR . $pluginName, $pluginName . '.yaml');

		return $pluginSettings;
	}

	protected function getUserFields($userrole,$inspectorrole = NULL)
	{
		if(!$inspectorrole)
		{
			# if there is no inspector-role we assume that it is the same role like the userrole 
			# for example account is always visible by the same user
			# edit user can be done by another user like admin.
			$inspectorrole = $userrole;
		}

		$storage 		= new StorageWrapper('\Typemill\Models\Storage');

		$userfields 	= $storage->getYaml('system/typemill/settings', 'user.yaml');

		# if a plugin with a role has been deactivated, then users with the role throw an error, so set them back to member...
		if(!$this->c->get('acl')->hasRole($userrole))
		{
			$userrole = 'member';
		}

		# dispatch fields;
		#$fields = $this->c->dispatcher->dispatch('onUserfieldsLoaded', new OnUserfieldsLoaded($fields))->getData();

		# only roles who can edit content need profile image and description
		if($this->c->get('acl')->isAllowed($userrole, 'mycontent', 'create'))
		{
			$newfield['image'] 			= ['label' => 'Profile-Image', 'type' => 'image'];
			$newfield['description'] 	= ['label' => 'Author-Description (Markdown)', 'type' => 'textarea'];
			
			$userfields = array_slice($userfields, 0, 1, true) + $newfield + array_slice($userfields, 1, NULL, true);
			# array_splice($fields,1,0,$newfield);
		}

		# Only admin ...
		if($this->c->get('acl')->isAllowed($inspectorrole, 'user', 'write'))
		{

			# can change userroles
			$definedroles = $this->c->get('acl')->getRoles();
			$options = [];

			# we need associative array to make select-field with key/value work
			foreach($definedroles as $role)
			{
				$options[$role] = $role;
 			}

			$userfields['userrole'] = ['label' => 'Role', 'type' => 'select', 'options' => $options];

			# can activate api access
			$userfields['apiaccess'] = ['label' => 'API access', 'checkboxlabel' => 'Activate API access for this user. Use username and password for api calls.', 'type' => 'checkbox'];
		}

		return $userfields;
	}



##########################################################################################
#  GET STUFF FOR EDITOR AREA
##########################################################################################

	# reads the cached structure with published and non-published pages for the author
	# setStructureDraft
	protected function getStructureForAuthors($userrole, $username)
	{
		# get the cached structure
		$this->structureDraft = $this->writeCache->getCache('cache', $this->structureDraftName);

		# if there is no cached structure
		if(!$this->structureDraft)
		{
			return $this->setFreshStructureDraft();
		}

		return true;
	}

	# creates a fresh structure with published and non-published pages for the author
	# setFreshStrutureDraft
	protected function createNewStructureForAuthors()
	{
		# scan the content of the folder
		$pagetreeDraft = Folder::scanFolder($this->settings['rootPath'] . $this->settings['contentFolder'], $draft = true );

		# if there is content, then get the content details
		if(count($pagetreeDraft) > 0)
		{
			# get the extended structure files with changes like navigation title or hidden pages
			$yaml = new writeYaml();
			$extended = $this->getExtended();

			# create an array of object with the whole content of the folder and changes from extended file
			$this->structureDraft = Folder::getFolderContentDetails($pagetreeDraft, $extended, $this->settings, $this->uri->getBaseUrl(), $this->uri->getBasePath());
			
			# cache structure draft
			$this->writeCache->updateCache('cache', $this->structureDraftName, 'lastCache.txt', $this->structureDraft);

			return true;
		}

		return false;
	}

	# reads the cached structure of published pages
	# setStrutureLive
	protected function getStructureForReaders()
	{
		# get the cached structure
		$this->structureLive = $this->writeCache->getCache('cache', $this->structureLiveName);

		# if there is no cached structure
		if(!$this->structureLive)
		{
			return $this->setFreshStructureLive();
		}

		return true;
	}

	# creates a fresh structure with published pages
	protected function setFreshStructureLive()
	{
		# scan the content of the folder
		$pagetreeLive = Folder::scanFolder($this->settings['rootPath'] . $this->settings['contentFolder'], $draft = false );

		# if there is content, then get the content details
		if($pagetreeLive && count($pagetreeLive) > 0)
		{
			# get the extended structure files with changes like navigation title or hidden pages
			$yaml = new writeYaml();
			$extended = $this->getExtended();

			# create an array of object with the whole content of the folder and changes from extended file
			$this->structureLive = Folder::getFolderContentDetails($pagetreeLive, $extended, $this->settings, $this->uri->getBaseUrl(), $this->uri->getBasePath());
			
			# cache structure live
			$this->writeCache->updateCache('cache', $this->structureLiveName, 'lastCache.txt', $this->structureLive);

			return true;
		}

		return false;
	}

	# reads the live navigation from cache (live structure without hidden pages)
	protected function setNavigation()
	{
		# get the cached structure
		$this->navigation = $this->writeCache->getCache('cache', 'navigation.txt');

		# if there is no cached structure
		if(!$this->navigation)
		{
			return $this->setFreshNavigation();
		}

		return true;
	}

	# creates a fresh live navigation (live structure without hidden pages)
	protected function setFreshNavigation()
	{

		if(!$this->extended)
		{
			$extended = $this->getExtended();
		}

		if($this->containsHiddenPages($this->extended))
		{
			if(!$this->structureLive)
			{
				$this->setStructureLive();
			}

			$structureLive = $this->c->dispatcher->dispatch('onPagetreeLoaded', new OnPagetreeLoaded($this->structureLive))->getData();
			$this->navigation = $this->createNavigation($structureLive);

			# cache navigation
			$this->writeCache->updateCache('cache', 'navigation.txt', false, $this->navigation);
			
			return true;
		}

		# make sure no old navigation file is left
		$this->writeCache->deleteFileWithPath('cache' . DIRECTORY_SEPARATOR . 'navigation.txt');

		return false;
	}

	# create navigation from structure
	protected function createNavigation($structureLive)
	{
		foreach ($structureLive as $key => $element)
		{
			if($element->hide === true)
			{
				unset($structureLive[$key]);
			}
			elseif(isset($element->folderContent))
			{
				$structureLive[$key]->folderContent = $this->createNavigation($element->folderContent);
			}
		}
		
		return $structureLive;
	}
}