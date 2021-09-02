<?php

namespace Typemill\Controllers;

use \Symfony\Component\Yaml\Yaml;
use Typemill\Models\Write;
use Typemill\Models\Fields;
use Typemill\Models\Validation;
use Typemill\Models\User;
use Typemill\Models\ProcessFile;
use Typemill\Models\ProcessImage;
use Typemill\Events\OnUserfieldsLoaded;
use Typemill\Events\OnSystemnaviLoaded;
use Typemill\Events\OnUserDeleted;

class SettingsController extends Controller
{	

	public function showBlank($request, $response, $args)
	{
		$user				= new User();
		$settings 			= $this->c->get('settings');
		$route 				= $request->getAttribute('route');
		$navigation 		= $this->getNavigation();

		$content 			= '<h1>Hello</h1><p>I am the showBlank method from the settings controller</p><p>In most cases I have been called from a plugin. But if you see this content, then the plugin does not work or has forgotten to inject its own content.</p>';

		return $this->render($response, 'settings/blank.twig', array(
			'settings' 		=> $settings,
			'acl' 			=> $this->c->acl, 
			'navigation'	=> $navigation,
			'content' 		=> $content,
			'route' 		=> $route->getName() 
		));
	}

	/*********************
	**	BASIC SETTINGS	**
	*********************/
	
	public function showSettings($request, $response, $args)
	{
		$user				= new User();
		$settings 			= $this->c->get('settings');
		$defaultSettings	= \Typemill\Settings::getDefaultSettings();
		$copyright			= $this->getCopyright();
		$languages			= $this->getLanguages();
		$locale				= isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? substr($_SERVER["HTTP_ACCEPT_LANGUAGE"],0,2) : 'en';
		$route 				= $request->getAttribute('route');
		$navigation 		= $this->getNavigation();

		# set navigation active
		$navigation['System']['active'] = true;

		return $this->render($response, 'settings/system.twig', array(
			'settings' 		=> $settings,
			'acl' 			=> $this->c->acl, 
			'navigation'	=> $navigation,
			'copyright' 	=> $copyright, 
			'languages' 	=> $languages, 
			'locale' 		=> $locale, 
			'formats' 		=> $defaultSettings['formats'],
			'route' 		=> $route->getName()
		));
	}
	
	public function saveSettings($request, $response, $args)
	{
		if($request->isPost())
		{

		    if( $request->getattribute('csrf_result') === false )
		    {
				$this->c->flash->addMessage('error', 'The form has a timeout, please try again.');
				return $response->withRedirect($this->c->router->pathFor('settings.show'));				
		    }

			$settings 			= \Typemill\Settings::getUserSettings();
			$defaultSettings	= \Typemill\Settings::getDefaultSettings();
			$params 			= $request->getParams();
			$files 				= $request->getUploadedFiles();
			$newSettings		= isset($params['settings']) ? $params['settings'] : false;
			$validate			= new Validation();
			$processFiles		= new ProcessFile();

			if($newSettings)
			{
				# check for image settings
				$imgwidth = isset($newSettings['images']['live']['width']) ? $newSettings['images']['live']['width'] : false;
				$imgheight = isset($newSettings['images']['live']['height']) ? $newSettings['images']['live']['height'] : false;

				# make sure only allowed fields are stored
				$newSettings = array(
					'title' 				=> $newSettings['title'],
					'author' 				=> $newSettings['author'],
					'copyright' 			=> $newSettings['copyright'],
					'year'					=> $newSettings['year'],
					'language'				=> $newSettings['language'],
					'langattr'				=> $newSettings['langattr'],
					'editor' 				=> $newSettings['editor'],
					'formats'				=> $newSettings['formats'],
					'access'				=> isset($newSettings['access']) ? true : null,
					'pageaccess'			=> isset($newSettings['pageaccess']) ? true : null,
					'hrdelimiter'			=> isset($newSettings['hrdelimiter']) ? true : null,
					'restrictionnotice'		=> $newSettings['restrictionnotice'],
					'wraprestrictionnotice'	=> isset($newSettings['wraprestrictionnotice']) ? true : null,
					'headlineanchors'		=> isset($newSettings['headlineanchors']) ? $newSettings['headlineanchors'] : null,
					'displayErrorDetails'	=> isset($newSettings['displayErrorDetails']) ? true : null,
					'twigcache'				=> isset($newSettings['twigcache']) ? true : null,
					'proxy'					=> isset($newSettings['proxy']) ? true : null,
					'trustedproxies'		=> $newSettings['trustedproxies'],
					'headersoff'			=> isset($newSettings['headersoff']) ? true : null,
					'urlschemes'			=> $newSettings['urlschemes'],
					'svg'					=> isset($newSettings['svg']) ? true : null,
				);

				# https://www.slimframework.com/docs/v3/cookbook/uploading-files.html; 

				$copyright 			= $this->getCopyright();

				$validate->settings($newSettings, $copyright, $defaultSettings['formats'], 'settings');
			
				# use custom image settings
				if( $imgwidth  && ctype_digit($imgwidth) && (strlen($imgwidth) < 5) )
				{
					$newSettings['images']['live']['width'] = $imgwidth;
				}
				if( $imgheight  && ctype_digit($imgheight) && (strlen($imgheight) < 5) )
				{
					$newSettings['images']['live']['height'] = $imgheight;
				}
			}
			else
			{
				$this->c->flash->addMessage('error', 'Wrong Input');
				return $response->withRedirect($this->c->router->pathFor('settings.show'));
			}

			if(isset($_SESSION['errors']))
			{
				$this->c->flash->addMessage('error', 'Please correct the errors');
				return $response->withRedirect($this->c->router->pathFor('settings.show'));
			}

			if(!$processFiles->checkFolders())
			{
					$this->c->flash->addMessage('error', 'Please make sure that your media folder exists and is writable.');
					return $response->withRedirect($this->c->router->pathFor('settings.show'));
			}

			# handle single input with single file upload
    		$logo = $files['settings']['logo'];
    		if($logo->getError() === UPLOAD_ERR_OK) 
    		{
    			$allowed = ['jpg', 'jpeg', 'png', 'svg'];
    			$extension = pathinfo($logo->getClientFilename(), PATHINFO_EXTENSION);
    			if(!in_array(strtolower($extension), $allowed))
    			{
					$_SESSION['errors']['settings']['logo'] = array('Only jpg, jpeg, png and svg allowed');
					$this->c->flash->addMessage('error', 'Please correct the errors');
					return $response->withRedirect($this->c->router->pathFor('settings.show'));
    			}

    			$processFiles->deleteFileWithName('logo');
		        $newSettings['logo'] = $processFiles->moveUploadedFile($logo, $overwrite = true, $name = 'logo');
		    }
		    elseif(isset($params['settings']['deletelogo']) && $params['settings']['deletelogo'] == 'delete')
		    {
		    	$processFiles->deleteFileWithName('logo');
		    	$newSettings['logo'] = '';
		    }
		    else
		    {
		    	$newSettings['logo'] = 	isset($settings['logo']) ? $settings['logo'] : ''; 
		    }

			# handle single input with single file upload
    		$favicon = $files['settings']['favicon'];
    		if ($favicon->getError() === UPLOAD_ERR_OK) 
    		{
    			$extension = pathinfo($favicon->getClientFilename(), PATHINFO_EXTENSION);
    			if(strtolower($extension) != 'png')
    			{
					$_SESSION['errors']['settings']['favicon'] = array('Only .png-files allowed');
					$this->c->flash->addMessage('error', 'Please correct the errors');
					return $response->withRedirect($this->c->router->pathFor('settings.show'));
    			}

    			$processImage = new ProcessImage([
    				'16' => ['width' => 16, 'height' => 16], 
    				'32' => ['width' => 32, 'height' => 32],
    				'72' => ['width' => 72, 'height' => 72],
    				'114' => ['width' => 114, 'height' => 114],
    				'144' => ['width' => 144, 'height' => 144],
    				'180' => ['width' => 180, 'height' => 180],
    			]);
    			$favicons = $processImage->generateSizesFromImageFile('favicon.png', $favicon->file);

    			foreach($favicons as $key => $favicon)
    			{
    				imagepng( $favicon, $processFiles->fileFolder . 'favicon-' . $key . '.png' );
					# $processFiles->moveUploadedFile($favicon, $overwrite = true, $name = 'favicon-' . $key);
    			}

		        $newSettings['favicon'] = 'favicon';
		    }
		    elseif(isset($params['settings']['deletefav']) && $params['settings']['deletefav'] == 'delete')
		    {
		    	$processFiles->deleteFileWithName('favicon');
		    	$newSettings['favicon'] = '';
		    }
		    else
		    {
		    	$newSettings['favicon'] = isset($settings['favicon']) ? $settings['favicon'] : ''; 
		    }

			# store updated settings
			\Typemill\Settings::updateSettings(array_merge($settings, $newSettings));
			
			$this->c->flash->addMessage('info', 'Settings are stored');
			return $response->withRedirect($this->c->router->pathFor('settings.show'));
		}
	}

	/*********************
	**	THEME SETTINGS	**
	*********************/
	
	public function showThemes($request, $response, $args)
	{
		$userSettings 	= $this->c->get('settings');
		$themes 		= $this->getThemes();
		$themedata		= array();
		$fieldsModel	= new Fields($this->c);

		foreach($themes as $themeName)
		{
			/* if theme is active, list it first */
			if($userSettings['theme'] == $themeName)
			{
				$themedata = array_merge(array($themeName => null), $themedata);
			}
			else
			{
				$themedata[$themeName] = null;
			}

			$themeSettings = \Typemill\Settings::getObjectSettings('themes', $themeName);

			# add standard-textarea for custom css
			$themeSettings['forms']['fields']['customcss'] = ['type' => 'textarea', 'label' => 'Custom CSS', 'rows' => 10, 'class' => 'codearea', 'description' => 'You can overwrite the theme-css with your own css here.'];

			# load custom css-file
			$write = new write();
			$customcss = $write->getFile('cache', $themeName . '-custom.css');
			$themeSettings['settings']['customcss'] = $customcss;


			if($themeSettings)
			{
				/* store them as default theme data with author, year, default settings and field-definitions */
				$themedata[$themeName] = $themeSettings;
			}
			
			if(isset($themeSettings['forms']['fields']))
			{
				$fields = $fieldsModel->getFields($userSettings, 'themes', $themeName, $themeSettings);

				/* overwrite original theme form definitions with enhanced form objects */
				$themedata[$themeName]['forms']['fields'] = $fields;
			}
			
			/* add the preview image */
			$img = getcwd() . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $themeName . DIRECTORY_SEPARATOR . $themeName;

			$image = false;
			if(file_exists($img . '.jpg'))
			{
				$image = $themeName . '.jpg';
			}
			if(file_exists($img . '.png'))
			{
				$image = $themeName . '.png';
			}

			$themedata[$themeName]['img'] = $image;
		}
		
		/* add the users for navigation */
		$route 	= $request->getAttribute('route');
		$navigation = $this->getNavigation();

		# set navigation active
		$navigation['Themes']['active'] = true;

		return $this->render($response, 'settings/themes.twig', array(
			'settings' 		=> $userSettings,
			'acl' 			=> $this->c->acl,
			'navigation' 	=> $navigation, 
			'themes' 		=> $themedata, 
			'route' 		=> $route->getName() 
		));
	}
	
	public function showPlugins($request, $response, $args)
	{
		$userSettings 	= $this->c->get('settings');
		$plugins		= array();
		$fieldsModel	= new Fields($this->c);
		$fields 		= array();

		/* iterate through the plugins in the stored user settings */
		foreach($userSettings['plugins'] as $pluginName => $pluginUserSettings)
		{
			/* add plugin to plugin Data, if active, set it first */
			/* if plugin is active, list it first */
			if($userSettings['plugins'][$pluginName]['active'] == true)
			{
				$plugins = array_merge(array($pluginName => null), $plugins);
			}
			else
			{
				$plugins[$pluginName] = Null;
			}
			
			/* Check if the user has deleted a plugin. Then delete it in the settings and store the updated settings. */
			if(!is_dir($userSettings['rootPath'] . 'plugins' . DIRECTORY_SEPARATOR . $pluginName))
			{
				/* remove the plugin settings and store updated settings */
				\Typemill\Settings::removePluginSettings($pluginName);
				continue;
			}
			
			/* load the original plugin definitions from the plugin folder (author, version and stuff) */
			$pluginOriginalSettings = \Typemill\Settings::getObjectSettings('plugins', $pluginName);
			if($pluginOriginalSettings)
			{
				/* store them as default plugin data with plugin author, plugin year, default settings and field-definitions */
				$plugins[$pluginName] = $pluginOriginalSettings;
			}
			
			/* check, if the plugin has been disabled in the form-session-data */
			if(isset($_SESSION['old']) && !isset($_SESSION['old'][$pluginName]['active']))
			{
				$plugins[$pluginName]['settings']['active'] = false;
			}
			
			/* if the plugin defines forms and fields, so that the user can edit the plugin settings in the frontend */
			if(isset($pluginOriginalSettings['forms']['fields']))
			{
				# if the plugin defines frontend fields
				if(isset($pluginOriginalSettings['public']))
				{
					$pluginOriginalSettings['forms']['fields']['recaptcha'] = ['type' => 'checkbox', 'label' => 'Google Recaptcha', 'checkboxlabel' => 'Activate Recaptcha' ];
					$pluginOriginalSettings['forms']['fields']['recaptcha_webkey'] = ['type' => 'text', 'label' => 'Recaptcha Website Key', 'help' => 'Add the recaptcha website key here. You can get the key from the recaptcha website.', 'description' => 'The website key is mandatory if you activate the recaptcha field'];
					$pluginOriginalSettings['forms']['fields']['recaptcha_secretkey'] = ['type' => 'text', 'label' => 'Recaptcha Secret Key', 'help' => 'Add the recaptcha secret key here. You can get the key from the recaptcha website.', 'description' => 'The secret key is mandatory if you activate the recaptcha field'];
				}
				
				/* get all the fields and prefill them with the dafault-data, the user-data or old input data */
				$fields = $fieldsModel->getFields($userSettings, 'plugins', $pluginName, $pluginOriginalSettings);
				
				/* overwrite original plugin form definitions with enhanced form objects */
				$plugins[$pluginName]['forms']['fields'] = $fields;			
			}
		}
		
		$route 	= $request->getAttribute('route');
		$navigation = $this->getNavigation();

		# set navigation active
		$navigation['Plugins']['active'] = true;
		
		return $this->render($response, 'settings/plugins.twig', array(
			'settings' 		=> $userSettings,
			'acl' 			=> $this->c->acl,
			'navigation' 	=> $navigation,
			'plugins' 		=> $plugins,
			'route' 		=> $route->getName() 
		));
	}

	/*************************************
	**	SAVE THEME- AND PLUGIN-SETTINGS	**
	*************************************/

	public function saveThemes($request, $response, $args)
	{
		if($request->isPost())
		{
		    if( $request->getattribute('csrf_result') === false )
		    {
				$this->c->flash->addMessage('error', 'The form has a timeout, please try again.');
				return $response->withRedirect($this->c->router->pathFor('themes.show'));				
		    }

			$userSettings 	= \Typemill\Settings::getUserSettings();
			$params 		= $request->getParams();
			$themeName		= isset($params['theme']) ? $params['theme'] : false;
			$userInput		= isset($params[$themeName]) ? $params[$themeName] : false;
			$validate		= new Validation();
			$themeSettings 	= \Typemill\Settings::getObjectSettings('themes', $themeName);

			if(isset($themeSettings['settings']['images']))
			{	
				# get the default settings
				$defaultSettings = \Typemill\Settings::getDefaultSettings();
				
				# merge the default image settings with the theme image settings, delete all others (image settings from old theme)
				$userSettings['images'] = array_merge($defaultSettings['images'], $themeSettings['settings']['images']);
			}
			
			/* set theme name and delete theme settings from user settings for the case, that the new theme has no settings */
			$userSettings['theme'] = $themeName;

			# extract the custom css from user input
			$customcss = isset($userInput['customcss']) ? $userInput['customcss'] : false;

			# delete custom css from userinput
			unset($userInput['customcss']);

			$write = new write();

			# make sure no file is set if there is no custom css
			if(!$customcss OR $customcss == '')
			{
				# delete the css file if exists
				$write->deleteFileWithPath('cache' . DIRECTORY_SEPARATOR . $themeName . '-custom.css');
			}
			else
			{
				if ( $customcss != strip_tags($customcss) )
				{
					$_SESSION['errors'][$themeName]['customcss'][] = 'custom css contains html';
				}
				else
				{
					# store css
					$write = new write();
					$write->writeFile('cache', $themeName . '-custom.css', $customcss);
				}
			}

			if($userInput)
			{
				# validate the user-input and return image-fields if they are defined
				$imageFields = $this->validateInput('themes', $themeName, $userInput, $validate);

				/* set user input as theme settings */
				$userSettings['themes'][$themeName] = $userInput;
			}

			# handle images
			$images = $request->getUploadedFiles();

			if(!isset($_SESSION['errors']) && isset($images[$themeName]))
			{
				$userInput = $this->saveImages($imageFields, $userInput, $userSettings, $images[$themeName]);

				# set user input as theme settings
				$userSettings['themes'][$themeName] = $userInput;
			}
			
			/* check for errors and redirect to path, if errors found */
			if(isset($_SESSION['errors']))
			{
				$this->c->flash->addMessage('error', 'Please correct the errors');
				return $response->withRedirect($this->c->router->pathFor('themes.show'));
			}
			
			/* store updated settings */
			\Typemill\Settings::updateSettings($userSettings);
			
			$this->c->flash->addMessage('info', 'Settings are stored');
			return $response->withRedirect($this->c->router->pathFor('themes.show'));
		}
	}

	public function savePlugins($request, $response, $args)
	{
		if($request->isPost())
		{

		    if( $request->getattribute('csrf_result') === false )
		    {
				$this->c->flash->addMessage('error', 'The form has a timeout, please try again.');
				return $response->withRedirect($this->c->router->pathFor('plugins.show'));
		    }

			$userSettings 	= \Typemill\Settings::getUserSettings();
			$pluginSettings	= array();
			$userInput 		= $request->getParams();
			$validate		= new Validation();
			
			/* use the stored user settings and iterate over all original plugin settings, so we do not forget any... */
			foreach($userSettings['plugins'] as $pluginName => $pluginUserSettings)
			{
				/* if there are no input-data for this plugin, then use the stored plugin settings */
				if(!isset($userInput[$pluginName]))
				{
					$pluginSettings[$pluginName] = $pluginUserSettings;
				}
				else
				{
					# fetch the original settings from the folder to get the field definitions
					$originalSettings = \Typemill\Settings::getObjectSettings('plugins', $pluginName);

					# check if the plugin has dependencies
					if(isset($userInput[$pluginName]['active']) && isset($originalSettings['dependencies']))
					{
						foreach($originalSettings['dependencies'] as $dependency)
						{
							if(!isset($userInput[$dependency]['active']) OR !$userInput[$dependency]['active'])
							{
								$this->c->flash->addMessage('error', 'Activate the plugin ' . $dependency . ' before you activate the plugin ' . $pluginName);
								return $response->withRedirect($this->c->router->pathFor('plugins.show'));
							}
						}
					}

					/* validate the user-input */
					$imageFields = $this->validateInput('plugins', $pluginName, $userInput[$pluginName], $validate, $originalSettings);

					/* use the input data */
					$pluginSettings[$pluginName] = $userInput[$pluginName];
				}

				# handle images
				$images = $request->getUploadedFiles();

				if(!isset($_SESSION['errors']) && isset($images[$pluginName]))
				{
					$userInput[$pluginName] = $this->saveImages($imageFields, $userInput[$pluginName], $userSettings, $images[$pluginName]);

					# set user input as theme settings
					$pluginSettings[$pluginName] = $userInput[$pluginName];
				}
				
				/* deactivate the plugin, if there is no active flag */
				if(!isset($userInput[$pluginName]['active']))
				{
					$pluginSettings[$pluginName]['active'] = false;
				}
			}

			if(isset($_SESSION['errors']))
			{
				$this->c->flash->addMessage('error', 'Please correct the errors below');
			}
			else
			{
				/* if everything is valid, add plugin settings to base settings again */
				$userSettings['plugins'] = $pluginSettings;
				
				/* store updated settings */
				\Typemill\Settings::updateSettings($userSettings);

				$this->c->flash->addMessage('info', 'Settings are stored');
			}
			
			return $response->withRedirect($this->c->router->pathFor('plugins.show'));
		}
	}

	/***********************
	**   USER MANAGEMENT  **
	***********************/

	public function showAccount($request, $response, $args)
	{
		$username 	= $_SESSION['user'];

		$validate 	= new Validation();
		
		if($validate->username($username))
		{
			# get settings
			$settings 	= $this->c->get('settings');

			# get user with userdata
			$user 		= new User();
			$userdata 	= $user->getSecureUser($username);
			
			# instantiate field-builder
			$fieldsModel	= new Fields($this->c);

			# get the field-definitions
			$fieldDefinitions = $this->getUserFields($userdata['userrole']);

			# prepare userdata for field-builder
			$userSettings['users']['user'] = $userdata;

			# generate the input form
			$userform = $fieldsModel->getFields($userSettings, 'users', 'user', $fieldDefinitions);

			$route = $request->getAttribute('route');
			$navigation = $this->getNavigation();

			# set navigation active
			$navigation['Account']['active'] = true;

			return $this->render($response, 'settings/user.twig', array(
				'settings' 		=> $settings,
				'acl' 			=> $this->c->acl,
				'navigation'	=> $navigation, 
				'usersettings' 	=> $userSettings, 		// needed for image url in form, will overwrite settings for field-template
				'userform' 		=> $userform, 			// field model, needed to generate frontend-field
				'userdata' 		=> $userdata, 			// needed to fill form with data
#				'userrole' 		=> false,				// not needed ? 
#				'username' 		=> $args['username'], 	// not needed ?
				'route' 		=> $route->getName()  	// needed to set link active
			));			
		}
		
		$this->c->flash->addMessage('error', 'User does not exists');
		return $response->withRedirect($this->c->router->pathFor('home'));
	}
	
	public function showUser($request, $response, $args)
	{
		# if user has no rights to watch userlist, then redirect to 
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'userlist', 'view') && $_SESSION['user'] !== $args['username'] )
		{
			return $response->withRedirect($this->c->router->pathFor('user.show', ['username' => $_SESSION['user']] ));
		}
		
		# get settings
		$settings 	= $this->c->get('settings');

		# get user with userdata
		$user 		= new User();
		$userdata 	= $user->getSecureUser($args['username']);

		if(!$userdata)
		{
			$this->c->flash->addMessage('error', 'User does not exists');
			return $response->withRedirect($this->c->router->pathFor('user.account'));				
		}
			
		# instantiate field-builder
		$fieldsModel	= new Fields($this->c);

		# get the field-definitions
		$fieldDefinitions = $this->getUserFields($userdata['userrole']);

		# prepare userdata for field-builder
		$userSettings['users']['user'] = $userdata;

		# generate the input form
		$userform = $fieldsModel->getFields($userSettings, 'users', 'user', $fieldDefinitions);

		$route = $request->getAttribute('route');
		$navigation = $this->getNavigation();

		# set navigation active
		$navigation['Users']['active'] = true;

		if(isset($userdata['lastlogin']))
		{
			$userdata['lastlogin'] = date("d.m.Y H:i:s", $userdata['lastlogin']);
		}
		
		return $this->render($response, 'settings/user.twig', array(
			'settings' 		=> $settings,
			'acl' 			=> $this->c->acl, 
			'navigation'	=> $navigation, 
			'usersettings' 	=> $userSettings, 		// needed for image url in form, will overwrite settings for field-template
			'userform' 		=> $userform, 			// field model, needed to generate frontend-field
			'userdata' 		=> $userdata, 			// needed to fill form with data
			'route' 		=> $route->getName()  	// needed to set link active
		));
	}

	public function listUser($request, $response)
	{
		$user			= new User();
		$users			= $user->getUsers();
		$userdata 		= array();
		$route 			= $request->getAttribute('route');
		$settings 		= $this->c->get('settings');
		$navigation 	= $this->getNavigation();
		
		# set navigation active
		$navigation['Users']['active'] = true;

		# set standard template
		$template = 'settings/userlist.twig';

		# use vue template for many users
		$totalusers 	= count($users);

		if($totalusers > 10)
		{
			$template = 'settings/userlistvue.twig';
		}
		else
		{
			foreach($users as $username)
			{
				$newuser = $user->getSecureUser($username);
				if($newuser)
				{
					$userdata[] = $newuser;
				}
			}
		}
		
		return $this->render($response, $template, array(
			'settings' 		=> $settings,
			'acl' 			=> $this->c->acl, 
			'navigation' 	=> $navigation, 
			'users' 		=> $users,
			'userdata' 		=> $userdata,
			'userroles' 	=> $this->c->acl->getRoles(),
			'route' 		=> $route->getName() 
		));
	}

	#returns userdata
	public function getUsersByNames($request, $response, $args)
	{
		$params 		= $request->getParams();
		$user			= new User();
		$userdata 		= [];

		if(isset($params['usernames']))
		{
			foreach($params['usernames'] as $username)
			{				
				$existinguser = $user->getSecureUser($username);
				if($existinguser)
				{
					$userdata[] = $existinguser;
				}
			}
		}

		return $response->withJson(['userdata' => $userdata]);		
	}

	# returns userdata
	public function getUsersByEmail($request, $response, $args)
	{
		$params 		= $request->getParams();
		$user			= new User();

		$userdata 		= $user->findUsersByEmail($params['email']);

		return $response->withJson(['userdata' => $userdata ]);
	}

	#returns userdata
	public function getUsersByRole($request, $response, $args)
	{
		$params 		= $request->getParams();
		$user			= new User();

		$userdata 		= $user->findUsersByRole($params['role']);

		return $response->withJson(['userdata' => $userdata ]);
	}	
	
	public function newUser($request, $response, $args)
	{
		$user 			= new User();
		$users			= $user->getUsers();
		$userroles 		= $this->c->acl->getRoles();
		$route 			= $request->getAttribute('route');
		$settings 		= $this->c->get('settings');
		$navigation 	= $this->getNavigation();

		# set navigation active
		$navigation['Users']['active'] = true;

		return $this->render($response, 'settings/usernew.twig', array(
			'settings' 		=> $settings, 
			'acl' 			=> $this->c->acl, 
			'navigation'	=> $navigation,
			'users' 		=> $users, 
			'userrole' 		=> $userroles, 
			'route' 		=> $route->getName() 
		));
	}
		
	public function createUser($request, $response, $args)
	{
		if($request->isPost())
		{
		    if( $request->getattribute('csrf_result') === false )
		    {
				$this->c->flash->addMessage('error', 'The form has a timeout, please try again.');
				return $response->withRedirect($this->c->router->pathFor('user.new'));				
		    }

			$params 		= $request->getParams();
			$user 			= new User();
			$validate		= new Validation();
			$userroles 		= $this->c->acl->getRoles();

			if($validate->newUser($params, $userroles))
			{
				$userdata	= array(
					'username' 		=> $params['username'], 
					'email' 		=> $params['email'], 
					'userrole' 		=> $params['userrole'], 
					'password' 		=> $params['password']);
				
				$user->createUser($userdata);

				$this->c->flash->addMessage('info', 'Welcome, there is a new user!');
				return $response->withRedirect($this->c->router->pathFor('user.list'));
			}
			
			$this->c->flash->addMessage('error', 'Please correct your input');
			return $response->withRedirect($this->c->router->pathFor('user.new'));
		}
	}
	
	public function updateUser($request, $response, $args)
	{

		if($request->isPost())
		{
		    if( $request->getattribute('csrf_result') === false )
		    {
				$this->c->flash->addMessage('error', 'The form has a timeout, please try again.');
				return $response->withRedirect($this->c->router->pathFor('user.account'));
		    }

			$params 		= $request->getParams();
			$userdata 		= $params['user'];
			$user 			= new User();
			$validate		= new Validation();
			$userroles 		= $this->c->acl->getRoles();

			$redirectRoute	= ($userdata['username'] == $_SESSION['user']) ? $this->c->router->pathFor('user.account') : $this->c->router->pathFor('user.show', ['username' => $userdata['username']]);

			# check if user is allowed to view (edit) userlist and other users
			if(!$this->c->acl->isAllowed($_SESSION['role'], 'userlist', 'write'))
			{
				# if an editor tries to update other userdata than its own */
				if($_SESSION['user'] !== $userdata['username'])
				{
					return $response->withRedirect($this->c->router->pathFor('user.account'));
				}
				
				# non admins cannot change their userrole, so set it to session-value
				$userdata['userrole'] = $_SESSION['role'];
			}

			# validate standard fields for users
			if($validate->existingUser($userdata, $userroles))
			{
				# validate custom input fields and return images
				$userfields = $this->getUserFields($userdata['userrole']);
				$imageFields = $this->validateInput('users', 'user', $userdata, $validate, $userfields);

				if(!empty($imageFields))
				{
					$images = $request->getUploadedFiles();

					if(isset($images['user']))
					{
						# set image size
						$settings = $this->c->get('settings');
						$imageSizes = $settings['images'];
						$imageSizes['live'] = ['width' => 500, 'height' => 500];
						$settings->replace(['images' => $imageSizes]);
						$imageresult = $this->saveImages($imageFields, $userdata, $settings, $images['user']);
		
						if(isset($_SESSION['slimFlash']['error']))
						{
							return $response->withRedirect($redirectRoute);
						}
						elseif(isset($imageresult['username']))
						{
							$userdata = $imageresult;
						}
					}
				}

				# check for errors and redirect to path, if errors found */
				if(isset($_SESSION['errors']))
				{
					$this->c->flash->addMessage('error', 'Please correct the errors');
					return $response->withRedirect($redirectRoute);
				}

				if(empty($userdata['password']) AND empty($userdata['newpassword']))
				{
					# make sure no invalid passwords go into model
					unset($userdata['password']);
					unset($userdata['newpassword']);

					$user->updateUser($userdata);
					$this->c->flash->addMessage('info', 'Saved all changes');
					return $response->withRedirect($redirectRoute);
				}
				elseif($validate->newPassword($userdata))
				{
					$userdata['password'] = $userdata['newpassword'];
					unset($userdata['newpassword']);

					$user->updateUser($userdata);
					$this->c->flash->addMessage('info', 'Saved all changes');
					return $response->withRedirect($redirectRoute);
				}
			}

			# change error-array for formbuilder
			$errors = $_SESSION['errors'];
			unset($_SESSION['errors']);
			$_SESSION['errors']['user'] = $errors;#

			$this->c->flash->addMessage('error', 'Please correct your input');
			return $response->withRedirect($redirectRoute);
		}
	}
	
	public function deleteUser($request, $response, $args)
	{
		if($request->isPost())
		{
		    if( $request->getattribute('csrf_result') === false )
		    {
				$this->c->flash->addMessage('error', 'The form has a timeout, please try again.');
				return $response->withRedirect($this->c->router->pathFor('user.account'));				
		    }

			$params 		= $request->getParams();
			$validate		= new Validation();
			$user			= new User();

			# check if user is allowed to view (edit) userlist and other users
			if(!$this->c->acl->isAllowed($_SESSION['role'], 'userlist', 'write'))
			{
				# if an editor tries to delete other user than its own
				if($_SESSION['user'] !== $params['username'])
				{
					return $response->withRedirect($this->c->router->pathFor('user.account'));
				}
			}
			
			if($validate->username($params['username']))
			{
				$userdata = $user->getSecureUser($params['username']);
				if(!$userdata)
				{
					$this->c->flash->addMessage('error', 'Ups, we did not find that user');
					return $response->withRedirect($this->c->router->pathFor('user.show', ['username' => $params['username']]));
				}

				$user->deleteUser($params['username']);

				$this->c->dispatcher->dispatch('onUserDeleted', new OnUserDeleted($userdata));

				# if user deleted his own account
				if($_SESSION['user'] == $params['username'])
				{
					session_destroy();		
					return $response->withRedirect($this->c->router->pathFor('auth.show'));
				}
				
				$this->c->flash->addMessage('info', 'Say goodbye, the user is gone!');
				return $response->withRedirect($this->c->router->pathFor('user.list'));
			}
			
			$this->c->flash->addMessage('error', 'Ups, it is not a valid username');
			return $response->withRedirect($this->c->router->pathFor('user.show', ['username' => $params['username']]));			
		}
	}

	public function clearCache($request, $response, $args)
	{
		$settings 	= $this->c->get('settings');
		$dir 		= $settings['basePath'] . 'cache';
		$iterator 	= new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
		$files 		= new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::CHILD_FIRST);
		
		$error = false;

		foreach($files as $file)
		{
		    if ($file->isDir())
		    {
		    	if(!rmdir($file->getRealPath()))
		    	{
		    		$error = 'Could not delete some folders.';
		    	}
		    }
		    elseif($file->getExtension() !== 'css')
		    {
				if(!unlink($file->getRealPath()) )
				{
					$error = 'Could not delete some files.';
				}
		    }
		}

		if($error)
		{
			return $response->withJson(['errors' => $error], 500);
		}

		return $response->withJson(array('errors' => false));

	}

	private function getUserFields($role)
	{
		# if a plugin with a role has been deactivated, then users with the role throw an error, so set them back to member...
		if(!$this->c->acl->hasRole($role))
		{
			$role = 'member';
		}

		$fields = [];
		$fields['username'] 	= ['label' => 'Username (read only)', 'type' => 'text', 'readonly' => true];
		$fields['firstname'] 	= ['label' => 'First Name', 'type' => 'text'];
		$fields['lastname'] 	= ['label' => 'Last Name', 'type' => 'text'];
		$fields['email'] 		= ['label' => 'E-Mail', 'type' => 'text', 'required' => true];
		$fields['userrole'] 	= ['label' => 'Role', 'type' => 'text', 'readonly' => true];
		$fields['password'] 	= ['label' => 'Actual Password', 'type' => 'password'];
		$fields['newpassword'] 	= ['label' => 'New Password', 'type' => 'password'];

		# dispatch fields;
		$fields = $this->c->dispatcher->dispatch('onUserfieldsLoaded', new OnUserfieldsLoaded($fields))->getData();

		# only roles who can edit content need profile image and description
		if($this->c->acl->isAllowed($role, 'mycontent', 'create'))
		{
			$newfield['image'] 			= ['label' => 'Profile-Image', 'type' => 'image'];
			$newfield['description'] 	= ['label' => 'Author-Description (Markdown)', 'type' => 'textarea'];			
			
			$fields = array_slice($fields, 0, 1, true) + $newfield + array_slice($fields, 1, NULL, true);
			# array_splice($fields,1,0,$newfield);
		}

		# Only admin can change userroles
		if($this->c->acl->isAllowed($_SESSION['role'], 'userlist', 'write'))
		{
			$userroles = $this->c->acl->getRoles();
			$options = [];

			# we need associative array to make select-field with key/value work
			foreach($userroles as $userrole)
			{
				$options[$userrole] = $userrole;
 			}

			$fields['userrole'] = ['label' => 'Role', 'type' => 'select', 'options' => $options];
		}

		$userform = [];
		$userform['forms']['fields'] = $fields;
		return $userform;
	}

	private function getThemes()
	{
		$themeFolder 	= $this->c->get('settings')['rootPath'] . $this->c->get('settings')['themeFolder'];
		$themeFolderC 	= scandir($themeFolder);
		$themes 		= array();
		foreach ($themeFolderC as $key => $theme)
		{
			if (!in_array($theme, array(".","..")))
			{
				if (is_dir($themeFolder . DIRECTORY_SEPARATOR . $theme))
				{
					$themes[] = $theme;
				}
			}
		}
		return $themes;
	}
		
	private function getCopyright()
	{
		return array(
			"©",
			"CC-BY",
			"CC-BY-NC",
			"CC-BY-NC-ND",
			"CC-BY-NC-SA",
			"CC-BY-ND",
			"CC-BY-SA",
			"None"
		);
	}
		
	private function getLanguages()
	{
		return array(
			'en' => 'English',
			'ru' => 'Russian',
			'nl' => 'Dutch, Flemish',
			'de' => 'German',
			'it' => 'Italian',
			'fr' => 'French',
		);
	}

	private function getNavigation()
	{
		$navigation = [
			'System'	=> ['routename' => 'settings.show', 'icon' => 'icon-wrench', 'aclresource' => 'system', 'aclprivilege' => 'view'],
			'Themes'	=> ['routename' => 'themes.show', 'icon' => 'icon-paint-brush', 'aclresource' => 'system', 'aclprivilege' => 'view'],
			'Plugins'	=> ['routename' => 'plugins.show', 'icon' => 'icon-plug', 'aclresource' => 'system', 'aclprivilege' => 'view'],
			'Account'	=> ['routename' => 'user.account', 'icon' => 'icon-user', 'aclresource' => 'user', 'aclprivilege' => 'view'],
			'Users'		=> ['routename' => 'user.list', 'icon' => 'icon-group', 'aclresource' => 'userlist', 'aclprivilege' => 'view']
		];

		# dispatch fields;
		$navigation = $this->c->dispatcher->dispatch('onSystemnaviLoaded', new OnSystemnaviLoaded($navigation))->getData();

		return $navigation;
	}

	private function validateInput($objectType, $objectName, $userInput, $validate, $originalSettings = NULL)
	{
		if(!$originalSettings)
		{
			# fetch the original settings from the folder (plugin or theme) to get the field definitions
			$originalSettings = \Typemill\Settings::getObjectSettings($objectType, $objectName);
		}

		# images get special treatment
		$imageFieldDefinitions = array();

		if(isset($originalSettings['forms']['fields']))
		{
			/* flaten the multi-dimensional array with fieldsets to a one-dimensional array */
			$originalFields = array();
			foreach($originalSettings['forms']['fields'] as $fieldName => $fieldValue)
			{
				if(isset($fieldValue['fields']))
				{
					foreach($fieldValue['fields'] as $subFieldName => $subFieldValue)
					{
						$originalFields[$subFieldName] = $subFieldValue;
					}
				}
				else
				{
					$originalFields[$fieldName] = $fieldValue;
				}
			}
			
			# if the plugin defines frontend fields
			if(isset($originalSettings['public']))
			{
				$originalFields['recaptcha'] = ['type' => 'checkbox', 'label' => 'Google Recaptcha', 'checkboxlabel' => 'Activate Recaptcha' ];
				$originalFields['recaptcha_webkey'] = ['type' => 'text', 'label' => 'Recaptcha Website Key', 'help' => 'Add the recaptcha website key here. You can get the key from the recaptcha website.', 'description' => 'The website key is mandatory if you activate the recaptcha field'];
				$originalFields['recaptcha_secretkey'] = ['type' => 'text', 'label' => 'Recaptcha Secret Key', 'help' => 'Add the recaptcha secret key here. You can get the key from the recaptcha website.', 'description' => 'The secret key is mandatory if you activate the recaptcha field'];
			}

			# if plugin is not active, then skip required
			$skiprequired = false;
			if($objectType == 'plugins' && !isset($userInput['active']))
			{
				$skiprequired = true;
			}
			
			/* take the user input data and iterate over all fields and values */
			foreach($userInput as $fieldName => $fieldValue)
			{
				/* get the corresponding field definition from original plugin settings */
				$fieldDefinition = isset($originalFields[$fieldName]) ? $originalFields[$fieldName] : false;

				if($fieldDefinition)
				{

					# check if the field is a select field with dataset = userroles 
					if(isset($fieldDefinition['type']) && ($fieldDefinition['type'] == 'select' ) && isset($fieldDefinition['dataset']) && ($fieldDefinition['dataset'] == 'userroles' ) )
					{
						$userroles = [null => null];
						foreach($this->c->acl->getRoles() as $userrole)
						{
							$userroles[$userrole] = $userrole;
						}
						$fieldDefinition['options'] = $userroles;
					}

					/* validate user input for this field */
					$validate->objectField($fieldName, $fieldValue, $objectName, $fieldDefinition, $skiprequired);
					
					if($fieldDefinition['type'] == 'image')
					{
						# we want to return all images-fields for further processing
						$imageFieldDefinitions[$fieldName] = $fieldDefinition;
					}
				}
				if(!$fieldDefinition && $objectType != 'users' && $fieldName != 'active')
				{
					$_SESSION['errors'][$objectName][$fieldName] = array('This field is not defined!');
				}
			}
		}

		return $imageFieldDefinitions;
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

}