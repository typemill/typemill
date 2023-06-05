<?php 

namespace Typemill;

use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Role\GenericRole as Role;
use Laminas\Permissions\Acl\Resource\GenericResource as Resource;

class Settings
{	
	public static function loadSettings()
	{
		$defaultSettings 	= self::getDefaultSettings();
		$userSettings 		= self::getUserSettings();		
		
		$settings 			= $defaultSettings;

		if($userSettings)
		{
			$settings 			= array_merge($defaultSettings, $userSettings);
		}

		# no individual image sizes are allowed since 1.3.4
		# $settings['images']	= $defaultSettings['images'];
		# make sure that thumb sizes are still there.
		$images = array_merge($defaultSettings['images'], $settings['images']);
		$settings['images'] = $images;

		# we have to check if the theme has been deleted
		$themefolder = $settings['rootPath'] . $settings['themeFolder'] . DIRECTORY_SEPARATOR;

		# if there is no theme in settings or theme has been deleted
		if(!isset($settings['theme']) OR !file_exists($themefolder . $settings['theme']))
		{
			# scan theme folder and get the first theme
			$themes = array_filter(scandir($themefolder), function ($item) use($themefolder) {
				return is_dir($themefolder . $item) && strpos($item, '.') !== 0;
			});
			$firsttheme = reset($themes);

			# if there is a theme with an index.twig-file
			if($firsttheme && file_exists($themefolder . $firsttheme . DIRECTORY_SEPARATOR . 'index.twig'))
			{
				$settings['theme'] = $firsttheme;
			}
			else
			{
				die('You need at least one theme with an index.twig-file in your theme-folder.');
			}
		}

		# We know the used theme now so create the theme path 
		$settings['themePath'] = $settings['rootPath'] . $settings['themeFolder'] . DIRECTORY_SEPARATOR . $settings['theme'];

		# if there are no theme settings yet (e.g. no setup yet) use default theme settings
		if(!isset($settings['themes']))
		{
			$themeSettings = self::getObjectSettings('themes', $settings['theme']);
			$settings['themes'][$settings['theme']] = isset($themeSettings['settings']) ? $themeSettings['settings'] : false;
		}

		return array('settings' => $settings);
	}
	
	public static function getDefaultSettings()
	{
		$rootPath = __DIR__ . DIRECTORY_SEPARATOR .  '..' . DIRECTORY_SEPARATOR;

		return [
			'determineRouteBeforeAppMiddleware' 	=> true,
			'title'									=> 'TYPEMILL',
			'author'								=> 'Unknown',
			'copyright'								=> 'Copyright',
			'language'								=> 'en',
			'langattr'								=> 'en',
			'startpage'								=> true,
			'rootPath'								=> $rootPath,
			'themeFolder'							=> 'themes',
			'themeBasePath'							=> $rootPath,
			'themePath'								=> '',
			'settingsPath'							=> $rootPath . 'settings',
			'userPath'								=> $rootPath . 'settings' . DIRECTORY_SEPARATOR . 'users',
			'authorPath'							=> __DIR__ . DIRECTORY_SEPARATOR . 'author' . DIRECTORY_SEPARATOR,
			'editor'								=> 'visual',
			'formats'								=> ['markdown', 'headline', 'ulist', 'olist', 'table', 'quote', 'notice', 'image', 'video', 'file', 'toc', 'hr', 'definition', 'code', 'shortcode'],
			'contentFolder'							=> 'content',
			'version'								=> '1.5.3.4',
			'setup'									=> true,
			'welcome'								=> true,
			'maxuploadsize'							=> 20,
			'images'								=> ['live' => ['width' => 820], 'thumbs' => ['width' => 250, 'height' => 150]],
		];
	}
	
	public static function getUserSettings()
	{
		$yaml = new Models\WriteYaml();
		
		$userSettings = $yaml->getYaml('settings', 'settings.yaml');
		
		return $userSettings;
	}


  	public static function whichLanguage()
  	{
    	# Check which languages are available
    	$langs = [];
    	$path = __DIR__ . '/author/languages/*.yaml';
    	
    	foreach (glob($path) as $filename) 
    	{
      		$langs[] = basename($filename,'.yaml');
    	}
    
    	# Detect browser language
    	$accept_lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : false;
    	$lang = in_array($accept_lang, $langs) ? $accept_lang : 'en';

    	return $lang;
  	}

	public static function getObjectSettings($objectType, $objectName)
	{
		$yaml = new Models\WriteYaml();
		
		$objectFolder 	= $objectType . DIRECTORY_SEPARATOR . $objectName;
		$objectFile		= $objectName . '.yaml';
		$objectSettings = $yaml->getYaml($objectFolder, $objectFile);

		return $objectSettings;
	}
  
	public static function createSettings()
	{
		$yaml = new Models\WriteYaml();

    	$language = self::whichLanguage();
    
		# create initial settings file with only setup false
		if($yaml->updateYaml('settings', 'settings.yaml', array('setup' => false, 'language' => $language)))
		{
			return true; 
		}
		return false;
	}

	public static function updateSettings($settings)
	{
		# only allow if usersettings already exists (setup has been done)
		$userSettings 	= self::getUserSettings();
		
		if($userSettings)
		{
			# whitelist settings that can be stored in usersettings (values are not relevant here, only keys)
			$allowedUserSettings = ['displayErrorDetails' => true,
									'title' => true,
									'copyright' => true,
									'language' => true,
									'langattr' => true,
									'startpage' => true,
									'author' => true,
									'year' => true,
									'access' => true,
									'pageaccess' => true,
									'hrdelimiter' => true,
									'restrictionnotice' => true,
									'wraprestrictionnotice' => true,
									'headlineanchors' => true,
									'theme' => true,
									'editor' => true,
									'formats' => true,
									'setup' => true,
									'welcome' => true,
									'images' => true,
									'live' => true,
									'width' => true,
									'height' => true,
									'plugins' => true,
									'themes' => true,
									'latestVersion' => true,
									'logo' => true,
									'favicon' => true,
									'twigcache' => true,
									'proxy' => true,
									'trustedproxies' => true,
									'headersoff' => true,
									'schemelessbaseurl' => true,
									'urlschemes' => true,
									'svg' => true,
									'recoverpw' => true,
									'recoversubject' => true,
									'recovermessage' => true,
									'recoverfrom' => true,
									'securitylog' => true,
									'oldslug' => true,
									'refreshcache' => true,
									'pingsitemap' => true,
									'maxuploadsize' => true,
								];

			# cleanup the existing usersettings
			$userSettings = array_intersect_key($userSettings, $allowedUserSettings);

			# cleanup the new settings passed as an argument
			$settings 	= array_intersect_key($settings, $allowedUserSettings);
			
			# merge usersettings with new settings
			$settings 	= array_merge($userSettings, $settings);

			# write settings to yaml
			$yaml = new Models\WriteYaml();
			$yaml->updateYaml('settings', 'settings.yaml', $settings);					
		}
	}

	public static function loadResources()
	{
		return ['content',
				'mycontent',
				'user',
				'userlist',
				'system'];
	}

	public static function loadRolesAndPermissions()
	{
		$member['name']			= 'member';
		$member['inherits'] 	= NULL;
		$member['permissions']	= ['user' => ['view','update','delete']];

		$author['name']			= 'author';
		$author['inherits']		= 'member';
		$author['permissions']	= ['mycontent' => ['view', 'create', 'update'],
								   'content' => ['view']];

		$editor['name']			= 'editor';
		$editor['inherits']		= 'author';
		$editor['permissions']	= [ 'mycontent' => ['delete', 'publish', 'unpublish'],
									'content' => ['create', 'update', 'delete', 'publish', 'unpublish']];

		return ['member' => $member,'author' => $author, 'editor' => $editor];
	}

	public static function createAcl($roles, $resources)
	{
		$acl = new Acl();

		foreach($resources as $resource)
		{
			$acl->addResource(new Resource($resource));
		}

		# add all other roles dynamically
		foreach($roles as $role)
		{
			$acl->addRole(new Role($role['name']), $role['inherits']);

			foreach($role['permissions'] as $resource => $permissions)
			{
				$acl->allow($role['name'], $resource, $permissions);
			}
		}

		# add administrator role
		$acl->addRole(new Role('administrator'));
		$acl->allow('administrator');

		return $acl;
	}
}