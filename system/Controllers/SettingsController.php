<?php

namespace Typemill\Controllers;

use \Symfony\Component\Yaml\Yaml;
use Typemill\Models\Write;
use Typemill\Models\Fields;
use Typemill\Models\Validation;
use Typemill\Models\User;

class SettingsController extends Controller
{	
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
		$users				= $user->getUsers();
		$route 				= $request->getAttribute('route');
		
		return $this->render($response, 'settings/system.twig', array('settings' => $settings, 'copyright' => $copyright, 'languages' => $languages, 'locale' => $locale, 'formats' => $defaultSettings['formats'] ,'users' => $users, 'route' => $route->getName() ));
	}
	
	public function saveSettings($request, $response, $args)
	{
		if($request->isPost())
		{
			$referer		= $request->getHeader('HTTP_REFERER');
			$uri 			= $request->getUri();
			$base_url		= $uri->getBaseUrl();

			# security, users should not be able to fake post with settings from other typemill pages.
			if(!isset($referer[0]) OR $referer[0] !== $base_url . '/tm/settings' )
			{
				$this->c->flash->addMessage('error', 'illegal referer');
				return $response->withRedirect($this->c->router->pathFor('settings.show'));				
			}
			
			$settings 			= \Typemill\Settings::getUserSettings();
			$defaultSettings	= \Typemill\Settings::getDefaultSettings();
			$params 			= $request->getParams();
			$newSettings		= isset($params['settings']) ? $params['settings'] : false;
			$validate			= new Validation();

			if($newSettings)
			{
				/* make sure only allowed fields are stored */
				$newSettings = array(
					'title' 		=> $newSettings['title'],
					'author' 		=> $newSettings['author'],
					'copyright' 	=> $newSettings['copyright'],
					'year'			=> $newSettings['year'],
					'language'		=> $newSettings['language'],
					'editor' 		=> $newSettings['editor'], 
					'formats'		=> $newSettings['formats'],
				);
				
				$copyright 			= $this->getCopyright();

				$validate->settings($newSettings, $copyright, $defaultSettings['formats'], 'settings');
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
			
			/* store updated settings */
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
		$fieldsModel	= new Fields();

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
		$user		= new User();
		$users		= $user->getUsers();
		$route 		= $request->getAttribute('route');

		return $this->render($response, 'settings/themes.twig', array('settings' => $userSettings, 'themes' => $themedata, 'users' => $users, 'route' => $route->getName() ));
	}
	
	public function showPlugins($request, $response, $args)
	{
		$userSettings 	= $this->c->get('settings');
		$plugins		= array();
		$fieldsModel	= new Fields();
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
		
		$user 	= new User();
		$users 	= $user->getUsers();
		$route 	= $request->getAttribute('route');
		
		return $this->render($response, 'settings/plugins.twig', array('settings' => $userSettings, 'plugins' => $plugins, 'users' => $users, 'route' => $route->getName() ));
	}

	/*************************************
	**	SAVE THEME- AND PLUGIN-SETTINGS	**
	*************************************/

	public function saveThemes($request, $response, $args)
	{
		if($request->isPost())
		{
			$referer		= $request->getHeader('HTTP_REFERER');
			$uri 			= $request->getUri();
			$base_url		= $uri->getBaseUrl();

			# users should not be able to fake post with settings from other typemill pages.
			if(!isset($referer[0]) OR $referer[0] !== $base_url . '/tm/themes' )
			{
				$this->c->flash->addMessage('error', 'illegal referer');
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
				/* validate the user-input */
				$this->validateInput('themes', $themeName, $userInput, $validate);
				
				/* set user input as theme settings */
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
			$referer		= $request->getHeader('HTTP_REFERER');
			$uri 			= $request->getUri();
			$base_url		= $uri->getBaseUrl();

			# security, users should not be able to fake post with settings from other typemill pages.
			if(!isset($referer[0]) OR $referer[0] !== $base_url . '/tm/plugins' )
			{
				$this->c->flash->addMessage('error', 'illegal referer');
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
					/* validate the user-input */
					$this->validateInput('plugins', $pluginName, $userInput[$pluginName], $validate);

					/* use the input data */
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

	private function validateInput($objectType, $objectName, $userInput, $validate)
	{
		/* fetch the original settings from the folder (plugin or theme) to get the field definitions */
		$originalSettings = \Typemill\Settings::getObjectSettings($objectType, $objectName);

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
					/* validate user input for this field */
					$validate->objectField($fieldName, $fieldValue, $objectName, $fieldDefinition, $skiprequired);
				}
				if(!$fieldDefinition && $fieldName != 'active')
				{
					$_SESSION['errors'][$objectName][$fieldName] = array('This field is not defined!');
				}
			}
		}
	}

	/***********************
	**   USER MANAGEMENT  **
	***********************/
	
	public function showUser($request, $response, $args)
	{
		if($_SESSION['role'] == 'editor' && $_SESSION['user'] !== $args['username'])
		{
			return $response->withRedirect($this->c->router->pathFor('user.show', ['username' => $_SESSION['user']] ));
		}
		
		$validate 	= new Validation();
		
		if($validate->username($args['username']))
		{
			$user 		= new User();
			$users		= $user->getUsers();
			$userrole	= $user->getUserroles();
			$userdata 	= $user->getUser($args['username']);
			$settings 	= $this->c->get('settings');
			
			if($userdata)
			{				
				return $this->render($response, 'settings/user.twig', array('settings' => $settings, 'users' => $users, 'userdata' => $userdata, 'userrole' => $userrole, 'username' => $args['username'] ));
			}
		}
		
		$this->c->flash->addMessage('error', 'User does not exists');
		return $response->withRedirect($this->c->router->pathFor('user.list'));
	}

	public function listUser($request, $response)
	{
		$user		= new User();
		$users		= $user->getUsers();
		$userdata 	= array();
		$route 		= $request->getAttribute('route');
		$settings 	= $this->c->get('settings');
		
		foreach($users as $username)
		{
			$userdata[] = $user->getUser($username);
		}
		
		return $this->render($response, 'settings/userlist.twig', array('settings' => $settings, 'users' => $users, 'userdata' => $userdata, 'route' => $route->getName() ));		
	}
	
	public function newUser($request, $response, $args)
	{
		$user 		= new User();
		$users		= $user->getUsers();
		$userrole	= $user->getUserroles();
		$route 		= $request->getAttribute('route');
		$settings 	= $this->c->get('settings');

		return $this->render($response, 'settings/usernew.twig', array('settings' => $settings, 'users' => $users, 'userrole' => $userrole, 'route' => $route->getName() ));
	}
		
	public function createUser($request, $response, $args)
	{
		if($request->isPost())
		{
			$referer		= $request->getHeader('HTTP_REFERER');
			$uri 			= $request->getUri();
			$base_url		= $uri->getBaseUrl();

			# security, users should not be able to fake post with settings from other typemill pages.
			if(!isset($referer[0]) OR $referer[0] !== $base_url . '/tm/user/new' )
			{
				$this->c->flash->addMessage('error', 'illegal referer');
				return $response->withRedirect($this->c->router->pathFor('user.new'));
			}
			
			$params 		= $request->getParams();
			$user 			= new User();
			$userroles		= $user->getUserroles();
			$validate		= new Validation();

			if($validate->newUser($params, $userroles))
			{
				$userdata	= array('username' => $params['username'], 'firstname' => $params['firstname'], 'lastname' => $params['lastname'], 'email' => $params['email'], 'userrole' => $params['userrole'], 'password' => $params['password']);
				
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
			$referer		= $request->getHeader('HTTP_REFERER');
			$uri 			= $request->getUri();
			$base_url		= $uri->getBaseUrl();

			# security, users should not be able to fake post with settings from other typemill pages.
			if(!isset($referer[0]) OR strpos($referer[0], $base_url . '/tm/user/') === false )
			{
				$this->c->flash->addMessage('error', 'illegal referer');
				return $response->withRedirect($this->c->router->pathFor('user.list'));
			}
			
			$params 		= $request->getParams();
			$user 			= new User();
			$userroles		= $user->getUserroles();
			$validate		= new Validation();
			
			/* non admins have different update rights */
			if($_SESSION['role'] !== 'administrator')
			{
				/* if an editor tries to update other userdata than its own */
				if($_SESSION['user'] !== $params['username'])
				{
					return $response->withRedirect($this->c->router->pathFor('user.show', ['username' => $_SESSION['user']] ));
				}
				
				/* non admins cannot change his userrole */
				$params['userrole'] = $_SESSION['role'];
			}
	
			if($validate->existingUser($params, $userroles))
			{
				$userdata	= array('username' => $params['username'], 'firstname' => $params['firstname'], 'lastname' => $params['lastname'], 'email' => $params['email'], 'userrole' => $params['userrole']);
				
				if(empty($params['password']) AND empty($params['newpassword']))
				{
					$user->updateUser($userdata);
					$this->c->flash->addMessage('info', 'Saved all changes');
					return $response->withRedirect($this->c->router->pathFor('user.show', ['username' => $params['username']]));
				}
				elseif($validate->newPassword($params))
				{
					$userdata['password'] = $params['newpassword'];				
					$user->updateUser($userdata);
					$this->c->flash->addMessage('info', 'Saved all changes');
					return $response->withRedirect($this->c->router->pathFor('user.show', ['username' => $params['username']]));
				}
			}
			
			$this->c->flash->addMessage('error', 'Please correct your input');
			return $response->withRedirect($this->c->router->pathFor('user.show', ['username' => $params['username']]));
		}
	}
	
	public function deleteUser($request, $response, $args)
	{
		if($request->isPost())
		{
			$referer		= $request->getHeader('HTTP_REFERER');
			$uri 			= $request->getUri();
			$base_url		= $uri->getBaseUrl();

			# security, users should not be able to fake post with settings from other typemill pages.
			if(!isset($referer[0]) OR strpos($referer[0], $base_url . '/tm/user/') === false )
			{
				$this->c->flash->addMessage('error', 'illegal referer');
				return $response->withRedirect($this->c->router->pathFor('user.list'));
			}
			
			$params 		= $request->getParams();
			$validate		= new Validation();
			$user			= new User();

			/* non admins have different update rights */
			if($_SESSION['role'] !== 'administrator')
			{
				/* if an editor tries to delete other user than its own */
				if($_SESSION['user'] !== $params['username'])
				{
					return $response->withRedirect($this->c->router->pathFor('user.show', ['username' => $_SESSION['user']] ));
				}				
			}
			
			if($validate->username($params['username']))
			{
				$user->deleteUser($params['username']);

				# if user deleted his own account
				if($_SESSION['user'] == $params['username'])
				{
					session_destroy();		
					return $response->withRedirect($this->c->router->pathFor('auth.show'));
				}
				
				$this->c->flash->addMessage('info', 'Say goodbye, the user is gone!');
				return $response->withRedirect($this->c->router->pathFor('user.list'));			
			}
			
			$this->c->flash->addMessage('error', 'Ups, we did not find that user');
			return $response->withRedirect($this->c->router->pathFor('user.show', ['username' => $params['username']]));			
		}
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
			'nl' => 'Dutch, Flemish',
			'en' => 'English',
			'de' => 'German',
			'it' => 'Italian',
		);
	}
}