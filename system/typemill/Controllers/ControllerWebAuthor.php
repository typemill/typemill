<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;
use Typemill\Models\Navigation;
use Typemill\Models\Content;
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
					'title'			=> 'Blox editor',
					'description'	=> 'Edit your content with the visual blox editor'
			    ]);
		    }

			$keyPathArray 		= explode(".", $pageinfo['keyPath']);

		    # extend : $request->getAttribute('c_userrole')
#		    $draftNavigation 	= $navigation->getDraftNavigation($urlinfo, $langattr);

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
					'title'			=> 'Raw editor',
					'description'	=> 'Edit your content with the raw editor in pure markdown syntax.'
			    ]);
		    }

			$keyPathArray 		= explode(".", $pageinfo['keyPath']);

		    # extend : $request->getAttribute('c_userrole')
		    $draftNavigation 	= $navigation->getDraftNavigation($urlinfo, $langattr);
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
										'content' 		=> $draftMarkdownHtml,
									]
		]);
	}
}