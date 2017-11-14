<?php

namespace Typemill\Controllers;

use \Symfony\Component\Yaml\Yaml;

class SetupController extends Controller
{
	public function setup($request, $response, $args)
	{				
		$themes 		= $this->getThemes();
		$copyright 		= $this->getCopyright();
		$uri 			= $request->getUri();
		$base_url		= $uri->getBaseUrl();
		$errors 		= false;
		
		/* Check, if setting folder is writable */
		if(!is_writable($this->c->get('settings')['settingsPath'])){ $errors['folder'] = 'Your settings folder is not writable.'; }
				
		$data = array(
			'themes' 	=> $themes,
			'copyright'	=> $copyright,
			'inputs'	=> false,
			'errors'	=> $errors,
			'base_url'	=> $base_url
		);
		$this->c->view->render($response, '/setup.twig', $data);
	}
	
	public function save($request, $response, $args)
	{
		if($request->isPost())
		{
			$params 	= $request->getParams();
			
			$copyright	= $this->getCopyright();
			$themes		= $this->getThemes();
			$errors 	= array();
			$uri 		= $request->getUri();
			$base_url	= $uri->getBaseUrl();

			/* Validate Title */
			if(!isset($params['title'])){ $errors['title'] = 'Please add a title. '; }
			if(strlen($params['title']) < 2){ $errors['title'] = 'Title is too short (< 2). '; }
			if(strlen($params['title']) > 20){ $errors['title'] = 'Title is too long (> 20). '; }			
			
			/* Validate Author */
			if(isset($params['author']) && !empty($params['author']))
			{
				if(strlen($params['author']) < 2){ $errors['author'] = 'Text is too short (< 2). '; }
				if(strlen($params['author']) > 40){ $errors['author'] .= 'Text is too long (> 40). '; } 
				if(preg_match('/[\(\)\[\]\{\}\?\*\$\"\'\|<>=!;@#%§]/', $params['author'])){ $errors['author'] .= 'Only special chars like a,b a-b a_b a&b are allowed.'; }
			}
			
			/* Validate Year */
			if(!isset($params['year'])){ $errors['year'] = 'Please add a year, e.g. 2017.'; }
			if(!preg_match('/^(\d{4})$/', $params['year'])){ $errors['year'] = 'Use four digits for the year like 2017.'; }
			
			/* Validate Copyright */
			if(isset($params['copyright']) AND !in_array($params['copyright'], $copyright )){ $errors['copyright'] = 'Please select a valid copyright.'; }
			
			/* Validate Theme */
			if(!isset($params['theme']) AND !in_array($params['theme'], $themes)){ $errors['theme'] = 'Please select a valid theme.'; }
			
			/* Validate Startpage */
			if(isset($params['startpage'])){ $params['startpage'] = true; }else{ $params['startpage'] = false; }
			
			/* Validate Folder Writable */
			if(!is_writable($this->c->get('settings')['settingsPath'])){ $errors['folder'] = 'Your settings folder is not writable.'; }
			
			/* Prevent Title From Hacking */
			$params['title'] = htmlentities(stripslashes($params['title']));
			
			if(!empty($errors))
			{
				$data = array(
					'themes' 	=> $themes,
					'copyright' => $copyright,
					'errors'	=> $errors,
					'inputs'	=> $params,
					'base_url'	=> $base_url
					
				);
				$this->c->view->render($response, '/setup.twig', $data);
			}
			else
			{
				$file 			= $this->c->get('settings')['settingsPath'] . DIRECTORY_SEPARATOR . 'settings.yaml';
				$fh 			= fopen($file, 'w');
				$yaml 			= Yaml::dump($params);
				
				file_put_contents($file, $yaml);

				$data = array(
					'inputs'	=> $params,
					'base_url'	=> $base_url
					
				);
				
				$this->c->view->render($response, '/welcome.twig', $data);
			}
		}
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
}