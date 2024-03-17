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


	# THE FOLLOWING METHODS INTERACT WITH LICENSE SERVER

	public function testLicenseCall()
	{
		# make the call to the license server
		$url 				= 'https://service.typemill.net/api/v1/testcall';
		$testcall 			= $this->callLicenseServer(['test' => 'test'], $url);

		if(!$testcall)
		{
			return false;
		}

		return true;
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

		# make the call to the license server
		$url 				= 'https://service.typemill.net/api/v1/activate';
		$signedLicense 		= $this->callLicenseServer($licensedata, $url);

		if(!$signedLicense)
		{
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

		# make the call to the license server
		$url 				= 'https://service.typemill.net/api/v1/update';
		$signedLicense 		= $this->callLicenseServer($licensedata, $url);

		if(!$signedLicense)
		{
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

	private function callLicenseServer( $licensedata, $url )
	{
		$authstring 		= $this->getPublicKeyPem();

		if(!$authstring)
		{
			$this->message 	= Translations::translate('Please check if there is a readable file public_key.pem in your settings folder.');

			return false;
		}

		$authstring 		= hash('sha256', substr($authstring, 0, 50));

		$postdata 			= http_build_query($licensedata);

		if(in_array('curl', get_loaded_extensions()))
		{
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
			curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			    "Content-Type: application/x-www-form-urlencoded",
			    "Accept: application/json",
			    "Authorization: $authstring",
			    "Connection: close"
			));

			$response = curl_exec($curl);

			if (curl_errno($curl))
			{
				$this->message 	= Translations::translate('We got a curl error: ') . curl_error($curl);

				return false;
			}

			curl_close($curl);
		}
		else
		{
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

			$context = stream_context_create($options);

			$response = file_get_contents($url, false, $context);

			if ($response === FALSE)
			{
			    if (!empty($http_response_header))
			    {
			        list($version, $status_code, $msg) = explode(' ', $http_response_header[0], 3);
			        
					$this->message 	= Translations::translate('We got an error from file_get_contents: ') . $status_code . ' ' . $msg;
			    }
			    else
			    {
					$this->message 	= Translations::translate('No HTTP response received or file_get_contents is blocked.');
			    }

				return false;
			}
		}

		$responseJson = json_decode($response,true);

		if(isset($responseJson['code']))
		{
			$this->message 	= $responseJson['code'];
		
			return false;
		}

		return $responseJson;
	}

	private function hashMail(string $mail)
	{
		return hash('sha256', trim($mail) . 'TYla5xa8JUur');
	}

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