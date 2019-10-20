<?php

namespace Typemill\Models;

class WriteSitemap extends Write
{
	public function updateSitemap($folderName, $sitemapFileName, $requestFileName, $data, $baseUrl)
	{
		$sitemap 	= '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$sitemap 	.= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		$sitemap	= $this->addUrlSet($sitemap, $baseUrl);
		$sitemap	.= $this->generateUrlSets($data);
		$sitemap 	.= '</urlset>';
		
		$this->writeFile($folderName, $sitemapFileName, $sitemap);
		$this->writeFile($folderName, $requestFileName, time());
	}
		
	public function generateUrlSets($data)
	{		
		$urlset = '';
		
		foreach($data as $item)
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
}