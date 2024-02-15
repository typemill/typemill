<?php

namespace Typemill\Models;

use Typemill\Models\StorageWrapper;
use Typemill\Static\Translations;

class License
{
	public $message = '';

	private $plans  = [
		'MAKER' => [
			'name' 	=> 'MAKER',
			'scope'	=> ['MAKER' => true]
		],
		'BUSINESS' => [
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

		if($domain && $date)
		{
			return $this->plans[$licensedata['plan']]['scope'];
		}

		return false;
	}

	public function refreshLicense()
	{
		# same as update
	}

	private function updateLicence()
	{
		# if license not valid anymore, check server for update
	}

	private function controlLicence()
	{
		# regularly check license on server each month.
	}

	public function getLicenseFields()
	{
		$storage 		= new StorageWrapper('\Typemill\Models\Storage');
		$licensefields 	= $storage->getYaml('systemSettings', '', 'license.yaml');

		return $licensefields;
	}

	# check the local licence file (like pem or pub)
	private function checkLicense()
	{
		$storage = new StorageWrapper('\Typemill\Models\Storage');

		$licensedata = $storage->getYaml('basepath', 'settings', 'license.yaml');

		if(!$licensedata)
		{
			$this->message = Translations::translate('no license found');

			return false;
		}

		if(!isset($licensedata['license'],$licensedata['email'],$licensedata['domain'],$licensedata['plan'],$licensedata['payed_until'],$licensedata['signature']))
		{
			$this->message = Translations::translate('License data incomplete');

			return false;
		}

		$licenseStatus = $this->validateLicense($licensedata);

		if($licenseStatus !== true)
		{
			$this->message = Translations::translate('Validation failed') . ': ' . $licenseStatus;

			return false;
		}

		# check here if payed until is in past
	    $nextBillDate 	= new \DateTime($licensedata['payed_until']);
	    $currentDate 	= new \DateTime();

	    if($nextBillDate < $currentDate) 
	    {
	    	$this->message = Translations::translate('The subscription period is not paid yet.');

	    	return false;
	    }

		unset($licensedata['signature']);

		return $licensedata;
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
			$this->message = Translations::translate('There was an error checking the license signature');

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

		$response = file_get_contents('https://service.typemill.net/api/v1/activate', false, $context);

		if(substr($http_response_header[0], -6) != "200 OK")
		{
			$this->message = Translations::translate('the license server responded with: ') . $http_response_header[0];

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

		$result = $storage->updateYaml('settingsFolder', '', 'license.yaml', $signedLicense['license']);

		if(!$result)
		{
			$this->message = $storage->getError();
			return false;
		}

		return true;
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

	# we have it in static license, too so use it from static and delete this duplicate.
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