<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;
use Typemill\Models\Multilang;
use Typemill\Models\Meta;
use Typemill\Models\Navigation;
use Typemill\Models\Content;
use Typemill\Models\StorageWrapper;
use Typemill\Models\Validation;
use Typemill\Static\Translations;


class ControllerApiMultilang extends Controller
{
	protected $navigation = false;

	protected $extended = false;

	protected $multilangIndex = false;

	protected $urlinfo = false;

	protected $langattr = false;

    # used for author environment (api based)
	public function getMultilang(Request $request, Response $response, $args)
	{
    	$pageid				= $args['pageid'];

		if(!$pageid)
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('pageid is missing'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		$navigation             = new Navigation();
 
		# add multilanguage definitions if active
		if(!$navigation->checkMultilangSettings($this->settings))
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('multilanguage is not activated'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

        $multilang 				= new Multilang();

		$multilangIndex         = $multilang->getMultilangIndex();
        if(!$multilangIndex)
        {
            $urlinfo                = $this->c->get('urlinfo');
            $langattr               = $this->settings['langattr'];
            $meta                   = new Meta();
            $draftNav               = $navigation->getFullDraftNavigation($urlinfo, $langattr);
            $multilangIndex         = $multilang->generateMultilangIndex($meta, $draftNav, $this->settings);
            if($multilangIndex && is_array($multilangIndex))
            {
                $multilang->storeMultilangIndex($multilangIndex);
            }
        }

		$multilangData            = $multilang->getMultilangData($pageid, $multilangIndex);
		$multilangDefinitions     = $multilang->getMultilangDefinitions($this->settings, $pageid, $multilangIndex);

		$response->getBody()->write(json_encode([
			'multilangData' 			=> $multilangData, 
			'multilangDefinitions'		=> $multilangDefinitions, 
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}

    # used for author environment (api based)
	public function createMultilang(Request $request, Response $response, $args)
	{
		$baselang 			= $this->settings['baselangcode'] ?? false;
    	$pageid				= $args['pageid'];
		$params 			= $request->getParsedBody();
		$lang 				= $params['lang'] ?? false; 
		$slug 				= $params['slug'] ?? false;

		/*
		{
			"lang":"de",
			"slug":"erstellle-deine-erste-seite",
		}
		*/

		if(!$pageid)
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('pageid is missing'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		# validate params
		if(!$baselang or !$lang or !$slug)
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('baselang, lang or slug is missing'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);			
		}

        $multilang = new Multilang();

		# add multilanguage definitions if active
		if(!$multilang->checkMultilangSettings($this->settings))
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('multilanguage is not activated'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		$multilangIndex           = $multilang->getMultilangIndex();
        if(!$multilangIndex)
        {
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('no index for multilanguage found'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

		$multilangData 	= $multilang->getMultilangData($pageid, $multilangIndex);
		/*
			"multilangData":
			{
				"en":"create-your-first-page",
				"de":false,
				"it":false,
				"parent":"c6123049882e71e0",
				"path":
				{
					"en":["getting-started","create-your-first-page"],
					"de":[false,false],
					"it":[false,false]
				}
			}
		*/

		$baseUrlSegments 	= $multilangData['path'][$baselang] ?? false;
		if(!$baseUrlSegments or !is_array($baseUrlSegments))
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('we did not find a path to the base-language page.'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		$targetUrlSegments 	= $multilangData['path'][$lang] ?? false;
		if(!$targetUrlSegments or !is_array($targetUrlSegments))
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('we did not find a path to the target-language page.'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		# check if a parentPage is missing
		$parentsMissing = in_array(false, array_slice($targetUrlSegments, 0, -1), true);
		if($parentsMissing)
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('Please create the missing parent pages first.'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		# first check if the language folder exists in the content folder
		$result = $this->checkBaseFolder($lang);
		if($result !== true)
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('We could not create the base language folder.'),
				'error' => $result
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);			
		}

#		$multilangParentData = $multilang->getMultilangData($multilangData['parent'], $multilangIndex);

		$lastKey = array_key_last($targetUrlSegments);

		# and page did not exist yet
		if($targetUrlSegments[$lastKey] === false)
		{
			# use the new slug for the new language page
			$targetUrlSegments[$lastKey] = $slug;

			# create a new page for the missing segment
			$newpage = $this->createNewLanguagePage(
				$lang, # to know the language in content folder 
				$lastKey, # to know the segment in targetpath and basepath
				$baseUrlSegments, # to know where to find the file or folder in the baselanguage content folder
				$targetUrlSegments, # to know where to copy the base language file in the language folder
			);

			if($newpage !== true)
			{
				$response->getBody()->write(json_encode([
					'message' => Translations::translate('We could not create the new page.'),
					'error' => $newpage
				]));

				return $response->withHeader('Content-Type', 'application/json')->withStatus(404);							
			}
		}
		# slug has been renamed
		elseif($targetUrlSegments[$lastKey] !== $slug)
		{
			# rename the page
		}

		# update the multilangIndex
		$multilangIndex[$pageid][$lang] = $slug;

		$multilang->storeMultilangIndex($multilangIndex);

		$multilangData 	= $multilang->getMultilangData($pageid, $multilangIndex);

		# send the updated data to the frontend
		$response->getBody()->write(json_encode([
			'multilangData' 			=> $multilangData
		]));

		return $response->withHeader('Content-Type', 'application/json');
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

	private function createNewLanguagePage($lang, $key, $baseSegments, $targetSegments)
	{
		# get the source path
		# $source 			= array_slice($baseSegments, 0, $key+1);
		$sourceUrl 			= '/' . implode('/', $baseSegments);
		$sourceInfo 		= $this->getSourceInfo($sourceUrl);
		if(!$sourceInfo)
		{
			return "We could not find the source page";
		}
		$sourcePath 		= $sourceInfo['path'];

		# get the parent url segments of target
		$parentPath = false;
		if(count($targetSegments) > 1)
		{
			$targetParent 		= array_slice($targetSegments, 0, $key);
			$targetParentUrl 	= '/' . implode('/', $targetParent);
			$targetParentInfo 	= $this->getSourceInfo($targetParentUrl, $lang);
			if(!$targetParentInfo)
			{
				return "We could not find the parent page of the target";
			}
			$parentPath 		= trim($targetParentInfo['path'], DIRECTORY_SEPARATOR);
		}

		$targetSlug 			= $targetSegments[$key];
		$targetPathWithoutLang 	= ($parentPath ? $parentPath . DIRECTORY_SEPARATOR : '')
		             			. $sourceInfo['index'] . '-' . $targetSlug;

		$targetPath 			= '_' . $lang . DIRECTORY_SEPARATOR . $targetPathWithoutLang;

/*
		echo '<pre>';
		echo '<br>sourcepath: ' . $sourcePath;
		echo '<br>parentpath: ' . $parentPath;
		echo '<br>targetpath: ' . $targetPath;
		die();

/*
		page in base-folder: 
		* sourcepath: /04-testbasepage.txt
		* parentpath:
		* targetpath: _de/04-test		

		folder in base-folder:
		* sourcepath: /00-getting-started
		* parentpath: 
		* targetpath: _de/00-folder
		
		file in folder:
		* sourcepath: /00-getting-started/00-create-your-first-page.md
		* parentpath: 00-loslegen
		* targetpath: _de/00-loslegen/00-detwer
*/

		$storage = new StorageWrapper($this->settings['storage']);

		# it is a file
		if($sourceInfo['extension'])
		{
			# copy yaml
			$storage->copyFile(
				'contentFolder', 
				'', 
				$sourceInfo['pathWoE'] . '.yaml', 
				$targetPath . '.yaml'
			);

			# copy content file always as txt (draft)
			$storage->copyFile(
				'contentFolder', 
				'', 
				$sourceInfo['pathWoE'] . '.' . $sourceInfo['extension'], 
				$targetPath . '.txt'
			);
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
		$navigation->setLanguage($lang);
		$naviFileName = $navigation->getNaviFileNameForPath($targetPathWithoutLang);
	    $navigation->clearNavigation([$naviFileName]);

		return true;
	}

	private function getSourceInfo($url, $lang = false)
	{
		# load the extended navigation to quickly find the urls in sub functions
        $urlinfo     		= $this->c->get('urlinfo');
        $langattr       	= $this->settings['langattr'];
		$navigation 		= new Navigation();
		if($lang)
		{
			$navigation->setLanguage($lang);
		}

		$extended 			= $navigation->getFullExtendedNavigation($urlinfo, $langattr);

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