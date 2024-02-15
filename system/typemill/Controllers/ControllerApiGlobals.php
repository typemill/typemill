<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\Navigation;
use Typemill\Models\Sitemap;
use Typemill\Models\StorageWrapper;

class ControllerApiGlobals extends Controller
{
	public function getSystemNavi(Request $request, Response $response)
	{
		$navigation 		= new Navigation();
		$systemNavigation	= $navigation->getSystemNavigation(
									$userrole 		= $request->getAttribute('c_userrole'),
									$acl 			= $this->c->get('acl'),
									$urlinfo 		= $this->c->get('urlinfo'),
									$dispatcher 	= $this->c->get('dispatcher'),
									$parser 		= $this->routeParser
								);

		# won't work because api has no session, instead you have to pass user
		$response->getBody()->write(json_encode([
			'systemnavi' => $systemNavigation
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	public function getMainNavi(Request $request, Response $response)
	{
		$navigation 		= new Navigation();
		$mainNavigation		= $navigation->getMainNavigation(
									$userrole 	= $request->getAttribute('c_userrole'),
									$acl 		= $this->c->get('acl'),
									$urlinfo 	= $this->c->get('urlinfo'),
									$editor 	= $this->settings['editor']
								);

		$response->getBody()->write(json_encode([
			'mainnavi' => $mainNavigation
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	public function clearNavigation(Request $request, Response $response)
	{
		$navigation = new Navigation();

		$result = $navigation->clearNavigation();

		$response->getBody()->write(json_encode([
			'result' => $result
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);;
	}

	public function showSecurityLog(Request $request, Response $response)
	{
		$storage 	= new StorageWrapper('\Typemill\Models\Storage');
		$logfile 	= $storage->getFile('dataFolder', 'security', 'securitylog.txt');

		if($logfile)
		{
			$logfile = trim($logfile);
			if($logfile == '')
			{
				$lines = ['Logfile is empty'];
			}
			else
			{
				$lines = preg_split('/\r\n|\n|\r/', $logfile);				
			}

			$response->getBody()->write(json_encode([
				'lines' => $lines
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(200);;

		}

		$response->getBody()->write(json_encode([
			'error' => 'No logfile found'
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
	}

	public function deleteSecurityLog(Request $request, Response $response)
	{
		$storage 	= new StorageWrapper('\Typemill\Models\Storage');
		$result 	= $storage->deleteFile('dataFolder', 'security', 'securitylog.txt');

		$response->getBody()->write(json_encode([
			'result' => $result
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);;
	}

	public function deleteCache(Request $request, Response $response)
	{
		$storage 	= new StorageWrapper('\Typemill\Models\Storage');

		$cacheFolder = $storage->getFolderPath('cacheFolder');

		$iterator 	= new \RecursiveDirectoryIterator($cacheFolder, \RecursiveDirectoryIterator::SKIP_DOTS);
		$files 		= new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::CHILD_FIRST);
		
		$error = false;

		foreach($files as $file)
		{
		    if ($file->isDir())
		    {
		    	if(!rmdir($file->getRealPath()))
		    	{
		    		$error = 'Could not delete some folders.';
		    	}
		    }
		    elseif($file->getExtension() !== 'css')
		    {
				if(!unlink($file->getRealPath()) )
				{
					$error = 'Could not delete some files.';
				}
		    }
		}

		$sitemap 		= new Sitemap();
		$navigation 	= new Navigation();
		$urlinfo 		= $this->c->get('urlinfo');
		$liveNavigation = $navigation->getLiveNavigation($urlinfo, $this->settings['langattr']);
		$sitemap->updateSitemap($liveNavigation, $urlinfo);

		if($error)
		{
			$response->getBody()->write(json_encode([
				'error' => $error
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		$response->getBody()->write(json_encode([
			'result' => true
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}


	public function getTranslations(Request $request, Response $response)
	{		
		$response->getBody()->write(json_encode([
			'translations' => $this->c->get('translations'),
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}
}