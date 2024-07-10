<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\Media;
use Typemill\Models\StorageWrapper;
use Typemill\Extensions\ParsedownExtension;
use Typemill\Static\Translations;

class ControllerApiImage extends Controller
{

	# MISSING
	# 
	# return error messages and display in image component
	# check if resized is bigger than original, then use original

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

		# store the original image
		if(!$media->storeOriginalToTmp())
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
					'name' => 'media/live/' . $media->getFullName(),
				]));

				return $response->withHeader('Content-Type', 'application/json');
			}

			$response->getBody()->write(json_encode([
				'message' 		=> $media->errors[0],
				'fullerrors'	=> $media->errors,
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
		}

		# for all other image types, check if they should be transformed to webp
		if(!isset($params['keepformat']) && $this->settingActive('convertwebp'))
		{
			echo '<pre>';
			var_dump($params);
			die('set wp');
			$media->setExtension('webp');
		}

		if(!$media->storeRenditionsToTmp($this->settings['images']))
		{
			$response->getBody()->write(json_encode([
				'message' 		=> $media->errors[0],
				'fullerrors'	=> $media->errors,
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
		}


		$response->getBody()->write(json_encode([
			'message' => Translations::translate('Image saved successfully'),
			'name' => 'media/tmp/' . $media->getFullName(),
		]));

		return $response->withHeader('Content-Type', 'application/json');
	
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
		$sizes = $this->settings['images'];
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
}
