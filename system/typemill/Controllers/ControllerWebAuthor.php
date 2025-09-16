<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;
use Typemill\Models\Navigation;
use Typemill\Models\Content;
use Typemill\Models\User;
use Typemill\Events\OnPagetreeLoaded;
use Typemill\Events\OnItemLoaded;
use Typemill\Events\OnMarkdownLoaded;
use Typemill\Events\OnPageReady;

class ControllerWebAuthor extends Controller
{
	public function showBlox(Request $request, Response $response, $args)
	{
		# get url for requested page
		$url 				= isset($args['route']) ? '/' . $args['route'] : '/';
		$urlinfo 			= $this->c->get('urlinfo');
		$fullUrl  			= $urlinfo['baseurl'] . $url;
		$langattr 			= $this->settings['langattr'];
		$userrole 			= $request->getAttribute('c_userrole');
		$username 			= $request->getAttribute('c_username');

	    $navigation 		= new Navigation();

		# configure multilang and multiproject
		$navigation->setProject($this->settings, $url);

	    $projects 			= $navigation->getAllProjects($this->settings);

		$draftNavigation 	= $navigation->getFullDraftNavigation($urlinfo, $langattr);

	    $home 				= $navigation->getHomepageItem($urlinfo['baseurl']);

		if($navigation->isHome($url))
		{
			$item 				= $home;
			$item->active 		= true;
		}
		else
		{
			$pageinfo = $navigation->getPageInfoForUrl($url, $urlinfo, $langattr);

		    if(!$pageinfo)
		    {
			    return $this->c->get('view')->render($response->withStatus(404), '404.twig', [
					'title'			=> 'Blox editor',
					'description'	=> 'Edit your content with the visual blox editor'
			    ]);
		    }

			$keyPathArray 		= explode(".", $pageinfo['keyPath']);

			$draftNavigation 	= $navigation->setActiveNaviItemsWithKeyPath($draftNavigation, $keyPathArray);
			$draftNavigation 	= $this->c->get('dispatcher')->dispatch(new OnPagetreeLoaded($draftNavigation), 'onPagetreeLoaded')->getData();

			$item 				= $navigation->getItemWithKeyPath($draftNavigation, $keyPathArray);
			$item 				= $this->c->get('dispatcher')->dispatch(new OnItemLoaded($item), 'onItemLoaded')->getData();
		}

	    $userModel = new User();
	    $user = $userModel->setUser($username);
	    if($user && $user->getValue('folderaccess'))
	    {
	        # then create navigation based on allowed folders (to be implemented)
	      	$draftNavigation = $navigation->getAllowedFolders($draftNavigation, $user->getValue('folderaccess'));

	      	if($url != '/')
	      	{
		    	$accessallowed = $navigation->checkFolderAccess($url, $user->getValue('folderaccess'));

		        # if not allowed show a 404 not found so that reengineering of urls is not possible
		        if(!$accessallowed)
		        {
		        	$destination = isset($draftNavigation[0]->slug) ? $draftNavigation[0]->slug : '';
					$redirect = $urlinfo['baseurl'] . '/tm/content/visual/' . $destination;

					return $response->withHeader('Location', $redirect)->withStatus(302);
		        }
	      	}
	    }

	#	$item->modified		= ($item->published OR $item->drafted) ? filemtime($this->settings['contentFolder'] . $this->path) : false;

		$mainNavigation 	= $navigation->getMainNavigation($request->getAttribute('c_userrole'), $this->c->get('acl'), $urlinfo, $this->settings['editor']);

		$content 			= new Content($urlinfo['baseurl'], $this->settings, $this->c->get('dispatcher'));
		$draftMarkdown  	= $content->getDraftMarkdown($item);
		$draftMarkdown 		= $this->c->get('dispatcher')->dispatch(new OnMarkdownLoaded($draftMarkdown), 'onMarkdownLoaded')->getData();

		$draftMarkdownHtml	= $content->addDraftHtml($draftMarkdown);

	    return $this->c->get('view')->render($response, 'content/blox-editor.twig', [
			'settings' 			=> $this->settings,
			'darkmode'			=> $request->getAttribute('c_darkmode'),
			'mainnavi'			=> $mainNavigation,
			'content' 			=> $draftMarkdownHtml,
			'jsdata' 			=> [
										'settings' 		=> $this->settings,
										'urlinfo'		=> $urlinfo,
										'labels'		=> $this->c->get('translations'),
										'navigation'	=> $draftNavigation,
										'item'			=> $item,
										'home' 			=> $home,
										'projects' 		=> $projects,
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

		# configure multilang and multiproject
		$navigation->setProject($this->settings, $url);

		$extendedNavigation 	= $navigation->getFullExtendedNavigation($urlinfo, $langattr);

	    $projects 			= $navigation->getAllProjects($this->settings);

		$draftNavigation 	= $navigation->getFullDraftNavigation($urlinfo, $langattr);
	    $home 				= $navigation->getHomepageItem($urlinfo['baseurl']);

		if($url == '/')
		{
			$item 				= $home;
			$item->active 		= true;
		}
		else
		{
			$pageinfo = $navigation->getPageInfoForUrl($url, $urlinfo, $langattr);
		    if(!$pageinfo)
		    {
			    return $this->c->get('view')->render($response->withStatus(404), '404.twig', [
					'title'			=> 'Raw editor',
					'description'	=> 'Edit your content with the raw editor in pure markdown syntax.'
			    ]);
		    }

			$keyPathArray 		= explode(".", $pageinfo['keyPath']);

		    # extend : $request->getAttribute('c_userrole')
			$draftNavigation 	= $navigation->setActiveNaviItemsWithKeyPath($draftNavigation, $keyPathArray);
			$draftNavigation 	= $this->c->get('dispatcher')->dispatch(new OnPagetreeLoaded($draftNavigation), 'onPagetreeLoaded')->getData();

			$item 				= $navigation->getItemWithKeyPath($draftNavigation, $keyPathArray);
			$item 				= $this->c->get('dispatcher')->dispatch(new OnItemLoaded($item), 'onItemLoaded')->getData();
		}

	#	$item->modified		= ($item->published OR $item->drafted) ? filemtime($this->settings['contentFolder'] . $this->path) : false;

		$mainNavigation 	= $navigation->getMainNavigation($request->getAttribute('c_userrole'), $this->c->get('acl'), $urlinfo, $this->settings['editor']);

		$content 			= new Content($urlinfo['baseurl'], $this->settings, $this->c->get('dispatcher'));

		$draftMarkdown  	= $content->getDraftMarkdown($item);
		$draftMarkdown 		= $this->c->get('dispatcher')->dispatch(new OnMarkdownLoaded($draftMarkdown), 'onMarkdownLoaded')->getData();

		$draftMarkdownHtml	= $content->addDraftHtml($draftMarkdown);

	    return $this->c->get('view')->render($response, 'content/raw-editor.twig', [
			'settings' 			=> $this->settings,
			'darkmode'			=> $request->getAttribute('c_darkmode'),
			'mainnavi'			=> $mainNavigation,
			'content' 			=> $draftMarkdownHtml,
			'jsdata' 			=> [
										'settings' 		=> $this->settings,
										'urlinfo'		=> $urlinfo,
										'labels'		=> $this->c->get('translations'),
										'navigation'	=> $draftNavigation,
										'item'			=> $item,
										'home' 			=> $home,
										'projects' 		=> $projects,
										'content' 		=> $draftMarkdownHtml,
									]
		]);
	}
}