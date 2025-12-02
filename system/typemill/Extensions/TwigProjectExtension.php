<?php

namespace Typemill\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Typemill\Models\StorageWrapper;

class TwigProjectExtension extends AbstractExtension
{
	public function getFunctions()
	{
		return [
			new TwigFunction('getProjectsHome', array($this, 'getProjectsHome' ))
		];
	}

	public function getProjectsHome($settings)
	{
		$projects = [];

		$storage = new StorageWrapper($settings['storage']);

		if(isset($settings['projectinstances']) && !empty($settings['projectinstances']))
		{
			foreach($settings['projectinstances'] as $id => $label)
			{
				$meta = $storage->getYaml('contentFolder', '_'.$id, 'index.yaml');

				$projects[$id] = [
					'label' 	=> $label,
					'meta'		=> $meta
				];
			}
		}

		return $projects;
	}
}