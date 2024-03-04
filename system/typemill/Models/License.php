<?php

namespace Typemill\Models;

use Typemill\Models\StorageWrapper;
use Typemill\Static\Translations;

class License
{
	private $message = '';

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

	public function getLicenseFile()
	{
		$storage 		= new StorageWrapper('\Typemill\Models\Storage');

		$licensefile 	= $storage->getYaml('basepath', 'settings', 'license.yaml');

		if($licensefile)
		{
			return $licensefile;
		}

		$this->message 	= 'Error loading license: ' . $storage->getError();

		return false;
	}

	public function getLicenseFields()
	{
		$storage 		= new StorageWrapper('\Typemill\Models\Storage');
		$licensefields 	= $storage->getYaml('systemSettings', '', 'license.yaml');

		return $licensefields;
	}

	# used to activate or deactivate features that require a license
	public function getLicenseScope(array $urlinfo)
	{
		$licensedata 	= $this->getLicenseFile();
		if(!$licensedata)
		{
			return false;
		}

		# this means that a check (and update or call to cache timer) will take place when visit system license
		$licensecheck 	= $this->checkLicense($licensedata,$urlinfo);
		if(!$licensecheck)
		{
			return false;
		}

		return $this->plans[$licensedata['plan']]['scope'];
	}

	# check the local licence file (like pem or pub)
	public function checkLicense($licensedata, array $urlinfo, $forceUpdateCheck = NULL)
	{
		if(!isset(
			$licensedata['license'],
			$licensedata['email'],
			$licensedata['domain'],
			$licensedata['plan'],
			$licensedata['payed_until'],
			$licensedata['signature']
		))
		{
			$this->message = Translations::translate('License data are incomplete');

			return false;
		}

		# check if license data are valid and not manipulated
		$licenseStatus = $this->validateLicense($licensedata);

		if($licenseStatus !== true)
		{
			$this->message = Translations::translate('The license data are invalid.');

			return false;
		}

		# check if website uses licensed domain
		$licenseDomain = $this->checkLicenseDomain($licensedata['domain'], $urlinfo);

		if(!$licenseDomain)
		{
			$this->message = Translations::translate('The website is running not under the domain of your license.');

			return false;
		}

		# check if subscription period is paid
		$subscriptionPaid = $this->checkLicenseDate($licensedata['payed_until']);

	    if(!$subscriptionPaid) 
	    {
			$storage = new StorageWrapper('\Typemill\Models\Storage');
	    	if(!$forceUpdateCheck && !$storage->timeoutIsOver('licenseupdate', 3600))
	    	{
				$this->message = Translations::translate('The subscription period has not been paid yet. We will check it every 60 minutes.') . $this->message;

	    		return false;
	    	}

	    	$update = $this->updateLicense($licensedata);
	    	if(!$update)
	    	{
		    	$this->message = Translations::translate('The subscription period has not been paid yet and we got an error. ') . $this->message;
	
	    		return false;
	    	}
	    }

		return true;
	}

	private function checkLicenseDate(string $payed_until)
	{
		# check here if payed until is in past
	    $nextBillDate 	= new \DateTime($payed_until);
	    $currentDate 	= new \DateTime();

	    if($nextBillDate > $currentDate) 
	    {
	    	return true;
	    }

	    return false;
	}

	private function checkLicenseDomain(string $licensedomain, array $urlinfo)
	{
		$licensehost 		= parse_url($licensedomain, PHP_URL_HOST);
		$licensehost 		= str_replace("www.", "", $licensehost);

		$thishost 			= parse_url($urlinfo['baseurl'], PHP_URL_HOST);
		$thishost 			= str_replace("www.", "", $thishost);

		$whitelist 			= ['localhost', '127.0.0.1', 'typemilltest.', $licensehost];

		foreach($whitelist as $domain)
		{
			if(substr($thishost, 0, strlen($domain)) == $domain)
			{
				return true;
			}
		}

		return false;
	}

	private function validateLicense($data)
	{
	    $licensedata = [
			'email'				=> $this->hashMail($data['email']),
			'domain'			=> $data['domain'],
			'license'			=> $data['license'],
			'plan' 				=> $data['plan'],
			'payed_until' 		=> $data['payed_until']
	    ];
		
		ksort($licensedata);

		# test manipulate data
#		$licensedata['plan'] 	= 'wrong';
		
		$licensedata 			= json_encode($licensedata);

		# Check signature
		$public_key_pem 		= $this->getPublicKeyPem();

		if(!$public_key_pem)
		{
			$this->message 		= Translations::translate('We could not find or read the public_key.pem in the settings-folder.');

			return false;
		}

		$binary_signature 		= base64_decode($data['signature']);

		$verified 				= openssl_verify($licensedata, $binary_signature, $public_key_pem, OPENSSL_ALGO_SHA256);

		if ($verified == 1)
		{
		    return true;
		} 
		elseif ($verified == 0)
		{
			$this->message 		= Translations::translate('License validation failed');

		    return false;
		} 
		else
		{
			$this->message 		= Translations::translate('There was an error checking the license signature');

			return false;
		}
	}

	public function activateLicense($params)
	{
		# prepare data for call to licence server

		$readableMail 		= trim($params['email']);

		$licensedata = [ 
			'license'		=> $params['license'], 
			'email'			=> $this->hashMail($readableMail),
			'domain'		=> $params['domain']
		];

		$postdata 			= http_build_query($licensedata);

		$authstring 		= $this->getPublicKeyPem();
		$authstring 		= hash('sha256', substr($authstring, 0, 50));

		$options = array (
    		'http' => array (
        		'method' 		=> 'POST',
   				'ignore_errors' => true,
        		'header'		=> 	"Content-Type: application/x-www-form-urlencoded\r\n" .
									"Accept: application/json\r\n" .
									"Authorization: $authstring\r\n" .
									"Connection: close\r\n",
        		'content' 		=> $postdata
			)
    	);

		$context 			= stream_context_create($options);

		$response 			= file_get_contents('https://service.typemill.net/api/v1/activate', false, $context);

		$signedLicense 		= json_decode($response,true);

		if(substr($http_response_header[0], -6) != "200 OK")
		{
			$message 		= $http_response_header[0];
			if(isset($signedLicense['code'])  && ($signedLicense['code'] != ''))
			{
				$message 	= $signedLicense['code'];
			}
			$this->message 	= Translations::translate('Answer from license server: ') . $message;

			return false;
		}

		if(isset($signedLicense['code']))
		{
			$this->message 	= $signedLicense['code'];
			return false;
		}

		$signedLicense['license']['email'] = $readableMail;
		$storage = new StorageWrapper('\Typemill\Models\Storage');

		$result = $storage->updateYaml('settingsFolder', '', 'license.yaml', $signedLicense['license']);

		if(!$result)
		{
			$this->message = $storage->getError();
			return false;
		}

		return true;
	}

	# if license not valid anymore, check server for update
	private function updateLicense($data)
	{
		$readableMail 		= trim($data['email']);

		$licensedata = [ 
			'license'		=> $data['license'], 
			'email'			=> $this->hashMail($readableMail),
			'domain'		=> $data['domain'],
			'signature'		=> $data['signature'],
			'plan'			=> $data['plan'],
			'payed_until' 	=> $data['payed_until']
		];

		$postdata 			= http_build_query($licensedata);

		$authstring 		= $this->getPublicKeyPem();
		$authstring 		= hash('sha256', substr($authstring, 0, 50));

		$options = array (
    		'http' => array (
        		'method' 		=> 'POST',
   				'ignore_errors' => true,
        		'header'		=> 	"Content-Type: application/x-www-form-urlencoded\r\n" .
									"Accept: application/json\r\n" .
									"Authorization: $authstring\r\n" .
									"Connection: close\r\n",
        		'content' 		=> $postdata
			)
    	);

		$context 			= stream_context_create($options);

		$response 			= file_get_contents('https://service.typemill.net/api/v1/update', false, $context);

		$signedLicense 		= json_decode($response,true);

		if(substr($http_response_header[0], -6) != "200 OK")
		{
			$message 		= $http_response_header[0];

			if(isset($signedLicense['code'])  && ($signedLicense['code'] != ''))
			{
				$message 	= $signedLicense['code'];
			}

			# problem: if admin is on license website, then the first check has already been done on start in system and it has set timer, so the admin will never see this message from server.
			$this->message 	= Translations::translate('Answer from license server: ') . $message;

			return false;
		}

		if(isset($signedLicense['code']))
		{
			$this->message = $signedLicense['code'];

			return false;
		}

		$signedLicense['license']['email'] = $readableMail;
		$storage = new StorageWrapper('\Typemill\Models\Storage');

		$result = $storage->updateYaml('settingsFolder', '', 'license.yaml', $signedLicense['license']);

		if(!$result)
		{
			$this->message = 'We could not store the updated license: ' . $storage->getError();
			
			return false;
		}

		return true;
	}

	private function hashMail(string $mail)
	{
		return hash('sha256', trim($mail) . 'TYla5xa8JUur');
	}

	# we have it in static license, too so use it from static and delete this duplicate.
	public function getPublicKeyPem()
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