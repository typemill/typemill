<?php

namespace Typemill\Controllers;

use Typemill\Models\Folder;
use Typemill\Models\WriteCache;
use Typemill\Models\WriteSitemap;
use Typemill\Models\WriteYaml;
use \Symfony\Component\Yaml\Yaml;
use Typemill\Models\VersionCheck;
use Typemill\Models\Helpers;
use Typemill\Events\OnPagetreeLoaded;
use Typemill\Events\OnBreadcrumbLoaded;
use Typemill\Events\OnItemLoaded;
use Typemill\Events\OnMarkdownLoaded;
use Typemill\Events\OnContentArrayLoaded;
use Typemill\Events\OnHtmlLoaded;
use Typemill\Extensions\ParsedownExtension;

class PageController extends Controller
{
	public function index($request, $response, $args)
	{
	
		/* Initiate Variables */
		$structure		= false;
		$contentHTML	= false;
		$item			= false;
		$breadcrumb 	= false;
		$description	= '';
		$settings		= $this->c->get('settings');
		$pathToContent	= $settings['rootPath'] . $settings['contentFolder'];
		$cache 			= new WriteCache();
		$uri 			= $request->getUri();
		$base_url		= $uri->getBaseUrl();

		try
		{
			/* if the cached structure is still valid, use it */
			if($cache->validate('cache', 'lastCache.txt',600))
			{
				$structure	= $this->getCachedStructure($cache);
			}
			else
			{
				/* if not, get a fresh structure of the content folder */
				$structure 	= $this->getFreshStructure($pathToContent, $cache, $uri);

				/* if there is no structure at all, the content folder is probably empty */
				if(!$structure)
				{
					$content = '<h1>No Content</h1><p>Your content folder is empty.</p>'; 

					return $this->render($response, '/index.twig', array( 'content' => $content ));
				}
				elseif(!$cache->validate('cache', 'lastSitemap.txt', 86400))
				{
					/* update sitemap */
					$sitemap = new WriteSitemap();
					$sitemap->updateSitemap('cache', 'sitemap.xml', 'lastSitemap.txt', $structure, $uri->getBaseUrl());

					/* check and update the typemill-version in the user settings */
					$this->updateVersion($uri->getBaseUrl());					
				}
			}
			
			/* dispatch event and let others manipulate the structure */
			$structure = $this->c->dispatcher->dispatch('onPagetreeLoaded', new OnPagetreeLoaded($structure))->getData();
		}
		catch (Exception $e)
		{
			echo $e->getMessage();
			exit(1);
		}
		
		/* if the user is on startpage */
		if(empty($args))
		{	
			/* check, if there is an index-file in the root of the content folder */
			$contentMD = file_exists($pathToContent . DIRECTORY_SEPARATOR . 'index.md') ? file_get_contents($pathToContent . DIRECTORY_SEPARATOR . 'index.md') : NULL;
		}
		else
		{
			/* get the request url */
			$urlRel = $uri->getBasePath() . '/' . $args['params'];			
			
			/* find the url in the content-item-tree and return the item-object for the file */
			$item = Folder::getItemForUrl($structure, $urlRel);
						
			/* if there is still no item, return a 404-page */
			if(!$item)
			{
				return $this->render404($response, array( 'navigation' => $structure, 'settings' => $settings,  'base_url' => $base_url )); 
			}
			
			/* get breadcrumb for page */
			$breadcrumb = Folder::getBreadcrumb($structure, $item->keyPathArray);
			$breadcrumb = $this->c->dispatcher->dispatch('onBreadcrumbLoaded', new OnBreadcrumbLoaded($breadcrumb))->getData();
			
			/* add the paging to the item */
			$item = Folder::getPagingForItem($structure, $item);
			$item = $this->c->dispatcher->dispatch('onItemLoaded', new OnItemLoaded($item))->getData();
			
			/* check if url is a folder. If so, check if there is an index-file in that folder */
			if($item->elementType == 'folder' && $item->index)
			{
				$filePath = $pathToContent . $item->path . DIRECTORY_SEPARATOR . 'index.md';
			}
			elseif($item->elementType == 'file')
			{
				$filePath = $pathToContent . $item->path;
			}
			
			/* add the modified date for the file */
			$item->modified	= isset($filePath) ? filemtime($filePath) : false;
						
			/* read the content of the file */
			$contentMD 		= isset($filePath) ? file_get_contents($filePath) : false;			
		}
		
		$contentMD = $this->c->dispatcher->dispatch('onMarkdownLoaded', new OnMarkdownLoaded($contentMD))->getData();
				
		/* initialize parsedown */
		$parsedown 		= new ParsedownExtension();

		/* parse markdown-file to content-array */
		$contentArray 	= $parsedown->text($contentMD);
		$contentArray 	= $this->c->dispatcher->dispatch('onContentArrayLoaded', new OnContentArrayLoaded($contentArray))->getData();
	
		/* get the first image from content array */
		$firstImage		= $this->getFirstImage($contentArray);
		
		/* parse markdown-content-array to content-string */
		$contentHTML	= $parsedown->markup($contentArray);
		$contentHTML 	= $this->c->dispatcher->dispatch('onHtmlLoaded', new OnHtmlLoaded($contentHTML))->getData();

		/* extract the h1 headline*/
		$contentParts	= explode("</h1>", $contentHTML);
		$title			= isset($contentParts[0]) ? strip_tags($contentParts[0]) : $settings['title'];
		
		$contentHTML	=  isset($contentParts[1]) ? $contentParts[1] : $contentHTML;
		
		/* create excerpt from content */
		$excerpt		= substr($contentHTML,0,500);
		
		/* create description from excerpt */
		$description	= isset($excerpt) ? strip_tags($excerpt) : false;
		if($description)
		{
			$description 	= trim(preg_replace('/\s+/', ' ', $description));
			$description	= substr($description, 0, 300);		
			$lastSpace 		= strrpos($description, ' ');
			$description 	= substr($description, 0, $lastSpace);
		}
				
		/* get url and alt-tag for first image, if exists */
		if($firstImage)
		{
			preg_match('#\((.*?)\)#', $firstImage, $img_url);
			if($img_url[1])
			{
				preg_match('#\[(.*?)\]#', $firstImage, $img_alt);
				
				$firstImage = array('img_url' => $base_url . $img_url[1], 'img_alt' => $img_alt[1]);
			}
		}
		
		$route = empty($args) && $settings['startpage'] ? '/cover.twig' : '/index.twig';
		
		return $this->render($response, $route, array('navigation' => $structure, 'content' => $contentHTML, 'item' => $item, 'breadcrumb' => $breadcrumb, 'settings' => $settings, 'title' => $title, 'description' => $description, 'base_url' => $base_url, 'image' => $firstImage ));
	}

	protected function getCachedStructure($cache)
	{
		return $cache->getCache('cache', 'structure.txt');
	}
	
	protected function getFreshStructure($pathToContent, $cache, $uri)
	{
		/* scan the content of the folder */
		$structure = Folder::scanFolder($pathToContent);

		/* if there is no content, render an empty page */
		if(count($structure) == 0)
		{
			return false;
		}

		/* create an array of object with the whole content of the folder */
		$structure = Folder::getFolderContentDetails($structure, $uri->getBaseUrl(), $uri->getBasePath());		

		/* cache navigation */
		$cache->updateCache('cache', 'structure.txt', 'lastCache.txt', $structure);
		
		return $structure;
	}
	
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
			if(isset($block['element']['name']) && $block['element']['name'] == 'p')
			{
				if(isset($block['element']['handler']['argument']) && substr($block['element']['handler']['argument'], 0, 2) == '![' )
				{
					return $block['element']['handler']['argument'];	
				}
			}
		}
		
		return false;
	}
}