<?php

namespace Typemill\Controllers;

use Slim\Views\Twig;
use Slim\Http\Request;
use Slim\Http\Response;
use Typemill\Models\Folder;
use Typemill\Models\WriteYaml;
use \Symfony\Component\Yaml\Yaml;
use Typemill\Models\Helpers;

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
}