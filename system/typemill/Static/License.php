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



/* KIRBY -> source -> cms -> system.php

	/**
	 * Loads the license file and returns
	 * the license information if available
	 *
	 * @return string|bool License key or `false` if the current user has
	 *                     permissions for access.settings, otherwise just a
	 *                     boolean that tells whether a valid license is active
	 
	public function license()
	{
		try {
			$license = Json::read($this->app->root('license'));
		} catch (Throwable) {
			return false;
		}

		// check for all required fields for the validation
		if (isset(
			$license['license'],
			$license['order'],
			$license['date'],
			$license['email'],
			$license['domain'],
			$license['signature']
		) !== true) {
			return false;
		}

		// build the license verification data
		$data = [
			'license' => $license['license'],
			'order'   => $license['order'],
			'email'   => hash('sha256', $license['email'] . 'kwAHMLyLPBnHEskzH9pPbJsBxQhKXZnX'),
			'domain'  => $license['domain'],
			'date'    => $license['date']
		];


		// get the public key
		$pubKey = F::read($this->app->root('kirby') . '/kirby.pub');

		// verify the license signature
		$data      = json_encode($data);
		$signature = hex2bin($license['signature']);
		if (openssl_verify($data, $signature, $pubKey, 'RSA-SHA256') !== 1) {
			return false;
		}

		// verify the URL
		if ($this->licenseUrl() !== $this->licenseUrl($license['domain'])) {
			return false;
		}

		// only return the actual license key if the
		// current user has appropriate permissions
		if ($this->app->user()?->isAdmin() === true) {
			return $license['license'];
		}

		return true;
	}


	/**
	 * Validates the license key
	 * and adds it to the .license file in the config
	 * folder if possible.
	 *
	 * @throws \Kirby\Exception\Exception
	 * @throws \Kirby\Exception\InvalidArgumentException
	 *
	public function register(string $license = null, string $email = null): bool
	{
		if (Str::startsWith($license, 'K3-PRO-') === false) {
			throw new InvalidArgumentException(['key' => 'license.format']);
		}

		if (V::email($email) === false) {
			throw new InvalidArgumentException(['key' => 'license.email']);
		}

		// @codeCoverageIgnoreStart
		$response = Remote::get('https://hub.getkirby.com/register', [
			'data' => [
				'license' => $license,
				'email'   => Str::lower(trim($email)),
				'domain'  => $this->indexUrl()
			]
		]);

		if ($response->code() !== 200) {
			throw new Exception($response->content());
		}

		// decode the response
		$json = Json::decode($response->content());

		// replace the email with the plaintext version
		$json['email'] = $email;

		// where to store the license file
		$file = $this->app->root('license');

		// save the license information
		Json::write($file, $json);

		if ($this->license() === false) {
			throw new InvalidArgumentException([
				'key' => 'license.verification'
			]);
		}
		// @codeCoverageIgnoreEnd

		return true;
	}

*/