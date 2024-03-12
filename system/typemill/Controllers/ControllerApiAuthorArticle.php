<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;
use Typemill\Models\StorageWrapper;
use Typemill\Models\Validation;
use Typemill\Models\Navigation;
use Typemill\Models\Content;
use Typemill\Models\Meta;
use Typemill\Models\Sitemap;
use Typemill\Static\Slug;
use Typemill\Static\Translations;
use Typemill\Events\OnPagePublished;
use Typemill\Events\OnPageUnpublished;
use Typemill\Events\OnPageDeleted;
use Typemill\Events\OnPageSorted;
use Typemill\Events\OnPageRenamed;

class ControllerApiAuthorArticle extends Controller
{
	public function publishArticle(Request $request, Response $response, $args)
	{
		$params 			= $request->getParsedBody();
		$validate			= new Validation();
		$validInput 		= $validate->articlePublish($params);
		if($validInput !== true)
		{
			$errors 		= $validate->returnFirstValidationErrors($validInput);
			$response->getBody()->write(json_encode([
				'message' 	=> reset($errors),
				'errors' 	=> $errors
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$navigation 		= new Navigation();
		$urlinfo 			= $this->c->get('urlinfo');
		$item 				= $this->getItem($navigation, $params['url'], $urlinfo);
		if(!$item)
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('page not found'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		# if user is not allowed to perform this action (e.g. not admin)
		if(!$this->userroleIsAllowed($request->getAttribute('c_userrole'), 'content', 'publish'))
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

	    # publish content
		$content 			= new Content($urlinfo['baseurl'], $this->settings, $this->c->get('dispatcher'));
		$draftMarkdown  	= $content->getDraftMarkdown($item);
		$publish 			= $content->publishMarkdown($item, $draftMarkdown);
		if($publish !== true)
		{
			$response->getBody()->write(json_encode([
				'message' => $publish,
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
		}

		# refresh navigation and item
	    $navigation->clearNavigation();
		$draftNavigation 	= $navigation->getDraftNavigation($urlinfo, $this->settings['langattr']);
		$draftNavigation 	= $navigation->setActiveNaviItemsWithKeyPath($draftNavigation, $item->keyPathArray);
		$item 				= $navigation->getItemWithKeyPath($draftNavigation, $item->keyPathArray);

		$sitemap 			= new Sitemap();
		$sitemap->updateSitemap($draftNavigation, $urlinfo);

		# META is important e.g. for newsletter, so send it, too
		$meta 				= new Meta();
		$metadata  			= $meta->getMetaData($item);
		$metadata 			= $meta->addMetaDefaults($metadata, $item, $this->settings['author'], $request->getAttribute('c_username'));
		$metadata 			= $meta->addMetaTitleDescription($metadata, $item, $draftMarkdown);

		# dispatch event, e.g. send newsletter and more
		$data = [
			'markdown' 	=> $draftMarkdown, 
			'item' 		=> $item,
			'metadata'	=> $metadata
		];

		$message = $this->c->get('dispatcher')->dispatch(new OnPagePublished($data), 'onPagePublished')->getData();

		# validate message

		$response->getBody()->write(json_encode([
			'navigation'	=> $draftNavigation,
			'item'			=> $item,
			'metadata'		=> $metadata,
			'message'		=> $message
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}

	public function unpublishArticle(Request $request, Response $response, $args)
	{
		$params 			= $request->getParsedBody();
		$validate			= new Validation();
		$validInput 		= $validate->articlePublish($params);
		if($validInput !== true)
		{
			$errors 		= $validate->returnFirstValidationErrors($validInput);
			$response->getBody()->write(json_encode([
				'message' 	=> reset($errors),
				'errors' 	=> $errors
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$navigation 		= new Navigation();
		$urlinfo 			= $this->c->get('urlinfo');
		$item 				= $this->getItem($navigation, $params['url'], $urlinfo);
		if(!$item)
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('page not found'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		# if user is not allowed to perform this action (e.g. not admin)
		if(!$this->userroleIsAllowed($request->getAttribute('c_userrole'), 'content', 'publish'))
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

	    # publish content
		$content 			= new Content($urlinfo['baseurl'], $this->settings, $this->c->get('dispatcher'));
		$draftMarkdown  	= $content->getDraftMarkdown($item);
		$content->unpublishMarkdown($item, $draftMarkdown);

		# refresh navigation and item
	    $navigation->clearNavigation();
		$draftNavigation 	= $navigation->getDraftNavigation($urlinfo, $this->settings['langattr']);
		$draftNavigation 	= $navigation->setActiveNaviItemsWithKeyPath($draftNavigation, $item->keyPathArray);
		$item 				= $navigation->getItemWithKeyPath($draftNavigation, $item->keyPathArray);
		
		$sitemap 			= new Sitemap();
		$sitemap->updateSitemap($draftNavigation, $urlinfo);

		# check if it is a folder and if the folder has published pages.
		$message = false;
		if($item->elementType == 'folder' && isset($item->folderContent))
		{
			foreach($item->folderContent as $folderContent)
			{
				if($folderContent->status == 'published' OR $folderContent->status == 'modified')
				{
					$message = Translations::translate('There are published pages within this folder. The pages are not visible on your website anymore.');
				}
			}
		}

		# dispatch event
		$this->c->get('dispatcher')->dispatch(new OnPageUnpublished($item), 'onPageUnpublished');

		$response->getBody()->write(json_encode([
			'message'		=> $message,
			'navigation'	=> $draftNavigation,
			'item'			=> $item
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}

	public function updateDraft(Request $request, Response $response, $args)
	{
		$params 			= $request->getParsedBody();
		$validate			= new Validation();
		$validInput 		= $validate->articleUpdate($params);
		if($validInput !== true)
		{
			$errors 		= $validate->returnFirstValidationErrors($validInput);
			$response->getBody()->write(json_encode([
				'message' 	=> reset($errors),
				'errors' 	=> $errors
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$navigation 		= new Navigation();
		$urlinfo 			= $this->c->get('urlinfo');
		$item 				= $this->getItem($navigation, $params['url'], $urlinfo);
		if(!$item)
		{
			$response->getBody()->write(json_encode([
				'message' => 'page not found',
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		# if user is not allowed to perform this action (e.g. not admin)
		if(!$this->userroleIsAllowed($request->getAttribute('c_userrole'), 'content', 'update'))
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

	    # save draft content
		$content 			= new Content($urlinfo['baseurl'], $this->settings, $this->c->get('dispatcher'));
		$markdown 			= $params['title'] . PHP_EOL . PHP_EOL . $params['body'];
		$markdownArray 		= $content->markdownTextToArray($markdown);
		$content->saveDraftMarkdown($item, $markdownArray);

		# refresh navigation and item
	    $navigation->clearNavigation();
		$draftNavigation 	= $navigation->getDraftNavigation($urlinfo, $this->settings['langattr']);
		$draftNavigation 	= $navigation->setActiveNaviItemsWithKeyPath($draftNavigation, $item->keyPathArray);
		$item 				= $navigation->getItemWithKeyPath($draftNavigation, $item->keyPathArray);
		
		# refresh content
		$draftMarkdown  	= $content->getDraftMarkdown($item);
		$draftMarkdownHtml	= $content->addDraftHtml($draftMarkdown);

		$response->getBody()->write(json_encode([
			'item'			=> $item,
			'navigation'	=> $draftNavigation,
			'content' 		=> $draftMarkdownHtml
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}

	public function publishDraft(Request $request, Response $response, $args)
	{
		$params 			= $request->getParsedBody();
		$validate			= new Validation();
		$validInput 		= $validate->articleUpdate($params);
		if($validInput !== true)
		{
			$errors 		= $validate->returnFirstValidationErrors($validInput);
			$response->getBody()->write(json_encode([
				'message' 	=> reset($errors),
				'errors' 	=> $errors
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$navigation 		= new Navigation();
		$urlinfo 			= $this->c->get('urlinfo');
		$item 				= $this->getItem($navigation, $params['url'], $urlinfo);
		if(!$item)
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('page not found'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		# if user is not allowed to perform this action (e.g. not admin)
		if(!$this->userroleIsAllowed($request->getAttribute('c_userrole'), 'content', 'update'))
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

	    # save draft content
		$content 			= new Content($urlinfo['baseurl'], $this->settings, $this->c->get('dispatcher'));
		$markdown 			= $params['title'] . PHP_EOL . PHP_EOL . $params['body'];
		$markdownArray 		= $content->markdownTextToArray($markdown);
		$content->publishMarkdown($item, $markdownArray);

		# refresh navigation and item
	    $navigation->clearNavigation();
		$draftNavigation 	= $navigation->getDraftNavigation($urlinfo, $this->settings['langattr']);
		$draftNavigation 	= $navigation->setActiveNaviItemsWithKeyPath($draftNavigation, $item->keyPathArray);
		$item 				= $navigation->getItemWithKeyPath($draftNavigation, $item->keyPathArray);

		$sitemap 			= new Sitemap();
		$sitemap->updateSitemap($draftNavigation, $urlinfo);
		
		# refresh content
		$draftMarkdown  	= $content->getDraftMarkdown($item);
		$draftMarkdownHtml	= $content->addDraftHtml($draftMarkdown);

		# META is important e.g. for newsletter, so send it, too
		$meta 				= new Meta();
		$metadata  			= $meta->getMetaData($item);
		$metadata 			= $meta->addMetaDefaults($metadata, $item, $this->settings['author'], $request->getAttribute('c_username'));
#		$metadata 			= $meta->addMetaTitleDescription($metadata, $item, $markdownArray);

		# dispatch event, e.g. send newsletter and more
		$data = [
			'markdown' 	=> $draftMarkdown, 
			'item' 		=> $item,
			'meta'		=> $metadata
		];
		$this->c->get('dispatcher')->dispatch(new OnPagePublished($data), 'onPagePublished');

		$response->getBody()->write(json_encode([
			'item'			=> $item,
			'navigation'	=> $draftNavigation,
			'content' 		=> $draftMarkdownHtml
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}

	public function discardArticleChanges(Request $request, Response $response, $args)
	{
		$params 			= $request->getParsedBody();
		$validate			= new Validation();
		$validInput 		= $validate->articlePublish($params);
		if($validInput !== true)
		{
			$errors 		= $validate->returnFirstValidationErrors($validInput);
			$response->getBody()->write(json_encode([
				'message' 	=> reset($errors),
				'errors' 	=> $errors
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$navigation 		= new Navigation();
		$urlinfo 			= $this->c->get('urlinfo');
		$item 				= $this->getItem($navigation, $params['url'], $urlinfo);
		if(!$item)
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('page not found'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		# if user is not allowed to perform this action (e.g. not admin)
		if(!$this->userroleIsAllowed($request->getAttribute('c_userrole'), 'content', 'update'))
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

	    # publish content
		$content 			= new Content($urlinfo['baseurl'], $this->settings, $this->c->get('dispatcher'));
		$content->deleteDraft($item);

		# refresh navigation and item
	    $navigation->clearNavigation();
		$draftNavigation 	= $navigation->getDraftNavigation($urlinfo, $this->settings['langattr']);
		$draftNavigation 	= $navigation->setActiveNaviItemsWithKeyPath($draftNavigation, $item->keyPathArray);
		$item 				= $navigation->getItemWithKeyPath($draftNavigation, $item->keyPathArray);
		
		# refresh content
		$draftMarkdown  	= $content->getDraftMarkdown($item);
		$draftMarkdownHtml	= $content->addDraftHtml($draftMarkdown);

		$response->getBody()->write(json_encode([
			'item'			=> $item,
			'navigation'	=> $draftNavigation,
			'content' 		=> $draftMarkdownHtml
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}

	public function createArticle(Request $request, Response $response, $args)
	{
		$params 			= $request->getParsedBody();
		$validate			= new Validation();
		$validInput 		= $validate->navigationItem($params);
		if($validInput !== true)
		{
			$errors 		= $validate->returnFirstValidationErrors($validInput);
			$response->getBody()->write(json_encode([
				'message' 	=> reset($errors),
				'errors' 	=> $errors
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		# set variables
		$urlinfo 			= $this->c->get('urlinfo');
		$langattr 			= $this->settings['langattr'] ?? 'en';

		# get navigation
	    $navigation 		= new Navigation();
	    $draftNavigation 	= $navigation->getDraftNavigation($urlinfo, $langattr);
	    if($params['folder_id'] == 'root')
	    {
   			if($params['item_name'] == 'tm')
   			{
				$response->getBody()->write(json_encode([
					'message' 	=> Translations::translate('You cannot create an item with the slug /tm in the root folder because this is the system path.')
				]));

				return $response->withHeader('Content-Type', 'application/json')->withStatus(402);
   			}

			$folderContent		= $draftNavigation;
		}
		else
	    {
			# get the ids (key path) for item, old folder and new folder
			$folderKeyPath 		= explode('.', $params['folder_id']);
			
			# get the item from structure
			$folder				= $navigation->getItemWithKeyPath($draftNavigation, $folderKeyPath);

			if(!$folder)
			{ 
				$response->getBody()->write(json_encode([
					'message' 	=> Translations::translate('We could not find this page. Please refresh and try again.')
				]));

				return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
			}

			$folderContent		= $folder->folderContent;
	    }

		$slug 			= Slug::createSlug($params['item_name'], $langattr);

		# iterate through the whole content of the new folder
		$index 			= 0;
		$writeError 	= false;
		$folderPath 	= isset($folder) ? $folder->path : '';
		$storage 		= new StorageWrapper('\Typemill\Models\Storage');

		foreach($folderContent as $folderItem)
		{
			# check, if the same name as new item, then return an error
			if($folderItem->slug == $slug)
			{
				$response->getBody()->write(json_encode([
					'message' 	=> Translations::translate('There is already a page with this name. Please choose another name.')
				]));

				return $response->withHeader('Content-Type', 'application/json')->withStatus(402);
			}
			
			# rename files just in case that index is not in line (because file has been moved before)
			if(!$storage->moveContentFile($folderItem, $folderPath, $index))
			{
				$writeError = true;
			}
			$index++;
		}

		if($writeError)
		{ 
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('Something went wrong. Please refresh the page and check, if all folders and files are writable.')
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
		}

		# add prefix number to the name
		$namePath 	= $index > 9 ? $index . '-' . $slug : '0' . $index . '-' . $slug;
		
		# create default content
		$content 	= json_encode(['# ' . $params['item_name'], 'Content']);
		
		# for initial metadata
		$meta 		= new Meta();

		if($params['type'] == 'file')
		{
			if(!$storage->writeFile('contentFolder', $folderPath, $namePath . '.txt', $content))
			{
				$response->getBody()->write(json_encode([
					'message' 	=> Translations::translate('We could not create the file. Please refresh the page and check, if all folders and files are writable.')
				]));

				return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
			}

			$metadata = $meta->createInitialMeta($request->getAttribute('c_username'), $params['item_name']);

			$storage->updateYaml('contentFolder', $folderPath, $namePath . '.yaml', $metadata);
		}
		elseif($params['type'] == 'folder')
		{
			if(!$storage->checkFolder('contentFolder', $folderPath, $namePath))
			{
				$response->getBody()->write(json_encode([
					'message' 	=> Translations::translate('We could not create the folder. Please refresh the page and check, if all folders and files are writable.')
				]));

				return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
			}

			$storage->writeFile('contentFolder', $folderPath . DIRECTORY_SEPARATOR . $namePath, 'index.txt', $content);

			$metadata = $meta->createInitialMeta($request->getAttribute('c_username'), $params['item_name']);

			$storage->updateYaml('contentFolder', $folderPath . DIRECTORY_SEPARATOR . $namePath, 'index.yaml', $metadata);

			# always redirect to a folder
#			$url = $urlinfo['baseurl'] . '/tm/content/' . $this->settings['editor'] . $folder->urlRelWoF . '/' . $slug;
		}

		$navigation->clearNavigation();

		$response->getBody()->write(json_encode([
			'navigation'	=> $navigation->getDraftNavigation($urlinfo, $langattr),
			'message'		=> '',
			'url' 			=> false
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}

	public function createPost(Request $request, Response $response, $args)
	{
		$params 			= $request->getParsedBody();
		$validate			= new Validation();
		$validInput 		= $validate->navigationItem($params);
		if($validInput !== true)
		{
			$errors 		= $validate->returnFirstValidationErrors($validInput);
			$response->getBody()->write(json_encode([
				'message' 	=> reset($errors),
				'errors' 	=> $errors
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		# set variables
		$urlinfo 			= $this->c->get('urlinfo');
		$langattr 			= $this->settings['langattr'] ?? 'en';

		# get navigation
	    $navigation 		= new Navigation();
	    $draftNavigation 	= $navigation->getDraftNavigation($urlinfo, $langattr);
	    if($params['folder_id'] == 'root')
	    {
			$folderContent		= $draftNavigation;
		}
		else
	    {
			# get the ids (key path) for item, old folder and new folder
			$folderKeyPath 		= explode('.', $params['folder_id']);
			
			# get the item from structure
			$folder				= $navigation->getItemWithKeyPath($draftNavigation, $folderKeyPath);

			if(!$folder)
			{ 
				$response->getBody()->write(json_encode([
					'message' 	=> Translations::translate('We could not find this page. Please refresh and try again.')
				]));

				return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
			}

			$folderContent		= $folder->folderContent;
	    }

		$slug 			= Slug::createSlug($params['item_name'], $langattr);

		# iterate through the whole content of the new folder
		$index 			= 0;
		$writeError 	= false;
		$folderPath 	= isset($folder) ? $folder->path : '';
		$storage 		= new StorageWrapper('\Typemill\Models\Storage');

		foreach($folderContent as $folderItem)
		{
			# check, if the same name as new item, then return an error
			if($folderItem->slug == $slug)
			{
				$response->getBody()->write(json_encode([
					'message' 	=> Translations::translate('There is already a page with this name. Please choose another name.')
				]));

				return $response->withHeader('Content-Type', 'application/json')->withStatus(402);
			}
		}

		# add prefix date to the name
		$namePath 		= date("YmdHi") . '-' . $slug;
		
		# create default content
		$content = json_encode(['# ' . $params['item_name'], 'Content']);

		# for initial metadata
		$meta 		= new Meta();
		
		if($params['type'] == 'file')
		{
			if(!$storage->writeFile('contentFolder', $folderPath, $namePath . '.txt', $content))
			{
				$response->getBody()->write(json_encode([
					'message' 	=> Translations::translate('We could not create the file. Please refresh the page and check, if all folders and files are writable.')
				]));

				return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
			}

			$metadata = $meta->createInitialMeta($request->getAttribute('c_username'), $params['item_name']);

			$storage->updateYaml('contentFolder', $folderPath, $namePath . '.yaml', $metadata);
		}
		elseif($params['type'] == 'folder')
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('We cannot create a folder, only files.')
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(402);
		}

		$navigation->clearNavigation();
	    $draftNavigation 	= $navigation->getDraftNavigation($urlinfo, $langattr);
		$item				= $navigation->getItemWithKeyPath($draftNavigation, $folderKeyPath);

		$response->getBody()->write(json_encode([
			'navigation'	=> $draftNavigation,
			'item'			=> $item,
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}

	public function renameArticle(Request $request, Response $response, $args)
	{
		$params 			= $request->getParsedBody();
		$validate			= new Validation();
		$validInput 		= $validate->articleRename($params);
		if($validInput !== true)
		{
			$errors 		= $validate->returnFirstValidationErrors($validInput);
			$response->getBody()->write(json_encode([
				'message' 	=> reset($errors),
				'errors' 	=> $errors
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$navigation 		= new Navigation();
		$urlinfo 			= $this->c->get('urlinfo');
		$item 				= $this->getItem($navigation, $params['url'], $urlinfo);
		if(!$item)
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('page not found'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		# if user is not allowed to perform this action (e.g. not admin)
		if(!$this->userroleIsAllowed($request->getAttribute('c_userrole'), 'content', 'update'))
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

		# check if name exists
		$parentUrl 			= str_replace($item->slug, '', $item->urlRelWoF);
		if($parentUrl == '/')
		{
			$parentItem = new \stdClass;
			$parentItem->folderContent = $navigation->getDraftNavigation($urlinfo, $this->settings['langattr']);
		}
		else
		{
			$parentItem = $this->getItem($navigation, $parentUrl, $urlinfo);
		}

		foreach($parentItem->folderContent as $sibling)
		{
			if($sibling->slug == $params['slug'])
			{
				$response->getBody()->write(json_encode([
					'message' => Translations::translate('There is already a page with that slug'),
				]));

				return $response->withHeader('Content-Type', 'application/json')->withStatus(402);
			}
		}

		$navigation->renameItem($item, $params['slug']);
		$navigation->clearNavigation();
		$draftNavigation = $navigation->getDraftNavigation($urlinfo, $this->settings['langattr']);

		$sitemap = new Sitemap();
		$sitemap->updateSitemap($draftNavigation, $urlinfo);

		# create the new url for redirects
		$newUrlRel =  str_replace($item->slug, $params['slug'], $item->urlRelWoF);
		$url = $urlinfo['baseurl'] . '/tm/content/' . $this->settings['editor'] . $newUrlRel;

		$data = [
			'item' => $item,
			'newUrl' => $newUrlRel
		];
		$this->c->get('dispatcher')->dispatch(new OnPageRenamed($data), 'onPageRenamed');

		$response->getBody()->write(json_encode([
			'navigation'	=> $draftNavigation,
			'message'		=> '',
			'url' 			=> $url
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}

	public function sortArticle(Request $request, Response $response, $args)
	{ 
		$params 			= $request->getParsedBody();
		$validate			= new Validation();
		$validInput 		= $validate->navigationSort($params);
		if($validInput !== true)
		{
			$errors 		= $validate->returnFirstValidationErrors($validInput);
			$response->getBody()->write(json_encode([
				'message' 	=> reset($errors),
				'errors' 	=> $errors
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		# set variables
		$urlinfo 			= $this->c->get('urlinfo');
		$langattr 			= $this->settings['langattr'] ?? 'en';

		$itemKeyPath 		= explode('.', $params['item_id']);
		$parentKeyFrom		= explode('.', $params['parent_id_from']);
		$parentKeyTo		= explode('.', $params['parent_id_to']);

		# get navigation
	    $navigation 		= new Navigation();
	    $draftNavigation 	= $navigation->getDraftNavigation($urlinfo, $langattr);
		$item 				= $navigation->getItemWithKeyPath($draftNavigation, $itemKeyPath);
		if(!$item)
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('page not found'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		# if user is not allowed to perform this action (e.g. not admin)
		if(!$this->userroleIsAllowed($request->getAttribute('c_userrole'), 'content', 'update'))
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

	    $extendedNavigation	= $navigation->getExtendedNavigation($urlinfo, $langattr);
	    $pageinfo 			= $extendedNavigation[$params['url']] ?? false;
	    if(!$pageinfo)
	    {
			$response->getBody()->write(json_encode([
				'message' => 'page not found',
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
	    }

		# if an item is moved to the first level
		if($params['parent_id_to'] == '')
		{
			# create empty and default values so that the logic below still works
			$newFolder 			=  new \stdClass();
			$newFolder->path	= '';
			$folderContent		= $draftNavigation;
		}
		else
		{
			# get the target folder from navigation
			$newFolder 			= $navigation->getItemWithKeyPath($draftNavigation, $parentKeyTo);
			
			# get the content of the target folder
			$folderContent		= $newFolder->folderContent;
		}
		
		# if the item has been moved within the same folder
		if($params['parent_id_from'] == $params['parent_id_to'])
		{
			# get key of item
			$itemKey = end($itemKeyPath);
			reset($itemKeyPath);
			
			# delete item from folderContent
			unset($folderContent[$itemKey]);
		}
		else
		{
			# an active file has been moved to another folder, so send new url with response
			if($params['active'] == 'active')
			{
				$url = $urlinfo['baseurl'] . '/tm/content/' . $this->settings['editor'] . $newFolder->urlRelWoF . '/' . $item->slug;
			}
		}

		# add item to newFolder
		array_splice($folderContent, $params['index_new'], 0, array($item));
		
		# move and rename files in the new folder
		$index 			= 0;
		$writeError 	= false;
		$storage 		= new StorageWrapper('\Typemill\Models\Storage');
		foreach($folderContent as $folderItem)
		{
			if(!$storage->moveContentFile($folderItem, $newFolder->path, $index))
			{
				$writeError = true;
			}
			$index++;
		}
		if($writeError)
		{
			$response->getBody()->write(json_encode([
				'message' 		=> Translations::translate('Something went wrong. Please refresh the page and check, if all folders and files are writable.'),
				'navigation' 	=> $draftNavigation,
				'url'			=> false
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
		}

		# refresh navigation and item
	    $navigation->clearNavigation();
	    $draftNavigation = $navigation->getDraftNavigation($urlinfo, $langattr);

		$sitemap = new Sitemap();
		$sitemap->updateSitemap($draftNavigation, $urlinfo);

		$this->c->get('dispatcher')->dispatch(new OnPageSorted($params), 'onPageSorted');

		$response->getBody()->write(json_encode([
			'navigation'	=> $draftNavigation,
			'message'		=> '',
			'url' 			=> false
		]));

		return $response->withHeader('Content-Type', 'application/json');	    
	}

	public function deleteArticle(Request $request, Response $response, $args)
	{
		$params 			= $request->getParsedBody();
		$validate			= new Validation();
		$validInput 		= $validate->articlePublish($params);
		if($validInput !== true)
		{
			$errors 		= $validate->returnFirstValidationErrors($validInput);
			$response->getBody()->write(json_encode([
				'message' 	=> reset($errors),
				'errors' 	=> $errors
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$navigation 		= new Navigation();
		$urlinfo 			= $this->c->get('urlinfo');
		$item 				= $this->getItem($navigation, $params['url'], $urlinfo);
		if(!$item)
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('page not found'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		# if user is not allowed to perform this action (e.g. not admin)
		if(!$this->userroleIsAllowed($request->getAttribute('c_userrole'), 'content', 'delete'))
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

		$content = new Content($urlinfo['baseurl'], $this->settings, $this->c->get('dispatcher'));

		# check if it is a folder and if the folder has published pages.
		if($item->elementType == 'folder')
		{
			# check if folder has published pages
			if($content->hasPublishedItems($item))
			{
				$result = Translations::translate('The folder contains published pages. Please unpublish or delete them first.');
			}
			else
			{
				$result = $content->deleteFolder($item);
			}
		}
		else
		{
			$result = $content->deletePage($item);
		}

		if($result !== true)
		{
			$response->getBody()->write(json_encode([
				'message' => $result,
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
		}

		# refresh navigation
	    $navigation->clearNavigation();
	    $draftNavigation = $navigation->getDraftNavigation($urlinfo, $this->settings['langattr']);

		$sitemap = new Sitemap();
		$sitemap->updateSitemap($draftNavigation, $urlinfo);

		# check if it is a subfile or subfolder and set the redirect-url to the parent item
		$url = $urlinfo['baseurl'] . '/tm/content/' . $this->settings['editor'];
		if(count($item->keyPathArray) > 1)
		{
			array_pop($item->keyPathArray);

			$parentItem = $navigation->getItemWithKeyPath($draftNavigation, $item->keyPathArray);
	
			if($parentItem)
			{
				# an active file has been moved to another folder
				$url .= $parentItem->urlRelWoF;
			}
		}
		
		# dispatch event
		$this->c->get('dispatcher')->dispatch(new OnPageDeleted($item), 'onPageDeleted');

		$response->getBody()->write(json_encode([
			'url' => $url
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}
}