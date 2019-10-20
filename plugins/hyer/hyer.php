<?php

namespace Plugins\hyer;

use \Typemill\Plugin;

class Hyer extends Plugin
{
	protected $item;
	
    public static function getSubscribedEvents()
    {
		return array(
			'onSettingsLoaded' 		=> 'onsettingsLoaded',
			'onItemLoaded' 			=> 'onItemLoaded',
			'onContentArrayLoaded' 	=> 'onContentArrayLoaded',
		);
	}
	
	public static function addNewRoutes()
	{
		# the route for the api calls
		return array(
			array(
				'httpMethod'    => 'get', 
				'route'         => '/latestadsfe30s8edw4wdkp',
				'class'         => 'Plugins\hyer\getads:latest'
			),
			array(
				'httpMethod'    => 'get', 
				'route'         => '/proadsfe30s8edw4wdkp',
				'class'         => 'Plugins\hyer\getads:pro'
			),
			array(
				'httpMethod'    => 'get', 
				'route'         => '/searchadsfe30s8edw4wdkp',
				'class'         => 'Plugins\hyer\getads:search'
			)	
		);
	}

	public function onSettingsLoaded($settings)
	{
		$this->settings = $settings->getData();
	}
	
	public function onItemLoaded($item)
	{
		$this->item = $item->getData();
	}
	
	public function onContentArrayLoaded($contentArray)
	{
		# get content array
		$content 	= $contentArray->getData();
		$settings 	= $this->getPluginSettings('hyer');
		$path		= $this->getPath();
		$segment	= basename($path); # get the last url segment
		$urlmap 	= json_decode($settings['urlmap'],true); # to find the correct page to include app
		$directory 	= trim($settings['directory'],"/"); # the path for the directory
		$salt 		= "asPx9Derf2";

		# if we are on the directory page

		if(trim($path,"/") == trim($settings['directory'],"/"))
		{
			# activate axios and vue in frontend
			$this->activateAxios();
			$this->activateVue();
			$this->activateTachyons();

			# add the css and vue application
		    $this->addCSS('/hyer/public/hyer.css');
		    $this->addJS('/hyer/public/hyer.js');	

			$twig   = $this->getTwig();  // get the twig-object
			$loader = $twig->getLoader();  // get the twig-template-loader	
			$loader->addPath(__DIR__ . '/templates');
			$svg 	= $twig->fetch('/hyer.twig');

			# simple security for first request
			$secret = time();
			$secret = substr($secret,0,-1);
			$secret = md5($secret . $salt);

			# simple csrf protection with a session for long following requests
			session_start();
			$length 			= 32;
			$token 				= substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, $length);
			$_SESSION['hyer'] 	= $token; 
			$_SESSION['hyer-expire'] = time() + 1300; # 60 seconds * 30 minutes

			# use this to secure the api long term
			# $token = time();
			# $token = substr($token,0,-4);
			# $token = md5($token . $salt);

			$products = '';

			foreach($urlmap as $product)
			{
				$products .= $product . ',';
			}
			$products = trim($products, ",");

			# create div for vue app
			$textHyer 		= '<div data-access="' . $secret . '" data-token="' . $token . '" data-products="' . $products . '" id="hyerapp"><directory></directory></div>';

			# create content type
			$textHyer = Array
			(
				'rawHtml' => $svg . $textHyer,
				'allowRawHtmlInSafeMode' => true,
				'autobreak' => 1
			);

			$content[] = $textHyer;

		}
		# map the url with the map in settings
		elseif(isset($urlmap[$segment]))
		{
			# activate axios and vue in frontend
			$this->activateAxios();
			$this->activateVue();
			$this->activateTachyons();

			# add the css and vue application
		    $this->addCSS('/hyer/public/hyer.css');
		    $this->addJS('/hyer/public/hyer.js');	

			$twig   = $this->getTwig();  // get the twig-object
			$loader = $twig->getLoader();  // get the twig-template-loader	
			$loader->addPath(__DIR__ . '/templates');
			$svg 	= $twig->fetch('/hyer.twig');

			# use this to secure the api a bit
			$secret = time();
			$secret = substr($secret,0,-1);
			$secret = md5($secret . $salt);
		
			# create div for vue app
			$textHyer 		= '<div data-access="' . $secret . '" id="hyerapp"><premiumads></premiumads></div>';
			$textHeadline 	= (isset($settings['headline']) && !empty($settings['headline'])) ? $settings['headline'] : false;
			$textTeaser 	= (isset($settings['teaser']) && !empty($settings['teaser'])) ? $settings['teaser'] : false;

			# create content type
			$textHyer = Array
			(
				'rawHtml' => $svg . $textHyer,
				'allowRawHtmlInSafeMode' => true,
				'autobreak' => 1
			);

			if($textHeadline)
			{
				$textHeadline = array(
		            'name' => 'h2',
		            'text' => $textHeadline,
		            'handler' => 'line',
		            'attributes' => Array
		                (
		                    'id' => $textHeadline
		                )
				);
			}

			if($textTeaser)
			{
				$textTeaser = array(
            		'name' => 'p',
            		'handler' => Array
                	(
                    	'function' => 'lineElements',
                    	'argument' => $textTeaser,
                    	'destination' => 'elements'
                	)
				);
			}
						
			$length 		= count($content);
			$thisElement 	= 0;
			$pos 			= false;
			$i 				= 0;
			$position		= isset($settings['position']) ? $settings['position'] : 3;
			
			while($i < $length)
			{
				if($content[$i]['name'] == 'h2')
				{
					$thisElement = $thisElement + 1;
				}

				# place hyer app before the 3rd headline h2-level
				if($thisElement == $position)
				{
					$pos = $i;
					break;
				}
				$i++;
			}

			if($pos)
			{
				$start 		= array_slice($content, 0, $pos);
				$end 		= array_slice($content, $pos);
				if($textHeadline){ $start[] = $textHeadline; }
				if($textTeaser){ $start[] = $textTeaser; }
				$content 	= array_merge( $start, array($textHyer), $end );
			}
			else
			{
				if($textHeadline){ $content[] = $textHeadline; }
				if($textTeaser){ $content[] = $textTeaser; }
				$content[] = $textHyer;
			}
		}
		
		$contentArray->setData($content);
	}
}