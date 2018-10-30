<?php

namespace Typemill\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Interop\Container\ContainerInterface;
use Typemill\Models\Validation;
use Typemill\Models\Folder;
use Typemill\Models\Write;
use Typemill\Models\WriteCache;

abstract class ContentController
{
	# holds the pimple container
	protected $c;
	
	# holds the params from request
	protected $params;
		
	# holds the slim-uri-object
	protected $uri;
	
	# holds the errors to output in frontend 
	protected $errors = false;
	
	# holds a write object to write files 
	protected $write;
	
	# holds the structure of content folder as a serialized array of objects 
	protected $structure;
	
	# holds the name of the structure-file with drafts for author environment 
	protected $structureDraftName;
	
	# holds the name of the structure-file without drafts for live site 
	protected $structureLiveName;
	
	# hold the page-item as an object
	protected $item;
	
	# hold the breadcrumb as an object
	protected $breadcrumb;
	
	# holds the path to the requested file
	protected $path = false;
	
	# holds the content of the page
	protected $content;
	
	public function __construct(ContainerInterface $c)
	{
		$this->c 					= $c;
		$this->settings				= $this->c->get('settings');
		$this->structureLiveName 	= 'structure.txt';
		$this->structureDraftName 	= 'structure-draft.txt';
	}
	
	protected function render($response, $route, $data)
	{
		if(isset($_SESSION['old']))
		{
			unset($_SESSION['old']);
		}
		
		if($this->c->request->getUri()->getScheme() == 'https')
		{
			$response = $response->withAddedHeader('Strict-Transport-Security', 'max-age=63072000');
		}

		$response = $response->withAddedHeader('X-Content-Type-Options', 'nosniff');
		$response = $response->withAddedHeader('X-Frame-Options', 'SAMEORIGIN');
		$response = $response->withAddedHeader('X-XSS-Protection', '1;mode=block');
		$response = $response->withAddedHeader('Referrer-Policy', 'no-referrer-when-downgrade');
		
		return $this->c->view->render($response, $route, $data);
	}
	
	protected function render404($response, $data = NULL)
	{
		return $this->c->view->render($response->withStatus(404), '/404.twig', $data);
	}
	
	protected function renderIntern404($response, $data = NULL)
	{
		return $this->c->view->render($response->withStatus(404), '/intern404.twig', $data);
	}	

	protected function validateEditorInput()
	{
		$validate = new Validation();
		$vResult = $validate->editorInput($this->params);
		
		if(is_array($vResult))
		{ 
			$this->errors = ['errors' => $vResult];
			return false;
		}
		return true;
	}

	protected function validateBlockInput()
	{
		$validate = new Validation();
		$vResult = $validate->blockInput($this->params);
		
		if(is_array($vResult))
		{ 
			$this->errors = ['errors' => $vResult];
			return false;
		}
		return true;
	}
	
	protected function validateNavigationSort()
	{
		$validate = new Validation();
		$vResult = $validate->navigationSort($this->params);
		
		if(is_array($vResult))
		{
			$this->errors = ['errors' => $vResult];
			return false;
		}
		return true;
	}

	protected function validateNaviItem()
	{
		$validate = new Validation();
		$vResult = $validate->navigationItem($this->params);
		
		if(is_array($vResult))
		{
			$this->errors = ['errors' => $vResult];
			return false;
		}
		return true;
	}
	
	protected function setStructure($draft = false, $cache = true)
	{
		# set initial structure to false
		$structure = false;

		# name of structure-file for draft or live
		$filename = $draft ? $this->structureDraftName : $this->structureLiveName;
		
		# set variables and objects
		$this->write = new writeCache();
				
		# check, if cached structure is still valid 
		if($cache && $this->write->validate('cache', 'lastCache.txt', 600))
		{
			# get the cached structure
			$structure = $this->write->getCache('cache', $filename);
		}
		
		# if no structure was found or cache is deactivated
		if(!$structure)
		{
			# scan the content of the folder
			$structure = Folder::scanFolder($this->settings['rootPath'] . $this->settings['contentFolder'], $draft);

			# if there is content, then get the content details
			if(count($structure) > 0)
			{
				# create an array of object with the whole content of the folder
				$structure = Folder::getFolderContentDetails($structure, $this->uri->getBaseUrl(), $this->uri->getBasePath());
			}
			
			# cache navigation
			$this->write->updateCache('cache', $filename, 'lastCache.txt', $structure);
		}
				
		$this->structure = $structure;
		return true;
	}

	protected function setItem()
	{
		# if it is the homepage
		if($this->params['url'] == $this->uri->getBasePath() OR $this->params['url'] == '/')
		{
			$item 					= new \stdClass;
			$item->elementType 		= 'folder';
			$item->path				= '';
			$item->urlRel			= '/';
		}
		else
		{
			# search for the url in the structure
			$item = Folder::getItemForUrl($this->structure, $this->params['url']);
		}

		if($item)
		{
			if($item->elementType == 'file')
			{
				$pathParts 					= explode('.', $item->path);
				$fileType 					= array_pop($pathParts);
				$pathWithoutType 			= implode('.', $pathParts);
				$item->pathWithoutType		= $pathWithoutType;
			}
			elseif($item->elementType == 'folder')
			{
				$item->pathWithoutItem		= $item->path;
				$item->path 				= $item->path . DIRECTORY_SEPARATOR . 'index';
				$item->pathWithoutType		= $item->path;
			}
			$this->item = $item;
			return true;
		}

		$this->errors = ['errors' => ['message' => 'requested page-url not found']];
		return false;
	}
	
	# determine if you want to write to published file (md) or to draft (txt)
	protected function setItemPath($fileType)
	{
		$this->path = $this->item->pathWithoutType . '.' . $fileType;
	}
		
	protected function setPublishStatus()
	{
		$this->item->published = false;
		$this->item->drafted = false;
				
		if(file_exists($this->settings['rootPath'] . $this->settings['contentFolder'] . $this->item->pathWithoutType . '.md'))
		{
			$this->item->published = true;
			
			# add file-type in case it is a folder
			$this->item->fileType = "md"; 
		}
		
		if(file_exists($this->settings['rootPath'] . $this->settings['contentFolder'] . $this->item->pathWithoutType . '.txt'))
		{
			$this->item->drafted = true;
			
			# add file-type in case it is a folder
			$this->item->fileType = "txt"; 
		}
		
		if(!$this->item->drafted && !$this->item->published && $this->item->elementType == "folder")
		{
			# set txt as default for a folder, so that we can create an index.txt for a folder.
			$this->item->fileType = "txt"; 			
		}
	}
		
	protected function deleteContentFiles($fileTypes, $folder = false)
	{
		$basePath = $this->settings['rootPath'] . $this->settings['contentFolder'];

		foreach($fileTypes as $fileType)
		{
			if(file_exists($basePath . $this->item->pathWithoutType . '.' . $fileType) && !unlink($basePath . $this->item->pathWithoutType . '.' . $fileType) )
			{
				$this->errors = ['message' => 'We could not delete the file, please check, if the file is writable.'];				
			}
		}
		
		if($this->errors)
		{
			return false;
		}
		
		return true;
	}
	
	protected function deleteContentFolder()
	{
		$basePath = $this->settings['rootPath'] . $this->settings['contentFolder'];
		$path = $basePath . $this->item->pathWithoutItem;

		if(file_exists($path))
		{
			$files = array_diff(scandir($path), array('.', '..'));
			
			# check if there are folders first, then stop the operation
			foreach ($files as $file)
			{
				if(is_dir(realpath($path) . DIRECTORY_SEPARATOR . $file))
				{
					$this->errors = ['message' => 'Please delete the sub-folder first.'];
				}
			}

			if(!$this->errors)
			{
				foreach ($files as $file)
				{
					unlink(realpath($path) . DIRECTORY_SEPARATOR . $file);
				}
				return rmdir($path);
			}
		}
		return false;
	}
	
	protected function setContent()
	{
		# if the file exists
		if($this->item->published OR $this->item->drafted)
		{
			$content = $this->write->getFile($this->settings['contentFolder'], $this->path);			
			if($this->item->fileType == 'txt')
			{
				# decode the json-draft to an array
				$content = json_decode($content);
			}
		}
		elseif($this->item->elementType == "folder")
		{
			$content = '';
		}
		else
		{
			$this->errors = ['errors' => ['message' => 'requested file not found']];
			return false;
		}
		
		$this->content = $content;
		return true;		
	}
}