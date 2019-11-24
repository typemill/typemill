<?php

namespace Typemill\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Typemill\Models\Folder;
use Typemill\Models\Write;
use Typemill\Models\ProcessImage;
use Typemill\Extensions\ParsedownExtension;
use Typemill\Events\OnPagePublished;
use Typemill\Events\OnPageUnpublished;
use Typemill\Events\OnPageDeleted;
use Typemill\Events\OnPageSorted;

class ContentApiController extends ContentController
{
	public function publishArticle(Request $request, Response $response, $args)
	{
		# get params from call 
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();

		# validate input only if raw mode
		if($this->params['raw'])
		{
			if(!$this->validateEditorInput()){ return $response->withJson($this->errors,422); }
		}

		# set structure
		if(!$this->setStructure($draft = true)){ return $response->withJson($this->errors, 404); }

		# set item 
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }
		
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
				$parsedown = new ParsedownExtension();

				# turn markdown into an array of markdown-blocks
				$this->content = $parsedown->arrayBlocksToMarkdown($this->content);				
			}
		}
		
		# set path for the file (or folder)
		$this->setItemPath('md');
		
		# update the file
		if($this->write->writeFile($this->settings['contentFolder'], $this->path, $this->content))
		{
			# update the file
			$delete = $this->deleteContentFiles(['txt']);
			
			# update the internal structure
			$this->setStructure($draft = true, $cache = false);
			
			# update the public structure
			$this->setStructure($draft = false, $cache = false);

			# dispatch event
			$this->c->dispatcher->dispatch('onPagePublished', new OnPagePublished($this->item));

			return $response->withJson(['success'], 200);
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
		$this->uri 		= $request->getUri();

		# set structure
		if(!$this->setStructure($draft = true)){ return $response->withJson($this->errors, 404); }

		# set item 
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }

		# set the status for published and drafted
		$this->setPublishStatus();

		# check if draft exists, if not, create one.
		if(!$this->item->drafted)
		{
			# set path for the file (or folder)
			$this->setItemPath('md');
			
			# set content of markdown-file
			if(!$this->setContent()){ return $response->withJson($this->errors, 404); }
			
			# initialize parsedown extension
			$parsedown = new ParsedownExtension();

			# turn markdown into an array of markdown-blocks
			$contentArray = $parsedown->markdownToArrayBlocks($this->content);
			
			# encode the content into json
			$contentJson = json_encode($contentArray);

			# set path for the file (or folder)
			$this->setItemPath('txt');
			
			/* update the file */
			if(!$this->write->writeFile($this->settings['contentFolder'], $this->path, $contentJson))
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

		# update the file
		$delete = $this->deleteContentFiles(['md']);
		
		if($delete)
		{
			# update the internal structure
			$this->setStructure($draft = true, $cache = false);
			
			# update the live structure
			$this->setStructure($draft = false, $cache = false);

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
		$this->uri 		= $request->getUri();
		
		# set structure
		if(!$this->setStructure($draft = true)){ return $response->withJson($this->errors, 404); }

		# set item
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }
		
		# remove the unpublished changes
		$delete = $this->deleteContentFiles(['txt']);

		# set redirect url to edit page

		$url = $this->uri->getBaseUrl() . '/tm/content/' . $this->settings['editor'];
		if(isset($this->item->urlRelWoF))
		{
			$url = $url . $this->item->urlRelWoF;
		}

		# remove the unpublished changes
		$delete = $this->deleteContentFiles(['txt']);
		
		if($delete)
		{
			# update the backend structure
			$this->setStructure($draft = true, $cache = false);
			
			return $response->withJson(['data' => $this->structure, 'errors' => false, 'url' => $url], 200);
		}
		else
		{
			return $response->withJson(['data' => $this->structure, 'errors' => $this->errors], 404); 
		}
	}

	public function deleteArticle(Request $request, Response $response, $args)
	{
		# get params from call 
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();

		# set url to base path initially
		$url = $this->uri->getBaseUrl() . '/tm/content/' . $this->settings['editor'];
		
		# set structure
		if(!$this->setStructure($draft = true)){ return $response->withJson($this->errors, 404); }

		# set item
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }
		
		if($this->item->elementType == 'file')
		{
			$delete = $this->deleteContentFiles(['md','txt']);
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
				$parentItem = Folder::getParentItem($this->structure, $this->item->keyPathArray);

				if($parentItem)
				{
					# an active file has been moved to another folder
					$url .= $parentItem->urlRelWoF;
				}
			}
			
			# update the live structure
			$this->setStructure($draft = false, $cache = false);

			#update the backend structure
			$this->setStructure($draft = true, $cache = false);

			# dispatch event
			$this->c->dispatcher->dispatch('onPageDeleted', new OnPageDeleted($this->item));
			
			return $response->withJson(array('data' => $this->structure, 'errors' => false, 'url' => $url), 200);
		}
		else
		{
			return $response->withJson(array('data' => $this->structure, 'errors' => $this->errors), 404); 
		}
	}
	
	public function updateArticle(Request $request, Response $response, $args)
	{
		# get params from call 
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();
		
		# validate input 
		if(!$this->validateEditorInput()){ return $response->withJson($this->errors,422); }
		
		# set structure
		if(!$this->setStructure($draft = true)){ return $response->withJson($this->errors, 404); }
				
		# set item 
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }

		# set path for the file (or folder)
		$this->setItemPath('txt');

		# merge title with content for complete markdown document
		$updatedContent = '# ' . $this->params['title'] . "\r\n\r\n" . $this->params['content'];

		# initialize parsedown extension
		$parsedown 		= new ParsedownExtension();
		
		# turn markdown into an array of markdown-blocks
		$contentArray = $parsedown->markdownToArrayBlocks($updatedContent);
		
		# encode the content into json
		$contentJson = json_encode($contentArray);		
				
		/* update the file */
		if($this->write->writeFile($this->settings['contentFolder'], $this->path, $contentJson))
		{
			# update the internal structure
			$this->setStructure($draft = true, $cache = false);
			
			return $response->withJson(['success'], 200);
		}
		else
		{
			return $response->withJson(['errors' => ['message' => 'Could not write to file. Please check if the file is writable']], 404);
		}
	}
	
	public function sortArticle(Request $request, Response $response, $args)
	{
		# get params from call
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();
		
		# url is only needed, if an active page is moved to another folder, so user has to be redirected to the new url
		$url 			= false;
		
		# set structure
		if(!$this->setStructure($draft = true)){ return $response->withJson(array('data' => false, 'errors' => $this->errors, 'url' => $url), 404); }
		
		# validate input
		if(!$this->validateNavigationSort()){ return $response->withJson(array('data' => $this->structure, 'errors' => 'Data not valid. Please refresh the page and try again.', 'url' => $url), 422); }
		
		# get the ids (key path) for item, old folder and new folder
		$itemKeyPath 	= explode('.', $this->params['item_id']);
		$parentKeyFrom	= explode('.', $this->params['parent_id_from']);
		$parentKeyTo	= explode('.', $this->params['parent_id_to']);
		
		# get the item from structure
		$item 			= Folder::getItemWithKeyPath($this->structure, $itemKeyPath);

		if(!$item){ return $response->withJson(array('data' => $this->structure, 'errors' => 'We could not find this page. Please refresh and try again.', 'url' => $url), 404); }
		
		# if a folder is moved on the first level
		if($this->params['parent_id_from'] == 'navi')
		{
			# create empty and default values so that the logic below still works
			$newFolder 			=  new \stdClass();
			$newFolder->path	= '';
			$folderContent		= $this->structure;
		}
		else
		{
			# get the target folder from structure
			$newFolder 		= Folder::getItemWithKeyPath($this->structure, $parentKeyTo);
			
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
		elseif($this->params['active'] == 'active')
		{
			# an active file has been moved to another folder, so send new url with response
			$url = $this->uri->getBaseUrl() . '/tm/content/' . $this->settings['editor'] . $newFolder->urlRelWoF . '/' . $item->slug;
		}
		
		# add item to newFolder
		array_splice($folderContent, $this->params['index_new'], 0, array($item));

		# initialize index
		$index = 0;
		
		# initialise write object
		$write = new Write();

		# iterate through the whole content of the new folder to rename the files
		$writeError = false;
		foreach($folderContent as $folderItem)
		{
			if(!$write->moveElement($folderItem, $newFolder->path, $index))
			{
				$writeError = true;
			}
			$index++;
		}
		if($writeError){ return $response->withJson(array('data' => $this->structure, 'errors' => 'Something went wrong. Please refresh the page and check, if all folders and files are writable.', 'url' => $url), 404); }

		# update the structure for editor
		$this->setStructure($draft = true, $cache = false);
		
		# get item for url and set it active again
		if(isset($this->params['url']))
		{
			$activeItem = Folder::getItemForUrl($this->structure, $this->params['url']);
		}
		
		# keep the internal structure for response
		$internalStructure = $this->structure;
		
		# update the structure for website
		$this->setStructure($draft = false, $cache = false);
		
		# dispatch event
		$this->c->dispatcher->dispatch('onPageSorted', new OnPageSorted($this->params));

		return $response->withJson(array('data' => $internalStructure, 'errors' => false, 'url' => $url));
	}
	
	public function createArticle(Request $request, Response $response, $args)
	{
		# get params from call
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();
		
		# url is only needed, if an active page is moved
		$url 			= false;
		
		# set structure
		if(!$this->setStructure($draft = true)){ return $response->withJson(array('data' => false, 'errors' => $this->errors, 'url' => $url), 404); }
		
		# validate input
		if(!$this->validateNaviItem()){ return $response->withJson(array('data' => $this->structure, 'errors' => 'Special Characters not allowed. Length between 1 and 20 chars.', 'url' => $url), 422); }
		
		# get the ids (key path) for item, old folder and new folder
		$folderKeyPath 	= explode('.', $this->params['folder_id']);
		
		# get the item from structure
		$folder			= Folder::getItemWithKeyPath($this->structure, $folderKeyPath);

		if(!$folder){ return $response->withJson(array('data' => $this->structure, 'errors' => 'We could not find this page. Please refresh and try again.', 'url' => $url), 404); }
		
		# Rename all files within the folder to make sure, that namings and orders are correct
		# get the content of the target folder
		$folderContent	= $folder->folderContent;
		
		# create the name for the new item
		$nameParts 	= Folder::getStringParts($this->params['item_name']);		
		$name 		= implode("-", $nameParts);
		$slug		= $name;
				
		# initialize index
		$index = 0;
		
		# initialise write object
		$write = new Write();

		# iterate through the whole content of the new folder
		$writeError = false;
		
		foreach($folderContent as $folderItem)
		{
			# check, if the same name as new item, then return an error
			if($folderItem->slug == $slug)
			{
				return $response->withJson(array('data' => $this->structure, 'errors' => 'There is already a page with this name. Please choose another name.', 'url' => $url), 404);
			}
			
			if(!$write->moveElement($folderItem, $folder->path, $index))
			{
				$writeError = true;
			}
			$index++;
		}

		if($writeError){ return $response->withJson(array('data' => $this->structure, 'errors' => 'Something went wrong. Please refresh the page and check, if all folders and files are writable.', 'url' => $url), 404); }

		# add prefix number to the name
		$namePath 	= $index > 9 ? $index . '-' . $name : '0' . $index . '-' . $name;
		$folderPath	= 'content' . $folder->path;
		
		# create default content
		$content = json_encode(['# Add Title', 'Add Content']);
		
		if($this->params['type'] == 'file')
		{
			if(!$write->writeFile($folderPath, $namePath . '.txt', $content))
			{
				return $response->withJson(array('data' => $this->structure, 'errors' => 'We could not create the file. Please refresh the page and check, if all folders and files are writable.', 'url' => $url), 404);
			}
		}
		elseif($this->params['type'] == 'folder')
		{
			if(!$write->checkPath($folderPath . DIRECTORY_SEPARATOR . $namePath))
			{
				return $response->withJson(array('data' => $this->structure, 'errors' => 'We could not create the folder. Please refresh the page and check, if all folders and files are writable.', 'url' => $url), 404);
			}
			$write->writeFile($folderPath . DIRECTORY_SEPARATOR . $namePath, 'index.txt', $content);
		}
		
		# update the structure for editor
		$this->setStructure($draft = true, $cache = false);

		# get item for url and set it active again
		if(isset($this->params['url']))
		{
			$activeItem = Folder::getItemForUrl($this->structure, $this->params['url']);
		}

		# activate this if you want to redirect after creating the page...
		# $url = $this->uri->getBaseUrl() . '/tm/content' . $folder->urlRelWoF . '/' . $name;
		
		return $response->withJson(array('data' => $this->structure, 'errors' => false, 'url' => $url));
	}

	public function createBaseFolder(Request $request, Response $response, $args)
	{
		# get params from call
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();
		
		# url is only needed, if an active page is moved
		$url 			= false;
		
		# set structure
		if(!$this->setStructure($draft = true)){ return $response->withJson(array('data' => false, 'errors' => $this->errors, 'url' => $url), 404); }
		
		# validate input
		#if(!$this->validateBaseFolder()){ return $response->withJson(array('data' => $this->structure, 'errors' => 'Special Characters not allowed. Length between 1 and 20 chars.', 'url' => $url), 422); }
				
		# create the name for the new item
		$nameParts 	= Folder::getStringParts($this->params['item_name']);		
		$name 		= implode("-", $nameParts);
		$slug		= $name;

		# initialize index
		$index = 0;		
		
		# initialise write object
		$write = new Write();

		# iterate through the whole content of the new folder
		$writeError = false;
		
		foreach($this->structure as $folder)
		{
			# check, if the same name as new item, then return an error
			if($folder->slug == $slug)
			{
				return $response->withJson(array('data' => $this->structure, 'errors' => 'There is already a page with this name. Please choose another name.', 'url' => $url), 404);
			}
			
			if(!$write->moveElement($folder, '', $index))
			{
				$writeError = true;
			}
			$index++;
		}

		if($writeError){ return $response->withJson(array('data' => $this->structure, 'errors' => 'Something went wrong. Please refresh the page and check, if all folders and files are writable.', 'url' => $url), 404); }

		# add prefix number to the name
		$namePath 	= $index > 9 ? $index . '-' . $name : '0' . $index . '-' . $name;
		$folderPath	= 'content';
		
		if(!$write->checkPath($folderPath . DIRECTORY_SEPARATOR . $namePath))
		{
			return $response->withJson(array('data' => $this->structure, 'errors' => 'We could not create the folder. Please refresh the page and check, if all folders and files are writable.', 'url' => $url), 404);
		}

		# create default content
		$content = json_encode(['# Add Title', 'Add Content']);
		
		$write->writeFile($folderPath . DIRECTORY_SEPARATOR . $namePath, 'index.txt', $content);
		
		# update the structure for editor
		$this->setStructure($draft = true, $cache = false);

		# get item for url and set it active again
		if(isset($this->params['url']))
		{
			$activeItem = Folder::getItemForUrl($this->structure, $this->params['url']);
		}

		return $response->withJson(array('data' => $this->structure, 'errors' => false, 'url' => $url));
	}

	public function getNavigation(Request $request, Response $response, $args)
	{
		# get params from call
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();

		# set structure
		if(!$this->setStructure($draft = true, $cache = false)){ return $response->withJson(array('data' => false, 'errors' => $this->errors, 'url' => $url), 404); }

		# set information for homepage
		$this->setHomepage();

		# get item for url and set it active again
		if(isset($this->params['url']))
		{
			$activeItem = Folder::getItemForUrl($this->structure, $this->params['url']);
		}

		return $response->withJson(array('data' => $this->structure, 'homepage' => $this->homepage, 'errors' => false));
	}

	public function getArticleMarkdown(Request $request, Response $response, $args)
	{
		/* get params from call */
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();
		
		# set structure
		if(!$this->setStructure($draft = true)){ return $response->withJson(array('data' => false, 'errors' => $this->errors), 404); }
		
		/* set item */
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }
		
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
			$parsedown = new ParsedownExtension();

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
		$this->uri 		= $request->getUri();
		
		# set structure
		if(!$this->setStructure($draft = true)){ return $response->withJson(array('data' => false, 'errors' => $this->errors), 404); }
		
		/* set item */
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }
		
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
		$parsedown = new ParsedownExtension();

		# fix footnotes in parsedown, might break with complicated footnotes
		$parsedown->setVisualMode();

		# if content is not an array, then transform it
		if(!is_array($content))
		{
			# turn markdown into an array of markdown-blocks
			$content = $parsedown->markdownToArrayBlocks($content);
		}
				
		# needed for ToC links
		$relurl = '/tm/content/' . $this->settings['editor'] . '/' . $this->item->urlRel;
		
		# flag for TOC
		$toc = false;

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
			$content[$key]	= ['id' => $key, 'html' => $parsedown->markup($contentArray, $relurl)];
		}

		if($toc)
		{
			$tocMarkup = $parsedown->buildTOC($parsedown->headlines);
			$content[$toc] = ['id' => $toc, 'html' => $tocMarkup];
		}

		return $response->withJson(array('data' => $content, 'errors' => false));
	}

	public function addBlock(Request $request, Response $response, $args)
	{
		/* get params from call */
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();

		/* validate input */
		if(!$this->validateBlockInput()){ return $response->withJson($this->errors,422); }
		
		# set structure
		if(!$this->setStructure($draft = true)){ return $response->withJson(array('data' => false, 'errors' => $this->errors), 404); }
		
		/* set item */
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }

		# set the status for published and drafted
		$this->setPublishStatus();

		# set path
		$this->setItemPath($this->item->fileType);

		# read content from file
		if(!$this->setContent()){ return $response->withJson(array('data' => false, 'errors' => $this->errors), 404); }

		# make it more clear which content we have
		$pageMarkdown = $this->content;

		$blockMarkdown = $this->params['markdown'];

        # standardize line breaks
        $blockMarkdown = str_replace(array("\r\n", "\r"), "\n", $blockMarkdown);

        # remove surrounding line breaks
        $blockMarkdown = trim($blockMarkdown, "\n");		
		
		if($pageMarkdown == '')
		{
			$pageMarkdown = [];
		}

		# initialize parsedown extension
		$parsedown = new ParsedownExtension();

		# if content is not an array, then transform it
		if(!is_array($pageMarkdown))
		{
			# turn markdown into an array of markdown-blocks
			$pageMarkdown = $parsedown->markdownToArrayBlocks($pageMarkdown);
		}

		# if it is a new content-block
		if($this->params['block_id'] == 99999)
		{
			# set the id of the markdown-block (it will be one more than the actual array, so count is perfect) 
			$id = count($pageMarkdown);

			# add the new markdown block to the page content
			$pageMarkdown[] = $blockMarkdown;			
		}
		elseif(($this->params['block_id'] == 0) OR !isset($pageMarkdown[$this->params['block_id']]))
		{
			# if the block does not exists, return an error
			return $response->withJson(array('data' => false, 'errors' => 'The ID of the content-block is wrong.'), 404);
		}
		else
		{
			# insert new markdown block
			array_splice( $pageMarkdown, $this->params['block_id'], 0, $blockMarkdown );			
			$id = $this->params['block_id'];
		}
	
		# encode the content into json
		$pageJson = json_encode($pageMarkdown);

		# set path for the file (or folder)
		$this->setItemPath('txt');
	
		/* update the file */
		if($this->write->writeFile($this->settings['contentFolder'], $this->path, $pageJson))
		{
			# update the internal structure
			$this->setStructure($draft = true, $cache = false);
			$this->content = $pageMarkdown;
		}
		else
		{
			return $response->withJson(['errors' => ['message' => 'Could not write to file. Please check if the file is writable']], 404);
		}
	
		/* set safe mode to escape javascript and html in markdown */
		$parsedown->setSafeMode(true);

		/* parse markdown-file to content-array */
		$blockArray = $parsedown->text($blockMarkdown);
		
		# we assume that toc is not relevant
		$toc = false;

		# needed for ToC links
		$relurl = '/tm/content/' . $this->settings['editor'] . '/' . $this->item->urlRel;
		
		if($blockMarkdown == '[TOC]')
		{
			# if block is table of content itself, then generate the table of content
			$tableofcontent = $this->generateToc();

			# and only use the html-markup
			$blockHTML = $tableofcontent['html'];
		}
		else
		{
			# parse markdown-content-array to content-string
			$blockHTML = $parsedown->markup($blockArray, $relurl);
			
			# if it is a headline
			if($blockMarkdown[0] == '#')
			{
				# then the TOC holds either false (if no toc used in the page) or it holds an object with the id and toc-markup
				$toc = $this->generateToc();
			}
		}

		return $response->withJson(array('content' => [ 'id' => $id, 'html' => $blockHTML ] , 'markdown' => $blockMarkdown, 'id' => $id, 'toc' => $toc, 'errors' => false));
	}

	protected function generateToc()
	{
		# we assume that page has no table of content
		$toc = false;

		# make sure $this->content is updated
		$content = $this->content;

		if($content == '')
		{
			$content = [];
		}
		
		# initialize parsedown extension
		$parsedown = new ParsedownExtension();
		
		# if content is not an array, then transform it
		if(!is_array($content))
		{
			# turn markdown into an array of markdown-blocks
			$content = $parsedown->markdownToArrayBlocks($content);
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
				# toc is true and holds the key of the table of content now
				$toc = $key;
			}

			# parse markdown-content-array to content-string
			$content[$key]	= ['id' => $key, 'html' => $parsedown->markup($contentArray, $relurl)];
		}

		# if page has a table of content
		if($toc)
		{
			# generate the toc markup
			$tocMarkup = $parsedown->buildTOC($parsedown->headlines);

			# toc holds the id of the table of content and the html-markup now
			$toc = ['id' => $toc, 'html' => $tocMarkup];
		}

		return $toc;
	}

	public function updateBlock(Request $request, Response $response, $args)
	{
		/* get params from call */
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();

		/* validate input */
		if(!$this->validateBlockInput()){ return $response->withJson($this->errors,422); }
		
		# set structure
		if(!$this->setStructure($draft = true)){ return $response->withJson(array('data' => false, 'errors' => $this->errors), 404); }
		
		/* set item */
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }

		# set the status for published and drafted
		$this->setPublishStatus();

		# set path
		$this->setItemPath($this->item->fileType);

		# read content from file
		if(!$this->setContent()){ return $response->withJson(array('data' => false, 'errors' => $this->errors), 404); }

		# make it more clear which content we have
		$pageMarkdown = $this->content;

		$blockMarkdown = $this->params['markdown'];

        # standardize line breaks
        $blockMarkdown = str_replace(array("\r\n", "\r"), "\n", $blockMarkdown);

        # remove surrounding line breaks
        $blockMarkdown = trim($blockMarkdown, "\n");

		if($pageMarkdown == '')
		{
			$pageMarkdown = [];
		}

		# initialize parsedown extension
		$parsedown = new ParsedownExtension();
		$parsedown->setVisualMode();

		# if content is not an array, then transform it
		if(!is_array($pageMarkdown))
		{
			# turn markdown into an array of markdown-blocks
			$pageMarkdown = $parsedown->markdownToArrayBlocks($pageMarkdown);
		}

		if(!isset($pageMarkdown[$this->params['block_id']]))
		{
			# if the block does not exists, return an error
			return $response->withJson(array('data' => false, 'errors' => 'The ID of the content-block is wrong.'), 404);
		}
		elseif($this->params['block_id'] == 0)
		{
			# if it is the title, then delete the "# " if it exists
			$blockMarkdown = trim($blockMarkdown, "# ");
			
			# store the markdown-headline in a separate variable
			$blockMarkdownTitle = '# ' . $blockMarkdown;
			
			# add the markdown-headline to the page-markdown
			$pageMarkdown[0] = $blockMarkdownTitle;
			$id = 0;
		}
		else
		{
			# update the markdown block in the page content
			$pageMarkdown[$this->params['block_id']] = $blockMarkdown;
			$id = $this->params['block_id'];
		}

		# encode the content into json
		$pageJson = json_encode($pageMarkdown);

		# set path for the file (or folder)
		$this->setItemPath('txt');
	
		/* update the file */
		if($this->write->writeFile($this->settings['contentFolder'], $this->path, $pageJson))
		{
			# update the internal structure
			$this->setStructure($draft = true, $cache = false);

			# updated the content variable
			$this->content = $pageMarkdown;
		}
		else
		{
			return $response->withJson(['errors' => ['message' => 'Could not write to file. Please check if the file is writable']], 404);
		}
	
		/* set safe mode to escape javascript and html in markdown */
		$parsedown->setSafeMode(true);

		/* parse markdown-file to content-array, if title parse title. */
		if($this->params['block_id'] == 0)
		{
			$blockArray		= $parsedown->text($blockMarkdownTitle);
		}
		else
		{
			$blockArray 	= $parsedown->text($blockMarkdown);
		}
		
		# we assume that toc is not relevant
		$toc = false;

		# needed for ToC links
		$relurl = '/tm/content/' . $this->settings['editor'] . '/' . $this->item->urlRel;
		
		if($blockMarkdown == '[TOC]')
		{
			# if block is table of content itself, then generate the table of content
			$tableofcontent = $this->generateToc();

			# and only use the html-markup
			$blockHTML = $tableofcontent['html'];
		}
		else
		{
			# parse markdown-content-array to content-string
			$blockHTML = $parsedown->markup($blockArray, $relurl);
			
			# if it is a headline
			if($blockMarkdown[0] == '#')
			{
				# then the TOC holds either false (if no toc used in the page) or it holds an object with the id and toc-markup
				$toc = $this->generateToc();
			}
		}

		return $response->withJson(array('content' => [ 'id' => $id, 'html' => $blockHTML ] , 'markdown' => $blockMarkdown, 'id' => $id, 'toc' => $toc, 'errors' => false));
	}
	
	public function moveBlock(Request $request, Response $response, $args)
	{
		# get params from call
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();

		# validate input 
		# if(!$this->validateBlockInput()){ return $response->withJson($this->errors,422); }
		
		# set structure
		if(!$this->setStructure($draft = true)){ return $response->withJson(array('data' => false, 'errors' => $this->errors), 404); }
		
		# set item 
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }

		# set the status for published and drafted
		$this->setPublishStatus();

		# set path
		$this->setItemPath($this->item->fileType);

		# read content from file
		if(!$this->setContent()){ return $response->withJson(array('data' => false, 'errors' => $this->errors), 404); }

		# make it more clear which content we have
		$pageMarkdown = $this->content;
		
		if($pageMarkdown == '')
		{
			$pageMarkdown = [];
		}

		# initialize parsedown extension
		$parsedown = new ParsedownExtension();

		# if content is not an array, then transform it
		if(!is_array($pageMarkdown))
		{
			# turn markdown into an array of markdown-blocks
			$pageMarkdown = $parsedown->markdownToArrayBlocks($pageMarkdown);
		}

		$oldIndex = ($this->params['old_index'] + 1);
		$newIndex = ($this->params['new_index'] + 1);
		
		if(!isset($pageMarkdown[$oldIndex]))
		{
			# if the block does not exists, return an error
			return $response->withJson(array('data' => false, 'errors' => 'The ID of the content-block is wrong.'), 404);
		}

		$extract = array_splice($pageMarkdown, $oldIndex, 1);
		array_splice($pageMarkdown, $newIndex, 0, $extract);
	
		# encode the content into json
		$pageJson = json_encode($pageMarkdown);

		# set path for the file (or folder)
		$this->setItemPath('txt');
	
		/* update the file */
		if($this->write->writeFile($this->settings['contentFolder'], $this->path, $pageJson))
		{
			# update the internal structure
			$this->setStructure($draft = true, $cache = false);

			# update this content
			$this->content = $pageMarkdown;
		}
		else
		{
			return $response->withJson(['errors' => ['message' => 'Could not write to file. Please check if the file is writable']], 404);
		}

		# we assume that toc is not relevant
		$toc = false;

		# needed for ToC links
		$relurl = '/tm/content/' . $this->settings['editor'] . '/' . $this->item->urlRel;

		# if the moved item is a headline
		if($extract[0][0] == '#')
		{
			$toc = $this->generateToc();
		}

		# if it is the title, then delete the "# " if it exists
		$pageMarkdown[0] = trim($pageMarkdown[0], "# ");

		return $response->withJson(array('markdown' => $pageMarkdown, 'toc' => $toc, 'errors' => false));
	}

	public function deleteBlock(Request $request, Response $response, $args)
	{
		/* get params from call */
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();
		$errors			= false;
		
		# set structure
		if(!$this->setStructure($draft = true)){ return $response->withJson(array('data' => false, 'errors' => $this->errors), 404); }
		
		# set item
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }

		# set the status for published and drafted
		$this->setPublishStatus();

		# set path
		$this->setItemPath($this->item->fileType);

		# read content from file
		if(!$this->setContent()){ return $response->withJson(array('data' => false, 'errors' => $this->errors), 404); }

		# get content
		$this->content;

		if($this->content == '')
		{
			$this->content = [];
		}

		# initialize parsedown extension
		$parsedown = new ParsedownExtension();

		# if content is not an array, then transform it
		if(!is_array($this->content))
		{
			# turn markdown into an array of markdown-blocks
			$this->content = $parsedown->markdownToArrayBlocks($this->content);
		}

		# check if id exists
		if(!isset($this->content[$this->params['block_id']])){ return $response->withJson(array('data' => false, 'errors' => 'The ID of the content-block is wrong.'), 404); }

		# check if block is image
		$contentBlock 		= $this->content[$this->params['block_id']];
		$contentBlockStart 	= substr($contentBlock, 0, 2);
		if($contentBlockStart == '[!' OR $contentBlockStart == '![')
		{
			# extract image path
			preg_match("/\((.*?)\)/",$contentBlock,$matches);
			if(isset($matches[1]))
			{
				$imageBaseName	= explode('-', $matches[1]);
				$imageBaseName	= str_replace('media/live/', '', $imageBaseName[0]);
				$processImage 	= new ProcessImage();
				if(!$processImage->deleteImage($imageBaseName))
				{
					$errors = 'Could not delete some of the images, please check manually';
				}
			}
		}
		
		# delete the block
		unset($this->content[$this->params['block_id']]);
		$this->content = array_values($this->content);

		$pageMarkdown = $this->content;
		
		# delete markdown from title
		if(isset($pageMarkdown[0]))
		{
			$pageMarkdown[0] = trim($pageMarkdown[0], "# ");
		}
		
		# encode the content into json
		$pageJson = json_encode($this->content);

		# set path for the file (or folder)
		$this->setItemPath('txt');		
	
		/* update the file */
		if($this->write->writeFile($this->settings['contentFolder'], $this->path, $pageJson))
		{
			# update the internal structure
			$this->setStructure($draft = true, $cache = false);
		}
		else
		{
			return $response->withJson(['errors' => ['message' => 'Could not write to file. Please check if the file is writable']], 404);
		}
		
		$toc = false;

		if($contentBlock[0] == '#')
		{
			$toc = $this->generateToc();
		}

		return $response->withJson(array('markdown' => $pageMarkdown, 'toc' => $toc, 'errors' => $errors));
	}

	public function createImage(Request $request, Response $response, $args)
	{
		/* get params from call */
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();
		
		$imageProcessor	= new ProcessImage();
		
		if($imageProcessor->createImage($this->params['image'], $this->settings['images']))
		{
			return $response->withJson(array('errors' => false));		
		}

		return $response->withJson(array('errors' => 'could not store image to temporary folder'));	
	}
	
	public function publishImage(Request $request, Response $response, $args)
	{
		$params 		= $request->getParsedBody();

		$imageProcessor	= new ProcessImage();
		
		$imageUrl 		= $imageProcessor->publishImage($this->settings['images'], $name = false);
		if($imageUrl)
		{
			$params['markdown']	= str_replace('imgplchldr', $imageUrl, $params['markdown']);
						
			$request 	= $request->withParsedBody($params);
			
			return $this->addBlock($request, $response, $args);
		}

		return $response->withJson(array('errors' => 'could not store image to media folder'));	
	}

	public function saveVideoImage(Request $request, Response $response, $args)
	{
		/* get params from call */
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();
		$class			= false;

		$imageUrl		= $this->params['markdown'];
		
		if(strpos($imageUrl, 'https://www.youtube.com/watch?v=') !== false)
		{
			$videoID 	= str_replace('https://www.youtube.com/watch?v=', '', $imageUrl);
			$videoID 	= strpos($videoID, '&') ? substr($videoID, 0, strpos($videoID, '&')) : $videoID;
			$class		= 'youtube';
		}
		if(strpos($imageUrl, 'https://youtu.be/') !== false)
		{
			$videoID 	= str_replace('https://youtu.be/', '', $imageUrl);
			$videoID	= strpos($videoID, '?') ? substr($videoID, 0, strpos($videoID, '?')) : $videoID;
			$class		= 'youtube';
		}
		
		if($class == 'youtube')
		{
			$videoURLmaxres = 'https://i1.ytimg.com/vi/' . $videoID . '/maxresdefault.jpg';
			$videoURL0 = 'https://i1.ytimg.com/vi/' . $videoID . '/0.jpg';
		}

		$ctx = stream_context_create(array(
			'https' => array(
				'timeout' => 1
				)
			)
		);
		
		$imageData		= @file_get_contents($videoURLmaxres, 0, $ctx);
		if($imageData === false)
		{
			$imageData	= @file_get_contents($videoURL0, 0, $ctx);
			if($imageData === false)
			{
				return $response->withJson(array('errors' => 'could not get the video image'));
			}
		}
		
		$imageData64	= 'data:image/jpeg;base64,' . base64_encode($imageData);
		$desiredSizes	= ['live' => ['width' => 560, 'height' => 315]];
		$imageProcessor	= new ProcessImage();
		$tmpImage		= $imageProcessor->createImage($imageData64, $desiredSizes);
		
		if(!$tmpImage)
		{
			return $response->withJson(array('errors' => 'could not create temporary image'));			
		}
		
		$imageUrl 		= $imageProcessor->publishImage($desiredSizes, $videoID);
		if($imageUrl)
		{
			$this->params['markdown'] = '![' . $class . '-video](' . $imageUrl . ' "click to load video"){#' . $videoID. ' .' . $class . '}';

			$request 	= $request->withParsedBody($this->params);
			
			return $this->addBlock($request, $response, $args);
		}
		
		return $response->withJson(array('errors' => 'could not store the preview image'));	
	}
}