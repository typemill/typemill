<?php

namespace Typemill\Controllers;

use Typemill\Models\Folder;
use Typemill\Models\WriteMeta;
use Typemill\Models\WriteYaml;
use Typemill\Models\Validation;

class ControllerAuthor extends ControllerShared
{	
	# holds the params from request
	protected $params;
		
	# holds the slim-uri-object from request
	protected $uri;
	
	# holds the errors to output in frontend 
	protected $errors = false;
	
	# holds informations about the homepage
	protected $homepage;

	# hold the page-item as an object
	protected $item;
	
	# hold the breadcrumb as an object
	protected $breadcrumb;
	
	# holds the path to the requested file
	protected $path = false;
	
	# holds the content of the page
	protected $content;

	# holds the ownership (my content or not my content)
	protected $mycontent = false;
	
	# author
	protected function getValidator()
	{
		return new Validation();
	}

	# author
	protected function validateEditorInput()
	{
		$validate 	= new Validation();
		$vResult 	= $validate->editorInput($this->params);
		
		if(is_array($vResult))
		{
			$message 		= reset($vResult);
			$this->errors 	= ['errors' => $vResult];

			if(isset($message[0])){
				$this->errors['errors']['message'] = $message[0]; 
			}
			
			return false;
		}
		return true;
	}

	# author
	protected function validateBlockInput()
	{
		$validate = new Validation();
		$vResult = $validate->blockInput($this->params);
		
		if(is_array($vResult))
		{ 
			$message = reset($vResult);
			$this->errors = ['errors' => $vResult];

			if(isset($message[0]))
			{ 
				$this->errors['errors']['message'] = $message[0]; 
			}

			return false;
		}
		return true;
	}
	
	# author
	protected function validateNavigationSort()
	{
		$validate = new Validation();
		$vResult = $validate->navigationSort($this->params);
		
		if(is_array($vResult))
		{
			$message = reset($vResult);
			$this->errors = ['errors' => $vResult];
			
			if(isset($message[0])){ 
				$this->errors['errors']['message'] = $message[0]; 
			}

			return false;
		}
		return true;
	}

	# author
	protected function validateNaviItem()
	{
		$validate = new Validation();
		$vResult = $validate->navigationItem($this->params);
		
		if(is_array($vResult))
		{
			$message = reset($vResult);
			$this->errors = ['errors' => $vResult];
			
			if(isset($message[0]))
			{ 
				$this->errors['errors']['message'] = $message[0]; 
			}
			
			return false;
		}
		return true;
	}

	# author
	protected function validateBaseNaviItem()
	{
		$validate = new Validation();
		$vResult = $validate->navigationBaseItem($this->params);
		
		if(is_array($vResult))
		{
			$message = reset($vResult);
			$this->errors = ['errors' => $vResult];
			
			if(isset($message[0]))
			{ 
				$this->errors['errors']['message'] = $message[0]; 
			}

			return false;
		}
		return true;
	}

	# only backoffice
	protected function setHomepage($args)
	{
		$contentFolder = Folder::scanFolderFlat($this->settings['rootPath'] . $this->settings['contentFolder']);

		if(in_array('index.md', $contentFolder))
		{
			$md = true;
			$status = 'published';
		}
		if(in_array('index.txt', $contentFolder))
		{
			$txt = true;
			$status = 'unpublished';
		}
		if(isset($txt) && isset($md))
		{
			$status = 'modified';
		}

		$active = false;
		if($this->params['url'] == '/' || (is_array($args) && empty($args)))
		{
			$active = 'active';
		}

		$this->homepage = ['status' => $status, 'active' => $active];
	}

	# only backoffice
	protected function setItem()
	{
		# home is only set by backend controller, not by api calls
		$home = isset($this->homepage['active']) ? $this->homepage['active'] : false;

		# search for the url in the structure
		$item = Folder::getItemForUrl($this->structureDraft, $this->params['url'], $this->uri->getBaseUrl(), NULL, $home);

		if($item)
		{
			$this->item = $item;
			return true;
		}

		$this->errors = ['errors' => ['message' => 'requested page-url not found']];

		return false;
	}
	
	# only backoffice
	# determine if you want to write to published file (md) or to draft (txt)
	protected function setItemPath($fileType)
	{
		$this->path = $this->item->pathWithoutType . '.' . $fileType;
	}
	
	# only backoffice
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
	
	# only backoffice
	protected function setContent()
	{
		# if the file exists
		if($this->item->published OR $this->item->drafted)
		{
			$content = $this->writeCache->getFile($this->settings['contentFolder'], $this->path);			
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

	# only backoffice
	protected function checkContentOwnership()
	{
		# get page meta
		$writeMeta = new writeMeta();
		$pagemeta = $writeMeta->getPageMeta($this->settings, $this->item);

		# check ownership
		if(isset($pagemeta['meta']['owner']) && $pagemeta['meta']['owner'] && $pagemeta['meta']['owner'] !== '' )
		{
			$allowedusers = array_map('trim', explode(",", $pagemeta['meta']['owner']));
			if(isset($_SESSION['user']) && in_array($_SESSION['user'], $allowedusers))
			{
				$this->mycontent = true;
				return true;
			}
		}

		return false;
	}
	
	# only backoffice
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
	
	# only backoffice
	protected function deleteContentFolder()
	{
		$basePath = $this->settings['rootPath'] . $this->settings['contentFolder'];
		$path = $basePath . $this->item->path;

		if(file_exists($path))
		{
			$files = array_diff(scandir($path), array('.', '..'));

			# check if there are published pages or folders inside, then stop the operation
			foreach ($files as $file)
			{
				if(is_dir(realpath($path) . DIRECTORY_SEPARATOR . $file))
				{
					$this->errors = ['message' => 'Please delete the sub-folder first.'];
				}

				if(substr($file, -3) == '.md' && $file != 'index.md')
				{
					$this->errors = ['message' => 'Please unpublish all pages in the folder first.'];		
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

			# delete all files from the extended file
			$this->deleteFromExtended();
		}
		return false;
	}
}