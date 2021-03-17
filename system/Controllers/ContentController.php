<?php

namespace Typemill\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Psr\Container\ContainerInterface;
use Typemill\Models\Validation;
use Typemill\Models\Folder;
use Typemill\Models\Write;
use Typemill\Models\WriteCache;
use Typemill\Models\WriteYaml;
use Typemill\Models\WriteMeta;

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
	
	public function __construct(ContainerInterface $c)
	{
		$this->c 					= $c;
		$this->settings				= $this->c->get('settings');
		$this->structureLiveName 	= 'structure.txt';
		$this->structureDraftName 	= 'structure-draft.txt';

		$this->c->dispatcher->dispatch('onTwigLoaded');
	}
	
	# admin ui rendering
	protected function render($response, $route, $data)
	{
		if(isset($_SESSION['old']))
		{
			unset($_SESSION['old']);
		}

		$response = $response->withoutHeader('Server');
		$response = $response->withAddedHeader('X-Powered-By', 'Typemill');
		
		if(!isset($this->settings['headersoff']) or !$this->settings['headersoff'])
		{
			$response = $response->withAddedHeader('X-Content-Type-Options', 'nosniff');
			$response = $response->withAddedHeader('X-Frame-Options', 'SAMEORIGIN');
			$response = $response->withAddedHeader('X-XSS-Protection', '1;mode=block');
			$response = $response->withAddedHeader('Referrer-Policy', 'no-referrer-when-downgrade');
			if($this->c->request->getUri()->getScheme() == 'https')
			{
				$response = $response->withAddedHeader('Strict-Transport-Security', 'max-age=63072000');
			}
		}
		
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

	protected function getValidator()
	{
		return new Validation();
	}

	protected function validateEditorInput()
	{
		$validate = new Validation();
		$vResult = $validate->editorInput($this->params);
		
		if(is_array($vResult))
		{
			$message = reset($vResult);
			$this->errors = ['errors' => $vResult];
			if(isset($message[0])){ $this->errors['errors']['message'] = $message[0]; }
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
			$message = reset($vResult);
			$this->errors = ['errors' => $vResult];
			if(isset($message[0])){ $this->errors['errors']['message'] = $message[0]; }
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
			$message = reset($vResult);
			$this->errors = ['errors' => $vResult];
			if(isset($message[0])){ $this->errors['errors']['message'] = $message[0]; }
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
			$message = reset($vResult);
			$this->errors = ['errors' => $vResult];
			if(isset($message[0])){ $this->errors['errors']['message'] = $message[0]; }
			return false;
		}
		return true;
	}

	protected function validateBaseNaviItem()
	{
		$validate = new Validation();
		$vResult = $validate->navigationBaseItem($this->params);
		
		if(is_array($vResult))
		{
			$message = reset($vResult);
			$this->errors = ['errors' => $vResult];
			if(isset($message[0])){ $this->errors['errors']['message'] = $message[0]; }
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
				# get the extended structure files with changes like navigation title or hidden pages
				$yaml = new writeYaml();
				$extended = $yaml->getYaml('cache', 'structure-extended.yaml');

				# create an array of object with the whole content of the folder and changes from extended file
				$structure = Folder::getFolderContentDetails($structure, $extended, $this->uri->getBaseUrl(), $this->uri->getBasePath());
			}
			
			# cache navigation
			$this->write->updateCache('cache', $filename, 'lastCache.txt', $structure);
		}
		
		$this->structure = $structure;
		return true;
	}

	protected function renameExtended($item, $newFolder)
	{
		# get the extended structure files with changes like navigation title or hidden pages
		$yaml = new writeYaml();
		$extended = $yaml->getYaml('cache', 'structure-extended.yaml');

		if(isset($extended[$item->urlRelWoF]))
		{
			$newUrl = $newFolder->urlRelWoF . '/' . $item->slug;

			$entry = $extended[$item->urlRelWoF];
			
			unset($extended[$item->urlRelWoF]);
			
			$extended[$newUrl] = $entry;
			$yaml->updateYaml('cache', 'structure-extended.yaml', $extended);
		}

		return true;
	}

	protected function deleteFromExtended()
	{
		# get the extended structure files with changes like navigation title or hidden pages
		$yaml = new writeYaml();
		$extended = $yaml->getYaml('cache', 'structure-extended.yaml');

		if($this->item->elementType == "file" && isset($extended[$this->item->urlRelWoF]))
		{
			unset($extended[$this->item->urlRelWoF]);
			$yaml->updateYaml('cache', 'structure-extended.yaml', $extended);
		}

		if($this->item->elementType == "folder")
		{
			$changed = false;

			# delete all entries with that folder url
			foreach($extended as $url => $entries)
			{
				if( strpos($url, $this->item->urlRelWoF) !== false )
				{
					$changed = true;
					unset($extended[$url]);
				}
			}

			if($changed)
			{
				$yaml->updateYaml('cache', 'structure-extended.yaml', $extended);
			}
		}
	}

	# this is only set by content backend controller
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

	protected function setItem()
	{
		# home is only set by backend controller, not by api calls
		$home = isset($this->homepage['active']) ? $this->homepage['active'] : false;

		# search for the url in the structure
		$item = Folder::getItemForUrl($this->structure, $this->params['url'], $this->uri->getBaseUrl(), NULL, $home);

		if($item)
		{
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
}