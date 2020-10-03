<?php

namespace Plugins\blurbbox;

use \Typemill\Plugin;

class blurbbox extends plugin
{	
    public static function getSubscribedEvents()
    {
		return array(
			'onPageReady'			=> 'onPageReady',
		);
	}
	
	public function onPageReady($page)
	{
		$pageData = $page->getData($page);

		$pageData['widgets']['blurbbox'] = '<div><p>Notes on (mostly) daily perceptions and experiences by Joseph Zitt, an American immigrant to Israel.</p>' .
											'<p><a href="http://www.josephzitt.com/home/books/as-if-in-dreams-notes-following-aliyah/">Buy the book.</a></p>' .
											'<p><a href="https://buttondown.email/josephzitt">Subscribe to the weekly newsletter.</a></p></div>'; 

 		$page->setData($pageData);
	}
}