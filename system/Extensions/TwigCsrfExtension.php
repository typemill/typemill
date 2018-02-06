<?php

namespace Typemill\Extensions;

use Slim\Csrf\Guard;

class TwigCsrfExtension extends \Twig_Extension
{
	
	protected $guard;
	
	public function __construct(Guard $guard)
	{
		$this->guard = $guard;
	}
	
	public function getFunctions()
	{
		return [
			new \Twig_SimpleFunction('csrf_field', array($this, 'csrfField' ))		
		];
	}
	
	public function csrfField()
	{	
		return '
			<input type="hidden" id="' . $this->guard->getTokenNameKey() . '" name="' . $this->guard->getTokenNameKey() . '" value="' . $this->guard->getTokenName() . '">
			<input type="hidden" id="' . $this->guard->getTokenValueKey() . '" name="' . $this->guard->getTokenValueKey() . '" value="' . $this->guard->getTokenValue() . '">		
		';
	}
}