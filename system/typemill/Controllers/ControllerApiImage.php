<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\ProcessImage;
use Typemill\Models\StorageWrapper;


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





		
		$imageData64	= 'data:image/jpeg;base64,' . base64_encode($imageData);
		$desiredSizes	= ['live' => ['width' => 560, 'height' => 315]];
		$imageProcessor	= new ProcessImage($this->settings['images']);
		if(!$imageProcessor->checkFolders())
		{
			return $response->withJson(['errors' => ['message' => 'Please check if your media-folder exists and all folders inside are writable.']], 500);
		}

		$tmpImage		= $imageProcessor->createImage($imageData64, $videoID, $desiredSizes);
		
		if(!$tmpImage)
		{
			return $response->withJson(array('errors' => 'could not create temporary image'));			
		}
		
		$imageUrl 		= $imageProcessor->publishImage();
		if($imageUrl)
		{
			$this->params['markdown'] = '![' . $class . '-video](' . $imageUrl . ' "click to load video"){#' . $videoID. ' .' . $class . '}';

			$request 	= $request->withParsedBody($this->params);
			$block = new ControllerAuthorBlockApi($this->c);
			if($this->params['new'])
			{
				return $block->addBlock($request, $response, $args);
			}
			return $block->updateBlock($request, $response, $args);
		}
		
		return $response->withJson(array('errors' => 'could not store the preview image'));	
	}









	public function getMediaLibImages(Request $request, Response $response, $args)
	{
		# get params from call 
		$this->params 	= $request->getParsedBody();
		$this->uri 		= $request->getUri()->withUserInfo('');

		$imageProcessor	= new ProcessImage($this->settings['images']);
		if(!$imageProcessor->checkFolders('images'))
		{
			return $response->withJson(['errors' => 'Please check if your media-folder exists and all folders inside are writable.'], 500);
		}
		
		$imagelist 		= $imageProcessor->scanMediaFlat();

		$response->getBody()->write(json_encode([
			'images' => $imagelist
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}

	public function getImage(Request $request, Response $response, $args)
	{
		# get params from call 
		$this->params 	= $request->getParsedBody();
		$this->uri 		= $request->getUri()->withUserInfo('');

		$this->setStructureDraft();

		$imageProcessor	= new ProcessImage($this->settings['images']);
		if(!$imageProcessor->checkFolders('images'))
		{
			return $response->withJson(['errors' => 'Please check if your media-folder exists and all folders inside are writable.'], 500);
		}

		$imageDetails 	= $imageProcessor->getImageDetails($this->params['name'], $this->structureDraft);
		
		if($imageDetails)
		{
			return $response->withJson(['image' => $imageDetails]);
		}
		
		return $response->withJson(['errors' => 'Image not found or image name not valid.'], 404);
	}
	
	public function deleteImage(Request $request, Response $response, $args)
	{
		# get params from call 
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');

		# minimum permission is that user is allowed to delete content
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'content', 'delete'))
		{
			return $response->withJson(array('data' => false, 'errors' => 'You are not allowed to delete images.'), 403);
		}

		if(!isset($this->params['name']))
		{
			return $response->withJson(['errors' => 'image name is missing'],500);
		}

		$imageProcessor	= new ProcessImage($this->settings['images']);
		if(!$imageProcessor->checkFolders('images'))
		{
			return $response->withJson(['errors' => 'Please check if your media-folder exists and all folders inside are writable.'], 500);
		}

		if($imageProcessor->deleteImage($this->params['name']))
		{
			return $response->withJson(['errors' => false]);
		}

		return $response->withJson(['errors' => 'Oops, looks like we could not delete all sizes of that image.'], 500);
	}


}
