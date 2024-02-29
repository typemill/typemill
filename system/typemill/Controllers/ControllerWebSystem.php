<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\Navigation;
use Typemill\Models\Extension;
use Typemill\Models\User;
use Typemill\Models\License;
use Typemill\Models\Settings;

class ControllerWebSystem extends Controller
{	
	public function showSettings(Request $request, Response $response, $args)
	{
		$navigation 		= new Navigation();
		$mainNavigation		= $navigation->getMainNavigation(
									$userrole 	= $request->getAttribute('c_userrole'),
									$acl 		= $this->c->get('acl'),
									$urlinfo 	= $this->c->get('urlinfo'),
									$editor 	= $this->settings['editor']
								);

		$systemNavigation	= $navigation->getSystemNavigation(
									$userrole 	= $request->getAttribute('c_userrole'),
									$acl 		= $this->c->get('acl'),
									$urlinfo 	= $this->c->get('urlinfo'),
									$dispatcher = $this->c->get('dispatcher'),
									$parser 	= $this->routeParser
								);

		$settingsModel 		= new Settings();
		$systemfields 		= $settingsModel->getSettingsDefinitions();
		$systemfields 		= $this->addDatasets($systemfields);

		# add full url for sitemap to settings
		$this->settings['sitemap'] = $this->c->get('urlinfo')['baseurl'] . '/cache/sitemap.xml';

	    return $this->c->get('view')->render($response, 'system/system.twig', [
#			'captcha' 			=> $this->checkIfAddCaptcha(),
#			'basicauth'			=> $user->getBasicAuth(),
			'settings' 			=> $this->settings,
			'darkmode'			=> $request->getAttribute('c_darkmode'),
			'mainnavi'			=> $mainNavigation,
			'jsdata' 			=> [
										'settings' 		=> $this->settings,
										'system'		=> $systemfields,
										'systemnavi'	=> $systemNavigation,
										'labels'		=> $this->c->get('translations'),
										'urlinfo'		=> $this->c->get('urlinfo')
									]
	    ]);
	}

	public function showThemes(Request $request, Response $response, $args)
	{
		$navigation 		= new Navigation();
		$mainNavigation		= $navigation->getMainNavigation(
									$userrole 	= $request->getAttribute('c_userrole'),
									$acl 		= $this->c->get('acl'),
									$urlinfo 	= $this->c->get('urlinfo'),
									$editor 	= $this->settings['editor']
								);

		$systemNavigation	= $navigation->getSystemNavigation(
									$userrole 	= $request->getAttribute('c_userrole'),
									$acl 		= $this->c->get('acl'),
									$urlinfo 	= $this->c->get('urlinfo'),
									$dispatcher = $this->c->get('dispatcher'),
									$parser 	= $this->routeParser
								);

		$extension 			= new Extension();
		$themeDefinitions 	= $extension->getThemeDetails($this->settings['theme']);

		# add userroles and other datasets
		foreach($themeDefinitions as $name => $definitions)
		{
			if(isset($definitions['forms']['fields']))
			{
				$themeDefinitions[$name]['forms']['fields'] = $this->addDatasets($definitions['forms']['fields']);
			}
		}

		$themeSettings 		= $extension->getThemeSettings($this->settings['themes']);

		$license = [];
		if(is_array($this->settings['license']))
		{
			$license = array_keys($this->settings['license']);
		}

	    return $this->c->get('view')->render($response, 'system/themes.twig', [
			'settings' 			=> $this->settings,
			'darkmode'			=> $request->getAttribute('c_darkmode'),
			'mainnavi'			=> $mainNavigation,
			'jsdata' 			=> [
										'systemnavi'		=> $systemNavigation,
										'settings' 		=> $themeSettings,
										'definitions'	=> $themeDefinitions,
										'theme'			=> $this->settings['theme'],
										'license' 		=> $license,
										'labels'		=> $this->c->get('translations'),
										'urlinfo'		=> $this->c->get('urlinfo')
									]
	    ]);
	}

	public function showPlugins(Request $request, Response $response, $args)
	{
		$navigation 		= new Navigation();
		$mainNavigation		= $navigation->getMainNavigation(
									$userrole 	= $request->getAttribute('c_userrole'),
									$acl 		= $this->c->get('acl'),
									$urlinfo 	= $this->c->get('urlinfo'),
									$editor 	= $this->settings['editor']
								);

		$systemNavigation	= $navigation->getSystemNavigation(
									$userrole 	= $request->getAttribute('c_userrole'),
									$acl 		= $this->c->get('acl'),
									$urlinfo 	= $this->c->get('urlinfo'),
									$dispatcher = $this->c->get('dispatcher'),
									$parser 	= $this->routeParser
								);


		$pluginSettings 	= $this->settings['plugins'] ?? false;
		$pluginDefinitions 	= [];

		if($pluginSettings)
		{
			$extension 			= new Extension();
			$pluginDefinitions 	= $extension->getPluginDetails($pluginSettings);

			# add userroles and other datasets
			foreach($pluginDefinitions as $name => $definitions)
			{
				if(isset($definitions['forms']['fields']))
				{
					$pluginDefinitions[$name]['forms']['fields'] = $this->addDatasets($definitions['forms']['fields']);
				}
			}

		}

		$license = [];
		if(isset($this->settings['license']) && is_array($this->settings['license']))
		{
			$license = array_keys($this->settings['license']);
		}

	    return $this->c->get('view')->render($response, 'system/plugins.twig', [
			'settings' 			=> $this->settings,
			'darkmode'			=> $request->getAttribute('c_darkmode'),
			'mainnavi'			=> $mainNavigation,
			'jsdata' 			=> [
										'systemnavi'	=> $systemNavigation,
										'settings' 		=> $pluginSettings,
										'definitions'	=> $pluginDefinitions,
										'license'		=> $license,
										'labels'		=> $this->c->get('translations'),
										'urlinfo'		=> $this->c->get('urlinfo')
									]
	    ]);
	}

	public function showLicense(Request $request, Response $response, $args)
	{
		$navigation 		= new Navigation();
		$mainNavigation		= $navigation->getMainNavigation(
									$userrole 	= $request->getAttribute('c_userrole'),
									$acl 		= $this->c->get('acl'),
									$urlinfo 	= $this->c->get('urlinfo'),
									$editor 	= $this->settings['editor']
								);

		$systemNavigation	= $navigation->getSystemNavigation(
									$userrole 	= $request->getAttribute('c_userrole'),
									$acl 		= $this->c->get('acl'),
									$urlinfo 	= $this->c->get('urlinfo'),
									$dispatcher = $this->c->get('dispatcher'),
									$parser 	= $this->routeParser
								);

		$message 		= false;
		$license 		= new License();
		$licensefields 	= $license->getLicenseFields();
		$licensedata 	= $license->getLicenseFile();

		# disable input fields if license data exist (readonly)
		if($licensedata)
		{
			foreach($licensefields as $key => $licensefield)
			{
				$licensefields[$key]['disabled'] = true;
			}
		}

		# check license data 
		$licensecheck 	= $license->checkLicense($licensedata, $this->c->get('urlinfo'));
		if(!$licensecheck)
		{
			$message 	= $license->getMessage();
		}

		unset($licensedata['signature']);

	    return $this->c->get('view')->render($response, 'system/license.twig', [
			'settings' 			=> $this->settings,
			'darkmode'			=> $request->getAttribute('c_darkmode'),
			'mainnavi'			=> $mainNavigation,
			'jsdata' 			=> [
										'systemnavi'	=> $systemNavigation,
										'licensedata' 	=> $licensedata,
										'licensefields'	=> $licensefields,
										'message'		=> $message,
										'labels'		=> $this->c->get('translations'),
										'urlinfo'		=> $this->c->get('urlinfo')							]
	    ]);
	}

	public function showAccount(Request $request, Response $response, $args)
	{
		$navigation 		= new Navigation();
		$mainNavigation		= $navigation->getMainNavigation(
									$userrole 	= $request->getAttribute('c_userrole'),
									$acl 		= $this->c->get('acl'),
									$urlinfo 	= $this->c->get('urlinfo'),
									$editor 	= $this->settings['editor']
								);

		$systemNavigation	= $navigation->getSystemNavigation(
									$userrole 	= $request->getAttribute('c_userrole'),
									$acl 		= $this->c->get('acl'),
									$urlinfo 	= $this->c->get('urlinfo'),
									$dispatcher = $this->c->get('dispatcher'),
									$parser 	= $this->routeParser
								);

		$username			= $request->getAttribute('c_username');
		$user				= new User();
		$user->setUser($username);

		$userdata			= $user->getUserData();
		$userfields 		= $user->getUserFields($this->c->get('acl'), $userdata['userrole']);

	    return $this->c->get('view')->render($response, 'system/account.twig', [
			'settings' 			=> $this->settings,
			'darkmode'			=> $request->getAttribute('c_darkmode'),
			'mainnavi'			=> $mainNavigation,
			'jsdata' 			=> [
										'systemnavi'		=> $systemNavigation,
										'userdata'		=> $userdata,
										'userfields'	=> $userfields,
										'userroles'		=> $this->c->get('acl')->getRoles(),
										'labels'		=> $this->c->get('translations'),
										'urlinfo'		=> $this->c->get('urlinfo')
									]
	    ]);
	}

	public function showUsers(Request $request, Response $response, $args)
	{
		$navigation 		= new Navigation();
		$mainNavigation		= $navigation->getMainNavigation(
									$userrole 	= $request->getAttribute('c_userrole'),
									$acl 		= $this->c->get('acl'),
									$urlinfo 	= $this->c->get('urlinfo'),
									$editor 	= $this->settings['editor']
								);

		$systemNavigation	= $navigation->getSystemNavigation(
									$userrole 	= $request->getAttribute('c_userrole'),
									$acl 		= $this->c->get('acl'),
									$urlinfo 	= $this->c->get('urlinfo'),
									$dispatcher = $this->c->get('dispatcher'),
									$parser 	= $this->routeParser
								);

		$user				= new User();
		$usernames			= $user->getAllUsers();
		$userdata			= [];
		$count 				= 0;
		foreach($usernames as $username)
		{
			if($count == 10) break;
			$user->setUser($username);
			$userdata[] = $user->getUserData();
			$count++;
		}

	    return $this->c->get('view')->render($response, 'system/users.twig', [
			'settings' 			=> $this->settings,
			'darkmode'			=> $request->getAttribute('c_darkmode'),
			'mainnavi'			=> $mainNavigation,
			'jsdata' 			=> [
										'systemnavi'		=> $systemNavigation,
										'totalusers'	=> count($usernames),
										'usernames' 	=> $usernames,
										'userdata'		=> $userdata,
										'userroles'		=> $this->c->get('acl')->getRoles(),
										'labels'		=> $this->c->get('translations'),
										'urlinfo'		=> $this->c->get('urlinfo')
									]
	    ]);
	}

	public function showUser(Request $request, Response $response, $args)
	{
		$navigation 		= new Navigation();
		$mainNavigation		= $navigation->getMainNavigation(
									$userrole 	= $request->getAttribute('c_userrole'),
									$acl 		= $this->c->get('acl'),
									$urlinfo 	= $this->c->get('urlinfo'),
									$editor 	= $this->settings['editor']
								);

		$systemNavigation	= $navigation->getSystemNavigation(
									$userrole 	= $request->getAttribute('c_userrole'),
									$acl 		= $this->c->get('acl'),
									$urlinfo 	= $this->c->get('urlinfo'),
									$dispatcher = $this->c->get('dispatcher'),
									$parser 	= $this->routeParser
								);

		$user				= new User();
		$username			= $args['username'] ?? false;
		if(!$user->setUser($username))
		{
			die("return a not found page");
		}

		$userdata			= $user->getUserData();
		$inspector 			= $request->getAttribute('c_userrole');
		$userfields 		= $user->getUserFields($this->c->get('acl'), $userdata['userrole'], $inspector);

	    return $this->c->get('view')->render($response, 'system/user.twig', [
			'settings' 			=> $this->settings,
			'darkmode'			=> $request->getAttribute('c_darkmode'),
			'mainnavi'			=> $mainNavigation,
			'jsdata' 			=> [
										'systemnavi'		=> $systemNavigation,
										'userdata'		=> $userdata,
										'userfields'	=> $userfields,
										'userroles'		=> $this->c->get('acl')->getRoles(),
										'labels'		=> $this->c->get('translations'),
										'urlinfo'		=> $this->c->get('urlinfo')
									]
	    ]);
	}

	public function newUser(Request $request, Response $response, $args)
	{
		$navigation 		= new Navigation();
		$mainNavigation		= $navigation->getMainNavigation(
									$userrole 	= $request->getAttribute('c_userrole'),
									$acl 		= $this->c->get('acl'),
									$urlinfo 	= $this->c->get('urlinfo'),
									$editor 	= $this->settings['editor']
								);

		$systemNavigation	= $navigation->getSystemNavigation(
									$userrole 	= $request->getAttribute('c_userrole'),
									$acl 		= $this->c->get('acl'),
									$urlinfo 	= $this->c->get('urlinfo'),
									$dispatcher = $this->c->get('dispatcher'),
									$parser 	= $this->routeParser
								);

	    return $this->c->get('view')->render($response, 'system/usernew.twig', [
			'settings' 			=> $this->settings,
			'darkmode'			=> $request->getAttribute('c_darkmode'),
			'mainnavi'			=> $mainNavigation,
			'jsdata' 			=> [
										'systemnavi'		=> $systemNavigation,
										'userroles'		=> $this->c->get('acl')->getRoles(),
										'labels'		=> $this->c->get('translations'),
										'urlinfo'		=> $this->c->get('urlinfo')
									]
	    ]);
	}

	public function blankSystemPage(Request $request, Response $response, $args)
	{
		$navigation 		= new Navigation();
		$mainNavigation		= $navigation->getMainNavigation(
									$userrole 	= $request->getAttribute('c_userrole'),
									$acl 		= $this->c->get('acl'),
									$urlinfo 	= $this->c->get('urlinfo'),
									$editor 	= $this->settings['editor']
								);
		$userrole 	= $request->getAttribute('c_userrole');
		$acl 		= $this->c->get('acl');
		$urlinfo 	= $this->c->get('urlinfo');
		$editor 	= $this->settings['editor'];

		$systemNavigation	= $navigation->getSystemNavigation(
									$userrole 	= $request->getAttribute('c_userrole'),
									$acl 		= $this->c->get('acl'),
									$urlinfo 	= $this->c->get('urlinfo'),
									$dispatcher = $this->c->get('dispatcher'),
									$parser 	= $this->routeParser
								);

		$pluginDefinitions 	= false;
		$pluginname 		= strtolower(trim(str_replace('tm/', '', $urlinfo['route']), '/'));
		if($pluginname && $pluginname != '' && isset($this->settings['plugins'][$pluginname]))
		{
			$extension 			= new Extension();
			$pluginDefinitions 	= $extension->getPluginDefinition($pluginname);
		}

	    return $this->c->get('view')->render($response, 'layouts/layoutSystemBlank.twig', [
			'settings' 			=> $this->settings,
			'darkmode'			=> $request->getAttribute('c_darkmode'),
			'mainnavi'			=> $mainNavigation,
			'jsdata' 			=> [
										'systemnavi'	=> $systemNavigation,
										'settings' 		=> $this->settings,
										'labels'		=> $this->c->get('translations'),
										'urlinfo'		=> $this->c->get('urlinfo'),
										'acl'			=> $this->c->get('acl'),
										'userroles'		=> $this->c->get('acl')->getRoles(),
										'plugin'		=> $pluginDefinitions
									]
	    ]);
	}
}