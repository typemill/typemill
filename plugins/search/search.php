<?php

namespace Plugins\search;

use \Typemill\Plugin;
use \Typemill\Models\Write;

class Search extends index
{
	protected $item;
	
    public static function getSubscribedEvents()
    {
		return array(
			'onSettingsLoaded' 		=> 'onsettingsLoaded',
			'onContentArrayLoaded' 	=> 'onContentArrayLoaded',			
			'onPageReady'			=> 'onPageReady',
			'onPagePublished'		=> 'onPagePublished',
			'onPageUnpublished'		=> 'onPageUnpublished',
			'onPageSorted'			=> 'onPageSorted',
			'onPageDeleted'			=> 'onPageDeleted',			
		);
	}
	
	# get search.json with route
	# update search.json on publish

	public static function addNewRoutes()
	{
		# the route for the api calls
		return array(
			array(
				'httpMethod'    => 'get', 
				'route'         => '/indexrs51gfe2o2',
				'class'         => 'Plugins\search\index:index'
			),
		);
	}

	public function onSettingsLoaded($settings)
	{
		$this->settings = $settings->getData();
	}

	# at any of theses events, delete the old search index
	public function onPagePublished($item)
	{
		$this->deleteSearchIndex();
	}
	public function onPageUnpublished($item)
	{
		$this->deleteSearchIndex();
	}
	public function onPageSorted($inputParams)
	{
		$this->deleteSearchIndex();
	}
	public function onPageDeleted($item)
	{
		$this->deleteSearchIndex();
	}

	private function deleteSearchIndex()
	{
    	$write = new Write();

    	# store the index file here
    	$write->deleteFileWithPath('cache' . DIRECTORY_SEPARATOR . 'index.json');		
	}
	
	public function onContentArrayLoaded($contentArray)
	{
		# get content array
		$content 	= $contentArray->getData();
		$settings 	= $this->getPluginSettings('search');
		$salt 		= "asPx9Derf2";

		# activate axios and vue in frontend
		$this->activateAxios();
		$this->activateVue();

		# add the css and vue application
		$this->addCSS('/search/public/search.css');
		$this->addJS('/search/public/lunr.min.js');	
		$this->addJS('/search/public/search.js');

		# simple security for first request
		$secret = time();
		$secret = substr($secret,0,-1);
		$secret = md5($secret . $salt);

		# simple csrf protection with a session for long following requests
		if (session_status() == PHP_SESSION_NONE) {
		    session_start();
		}

		$length 					= 32;
		$token 						= substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, $length);
		$_SESSION['search'] 		= $token; 
		$_SESSION['search-expire'] 	= time() + 1300; # 60 seconds * 30 minutes

		# create div for vue app
		$search 	= '<div data-access="' . $secret . '" data-token="' . $token . '" id="searchresult"></div>';

		# create content type
		$search = Array
		(
			'rawHtml' => $search,
			'allowRawHtmlInSafeMode' => true,
			'autobreak' => 1
		);

		$content[] = $search;

		$contentArray->setData($content);
	}

	public function onPageReady($page)
	{
		$pageData = $page->getData($page);

		$pageData['widgets']['search'] = '<div class="searchContainer" id="searchForm">'.
	        									'<input id="searchField" type="text" placeholder="search ..." />'.
	        									'<button id="searchButton" type="button">GO</button>'.
    									'</div>';
 		$page->setData($pageData);
	}
}