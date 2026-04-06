<?php

namespace Typemill\Events;

use Symfony\Component\EventDispatcher\Event;

class OnShortcodeFound extends BaseEvent
{

	# allowed structure of returned data: 
	# $shortcodeArray['data']['embed'] = [ 'url' => '', 'params' => ''];

/*
	public function setData($data)
	{
		# validate and fix data structure here 
		$this->data = $data;
	}
*/

}