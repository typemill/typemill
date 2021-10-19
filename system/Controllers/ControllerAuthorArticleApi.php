<?php

namespace Typemill\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Typemill\Models\Folder;
use Typemill\Models\WriteYaml;
use Typemill\Models\WriteMeta;
use Typemill\Extensions\ParsedownExtension;
use Typemill\Events\OnPagePublished;
use Typemill\Events\OnPageUnpublished;
use Typemill\Events\OnPageDeleted;
use Typemill\Events\OnPageSorted;

class ControllerAuthorArticleApi extends ControllerAuthor
{
	public function publishArticle(Request $request, Response $response, $args)
	{
		# get params from call 
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');

		# minimum permission is that user can publish his own content
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'mycontent', 'publish'))
		{
			return $response->withJson(array('data' => false, 'errors' => ['message' => 'You are not allowed to publish content.']), 403);
		}

		# validate input only if raw mode
		if($this->params['raw'])
		{
			if(!$this->validateEditorInput()){ return $response->withJson($this->errors,422); }
		}

		# set structure
		if(!$this->setStructureDraft()){ return $response->withJson($this->errors, 404); }

		# set information for homepage
		$this->setHomepage($args = false);

		# set item 
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }

		# if user has no right to update content from others (eg admin or editor)
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'content', 'publish'))
		{
			# check ownership. This code should nearly never run, because there is no button/interface to trigger it.
			if(!$this->checkContentOwnership())
			{
				return $response->withJson(array('data' => false, 'errors' => ['message' => 'You are not allowed to publish content.']), 403);
			}
		}
		
		# set the status for published and drafted
		$this->setPublishStatus();
		
		# set path
		$this->setItemPath($this->item->fileType);
		
		# if raw mode, use the content from request
		if($this->params['raw'])
		{
			$this->content = '# ' . $this->params['title'] . "\r\n\r\n" . $this->params['content'];	
		}
		else
		{
			# read content from file
			if(!$this->setContent()){ return $response->withJson(array('data' => false, 'errors' => $this->errors), 404); }
			
			# If it is a draft, then create clean markdown content
			if(is_array($this->content))
			{
				# initialize parsedown extension
				$parsedown = new ParsedownExtension($this->uri->getBaseUrl());

				# turn markdown into an array of markdown-blocks
				$this->content = $parsedown->arrayBlocksToMarkdown($this->content);				
			}
		}
		
		# set path for the file (or folder)
		$this->setItemPath('md');
		
		# update the file
		if($this->writeCache->writeFile($this->settings['contentFolder'], $this->path, $this->content))
		{
			# update the file
			$delete = $this->deleteContentFiles(['txt']);
			
			# update the internal structure
			$this->setFreshStructureDraft();
			
			# update the public structure
			$this->setFreshStructureLive();

			# update the navigation
			$this->setFreshNavigation();

			# update the sitemap
			$this->updateSitemap();

			# complete the page meta if title or description not set
			$writeMeta = new WriteMeta();
			$meta = $writeMeta->completePageMeta($this->content, $this->settings, $this->item);

			# dispatch event
			$page = ['content' => $this->content, 'meta' => $meta, 'item' => $this->item];
			$page = $this->c->dispatcher->dispatch('onPagePublished', new OnPagePublished($page))->getData();

			return $response->withJson(['success' => true, 'meta' => $page['meta']], 200);
		}
		else
		{
			return $response->withJson(['errors' => ['message' => 'Could not write to file. Please check if the file is writable']], 404);
		}
	}

	public function unpublishArticle(Request $request, Response $response, $args)
	{
		# get params from call 
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');

		# minimum permission is that user can unpublish his own content
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'mycontent', 'unpublish'))
		{
			return $response->withJson(array('data' => false, 'errors' => ['message' => 'You are not allowed to unpublish content.']), 403);
		}

		# set structure
		if(!$this->setStructureDraft()){ return $response->withJson($this->errors, 404); }

		# set information for homepage
		$this->setHomepage($args = false);

		# set item 
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }

		# if user has no right to update content from others (eg admin or editor)
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'content', 'unpublish'))
		{
			# check ownership. This code should nearly never run, because there is no button/interface to trigger it.
			if(!$this->checkContentOwnership())
			{
				return $response->withJson(array('data' => false, 'errors' => ['message' => 'You are not allowed to unpublish content.']), 403);
			}
		}

		# set the status for published and drafted
		$this->setPublishStatus();

		# check if draft exists, if not, create one.
		if(!$this->item->drafted)
		{
			# set path for the live file (or folder)
			$this->setItemPath('md');
			
			# set content of markdown-file
			if(!$this->setContent()){ return $response->withJson($this->errors, 404); }
			
			# initialize parsedown extension
			$parsedown = new ParsedownExtension($this->uri->getBaseUrl());

			# turn markdown into an array of markdown-blocks
			$contentArray = $parsedown->markdownToArrayBlocks($this->content);
			
			# encode the content into json
			$contentJson = json_encode($contentArray);

			# set path for the draft file (or folder)
			$this->setItemPath('txt');
			
			# update the file
			if(!$this->writeCache->writeFile($this->settings['contentFolder'], $this->path, $contentJson))
			{
				return $response->withJson(['errors' => ['message' => 'Could not create a draft of the page. Please check if the folder is writable']], 404);
			}
		}
		
		# check if it is a folder and if the folder has published pages.
		$message = false;
		if($this->item->elementType == 'folder')
		{
			foreach($this->item->folderContent as $folderContent)
			{
				if($folderContent->status == 'published')
				{
					$message = 'There are published pages within this folder. The pages are not visible on your website anymore.';
				}
			}
		}

		# delete the live file
		$delete = $this->deleteContentFiles(['md']);
		
		if($delete)
		{
			# update the internal structure
			$this->setFreshStructureDraft();
			
			# update the live structure
			$this->setFreshStructureLive();

			# update the navigation
			$this->setFreshNavigation();

			# update the sitemap
			$this->updateSitemap();

			# dispatch event
			$this->c->dispatcher->dispatch('onPageUnpublished', new OnPageUnpublished($this->item));
			
			return $response->withJson(['success' => ['message' => $message]], 200);
		}
		else
		{
			return $response->withJson(['errors' => ['message' => "Could not delete some files. Please check if the files exists and are writable"]], 404);
		}
	}

	public function discardArticleChanges(Request $request, Response $response, $args)
	{
		# get params from call 
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');
		
		# minimum permission is that user is allowed to update his own content
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'mycontent', 'update'))
		{
			return $response->withJson(array('data' => false, 'errors' => ['message' => 'You are not allowed to publish content.']), 403);
		}

		# set structure
		if(!$this->setStructureDraft()){ return $response->withJson($this->errors, 404); }

		# set information for homepage
		$this->setHomepage($args = false);

		# set item
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }
		
		# if user has no right to update content from others (eg admin or editor)
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'content', 'update'))
		{
			# check ownership. This code should nearly never run, because there is no button/interface to trigger it.
			if(!$this->checkContentOwnership())
			{
				return $response->withJson(array('data' => false, 'errors' => ['message' => 'You are not allowed to update content.']), 403);
			}
		}

		# set redirect url to edit page
		$url = $this->uri->getBaseUrl() . '/tm/content/' . $this->settings['editor'];
		if(isset($this->item->urlRelWoF) && $this->item->urlRelWoF != '/' )
		{
			$url = $url . $this->item->urlRelWoF;
		}

		# remove the unpublished changes
		$delete = $this->deleteContentFiles(['txt']);
		
		if($delete)
		{
			# update the backend structure
			$this->setFreshStructureDraft();
			
			return $response->withJson(['data' => $this->structureDraft, 'errors' => false, 'url' => $url], 200);
		}
		else
		{
			return $response->withJson(['data' => $this->structureDraft, 'errors' => $this->errors], 404); 
		}
	}

	public function deleteArticle(Request $request, Response $response, $args)
	{
		# get params from call 
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');

		# minimum permission is that user is allowed to delete his own content
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'mycontent', 'delete'))
		{
			return $response->withJson(array('data' => false, 'errors' => ['message' => 'You are not allowed to delete content.']), 403);
		}

		# set url to base path initially
		$url = $this->uri->getBaseUrl() . '/tm/content/' . $this->settings['editor'];
		
		# set structure
		if(!$this->setStructureDraft()){ return $response->withJson($this->errors, 404); }

		# set information for homepage
		$this->setHomepage($args = false);

		# set item
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }

		# if user has no right to delete content from others (eg admin or editor)
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'content', 'delete'))
		{
			# check ownership. This code should nearly never run, because there is no button/interface to trigger it.
			if(!$this->checkContentOwnership())
			{
				return $response->withJson(array('data' => false, 'errors' => ['message' => 'You are not allowed to delete content.']), 403);
			}
		}
		
		if($this->item->elementType == 'file')
		{
			$delete = $this->deleteContentFiles(['md','txt', 'yaml']);
		}
		elseif($this->item->elementType == 'folder')
		{
			$delete = $this->deleteContentFolder();
		}

		if($delete)
		{
			# check if it is a subfile or subfolder and set the redirect-url to the parent item
			if(count($this->item->keyPathArray) > 1)
			{
				# get the parent item
				$parentItem = Folder::getParentItem($this->structureDraft, $this->item->keyPathArray);

				if($parentItem)
				{
					# an active file has been moved to another folder
					$url .= $parentItem->urlRelWoF;
				}
			}
			
			# update the live structure
			$this->setFreshStructureDraft();

			# update the backend structure
			$this->setFreshStructureLive();

			# check if page is in extended structure and delete it
			$this->deleteFromExtended();

			# update the navigation
			$this->setFreshNavigation();

			# update the sitemap
			$this->updateSitemap();

			# dispatch event
			$this->c->dispatcher->dispatch('onPageDeleted', new OnPageDeleted($this->item));
			
			return $response->withJson(array('data' => $this->structureDraft, 'errors' => false, 'url' => $url), 200);
		}
		else
		{
			return $response->withJson(array('data' => $this->structure, 'errors' => $this->errors), 422); 
		}
	}
	
	public function updateArticle(Request $request, Response $response, $args)
	{
		# get params from call 
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');

		# minimum permission is that user is allowed to update his own content
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'mycontent', 'update'))
		{
			return $response->withJson(array('data' => false, 'errors' => ['message' => 'You are not allowed to update content.']), 403);
		}
		
		# validate input 
		if(!$this->validateEditorInput()){ return $response->withJson($this->errors,422); }
		
		# set structure
		if(!$this->setStructureDraft()){ return $response->withJson($this->errors, 404); }

		# set information for homepage
		$this->setHomepage($args = false);

		# set item 
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }

		# if user has no right to delete content from others (eg admin or editor)
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'content', 'update'))
		{
			# check ownership. This code should nearly never run, because there is no button/interface to trigger it.
			if(!$this->checkContentOwnership())
			{
				return $response->withJson(array('data' => false, 'errors' => ['message' => 'You are not allowed to update content.']), 403);
			}
		}

		# set draft path for the file (or folder)
		$this->setItemPath('txt');

		# merge title with content for complete markdown document
		$updatedContent = '# ' . $this->params['title'] . "\r\n\r\n" . $this->params['content'];

		# initialize parsedown extension
		$parsedown 		= new ParsedownExtension($this->uri->getBaseUrl());
		
		# turn markdown into an array of markdown-blocks
		$contentArray = $parsedown->markdownToArrayBlocks($updatedContent);
		
		# encode the content into json
		$contentJson = json_encode($contentArray);		
				
		# update the file
		if($this->writeCache->writeFile($this->settings['contentFolder'], $this->path, $contentJson))
		{
			# update the internal structure to show that this page has drafted changes now
			$this->setFreshStructureDraft();
			
			return $response->withJson(['success'], 200);
		}
		else
		{
			return $response->withJson(['errors' => ['message' => 'Could not write to file. Please check if the file is writable']], 404);
		}
	}

	public function renameArticle(Request $request, Response $response, $args)
	{
		# get params from call
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');
		$dir 			= $this->settings['basePath'] . 'cache';
		$pathToContent	= $this->settings['rootPath'] . $this->settings['contentFolder'];

		# minimum permission is that user is allowed to update his own content
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'mycontent', 'update'))
		{
			return $response->withJson(array('data' => false, 'errors' => 'You are not allowed to update content.'), 403);
		}

		# validate input 1: check if valid characters
		if(!preg_match("/^[a-z0-9\-]*$/", $this->params['slug']))
		{
			return $response->withJson(['errors' => ['message' => 'the slug contains invalid characters.' ]],422);
		}
		
		# set structure
		if(!$this->setStructureDraft()){ return $response->withJson($this->errors, 404); }

		# set information for homepage
		$this->setHomepage($args = false);

		# set item 
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }

		# validate input part 2: check if slug has changed or is empty
		if($this->params['slug'] == $this->item->slug OR $this->params['slug'] == '')
		{
			return $response->withJson(['errors' => ['message' => 'the slug is empty or the same as the old one.' ]],422);
		}

		# if user has no right to update content from others (eg admin or editor)
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'content', 'update'))
		{
			# check ownership. This code should nearly never run, because there is no button/interface to trigger it.
			if(!$this->checkContentOwnership())
			{
				return $response->withJson(array('data' => $this->structure, 'errors'  => 'You are not allowed to move that content.'), 403);
			}
		}

		# get the folder where file lives in
		$pathWithoutFile = str_replace($this->item->originalName, '', $this->item->path);
		
		# create the new file name with the updated slug
		$newPathWithoutType = $pathWithoutFile . $this->item->order . '-' . $this->params['slug'];

		# validate input part 3: check if name is taken already
		$parentKey = $this->item->keyPathArray;
		array_pop($parentKey);
		if(!empty($parentKey))
		{
			$parentFolder = Folder::getItemWithKeyPath($this->structureDraft, $parentKey);

			foreach($parentFolder->folderContent as $item)
			{
				if($item->slug == $this->params['slug'])
				{
					return $response->withJson(['errors' => ['message' => 'There is already a page with that slug' ]],422);
				}
			}
		}
		else
		{
			foreach($this->structureDraft as $baseItem)
			{
				if($baseItem->slug == $this->params['slug'])
				{
					return $response->withJson(['errors' => ['message' => 'There is already a page with that slug' ]],422);
				}				
			}
		}

		# rename the file
		if($this->item->elementType == 'file')
		{
			$this->writeCache->renamePost($this->item->pathWithoutType, $newPathWithoutType);
		}
		elseif($this->item->elementType == 'folder')
		{
			$this->writeCache->renameFile('content', $this->item->path, $newPathWithoutType);
		}

		# delete the cache
		$error 		= $this->writeCache->deleteCacheFiles($dir);
		if($error)
		{
			return $response->withJson(['errors' => $errors], 500);
		}

		# recreates the cache for structure, structure-extended, navigation, sitemap
		$this->setFreshStructureDraft();
		$this->setFreshStructureLive();
		$this->setFreshNavigation();
		$this->updateSitemap();

		$newUrlRel =  str_replace($this->item->slug, $this->params['slug'], $this->item->urlRelWoF);

		$url = $this->uri->getBaseUrl() . '/tm/content/' . $this->settings['editor'] . $newUrlRel;
		
		return $response->withJson(array('data' => false, 'errors' => false, 'url' => $url));
	}
	
	public function sortArticle(Request $request, Response $response, $args)
	{
		# get params from call
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');

		# minimum permission is that user is allowed to update his own content
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'mycontent', 'update'))
		{
			return $response->withJson(array('data' => false, 'errors' => 'You are not allowed to update content.'), 403);
		}
		
		# url is only needed, if an active page is moved to another folder, so user has to be redirected to the new url
		$url 			= false;
		
		# set structure
		if(!$this->setStructureDraft()){ return $response->withJson(array('data' => false, 'errors' => $this->errors, 'url' => $url), 404); }
		
		# validate input
		if(!$this->validateNavigationSort()){ return $response->withJson(array('data' => $this->structureDraft, 'errors' => 'Data not valid. Please refresh the page and try again.', 'url' => $url), 422); }
		
		# get the ids (key path) for item, old folder and new folder
		$itemKeyPath 	= explode('.', $this->params['item_id']);
		$parentKeyFrom	= explode('.', $this->params['parent_id_from']);
		$parentKeyTo	= explode('.', $this->params['parent_id_to']);
		
		# get the item from structure
		$item 			= Folder::getItemWithKeyPath($this->structureDraft, $itemKeyPath);

		if(!$item){ return $response->withJson(array('data' => $this->structureDraft, 'errors' => 'We could not find this page. Please refresh and try again.', 'url' => $url), 404); }
		
		# needed for acl check
		$this->item = $item;

		# if user has no right to update content from others (eg admin or editor)
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'content', 'update'))
		{
			# check ownership. This code should nearly never run, because there is no button/interface to trigger it.
			if(!$this->checkContentOwnership())
			{
				return $response->withJson(array('data' => $this->structureDraft, 'errors'  => 'You are not allowed to move that content.'), 403);
			}
		}

		# if an item is moved to the first level
		if($this->params['parent_id_to'] == 'navi')
		{
			# create empty and default values so that the logic below still works
			$newFolder 			=  new \stdClass();
			$newFolder->path	= '';
			$folderContent		= $this->structureDraft;
		}
		else
		{
			# get the target folder from structure
			$newFolder 		= Folder::getItemWithKeyPath($this->structureDraft, $parentKeyTo);
			
			# get the content of the target folder
			$folderContent	= $newFolder->folderContent;
		}
		
		# if the item has been moved within the same folder
		if($this->params['parent_id_from'] == $this->params['parent_id_to'])
		{
			# get key of item
			$itemKey = end($itemKeyPath);
			reset($itemKeyPath);
			
			# delete item from folderContent
			unset($folderContent[$itemKey]);
		}
		else
		{
			# rename links in extended file
			$this->renameExtended($item, $newFolder);

			# an active file has been moved to another folder, so send new url with response
			if($this->params['active'] == 'active')
			{
				$url = $this->uri->getBaseUrl() . '/tm/content/' . $this->settings['editor'] . $newFolder->urlRelWoF . '/' . $item->slug;
			}
		}
		
		# add item to newFolder
		array_splice($folderContent, $this->params['index_new'], 0, array($item));

		# initialize index
		$index = 0;
		
		# iterate through the whole content of the new folder to rename the files
		$writeError = false;
		foreach($folderContent as $folderItem)
		{
			if(!$this->writeCache->moveElement($folderItem, $newFolder->path, $index))
			{
				$writeError = true;
			}
			$index++;
		}
		if($writeError){ return $response->withJson(array('data' => $this->structureDraft, 'errors' => ['message' => 'Something went wrong. Please refresh the page and check, if all folders and files are writable.'], 'url' => $url), 404); }

		# update the structure for editor
		$this->setFreshStructureDraft();
		
		# get item for url and set it active again
		if(isset($this->params['url']))
		{
			$activeItem = Folder::getItemForUrl($this->structureDraft, $this->params['url'], $this->uri->getBaseUrl());
		}

		# update the structure for website
		$this->setFreshStructureLive();

		# update the navigation
		$this->setFreshNavigation();

		# update the sitemap
		$this->updateSitemap();
		
		# dispatch event
		$this->c->dispatcher->dispatch('onPageSorted', new OnPageSorted($this->params));

		return $response->withJson(array('data' => $this->structureDraft, 'errors' => false, 'url' => $url));
	}

	public function createPost(Request $request, Response $response, $args)
	{
		# get params from call
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');

		# minimum permission is that user is allowed to update his own content
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'mycontent', 'create'))
		{
			return $response->withJson(array('data' => false, 'errors' => ['message' => 'You are not allowed to create content.']), 403);
		}

		# url is only needed, if an active page is moved
		$url 			= false;
		
		# set structure
		if(!$this->setStructureDraft()){ return $response->withJson(array('data' => false, 'errors' => $this->errors, 'url' => $url), 404); }

		# validate input
		if(!$this->validateNaviItem()){ return $response->withJson(array('data' => $this->structureDraft, 'errors' => ['message' => 'Special Characters not allowed. Length between 1 and 60 chars.'], 'url' => $url), 422); }
		
		# get the ids (key path) for item, old folder and new folder
		$folderKeyPath 	= explode('.', $this->params['folder_id']);
		
		# get the item from structure
		$folder			= Folder::getItemWithKeyPath($this->structureDraft, $folderKeyPath);

		if(!$folder){ return $response->withJson(array('data' => $this->structureDraft, 'errors' => ['message' => 'We could not find this page. Please refresh and try again.'], 'url' => $url), 404); }
		
		$name 		= $this->params['item_name'];
		$slug 		= Folder::createSlug($this->params['item_name'], $this->settings);
		$namePath 	= date("YmdHi") . '-' . $slug;
		$folderPath	= 'content' . $folder->path;
		$content 	= json_encode(['# ' . $name, 'Content']);

		# initialise write object
		$writeYaml 	= new WriteYaml();
		
		# check, if name exists
		if($writeYaml->checkFile($folderPath, $namePath . '.txt') OR $writeYaml->checkFile($folderPath, $namePath . '.md'))
		{
			return $response->withJson(array('data' => $this->structureDraft, 'errors' => 'There is already a page with this name. Please choose another name.', 'url' => $url), 404);
		}
		
		if(!$writeYaml->writeFile($folderPath, $namePath . '.txt', $content))
		{
			return $response->withJson(array('data' => $this->structureDraft, 'errors' => 'We could not create the file. Please refresh the page and check, if all folders and files are writable.', 'url' => $url), 404);
		}

		# get extended structure
		$extended 	= $writeYaml->getYaml('cache', 'structure-extended.yaml');

		# create the url for the item
		$urlWoF 	= $folder->urlRelWoF . '/' . $slug;

		# add the navigation name to the item htmlspecialchars needed for french language
		$extended[$urlWoF] = ['hide' => false, 'navtitle' => $name];

		# store the extended structure
		$writeYaml->updateYaml('cache', 'structure-extended.yaml', $extended);

		# update the structure for editor
		$this->setFreshStructureDraft();

		$folder	= Folder::getItemWithKeyPath($this->structureYaml, $folderKeyPath);

		# activate this if you want to redirect after creating the page...
		# $url = $this->uri->getBaseUrl() . '/tm/content/' . $this->settings['editor'] . $folder->urlRelWoF . '/' . $slug;
		
		return $response->withJson(array('posts' => $folder, $this->structureDraft, 'errors' => false, 'url' => $url));
	}
	
	public function createArticle(Request $request, Response $response, $args)
	{
		# get params from call
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');

		# minimum permission is that user is allowed to update his own content
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'mycontent', 'create'))
		{
			return $response->withJson(array('data' => false, 'errors' => ['message' => 'You are not allowed to create content.']), 403);
		}

		# url is only needed, if an active page is moved
		$url 			= false;
		
		# set structure
		if(!$this->setStructureDraft()){ return $response->withJson(array('data' => false, 'errors' => $this->errors, 'url' => $url), 404); }
		
		# validate input
		if(!$this->validateNaviItem()){ return $response->withJson(array('data' => $this->structureDraft, 'errors' => 'Special Characters not allowed. Length between 1 and 60 chars.', 'url' => $url), 422); }
		
		# get the ids (key path) for item, old folder and new folder
		$folderKeyPath 	= explode('.', $this->params['folder_id']);
		
		# get the item from structure
		$folder			= Folder::getItemWithKeyPath($this->structureDraft, $folderKeyPath);

		if(!$folder){ return $response->withJson(array('data' => $this->structureDraft, 'errors' => 'We could not find this page. Please refresh and try again.', 'url' => $url), 404); }
		
		# Rename all files within the folder to make sure, that namings and orders are correct
		# get the content of the target folder
		$folderContent	= $folder->folderContent;
		
		$name 		= $this->params['item_name'];
		$slug 		= Folder::createSlug($this->params['item_name'], $this->settings);

		# initialize index
		$index = 0;
		
		# initialise write object
		$writeYaml = new WriteYaml();

		# iterate through the whole content of the new folder
		$writeError = false;
		
		foreach($folderContent as $folderItem)
		{
			# check, if the same name as new item, then return an error
			if($folderItem->slug == $slug)
			{
				return $response->withJson(array('data' => $this->structureDraft, 'errors' => 'There is already a page with this name. Please choose another name.', 'url' => $url), 404);
			}
			
			if(!$writeYaml->moveElement($folderItem, $folder->path, $index))
			{
				$writeError = true;
			}
			$index++;
		}

		if($writeError){ return $response->withJson(array('data' => $this->structureDraft, 'errors' => 'Something went wrong. Please refresh the page and check, if all folders and files are writable.', 'url' => $url), 404); }

		# add prefix number to the name
		$namePath 	= $index > 9 ? $index . '-' . $slug : '0' . $index . '-' . $slug;
		$folderPath	= 'content' . $folder->path;
		
		# create default content
		$content = json_encode(['# ' . $name, 'Content']);
		
		if($this->params['type'] == 'file')
		{
			if(!$writeYaml->writeFile($folderPath, $namePath . '.txt', $content))
			{
				return $response->withJson(array('data' => $this->structureDraft, 'errors' => 'We could not create the file. Please refresh the page and check, if all folders and files are writable.', 'url' => $url), 404);
			}
		}
		elseif($this->params['type'] == 'folder')
		{
			if(!$writeYaml->checkPath($folderPath . DIRECTORY_SEPARATOR . $namePath))
			{
				return $response->withJson(array('data' => $this->structureDraft, 'errors' => 'We could not create the folder. Please refresh the page and check, if all folders and files are writable.', 'url' => $url), 404);
			}
			$this->writeCache->writeFile($folderPath . DIRECTORY_SEPARATOR . $namePath, 'index.txt', $content);

			# always redirect to a folder
			$url = $this->uri->getBaseUrl() . '/tm/content/' . $this->settings['editor'] . $folder->urlRelWoF . '/' . $slug;

		}
		
		# get extended structure
		$extended = $writeYaml->getYaml('cache', 'structure-extended.yaml');

		# create the url for the item
		$urlWoF = $folder->urlRelWoF . '/' . $slug;

		# add the navigation name to the item htmlspecialchars needed for french language
		$extended[$urlWoF] = ['hide' => false, 'navtitle' => $name];

		# store the extended structure
		$writeYaml->updateYaml('cache', 'structure-extended.yaml', $extended);

		# update the structure for editor
		$this->setFreshStructureDraft();

		# get item for url and set it active again
		if(isset($this->params['url']))
		{
			$activeItem = Folder::getItemForUrl($this->structureDraft, $this->params['url'], $this->uri->getBaseUrl());
		}

		# activate this if you want to redirect after creating the page...
		# $url = $this->uri->getBaseUrl() . '/tm/content/' . $this->settings['editor'] . $folder->urlRelWoF . '/' . $slug;
		
		return $response->withJson(array('data' => $this->structureDraft, 'errors' => false, 'url' => $url));
	}

	public function createBaseItem(Request $request, Response $response, $args)
	{
		# get params from call
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');

		# minimum permission is that user is allowed to update his own content
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'mycontent', 'create'))
		{
			return $response->withJson(array('data' => false, 'errors' => 'You are not allowed to create content.'), 403);
		}
		
		# url is only needed, if an active page is moved
		$url 			= false;
		
		# set structure
		if(!$this->setStructureDraft()){ return $response->withJson(array('data' => false, 'errors' => $this->errors, 'url' => $url), 404); }
		
		# validate input
		if(!$this->validateBaseNaviItem()){ return $response->withJson(array('data' => $this->structureDraft, 'errors' => 'Special Characters not allowed. Length between 1 and 20 chars.', 'url' => $url), 422); }

		$name 		= $this->params['item_name'];
		$slug 		= Folder::createSlug($this->params['item_name'], $this->settings);

		# initialize index
		$index = 0;
		
		# initialise write object
		$writeYaml = new WriteYaml();

		# iterate through the whole content of the new folder
		$writeError = false;

		foreach($this->structureDraft as $item)
		{
			# check, if the same name as new item, then return an error
			if($item->slug == $slug)
			{
				return $response->withJson(array('data' => $this->structureDraft, 'errors' => 'There is already a page with this name. Please choose another name.', 'url' => $url), 422);
			}
			
			if(!$writeYaml->moveElement($item, '', $index))
			{
				$writeError = true;
			}
			$index++;
		}

		if($writeError){ return $response->withJson(array('data' => $this->structureDraft, 'errors' => 'Something went wrong. Please refresh the page and check, if all folders and files are writable.', 'url' => $url), 422); }

		# add prefix number to the name
		$namePath 	= $index > 9 ? $index . '-' . $slug : '0' . $index . '-' . $slug;
		$folderPath	= 'content';
		
		# create default content
		$content = json_encode(['# ' . $name, 'Content']);
		
		if($this->params['type'] == 'file')
		{
			if(!$writeYaml->writeFile($folderPath, $namePath . '.txt', $content))
			{
				return $response->withJson(array('data' => $this->structureDraft, 'errors' => 'We could not create the file. Please refresh the page and check, if all folders and files are writable.', 'url' => $url), 422);
			}
		}
		elseif($this->params['type'] == 'folder')
		{
			if(!$this->writeCache->checkPath($folderPath . DIRECTORY_SEPARATOR . $namePath))
			{
				return $response->withJson(array('data' => $this->structureDraft, 'errors' => 'We could not create the folder. Please refresh the page and check, if all folders and files are writable.', 'url' => $url), 422);
			}
			$writeYaml->writeFile($folderPath . DIRECTORY_SEPARATOR . $namePath, 'index.txt', $content);

			# activate this if you want to redirect after creating the page...
			$url = $this->uri->getBaseUrl() . '/tm/content/' . $this->settings['editor'] . '/' . $slug;			
		}


		# get extended structure
		$extended = $writeYaml->getYaml('cache', 'structure-extended.yaml');

		# create the url for the item
		$urlWoF = '/' . $slug;

		# add the navigation name to the item htmlspecialchars needed for frensh language
		$extended[$urlWoF] = ['hide' => false, 'navtitle' => $name];

		# store the extended structure
		$writeYaml->updateYaml('cache', 'structure-extended.yaml', $extended);

		# update the structure for editor
		$this->setFreshStructureDraft();

		# get item for url and set it active again
		if(isset($this->params['url']))
		{
			$activeItem = Folder::getItemForUrl($this->structureDraft, $this->params['url'], $this->uri->getBaseUrl());
		}

		return $response->withJson(array('data' => $this->structureDraft, 'errors' => false, 'url' => $url));
	}

	# get the backend navigation
	public function getNavigation(Request $request, Response $response, $args)
	{
		# get params from call
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');

		# set structure
		if(!$this->setStructureDraft()){ return $response->withJson(array('data' => false, 'errors' => $this->errors, 'url' => $url), 404); }

		# set information for homepage
		$this->setHomepage($args = false);

		# get item for url and set it active again
		if(isset($this->params['url']))
		{
			$activeItem = Folder::getItemForUrl($this->structureDraft, $this->params['url'], $this->uri->getBaseUrl());
		}

		return $response->withJson(array('data' => $this->structureDraft, 'homepage' => $this->homepage, 'errors' => false));
	}

	public function getArticleMarkdown(Request $request, Response $response, $args)
	{
		/* get params from call */
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');

		# minimum permission is that user is allowed to update his own content. This will completely disable the block-editor
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'mycontent', 'update'))
		{
			return $response->withJson(array('data' => false, 'errors' => 'You are not allowed to edit content.'), 403);
		}

		# set structure
		if(!$this->setStructureDraft()){ return $response->withJson(array('data' => false, 'errors' => $this->errors), 404); }

		# set information for homepage
		$this->setHomepage($args = false);
		
		/* set item */
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }

		# if user has no right to delete content from others (eg admin or editor)
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'content', 'update'))
		{
			# check ownership. This code should nearly never run, because there is no button/interface to trigger it.
			if(!$this->checkContentOwnership())
			{
				return $response->withJson(array('data' => false, 'errors' => 'You are not allowed to delete content.'), 403);
			}
		}
		
		# set the status for published and drafted
		$this->setPublishStatus();

		# set path
		$this->setItemPath($this->item->fileType);
		
		# read content from file
		if(!$this->setContent()){ return $response->withJson(array('data' => false, 'errors' => $this->errors), 404); }

		$content = $this->content;

		if($content == '')
		{
			$content = [];
		}
		
		# if content is not an array, then transform it
		if(!is_array($content))
		{
			# initialize parsedown extension
			$parsedown = new ParsedownExtension($this->uri->getBaseUrl());

			# turn markdown into an array of markdown-blocks
			$content = $parsedown->markdownToArrayBlocks($content);
		}
		
		# delete markdown from title
		if(isset($content[0]))
		{
			$content[0] = trim($content[0], "# ");
		}
		
		return $response->withJson(array('data' => $content, 'errors' => false));
	}
	
	public function getArticleHtml(Request $request, Response $response, $args)
	{
		/* get params from call */
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');
		
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'mycontent', 'update'))
		{
			return $response->withJson(array('data' => false, 'errors' => 'You are not allowed to edit content.'), 403);
		}

		# set structure
		if(!$this->setStructureDraft()){ return $response->withJson(array('data' => false, 'errors' => $this->errors), 404); }

		# set information for homepage
		$this->setHomepage($args = false);
		
		/* set item */
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }

		# if user has no right to delete content from others (eg admin or editor)
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'content', 'update'))
		{
			# check ownership. This code should nearly never run, because there is no button/interface to trigger it.
			if(!$this->checkContentOwnership())
			{
				return $response->withJson(array('data' => false, 'errors' => 'You are not allowed to delete content.'), 403);
			}
		}

		# set the status for published and drafted
		$this->setPublishStatus();

		# set path
		$this->setItemPath($this->item->fileType);
		
		# read content from file
		if(!$this->setContent()){ return $response->withJson(array('data' => false, 'errors' => $this->errors), 404); }

		$content = $this->content;

		if($content == '')
		{
			$content = [];
		}
		
		# initialize parsedown extension
		$parsedown = new ParsedownExtension($this->uri->getBaseUrl(), $settings = false, $this->c->dispatcher);

		# fix footnotes in parsedown, might break with complicated footnotes
		$parsedown->setVisualMode();

		# flag for TOC
		$toc = false;

		$tocMarkup = false;

		# if content is not an array, then transform it
		if(!is_array($content))
		{
			# turn markdown into an array of markdown-blocks
			$content = $parsedown->markdownToArrayBlocks($content);

			# build toc here to avoid duplicated toc for live content
			$tocMarkup = $parsedown->buildTOC($parsedown->headlines);
		}
				
		# needed for ToC links
		$relurl = '/tm/content/' . $this->settings['editor'] . '/' . $this->item->urlRel;
		
		# loop through mardkown-array and create html-blocks
		foreach($content as $key => $block)
		{
			# parse markdown-file to content-array
			$contentArray 	= $parsedown->text($block);

			if($block == '[TOC]')
			{
				$toc = $key;
			}

			# parse markdown-content-array to content-string
			$content[$key]	= ['id' => $key, 'html' => $parsedown->markup($contentArray)];
		}

		if($toc)
		{
			if(!$tocMarkup)
			{
				$tocMarkup = $parsedown->buildTOC($parsedown->headlines);
			}

			$content[$toc] = ['id' => $toc, 'html' => $tocMarkup];
		}

		return $response->withJson(array('data' => $content, 'errors' => false));
	}
}