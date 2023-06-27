<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;
use Typemill\Models\Navigation;
use Typemill\Models\Content;

class ControllerWebAuthor extends Controller
{
	public function showBlox(Request $request, Response $response, $args)
	{
		# get url for requested page
		$url 				= isset($args['route']) ? '/' . $args['route'] : '/';
		$urlinfo 			= $this->c->get('urlinfo');
		$fullUrl  			= $urlinfo['baseurl'] . $url;
		$langattr 			= $this->settings['langattr'];

	    $navigation 		= new Navigation();
		$draftNavigation 	= $navigation->getDraftNavigation($urlinfo, $langattr);
	    $home 				= $navigation->getHomepageItem($urlinfo['baseurl']);

		if($url == '/')
		{
			$item 				= $home;
			$item->active 		= true;
		}
		else
		{
		    $extendedNavigation	= $navigation->getExtendedNavigation($urlinfo, $langattr);

		    $pageinfo 			= $extendedNavigation[$url] ?? false;
		    if(!$pageinfo)
		    {
			    return $this->c->get('view')->render($response->withStatus(404), '404.twig', [
					'title'			=> 'Typemill Author Area',
					'description'	=> 'Typemill Version 2 wird noch besser als Version 1.'
			    ]);
		    }

			$keyPathArray 		= explode(".", $pageinfo['keyPath']);

		    # extend : $request->getAttribute('c_userrole')
#		    $draftNavigation 	= $navigation->getDraftNavigation($urlinfo, $langattr);

			$draftNavigation 	= $navigation->setActiveNaviItems($draftNavigation, $keyPathArray);

			$item 				= $navigation->getItemWithKeyPath($draftNavigation, $keyPathArray);
		}

	#	$item->modified		= ($item->published OR $item->drafted) ? filemtime($this->settings['contentFolder'] . $this->path) : false;

		$mainNavigation 	= $navigation->getMainNavigation($request->getAttribute('c_userrole'), $this->c->get('acl'), $urlinfo, $this->settings['editor']);

		$content 			= new Content($urlinfo['baseurl']);

		$draftMarkdown  	= $content->getDraftMarkdown($item);

		$draftMarkdownHtml	= $content->addDraftHtml($draftMarkdown);

	    return $this->c->get('view')->render($response, 'content/blox-editor.twig', [
			'settings' 			=> $this->settings,
			'mainnavi'			=> $mainNavigation,
			'content' 			=> $draftMarkdownHtml,
			'jsdata' 			=> [
										'settings' 		=> $this->settings,
										'urlinfo'		=> $urlinfo,
										'labels'		=> $this->c->get('translations'),
										'navigation'	=> $draftNavigation,
										'item'			=> $item,
										'home' 			=> $home,
										'content' 		=> $draftMarkdownHtml
									]
		]);
	}

	public function showRaw(Request $request, Response $response, $args)
	{
		# get url for requested page
		$url 				= isset($args['route']) ? '/' . $args['route'] : '/';
		$urlinfo 			= $this->c->get('urlinfo');
		$fullUrl  			= $urlinfo['baseurl'] . $url;
		$langattr 			= $this->settings['langattr'];

	    $navigation 		= new Navigation();
		$draftNavigation 	= $navigation->getDraftNavigation($urlinfo, $langattr);
	    $home 				= $navigation->getHomepageItem($urlinfo['baseurl']);

		if($url == '/')
		{
			$item 				= $home;
			$item->active 		= true;
		}
		else
		{
		    $extendedNavigation	= $navigation->getExtendedNavigation($urlinfo, $langattr);

		    $pageinfo 			= $extendedNavigation[$url] ?? false;
		    if(!$pageinfo)
		    {
			    return $this->c->get('view')->render($response->withStatus(404), '404.twig', [
					'title'			=> 'Typemill Author Area',
					'description'	=> 'Typemill Version 2 wird noch besser als Version 1.'
			    ]);
		    }

			$keyPathArray 		= explode(".", $pageinfo['keyPath']);

		    # extend : $request->getAttribute('c_userrole')
		    $draftNavigation 	= $navigation->getDraftNavigation($urlinfo, $langattr);

			$draftNavigation 	= $navigation->setActiveNaviItems($draftNavigation, $keyPathArray);

			$item 				= $navigation->getItemWithKeyPath($draftNavigation, $keyPathArray);
		}

	#	$item->modified		= ($item->published OR $item->drafted) ? filemtime($this->settings['contentFolder'] . $this->path) : false;

		$mainNavigation 	= $navigation->getMainNavigation($request->getAttribute('c_userrole'), $this->c->get('acl'), $urlinfo, $this->settings['editor']);

		$content 			= new Content($urlinfo['baseurl']);

		$draftMarkdown  	= $content->getDraftMarkdown($item);

		$draftMarkdownHtml	= $content->addDraftHtml($draftMarkdown);

	    return $this->c->get('view')->render($response, 'content/raw-editor.twig', [
			'settings' 			=> $this->settings,
			'mainnavi'			=> $mainNavigation,
			'content' 			=> $draftMarkdownHtml,
			'jsdata' 			=> [
										'settings' 		=> $this->settings,
										'urlinfo'		=> $urlinfo,
										'labels'		=> $this->c->get('translations'),
										'navigation'	=> $draftNavigation,
										'item'			=> $item,
										'home' 			=> $home,
										'content' 		=> $draftMarkdownHtml,
									]
		]);





		# get params from call
#		$this->uri 		= $request->getUri()->withUserInfo('');
#		$this->params	= isset($args['params']) ? ['url' => $this->uri->getBasePath() . '/' . $args['params']] : ['url' => $this->uri->getBasePath()];
		
		# set structure
		if(!$this->setStructureDraft()){ return $this->renderIntern404($response, array( 'navigation' => true, 'content' => $this->errors )); }
		
		# set information for homepage
		$this->setHomepage($args);

		# set item
		if(!$this->setItem()){ return $this->renderIntern404($response, array( 'navigation' => $this->structure, 'settings' => $this->settings, 'content' => $this->errors )); }

		# we have to check ownership here to use it for permission-check in tempates
		$this->checkContentOwnership();
		
		# get the breadcrumb (here we need it only to mark the actual item active in navigation)
		$breadcrumb = isset($this->item->keyPathArray) ? Folder::getBreadcrumb($this->structureDraft, $this->item->keyPathArray) : false;
		
		# set the status for published and drafted
		$this->setPublishStatus();

		# set path
		$this->setItemPath($this->item->fileType);

		# add the modified date for the file
		$this->item->modified	= ($this->item->published OR $this->item->drafted) ? filemtime($this->settings['contentFolder'] . $this->path) : false;
		
		# read content from file
		if(!$this->setContent()){ return $this->renderIntern404($response, array( 'navigation' => $this->structure, 'settings' => $this->settings, 'content' => $this->errors )); }
		
		$content = $this->content;
		$title = false;

		# if content is an array, then it is a draft
		if(is_array($content))
		{
			# transform array to markdown
			$parsedown = new ParsedownExtension($this->uri->getBaseUrl());			
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

		return $this->renderIntern($response, 'editor/editor-raw.twig', array(
			'acl'			=> $this->c->acl,
			'mycontent'		=> $this->mycontent,
			'navigation' 	=> $this->structureDraft, 
			'homepage' 		=> $this->homepage, 
			'title' 		=> $title, 
			'content' 		=> $content, 
			'item' 			=> $this->item, 
			'settings' 		=> $this->settings
		));
	}	
}