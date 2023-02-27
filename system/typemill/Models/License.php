<?php

namespace Typemill\Models;

use Typemill\Models\StorageWrapper;

class License
{
	public $message = '';

	private $plans  = [
		'44699' => [
			'name' 	=> 'MAKER',
			'scope'	=> ['MAKER' => true]
		],
		'33334' => [
			'name' 	=> 'BUSINESS',
			'scope'	=> ['MAKER' => true, 'BUSINESS' => true]
		]
	];

	public function getMessage()
	{
		return $this->message;
	}

	# used for license management in admin settings
	public function getLicenseData(array $urlinfo)
	{
		# returns data for settings page
		$licensedata = $this->checkLicense();
		if($licensedata)
		{
			$licensedata['plan'] 		= $this->plans[$licensedata['plan']]['name'];
			$licensedata['domaincheck']	= $this->checkLicenseDomain($licensedata['domain'], $urlinfo);
			$licensedata['datecheck'] 	= $this->checkLicenseDate($licensedata['payed_until']);

			return $licensedata;
		}

		return false;
	}

	# used to activate or deactivate features that require a license
	public function getLicenseScope(array $urlinfo)
	{
		$licensedata 	= $this->checkLicense();

		if(!$licensedata)
		{
			return false;
		}

		$domain 		= $this->checkLicenseDomain($licensedata['domain'], $urlinfo);
		$date 			= $this->checkLicenseDate($licensedata['payed_until']);

		$domain = true;

		if($domain && $date)
		{
			return $this->plans[$licensedata['plan']]['scope'];
		}

		return false;
	}

	public function refreshLicense()
	{

	}

	# check the local licence file (like pem or pub)
	private function checkLicense()
	{
		$storage = new StorageWrapper('\Typemill\Models\Storage');

		$licensedata = $storage->getYaml('settings', 'license.yaml');

		if(!$licensedata)
		{
			$this->message = 'no license found';

			return false;
		}

		if(!isset($licensedata['license'],$licensedata['email'],$licensedata['domain'],$licensedata['plan'],$licensedata['payed_until'],$licensedata['signature']))
		{
			$this->message = 'License data incomplete';

			return false;
		}
		$licenseStatus = $this->validateLicense($licensedata);

		if($licenseStatus === true)
		{
			unset($licensedata['signature']);

			# check here if payed until is in past
			return $licensedata;
		}

		return false;
	}

	private function validateLicense($data)
	{
		$public_key_pem 	= $this->getPublicKeyPem();

		$binary_signature 	= base64_decode($data['signature']);

		$data['email'] 		= $this->hashMail($data['email']);
		unset($data['signature']);

		# test manipulate data
		#$data['plan'] 		= 'wrong';

		$data = json_encode($data);

		# Check signature
		$verified = openssl_verify($data, $binary_signature, $public_key_pem, OPENSSL_ALGO_SHA256);

		if ($verified == 1)
		{			
		    return true;
		} 
		elseif ($verified == 0)
		{
			$this->message = 'License data are invalid';

		    return false;
		} 
		else
		{
			$this->message = 'There was an error checking the license signature';

			return false;
		}
	}

	public function activateLicense($params)
	{
		# prepare data for call to licence server
		$licensedata = [ 
			'license'		=> $params['license'], 
			'email'			=> $this->hashMail($params['email']),
			'domain'		=> $params['domain']
		];

		$postdata = http_build_query($licensedata);

		$authstring = $this->getPublicKeyPem();
		$authstring = hash('sha256', substr($authstring, 0, 50));

		$options = array (
    		'http' => array (
        		'method' 	=> 'POST',
   				'ignore_errors' => true,
        		'header'	=> 	"Content-Type: application/x-www-form-urlencoded\r\n" .
								"Accept: application/json\r\n" .
								"Authorization: $authstring\r\n" .
								"Connection: close\r\n",
        		'content' 	=> $postdata
			)
    	);

		$context = stream_context_create($options);

		$response = file_get_contents('https://service.typemill.net/api/v2/activate', false, $context);

		if(substr($http_response_header[0], -6) != "200 OK")
		{
			$this->message = 'the license server responded with: ' . $http_response_header[0];

			return false;
		}

		$signedLicense = json_decode($response,true);

		if(isset($signedLicense['code']))
		{
#			$this->message = 'Something went wrong. Please check your input data or contact the support.';
			$this->message = $signedLicense['code'];
			return false;
		}

/*
		# check for positive and validate response data
		if($signedLicense['license'])
		{
			$this->message = ;
		}
*/
		$signedLicense['license']['email'] = trim($params['email']);
		$storage = new StorageWrapper('\Typemill\Models\Storage');

		$storage->updateYaml('settings', 'license.yaml', $signedLicense['license']);

		return true;
	}

	private function updateLicence()
	{
		# todo
	}

	private function checkLicenseDomain(string $licensedomain, array $urlinfo)
	{
		$licensehost 		= parse_url($licensedomain, PHP_URL_HOST);
		$licensehost 		= str_replace("www.", "", $licensehost);

		$thishost 			= parse_url($urlinfo['baseurl'], PHP_URL_HOST);
		$thishost 			= str_replace("www.", "", $thishost);

		$whitelist = ['localhost', '127.0.0.1', 'typemilltest.', $licensehost];

		foreach($whitelist as $domain)
		{
			if(substr($thishost, 0, strlen($domain)) == $domain)
			{
				return true;
			}
		}

		return false;
	}

	private function checkLicenseDate(string $payed_until)
	{
		if(strtotime($payed_until) > strtotime(date('Y-m-d')))
		{
			return true;
		}
		return false;
	}

	private function hashMail(string $mail)
	{
		return hash('sha256', trim($mail) . 'TYla5xa8JUur');
	}

	private function getPublicKeyPem()
	{
		$pkeyfile = getcwd() . DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR . "public_key.pem";

		if(file_exists($pkeyfile) && is_readable($pkeyfile))
		{
			# fetch public key from file and ready it
			$fp 				= fopen($pkeyfile, "r");
			$public_key_pem 	= fread($fp, 8192);
			fclose($fp);

			return $public_key_pem;
		}

		return false;
	}

}