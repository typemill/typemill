<?php

namespace Typemill\Controllers;

use Typemill\Models\Folder;
use Typemill\Models\WriteCache;
use Typemill\Models\WriteSitemap;
use Typemill\Models\WriteYaml;
use Typemill\Models\WriteMeta;
use \Symfony\Component\Yaml\Yaml;
use Typemill\Models\VersionCheck;
use Typemill\Models\Markdown;
use Typemill\Events\OnCacheUpdated;
use Typemill\Events\OnPagetreeLoaded;
use Typemill\Events\OnBreadcrumbLoaded;
use Typemill\Events\OnItemLoaded;
use Typemill\Events\OnOriginalLoaded;
use Typemill\Events\OnMetaLoaded;
use Typemill\Events\OnMarkdownLoaded;
use Typemill\Events\OnContentArrayLoaded;
use Typemill\Events\OnHtmlLoaded;
use Typemill\Events\OnRestrictionsLoaded;
use Typemill\Extensions\ParsedownExtension;

class PageController extends Controller
{
	public function index($request, $response, $args)
	{	

		/* Initiate Variables */
		$structure		= false;
		$contentHTML	= false;
		$item			= false;
		$home			= false;
		$breadcrumb 	= false;
		$pathToContent	= $this->settings['rootPath'] . $this->settings['contentFolder'];
		$cache 			= new WriteCache();
		$uri 			= $request->getUri()->withUserInfo('');
		$base_url		= $uri->getBaseUrl();

		$this->pathToContent = $pathToContent;

		try
		{
			# if the cached structure is still valid, use it
			if($cache->validate('cache', 'lastCache.txt', 600))
			{
				$structure	= $this->getCachedStructure($cache);
			}
			else
			{
				# dispatch message that the cache has been refreshed 
				$this->c->dispatcher->dispatch('onCacheUpdated', new OnCacheUpdated(false));
			}

			if(!isset($structure) OR !$structure) 
			{
				# if not, get a fresh structure of the content folder
				$structure 	= $this->getFreshStructure($pathToContent, $cache, $uri);

				# if there is no structure at all, the content folder is probably empty
				if(!$structure)
				{
					$content = '<h1>No Content</h1><p>Your content folder is empty.</p>'; 

					return $this->render($response, '/index.twig', array( 'content' => $content ));
				}
				elseif(!$cache->validate('cache', 'lastSitemap.txt', 86400))
				{
					# update sitemap
					$sitemap = new WriteSitemap();
					$sitemap->updateSitemap('cache', 'sitemap.xml', 'lastSitemap.txt', $structure, $uri->getBaseUrl());
				}
			}
			
			# dispatch event and let others manipulate the structure
			$structure = $this->c->dispatcher->dispatch('onPagetreeLoaded', new OnPagetreeLoaded($structure))->getData();
		}
		catch (Exception $e)
		{
			echo $e->getMessage();
			exit(1);
		}

		# get meta-Information
		$writeMeta 		= new WriteMeta();
		$theme 			= $this->settings['theme'];

		# check if there is a custom theme css
		$customcss = $writeMeta->checkFile('cache', $theme . '-custom.css');
		if($customcss)
		{
			$this->c->assets->addCSS($base_url . '/cache/' . $theme . '-custom.css');
		}

		$logo = false;
		if(isset($this->settings['logo']) && $this->settings['logo'] != '')
		{
			$logo = 'media/files/' . $this->settings['logo'];
		}

		$favicon = false;
		if(isset($this->settings['favicon']) && $this->settings['favicon'] != '')
		{
			$favicon = true;
		}

		# get the cached navigation here (structure without hidden files )
		$navigation = $cache->getCache('cache', 'navigation.txt');
		if(!$navigation)
		{
			# use the structure as navigation if there is no difference
			$navigation = $structure;
		}

		# if the user is on startpage
		$home = false;
		if(empty($args))
		{
			$home = true;
			$item = Folder::getItemForUrl($navigation, $uri->getBasePath(), $uri->getBaseUrl(), NULL, $home);
			$urlRel = $uri->getBasePath();
		}
		else
		{
			# get the request url, trim args so physical folders have no trailing slash
			$urlRel = $uri->getBasePath() . '/' . trim($args['params'], "/");

			# find the url in the content-item-tree and return the item-object for the file
			# important to use the structure here so it is found, even if the item is hidden.
			$item = Folder::getItemForUrl($structure, $urlRel, $uri->getBasePath());

			# if there is still no item, return a 404-page
			if(!$item)
			{
				return $this->render404($response, array( 
					'navigation'	=> $navigation, 
					'settings' 		=> $this->settings,  
					'base_url' 		=> $base_url,
					'title' 		=> false,
					'content' 		=> false, 
					'item' 			=> false,
					'breadcrumb' 	=> false, 
					'metatabs'		=> false,
					'image' 		=> false,
					'logo'			=> $logo,
					'favicon'		=> $favicon
				)); 
			}

			if(!$item->hide)
			{
				# get breadcrumb for page and set pages active
				# use navigation, the hidden pages won't get a breadcrumb
				$breadcrumb = Folder::getBreadcrumb($navigation, $item->keyPathArray);
				$breadcrumb = $this->c->dispatcher->dispatch('onBreadcrumbLoaded', new OnBreadcrumbLoaded($breadcrumb))->getData();

				# set pages active for navigation again
				# Folder::getBreadcrumb($navigation, $item->keyPathArray);
				
				# add the paging to the item
				$item = Folder::getPagingForItem($navigation, $item);
			}
		}

		if(isset($item->hide) && $item->hide) 
		{
			# delete the paging elements
			$item->thisChapter = false;
			$item->nextItem = false;
			$item->prevItem = false;
			$breadcrumb = false;
		}

		# dispatch the item
		$item 			= $this->c->dispatcher->dispatch('onItemLoaded', new OnItemLoaded($item))->getData();

		# set the filepath
		$filePath 	= $pathToContent . $item->path;
		
		# check if url is a folder and add index.md 
		if($item->elementType == 'folder')
		{
			$filePath 	= $filePath . DIRECTORY_SEPARATOR . 'index.md';

			# if folder is not hidden
			if(isset($item->hide) && !$item->hide)
			{
				# use the navigation instead of the structure so that hidden elements are erased
				$item = Folder::getItemForUrl($navigation, $urlRel, $uri->getBaseUrl(), NULL, $home);
			}
		}

		# read the content of the file
		$contentMD 		= file_exists($filePath) ? file_get_contents($filePath) : false;

		# dispatch the original content without plugin-manipulations for case anyone wants to use it
		$this->c->dispatcher->dispatch('onOriginalLoaded', new OnOriginalLoaded($contentMD));
		
		# makes sure that you always have the full meta with title, description and all the rest.
		$metatabs 		= $writeMeta->completePageMeta($contentMD, $this->settings, $item);

		# dispatch meta 
		$metatabs 		= $this->c->dispatcher->dispatch('onMetaLoaded', new OnMetaLoaded($metatabs))->getData();

		# dispatch content
		$contentMD 		= $this->c->dispatcher->dispatch('onMarkdownLoaded', new OnMarkdownLoaded($contentMD))->getData();

		$itemUrl 		= isset($item->urlRel) ? $item->urlRel : false;

		/* initialize parsedown */
		$parsedown 		= new ParsedownExtension($base_url, $this->settings);
		
		/* set safe mode to escape javascript and html in markdown */
		$parsedown->setSafeMode(true);

		# check access restriction here
		$restricted 	= $this->checkRestrictions($metatabs['meta']);
		if($restricted)
		{
			# convert markdown into array of markdown block-elements
			$markdownBlocks = $parsedown->markdownToArrayBlocks($contentMD);

			# infos that plugins need to add restriction content
			$restrictions = [
				'restricted' 		=> $restricted,
				'defaultContent' 	=> true,
				'markdownBlocks'	=> $markdownBlocks,
			];

			# dispatch the data
			$restrictions 	= $this->c->dispatcher->dispatch('onRestrictionsLoaded', new OnRestrictionsLoaded( $restrictions ))->getData();

			# use the returned markdown
			$markdownBlocks = $restrictions['markdownBlocks'];

			# if no plugin has disabled the default behavior
			if($restrictions['defaultContent'])
			{
				# cut the restricted content
				$shortenedPage = $this->cutRestrictedContent($markdownBlocks);

				# check if there is customized content
				$restrictionnotice = $this->prepareRestrictionNotice();

				# add notice to shortened content
				$shortenedPage[] = $restrictionnotice;

				# Use the shortened page
				$markdownBlocks = $shortenedPage;
			}

			# finally transform the markdown blocks back to pure markdown text
			$contentMD = $parsedown->arrayBlocksToMarkdown($markdownBlocks);
		}

		/* parse markdown-file to content-array */
		$contentArray 	= $parsedown->text($contentMD);
		$contentArray 	= $this->c->dispatcher->dispatch('onContentArrayLoaded', new OnContentArrayLoaded($contentArray))->getData();

		/* parse markdown-content-array to content-string */
		$contentHTML	= $parsedown->markup($contentArray);
		$contentHTML 	= $this->c->dispatcher->dispatch('onHtmlLoaded', new OnHtmlLoaded($contentHTML))->getData();
		
		/* extract the h1 headline*/
		$contentParts	= explode("</h1>", $contentHTML, 2);
		$title			= isset($contentParts[0]) ? strip_tags($contentParts[0]) : $this->settings['title'];
		
		$contentHTML	= isset($contentParts[1]) ? $contentParts[1] : $contentHTML;

		# get the first image from content array */
		$img_url		= isset($metatabs['meta']['heroimage']) ? $metatabs['meta']['heroimage'] : false;
		$img_alt		= isset($metatabs['meta']['heroimagealt']) ? $metatabs['meta']['heroimagealt'] : false;

		# get url and alt-tag for first image, if exists */
		if(!$img_url OR $img_url == '')
		{
			# extract first image from content
			$firstImageMD = $this->getFirstImage($contentArray);

			if($firstImageMD)
			{
				preg_match('#\((.*?)\)#', $firstImageMD, $img_url_result);
				$img_url = isset($img_url_result[1]) ? $img_url_result[1] : false;
				
				if($img_url)
				{
					preg_match('#\[(.*?)\]#', $firstImageMD, $img_alt_result);
					$img_alt = isset($img_alt_result[1]) ? $img_alt_result[1] : false;
				}
			}
			elseif($logo)
			{
				$img_url = $logo;
				$pathinfo = pathinfo($this->settings['logo']);
				$img_alt = $pathinfo['filename'];
			}
		}
		
		$firstImage = false;
		if($img_url)
		{
			$firstImage = array('img_url' => $base_url . '/' . $img_url, 'img_alt' => $img_alt);
		}
		
		$route = empty($args) && isset($this->settings['themes'][$theme]['cover']) ? '/cover.twig' : '/index.twig';

		return $this->render($response, $route, [
			'home'			=> $home,
			'navigation' 	=> $navigation, 
			'title' 		=> $title,
			'content' 		=> $contentHTML, 
			'item' 			=> $item,
			'breadcrumb' 	=> $breadcrumb, 
			'settings' 		=> $this->settings,
			'metatabs'		=> $metatabs,
			'base_url' 		=> $base_url, 
			'image' 		=> $firstImage,
			'logo'			=> $logo,
			'favicon'		=> $favicon
		]);
	}

	protected function getCachedStructure($cache)
	{
		return $cache->getCache('cache', 'structure.txt');
	}
	
	protected function getFreshStructure($pathToContent, $cache, $uri)
	{
		/* scan the content of the folder */
		$pagetree = Folder::scanFolder($pathToContent);

		/* if there is no content, render an empty page */
		if(count($pagetree) == 0)
		{
			return false;
		}

		# get the extended structure files with changes like navigation title or hidden pages
		$yaml = new writeYaml();
		$extended = $yaml->getYaml('cache', 'structure-extended.yaml');

		# create an array of object with the whole content of the folder
		$structure = Folder::getFolderContentDetails($pagetree, $extended, $uri->getBaseUrl(), $uri->getBasePath());

		# now update the extended structure
		if(!$extended)
		{
			$extended = $this->createExtended($this->pathToContent, $yaml, $structure);

			if(!empty($extended))
			{
				$yaml->updateYaml('cache', 'structure-extended.yaml', $extended);

				# we have to update the structure with extended again
				$structure = Folder::getFolderContentDetails($pagetree, $extended, $uri->getBaseUrl(), $uri->getBasePath());
			}
		}
		
		# cache structure
		$cache->updateCache('cache', 'structure.txt', 'lastCache.txt', $structure);

		if($extended && $this->containsHiddenPages($extended))
		{
			# generate the navigation (delete empty pages)
			$navigation = $this->createNavigationFromStructure($structure);

			# cache navigation
			$cache->updateCache('cache', 'navigation.txt', false, $navigation);
		}
		else
		{
			# make sure no separate navigation file is set
			$cache->deleteFileWithPath('cache' . DIRECTORY_SEPARATOR . 'navigation.txt');
		}
		
		# load and return the cached structure, because might be manipulated with navigation....
		return 	$this->getCachedStructure($cache);
	}
	
	protected function createExtended($contentPath, $yaml, $structure, $extended = NULL)
	{
		if(!$extended)
		{
			$extended = [];
		}

		foreach ($structure as $key => $item)
		{
			# $filename = ($item->elementType == 'folder') ? DIRECTORY_SEPARATOR . 'index.yaml' : $item->pathWithoutType . '.yaml';
			$filename = $item->pathWithoutType . '.yaml';

			if(file_exists($contentPath . $filename))
			{				
				# read file
				$meta = $yaml->getYaml('content', $filename);

				$extended[$item->urlRelWoF]['hide'] = isset($meta['meta']['hide']) ? $meta['meta']['hide'] : false;
				$extended[$item->urlRelWoF]['navtitle'] = isset($meta['meta']['navtitle']) ? $meta['meta']['navtitle'] : '';
			}

			if ($item->elementType == 'folder')
			{
				$extended 	= $this->createExtended($contentPath, $yaml, $item->folderContent, $extended);
			}
		}
		return $extended;
	}

	protected function containsHiddenPages($extended)
	{
		foreach($extended as $element)
		{
			if(isset($element['hide']) && $element['hide'] === true)
			{
				return true;
			}
		}
		return false;
	}

	protected function createNavigationFromStructure($navigation)
	{
		foreach ($navigation as $key => $element)
		{
			if($element->hide === true)
			{
				unset($navigation[$key]);
			}
			elseif(isset($element->folderContent))
			{
				$navigation[$key]->folderContent = $this->createNavigationFromStructure($element->folderContent);
			}
		}
		
		return $navigation;
	}

	# not in use, stored the latest version in user settings, but that does not make sense because checkd on the fly with api in admin
	protected function updateVersion($baseUrl)
	{
		/* check the latest public typemill version */
		$version 		= new VersionCheck();
		$latestVersion 	= $version->checkVersion($baseUrl);

		if($latestVersion)
		{
			/* store latest version */
			\Typemill\Settings::updateSettings(array('latestVersion' => $latestVersion));			
		}
	}
	
	protected function getFirstImage(array $contentBlocks)
	{
		foreach($contentBlocks as $block)
		{
			/* is it a paragraph? */
			if(isset($block['name']) && $block['name'] == 'p')
			{
				if(isset($block['handler']['argument']) && substr($block['handler']['argument'], 0, 2) == '![' )
				{
					return $block['handler']['argument'];	
				}
			}
		}
		
		return false;
	}

	# checks if a page has a restriction in meta and if the current user is blocked by that restriction
	protected function checkRestrictions($meta)
	{
		# check if content restrictions are active
		if(isset($this->settings['pageaccess']) && $this->settings['pageaccess'])
		{

			# check if page is restricted to certain user
			if(isset($meta['alloweduser']) && $meta['alloweduser'] && $meta['alloweduser'] !== '' )
			{
				$alloweduser = array_map('trim', explode(",", $meta['alloweduser']));
				if(isset($_SESSION['user']) && in_array($_SESSION['user'], $alloweduser))
				{
					# user has access to the page, so there are no restrictions
					return false;
				}

				# otherwise return array with type of restriction and allowed username
				return [ 'alloweduser' => $meta['alloweduser'] ];
			}

			# check if page is restricted to certain userrole
			if(isset($meta['allowedrole']) && $meta['allowedrole'] && $meta['allowedrole'] !== '' )
			{
				# var_dump($this->c->acl->inheritsRole('editor', 'member'));
				# die();
				if(
					isset($_SESSION['role']) 
					AND ( 
						$_SESSION['role'] == 'administrator' 
						OR $_SESSION['role'] == $meta['allowedrole'] 
						OR $this->c->acl->inheritsRole($_SESSION['role'], $meta['allowedrole']) 
					)
				)
				{
					# role has access to page, so there are no restrictions 
					return false;
				}
				
				return [ 'allowedrole' => $meta['allowedrole'] ];
			}

		}
		
		return false;

	}

	protected function cutRestrictedContent($markdown)
	{
		#initially add only the title of the page.
		$restrictedMarkdown = [$markdown[0]];
		unset($markdown[0]);

		if(isset($this->settings['hrdelimiter']) && $this->settings['hrdelimiter'] !== NULL )
		{
			foreach ($markdown as $block)
			{
				$firstCharacters = substr($block, 0, 3);
				if($firstCharacters == '---' OR $firstCharacters == '***')
				{
					return $restrictedMarkdown;
				}
				$restrictedMarkdown[] = $block;
			}

			# no delimiter found, so use the title only
			$restrictedMarkdown = [$restrictedMarkdown[0]];
		}

		return $restrictedMarkdown;
	}

	protected function prepareRestrictionNotice()
	{
		if( isset($this->settings['restrictionnotice']) && $this->settings['restrictionnotice'] != '' )
		{
			$restrictionNotice = $this->settings['restrictionnotice'];
		}
		else
		{
			$restrictionNotice = 'You are not allowed to access this content.';
		}

		if( isset($this->settings['wraprestrictionnotice']) && $this->settings['wraprestrictionnotice'] )
		{
	        # standardize line breaks
	        $text = str_replace(array("\r\n", "\r"), "\n", $restrictionNotice);

	        # remove surrounding line breaks
	        $text = trim($text, "\n");

	        # split text into lines
	        $lines = explode("\n", $text);

	        $restrictionNotice = '';

	        foreach($lines as $key => $line)
	        {
	        	$restrictionNotice .= "!!!! " . $line . "\n";
	        }
		}

		return $restrictionNotice;
	}
}