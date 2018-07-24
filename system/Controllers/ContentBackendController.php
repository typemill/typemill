<?php

namespace Typemill\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\Twig;
use Typemill\Extensions\ParsedownExtension;

class ContentBackendController extends ContentController
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
		# get params from call
		$this->uri 		= $request->getUri();
		$this->params	= isset($args['params']) ? ['url' => $this->uri->getBasePath() . '/' . $args['params']] : ['url' => $this->uri->getBasePath()];
		
		# set structure
		if(!$this->setStructure($draft = true)){ return $this->render404($response, array( 'navigation' => true, 'content' => $this->errors )); }
		
		# set item
		if(!$this->setItem()){ return $this->render404($response, array( 'navigation' => $this->structure, 'settings' => $this->settings, 'content' => $this->errors )); }
		
		# set the status for published and drafted
		$this->setPublishStatus();

		# set path
		$this->setItemPath($this->item->fileType);
						
		# add the modified date for the file
		$this->item->modified	= ($this->item->published OR $this->item->drafted) ? filemtime($this->settings['contentFolder'] . $this->path) : false;
		
		# read content from file
		if(!$this->setContent()){ return $this->render404($response, array( 'navigation' => $this->structure, 'settings' => $this->settings, 'content' => $this->errors )); }
		
		$content = $this->content;
		$title = false;

		# if content is an array, then it is a draft
		if(is_array($content))
		{
			# transform array to markdown
			$parsedown = new ParsedownExtension();			
			$content = $parsedown->arrayBlocksToMarkdown($content);
		}

		# if there is content
		if($content != '')
		{
			# normalize linebreaks
			$content = str_replace(array("\r\n", "\r"), "\n", $content);
			$content = trim($content, "\n");
			
			# and strip out title
			if($content[0] == '#')
			{
				$contentParts = explode("\n", $content, 2);
				$title = trim($contentParts[0],  "# \t\n\r\0\x0B");
				$content = trim($contentParts[1]);
			}
		}

		return $this->render($response, 'content/content.twig', array('navigation' => $this->structure, 'title' => $title, 'content' => $content, 'item' => $this->item, 'settings' => $this->settings ));
	}
}