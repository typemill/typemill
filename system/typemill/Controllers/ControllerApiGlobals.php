<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\Navigation;
use Typemill\Models\Multilang;
use Typemill\Models\Validation;
use Typemill\Models\Content;
use Typemill\Models\Meta;
use Typemill\Models\Sitemap;
use Typemill\Static\Translations;
use Typemill\Models\StorageWrapper;

class ControllerApiGlobals extends Controller
{
	public function getSystemNavi(Request $request, Response $response)
	{
		$navigation 		= new Navigation();
		$systemNavigation	= $navigation->getSystemNavigation(
									$userrole 		= $request->getAttribute('c_userrole'),
									$acl 			= $this->c->get('acl'),
									$urlinfo 		= $this->c->get('urlinfo'),
									$dispatcher 	= $this->c->get('dispatcher'),
									$parser 		= $this->routeParser
								);

		# won't work because api has no session, instead you have to pass user
		$response->getBody()->write(json_encode([
			'systemnavi' => $systemNavigation
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	public function getMainNavi(Request $request, Response $response)
	{
		$navigation 		= new Navigation();
		$mainNavigation		= $navigation->getMainNavigation(
									$userrole 	= $request->getAttribute('c_userrole'),
									$acl 		= $this->c->get('acl'),
									$urlinfo 	= $this->c->get('urlinfo'),
									$editor 	= $this->settings['editor']
								);

		$response->getBody()->write(json_encode([
			'mainnavi' => $mainNavigation
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	public function getNavigation(Request $request, Response $response, $args)
	{
		$params 			= $request->getQueryParams();

		$urlinfo 			= $this->c->get('urlinfo');
		$langattr 			= $this->settings['langattr'];
		$navigation 		= new Navigation();
		$draft 				= $params['draft'] ?? false;
		$url 				= $params['url'] ?? false;

		if($url)
		{
			$navigation->setProject($this->settings, $params['url'], $dispatcher = false);
		}

		if(isset($params['draft']) && $params['draft'] == true)
		{
			$contentnavi   	= $navigation->getFullDraftNavigation($urlinfo, $langattr);
		}
		else
		{
			$contentnavi 	= $navigation->getLiveNavigation($urlinfo, $langattr);	
		}

		if(!$contentnavi)
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('navigation not found'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		$response->getBody()->write(json_encode([
			'navigation'		=> $contentnavi
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}

	public function clearNavigation(Request $request, Response $response)
	{
		$navigation = new Navigation();

		$result = $navigation->clearAllNavigations();

		$response->getBody()->write(json_encode([
			'result' => $result
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);;
	}

	public function getItemForUrl(Request $request, Response $response, $args)
	{
		$params 			= $request->getQueryParams();
		$validate			= new Validation();
		$validInput 		= $validate->articleUrl($params);
		if($validInput !== true)
		{
			$errors 		= $validate->returnFirstValidationErrors($validInput);
			$response->getBody()->write(json_encode([
				'message' 	=> reset($errors),
				'errors' 	=> $errors
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$urlinfo 			= $this->c->get('urlinfo');
		$langattr 			= $this->settings['langattr'];
		$url 				= $params['url'];

		$navigation 		= new Navigation();
		$navigation->setProject($this->settings, $url, $dispatcher = false);

		$item 				= $navigation->getItemForUrl($url, $urlinfo, $langattr);

		if(!$item)
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('page not found'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		$response->getBody()->write(json_encode([
			'item'		=> $item
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}

	public function getItemsForSlug(Request $request, Response $response, $args)
	{
		$params 			= $request->getQueryParams();
		$validate			= new Validation();
		$validInput 		= $validate->articleSlug($params);
		if($validInput !== true)
		{
			$errors 		= $validate->returnFirstValidationErrors($validInput);
			$response->getBody()->write(json_encode([
				'message' 	=> reset($errors),
				'errors' 	=> $errors
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$urlinfo 			= $this->c->get('urlinfo');
		$langattr 			= $this->settings['langattr'];
		$url 				= $params['url'];

		$navigation 		= new Navigation();
		$url 				= $navigation->removeEditorFromUrl($url);
		if($url)
		{
			$navigation->setProject($this->settings, $url, $dispatcher = false);
		}

		$item 				= $navigation->getItemForUrl($url, $urlinfo, $langattr);
		if(!$items)
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('page not found'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		$response->getBody()->write(json_encode([
			'items'		=> $items
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}

	public function getArticleContent(Request $request, Response $response, $args)
	{
		$params 			= $request->getQueryParams();
		$validate			= new Validation();
		$validInput 		= $validate->articleUrl($params);
		if($validInput !== true)
		{
			$errors 		= $validate->returnFirstValidationErrors($validInput);
			$response->getBody()->write(json_encode([
				'message' 	=> reset($errors),
				'errors' 	=> $errors
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$urlinfo 			= $this->c->get('urlinfo');
		$langattr 			= $this->settings['langattr'];
		$url 				= $params['url'];

		$navigation 		= new Navigation();
		$url 				= $navigation->removeEditorFromUrl($url);
		if($url)
		{
			$navigation->setProject($this->settings, $url, $dispatcher = false);
		}
		
		$item 				= $navigation->getItemForUrl($url, $urlinfo, $langattr);
		if(!$item)
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('page not found'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		# if user is not allowed to perform this action (e.g. not admin)
		if(!$this->userroleIsAllowed($request->getAttribute('c_userrole'), 'content', 'read'))
		{
			# then check if user is the owner of this content
			$meta = new Meta();
			$metadata = $meta->getMetaData($item);
			if(!$this->userIsAllowed($request->getAttribute('c_username'), $metadata))
			{
				$response->getBody()->write(json_encode([
					'message' 	=> Translations::translate('You do not have enough rights.'),
				]));

				return $response->withHeader('Content-Type', 'application/json')->withStatus(403);				
			}
		}

		# GET THE CONTENT
		$content 			= new Content($urlinfo['baseurl'], $this->settings, $this->c->get('dispatcher'));
		$markdown 			= $content->getLiveMarkdown($item);

		if(isset($params['draft']) && $params['draft'] == true)
		{
			# if draft is explicitly requested
			$markdown 		= $content->getDraftMarkdown($item);
		}

		if(!$markdown)
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('page not found'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		if(!is_array($markdown))
		{
			$markdown 		= $content->markdownTextToArray($markdown);
		}
		$markdownHtml		= $content->addDraftHtml($markdown);

		$response->getBody()->write(json_encode([
			'content'		=> $markdownHtml
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}	

	public function getArticleMeta(Request $request, Response $response, $args)
	{
		$params 			= $request->getQueryParams();
		$validate			= new Validation();
		$validInput 		= $validate->articleUrl($params);
		if($validInput !== true)
		{
			$errors 		= $validate->returnFirstValidationErrors($validInput);
			$response->getBody()->write(json_encode([
				'message' 	=> reset($errors),
				'errors' 	=> $errors
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$urlinfo 			= $this->c->get('urlinfo');
		$langattr 			= $this->settings['langattr'];
		$url 				= $params['url'];

		$navigation 		= new Navigation();
		$url 				= $navigation->removeEditorFromUrl($url);

		# configure multilang and multiproject
		$navigation->setProject($this->settings, $url, $this->c->get('dispatcher'));

		$item 				= $navigation->getItemForUrl($url, $urlinfo, $langattr);
		if(!$item)
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('page not found'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		# if user is not allowed to perform this action (e.g. not admin)
		if(!$this->userroleIsAllowed($request->getAttribute('c_userrole'), 'content', 'read'))
		{
			# then check if user is the owner of this content
			$meta = new Meta();
			$metadata = $meta->getMetaData($item);
			if(!$this->userIsAllowed($request->getAttribute('c_username'), $metadata))
			{
				$response->getBody()->write(json_encode([
					'message' 	=> Translations::translate('You do not have enough rights.'),
				]));

				return $response->withHeader('Content-Type', 'application/json')->withStatus(403);				
			}
		}

		# GET THE META
		$meta 				= new Meta();
		$metadata  			= $meta->getMetaData($item);
		$metadata 			= $meta->addMetaDefaults($metadata, $item, $this->settings['author']);
#		$metadata 			= $meta->addMetaTitleDescription($metadata, $item, $markdown);

		$response->getBody()->write(json_encode([
			'meta'			=> $metadata
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}

	public function showSecurityLog(Request $request, Response $response)
	{
		$storage 	= new StorageWrapper('\Typemill\Models\Storage');
		$logfile 	= $storage->getFile('dataFolder', 'security', 'securitylog.txt');

		if($logfile)
		{
			$logfile = trim($logfile);
			if($logfile == '')
			{
				$lines = ['Logfile is empty'];
			}
			else
			{
				$lines = preg_split('/\r\n|\n|\r/', $logfile);				
			}

			$response->getBody()->write(json_encode([
				'lines' => $lines
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(200);;

		}

		$response->getBody()->write(json_encode([
			'error' => 'No logfile found'
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
	}

	public function deleteSecurityLog(Request $request, Response $response)
	{
		$storage 	= new StorageWrapper('\Typemill\Models\Storage');
		$result 	= $storage->deleteFile('dataFolder', 'security', 'securitylog.txt');

		$response->getBody()->write(json_encode([
			'result' => $result
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);;
	}

	public function deleteCache(Request $request, Response $response)
	{
		$storage 	= new StorageWrapper('\Typemill\Models\Storage');

		$cacheFolder = $storage->getFolderPath('cacheFolder');

		$iterator 	= new \RecursiveDirectoryIterator($cacheFolder, \RecursiveDirectoryIterator::SKIP_DOTS);
		$files 		= new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::CHILD_FIRST);
		
		$error = false;

		foreach($files as $file)
		{
		    if ($file->isDir())
		    {
		    	if(!rmdir($file->getRealPath()))
		    	{
		    		$error = 'Could not delete some folders.';
		    	}
		    }
		    elseif($file->getExtension() !== 'css')
		    {
				if(!unlink($file->getRealPath()) )
				{
					$error = 'Could not delete some files.';
				}
		    }
		}

		$sitemap 		= new Sitemap();
		$navigation 	= new Navigation();
		$urlinfo 		= $this->c->get('urlinfo');
		$liveNavigation = $navigation->getLiveNavigation($urlinfo, $this->settings['langattr']);
		$sitemap->updateSitemap($liveNavigation, $urlinfo);

		if($error)
		{
			$response->getBody()->write(json_encode([
				'error' => $error
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		$response->getBody()->write(json_encode([
			'result' => true
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	public function getTranslations(Request $request, Response $response)
	{		
		$response->getBody()->write(json_encode([
			'translations' => $this->c->get('translations'),
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}
}