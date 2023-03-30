<?php

namespace Typemill\Controllers;

use Typemill\Models\StorageWrapper;
use Typemill\Models\User;
use Typemill\Models\License;

class ControllerWebSystem extends ControllerData
{	
	public function showSettings($request, $response, $args)
	{
		$storage 		= new StorageWrapper('\Typemill\Models\Storage');
		$systemfields 	= $storage->getYaml('systemSettings', '', 'system.yaml');
		$translations 	= $this->c->get('translations');

		# add full url for sitemap to settings
		$this->settings['sitemap'] = $this->c->get('urlinfo')['baseurl'] . '/cache/sitemap.xml';

	    return $this->c->get('view')->render($response, 'system/system.twig', [
#			'basicauth'			=> $user->getBasicAuth(),
			'settings' 			=> $this->settings,
			'mainnavi'			=> $this->getMainNavigation($request->getAttribute('c_userrole')),
			'systemnavi'		=> $this->getSystemNavigation($request->getAttribute('c_userrole')),
			'jsdata' 			=> [
										'settings' 		=> $this->settings,
										'system'		=> $systemfields,
										'labels'		=> $translations,
										'urlinfo'		=> $this->c->get('urlinfo')
									]
#			'captcha' => $this->checkIfAddCaptcha(),
	    ]);
	}

	public function showThemes($request, $response, $args)
	{
		$storage 			= new StorageWrapper('\Typemill\Models\Storage');
		$translations 		= $this->c->get('translations');
		$themeDefinitions 	= $this->getThemeDetails();

		$themeSettings = [];
		foreach($this->settings['themes'] as $themename => $themeinputs)
		{
			$themeSettings[$themename] = $themeinputs;
			$themeSettings[$themename]['customcss'] = $storage->getFile('cacheFolder', '', $themename . '-custom.css');
		}

		$license = [];
		if(is_array($this->settings['license']))
		{
			$license = array_keys($this->settings['license']);
		}

	    return $this->c->get('view')->render($response, 'system/themes.twig', [
			'settings' 			=> $this->settings,
			'mainnavi'			=> $this->getMainNavigation($request->getAttribute('c_userrole')),
			'systemnavi'		=> $this->getSystemNavigation($request->getAttribute('c_userrole')),
			'jsdata' 			=> [
										'settings' 		=> $themeSettings,
										'definitions'	=> $themeDefinitions,
										'theme'			=> $this->settings['theme'],
										'license' 		=> $license,
										'labels'		=> $translations,
										'urlinfo'		=> $this->c->get('urlinfo')
									]
	    ]);
	}

	public function showPlugins($request, $response, $args)
	{
		$translations 		= $this->c->get('translations');
		$pluginDefinitions 	= $this->getPluginDetails();
		
		$pluginSettings = [];

		foreach($this->settings['plugins'] as $pluginname => $plugininputs)
		{
			$pluginSettings[$pluginname] = $plugininputs;
		}

		$license = [];
		if(is_array($this->settings['license']))
		{
			$license = array_keys($this->settings['license']);
		}

	    return $this->c->get('view')->render($response, 'system/plugins.twig', [
			'settings' 			=> $this->settings,
			'mainnavi'			=> $this->getMainNavigation($request->getAttribute('c_userrole')),
			'systemnavi'		=> $this->getSystemNavigation($request->getAttribute('c_userrole')),
			'jsdata' 			=> [
										'settings' 		=> $pluginSettings,
										'definitions'	=> $pluginDefinitions,
										'license'		=> $license,
										'labels'		=> $translations,
										'urlinfo'		=> $this->c->get('urlinfo')
									]
	    ]);
	}

	public function showLicense($request, $response, $args)
	{
		$storage 		= new StorageWrapper('\Typemill\Models\Storage');
		$license 		= new License();
		$licensefields 	= $storage->getYaml('systemSettings', '', 'license.yaml');
		$translations 	= $this->c->get('translations');

		$licensedata 	= $license->getLicenseData($this->c->get('urlinfo'));
		if($licensedata)
		{
			foreach($licensefields as $key => $licensefield)
			{
				$licensefields[$key]['disabled'] = true;
			}
		}

	    return $this->c->get('view')->render($response, 'system/license.twig', [
			'settings' 			=> $this->settings,
			'mainnavi'			=> $this->getMainNavigation($request->getAttribute('c_userrole')),
			'systemnavi'		=> $this->getSystemNavigation($request->getAttribute('c_userrole')),
			'jsdata' 			=> [
										'licensedata' 	=> $licensedata,
										'licensefields'	=> $licensefields,
										'labels'		=> $translations,
										'urlinfo'		=> $this->c->get('urlinfo')									]
	    ]);
	}

	public function showAccount($request, $response, $args)
	{
		$translations 		= $this->c->get('translations');	
		$username			= $request->getAttribute('c_username');
		$user				= new User();

		$user->setUser($username);
		$userdata			= $user->getUserData();
		$userfields 		= $this->getUserFields($userdata['userrole']);

	    return $this->c->get('view')->render($response, 'system/account.twig', [
			'settings' 			=> $this->settings,
			'mainnavi'			=> $this->getMainNavigation($request->getAttribute('c_userrole')),
			'systemnavi'		=> $this->getSystemNavigation($request->getAttribute('c_userrole')),
			'jsdata' 			=> [
										'userdata'		=> $userdata,
										'userfields'	=> $userfields,
										'userroles'		=> $this->c->get('acl')->getRoles(),
										'labels'		=> $translations,
										'urlinfo'		=> $this->c->get('urlinfo')
									]
	    ]);
	}

	public function showUsers($request, $response, $args)
	{
		$translations 		= $this->c->get('translations');	
		$user				= new User();
		$usernames			= $user->getAllUsers();
		$userdata			= [];

		$count = 0;
		foreach($usernames as $username)
		{
			if($count == 10) break;
			$user->setUser($username);
			$userdata[] = $user->getUserData();
			$count++;
		}

	    return $this->c->get('view')->render($response, 'system/users.twig', [
			'settings' 			=> $this->settings,
			'mainnavi'			=> $this->getMainNavigation($request->getAttribute('c_userrole')),
			'systemnavi'		=> $this->getSystemNavigation($request->getAttribute('c_userrole')),
			'jsdata' 			=> [
										'totalusers'	=> count($usernames),
										'usernames' 	=> $usernames,
										'userdata'		=> $userdata,
										'userroles'		=> $this->c->get('acl')->getRoles(),
										'labels'		=> $translations,
										'urlinfo'		=> $this->c->get('urlinfo')
									]
	    ]);
	}

	public function showUser($request, $response, $args)
	{
		$translations 		= $this->c->get('translations');
		$username			= $args['username'] ?? false;
		$inspector 			= $request->getAttribute('c_userrole');
		$user				= new User();

		if(!$user->setUser($username))
		{
			die("return a not found page");
		}

		$userdata			= $user->getUserData();
		$userfields 		= $this->getUserFields($userdata['userrole'], $inspector);

	    return $this->c->get('view')->render($response, 'system/user.twig', [
			'settings' 			=> $this->settings,
			'mainnavi'			=> $this->getMainNavigation($request->getAttribute('c_userrole')),
			'systemnavi'		=> $this->getSystemNavigation($request->getAttribute('c_userrole')),
			'jsdata' 			=> [
										'userdata'		=> $userdata,
										'userfields'	=> $userfields,
										'userroles'		=> $this->c->get('acl')->getRoles(),
										'labels'		=> $translations,
										'urlinfo'		=> $this->c->get('urlinfo')
									]
	    ]);
	}

	public function newUser($request, $response, $args)
	{
		$translations 		= $this->c->get('translations');

	    return $this->c->get('view')->render($response, 'system/usernew.twig', [
			'settings' 			=> $this->settings,
			'mainnavi'			=> $this->getMainNavigation($request->getAttribute('c_userrole')),
			'systemnavi'		=> $this->getSystemNavigation($request->getAttribute('c_userrole')),
			'jsdata' 			=> [
										'userroles'		=> $this->c->get('acl')->getRoles(),
										'labels'		=> $translations,
										'urlinfo'		=> $this->c->get('urlinfo')
									]
	    ]);
	}







/*
	public function showBlank($request, $response, $args)
	{
		$user				= new User();
		$settings 			= $this->c->get('settings');
		$route 				= $request->getAttribute('route');
		$navigation 		= $this->getMainNavigation();

		$content 			= '<h1>Hello</h1><p>I am the showBlank method from the settings controller</p><p>In most cases I have been called from a plugin. But if you see this content, then the plugin does not work or has forgotten to inject its own content.</p>';

		return $this->render($response, 'settings/blank.twig', array(
			'settings' 		=> $settings,
			'acl' 			=> $this->c->acl, 
			'navigation'	=> $navigation,
			'content' 		=> $content,
			'route' 		=> $route->getName() 
		));
	}
			

	public function clearCache($request, $response, $args)
	{
		$this->uri 			= $request->getUri()->withUserInfo('');
		$dir 				= $this->settings['basePath'] . 'cache';

		$error 				= $this->writeCache->deleteCacheFiles($dir);
		if($error)
		{
			return $response->withJson(['errors' => $error], 500);
		}

		# create a new draft structure
		$this->setFreshStructureDraft();

		# create a new draft structure
		$this->setFreshStructureLive();

		# create a new draft structure
		$this->setFreshNavigation();

		# update the sitemap
		$this->updateSitemap();

		return $response->withJson(array('errors' => false));
	}


	protected function saveImages($imageFields, $userInput, $userSettings, $files)
	{
		# initiate image processor with standard image sizes
		$processImages = new ProcessImage($userSettings['images']);

		if(!$processImages->checkFolders('images'))
		{
			$this->c->flash->addMessage('error', 'Please make sure that your media folder exists and is writable.');
			return false; 
		}

		foreach($imageFields as $fieldName => $imageField)
		{
			if(isset($userInput[$fieldName]))
			{
				# handle single input with single file upload
    			$image = $files[$fieldName];
    		
    			if($image->getError() === UPLOAD_ERR_OK) 
    			{
    				# not the most elegant, but createImage expects a base64-encoded string.
    				$imageContent = $image->getStream()->getContents();
					$imageData = base64_encode($imageContent);
					$imageSrc = 'data: ' . $image->getClientMediaType() . ';base64,' . $imageData;

					if($processImages->createImage($imageSrc, $image->getClientFilename(), $userSettings['images'], $overwrite = NULL))
					{
						# returns image path to media library
						$userInput[$fieldName] = $processImages->publishImage();
					}
			    }
			}
		}
		return $userInput;
	}
	*/

}