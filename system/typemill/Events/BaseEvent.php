<?php

namespace Typemill\Events;

#use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\Event;

class BaseEvent extends Event
#class BaseEvent extends GenericEvent
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