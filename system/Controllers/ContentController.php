<?php

namespace Typemill\Controllers;

use Slim\Views\Twig;
use Slim\Http\Request;
use Slim\Http\Response;
use Typemill\Models\Validation;
use Typemill\Models\Folder;
use Typemill\Models\Write;
use Typemill\Models\WriteYaml;
use Typemill\Models\WriteCache;
use \Symfony\Component\Yaml\Yaml;
use Typemill\Models\Helpers;
use Typemill\Extensions\ParsedownExtension;
use \Parsedown;


class ContentController extends Controller
{
	
	/**
	* Show Content
	* 
	* @param obj $request the slim request object
	* @param obj $response the slim response object
	* @return obje $response with redirect to route
	*/
	
	public function showContent(Request $request, Response $response, $args)
	{

		$settings		= $this->c->get('settings');
		$pathToContent	= $settings['rootPath'] . $settings['contentFolder'];
		$uri 			= $request->getUri();

		/* scan the content of the folder */
		$structure = Folder::scanFolder($pathToContent);

		/* if there is no content, render an empty page */
		if(count($structure) == 0)
		{
			return $this->render($response, 'content/content.twig', array( 'navigation' => true, 'content' => 'Nothing found in content folder.' ));
		}

		/* create an array of object with the whole content of the folder */
		$structure = Folder::getFolderContentDetails($structure, $uri->getBaseUrl(), $uri->getBasePath());		
		
		/* if there is no structure at all, the content folder is probably empty */
		if(!$structure)
		{
			return $this->render($response, 'content/content.twig', array( 'navigation' => true, 'content' => 'Nothing found in content folder.' ));
		}
		
		/* if it is the startpage */
		if(empty($args))
		{
			/* check, if there is an index-file in the root of the content folder */
			$contentMD = file_exists($pathToContent . DIRECTORY_SEPARATOR . 'index.md') ? file_get_contents($pathToContent . DIRECTORY_SEPARATOR . 'index.md') : NULL;
			
			/* if there is content (index.md), then add a marker for frontend, so ajax calls for homepage-index-urls work */
			if($contentMD)
			{
				$item = new \stdClass;
				$item->urlRel = 'is_homepage_index';
			}
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
		
			/* add the paging to the item */
			$item = Folder::getPagingForItem($structure, $item);
		
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
		
		$title = false;
		$content = $contentMD;
		
		if($contentMD[0] == '#')
		{
			$contentParts = explode("\r\n", $contentMD, 2);
			$title = trim($contentParts[0],  "# \t\n\r\0\x0B");
			$content = trim($contentParts[1]);
		}
					
		return $this->render($response, 'content/content.twig', array('navigation' => $structure, 'title' => $title, 'content' => $content, 'item' => $item, 'settings' => $settings ));
	}

	
	
	public function updateArticle(Request $request, Response $response, $args)
	{
		/* Extract the parameters from get-call */
		$params 		= $request->getParams();
		
		/* validate input */
		$validate		= new Validation();
		$vResult		= $validate->editorInput($params);

		if(is_array($vResult))
		{
			return $response->withJson(['errors' => $vResult], 422);
		}
		
		/* initiate variables and objects that we need */
		$settings		= $this->c->get('settings');
		$pathToContent	= $settings['rootPath'] . $settings['contentFolder'];
		$uri 			= $request->getUri();
		$base_url		= $uri->getBaseUrl();
		$write			= new writeCache();
		
		/* we will use the cached structure to find the url for the page-update. It acts as whitelist and is more secure than a file-path, for example. */
		$structure 		= $write->getCache('cache', 'structure.txt');

		/* if there is no structure, create a fresh structure */
		if(!$structure)
		{
			$structure 	= $this->getFreshStructure($pathToContent, $write, $uri);
			if(!$structure)
			{
				return $response->withJson(['errors' => ['content folder is empty']], 404);
			}
		}
		
		/* if it is the homepage */
		if($params['url'] == 'is_homepage_index')
		{
			$item = new \stdClass;
			$item->elementType = 'folder';
			$item->path = '';
		}
		else
		{
			/* search for the url in the structure */
			$item = Folder::getItemForUrl($structure, $params['url']);			
		}
				
		if(!$item)
		{
			return $response->withJson(['errors' => ['requested page-url not found']], 404);
		}
				
		if($item->elementType == 'folder')
		{
			$path = $item->path . DIRECTORY_SEPARATOR . 'index.md';
		}
		elseif($item->elementType == 'file')
		{
			$path = $item->path;
		}
		
		/* get the markdown file */
		$mdFile	= $write->getFile($settings['contentFolder'], $path);		
		if($mdFile)
		{
			/* merge title with content for complete markdown document */
			$updatedContent = '# ' . $params['title'] . "\r\n\r\n" . $params['content'];
			
			/* update the file */
			$write->writeFile($settings['contentFolder'], $path, $updatedContent);
			return $response->withJson(['success'], 200);
		}
		return $response->withJson(['errors' => ['requested markdown-file not found']], 404);
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
}