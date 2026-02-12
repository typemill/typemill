<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;
use Typemill\Models\Multilang;
use Typemill\Models\Meta;
use Typemill\Models\Navigation;
use Typemill\Models\StorageWrapper;
use Typemill\Static\Translations;

class ControllerApiMultilang extends Controller
{
	protected $navigation = false;

	protected $extended = false;

	protected $multilangIndex = false;

	protected $urlinfo = false;

	protected $langattr = false;

    # used for author environment (api based)
	public function getMultilangIndex(Request $request, Response $response, $args)
	{
		$params 			= $request->getQueryParams();
		
		if(!$params['url'])
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('pageid or url is missing'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		$navigation             = new Navigation(); 
		if(!$navigation->checkProjectSettings($this->settings) OR $this->settings['projects'] !== 'languages')
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('multilanguage is not activated'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}
        $navigation->setProject($this->settings, $params['url']);
        $project 				= $navigation->getProject();

        $multilang 				= new Multilang();
		$multilangIndex         = $multilang->getMultilangIndex($navigation->getProject());
        if(!$multilangIndex)
        {
        	$multilangIndex 	= $this->getFreshMultilangIndex($multilang);
            if(!$multilangIndex)
            {
				$response->getBody()->write(json_encode([
					'message' => Translations::translate('could not create multilangindex'),
				]));

				return $response->withHeader('Content-Type', 'application/json')->withStatus(404);            	
            }
        }

		$response->getBody()->write(json_encode([
			'multilangIndex' 			=> $multilangIndex, 
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}

    # used for author environment (api based)
	public function getMultilang(Request $request, Response $response, $args)
	{
		$params 			= $request->getQueryParams();
		
		if(!$params['pageid'] OR !$params['url'])
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('pageid or url is missing'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		$navigation             = new Navigation(); 
		if(!$navigation->checkProjectSettings($this->settings) OR $this->settings['projects'] !== 'languages')
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('multilanguage is not activated'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}
        $navigation->setProject($this->settings, $params['url']);
        $project 				= $navigation->getProject();

        $multilang 				= new Multilang();
		$multilangIndex         = $multilang->getMultilangIndex($navigation->getProject());
        if(!$multilangIndex)
        {
        	$multilangIndex 	= $this->getFreshMultilangIndex($multilang);
            if(!$multilangIndex)
            {
				$response->getBody()->write(json_encode([
					'message' => Translations::translate('could not create multilangindex'),
				]));

				return $response->withHeader('Content-Type', 'application/json')->withStatus(404);            	
            }
        }

        # if it is the base page
        if(!$project)
        {
			$multilangData       	= $multilang->getMultilangData($params['pageid'], $multilangIndex);
			$multilangDefinitions  	= $multilang->getMultilangDefinitions($this->settings, $params['pageid'], $multilangIndex);
        }
        else
        {
        	# it is a language
        	if($params['translationfor'])
        	{
        		# and is related to a base page
				$multilangData       	= $multilang->getMultilangData($params['translationfor'], $multilangIndex);
				$multilangDefinitions  	= $multilang->getMultilangDefinitions($this->settings, $params['translationfor'], $multilangIndex);
        	}
        	else
        	{
        		# and is not related to a base page
        		$multilangData = false;
        		$multilangDefinitions = false;
        	}
        }

        if($multilangData && isset($multilangData['parent']) && $multilangData['parent'])
        {
        	$multilangData['parent'] = $multilang->getMultilangData($multilangData['parent'], $multilangIndex);
        }

		$response->getBody()->write(json_encode([
			'multilangData' 			=> $multilangData, 
			'multilangDefinitions'		=> $multilangDefinitions, 
			'project' 					=> $project
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}

    # used for author environment
	public function createMultilang(Request $request, Response $response, $args)
	{
		$baselang 			= $this->settings['baseprojectid'] ?? false;
		$params 			= $request->getParsedBody();
    	$pageid				= $params['pageid'] ?? false;
		$lang 				= $params['lang'] ?? false; 
		$path 				= $params['path'] ?? false;

		# validate params
		if(!$pageid or !$baselang or !$lang or !$path)
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('pageid, baselang, lang or slug is missing'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);			
		}

# check if lang defined in project settings

		$navigation = new Navigation(); 
		if(!$navigation->checkProjectSettings($this->settings) OR $this->settings['projects'] !== 'languages')
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('multilanguage is not activated'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

        $multilang 			= new Multilang();
		$multilangIndex 	= $multilang->getMultilangIndex();
        if(!$multilangIndex)
        {
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('no index for multilanguage found'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

		$multilangData 	= $multilang->getMultilangData($pageid, $multilangIndex);
        if(!$multilangData)
        {
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('We did not find the page id in the mulitlangindex'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

		# first check if the language folder exists in the content folder
		$baselangfolder = $this->checkBaseFolder($lang);
		if($baselangfolder !== true)
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('We could not create the base language folder.'),
				'error' => $result
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);			
		}

		# first check if the target page already exists
		$targetInfo = $this->getSourceInfo($path, $lang);
		if($targetInfo)
		{
			$storage = new StorageWrapper($this->settings['storage']);

			# if page exists already
			if($targetInfo['extension'])
			{
				$targetPath = $targetInfo['pathWoE'] . '.yaml';
				$this->updateMeta($storage, $targetPath, $pageid);
			}
			else
			{
				$targetPath = $targetInfo['pathWoE'] . DIRECTORY_SEPARATOR . 'index.yaml';
				$this->updateMeta($storage, $targetPath, $pageid);
			}
		}
		else
		{
			# page does not exist yet

			# the original page that should be translated
			$sourceInfo = $this->getSourceInfo($multilangData[$baselang]);

			# the target
			$segments = explode('/', trim($path, '/'));

			if(isset($segments[0]) && $segments[0] == $lang)
			{
				# maybe we do not need that ?
				unset($segments[0]);
			}

			# remove the slug
			$slug = array_pop($segments);

			# now get the parent page for the translated version
			$targetParentInfo = false;
			if(!empty($segments))
			{
				$parentUrl = implode('/', $segments);
				$parentUrl = '/' . $lang . '/' . $parentUrl;
				$targetParentInfo = $this->getSourceInfo($parentUrl, $lang);
				if(!$targetParentInfo)
				{
					$response->getBody()->write(json_encode([
						'message' => Translations::translate('There is no parent url ' . $parentUrl . ', please create the parent page first.'),
					]));

					return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
				}
			}

			$this->copyPage($sourceInfo, $targetParentInfo, $slug, $lang, $pageid);
		}

		# update the multilangIndex
		$multilangIndex[$pageid][$lang] = $path;

		$multilang->storeMultilangIndex($multilangIndex);

		$multilangData 	= $multilang->getMultilangData($pageid, $multilangIndex);
        if($multilangData && isset($multilangData['parent']) && $multilangData['parent'])
        {
        	$multilangData['parent'] = $multilang->getMultilangData($multilangData['parent'], $multilangIndex);
        }

		# send the updated data to the frontend
		$response->getBody()->write(json_encode([
			'multilangData' 			=> $multilangData,
			'autotranslate'				=> $this->settings['autotranslate'] ?? false,
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}

	public function deleteMultilang(Request $request, Response $response, $args)
	{
		$baselang 			= $this->settings['baseprojectid'] ?? false;
		$params 			= $request->getParsedBody();
    	$pageid				= $params['pageid'] ?? false;
		$lang 				= $params['lang'] ?? false; 
		$url 				= $params['url'] ?? false; 

		# validate params
		if(!$pageid or !$baselang or !$lang or !$url)
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('pageid, baselang, lang or url is missing'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);			
		}

		$navigation = new Navigation(); 
		if(!$navigation->checkProjectSettings($this->settings) OR $this->settings['projects'] !== 'languages')
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('multilanguage is not activated'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

        $multilang 			= new Multilang();
		$multilangIndex 	= $multilang->getMultilangIndex();
        if(!$multilangIndex)
        {
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('no index for multilanguage found'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

		$multilangData 	= $multilang->getMultilangData($pageid, $multilangIndex);
        if(!$multilangData)
        {
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('We did not find the page id in the mulitlangindex'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

		# update the multilangIndex
		$multilangIndex[$pageid][$lang] = '';

		$multilang->storeMultilangIndex($multilangIndex);

		# update meta
		$storage 			= new StorageWrapper($this->settings['storage']);
		$targetInfo 		= $this->getSourceInfo($url, $lang);
		$targetPath 		= $targetInfo['path'];

		if($targetInfo['extension'])
		{
			$targetPath = $targetInfo['pathWoE'] . '.yaml';
			$this->updateMeta($storage, $targetPath, '');
		}
		else
		{
			$targetPath = $targetInfo['pathWoE'] . DIRECTORY_SEPARATOR . 'index.yaml';
			$this->updateMeta($storage, $targetPath, '');
		}

		$multilangData 	= $multilang->getMultilangData($pageid, $multilangIndex);
        if($multilangData && isset($multilangData['parent']) && $multilangData['parent'])
        {
        	$multilangData['parent'] = $multilang->getMultilangData($multilangData['parent'], $multilangIndex);
        }

		# send the updated data to the frontend
		$response->getBody()->write(json_encode([
			'multilangData' 			=> $multilangData
		]));

		return $response->withHeader('Content-Type', 'application/json');        
	}

	private function getFreshMultilangIndex($multilang)
	{
    	# create a fresh mulitlang index
        $langattr               = $this->settings['langattr'];
        $urlinfo    			= $this->c->get('urlinfo');
        $meta                   = new Meta();
		$navigation          	= new Navigation(); 
        $draftNav               = $navigation->getFullDraftNavigation($urlinfo, $langattr);

        $multilangIndex         = $multilang->generateMultilangBaseIndex($meta, $draftNav, $this->settings);

        foreach($this->settings['projectinstances'] as $lang => $label)
        {
        	$navigation->setProject($this->settings, $lang);
        	$draftNav 				= $navigation->getFullDraftNavigation($urlinfo, $langattr);
            $multilangIndex         = $multilang->addProjectToIndex($lang, $meta, $draftNav, $multilangIndex);
        }

        if($multilangIndex && is_array($multilangIndex))
        {
            $multilang->storeMultilangIndex($multilangIndex);

            return $multilangIndex;
        }

        return false;
	}

	private function checkBaseFolder($lang)
	{
		$lang = '_' . $lang . DIRECTORY_SEPARATOR;

		$storage = new StorageWrapper($this->settings['storage']);

		$result = true;
		
		if(!$storage->checkFolder('contentFolder', $lang))
		{
			$result = $storage->createFolder('contentFolder', $lang);

			$result = $storage->copyFile('contentFolder', '', 'index.yaml', $lang . 'index.yaml');

## rewrite index.yaml with unique id and translateionfor

			if(!$result)
			{
				return $storage->getError();
			}

			if($storage->checkFile('contentFolder', '', 'index.txt'))
			{
				$result = $storage->copyFile('contentFolder', '', 'index.txt', $lang . 'index.txt');
			}
			elseif($storage->checkFile('contentFolder', '', 'index.md'))
			{
				$result = $storage->copyFile('contentFolder', '', 'index.md', $lang . 'index.txt');
			}

			if(!$result)
			{
				return $storage->getError();				
			}
		}

		return true;
	}

	private function copyPage($sourceInfo, $targetParentInfo, $slug, $lang, $pageid)
	{
		$sourcePath 		= $sourceInfo['path'];

		if($targetParentInfo && isset($targetParentInfo['path']))
		{
			# it has lang prefix already
			$parentPath 	= trim($targetParentInfo['path'], DIRECTORY_SEPARATOR);
			$targetPath 	= $parentPath . DIRECTORY_SEPARATOR . $sourceInfo['index'] . '-' . $slug;
		}
		else
		{
			# it has no lang prefix yet
			$targetPath 	= '_' . $lang . DIRECTORY_SEPARATOR . $sourceInfo['index'] . '-' . $slug;
		}

		$storage = new StorageWrapper($this->settings['storage']);

		# it is a file
		if($sourceInfo['extension'])
		{
			# copy yaml
			$result = $storage->copyFile(
				'contentFolder', 
				'', 
				$sourceInfo['pathWoE'] . '.yaml', 
				$targetPath . '.yaml'
			);

			if(!$result)
			{
				return $storage->getError();
			}

			$this->updateMeta($storage, $targetPath . '.yaml', $pageid);

			# copy content file always as txt (draft)
			$result = $storage->copyFile(
				'contentFolder', 
				'', 
				$sourceInfo['pathWoE'] . '.' . $sourceInfo['extension'], 
				$targetPath . '.txt'
			);

			if(!$result)
			{
				return $storage->getError();
			}
		}
		# it is a folder
		else
		{
			# create folder
			$result = $storage->createFolder('contentFolder', $targetPath);

			# copy yaml
			$result = $storage->copyFile(
				'contentFolder', 
				'', 
				$sourcePath . DIRECTORY_SEPARATOR . 'index.yaml', 
				$targetPath . DIRECTORY_SEPARATOR . 'index.yaml'
			);

			if(!$result)
			{
				return $storage->getError();
			}

			$this->updateMeta($storage, $targetPath . DIRECTORY_SEPARATOR . 'index.yaml', $pageid);

			# copy content file
			if($storage->checkFile('contentFolder', '', $sourcePath . DIRECTORY_SEPARATOR . 'index.txt'))
			{
				$result = $storage->copyFile(
					'contentFolder', 
					'', 
					$sourcePath . DIRECTORY_SEPARATOR . 'index.txt', 
					$targetPath . DIRECTORY_SEPARATOR . 'index.txt'
				);
			}
			elseif($storage->checkFile('contentFolder', '', $sourcePath . DIRECTORY_SEPARATOR . 'index.md'))
			{
				$result = $storage->copyFile(
					'contentFolder', 
					'', 
					$sourcePath . DIRECTORY_SEPARATOR . 'index.md', 
					$targetPath . DIRECTORY_SEPARATOR . 'index.txt');
			}

			if(!$result)
			{
				return $storage->getError();
     		}
		}

		# clear the navigation cache
		$navigation = new Navigation();
		$navigation->setProject($this->settings, $lang);
#		$naviFileName = $navigation->getNaviFileNameForPath($targetPathWithoutLang);
	    $navigation->clearNavigation();

		return true;		
	}

	private function updateMeta($storage, $path, $pageid)
	{
		$meta = $storage->getYaml('contentFolder', '', $path);
		
		if($meta)
		{
	        $meta['meta']['pageid'] = bin2hex(random_bytes(8));
			$meta['meta']['translation_for'] = $pageid;

			$storage->updateYaml('contentFolder', '', $path, $meta);
		}
	}

	private function getSourceInfo($url, $lang = false)
	{
		# load the extended navigation to quickly find the urls in sub functions
        $urlinfo     		= $this->c->get('urlinfo');
        $langattr       	= $this->settings['langattr'];
		$navigation 		= new Navigation();
		if($lang)
		{
			$navigation->setProject($this->settings, $lang);
		}

		$extended 			= $navigation->getFullExtendedNavigation($urlinfo, $langattr);

		if(!$extended)
		{
			return false;
		}

		$sourceInfo 		= $extended[$url] ?? false;
		if(!$sourceInfo)
		{
			return false;
		}
		$sourceInfo['index'] 		= $this->getIndexFromPath($sourceInfo['path']);
		$sourceInfo['extension']	= $this->getExtension($sourceInfo['path']);
		$sourceInfo['pathWoE']		= $sourceInfo['extension'] ? substr($sourceInfo['path'], 0, -(strlen($sourceInfo['extension']) + 1)) : $sourceInfo['path'];

		return $sourceInfo;
	}

	private function getIndexFromPath($path)
	{
		$parts = explode('/', $path);

		$lastSegment = end($parts);

		$index = strtok($lastSegment, '-');

		return $index;	
	}

	private function getExtension($path)
	{
	    $extension = pathinfo($path, PATHINFO_EXTENSION);

	    return $extension;
	}
}