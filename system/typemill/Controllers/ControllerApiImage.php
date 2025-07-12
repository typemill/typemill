<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\Media;
use Typemill\Models\StorageWrapper;
use Typemill\Models\Navigation;
use Typemill\Models\User;
use Typemill\Extensions\ParsedownExtension;
use Typemill\Static\Translations;

class ControllerApiImage extends Controller
{
	public function getPagemedia(Request $request, Response $response, $args)
	{
		$url 			= $request->getQueryParams()['url'] ?? false;
		$path 			= $request->getQueryParams()['path'] ?? false;
		$pagemedia 		= [];

		if(!$path)
		{
			$response->getBody()->write(json_encode([
				'message' 		=> Translations::translate('Path is missing.')
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}
		
		$storage 		= new StorageWrapper('\Typemill\Models\Storage');

		$markdown 	= $storage->getFile('contentFolder', '', $path . '.txt');
		if($markdown)
		{
			$markdownArray 	= json_decode($markdown);
			$parsedown 		= new ParsedownExtension();
			$markdown 		= $parsedown->arrayBlocksToMarkdown($markdownArray);
		}
		else
		{
			$markdown = $storage->getFile('contentFolder', '', $path . '.md');
		}

		$mdmedia 	= $this->findMediaInText($markdown);

		$meta 		= $storage->getFile('contentFolder', '', $path . '.yaml');

		$mtmedia  	= $this->findMediaInText($meta);

		$pagemedia 	= array_merge($mdmedia[2], $mtmedia[2]);

		$response->getBody()->write(json_encode([
			'pagemedia' 	=> $pagemedia
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}

	public function getUnusedMedia(Request $request, Response $response, $args)
	{
		$storage 		= new StorageWrapper('\Typemill\Models\Storage');

		# load all images
		$imagelist 		= $storage->getImageList();

		# load all files
		$filelist 		= $storage->getFileList();

		# get navigation
		$urlinfo 			= $this->c->get('urlinfo');
		$langattr 			= $this->settings['langattr'];
	    $navigation 		= new Navigation();
		$draftNavigation 	= $navigation->getFullDraftNavigation($urlinfo, $langattr);

		$fullNavigation = $navigation->getHomepageItem($urlinfo['baseurl']);
		$fullNavigation->folderContent = $draftNavigation;

		$usedmediaList = $this->getMediaFromPages([$fullNavigation], $storage, $media = []);

		# get media from users
		$userModel = new User();
		$userList = $userModel->getAllUsers();
		$usedmediaList = $this->getMediaFromUsers($storage, $usedmediaList, $userList);

		# get media from settings
		$settingsfile 	= $storage->getFile('settingsFolder', '', 'settings.yaml');
		$settingsmedia  = $this->findMediaInText($settingsfile);
		if(isset($settingsmedia[2]) && !empty($settingsmedia[2]))
		{
			$usedmediaList 	= array_merge($usedmediaList, $settingsmedia[2]);
		}


		if(empty($usedmediaList))
		{

		}

		$usedMedia = [];
		foreach($usedmediaList as $name)
		{
			$usedMedia[$name] = true;
		}

		$unusedMedia = [];
		foreach($imagelist as $key => $item)
		{
			if(!isset($usedMedia[$item['name']]))
			{
				$unusedMedia[] = $item;
			}
		}

		foreach($filelist as $key => $item)
		{
			if(!isset($usedMedia[$item['name']]))
			{
				$unusedMedia[] = $item;
			}
		}

		$response->getBody()->write(json_encode([
			'used' => $usedMedia,
			'unused' => $unusedMedia
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}

	protected function getMediaFromUsers($storage, $usedmediaList, $userList)
	{
		foreach($userList as $username)
		{
			$userfile 	= $storage->getFile('settingsFolder', 'users', $username . '.yaml');
			$usermedia  = $this->findMediaInText($userfile);
			if(isset($usermedia[2]) && !empty($usermedia[2]))
			{
				$usedmediaList 	= array_merge($usedmediaList, $usermedia[2]);
			}
		}

		return $usedmediaList;
	}

	protected function getMediaFromPages($navigation, $storage, $usedMedia)
	{
		foreach($navigation as $item)
		{
			$pagemedia = [];
			$path = $item->pathWithoutType;
			$draftmd = $storage->getFile('contentFolder', '', $path . '.txt');
			if($draftmd)
			{
				$markdownArray 	= json_decode($draftmd);
				$parsedown 		= new ParsedownExtension();
				$markdown 		= $parsedown->arrayBlocksToMarkdown($markdownArray);
				$draftmedia 	= $this->findMediaInText($markdown);
				if(isset($draftmedia[2]) && !empty($draftmedia[2]))
				{
					$pagemedia 		= array_merge($pagemedia, $draftmedia[2]);
				}
			}
			
			$livemd = $storage->getFile('contentFolder', '', $path . '.md');
			if($livemd)
			{
				$livemedia 		= $this->findMediaInText($livemd);
				if(isset($livemedia[2]) && !empty($livemedia[2]))
				{
					$pagemedia 		= array_merge($pagemedia, $livemedia[2]);
				}
			}
			
			$meta = $storage->getFile('contentFolder', '', $path . '.yaml');
			if($meta)
			{
				$metamedia  	= $this->findMediaInText($meta);
				if(isset($metamedia[2]) && !empty($metamedia[2]))
				{
					$pagemedia 		= array_merge($pagemedia, $metamedia[2]);
				}
			}

			if(!empty($pagemedia))
			{
				$usedMedia = array_merge($usedMedia, $pagemedia);
			}

			if($item->elementType == 'folder' && !empty($item->folderContent))
			{
				$usedMedia = $this->getMediaFromPages($item->folderContent, $storage, $usedMedia);
			}
		}

		return $usedMedia;
	}

	protected function findMediaInText($text)
	{
		preg_match_all('/media\/(live|files)\/(.+?\.[a-zA-Z]{2,4})/', $text, $matches);

		return $matches;
	}	

	public function getImages(Request $request, Response $response, $args)
	{
		$url 			= $request->getQueryParams()['url'] ?? false;
		$path 			= $request->getQueryParams()['path'] ?? false;
		
		$storage 		= new StorageWrapper('\Typemill\Models\Storage');

		$imagelist 		= $storage->getImageList();

		$response->getBody()->write(json_encode([
			'images' 		=> $imagelist
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}

	public function getImage(Request $request, Response $response, $args)
	{
		$name 			= $request->getQueryParams()['name'] ?? false;

		# VALIDATE NAME

		if(!$name)
		{
			$response->getBody()->write(json_encode([
				'message' 		=> Translations::translate('Imagename is missing.')
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$storage 		= new StorageWrapper('\Typemill\Models\Storage');

		$imagedetails 	= $storage->getImageDetails($name);
		
		if(!$imagedetails)
		{
			$response->getBody()->write(json_encode([
				'message' 		=> Translations::translate('No image found.')
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		$response->getBody()->write(json_encode([
			'image' 		=> $imagedetails,
		]));

		return $response->withHeader('Content-Type', 'application/json');		
	}
	
	public function saveImage(Request $request, Response $response, $args)
	{
		$params = $request->getParsedBody();

		if(!isset($params['image']) OR !isset($params['name']))
		{
			$response->getBody()->write(json_encode([
				'message' 		=> Translations::translate('Image or name is missing.')
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}
		
		$media = new Media();

		if($this->settingActive('allowsvg'))
		{
			$media->addAllowedExtension('svg');
		}
		
		# prepare the image
		$size 	= (int) (strlen(rtrim($params['image'], '=')) * 3 / 4);
		$maxsizeMB = (isset($this->settings['maximageuploads']) && is_numeric($this->settings['maximageuploads'])) ? $this->settings['maximageuploads'] : 20;
		$maxsizeBytes = $maxsizeMB * 1024 * 1024;
		if ($size > $maxsizeBytes)
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('Image is bigger than ' . $maxsizeMB . 'MB.')
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		if(!$media->prepareImage($params['image'], $params['name']))
		{
			$response->getBody()->write(json_encode([
				'message' 		=> $media->errors[0],
				'fullerrors'	=> $media->errors,
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
		}

		# check if image name already exisits in live folder and create an unique name (do not overwrite existing files)
		$storage 			= new StorageWrapper('\Typemill\Models\Storage');
		$uniqueImageName 	= $storage->createUniqueImageName($media->getFilename(), $media->getExtension());
		$media->setFilename($uniqueImageName);

		# check if images should be transformed to webp
		if(!isset($params['keepformat']) && $this->settingActive('convertwebp'))
		{
			$media->setExtension('webp');
			$media->convertOriginal();
		}
		# store the original image
		elseif(!$media->storeOriginalToTmp())
		{
			$response->getBody()->write(json_encode([
				'message' 		=> $media->errors[0],
				'fullerrors'	=> $media->errors,
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);			
		}

		# if image is not resizable (animated gif or svg)
		if(!$media->isResizable())
		{
			if($media->saveOriginalForAll())
			{
				$response->getBody()->write(json_encode([
					'message' => Translations::translate('Image saved successfully'),
					'path' => 'media/live/' . $media->getFullName(),
				]));

				return $response->withHeader('Content-Type', 'application/json');
			}

			$response->getBody()->write(json_encode([
				'message' 		=> $media->errors[0],
				'fullerrors'	=> $media->errors,
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
		}

		$desiredSizes = $this->getDesiredSizes();

		if(!$media->storeRenditionsToTmp($desiredSizes))
		{
			$response->getBody()->write(json_encode([
				'message' 		=> $media->errors[0],
				'fullerrors'	=> $media->errors,
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
		}

		if(isset($params['publish']) && $params['publish'] === true)
		{
			$result = $storage->publishImage($media->getFullName());

			if(!$result)
			{
				$response->getBody()->write(json_encode([
					'message' 		=> $storage->getError()
				]));

				return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
			}

			$response->getBody()->write(json_encode([
				'message' => Translations::translate('Image saved successfully'),
				'path' => $result,
			]));

			return $response->withHeader('Content-Type', 'application/json');
		}
		else
		{
			$response->getBody()->write(json_encode([
				'message' => Translations::translate('Image saved successfully'),
				'path' => 'media/tmp/' . $media->getFullName(),
			]));

			return $response->withHeader('Content-Type', 'application/json');
		}
	}

	public function publishImage(Request $request, Response $response, $args)
	{
		$params = $request->getParsedBody();
		$noresize = (isset($params['noresize']) && $params['noresize'] == true) ? true : false;

		if(!isset($params['imgfile']) OR !$params['imgfile'])
		{
			$response->getBody()->write(json_encode([
				'message' 		=> Translations::translate('Image or filename is missing.'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$storage 	= new StorageWrapper('\Typemill\Models\Storage');

		$result 	= $storage->publishImage($params['imgfile'], $noresize);

		if(!$result)
		{
			$response->getBody()->write(json_encode([
				'message' 		=> $storage->getError()
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
		}

		$response->getBody()->write(json_encode([
			'message' => Translations::translate('Image saved successfully'),
			'path' => $result,
		]));

		return $response->withHeader('Content-Type', 'application/json');		
	}

	public function saveVideoImage(Request $request, Response $response, $args)
	{
		$params = $request->getParsedBody();

		if(!isset($params['videourl']) OR !$params['videourl'])
		{
			$response->getBody()->write(json_encode([
				'message' 		=> Translations::translate('Markdown is missing.'),
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$videoUrl		= $params['videourl'];
		$class			= false;
		if(strpos($videoUrl, 'https://www.youtube.com/watch?v=') !== false)
		{
			$videoID 	= str_replace('https://www.youtube.com/watch?v=', '', $videoUrl);
			$videoID 	= strpos($videoID, '&') ? substr($videoID, 0, strpos($videoID, '&')) : $videoID;
			$class		= 'youtube';
		}
		elseif(strpos($videoUrl, 'https://youtu.be/') !== false)
		{
			$videoID 	= str_replace('https://youtu.be/', '', $videoUrl);
			$videoID	= strpos($videoID, '?') ? substr($videoID, 0, strpos($videoID, '?')) : $videoID;
			$class		= 'youtube';
		}

		if($class == 'youtube')
		{
			$videoURLmaxres = 'https://i1.ytimg.com/vi/' . $videoID . '/maxresdefault.jpg';
			$videoURL0 = 'https://i1.ytimg.com/vi/' . $videoID . '/0.jpg';
		}

		$ctx = stream_context_create(array(
			'https' => array(
				'timeout' => 1
				)
			)
		);

		$imageData = @file_get_contents($videoURLmaxres, 0, $ctx);
		if($imageData === false)
		{
			$imageData = @file_get_contents($videoURL0, 0, $ctx);
			if($imageData === false)
			{
				$response->getBody()->write(json_encode([
					'message' 		=> Translations::translate('could not get the video image'),
				]));

				return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
			}
		}
		
		$imageData64 = 'data:image/jpeg;base64,' . base64_encode($imageData);

		$media = new Media();

		# prepare the image
		if(!$media->prepareImage($imageData64, $class . '-' . $videoID . '.jpg'))
		{
			$response->getBody()->write(json_encode([
				'message' 		=> $media->errors[0],
				'fullerrors'	=> $media->errors,
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
		}

		# check if image name already exisits in live folder and create an unique name (do not overwrite existing files)
		$storage 			= new StorageWrapper('\Typemill\Models\Storage');
		$uniqueImageName 	= $storage->createUniqueImageName($media->getFilename(), $media->getExtension());
		$media->setFilename($uniqueImageName);

		# store the original image
		if(!$media->storeOriginalToTmp())
		{
			$response->getBody()->write(json_encode([
				'message' 		=> $media->errors[0],
				'fullerrors'	=> $media->errors,
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);			
		}

		# for all other image types, check if they should be transformed to webp
		if($this->settingActive('convertwebp'))
		{
			$media->setExtension('webp');
		}

		# set to youtube size
		$sizes = $this->getDesiredSizes();
		$sizes['live'] = ['width' => 560, 'height' => 315];

		if(!$media->storeRenditionsToTmp($sizes))
		{
			$response->getBody()->write(json_encode([
				'message' 		=> $media->errors[0],
				'fullerrors'	=> $media->errors,
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
		}

		# now publish directly
		$livePath 	= $storage->publishImage($media->getFullName());

		if($livePath)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> Translations::translate('Image saved successfully'),
				'path' 		=> $livePath,
			]));

			return $response->withHeader('Content-Type', 'application/json');
		}

		$response->getBody()->write(json_encode([
			'message' 		=> $storage->getError(),
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
	}

	public function deleteImage(Request $request, Response $response, $args)
	{
		$params = $request->getParsedBody();

		if(!isset($params['name']))
		{
			$response->getBody()->write(json_encode([
				'message' 		=> Translations::translate('Imagename is missing.')
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$storage = new StorageWrapper('\Typemill\Models\Storage');

		$deleted = $storage->deleteImage($params['name']);

		if($deleted)
		{
			$response->getBody()->write(json_encode([
				'message' 		=> Translations::translate('Image deleted successfully.')
			]));

			return $response->withHeader('Content-Type', 'application/json');
		}

		$response->getBody()->write(json_encode([
			'message' 		=> $storage->getError()
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
	}

	private function getDesiredSizes()
	{
		$desiredSizes = [
		    'live' => [
		        'width' => 820,
		    ],
		    'thumbs' => [
		        'width' => 250,
		        'height' => 150,
		    ],
		];
		if(isset($this->settings['liveimagewidth']) && is_int($this->settings['liveimagewidth']) && $this->settings['liveimagewidth'] > 10)
		{
			$desiredSizes['live']['width'] = $this->settings['liveimagewidth'];
		}
		if(isset($this->settings['liveimageheight']) && is_int($this->settings['liveimageheight']) && $this->settings['liveimageheight'] > 10)
		{
			$desiredSizes['live']['height'] = $this->settings['liveimageheight'];
		}

		# we could check for theme settings here

		return $desiredSizes;
	}
}
