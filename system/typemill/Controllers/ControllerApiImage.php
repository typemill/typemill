<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\ProcessImage;
use Typemill\Models\StorageWrapper;
use Typemill\Extensions\ParsedownExtension;


# use Typemill\Models\ProcessFile;
# use Typemill\Models\Yaml;
# use Typemill\Controllers\ControllerAuthorBlockApi;

class ControllerApiImage extends Controller
{

	# MISSING
	# 
	# solution for logo
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
				'message' 		=> 'Path is missing.',
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
		$pagemedia 		= [];
		
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
				'message' 		=> 'Imagename is missing.',
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$storage 		= new StorageWrapper('\Typemill\Models\Storage');

		$imagedetails 	= $storage->getImageDetails($name);
		
		if(!$imagedetails)
		{
			$response->getBody()->write(json_encode([
				'message' 		=> 'No image found.',
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
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
				'message' 		=> 'Image or name is missing.',
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}
		
		$img = new ProcessImage();

		if($this->settingActive('allowsvg'))
		{
			$img->addAllowedExtension('svg');
		}
		
		# prepare the image
		if(!$img->prepareImage($params['image'], $params['name']))
		{
			$response->getBody()->write(json_encode([
				'message' 		=> $img->errors[0],
				'fullerrors'	=> $img->errors,
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		# check if image name already exisits in live folder and create an unique name (do not overwrite existing files)
		$storage 			= new StorageWrapper('\Typemill\Models\Storage');
		$uniqueImageName 	= $storage->createUniqueImageName($img->getFilename(), $img->getExtension());
		$img->setFilename($uniqueImageName);

		# store the original image
		if(!$img->storeOriginalToTmp())
		{
			$response->getBody()->write(json_encode([
				'message' 		=> $img->errors[0],
				'fullerrors'	=> $img->errors,
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);			
		}

		# if image is not resizable (animated gif or svg)
		if(!$img->isResizable())
		{
			if($img->saveOriginalForAll())
			{
				$response->getBody()->write(json_encode([
					'message' => 'Image saved successfully',
					'name' => 'media/live/' . $img->getFullName(),
				]));

				return $response->withHeader('Content-Type', 'application/json');
			}

			$response->getBody()->write(json_encode([
				'message' 		=> $img->errors[0],
				'fullerrors'	=> $img->errors,
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		# for all other image types, check if they should be transformed to webp
		if($this->settingActive('convertwebp'))
		{
			$img->setExtension('webp');
		}

		if(!$img->storeRenditionsToTmp($this->settings['images']))
		{
			$response->getBody()->write(json_encode([
				'message' 		=> $img->errors[0],
				'fullerrors'	=> $img->errors,
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
		}

/*
		if(isset($params['publish']) && $params['publish'])
		{
			if(!$img->publishImage($img->getFullName()))
			{
				$response->getBody()->write(json_encode([
					'message' 		=> $img->errors[0],
					'fullerrors'	=> $img->errors,
				]));

				return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
			}
		}
*/
		$response->getBody()->write(json_encode([
			'message' => 'Image saved successfully',
			'name' => 'media/tmp/' . $img->getFullName(),
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
				'message' 		=> 'Image or filename is missing.',
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

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$response->getBody()->write(json_encode([
			'message' => 'Image saved successfully',
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
				'message' 		=> 'Markdown is missing.',
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
				return $response->withJson(array('errors' => 'could not get the video image'));
			}
		}
		
		$imageData64 = 'data:image/jpeg;base64,' . base64_encode($imageData);

		$img = new ProcessImage();

		# prepare the image
		if(!$img->prepareImage($imageData64, $class . '-' . $videoID . '.jpg'))
		{
			$response->getBody()->write(json_encode([
				'message' 		=> $img->errors[0],
				'fullerrors'	=> $img->errors,
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		# check if image name already exisits in live folder and create an unique name (do not overwrite existing files)
		$storage 			= new StorageWrapper('\Typemill\Models\Storage');
		$uniqueImageName 	= $storage->createUniqueImageName($img->getFilename(), $img->getExtension());
		$img->setFilename($uniqueImageName);

		# store the original image
		if(!$img->storeOriginalToTmp())
		{
			$response->getBody()->write(json_encode([
				'message' 		=> $img->errors[0],
				'fullerrors'	=> $img->errors,
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);			
		}

		# for all other image types, check if they should be transformed to webp
		if($this->settingActive('convertwebp'))
		{
			$img->setExtension('webp');
		}

		# set to youtube size
		$sizes = $this->settings['images'];
		$sizes['live'] = ['width' => 560, 'height' => 315];

		if(!$img->storeRenditionsToTmp($sizes))
		{
			$response->getBody()->write(json_encode([
				'message' 		=> $img->errors[0],
				'fullerrors'	=> $img->errors,
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
		}

		# now publish directly
		$livePath 	= $storage->publishImage($img->getFullName());

		if($livePath)
		{
			$response->getBody()->write(json_encode([
				'message' 	=> 'Image saved successfully',
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
				'message' 		=> 'Imagename is missing.'
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
		}

		$storage = new StorageWrapper('\Typemill\Models\Storage');

		$deleted = $storage->deleteImage($params['name']);

		if($deleted)
		{
			$response->getBody()->write(json_encode([
				'message' 		=> 'Image deleted successfully.'
			]));

			return $response->withHeader('Content-Type', 'application/json');
		}

		$response->getBody()->write(json_encode([
			'message' 		=> $storage->getError()
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
	}
}
