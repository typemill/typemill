<?php

namespace Typemill\Extensions;

use Twig\Extension\AbstractExtension;
use Slim\Csrf\Guard;
 
class TwigCsrfExtension extends AbstractExtension
{	
	protected $csrf;

	public function __construct(Guard $csrf)
	{
		$this->csrf = $csrf;
	}

	public function getFunctions()
	{
		return [
			new \Twig\TwigFunction('csrf', [$this, 'csrf'])
		];
	}
	
	public function csrf()
	{
		$csrf = '<p>TokenNameValue: '. $this->csrf->getTokenName() .'</p><input type="hidden" id="csrf_name" name="' . $this->csrf->getTokenNameKey(). '" value="' . $this->csrf->getTokenName() . '"> 
				<input type="hidden" id="csrf_value" name="' . $this->csrf->getTokenValueKey(). '" value="' . $this->csrf->getTokenValue(). '">';
		
		return $csrf;
	}
}