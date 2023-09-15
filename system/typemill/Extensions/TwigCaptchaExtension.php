<?php

namespace Typemill\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Gregwar\Captcha\CaptchaBuilder;

class TwigCaptchaExtension extends AbstractExtension
{
	public function getFunctions()
	{
		return [
			new TwigFunction('captcha', array($this, 'captchaImage' ))
		];
	}
	
	public function captchaImage($initialize = false)
	{
		if(isset($_SESSION['captcha']) OR $initialize)
		{
			$builder = new CaptchaBuilder;
			$builder->build();

			if(isset($_SESSION['captcha']) && $_SESSION['captcha'] === 'error')
			{
				$template = '<div class="my-2 error">' .
								'<label for="captcha">Captcha</label>' .
								'<input type="text" name="captcha" class="form-control block w-full px-3 py-1.5 text-base font-normal text-gray-700 bg-white bg-clip-padding border border-solid border-red-500 bg-red-100 transition ease-in-out m-0 focus:text-gray-700 focus:bg-white focus:border-blue-600 focus:outline-none">' .
								'<span class="text-xs">The captcha was wrong.</span>'	.
								'<img class="captcha my-2" src="' . $builder->inline() . '" />' . 
							'</div>';
			}
			else
			{
				$template = '<div class="my-2">' .
								'<label for="captcha">Captcha</label>' .
								'<input type="text" name="captcha" class="form-control block w-full px-3 py-1.5 text-base font-normal text-gray-700 bg-white bg-clip-padding border border-solid border-gray-300 transition ease-in-out m-0 focus:text-gray-700 focus:bg-white focus:border-blue-600 focus:outline-none">' .
								'<img class="captcha my-2" src="' . $builder->inline() . '" />' . 
							'</div>';
			}

			$_SESSION['phrase'] = $builder->getPhrase();

			$_SESSION['captcha'] = true;

			return $template;
		}
	}
}