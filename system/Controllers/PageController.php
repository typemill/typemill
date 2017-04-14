<?php

namespace System\Controllers;

use System\Models\Folder;
use System\Models\Cache;
use System\Models\Helpers;

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
		$cache 			= new Cache();
		$uri 			= $request->getUri();
		$base_url		= $uri->getBaseUrl();
		
		if($cache->validate())
		{
			$structure	= $this->getCachedStructure($cache);
			$cached 	= true;
		}
		else
		{
			$structure 	= $this->getFreshStructure($pathToContent, $cache, $uri);
			$cached		= false;
			
			if(!$structure)
			{ 
				$content = '<h1>No Content</h1><p>Your content folder is empty.</p>'; 
				$this->c->view->render($response, '/index.twig', [ 'content' => $content ]);
			}
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

			if(!$item && $cached)
			{
				$structure = $this->getFreshStructure($pathToContent, $cache, $uri); 
				$item = Folder::getItemForUrl($structure, $urlRel);
			}
			if(!$item){	return $this->render404($response, array( 'navigation' => $structure, 'settings' => $settings,  'base_url' => $base_url )); }
			
			/* get breadcrumb for page */
			$breadcrumb = Folder::getBreadcrumb($structure, $item->keyPathArray);

			/* add the paging to the item */
			$item = Folder::getPagingForItem($structure, $item);

			/* check if url is a folder. If so, check if there is an index-file for the folder */
			if($item->elementType == 'folder' && $item->index)
			{
				$filePath = $pathToContent . $item->path . DIRECTORY_SEPARATOR . 'index.md';
			}
			elseif($item->elementType == 'file')
			{
				$filePath = $pathToContent . $item->path;
			}

			/* read the content of the file */
			$contentMD = isset($filePath) ? file_get_contents($filePath) : false;
		}
		
		/* initialize parsedown */
		$Parsedown = new \ParsedownExtra();

		/* parse markdown-file to html-string */
		$contentHTML = $Parsedown->text($contentMD);
		$description = substr(strip_tags($contentHTML),0,150);
		$description = trim(preg_replace('/\s+/', ' ', $description));
		
		/* 
			$timer['topiccontroller']=microtime(true);
			$timer['end topiccontroller']=microtime(true);
			Helpers::printTimer($timer);
		*/
		
		$route = empty($args) && $settings['startpage'] ? '/cover.twig' : '/index.twig';

		$this->c->view->render($response, $route, array('navigation' => $structure, 'content' => $contentHTML, 'item' => $item, 'breadcrumb' => $breadcrumb, 'settings' => $settings, 'description' => $description, 'base_url' => $base_url ));
	}

	
	protected function getCachedStructure($cache)
	{
		return $cache->getData('structure');
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
		$cache->refresh($structure, 'structure');
		
		return $structure;
	}	
}

?>