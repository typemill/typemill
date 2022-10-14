<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;

/*
use Typemill\Models\Folder;
use Typemill\Models\WriteMeta;
use Typemill\Extensions\ParsedownExtension;
use Typemill\Events\OnPagetreeLoaded;
use Typemill\Events\OnBreadcrumbLoaded;
use Typemill\Events\OnItemLoaded;
use Typemill\Events\OnOriginalLoaded;
use Typemill\Events\OnMetaLoaded;
use Typemill\Events\OnMarkdownLoaded;
use Typemill\Events\OnContentArrayLoaded;
use Typemill\Events\OnHtmlLoaded;
use Typemill\Events\OnRestrictionsLoaded;
*/

class ControllerWebFrontend extends Controller
{
	public function index(Request $request, Response $response)
	{
	    return $this->c->get('view')->render($response, 'home.twig', [
			'title'			=> 'Typemill Version 2',
			'description'	=> 'Typemill Version 2 wird noch besser als Version 1.'
	    ]);

		# Initiate Variables
		$contentHTML			= false;
		$item					= false;
		$home					= false;
		$breadcrumb 			= false;
		$currentpage			= false;
		$this->pathToContent	= $this->settings['rootPath'] . $this->settings['contentFolder'];
		$this->uri 				= $request->getUri()->withUserInfo('');
		$this->base_url 		= $this->uri->getBaseUrl();

		# if there is no structure at all, the content folder is probably empty
		if(!$this->setStructureLive())
		{
			return $this->render($response, '/index.twig', array( 'content' => '<h1>No Content</h1><p>Your content folder is empty.</p>' ));
		}

		# we can create an initial sitemap here, but makes no sense for every pagecall. Sitemap will be created on first author interaction (publish/delete/channge page).
		# $this->checkSitemap();

		# if the admin activated to refresh the cache automatically each 10 minutes (e.g. use without admin area)
		if(isset($this->settings['refreshcache']) && $this->settings['refreshcache'] && !$this->writeCache->validate('cache', 'lastCache.txt', 600))
		{
			# delete the cache
			$dir = $this->settings['basePath'] . 'cache';
			$this->writeCache->deleteCacheFiles($dir);

			# update the internal structure
			$this->setFreshStructureDraft();
			
			# update the public structure
			$this->setFreshStructureLive();

			# update the navigation
			$this->setFreshNavigation();

			# update the sitemap
			$this->updateSitemap();
		}

		# dispatch event and let others manipulate the structure
		$this->structureLive = $this->c->dispatcher->dispatch('onPagetreeLoaded', new OnPagetreeLoaded($this->structureLive))->getData();

		# check if there is a custom theme css
		$theme = $this->settings['theme'];
		$customcss = $this->writeCache->checkFile('cache', $theme . '-custom.css');
		if($customcss)
		{
			$this->c->assets->addCSS($this->base_url . '/cache/' . $theme . '-custom.css');
		}

		$logo = false;
		if(isset($this->settings['logo']) && $this->settings['logo'] != '')
		{
			# check if logo exists
			if(file_exists($this->settings['rootPath'] . 'media/live/' . $this->settings['logo']))
			{
				$logo = 'media/live/' . $this->settings['logo'];
			}
			elseif(file_exists($this->settings['rootPath'] . 'media/files/' . $this->settings['logo']))
			{
				$logo = 'media/files/' . $this->settings['logo'];				
			}
		}

		$favicon = false;
		if(isset($this->settings['favicon']) && $this->settings['favicon'] != '')
		{
			$favicon = true;
			$this->c->assets->addMeta('tilecolor','<meta name="msapplication-TileColor" content="#F9F8F6" />');
			$this->c->assets->addMeta('tileimage','<meta name="msapplication-TileImage" content="' . $this->base_url . '/media/files/favicon-144.png" />');
			$this->c->assets->addMeta('icon16','<link rel="icon" type="image/png" href="' . $this->base_url . '/media/files/favicon-16.png" sizes="16x16" />');
			$this->c->assets->addMeta('icon32','<link rel="icon" type="image/png" href="' . $this->base_url . '/media/files/favicon-32.png" sizes="32x32" />');
			$this->c->assets->addMeta('icon72','<link rel="apple-touch-icon" sizes="72x72" href="' . $this->base_url . '/media/files/favicon-72.png" />');
			$this->c->assets->addMeta('icon114','<link rel="apple-touch-icon" sizes="114x114" href="' . $this->base_url . '/media/files/favicon-114.png" />');
			$this->c->assets->addMeta('icon144','<link rel="apple-touch-icon" sizes="144x144" href="' . $this->base_url . '/media/files/favicon-144.png" />');
			$this->c->assets->addMeta('icon180','<link rel="apple-touch-icon" sizes="180x180" href="' . $this->base_url . '/media/files/favicon-180.png" />');
		}		

		# the navigation is a copy of the structure without the hidden pages
		# hint: if the navigation has been deleted from the cache, then we do not recreate it here to save performace. Instead you have to recreate cache in admin or change a page (publish/unpublish/delete/move)
		$navigation = $this->writeCache->getCache('cache', 'navigation.txt');
		if(!$navigation)
		{
			# use the structure if there is no cached navigation
			$navigation = $this->structureLive;
		}
		
		# start pagination
		if(isset($args['params']))
		{
			$argSegments = explode("/", $args['params']);

			# check if the last url segment is a number
			$pageNumber = array_pop($argSegments);
			if(is_numeric($pageNumber) && $pageNumber < 10000)
			{
				# then check if the segment before the page is a "p" that indicates a paginator
				$pageIndicator = array_pop($argSegments);
				if($pageIndicator == "p")
				{
					# use page number as current page variable
					$currentpage = $pageNumber;

					# set empty args for startpage
					$args = [];

					# if there are still params
					if(!empty($argSegments))
					{
						# add them to the args again
						$args['params'] = implode("/", $argSegments);
					}
				}
			}
		}

		# if the user is on startpage
		$home = false;
		if(empty($args))
		{
			$home 	= true;
			$item 	= Folder::getItemForUrl($navigation, $this->uri->getBasePath(), $this->uri->getBaseUrl(), NULL, $home);
			$urlRel = $this->uri->getBasePath();
		}
		else
		{
			# get the request url, trim args so physical folders have no trailing slash
			$urlRel = $this->uri->getBasePath() . '/' . trim($args['params'], "/");

			# find the url in the content-item-tree and return the item-object for the file
			# important to use the structure here so it is found, even if the item is hidden.
			$item = Folder::getItemForUrl($this->structureLive, $urlRel, $this->uri->getBasePath());

			# if the item is a folder and if that folder is not hidden
			if($item && $item->elementType == 'folder' && isset($item->hide) && !$item->hide)
			{
				# use the navigation instead of the structure so that hidden elements are erased
				$item = Folder::getItemForUrl($navigation, $urlRel, $this->uri->getBaseUrl(), NULL, $home);
			}

			# if there is still no item, return a 404-page
			if(!$item)
			{
				return $this->render404($response, array( 
					'navigation'	=> $navigation, 
					'settings' 		=> $this->settings,  
					'base_url' 		=> $this->base_url,
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
		}

		if(isset($item->hide)) 
		{
			# if it is a hidden page
 			if($item->hide)
 			{
				# get breadcrumb for page and set pages active
				# use structure here because the hidden item is not part of the navigation
				$breadcrumb = Folder::getBreadcrumb($this->structureLive, $item->keyPathArray);
				$breadcrumb = $this->c->dispatcher->dispatch('onBreadcrumbLoaded', new OnBreadcrumbLoaded($breadcrumb))->getData();

				# add the paging to the item
				$item = Folder::getPagingForItem($this->structureLive, $item);
 			}
			else
			{
				# get breadcrumb for page and set pages active
				# use navigation, because it is used for frontend
				$breadcrumb = Folder::getBreadcrumb($navigation, $item->keyPathArray);
				$breadcrumb = $this->c->dispatcher->dispatch('onBreadcrumbLoaded', new OnBreadcrumbLoaded($breadcrumb))->getData();
				
				# add the paging to the item
				$item = Folder::getPagingForItem($navigation, $item);
			}
		}

		# dispatch the item
		$item 			= $this->c->dispatcher->dispatch('onItemLoaded', new OnItemLoaded($item))->getData();

		# set the filepath
		$filePath 		= $this->pathToContent . $item->path;
		
		# check if url is a folder and add index.md 
		if($item->elementType == 'folder')
		{
			$filePath 	= $filePath . DIRECTORY_SEPARATOR . 'index.md';
		}

		# read the content of the file
		$contentMD 		= file_exists($filePath) ? file_get_contents($filePath) : false;

		# dispatch the original content without plugin-manipulations for case anyone wants to use it
		$this->c->dispatcher->dispatch('onOriginalLoaded', new OnOriginalLoaded($contentMD));

		# initiate object for metadata
		$writeMeta 		= new WriteMeta();
		
		# makes sure that you always have the full meta with title, description and all the rest.
		$metatabs 		= $writeMeta->completePageMeta($contentMD, $this->settings, $item);

		# write meta
		if(isset($metatabs['meta']['noindex']) && $metatabs['meta']['noindex'])
		{
			$this->c->assets->addMeta('noindex','<meta name="robots" content="noindex">');
		}

		$this->c->assets->addMeta('og_site_name','<meta property="og:site_name" content="' . $this->settings['title'] . '">');
		$this->c->assets->addMeta('og_title','<meta property="og:title" content="' . $metatabs['meta']['title'] . '">');
		$this->c->assets->addMeta('og_description','<meta property="og:description" content="' . $metatabs['meta']['description'] . '">');
		$this->c->assets->addMeta('og_type','<meta property="og:type" content="article">');
		$this->c->assets->addMeta('og_url','<meta property="og:url" content="' . $item->urlAbs . '">');

		# dispatch meta 
		$metatabs 		= $this->c->dispatcher->dispatch('onMetaLoaded', new OnMetaLoaded($metatabs))->getData();

		# dispatch content
		$contentMD 		= $this->c->dispatcher->dispatch('onMarkdownLoaded', new OnMarkdownLoaded($contentMD))->getData();

		$itemUrl 		= isset($item->urlRel) ? $item->urlRel : false;

		/* initialize parsedown */
		$parsedown 		= new ParsedownExtension($this->base_url, $this->settings, $this->c->dispatcher);
		
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
			$firstImage = array('img_url' => $this->base_url . '/' . $img_url, 'img_alt' => $img_alt);

			$this->c->assets->addMeta('og_image','<meta property="og:image" content="' . $this->base_url . '/' . $img_url . '">');
			$this->c->assets->addMeta('twitter_image_alt','<meta name="twitter:image:alt" content="' . $img_alt . '">');
			$this->c->assets->addMeta('twitter_card','<meta name="twitter:card" content="summary_large_image">');
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
			'base_url' 		=> $this->base_url, 
			'metatabs'		=> $metatabs,
			'image' 		=> $firstImage,
			'logo'			=> $logo,
			'favicon'		=> $favicon,
			'currentpage'	=> $currentpage
		]);
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