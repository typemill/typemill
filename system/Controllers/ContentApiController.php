<?php

namespace Typemill\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
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
			
			# update the structure
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

		# set structure
		if(!$this->setStructure($draft = true)){ return $response->withJson($this->errors, 404); }

		# set item 
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }
		
		# update the file
		$delete = $this->deleteContentFiles(['md','txt']);
		
		if($delete)
		{
			# update the live structure
			$this->setStructure($draft = false, $cache = false);
			
			#update the backend structure
			$this->setStructure($draft = true, $cache = false);
			
			return $response->withJson(['success'], 200);
		}
		else
		{
			return $response->withJson(['errors' => ['message' => "Could not delete some files. Please check if the files exists and are writable"]], 404);
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
			return $response->withJson(['success'], 200);
		}
		else
		{
			return $response->withJson(['errors' => ['message' => 'Could not write to file. Please check if the file is writable']], 404);
		}
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