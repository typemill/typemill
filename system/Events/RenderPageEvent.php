<?php

namespace Typemill\Events;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event for the pure content.
 */

class RenderPageEvent extends Event
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;		
    }
	
	public function setData($data)
	{
		$this->data = $data;
	}
}