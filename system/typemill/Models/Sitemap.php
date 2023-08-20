<?php

namespace Typemill\Models;

use Typemill\Models\StorageWrapper;

class Sitemap
{
	private $storage;

	public function __construct()
	{
		$this->storage 				= new StorageWrapper('\Typemill\Models\Storage');
	}

	public function updateSitemap($navigation, $urlinfo)
	{
		$sitemap 	= '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$sitemap 	.= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		$sitemap	= $this->addUrlSet($sitemap, $urlinfo['baseurl']);
		$sitemap	.= $this->generateUrlSets($navigation);
		$sitemap 	.= '</urlset>';
		
		$this->storage->writeFile('cacheFolder', '', 'sitemap.xml', $sitemap);
	}

	public function generateUrlSets($navigation)
	{
		$urlset = '';

		foreach($navigation as $item)
		{
			if($item->status == "published" OR $item->status == "modified")
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