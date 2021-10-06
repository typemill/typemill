<?php

namespace Typemill\Controllers;

use Psr\Container\ContainerInterface;
use Typemill\Models\Folder;
use Typemill\Models\WriteCache;
use Typemill\Models\WriteYaml;
use Typemill\Events\OnPageReady;

abstract class ControllerShared
{
	# holds the pimple container
	protected $c;

	# holds the settings
	protected $settings;
	
	# holds the write cache object
	protected $writeCache;
		
	# holds the structure of content folder as a serialized array of objects 
	protected $structureDraft = false;

	# holds the structure of content folder as a serialized array of objects 
	protected $structureLive = false;
	
	# holds the name of the structure-file with drafts for author environment 
	protected $structureDraftName = 'structure-draft.txt';

	# holds the name of the structure-file without drafts for live site 
	protected $structureLiveName = 'structure.txt';

	# holds the frontend navigation without hidden pages
	protected $navigation = false;

	# holds the list of pages with navigation titles and hidden pages. It extends the structures and navigations
	protected $extended = false;

	public function __construct(ContainerInterface $c)
	{
		$this->c 					= $c;
		$this->settings				= $this->c->get('settings');

		# used everywhere so instantiate it
		$this->writeCache 			= new writeCache();

		$this->c->dispatcher->dispatch('onTwigLoaded');
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
		if(count($pagetreeLive) > 0)
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

			$structureLive = $this->structureLive;
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

	# controllerFrontendWebsite, but not in use, makes no sense to check on each page load
	public function checkSitemap()
	{
		if(!$this->writeCache->getCache('cache', 'sitemap.xml'))
		{
			if(!$this->structureLive)
			{
				$this->setStructureLive();
			}

			$this->updateSitemap();
		}

		return true;
	}

	public function updateSitemap()
	{
		$sitemap 	= '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$sitemap 	.= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		$sitemap	= $this->addUrlSet($sitemap, $this->uri->getBaseUrl());
		$sitemap	.= $this->generateUrlSets($this->structureLive);
		$sitemap 	.= '</urlset>';
		
		$this->writeCache->writeFile('cache', 'sitemap.xml', $sitemap);
	}

	public function generateUrlSets($structureLive)
	{		
		$urlset = '';
		
		foreach($structureLive as $item)
		{
			if($item->elementType == 'folder')
			{
				$urlset = $this->addUrlSet($urlset, $item->urlAbs);
				$urlset .= $this->generateUrlSets($item->folderContent, $urlset);
			}
			else
			{
				$urlset = $this->addUrlSet($urlset, $item->urlAbs);
			}
		}
		return $urlset;
	}
	
	public function addUrlSet($urlset, $url)
	{
		$urlset .= '  <url>' . "\n";
		$urlset .= '    <loc>' . $url . '</loc>' . "\n";
		$urlset .= '  </url>' . "\n";
		return $urlset;
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