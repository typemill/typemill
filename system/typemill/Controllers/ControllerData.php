<?php

namespace Typemill\Controllers;

use Typemill\Models\Yaml;
use Typemill\Events\OnSystemnaviLoaded;

# this controller handels data for web and api
# web will use data for twig output
# api will use data for json output
# data controller will provide neutral data

class ControllerData extends Controller
{
	protected function getMainNavigation($userrole)
	{
		$yaml 			= new Yaml('\Typemill\Models\Storage');

		$mainnavi 		= $yaml->getYaml('system/typemill/settings', 'mainnavi.yaml');

		$allowedmainnavi = [];

		$acl 			= $this->c->get('acl');

		foreach($mainnavi as $name => $naviitem)
		{
			if($acl->isAllowed($userrole, $naviitem['aclresource'], $naviitem['aclprivilege']))
			{
				# not nice: check if the navi-item is active (e.g if segments like "content" or "system" is in current url)
				if($name == 'content' && strpos($this->settings['routepath'], 'tm/content'))
				{
					$naviitem['active'] = true;
				}
				elseif($name == 'account' && strpos($this->settings['routepath'], 'tm/account'))
				{
					$naviitem['active'] = true;
				}
				elseif($name == 'system')
				{
					$naviitem['active'] = true;
				}

				$allowedmainnavi[$name] = $naviitem;
			}
		}

		# if system is there, then we do not need the account item
		if(isset($allowedmainnavi['system']))
		{
			unset($allowedmainnavi['account']);
		}

		# set correct editor mode according to user settings
		if(isset($allowedmainnavi['content']) && $this->settings['editor'] == 'raw')
		{
			$allowedmainnavi['content']['routename'] = "content.raw";
		}

		return $allowedmainnavi;
	}

	protected function getSystemNavigation($userrole)
	{
		$yaml 			= new Yaml('\Typemill\Models\Storage');

		$systemnavi 	= $yaml->getYaml('system/typemill/settings', 'systemnavi.yaml');
		$systemnavi 	= $this->c->get('dispatcher')->dispatch(new OnSystemnaviLoaded($systemnavi), 'onSystemnaviLoaded')->getData();

		$allowedsystemnavi = [];

		$acl 			= $this->c->get('acl');

		foreach($systemnavi as $name => $naviitem)
		{
			# check if the navi-item is active (e.g if segments like "content" or "system" is in current url)
			# a bit fragile because url-segment and name/key in systemnavi.yaml and plugins have to be the same
			if(strpos($this->settings['routepath'], 'tm/' . $name))
			{
				$naviitem['active'] = true;
			}

			if($acl->isAllowed($userrole, $naviitem['aclresource'], $naviitem['aclprivilege']))
			{
				$allowedsystemnavi[$name] = $naviitem;
			}
		}

		return $allowedsystemnavi;
	}
}