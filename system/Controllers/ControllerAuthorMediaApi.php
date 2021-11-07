<?php

namespace Typemill\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Typemill\Models\ProcessImage;
use Typemill\Models\ProcessFile;
use Typemill\Models\WriteYaml;
use Typemill\Controllers\ControllerAuthorBlockApi;

class ControllerAuthorMediaApi extends ControllerAuthor
{
	public function getMediaLibImages(Request $request, Response $response, $args)
	{
		# get params from call 
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');

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
		$this->uri 		= $request->getUri()->withUserInfo('');

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

	public function getFile(Request $request, Response $response, $args)
	{
		# get params from call 
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');

		$this->setStructureDraft();

		$fileProcessor	= new ProcessFile();
		if(!$fileProcessor->checkFolders())
		{
			return $response->withJson(['errors' => 'Please check if your media-folder exists and all folders inside are writable.'], 500);
		}

		$fileDetails 	= $fileProcessor->getFileDetails($this->params['name'], $this->structureDraft);

		if($fileDetails)
		{
			return $response->withJson(['file' => $fileDetails]);
		}

		return $response->withJson(['errors' => 'file not found or file name invalid'],404);
	}

	public function getFileRestrictions(Request $request, Response $response, $args)
	{
		# get params from call 
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');
		$restriction 	= 'all';

		$userroles 		= $this->c->acl->getRoles();

		if(isset($this->params['filename']) && $this->params['filename'] != '')
		{
			$writeYaml		= new WriteYaml();
			$restrictions 	= $writeYaml->getYaml('media' . DIRECTORY_SEPARATOR . 'files', 'filerestrictions.yaml');
			if(isset($restrictions[$this->params['filename']]))
			{
				$restriction = $restrictions[$this->params['filename']];
			}
		}

		return $response->withJson(['userroles' => $userroles, 'restriction' => $restriction]);
	}

	public function updateFileRestrictions(Request $request, Response $response, $args)
	{
		# get params from call 
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');
		$filename 		= isset($this->params['filename']) ? $this->params['filename'] : false;
		$role 			= isset($this->params['role']) ? $this->params['role'] : false;

		if(!$filename OR !$role)
		{
			return $response->withJson(['errors' => ['message' => 'Filename or userrole is missing.']], 422);
		}

		$userroles 		= $this->c->acl->getRoles();

		if($role != 'all' AND !in_array($role, $userroles))
		{
			return $response->withJson(['errors' => ['message' => 'Userrole is unknown.']], 422);
		}

		$writeYaml		= new WriteYaml();
		$restrictions 	= $writeYaml->getYaml('media' . DIRECTORY_SEPARATOR . 'files', 'filerestrictions.yaml');
		if(!$restrictions)
		{
			$restrictions = [];
		}

		if($role == 'all')
		{
			unset($restrictions[$filename]);
		}
		else
		{
			$restrictions[$filename] = $role;
		}

		$writeYaml->updateYaml('media' . DIRECTORY_SEPARATOR . 'files', 'filerestrictions.yaml', $restrictions);

		return $response->withJson(['restrictions' => $restrictions]);
	}

	public function createImage(Request $request, Response $response, $args)
	{
		# get params from call
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');
		
		$imageProcessor	= new ProcessImage($this->settings['images']);		

		if(!$imageProcessor->checkFolders('images'))
		{
			return $response->withJson(['errors' => ['message' => 'Please check if your media-folder exists and all folders inside are writable.']], 500);
		}

        $imageParts 	= explode(";base64,", $this->params['image']);
        $imageType		= explode("/", $imageParts[0]);

		if(!isset($imageType[1]))
		{
			return $response->withJson(['errors' => ['message' => 'We did not find an image type, the file might be corrupted.']], 422);
		}

		$acceptedTypes	= [
			'png'		=> true,
			'jpg'		=> true,
			'jpeg'		=> true,
			'gif'		=> true,
			'webp'		=> true,
		];

		if(isset($this->settings['svg']) && $this->settings['svg'])
		{
			$acceptedTypes['svg+xml'] = true;
		}

		if(!isset($acceptedTypes[$imageType[1]]))
		{
			return $response->withJson(['errors' => ['message' => 'The image type is not supported.']], 422);
		}

		$imageResult = $imageProcessor->createImage($this->params['image'], $this->params['name'], $this->settings['images']);

		if($imageResult)
		{
			if(is_array($imageResult) && isset($imageResult['errors']))
			{
				return $response->withJson($imageResult,422);
			}

			# publish image directly, used for example by image field for meta-tabs
			if($this->params['publish'])
			{
				$imageProcessor->publishImage();
			}
			return $response->withJson(['name' => 'media/live/' . $imageProcessor->getFullName(),'errors' => false]);
		}

		return $response->withJson(['errors' => 'could not store image to temporary folder'],422);
	}

	public function uploadFile(Request $request, Response $response, $args)
	{
		# get params from call
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');

		if (!isset($this->params['file']))
		{
			return $response->withJson(['errors' => 'No file found.'],404);
		}

		$size 		= (int) (strlen(rtrim($this->params['file'], '=')) * 3 / 4);
		$extension 	= pathinfo($this->params['name'], PATHINFO_EXTENSION);
		$finfo 		= finfo_open( FILEINFO_MIME_TYPE );
		$mtype 		= finfo_file( $finfo, $this->params['file'] );
		finfo_close( $finfo );

		if ($size === 0)
		{
			return $response->withJson(['errors' => 'File is empty.'],422);
		}

		# 20 MB (1 byte * 1024 * 1024 * 20 (for 20 MB))
		if ($size > 20971520)
		{
			return $response->withJson(['errors' => 'File is bigger than 20MB.'],422);
		}

		# make sure only allowed filetypes are uploaded
		$allowedMimes = $this->getAllowedMtypes();

		if(!isset($allowedMimes[$mtype]))
		{
			return $response->withJson(['errors' => 'The mime-type is not allowed'],422);
		}

		if( 
			(is_array($allowedMimes[$mtype]) && !in_array($extension, $allowedMimes[$mtype])) OR
			(!is_array($allowedMimes[$mtype]) && $allowedMimes[$mtype] != $extension )
		)
		{
			return $response->withJson(['errors' => 'The file-extension is not allowed or wrong'],422);
		}

		$fileProcessor	= new ProcessFile();

		if(!$fileProcessor->checkFolders())
		{
			return $response->withJson(['errors' => 'Please check if your media-folder exists and all folders inside are writable.'], 500);
		}
	
		$fileinfo = $fileProcessor->storeFile($this->params['file'], $this->params['name']);
		
		if($fileinfo)
		{
			# publish file directly, used for example by file field for meta-tabs
			if(isset($this->params['publish']) && $this->params['publish'])
			{
				$fileProcessor->publishFile();
			}
			
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
		
		# check the resize modifier in the image markdown, set it to true and delete it from markdown
		$noresize = false;
		$markdown = isset($params['markdown']) ? $params['markdown'] : false;

	    if($markdown && (strlen($markdown) > 9) && (substr($markdown, -9) == '|noresize') )
	    {
	    	$noresize = true;
	    	$params['markdown'] = substr($markdown,0,-9);
	    }

		if($imageProcessor->publishImage($noresize))
		{
			$request 	= $request->withParsedBody($params);
		
			$block = new ControllerAuthorBlockApi($this->c);
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
		
			$block = new ControllerAuthorBlockApi($this->c);
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

	public function deleteFile(Request $request, Response $response, $args)
	{
		# get params from call 
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');

		# minimum permission is that user is allowed to delete content
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'content', 'delete'))
		{
			return $response->withJson(array('data' => false, 'errors' => 'You are not allowed to delete files.'), 403);
		}

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
		$this->uri 		= $request->getUri()->withUserInfo('');
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
	# https://wiki.selfhtml.org/wiki/MIME-Type/%C3%9Cbersicht
	# http://www.mime-type.net/application/x-latex/
	private function getAllowedMtypes()
	{
		return array(
			'application/vnd.oasis.opendocument.chart' 									=> 'odc',
			'application/vnd.oasis.opendocument.formula' 								=> 'odf',
			'application/vnd.oasis.opendocument.graphics' 								=> 'odg',
			'application/vnd.oasis.opendocument.image' 									=> 'odi',
			'application/vnd.oasis.opendocument.presentation' 							=> 'odp',
			'application/vnd.oasis.opendocument.spreadsheet' 							=> 'ods',
			'application/vnd.oasis.opendocument.text' 									=> 'odt',
			'application/vnd.oasis.opendocument.text-master' 							=> 'odm',

			'application/powerpoint'													=> 'ppt',
			'application/mspowerpoint' 													=> ['ppt','ppz','pps','pot'],
			'application/x-mspowerpoint'												=> 'ppt',
			'application/vnd.ms-powerpoint'												=> 'ppt',
			'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',

			'application/x-visio'														=> ['vsd','vst','msw'],
			'application/vnd.visio'														=> ['vsd','vst','msw'],
			'application/x-project'														=> ['mpc','mpt','mpv','mpx'],
			'application/vnd.ms-project'												=> 'mpp',

			'application/excel'															=> ['xla','xlb','xlc','xld','xlk','xll','xlm','xls','xlt','xlv','xlw'],
			'application/msexcel' 														=> ['xls','xla'],
			'application/x-excel'														=> ['xla','xlb','xlc','xld','xlk','xll','xlm','xls','xlt','xlv','xlw'],
			'application/x-msexcel'														=> ['xls', 'xla','xlw'],
			'application/vnd.ms-excel'													=> ['xlb','xlc','xll','xlm','xls','xlw'],
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 		=> 'xlsx', 

			'application/mshelp' 														=> ['hlp','chm'],
			'application/msword' 														=> ['doc','dot'],
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document' 	=> 'docx',

			'application/vnd.apple.keynote'												=> 'key',
			'application/vnd.apple.numbers'												=> 'numbers',
			'application/vnd.apple.pages'												=> 'pages',

			'application/x-latex' 														=> ['ltx','latex'],
			'application/pdf'															=> 'pdf',

			'application/vnd.amazon.mobi8-ebook'										=> 'azw3',
			'application/x-mobipocket-ebook'											=> 'mobi',
			'application/epub+zip'														=> 'epub',

			'application/x-gtar' 														=> 'gtar',
			'application/x-tar' 														=> 'tar',
			'application/zip' 															=> 'zip',
			'application/gzip'															=> 'gz',
		   	'application/x-gzip'														=> ['gz', 'gzip'],
		   	'application/x-compressed'													=> ['gz','tgz','z','zip'],
		   	'application/x-zip-compressed'												=> 'zip',
		   	'application/vnd.rar'														=> 'rar',
		   	'application/x-7z-compressed'												=> '7z',

		   	'application/rtf'															=> 'rtf',
		   	'application/x-rtf'															=> 'rtf',

			'text/calendar' 															=> 'ics',
			'text/comma-separated-values' 												=> 'csv',
			'text/css' 																	=> 'css',
			'text/plain' 																=> 'txt',
			'text/richtext' 															=> 'rtx',
			'text/rtf' 																	=> 'rtf',

			'audio/basic' 																=> ['au','snd'],
			'audio/mpeg' 																=> 'mp3',
			'audio/mp4' 																=> 'mp4',
			'audio/ogg' 																=> 'ogg',
			'audio/wav' 																=> 'wav',
			'audio/x-aiff' 																=> ['aif','aiff','aifc'],
			'audio/x-midi' 																=> ['mid','midi'],
			'audio/x-mpeg' 																=> 'mp2',
			'audio/x-pn-realaudio' 														=> ['ram','ra'],

		   	'image/png'																	=> 'png',
		   	'image/jpeg' 																=> ['jpeg','jpe','jpg'],
		   	'image/gif'																	=> 'gif',
		   	'image/tiff' 																=> ['tiff','tif'],
		   	'image/svg+xml'																=> 'svg',
		   	'image/x-icon'																=> 'ico',
		   	'image/webp' 																=> 'webp',

			'video/mpeg' 																=> ['mpeg','mpg','mpe'],
			'video/mp4' 																=> 'mp4',
			'video/ogg' 																=> ['ogg','ogv'],
			'video/quicktime' 															=> ['qt','mov'],
			'video/vnd.vivo' 															=> ['viv','vivo'],
			'video/webm' 																=> 'webm',
			'video/x-msvideo' 															=> 'avi',
			'video/x-sgi-movie' 														=> 'movie',
			'video/3gpp'  																=> '3gp',
		);
	}	
}
