<?php

namespace Typemill\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Typemill\Models\Folder;
use Typemill\Models\Write;
use Typemill\Extensions\ParsedownExtension;

class ContentApiController extends ContentController
{
	public function publishArticle(Request $request, Response $response, $args)
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
		
		# set the status for published and drafted
		$this->setPublishStatus();

		# set path for the file (or folder)
		$this->setItemPath('md');

		# merge title with content for complete markdown document
		$updatedContent = '# ' . $this->params['title'] . "\r\n\r\n" . $this->params['content'];
		
		# update the file
		if($this->write->writeFile($this->settings['contentFolder'], $this->path, $updatedContent))
		{
			# update the file
			$delete = $this->deleteContentFiles(['txt']);
			
			# update the internal structure
			$this->setStructure($draft = true, $cache = false);
			
			# update the public structure
			$this->setStructure($draft = false, $cache = false);

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
		
		# update the file
		$delete = $this->deleteContentFiles(['md']);
		
		if($delete)
		{
			# update the internal structure
			$this->setStructure($draft = true, $cache = false);
			
			# update the live structure
			$this->setStructure($draft = false, $cache = false);
			
			return $response->withJson(['success'], 200);
		}
		else
		{
			return $response->withJson(['errors' => ['message' => "Could not delete some files. Please check if the files exists and are writable"]], 404);
		}
	}

	public function deleteArticle(Request $request, Response $response, $args)
	{
		# get params from call 
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();

		# set url to base path initially
		$url = $this->uri->getBaseUrl() . '/tm/content';
		
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
		
		# url is only needed, if an active page is moved
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
			# an active file has been moved to another folder
			$url = $this->uri->getBaseUrl() . '/tm/content' . $newFolder->urlRelWoF . '/' . $item->slug;
		}
		
		# add item to newFolder
		array_splice($folderContent, $this->params['index_new'], 0, array($item));

		# initialize index
		$index = 0;
		
		# initialise write object
		$write = new Write();

		# iterate through the whole content of the new folder
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
		$nameParts = Folder::getStringParts($this->params['item_name']);		
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
		
		if($this->params['type'] == 'file')
		{
			if(!$write->writeFile($folderPath, $namePath . '.txt', ''))
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
			$write->writeFile($folderPath . DIRECTORY_SEPARATOR . $namePath, 'index.txt', '');
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
		$write->writeFile($folderPath . DIRECTORY_SEPARATOR . $namePath, 'index.txt', '');
		
		# update the structure for editor
		$this->setStructure($draft = true, $cache = false);

		# get item for url and set it active again
		if(isset($this->params['url']))
		{
			$activeItem = Folder::getItemForUrl($this->structure, $this->params['url']);
		}

		return $response->withJson(array('data' => $this->structure, 'errors' => false, 'url' => $url));
	}
	
	
	public function createBlock(Request $request, Response $response, $args)
	{
		/* get params from call */
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();
		
		/* validate input */
		if(!$this->validateInput()){ return $response->withJson($this->errors,422); }
		
		/* set structure */
		if(!$this->setStructure()){ return $response->withJson($this->errors, 404); }

		/* set item */
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }

		/* set path */
		$this->setItemPath();

		/* get markdown-file */
		if(!$this->setMarkdownFile()){ return $response->withJson($this->errors, 404); }
		
		/* get txt-file with content array */
		$contentArray = NULL;
		
		/* 
			create a txt-file with parsedown-array.
			you will have .md and .txt file.
			scan folder with option to show drafts.
			but what is with structure? We use the cached structure, do not forget!!!
			if there is a draft, replace the md file with txt-file.
			display content: you have to check if md or txt. if txt, then directly open the txt-file.
			in here set markdown-file or
			set txt-file.
			if publish, render txt-content, replace markdown-file, delete txt-file
		*/
		
		/* initialize pagedown */
		
		/* turn input into array */
		
		/* add input to contentArray */
		
		/* store updated contentArray */
		
		/* transform input to html */
		
		/* send html to client */
	}	
}