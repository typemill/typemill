<?php

namespace Typemill\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Typemill\Models\ProcessImage;
use Typemill\Models\ProcessFile;
use Typemill\Controllers\BlockApiController;
use \URLify;

class MediaApiController extends ContentController
{
	public function getMediaLibImages(Request $request, Response $response, $args)
	{
		# get params from call 
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();

		$imageProcessor	= new ProcessImage($this->settings['images']);
		if(!$imageProcessor->checkFolders('images'))
		{
			return $response->withJson(['errors' => 'Please check if your media-folder exists and all folders inside are writable.'], 500);
		}
		
		$imagelist 		= $imageProcessor->scanMediaFlat();

		return $response->withJson(['images' => $imagelist]);
	}

	public function getMediaLibFiles(Request $request, Response $response, $args)
	{
		# get params from call
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();

		$fileProcessor	= new ProcessFile();
		if(!$fileProcessor->checkFolders())
		{
			return $response->withJson(['errors' => 'Please check if your media-folder exists and all folders inside are writable.'], 500);
		}
		
		$filelist 		= $fileProcessor->scanFilesFlat();

		return $response->withJson(['files' => $filelist]);
	}

	public function getImage(Request $request, Response $response, $args)
	{
		# get params from call 
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();

		$this->setStructure($draft = true, $cache = false);

		$imageProcessor	= new ProcessImage($this->settings['images']);
		if(!$imageProcessor->checkFolders('images'))
		{
			return $response->withJson(['errors' => 'Please check if your media-folder exists and all folders inside are writable.'], 500);
		}

		$imageDetails 	= $imageProcessor->getImageDetails($this->params['name'], $this->structure);
		
		if($imageDetails)
		{
			return $response->withJson(['image' => $imageDetails]);
		}
		
		return $response->withJson(['errors' => 'Image not found or image name not valid.'], 404);
	}

	public function getFile(Request $request, Response $response, $args)
	{
		# get params from call 
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();

		$this->setStructure($draft = true, $cache = false);

		$fileProcessor	= new ProcessFile();
		if(!$fileProcessor->checkFolders())
		{
			return $response->withJson(['errors' => 'Please check if your media-folder exists and all folders inside are writable.'], 500);
		}

		$fileDetails 	= $fileProcessor->getFileDetails($this->params['name'], $this->structure);

		if($fileDetails)
		{
			return $response->withJson(['file' => $fileDetails]);
		}

		return $response->withJson(['errors' => 'file not found or file name invalid'],404);
	}

	public function createImage(Request $request, Response $response, $args)
	{
		# get params from call
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();
		
		$imageProcessor	= new ProcessImage($this->settings['images']);
		
		if(!$imageProcessor->checkFolders('images'))
		{
			return $response->withJson(['errors' => 'Please check if your media-folder exists and all folders inside are writable.'], 500);
		}	

		if($imageProcessor->createImage($this->params['image'], $this->params['name'], $this->settings['images']))
		{
			# publish image directly, used for example by image field for meta-tabs
			if($this->params['publish'])
			{
				$imageProcessor->publishImage();
			}
			return $response->withJson(['name' => 'media/live/' . $imageProcessor->getFullName(),'errors' => false]);	
		}

		return $response->withJson(['errors' => 'could not store image to temporary folder']);	
	}

	public function uploadFile(Request $request, Response $response, $args)
	{
		# get params from call
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();

		# make sure only allowed filetypes are uploaded
		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		$mtype = finfo_file( $finfo, $this->params['file'] );
		finfo_close( $finfo );
		$allowedMimes = $this->getAllowedMtypes();
		if(!in_array($mtype, $allowedMimes))
		{
			return $response->withJson(array('errors' => 'File-type is not allowed'));
		}

		$fileProcessor	= new ProcessFile();

		if(!$fileProcessor->checkFolders())
		{
			return $response->withJson(['errors' => 'Please check if your media-folder exists and all folders inside are writable.'], 500);
		}
	
		$fileinfo = $fileProcessor->storeFile($this->params['file'], $this->params['name']);
		if($fileinfo)
		{
			return $response->withJson(['errors' => false, 'info' => $fileinfo]);
		}

		return $response->withJson(['errors' => 'could not store file to temporary folder'],500);
	}
	
	public function publishImage(Request $request, Response $response, $args)
	{
		$params 		= $request->getParsedBody();

		$imageProcessor	= new ProcessImage($this->settings['images']);
		if(!$imageProcessor->checkFolders())
		{
			return $response->withJson(['errors' => 'Please check if your media-folder exists and all folders inside are writable.'], 500);
		}
		
		if($imageProcessor->publishImage())
		{
			$request 	= $request->withParsedBody($params);
		
			$block = new BlockApiController($this->c);
			if($params['new'])
			{
				return $block->addBlock($request, $response, $args);
			}
			return $block->updateBlock($request, $response, $args);
		}

		return $response->withJson(['errors' => 'could not store image to media folder'],500);	
	}

	public function publishFile(Request $request, Response $response, $args)
	{
		$params 		= $request->getParsedBody();

		$fileProcessor	= new ProcessFile();
		if(!$fileProcessor->checkFolders())
		{
			return $response->withJson(['errors' => 'Please check if your media-folder exists and all folders inside are writable.'], 500);
		}
		
		if($fileProcessor->publishFile())
		{
			$request 	= $request->withParsedBody($params);
		
			$block = new BlockApiController($this->c);
			if($params['new'])
			{
				return $block->addBlock($request, $response, $args);
			}
			return $block->updateBlock($request, $response, $args);
		}

		return $response->withJson(['errors' => 'could not store file to media folder'],500);	
	}

	public function deleteImage(Request $request, Response $response, $args)
	{
		# get params from call 
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();

		if(!isset($this->params['name']))
		{
			return $response->withJson(['errors' => 'image name is missing'],500);
		}

		$imageProcessor	= new ProcessImage($this->settings['images']);
		if(!$imageProcessor->checkFolders())
		{
			return $response->withJson(['errors' => 'Please check if your media-folder exists and all folders inside are writable.'], 500);
		}

		if($imageProcessor->deleteImage($this->params['name']))
		{
			return $response->withJson(['errors' => false]);
		}

		return $response->withJson(['errors' => 'Oops, looks like we could not delete all sizes of that image.'], 500);
	}

	public function deleteFile(Request $request, Response $response, $args)
	{
		# get params from call 
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();

		if(!isset($this->params['name']))
		{
			return $response->withJson(['errors' => 'file name is missing'],500);	
		}

		$fileProcessor	= new ProcessFile();

		if($fileProcessor->deleteFile($this->params['name']))
		{
			return $response->withJson(['errors' => false]);
		}

		return $response->withJson(['errors' => 'could not delete the file'],500);
	}

	public function saveVideoImage(Request $request, Response $response, $args)
	{
		/* get params from call */
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri();
		$class			= false;

		$imageUrl		= $this->params['markdown'];
		
		if(strpos($imageUrl, 'https://www.youtube.com/watch?v=') !== false)
		{
			$videoID 	= str_replace('https://www.youtube.com/watch?v=', '', $imageUrl);
			$videoID 	= strpos($videoID, '&') ? substr($videoID, 0, strpos($videoID, '&')) : $videoID;
			$class		= 'youtube';
		}
		if(strpos($imageUrl, 'https://youtu.be/') !== false)
		{
			$videoID 	= str_replace('https://youtu.be/', '', $imageUrl);
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
		
		$imageData		= @file_get_contents($videoURLmaxres, 0, $ctx);
		if($imageData === false)
		{
			$imageData	= @file_get_contents($videoURL0, 0, $ctx);
			if($imageData === false)
			{
				return $response->withJson(array('errors' => 'could not get the video image'));
			}
		}
		
		$imageData64	= 'data:image/jpeg;base64,' . base64_encode($imageData);
		$desiredSizes	= ['live' => ['width' => 560, 'height' => 315]];
		$imageProcessor	= new ProcessImage($this->settings['images']);
		if(!$imageProcessor->checkFolders())
		{
			return $response->withJson(['errors' => ['message' => 'Please check if your media-folder exists and all folders inside are writable.']], 500);
		}

		$tmpImage		= $imageProcessor->createImage($imageData64, $desiredSizes);
		
		if(!$tmpImage)
		{
			return $response->withJson(array('errors' => 'could not create temporary image'));			
		}
		
		$imageUrl 		= $imageProcessor->publishImage($desiredSizes, $videoID);
		if($imageUrl)
		{
			$this->params['markdown'] = '![' . $class . '-video](' . $imageUrl . ' "click to load video"){#' . $videoID. ' .' . $class . '}';

			$request 	= $request->withParsedBody($this->params);
			
			$block = new BlockApiController($this->c);
			if($params['new'])
			{
				return $block->addBlock($request, $response, $args);
			}
			return $block->updateBlock($request, $response, $args);
		}
		
		return $response->withJson(array('errors' => 'could not store the preview image'));	
	}

	# https://www.sitepoint.com/mime-types-complete-list/
	# https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Common_types
	private function getAllowedMtypes()
	{
		return array(
		   	'application/zip',
		   	'application/gzip',
		   	'application/x-gzip',
		   	'application/x-compressed',
		   	'application/x-zip-compressed',
		   	'application/vnd.rar',
		   	'application/x-7z-compressed',
			'application/x-visio',
			'application/vnd.visio',
			'application/excel',
			'application/x-excel',
			'application/x-msexcel',
			'application/vnd.ms-excel',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'application/powerpoint',
			'application/mspowerpoint',
			'application/x-mspowerpoint',
			'application/vnd.ms-powerpoint',
			'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'application/msword',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'application/x-project',
			'application/vnd.ms-project',			
			'application/vnd.apple.keynote',
			'application/vnd.apple.mpegurl',
			'application/vnd.apple.numbers',
			'application/vnd.apple.pages',
			'application/vnd.amazon.mobi8-ebook',
			'application/epub+zip',
			'application/pdf',
			'application/x-latex',
		   	'image/png',
		   	'image/jpeg',
		   	'image/gif',
		   	'image/tiff',
		   	'image/x-tiff',
		   	'image/svg+xml',
		   	'image/x-icon',
		   	'text/plain',
		   	'application/plain',
		   	'text/richtext',
		   	'text/vnd.rn-realtext',
		   	'application/rtf',
		   	'application/x-rtf',
		   	'font/*',
		   	'audio/mpeg',
		   	'audio/mp4',
		   	'audio/ogg',
		   	'audio/3gpp',
		   	'audio/3gpp2',
		   	'video/mpeg',
		   	'video/mp4',
		   	'video/ogg',
		   	'video/3gpp',
		   	'video/3gpp2',
		);
	}	
}