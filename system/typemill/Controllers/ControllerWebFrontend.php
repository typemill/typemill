<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;
use Typemill\Models\Navigation;
use Typemill\Models\Content;
use Typemill\Models\Meta;
use Typemill\Events\OnPagetreeLoaded;
use Typemill\Events\OnBreadcrumbLoaded;
use Typemill\Events\OnItemLoaded;
use Typemill\Events\OnMetaLoaded;
use Typemill\Events\OnMarkdownLoaded;
use Typemill\Events\OnContentArrayLoaded;
use Typemill\Events\OnHtmlLoaded;
use Typemill\Events\OnRestrictionsLoaded;
use Typemill\Events\OnPageReady;


class ControllerWebFrontend extends Controller
{
	public function index(Request $request, Response $response, $args)
	{
		$url 				= isset($args['route']) ? '/' . $args['route'] : '/';
		$urlinfo 			= $this->c->get('urlinfo');
		$langattr 			= $this->settings['langattr'];
		$userrole 			= $request->getAttribute('c_userrole');
		$username 			= $request->getAttribute('c_username');

		# GET THE NAVIGATION
	    $navigation 		= new Navigation();
		$draftNavigation 	= $navigation->getFullDraftNavigation($urlinfo, $langattr);
	    $home 				= false;

		# GET THE PAGINATION
		$currentpage 		= $navigation->getCurrentPage($args);
		if($currentpage)
		{
			$url 			= str_replace("/p/" . $currentpage, "", $url);
		}
		$fullUrl  			= $urlinfo['baseurl'] . $url;


		# FIND THE PAGE/ITEM IN NAVIGATION
		if($url == '/')
		{
			$item 				= $navigation->getHomepageItem($urlinfo['baseurl']);
			$item->active 		= true;
			$home 				= true;
		}
		else
		{
			$pageinfo 			= $navigation->getPageInfoForUrl($url, $urlinfo, $langattr);			

		    if(!$pageinfo)
		    {
			    return $this->c->get('view')->render($response->withStatus(404), '404.twig', [
					'title'			=> 'Page not found',
					'description'	=> 'We did not find the page you where looking for.'
			    ]);
		    }

			$keyPathArray 		= explode(".", $pageinfo['keyPath']);

			$item 				= $navigation->getItemWithKeyPath($draftNavigation, $keyPathArray);

			if(!$item)
			{
				return $this->c->get('view')->render($response->withStatus(404), '404.twig', [
					'title'			=> 'Page not found',
					'description'	=> 'We did not find the page you where looking for.'
				]);
			}
		}

		# CREATE THE BREADCRUMB
		$breadcrumb = $navigation->getBreadcrumb($draftNavigation, $item->keyPathArray);
		$breadcrumb = $this->c->get('dispatcher')->dispatch(new OnBreadcrumbLoaded($breadcrumb), 'onBreadcrumbLoaded')->getData();

		# CHECK IF WHOLE TREE IS PUBLISHED
		if($breadcrumb)
		{
			foreach($breadcrumb as $page)
			{
				if($page->status == 'unpublished')
				{
					return $this->c->get('view')->render($response->withStatus(404), '404.twig', [
						'title'			=> 'Page not found',
						'description'	=> 'We did not find the page you where looking for.'
					]);
				}
			}
		}

		$liveNavigation = $navigation->generateLiveNavigationFromDraft($draftNavigation);

		# STRIP OUT HIDDEN PAGES
		$liveNavigation = $navigation->removeHiddenPages($liveNavigation);

		# SET PAGEs ACTIVE
		$liveNavigation = $navigation->setActiveNaviItemsWithKeyPath($liveNavigation, $item->keyPathArray);

		# DISPATCH LIVE NAVIGATION
		$liveNavigation = $this->c->get('dispatcher')->dispatch(new OnPagetreeLoaded($liveNavigation), 'onPagetreeLoaded')->getData();

		# For FOLDERS use item without drafts and hidden pages
		if(!$home && $item->elementType == 'folder')
		{
			# $item = $navigation->getItemWithUrl($liveNavigation, $item->urlRelWoF);
			$item = $navigation->getItemWithKeyPath($liveNavigation, $item->keyPathArray);
		}

		# ADD BACKWARD-/FORWARD PAGINATION
		$item = $navigation->getPagingForItem($liveNavigation, $item);
		$item = $this->c->get('dispatcher')->dispatch(new OnItemLoaded($item), 'onItemLoaded')->getData();


		# GET THE CONTENT
		$content 			= new Content($urlinfo['baseurl'], $this->settings, $this->c->get('dispatcher'));
		$liveMarkdown		= $content->getLiveMarkdown($item);
		$liveMarkdown 		= $this->c->get('dispatcher')->dispatch(new OnMarkdownLoaded($liveMarkdown), 'onMarkdownLoaded')->getData();
		$markdownArray 		= $content->markdownTextToArray($liveMarkdown);


		# GET THE META
		$meta 				= new Meta();
		$metadata  			= $meta->getMetaData($item);
		$metadata 			= $meta->addMetaDefaults($metadata, $item, $this->settings['author']);
		$metadata 			= $meta->addMetaTitleDescription($metadata, $item, $markdownArray);
		$metadata 			= $this->c->get('dispatcher')->dispatch(new OnMetaLoaded($metadata),'onMetaLoaded')->getData();


		# REFERENCE FEATURE
		if(isset($metadata['meta']['referencetype']) && $metadata['meta']['referencetype'] != 'disable')
		{
			$referenceurl = rtrim($urlinfo['baseurl'], '/') . '/' . trim($metadata['meta']['reference'], '/');

			switch ($metadata['meta']['referencetype']) {
				case 'redirect301':
					return $response->withHeader('Location', $referenceurl)->withStatus(301);
					break;
				case 'redirect302':
					return $response->withHeader('Location', $referenceurl)->withStatus(302);
					break;
				case 'outlink':
					return $response->withHeader('Location', $metadata['meta']['reference'])->withStatus(301);
					break;
				case 'copy':
					$refpageinfo 		= $navigation->getPageInfoForUrl($metadata['meta']['reference'], $urlinfo, $langattr);
				    if(!$refpageinfo)
				    {
					    return $this->c->get('view')->render($response->withStatus(404), '404.twig', [
							'title'			=> 'Referenced page not found',
							'description'	=> 'We did not find the page that has been referenced. Please inform the website owner to fix it in meta reference.'
					    ]);
				    }

					$refKeyPathArray 	= explode(".", $refpageinfo['keyPath']);
					$refitem 			= $navigation->getItemWithKeyPath($draftNavigation, $refKeyPathArray);

					# GET THE CONTENT FROM REFENCED PAGE
					$liveMarkdown		= $content->getLiveMarkdown($refitem);
					$liveMarkdown 		= $this->c->get('dispatcher')->dispatch(new OnMarkdownLoaded($liveMarkdown), 'onMarkdownLoaded')->getData();
					$markdownArray 		= $content->markdownTextToArray($liveMarkdown);

					# GET THE META FROM REFERENCED PAGE
					$refmeta  			= $meta->getMetaData($refitem);
					if($refmeta && isset($refmeta['meta']))
					{
						$metadata 		= $meta->getMetaData($refitem);
						$metadata 		= $meta->addMetaDefaults($metadata, $refitem, $this->settings['author']);
						$metadata 		= $meta->addMetaTitleDescription($metadata, $refitem, $markdownArray);					
					}

					break;
			}
		}


		# CHECK ACCESS RESTRICTIONS
		$restricted 		= $this->checkRestrictions($metadata['meta'], $username, $userrole);
		if($restricted)
		{
			# infos that plugins need to add restriction content
			$restrictions = [
				'restricted' 		=> $restricted,
				'defaultContent' 	=> true,
				'markdownBlocks'	=> $markdownArray,
			];

			# dispatch the data
			$restrictions 	= $this->c->get('dispatcher')->dispatch(new OnRestrictionsLoaded( $restrictions ), 'onRestrictionsLoaded')->getData();

			# use the returned markdown
			$markdownArray = $restrictions['markdownBlocks'];

			# if no plugin has disabled the default behavior
			if($restrictions['defaultContent'])
			{
				# cut the restricted content
				$shortenedPage = $this->cutRestrictedContent($markdownArray);

				# check if there is customized content
				$restrictionnotice = $this->prepareRestrictionNotice();

				# add notice to shortened content
				$shortenedPage[] = $restrictionnotice;

				# Use the shortened page
				$markdownArray = $shortenedPage;
			}
		}


		# EXTRACT THE ARTICLE TITLE/HEADLINE
		$title 				= trim(array_shift($markdownArray), "# ");


		# TRANSFORM THE ARTICLE BODY TO HTML
		$body 				= $content->markdownArrayToText($markdownArray);
		$contentArray 		= $content->getContentArray($body);
		$contentArray 		= $this->c->get('dispatcher')->dispatch(new OnContentArrayLoaded($contentArray), 'onContentArrayLoaded')->getData();
		$contentHtml  		= $content->getContentHtml($contentArray);
		$contentHtml 		= $this->c->get('dispatcher')->dispatch(new OnHtmlLoaded($contentHtml), 'onHtmlLoaded')->getData();


		# ADD LOGO
		$logo = false;
		if(isset($this->settings['logo']) && $this->settings['logo'] != '' && $content->checkLogoFile($this->settings['logo']))
		{
			$logo = $this->settings['logo'];
		}


		# ADD ASSETS
		$assets 			= $this->c->get('assets'); 


		# ADD CUSTOM THEME CSS
		$theme 				= $this->settings['theme'];
		$customcss 			= $content->checkCustomCSS($theme);
		if($customcss)
		{
			$assets->addCSS($urlinfo['baseurl'] . '/cache/' . $theme . '-custom.css');
		}


		# ADD FAVICON
		$favicon = false;
		if(isset($this->settings['favicon']) && $this->settings['favicon'] != '')
		{
			$favicon = true;
			$assets->addMeta('tilecolor','<meta name="msapplication-TileColor" content="#F9F8F6" />');
			$assets->addMeta('tileimage','<meta name="msapplication-TileImage" content="' . $urlinfo['baseurl'] . '/media/custom/favicon-144x144.png" />');
			$assets->addMeta('icon16','<link rel="icon" type="image/png" href="' . $urlinfo['baseurl'] . '/media/custom/favicon-16x16.png" sizes="16x16" />');
			$assets->addMeta('icon32','<link rel="icon" type="image/png" href="' . $urlinfo['baseurl'] . '/media/custom/favicon-32x32.png" sizes="32x32" />');
			$assets->addMeta('icon72','<link rel="apple-touch-icon" sizes="72x72" href="' . $urlinfo['baseurl'] . '/media/custom/favicon-72x72.png" />');
			$assets->addMeta('icon114','<link rel="apple-touch-icon" sizes="114x114" href="' . $urlinfo['baseurl'] . '/media/custom/favicon-114x114.png" />');
			$assets->addMeta('icon144','<link rel="apple-touch-icon" sizes="144x144" href="' . $urlinfo['baseurl'] . '/media/custom/favicon-144x144.png" />');
			$assets->addMeta('icon180','<link rel="apple-touch-icon" sizes="180x180" href="' . $urlinfo['baseurl'] . '/media/custom/favicon-180x180.png" />');
		}


		# ADD META TAGS
		if(isset($metadata['meta']['noindex']) && $metadata['meta']['noindex'])
		{
			$assets->addMeta('noindex','<meta name="robots" content="noindex">');
		}
		$assets->addMeta('og_site_name','<meta property="og:site_name" content="' . $this->settings['title'] . '">');
		$assets->addMeta('og_title','<meta property="og:title" content="' . $metadata['meta']['title'] . '">');
		$assets->addMeta('og_description','<meta property="og:description" content="' . $metadata['meta']['description'] . '">');
		$assets->addMeta('og_type','<meta property="og:type" content="article">');
		$assets->addMeta('og_url','<meta property="og:url" content="' . $item->urlAbs . '">');


		# meta image
		$metaImageUrl = $metadata['meta']['heroimage'] ?? false;
		$metaImageAlt = $metadata['meta']['heroimagealt'] ?? false;
		if(!$metaImageUrl OR $metaImageUrl == '')
		{
			# extract first image from content
			$firstImageMD = $content->getFirstImage($contentArray);
			if($firstImageMD)
			{
				preg_match('#\((.*?)\)#', $firstImageMD, $img_url_result);
				$metaImageUrl = isset($img_url_result[1]) ? $img_url_result[1] : false;
				if($metaImageUrl)
				{
					preg_match('#\[(.*?)\]#', $firstImageMD, $img_alt_result);
					$metaImageAlt = isset($img_alt_result[1]) ? $img_alt_result[1] : false;
				}
			}
			elseif($logo)
			{
				$metaImageUrl = $logo;
				$pathinfo = pathinfo($this->settings['logo']);
				$metaImageAlt = $pathinfo['filename'];
			}
		}
		if($metaImageUrl)
		{
			$assets->addMeta('og_image','<meta property="og:image" content="' . $urlinfo['baseurl'] . '/' . $metaImageUrl . '">');
			$assets->addMeta('twitter_image_alt','<meta name="twitter:image:alt" content="' . $metaImageAlt . '">');
			$assets->addMeta('twitter_card','<meta name="twitter:card" content="summary_large_image">');
		}

		$pagedata = [
			'home'			=> $home,
			'navigation' 	=> $liveNavigation,
			'title' 		=> $title,
			'content' 		=> $contentHtml, 
			'item' 			=> $item,
			'breadcrumb' 	=> $breadcrumb, 
			'settings' 		=> $this->settings,
			'base_url' 		=> $urlinfo['baseurl'], 
			'metatabs'		=> $metadata,
			'logo'			=> $logo,
			'favicon'		=> $favicon,
			'currentpage'	=> $currentpage
		];

		$morepagedata = $this->c->get('dispatcher')->dispatch(new OnPageReady([]), 'onPageReady')->getData();

		$pagedata = array_merge($pagedata, $morepagedata);

		$route = empty($args) && isset($this->settings['themes'][$theme]['cover']) ? 'cover.twig' : 'index.twig';

	    return $this->c->get('view')->render($response, $route, $pagedata);
	}


	# checks if a page has a restriction in meta and if the current user is blocked by that restriction
	public function checkRestrictions($meta, $username, $userrole)
	{
		# check if content restrictions are active
		if(isset($this->settings['pageaccess']) && $this->settings['pageaccess'])
		{

			# check if page is restricted to certain user
			if(isset($meta['alloweduser']) && $meta['alloweduser'] && $meta['alloweduser'] !== '' )
			{
				$alloweduser = array_map('trim', explode(",", $meta['alloweduser']));
				if(isset($username) && in_array($username, $alloweduser))
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
				if(
					$userrole
					AND ( 
						$userrole == 'administrator' 
						OR $userrole == $meta['allowedrole'] 
						OR $this->c->get('acl')->inheritsRole($userrole, $meta['allowedrole']) 
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