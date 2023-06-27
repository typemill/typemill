<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\ProcessFile;
use Typemill\Models\StorageWrapper;

class ControllerApiFile extends Controller
{
	public function getFileRestrictions(Request $request, Response $response, $args)
	{
		$params = $request->getQueryParams();

		$restriction 	= 'all';

		$userroles 		= $this->c->get('acl')->getRoles();

		if(isset($params['filename']) && $params['filename'] != '')
		{
			$storage 		= new StorageWrapper('\Typemill\Models\Storage');
			$restrictions 	= $storage->getYaml('fileFolder', '', 'filerestrictions.yaml');

			if(isset($restrictions[$params['filename']]))
			{
				$restriction = $restrictions[$params['filename']];
			}
		}

		$response->getBody()->write(json_encode([
			'userroles'		=> $userroles, 
			'restriction'	=> $restriction
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}

	public function updateFileRestrictions(Request $request, Response $response, $args)
	{
		# get params from call 
		$params 		= $request->getParsedBody();
		$filename 		= $params['filename'] ?? false;
		$role 			= $params['role'] ?? false;

		if(!$filename OR !$role)
		{
			$response->getBody()->write(json_encode([
				'message' => 'Filename or userrole is missing.'
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
		}

		$userroles 		= $this->c->get("acl")->getRoles();

		if($role != 'all' AND !in_array($role, $userroles))
		{
			$response->getBody()->write(json_encode([
				'message' => 'Userrole is unknown.'
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
		}

		$storage 		= new StorageWrapper('\Typemill\Models\Storage');
		$restrictions 	= $storage->getYaml('fileFolder', '', 'filerestrictions.yaml');
		if(!$restrictions)
		{
			$restrictions = [];
		}

		# make sure you always add live path to the restriction registry, not temporary path
		$filename = str_replace('media/tmp', 'media/files', $filename);

		if($role == 'all')
		{
			unset($restrictions[$filename]);
		}
		else
		{
			$restrictions[$filename] = $role;
		}

		$storage->updateYaml('fileFolder', '', 'filerestrictions.yaml', $restrictions);

		$response->getBody()->write(json_encode([
			'restrictions'	=> $restrictions
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}

	public function uploadFile(Request $request, Response $response, $args)
	{
		$params 	= $request->getParsedBody();

		if (!isset($params['file']))
		{
			$response->getBody()->write(json_encode([
				'message' => 'No file found.'
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
		}

		$size 		= (int) (strlen(rtrim($params['file'], '=')) * 3 / 4);
		$extension 	= pathinfo($params['name'], PATHINFO_EXTENSION);
		$finfo 		= finfo_open( FILEINFO_MIME_TYPE );
		$mtype 		= @finfo_file( $finfo, $params['file'] );
		finfo_close($finfo);

		if ($size === 0)
		{
			$response->getBody()->write(json_encode([
				'message' => 'File is empty.'
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
		}

		# 20 MB (1 byte * 1024 * 1024 * 20 (for 20 MB))
		if ($size > 20971520)
		{
			$response->getBody()->write(json_encode([
				'message' => 'File is bigger than 20MB.'
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
		}

		# check extension first
		if (!$this->checkAllowedExtensions($extension))
		{
			$response->getBody()->write(json_encode([
				'message' => 'Filetype is not allowed.'
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
		}

		# check mimetype and extension if there is a mimetype. 
		# in some environments the finfo_file does not work with a base64 string. 
		if($mtype)
		{
			if(!$this->checkAllowedMimeTypes($mtype, $extension))
			{
				$response->getBody()->write(json_encode([
					'message' => 'The mime-type is missing, not allowed or does not fit to the file extension.'
				]));

				return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
			}
		}

		$fileProcessor	= new ProcessFile();

		$fileinfo = $fileProcessor->storeFile($params['file'], $params['name']);
		$filePath = str_replace('media/files', 'media/tmp', $fileinfo['url']);

		if($fileinfo)
		{
			# if the previous check of the mtype with the base64 string failed, then do it now again with the temporary file
			if(!$mtype)
			{
				$fullPath 	= $this->settings['rootPath'] . $filePath;
				$finfo 		= finfo_open( FILEINFO_MIME_TYPE );
				$mtype 		= @finfo_file( $finfo, $fullPath );
				finfo_close($finfo);

				if(!$mtype OR !$this->checkAllowedMimeTypes($mtype, $extension))
				{
					$fileProcessor->clearTempFolder();

					$response->getBody()->write(json_encode([
						'message' => 'The mime-type is missing, not allowed or does not fit to the file extension.'
					]));

					return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
				}
			}

			/*
				# publish file directly, used for example by file field for meta-tabs
				if(isset($params['publish']) && $params['publish'])
				{
					$fileProcessor->publishFile();
				}
			*/

			$response->getBody()->write(json_encode([
				'message' => 'File has been stored',
				'fileinfo' => $fileinfo,
				'filepath' => $filePath
			]));

			return $response->withHeader('Content-Type', 'application/json');
		}

		$response->getBody()->write(json_encode([
			'message' => 'Could not store file to temporary folder.'
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
	}

	public function publishFile(Request $request, Response $response, $args)
	{
		$params = $request->getParsedBody();

		if(!isset($params['file']) OR !$params['file'])
		{
			$response->getBody()->write(json_encode([
				'message' 		=> 'filename is missing.',
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$storage 	= new StorageWrapper('\Typemill\Models\Storage');

		$result 	= $storage->publishFile($params['file']);

		if(!$result)
		{
			$response->getBody()->write(json_encode([
				'message' 		=> $storage->getError()
			]));

			return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
		}

		$response->getBody()->write(json_encode([
			'message' => 'File saved successfully',
			'path' => $result,
		]));

		return $response->withHeader('Content-Type', 'application/json');		
	}

















	public function getMediaLibFiles(Request $request, Response $response, $args)
	{
		# get params from call
		$this->params 	= $request->getParsedBody();
		$this->uri 		= $request->getUri()->withUserInfo('');

		$fileProcessor	= new ProcessFile();
		if(!$fileProcessor->checkFolders())
		{
			return $response->withJson(['errors' => 'Please check if your media-folder exists and all folders inside are writable.'], 500);
		}
		
		$filelist 		= $fileProcessor->scanFilesFlat();

		$response->getBody()->write(json_encode([
			'files' => $filelist
		]));

		return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
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

	protected function checkAllowedMimeTypes($mtype, $extension)
	{
		$allowedMimes = $this->getAllowedMtypes();

		if(!isset($allowedMimes[$mtype]))
		{
			return false;
		}

		if(
			(is_array($allowedMimes[$mtype]) && !in_array($extension, $allowedMimes[$mtype])) OR
			(!is_array($allowedMimes[$mtype]) && $allowedMimes[$mtype] != $extension )
		)
		{
			return false;
		}

		return true;
	}

	protected function checkAllowedExtensions($extension)
	{	
		$mtypes = $this->getAllowedMtypes();
		foreach($mtypes as $mtExtension)
		{
			if(is_array($mtExtension))
			{
				if(in_array($extension, $mtExtension))
				{
					return true;
				}
			}
			else
			{
				if($extension == $mtExtension)
				{
					return true;
				}
			}
		}

		return false;
	}
}
