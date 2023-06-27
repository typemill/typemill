<?php 

namespace Typemill\Static;
use Typemill\Models\StorageWrapper;

class License
{
	public static function getLicenseData()
	{
		# returns data for settings page
	}

	public static function getLicensePlan()
	{
		# returns plan for plugins
	}

	# check the local licence file (like pem or pub)
	public static function checkLicense()
	{
		$storage = new StorageWrapper('\Typemill\Models\Storage');

		$licensedata = $storage->getYaml('basepath', 'settings', 'license.yaml');

		if(!$licensedata)
		{
			return ['result' => false, 'message' => 'no license found'];
		}

		if(!isset($licensedata['license'],$licensedata['email'],$licensedata['domain'],$licensedata['plan'],$licensedata['payed_until'],$licensedata['signature']))
		{
			return ['result' => false, 'message' => 'License data not complete'];
		}

		$licenseStatus = self::validateLicense($licensedata);

		unset($licensedata['signature']);

		if($licenseStatus === false)
		{
			return ['result' => false, 'message' => 'License data are invalid'];
		}
		elseif($licenseStatus === true)
		{
			echo '<pre>';
			print_r($licensedata);
			die();
		}
		else
		{
			die('error checking signature');
		}
	}

	public static function validateLicense($data)
	{
		$public_key_pem 	= self::getPublicKeyPem();

		$binary_signature 	= base64_decode($data['signature']);

		$data['email'] 		= self::hashMail($data['email']);
		unset($data['signature']);

		# manipulate data
		# $data['product'] 	= 'business';

		$data = json_encode($data);

		# Check signature
		$verified = openssl_verify($data, $binary_signature, $public_key_pem, OPENSSL_ALGO_SHA256);

		if ($verified == 1)
		{
		    return true;
		} 
		elseif ($verified == 0)
		{
		    return false;
		} 
		else
		{
		    die("ugly, error checking signature");
		}
	}

	public static function hashMail($mail)
	{
		return hash('sha256', trim($mail) . 'TYla5xa8JUur');
	}

	public static function getPublicKeyPem()
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