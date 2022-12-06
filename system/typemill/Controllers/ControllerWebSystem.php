<?php

namespace Typemill\Controllers;

use Typemill\Models\Yaml;
use Typemill\Models\User;

class ControllerWebSystem extends ControllerData
{	
	public function showSettings($request, $response, $args)
	{
		$yaml 			= new Yaml('\Typemill\Models\Storage');
		$systemfields 	= $yaml->getYaml('system/typemill/settings', 'system.yaml');
		$translations 	= $this->c->get('translations');

		# add full url for sitemap to settings
		$this->settings['sitemap'] = $this->c->get('urlinfo')['baseurl'] . '/cache/sitemap.xml';

	    return $this->c->get('view')->render($response, 'system/system.twig', [
#			'basicauth'			=> $user->getBasicAuth(),
			'settings' 			=> $this->settings,
			'mainnavi'			=> $this->getMainNavigation($request->getAttribute('userrole')),
			'systemnavi'		=> $this->getSystemNavigation($request->getAttribute('userrole')),
			'jsdata' 			=> [
										'settings' 		=> $this->settings,
										'system'		=> $systemfields,
										'labels'		=> $translations,
										'urlinfo'		=> $this->c->get('urlinfo')
									]
			#'captcha' => $this->checkIfAddCaptcha(),
	    ]);
	}

	public function showThemes($request, $response, $args)
	{
		$yaml 			= new Yaml('\Typemill\Models\Storage');
		$translations 	= $this->c->get('translations');
		$themeSettings 	= $this->getThemeDetails();

		$themedata = [];

		foreach($this->settings['themes'] as $themename => $themeinputs)
		{
			$themedata[$themename] = $themeinputs;
			$themedata[$themename]['customcss'] = $yaml->getFile('cache', $themename . '-custom.css');
		}

	    return $this->c->get('view')->render($response, 'system/themes.twig', [
			'settings' 			=> $this->settings,
			'mainnavi'			=> $this->getMainNavigation($request->getAttribute('userrole')),
			'systemnavi'		=> $this->getSystemNavigation($request->getAttribute('userrole')),
			'jsdata' 			=> [
										'settings' 		=> $themedata,
										'themes'		=> $themeSettings,
										'labels'		=> $translations,
										'urlinfo'		=> $this->c->get('urlinfo')
									]
	    ]);
	}

	public function showPlugins($request, $response, $args)
	{
#		$yaml 				= new Yaml('\Typemill\Models\Storage');
		$translations 		= $this->c->get('translations');
		$pluginSettings 	= $this->getPluginDetails();

		$plugindata = [];

		foreach($this->settings['plugins'] as $pluginname => $plugininputs)
		{
			$plugindata[$pluginname] = $plugininputs;
		}

	    return $this->c->get('view')->render($response, 'system/plugins.twig', [
			'settings' 			=> $this->settings,
			'mainnavi'			=> $this->getMainNavigation($request->getAttribute('userrole')),
			'systemnavi'		=> $this->getSystemNavigation($request->getAttribute('userrole')),
			'jsdata' 			=> [
										'settings' 		=> $plugindata,
										'plugins'		=> $pluginSettings,
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
			'mainnavi'			=> $this->getMainNavigation($request->getAttribute('userrole')),
			'systemnavi'		=> $this->getSystemNavigation($request->getAttribute('userrole')),
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

	public function showAccount($request, $response, $args)
	{

		$translations 		= $this->c->get('translations');	
		$username			= $request->getAttribute('username');
		$user				= new User();

		$user->setUser($username);
		$userdata			= $user->getUserData();
		$userfields 		= $this->getUserFields($userdata['userrole']);

	    return $this->c->get('view')->render($response, 'system/account.twig', [
			'settings' 			=> $this->settings,
			'mainnavi'			=> $this->getMainNavigation($request->getAttribute('userrole')),
			'systemnavi'		=> $this->getSystemNavigation($request->getAttribute('userrole')),
			'jsdata' 			=> [
										'userdata'		=> $userdata,
										'userfields'	=> $userfields,
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
		$navigation = $this->getMainNavigation();

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

	
	public function newUser($request, $response, $args)
	{
		$user 			= new User();
		$users			= $user->getUsers();
		$userroles 		= $this->c->acl->getRoles();
		$route 			= $request->getAttribute('route');
		$settings 		= $this->c->get('settings');
		$navigation 	= $this->getMainNavigation();

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
				# if an editor tries to update other userdata than its own 
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

				# check for errors and redirect to path, if errors found 
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