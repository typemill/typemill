<?php

namespace Typemill\Models;

use Typemill\Models\StorageWrapper;
use Typemill\Models\Content;
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



		# compare with meta that are in use right now (e.g. changed theme, disabled plugin)
		$metascheme = $this->getYaml('cache', 'metatabs.yaml');

		if($metascheme)
		{
			$meta = $this->whitelistMeta($meta,$metascheme);
		}

		return $meta;
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

	public function updateMeta($meta, $item)
	{
		$filename 	= $item->pathWithoutType . '.yaml';

		if($this->storage->updateYaml('contentFolder', '', $filename, $meta))
		{
			return true;
		}

		return $this->storage->getError();
	}

	public function addMetaDefaults($meta, $item, $authorFromSettings, $currentuser = false)
	{
		$modified = false;

		if(!isset($meta['meta']['owner']))
		{
			$meta['meta']['owner'] = $currentuser ? $currentuser : false;
			$modified = true;
		}

		if(!isset($meta['meta']['author']))
		{
			$meta['meta']['owner'] = $currentuser ? $currentuser : $authorFromSettings;
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















	public function getNavtitle($url)
	{
		die("meta moddel this method is outdated");

		# get the extended structure where the navigation title is stored
		$extended = $this->getYaml('cache', 'structure-extended.yaml');
		
		if(isset($extended[$url]['navtitle']))
		{ 
			return $extended[$url]['navtitle'];
		}
		return '';
	}

	# used by articleApiController and pageController to add title and description if an article is published
	public function completePageMeta($content, $settings, $item)
	{

		die("meta moddel this method is outdated");

		$meta = $this->getPageMeta($settings, $item);

		if(!$meta)
		{
			return $this->getPageMetaDefaults($content, $settings, $item);
		}

		$title = (isset($meta['meta']['title']) AND $meta['meta']['title'] !== '') ? true : false;
		$description = (isset($meta['meta']['description']) AND $meta['meta']['description'] !== '') ? true : false;

		if($title && $description)
		{
			return $meta;
		}

		# initialize parsedown extension
		$parsedown = new ParsedownExtension();

		# if content is not an array, then transform it
		if(!is_array($content))
		{
			# turn markdown into an array of markdown-blocks
			$content = $parsedown->markdownToArrayBlocks($content);
		}

		# delete markdown from title
		if(!$title && isset($content[0]))
		{
			$meta['meta']['title'] = trim($content[0], "# ");
		}

		if(!$description && isset($content[1]))
		{
			$meta['meta']['description'] = $this->generateDescription($content, $parsedown, $item);
		}

		$this->updateYaml($settings['contentFolder'], $item->pathWithoutType . '.yaml', $meta);
		
		return $meta;
	}

	private function whitelistMeta($meta, $metascheme)
	{

		die("meta moddel this method is outdated");

		# we have only 2 dimensions, so no recursive needed
		foreach($meta as $tab => $values)
		{
			if(!isset($metascheme[$tab]))
			{
				unset($meta[$tab]);
			}
			foreach($values as $key => $value)
			{
				if(!isset($metascheme[$tab][$key]))
				{
					unset($meta[$tab][$key]);
				}
			}
		}
		return $meta;
	}

	public function generateDescription($content, $parsedown, $item)
	{
		die("meta moddel this method is outdated");

		$description = isset($content[1]) ? $content[1] : '';

		# create description or abstract from content
		if($description !== '')
		{
			$firstLineArray = $parsedown->text($description);
			$description 	= strip_tags($parsedown->markup($firstLineArray, $item->urlAbs));

			# if description is very short
			if(strlen($description) < 100 && isset($content[2]))
			{
				$secondLineArray = $parsedown->text($content[2]);
				$description 	.= ' ' . strip_tags($parsedown->markup($secondLineArray, $item->urlAbs));
			}

			# if description is too long
			if(strlen($description) > 300)
			{
				$description	= substr($description, 0, 300);
				$lastSpace 		= strrpos($description, ' ');
				$description 	= substr($description, 0, $lastSpace);
			}
		}
		return $description;
	}
}