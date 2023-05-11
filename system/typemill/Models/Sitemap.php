<?php

namespace Typemill\Models;

use Typemill\Models\StorageWrapper;


############## REFACTOR, it is similar or part of navigation

class Sitemap
{
	private $storage;

	public function __construct()
	{
		$this->storage 				= new StorageWrapper('\Typemill\Models\Storage');
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

	public function updateSitemap($ping = false)
	{
		$sitemap 	= '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$sitemap 	.= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		$sitemap	= $this->addUrlSet($sitemap, $this->uri->getBaseUrl());
		$sitemap	.= $this->generateUrlSets($this->structureLive);
		$sitemap 	.= '</urlset>';
		
		$this->writeCache->writeFile('cache', 'sitemap.xml', $sitemap);

		if($ping && isset($this->settings['pingsitemap']) && $this->settings['pingsitemap'])
		{
			$sitemapUrl			= $this->uri->getBaseUrl() . '/cache/sitemap.xml';

			$pingGoogleUrl		= 'http://www.google.com/ping?sitemap=' . urlencode($sitemapUrl);
			$pingBingUrl		= 'http://www.bing.com/ping?sitemap=' . urlencode($sitemapUrl);

			$opts = array(
			  'http'=>array(
			    'method'=>"GET",
				'ignore_errors' => true,
			    'timeout' => 5
			  )
			);

			$context 			= stream_context_create($opts);

			$responseBing 		= file_get_contents($pingBingUrl, false, $context);
			$responseGoogle 	= file_get_contents($pingGoogleUrl, false, $context);
		}

	}

	public function generateUrlSets($navigation)
	{
		$urlset = '';

		foreach($navigation as $item)
		{
			if($item->elementType == 'folder' && isset($item->noindex) && $item->noindex === true)
			{
				$urlset .= $this->generateUrlSets($item->folderContent, $urlset);
			}
			elseif($item->elementType == 'folder')
			{
				$urlset = $this->addUrlSet($urlset, $item->urlAbs);
				$urlset .= $this->generateUrlSets($item->folderContent, $urlset);				
			}
			elseif(isset($item->noindex) && $item->noindex === true )
			{
				continue;
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
}