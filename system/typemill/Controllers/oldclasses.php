<?php

class oldclasses
{
	protected $errors = [];




########### CHECK IF NEEDED IN CONTROLLER
	
	protected function setUrlCollection($uri)
	{
		$scheme 	= $uri->getScheme();
		$authority 	= $uri->getAuthority();
		$protocol 	= ($scheme ? $scheme . ':' : '') . ($authority ? '//' . $authority : '');

		$this->basePath 		= $this->c->get('basePath');
        $this->currentPath 		= $uri->getPath();
        $this->fullBaseUrl 		= $protocol . $this->basePath;
        $this->fullCurrentUrl 	= $protocol . $this->currentPath;

        $this->urlCollection	= [
        	'basePath' 				=> $this->basePath,
        	'currentPath' 			=> $this->currentPath,
        	'fullBaseUrl'			=> $this->fullBaseUrl,
        	'fullCurrentUrl'		=> $this->fullCurrentUrl
        ];
	}

	# render page for frontend
	protected function render($response, $route, $data)
	{
		# why commented this out??
		$data = $this->c->dispatcher->dispatch('onPageReady', new OnPageReady($data))->getData();

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
	
	# render 404 for frontend
	protected function render404($response, $data = NULL)
	{
		return $this->c->view->render($response->withStatus(404), '/404.twig', $data);
	}

	# render page for authors (admin-area)
	protected function renderIntern($response, $route, $data)
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
		
	# render 404 for authors
	protected function renderIntern404($response, $data = NULL)
	{
		return $this->c->view->render($response->withStatus(404), '/intern404.twig', $data);
	}




########### MOVE TO GLOBAL API ??

	public function clearCache($request, $response, $args)
	{
		$this->uri 			= $request->getUri()->withUserInfo('');
		$dir 				= $this->settings['basePath'] . 'cache';

		$error 				= $this->writeCache->deleteCacheFiles($dir);
		if($error)
		{
			return $response->withJson(['errors' => $error], 500);
		}

		# create a new draft structure
		$this->setFreshStructureDraft();

		# create a new draft structure
		$this->setFreshStructureLive();

		# create a new draft structure
		$this->setFreshNavigation();

		# update the sitemap
		$this->updateSitemap();

		return $response->withJson(array('errors' => false));
	}

############ CHECK IF NEEDED FOR API IMAGE ???

	protected function saveImages($imageFields, $userInput, $userSettings, $files)
	{
		# initiate image processor with standard image sizes
		$processImages = new ProcessImage($userSettings['images']);

		if(!$processImages->checkFolders('images'))
		{
			$this->c->flash->addMessage('error', 'Please make sure that your media folder exists and is writable.');
			return false; 
		}

		foreach($imageFields as $fieldName => $imageField)
		{
			if(isset($userInput[$fieldName]))
			{
				# handle single input with single file upload
    			$image = $files[$fieldName];
    		
    			if($image->getError() === UPLOAD_ERR_OK) 
    			{
    				# not the most elegant, but createImage expects a base64-encoded string.
    				$imageContent = $image->getStream()->getContents();
					$imageData = base64_encode($imageContent);
					$imageSrc = 'data: ' . $image->getClientMediaType() . ';base64,' . $imageData;

					if($processImages->createImage($imageSrc, $image->getClientFilename(), $userSettings['images'], $overwrite = NULL))
					{
						# returns image path to media library
						$userInput[$fieldName] = $processImages->publishImage();
					}
			    }
			}
		}
		return $userInput;
	}


############ NAVIGATION ???

	# reads the cached structure with published and non-published pages for the author
	# setStructureDraft
	protected function getStructureForAuthors($userrole, $username)
	{
		# get the cached structure
		$this->structureDraft = $this->writeCache->getCache('cache', $this->structureDraftName);

		# if there is no cached structure
		if(!$this->structureDraft)
		{
			return $this->setFreshStructureDraft();
		}

		return true;
	}

	# creates a fresh structure with published and non-published pages for the author
	# setFreshStrutureDraft
	protected function createNewStructureForAuthors()
	{
		# scan the content of the folder
		$pagetreeDraft = Folder::scanFolder($this->settings['rootPath'] . $this->settings['contentFolder'], $draft = true );

		# if there is content, then get the content details
		if(count($pagetreeDraft) > 0)
		{
			# get the extended structure files with changes like navigation title or hidden pages
			$yaml = new writeYaml();
			$extended = $this->getExtended();

			# create an array of object with the whole content of the folder and changes from extended file
			$this->structureDraft = Folder::getFolderContentDetails($pagetreeDraft, $extended, $this->settings, $this->uri->getBaseUrl(), $this->uri->getBasePath());
			
			# cache structure draft
			$this->writeCache->updateCache('cache', $this->structureDraftName, 'lastCache.txt', $this->structureDraft);

			return true;
		}

		return false;
	}

	# reads the cached structure of published pages
	# setStrutureLive
	protected function getStructureForReaders()
	{
		# get the cached structure
		$this->structureLive = $this->writeCache->getCache('cache', $this->structureLiveName);

		# if there is no cached structure
		if(!$this->structureLive)
		{
			return $this->setFreshStructureLive();
		}

		return true;
	}

	# creates a fresh structure with published pages
	protected function setFreshStructureLive()
	{
		# scan the content of the folder
		$pagetreeLive = Folder::scanFolder($this->settings['rootPath'] . $this->settings['contentFolder'], $draft = false );

		# if there is content, then get the content details
		if($pagetreeLive && count($pagetreeLive) > 0)
		{
			# get the extended structure files with changes like navigation title or hidden pages
			$yaml = new writeYaml();
			$extended = $this->getExtended();

			# create an array of object with the whole content of the folder and changes from extended file
			$this->structureLive = Folder::getFolderContentDetails($pagetreeLive, $extended, $this->settings, $this->uri->getBaseUrl(), $this->uri->getBasePath());
			
			# cache structure live
			$this->writeCache->updateCache('cache', $this->structureLiveName, 'lastCache.txt', $this->structureLive);

			return true;
		}

		return false;
	}

	# reads the live navigation from cache (live structure without hidden pages)
	protected function setNavigation()
	{
		# get the cached structure
		$this->navigation = $this->writeCache->getCache('cache', 'navigation.txt');

		# if there is no cached structure
		if(!$this->navigation)
		{
			return $this->setFreshNavigation();
		}

		return true;
	}

	# creates a fresh live navigation (live structure without hidden pages)
	protected function setFreshNavigation()
	{

		if(!$this->extended)
		{
			$extended = $this->getExtended();
		}

		if($this->containsHiddenPages($this->extended))
		{
			if(!$this->structureLive)
			{
				$this->setStructureLive();
			}

			$structureLive = $this->c->dispatcher->dispatch('onPagetreeLoaded', new OnPagetreeLoaded($this->structureLive))->getData();
			$this->navigation = $this->createNavigation($structureLive);

			# cache navigation
			$this->writeCache->updateCache('cache', 'navigation.txt', false, $this->navigation);
			
			return true;
		}

		# make sure no old navigation file is left
		$this->writeCache->deleteFileWithPath('cache' . DIRECTORY_SEPARATOR . 'navigation.txt');

		return false;
	}

	# create navigation from structure
	protected function createNavigation($structureLive)
	{
		foreach ($structureLive as $key => $element)
		{
			if($element->hide === true)
			{
				unset($structureLive[$key]);
			}
			elseif(isset($element->folderContent))
			{
				$structureLive[$key]->folderContent = $this->createNavigation($element->folderContent);
			}
		}
		
		return $structureLive;
	}
	
	
	# reads the cached structure with published and non-published pages for the author
	protected function setStructureDraft()
	{
		# get the cached structure
		$this->structureDraft = $this->writeCache->getCache('cache', $this->structureDraftName);

		# if there is no cached structure
		if(!$this->structureDraft)
		{
			return $this->setFreshStructureDraft();
		}

		return true;
	}

	# creates a fresh structure with published and non-published pages for the author
	protected function setFreshStructureDraft()
	{
		# scan the content of the folder
		$pagetreeDraft = Folder::scanFolder($this->settings['rootPath'] . $this->settings['contentFolder'], $draft = true );

		# if there is content, then get the content details
		if(count($pagetreeDraft) > 0)
		{
			# get the extended structure files with changes like navigation title or hidden pages
			$yaml = new writeYaml();
			$extended = $this->getExtended();

			# create an array of object with the whole content of the folder and changes from extended file
			$this->structureDraft = Folder::getFolderContentDetails($pagetreeDraft, $extended, $this->settings, $this->uri->getBaseUrl(), $this->uri->getBasePath());
			
			# cache structure draft
			$this->writeCache->updateCache('cache', $this->structureDraftName, 'lastCache.txt', $this->structureDraft);

			return true;
		}

		return false;
	}

	# reads the cached structure of published pages
	protected function setStructureLive()
	{
		# get the cached structure
		$this->structureLive = $this->writeCache->getCache('cache', $this->structureLiveName);

		# if there is no cached structure
		if(!$this->structureLive)
		{
			return $this->setFreshStructureLive();
		}

		return true;
	}

	# creates a fresh structure with published pages
	protected function setFreshStructureLive()
	{
		# scan the content of the folder
		$pagetreeLive = Folder::scanFolder($this->settings['rootPath'] . $this->settings['contentFolder'], $draft = false );

		# if there is content, then get the content details
		if($pagetreeLive && count($pagetreeLive) > 0)
		{
			# get the extended structure files with changes like navigation title or hidden pages
			$yaml = new writeYaml();
			$extended = $this->getExtended();

			# create an array of object with the whole content of the folder and changes from extended file
			$this->structureLive = Folder::getFolderContentDetails($pagetreeLive, $extended, $this->settings, $this->uri->getBaseUrl(), $this->uri->getBasePath());
			
			# cache structure live
			$this->writeCache->updateCache('cache', $this->structureLiveName, 'lastCache.txt', $this->structureLive);

			return true;
		}

		return false;
	}

	# reads the live navigation from cache (live structure without hidden pages)
	protected function setNavigation()
	{
		# get the cached structure
		$this->navigation = $this->writeCache->getCache('cache', 'navigation.txt');

		# if there is no cached structure
		if(!$this->navigation)
		{
			return $this->setFreshNavigation();
		}

		return true;
	}

	# creates a fresh live navigation (live structure without hidden pages)
	protected function setFreshNavigation()
	{

		if(!$this->extended)
		{
			$extended = $this->getExtended();
		}

		if($this->containsHiddenPages($this->extended))
		{
			if(!$this->structureLive)
			{
				$this->setStructureLive();
			}

			$structureLive = $this->c->dispatcher->dispatch('onPagetreeLoaded', new OnPagetreeLoaded($this->structureLive))->getData();
			$this->navigation = $this->createNavigation($structureLive);

			# cache navigation
			$this->writeCache->updateCache('cache', 'navigation.txt', false, $this->navigation);
			
			return true;
		}

		# make sure no old navigation file is left
		$this->writeCache->deleteFileWithPath('cache' . DIRECTORY_SEPARATOR . 'navigation.txt');

		return false;
	}

	# create navigation from structure
	protected function createNavigation($structureLive)
	{
		foreach ($structureLive as $key => $element)
		{
			if($element->hide === true)
			{
				unset($structureLive[$key]);
			}
			elseif(isset($element->folderContent))
			{
				$structureLive[$key]->folderContent = $this->createNavigation($element->folderContent);
			}
		}
		
		return $structureLive;
	}

	protected function getExtended()
	{
		$yaml = new writeYaml();

		if(!$this->extended)
		{
			$this->extended = $yaml->getYaml('cache', 'structure-extended.yaml');
		}

		if(!$this->extended)
		{
			# scan the content of the folder
			$pagetreeDraft = Folder::scanFolder($this->settings['rootPath'] . $this->settings['contentFolder'], $draft = true );

			# if there is content, then get the content details
			if(count($pagetreeDraft) == 0)
			{
				return false;
			}

			# create an array of object with the whole content of the folder and changes from extended file
			$structureDraft = Folder::getFolderContentDetails($pagetreeDraft, $extended = false, $this->settings, $this->uri->getBaseUrl(), $this->uri->getBasePath());

			$this->extended = $this->createExtended($this->settings['rootPath'] . $this->settings['contentFolder'], $yaml, $structureDraft);

			$yaml->updateYaml('cache', 'structure-extended.yaml', $this->extended);
		}

		return $this->extended;
	}

	# creates a file that holds all hide flags and navigation titles 
	# reads all meta-files and creates an array with url => ['hide' => bool, 'navtitle' => 'bla']
	public function createExtended($contentPath, $yaml, $structureLive, $extended = NULL)
	{
		if(!$extended)
		{
			$extended = [];
		}

		foreach ($structureLive as $key => $item)
		{
			# $filename = ($item->elementType == 'folder') ? DIRECTORY_SEPARATOR . 'index.yaml' : $item->pathWithoutType . '.yaml';
			$filename = $item->pathWithoutType . '.yaml';

			if(file_exists($contentPath . $filename))
			{
				# read file
				$meta = $yaml->getYaml('content', $filename);

				$extended[$item->urlRelWoF]['hide'] = isset($meta['meta']['hide']) ? $meta['meta']['hide'] : false;
				$extended[$item->urlRelWoF]['navtitle'] = isset($meta['meta']['navtitle']) ? $meta['meta']['navtitle'] : '';
			}

			if ($item->elementType == 'folder')
			{
				$extended = $this->createExtended($contentPath, $yaml, $item->folderContent, $extended);
			}
		}
		return $extended;
	}

	# only backoffice
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

	# only backoffice
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


	# checks if there is a hidden page, returns true on first find
	protected function containsHiddenPages($extended)
	{
		foreach($extended as $element)
		{
			if(isset($element['hide']) && $element['hide'] === true)
			{
				return true;
			}
		}
		return false;
	}	
}