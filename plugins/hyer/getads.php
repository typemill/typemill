<?php

namespace Plugins\hyer;

use \Typemill\Plugin;

class getads extends Plugin
{

    public static function getSubscribedEvents(){}

    # get only pro ads for product
	public function pro()
	{
		
		# get referrer url
		if(!isset($_SERVER['HTTP_REFERER']))
		{
			return $this->returnJsonError(['message' => 'Referrer is missing']);
		}

		# get the url parameter
		$input = $this->getParams();

		# create secret
		$secret = time();
		$secret = substr($secret,0,-1);
		$oldsecret = $secret -1;
		$secret = md5($secret . $this->getSalt());
		$oldsecret = md5($oldsecret . $this->getSalt());

		if(!isset($input['access']) OR ($input['access'] != $secret AND $input['access'] != $oldsecret))
		{
			return $this->returnJsonError(['message' => 'Secret is incorrect']);			
		}

		# get the last segment of url
		$referrer =	$_SERVER['HTTP_REFERER'];
		$segment = basename($referrer);

		# get the settings
		$settings = \Typemill\Settings::loadSettings();

		# get url-map to find the correct cms for requesting page
		$urlmap = json_decode($settings['settings']['plugins']['hyer']['urlmap'],true);

		# map the request with the map in settings
		if(!isset($urlmap[$segment]))
		{
			return $this->returnJsonError(['message' => 'We could not map that url']);
		}

		# make call
#        $hyerApi 		= 'http://localhost/typemillService/public/publicapi/proads';
		$hyerApi 		= 'https://service.cmsstash.de/publicapi/proads';
        $hyerRequest 	= ['product' => $urlmap[$segment]];

        $result = $this->makeApiCall($hyerRequest, $hyerApi);

        return $this->returnJson($result);
	}

	# get 10 latest pro ads
	public function latest()
	{
		# get the url parameter
		$input = $this->getParams();

		# create secret
		$secret = time();
		$secret = substr($secret,0,-1);
		$oldsecret = $secret -1;
		$secret = md5($secret . $this->getSalt());
		$oldsecret = md5($oldsecret . $this->getSalt());

		if(!isset($input['access']) OR ($input['access'] != $secret AND $input['access'] != $oldsecret))
		{
			return $this->returnJsonError(['message' => 'Secret is incorrect']);
		}

#        $hyerApi 		= 'http://localhost/typemillService/public/publicapi/latestproads';
        $hyerApi 		= 'https://service.cmsstash.de/publicapi/latestproads';
        $hyerRequest 	= [];

        $result = $this->makeApiCall($hyerRequest, $hyerApi);

        return $this->returnJson($result);
	}

	# search for ads from directory
	public function search()
	{
		session_start();

		# get the url parameter
		$input = $this->getParams();

		# validate input here

		# read session data for simple csrf check
		$token = isset($_SESSION['hyer']) ? $_SESSION['hyer'] : false;
		$time = isset($_SESSION['hyer-expire']) ? $_SESSION['hyer-expire'] : false;

		# perform simple security or csrf check
		if(!isset($input['token']) OR !$token OR !$time OR ($input['token'] != $token ) OR (time() >= $time))
		{
			return $this->returnJsonError(['message' => 'Die Sitzung ist abgelaufen, bitte laden Sie die Seite neu.']);
		}

#        $hyerApi 		= 'http://localhost/typemillService/public/publicapi/searchads';
        $hyerApi 		= 'https://service.cmsstash.de/publicapi/searchads';
        $hyerRequest 	= [];

        if(isset($input['product']) && $input['product'] != '')
        {
			if(!$this->validateLengthBetween($input['product'], 2, 50) OR !$this->validateHtml($input['product']))
			{
				return $this->returnJsonError(['message' => 'Product is invalid']);
			}
        	$hyerRequest['product'] = $input['product']; 
        }
        if(isset($input['region']) && $input['region'] != '')
        { 
			if(!$this->validateLengthBetween($input['region'], 2, 50) OR !$this->validateHtml($input['region']))
			{
				return $this->returnJsonError(['message' => 'Product is invalid']);
			}
        	$hyerRequest['region'] = $input['region']; 
        }

        $result = $this->makeApiCall($hyerRequest, $hyerApi);
	    
        if($result)
        {
	        return $this->returnJson($result);
        }
		return $this->returnJsonError(['message' => 'Kein Ergebnis']);
	}

	private function makeApiCall($hyerRequest, $hyerApi)
	{

		# get the settings
		$settings = \Typemill\Settings::loadSettings();

		# get api key from settings
		$apikey = $settings['settings']['plugins']['hyer']['apikey'];
		$siteid = $settings['settings']['plugins']['hyer']['siteid'];


        # use key 'http' even if you send the request to https://...
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n" . "Authorization: Basic " . base64_encode($siteid . ":". $apikey),
                'method'  => 'GET',
                'content' => http_build_query($hyerRequest),
                'timeout' => 5
            )
        );

        ini_set('allow_url_fopen', '1');

        $context  	= stream_context_create($options);
        $result 	= file_get_contents($hyerApi, false, $context);
        $result		= json_decode($result);
        
        ini_set('allow_url_fopen', '0');

        return $result;
	}

	private function validateHtml($value)
	{
		if($value == strip_tags($value))
		{
			return true;
		}
		return false;
	}

    private function validateLengthBetween($value, $min, $max)
    {
        $length = $this->stringLength($value);

        return ($length !== false) && $length >= $min && $length <= $max;
    }

    private function stringLength($value)
    {
        if (!is_string($value)) {
            return false;
        } elseif (function_exists('mb_strlen')) {
            return mb_strlen($value);
        }

        return strlen($value);
    }

    private function getSalt()
    {
    	return "asPx9Derf2";
    }

}