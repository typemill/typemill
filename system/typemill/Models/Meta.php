<?php

namespace Typemill\Models;

use Typemill\Models\StorageWrapper;
use Typemill\Models\Content;
use Typemill\Models\User;
use Typemill\Models\Settings;

class Meta
{
	private $storage;

	public function __construct($baseurl = NULL)
	{
		$this->storage = new StorageWrapper('\Typemill\Models\Storage');
	}

	# used by contentApiController (backend) and pageController (frontend) and TwigMetaExtension (list pages)
	public function getMetaData($item)
	{
		$metadata = $this->storage->getYaml('contentFolder', '', $item->pathWithoutType . '.yaml');
			
		return $metadata;
	}

	public function getMetaDefinitions($settings, $folder)
	{
		$metadefinitions 	= $this->storage->getYaml('systemSettings', '', 'metatabs.yaml');
		$settingsModel 		= new Settings();

		# loop through all plugins
		if(!empty($settings['plugins']))
		{
			foreach($settings['plugins'] as $name => $plugin)
			{
				if($plugin['active'])
				{
					$pluginSettings = $settingsModel->getObjectSettings('pluginsFolder', $name);
					if($pluginSettings && isset($pluginSettings['metatabs']))
					{
						$metadefinitions = array_merge_recursive($metadefinitions, $pluginSettings['metatabs']);
					}
				}
			}
		}
		
		# add the meta from theme settings here
		$themeSettings = $settingsModel->getObjectSettings('themesFolder', $settings['theme']);
		
		if($themeSettings && isset($themeSettings['metatabs']))
		{
			$metadefinitions = array_merge_recursive($metadefinitions, $themeSettings['metatabs']);
		}

		# conditional fieldset for user or role based access
		if(!isset($settings['pageaccess']) || $settings['pageaccess'] === NULL )
		{
			unset($metadefinitions['meta']['fields']['fieldsetrights']);
		}

		# conditional fieldset for folders
		if(!$folder)
		{
			unset($metadefinitions['meta']['fields']['fieldsetfolder']);
		}

		# dispatch meta 
#		$metatabs 		= $this->c->dispatcher->dispatch('onMetaDefinitionsLoaded', new OnMetaDefinitionsLoaded($metatabs))->getData();

		return $metadefinitions;
	}

	# used if new articel/post is created
	public function createInitialMeta(string $username, string $navtitle)
	{
		$author = '';
		$user = new User();
		if($user->setUser($username))
		{
			$fullname = $user->getFullName();
			if($fullname)
			{
				$author = $fullname;
			}
		}

		$meta = [];

		$meta['meta'] = []; 
		
		$meta['meta']['owner'] = $username;

		$meta['meta']['author'] = $author;

		$meta['meta']['created'] = date("Y-m-d");

		$meta['meta']['time'] = date("H-i-s");

		$meta['meta']['navtitle'] = $navtitle;

		return $meta;
	}

	# used to fill meta data for existing page
	public function addMetaDefaults($meta, $item, $authorFromSettings, $currentuser = false)
	{
		$modified = false;

		if(!is_array($meta))
		{
			$meta = [];
		}
		if(!isset($meta['meta']) OR !is_array($meta['meta']))
		{ 
			$meta['meta'] = []; 
		}
		
		if(!isset($meta['meta']['owner']) OR !$meta['meta']['owner'])
		{
			if($currentuser)
			{
				$meta['meta']['owner'] = $currentuser;
				$modified = true;
			}
		}

		if(!isset($meta['meta']['author']))
		{
			$author = $authorFromSettings;

			if($currentuser)
			{
				$user = new User();
				if($user->setUser($currentuser))
				{
					$fullname 	= $user->getFullName();
					if($fullname)
					{
						$author = $fullname;
					}
				}
			}

			$meta['meta']['author'] = $author;
			$modified = true;
		}

		if(!isset($meta['meta']['created']))
		{
			$meta['meta']['created'] = date("Y-m-d");
			$modified = true;
		}

		if(!isset($meta['meta']['time']))
		{
			$meta['meta']['time'] = date("H-i-s");
			$modified = true;
		}

		if(!isset($meta['meta']['navtitle']))
		{
			$meta['meta']['navtitle'] = $item->name;
			$modified = true;
		}

		if($modified)
		{
			$this->updateMeta($meta, $item);
		}

		$filePath = $item->path;		
		if($item->elementType == 'folder')
		{
			$filePath 	= $item->path . DIRECTORY_SEPARATOR . 'index.md';
		}
		$meta['meta']['modified'] = $this->storage->getFileTime('contentFolder', '', $filePath);

		return $meta;
	}

	public function addMetaTitleDescription(array $meta, $item, array $markdown)
	{
		$title 			= (isset($meta['meta']['title']) && $meta['meta']['title'] != '') ? $meta['meta']['title'] : false;
		$description 	= (isset($meta['meta']['description']) && $meta['meta']['description'] != '') ? $meta['meta']['description'] : false;

		if(!$title OR !$description)
		{
			$content 	= new Content();

			if(!$title)
			{
				$meta['meta']['title'] = $content->getTitle($markdown);
			}

			if(!$description)
			{
				$meta['meta']['description'] = $content->getDescription($markdown);
			}

			$this->updateMeta($meta, $item);
		}

		return $meta;
	}

	public function updateMeta($meta, $item)
	{
		$filename 	= $item->pathWithoutType . '.yaml';

		if($this->storage->updateYaml('contentFolder', '', $filename, $meta))
		{
			return true;
		}

		return $this->storage->getError();
	}

	public function folderContainsFolders($folder)
	{
		foreach($folder->folderContent as $page)
		{
			if($page->elementType == 'folder')
			{
				return true;
			}
		}

		return false;
	}

	public function renamePost($oldPathWithoutType,$newPathWithoutType)
	{
		$filetypes = [
			'txt' 	=> true, 
			'md' 	=> true, 
			'yaml' 	=> true
		];

		foreach($filetypes as $filetype => $result)
		{
			if(!$this->storage->renameFile('contentFolder', '', $oldPathWithoutType . '.' . $filetype, $newPathWithoutType . '.' . $filetype))
			{
				$filetypes[$filetype] = $this->storage->getError();
			}
		}

		return $filetypes;
	}

	# just route it to storageWrapper because wrapper is initialized here and we dont want to initialize it in controllers
	public function transformPostsToPages($folder)
	{
		if($this->storage->transformPostsToPages($folder))
		{
			return true;
		}

#		return $this->storage->getError();
		return false;
	}

	public function transformPagesToPosts($folder)
	{
		if($this->storage->transformPagesToPosts($folder))
		{
			return true;
		}

#		return $this->storage->getError();
		return false;
	}
}