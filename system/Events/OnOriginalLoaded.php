<?php

namespace Typemill\Events;

use Symfony\Component\EventDispatcher\Event;

use Typemill\Extensions\ParsedownExtension;

/**
 * Event for html page.
 */

class OnOriginalLoaded extends Event
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getMarkdown()
    {
        return $this->data;
    }
	
	public function getHTML($urlrel)
	{
		$parsedown 		= new ParsedownExtension();
		$contentArray 	= $parsedown->text($this->data);
		$contentHTML 	= $parsedown->markup($contentArray, $urlrel);

		return $contentHTML;
	}
}