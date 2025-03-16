<?php 

namespace Typemill\Static;

use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Role\GenericRole as Role;
use Laminas\Permissions\Acl\Resource\GenericResource as Resource;

class Permissions
{
	public static function loadResources($defaultsettingspath)
	{
		$resourcesfile = $defaultsettingspath . 'resources.yaml';

		if(file_exists($resourcesfile))
		{
			$resourcesyaml  = file_get_contents($resourcesfile);
			$resources 		= \Symfony\Component\Yaml\Yaml::parse($resourcesyaml);
			
			return $resources;
		}

		return false;
	}

	public static function loadRolesAndPermissions($defaultsettingspath)
	{

		$permissionsfile = $defaultsettingspath . 'permissions.yaml';

		if(file_exists($permissionsfile))
		{
			$permissionsyaml  	= file_get_contents($permissionsfile);
			$permissions 		= \Symfony\Component\Yaml\Yaml::parse($permissionsyaml);
			
			return $permissions;
		}

		return false;
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