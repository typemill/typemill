<?php

namespace Plugins\demouser;

use \Typemill\Plugin;

class Demouser extends Plugin
{
    public static function getSubscribedEvents()
    {
        return array(
			'onSystemnaviLoaded'			=> 'onSystemnaviLoaded',
            'onRolesPermissionsLoaded'		=> 'onRolesPermissionsLoaded',
			'onPageReady'					=> 'onPageReady',
        );
    }

	# add routes
	public static function addNewRoutes()
	{
		return [
			['httpMethod' => 'get', 'route' => '/tm/demoaccess', 'name' => 'demoaccess.show', 'class' => 'Typemill\Controllers\ControllerSettings:showBlank', 'resource' => 'user', 'privilege' => 'view'],
		];
	}

	# add new navi-items into the admin settings
	public function onSystemnaviLoaded($navidata)
	{
		$this->addSvgSymbol('<symbol id="icon-key" viewBox="0 0 32 32"><path d="M22 0c-5.523 0-10 4.477-10 10 0 0.626 0.058 1.238 0.168 1.832l-12.168 12.168v6c0 1.105 0.895 2 2 2h2v-2h4v-4h4v-4h4l2.595-2.595c1.063 0.385 2.209 0.595 3.405 0.595 5.523 0 10-4.477 10-10s-4.477-10-10-10zM24.996 10.004c-1.657 0-3-1.343-3-3s1.343-3 3-3 3 1.343 3 3-1.343 3-3 3z"></path></symbol>');
		$navi = $navidata->getData();

		$navi['Demoaccess'] = ['routename' => 'demoaccess.show', 'icon' => 'icon-key', 'aclresource' => 'user', 'aclprivilege' => 'view'];

		# set the navigation item active
		if(trim($this->getPath(),"/") == 'tm/demoaccess')
		{
			$navi['Demoaccess']['active'] = true;
		}

		$navidata->setData($navi);
	}


    public function onRolesPermissionsLoaded($rolesAndPermissions)
    {
	    $rolesPermissions = $rolesAndPermissions->getData();

	    $demoauthor = [
	         'name' => 'demoauthor',
	         'inherits' => 'author',
	         'permissions' => [
	               'mycontent' => ['delete'],
	               'content' => ['create', 'update'],
	         ]
	    ];
	    $rolesPermissions['demoauthor'] = $demoauthor;
	    $rolesAndPermissions->setData($rolesPermissions);
    }

	# show subscriberlist in admin area
	public function onPageReady($data)
	{
		# admin stuff
		if($this->adminpath && $this->path == 'tm/demoaccess')
		{
			$settings 		= $this->getSettings();
			$username 		= isset($settings['plugins']['demouser']['demouser']) ? $settings['plugins']['demouser']['demouser'] : 'not set';
			$password 		= isset($settings['plugins']['demouser']['demopassword']) ? $settings['plugins']['demouser']['demopassword'] : 'not set';

			$pagedata 		= $data->getData();

			$twig 			= $this->getTwig();
			$loader 		= $twig->getLoader();
			$loader->addPath(__DIR__ . '/templates');
				
			# fetch the template and render it with twig
			$content 		= $twig->fetch('/demouser.twig', ['username' => $username, 'password' => $password]);

			$pagedata['content'] = $content;

			$data->setData($pagedata);
		}
	}

}