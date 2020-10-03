<?php

namespace Plugins\search;

use \Typemill\Plugin;
use \Typemill\Models\Write;
use \Typemill\Models\WriteCache;

class Index extends Plugin
{
    public static function getSubscribedEvents(){}	

    public function index()
    {
		$write = new Write();

		$index = $write->getFile('cache', 'index.json');
		if(!$index)
		{
			$this->createIndex();
			$index = $write->getFile('cache', 'index.json');
		}
	
		return $this->returnJson($index);
    }

    private function createIndex()
    {
    	$write = new WriteCache();

    	# get content structure
    	$structure = $write->getCache('cache', 'structure.txt');

    	# get data for search-index
    	$index = $this->getAllContent($structure, $write);

    	# store the index file here
    	$write->writeFile('cache', 'index.json', json_encode($index, JSON_UNESCAPED_SLASHES));
    }

    private function getAllContent($structure, $write, $index = NULL)
    {
    	foreach($structure as $item)
    	{
    		if($item->elementType == "folder")
    		{
    			if($item->fileType == 'md')
    			{
 		   			$page = $write->getFileWithPath('content' . $item->path . DIRECTORY_SEPARATOR . 'index.md');
 		   			$pageArray = $this->getPageContentArray($page, $item->urlAbs); 
    				$index[$pageArray['url']] = $pageArray;
    			}

	    		$index = $this->getAllContent($item->folderContent, $write, $index);
    		}
    		else
    		{
    			$page = $write->getFileWithPath('content' . $item->path);
 		   		$pageArray = $this->getPageContentArray($page, $item->urlAbs); 
    			$index[$pageArray['url']] = $pageArray;
    		}
    	}
    	return $index;
    }

    private function getPageContentArray($page, $url)
    {
    	$parts = explode("\n", $page, 2);

	    # get the title / headline
    	$title = trim($parts[0], '# ');
    	$title = str_replace(["\r\n", "\n", "\r"],' ', $title);

    	# get and cleanup the content
    	$content = $parts[1];
    	$content = strip_tags($content);
    	$content = str_replace(["\r\n", "\n", "\r"],' ', $content);

    	$pageContent = [
    		'title' 	=> $title,
    		'content' 	=> $content,
    		'url'		=> $url
    	];

    	return $pageContent;
    }
}