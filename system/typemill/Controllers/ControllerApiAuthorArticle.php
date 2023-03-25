<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;

use Typemill\Models\Navigation;
use Typemill\Models\Validation;
use Typemill\Models\StorageWrapper;

class ControllerApiAuthorArticle extends Controller
{
	public function sortArticle(Request $request, Response $response, $args)
	{
		$params 			= $request->getParsedBody();

		# input validation
		$validate 			= new Validation();
		$result				= $validate->navigationSort($params);
		if(!$result)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> 'Data not valid. Please refresh the page and try again.',
				'errors' 	=> $result
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(422);			
		}

		# set variables
		$itemKeyPath 		= explode('.', $params['item_id']);
		$parentKeyFrom		= explode('.', $params['parent_id_from']);
		$parentKeyTo		= explode('.', $params['parent_id_to']);
		$urlinfo 			= $this->c->get('urlinfo');
		$langattr 			= $this->settings['langattr'];

		# get navigation
	    $navigation 		= new Navigation();
	    $draftNavigation 	= $navigation->getDraftNavigation($urlinfo, $langattr);

		# validate user rights
		$acl 				= $this->c->get('acl');

		# if user has no right to update content from others (eg admin or editor)
		if(!$acl->isAllowed($request->getAttribute('c_userrole'), 'content', 'update'))
		{
			# check ownership. This code should nearly never run, because there is no button/interface to trigger it.
			if(!$this->checkContentOwnership())
			{
				$response->getBody()->write(json_encode([
					'message' 		=> 'You are not allowed to move that content.',
					'navigation' 	=> $draftNavigation
				]));

				return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
			}
		}

		$item 				= $navigation->getItemWithKeyPath($draftNavigation, $itemKeyPath);

	    $extendedNavigation	= $navigation->getExtendedNavigation($urlinfo, $langattr);

	    $pageinfo 			= $extendedNavigation[$params['url']] ?? false;

	    if(!$pageinfo)
	    {
			$response->getBody()->write(json_encode([
				'message' => 'page not found',
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
	    }

		# if an item is moved to the first level
		if($parentKeyTo == '')
		{
			# create empty and default values so that the logic below still works
			$newFolder 			=  new \stdClass();
			$newFolder->path	= '';
			$folderContent		= $draftNavigation;
		}
		else
		{
			# get the target folder from navigation
			$newFolder 			= $navigation->getItemWithKeyPath($draftNavigation, $parentKeyTo);
			
			# get the content of the target folder
			$folderContent		= $newFolder->folderContent;
		}
		
		# if the item has been moved within the same folder
		if($parentKeyFrom == $parentKeyTo)
		{
			# no need to ping search engines
			$ping = false;

			# get key of item
			$itemKey = end($itemKeyPath);
			reset($itemKeyPath);
			
			# delete item from folderContent
			unset($folderContent[$itemKey]);
		}
		else
		{
			# let us ping search engines
			$ping = true;

			# rename links in extended file
			#$navigation->renameExtended($item, $newFolder);

			# an active file has been moved to another folder, so send new url with response
			if($params['active'] == 'active')
			{
				$url = $urlinfo['baseurl'] . '/tm/content/' . $this->settings['editor'] . $newFolder->urlRelWoF . '/' . $item->slug;
			}
		}

		# add item to newFolder
		array_splice($folderContent, $params['index_new'], 0, array($item));
		
		# move and rename files in the new folder
		$index 			= 0;
		$writeError 	= false;
		$storage 		= new StorageWrapper('\Typemill\Models\Storage');
		foreach($folderContent as $folderItem)
		{
			if(!$storage->moveFile($folderItem, $newFolder->path, $index))
			{
				$writeError = true;
			}
			$index++;
		}
		if($writeError)
		{ 
			$response->getBody()->write(json_encode([
				'message' 		=> 'Something went wrong. Please refresh the page and check, if all folders and files are writable.',
				'navigation' 	=> $draftNavigation,
				'url'			=> false
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		# if everything worked, we have to recreate the navigation
	    $navigation->clearNavigation();
	    
/*		
		# get item for url and set it active again
		if(isset($this->params['url']))
		{
			$activeItem = Folder::getItemForUrl($this->structureDraft, $this->params['url'], $this->uri->getBaseUrl());
		}

		# update the structure for website
		$this->setFreshStructureLive();

		# update the navigation
		$this->setFreshNavigation();

		# update the sitemap
		$this->updateSitemap($ping);
		
		# dispatch event
		$this->c->dispatcher->dispatch('onPageSorted', new OnPageSorted($this->params));
*/

		$response->getBody()->write(json_encode([
			'navigation'	=> $navigation->getDraftNavigation($urlinfo, $langattr),
			'message'		=> '',
			'url' 			=> false
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}

	public function createArticle(Request $request, Response $response, $args)
	{
		# validate user rights
		$acl 				= $this->c->get('acl');

		# if user has no right to update content from others (eg admin or editor)
		if(!$acl->isAllowed($request->getAttribute('c_userrole'), 'mycontent', 'create'))
		{
			$response->getBody()->write(json_encode([
				'message' 		=> 'You are not allowed to create content.',
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
		}

		$params 			= $request->getParsedBody();

		# input validation
		$validate 			= new Validation();
		$result 			= $validate->validateNaviItem($params);
		if(!$result)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> 'Input not valid.',
				'errors' 	=> $result
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(422);			
		}

		# set variables
		$urlinfo 			= $this->c->get('urlinfo');
		$langattr 			= $this->settings['langattr'];

		# get navigation
	    $navigation 		= new Navigation();
	    $draftNavigation 	= $navigation->getDraftNavigation($urlinfo, $langattr);

	    if($params['folder_id'] == 'root')
	    {
			$folderContent		= $draftNavigation;
	    }
	    else
	    {
			# get the ids (key path) for item, old folder and new folder
			$folderKeyPath 		= explode('.', $params['folder_id']);
			
			# get the item from structure
			$folder				= $navigation->getItemWithKeyPath($draftNavigation, $folderKeyPath);

			if(!$folder)
			{ 
				$response->getBody()->write(json_encode([
					'message' 	=> 'We could not find this page. Please refresh and try again.'
				]));

				return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
			}

			$folderContent		= $folder->folderContent;
	    }





		
		$name 		= $params['item_name'];
		$slug 		= Folder::createSlug($this->params['item_name'], $this->settings);

		# initialize index
		$index = 0;
		
		# iterate through the whole content of the new folder
		$writeError = false;
		$folderPath = isset($folder) ? $folder->path : '';

		foreach($folderContent as $folderItem)
		{
			# check, if the same name as new item, then return an error
			if($folderItem->slug == $slug)
			{
				return $response->withJson(array('navigation' => $draftNavigation, 'errors' => 'There is already a page with this name. Please choose another name.', 'url' => $url), 404);
			}
			
			if(!$writeYaml->moveElement($folderItem, $folderPath, $index))
			{
				$writeError = true;
			}
			$index++;
		}

		if($writeError)
		{ 
			return $response->withJson(array('data' => $this->structureDraft, 'errors' => 'Something went wrong. Please refresh the page and check, if all folders and files are writable.', 'url' => $url), 404); 
		}






		# add prefix number to the name
		$namePath 	= $index > 9 ? $index . '-' . $slug : '0' . $index . '-' . $slug;
		$folderPath	= 'content' . $folder->path;
		
		# create default content
		$content = json_encode(['# ' . $name, 'Content']);
		
		if($this->params['type'] == 'file')
		{
			if(!$writeYaml->writeFile($folderPath, $namePath . '.txt', $content))
			{
				return $response->withJson(array('data' => $this->structureDraft, 'errors' => 'We could not create the file. Please refresh the page and check, if all folders and files are writable.', 'url' => $url), 404);
			}
		}
		elseif($this->params['type'] == 'folder')
		{
			if(!$writeYaml->checkPath($folderPath . DIRECTORY_SEPARATOR . $namePath))
			{
				return $response->withJson(array('data' => $this->structureDraft, 'errors' => 'We could not create the folder. Please refresh the page and check, if all folders and files are writable.', 'url' => $url), 404);
			}
			$this->writeCache->writeFile($folderPath . DIRECTORY_SEPARATOR . $namePath, 'index.txt', $content);

			# always redirect to a folder
			$url = $this->uri->getBaseUrl() . '/tm/content/' . $this->settings['editor'] . $folder->urlRelWoF . '/' . $slug;

		}

		# get extended structure
		$extended = $writeYaml->getYaml('cache', 'structure-extended.yaml');

		# create the url for the item
		$urlWoF = $folder->urlRelWoF . '/' . $slug;
#		$urlWoF = '/' . $slug;

		# add the navigation name to the item htmlspecialchars needed for french language
		$extended[$urlWoF] = ['hide' => false, 'navtitle' => $name];

		# store the extended structure
		$writeYaml->updateYaml('cache', 'structure-extended.yaml', $extended);

		# update the structure for editor
		$this->setFreshStructureDraft();

		# get item for url and set it active again
		if(isset($this->params['url']))
		{
			$activeItem = Folder::getItemForUrl($this->structureDraft, $this->params['url'], $this->uri->getBaseUrl());
		}

		# activate this if you want to redirect after creating the page...
		# $url = $this->uri->getBaseUrl() . '/tm/content/' . $this->settings['editor'] . $folder->urlRelWoF . '/' . $slug;
		
		return $response->withJson(array('data' => $this->structureDraft, 'errors' => false, 'url' => $url));
	}
}