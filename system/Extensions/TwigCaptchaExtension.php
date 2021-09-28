<?php

namespace Typemill\Extensions;

use Gregwar\Captcha\CaptchaBuilder;

class TwigCaptchaExtension extends \Twig_Extension
{
	public function getFunctions()
	{
		return [
			new \Twig_SimpleFunction('captcha', array($this, 'captchaImage' ))
		];
	}
	
	public function captchaImage($initialize = false)
	{

		if(isset($_SESSION['captcha']) OR $initialize)
		{
			$builder = new CaptchaBuilder;
			$builder->build();

			$error = '';
			if(isset($_SESSION['captcha']) && $_SESSION['captcha'] === 'error')
			{
				$error = '<span class="error">The captcha was wrong.</span>';
			}

			$_SESSION['phrase'] = $builder->getPhrase();

			$_SESSION['captcha'] = true;

			$template = '<div class="formElement">' .
							'<label for="captcha">Captcha</label>' .
							'<input type="text" name="captcha">' .
							$error .
							'<img class="captcha" src="' . $builder->inline() . '" />' . 
						'</div>';

			return $template;
		}
	}
}