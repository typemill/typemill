<?php

namespace Typemill\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Typemill\Models\StorageWrapper;
use Typemill\Static\Translations;

class ControllerWebDownload extends Controller
{
	public function download(Request $request, Response $response, $args)
	{
		$filename 		= isset($args['params']) ? $args['params'] : false;
		if(!$filename)
		{
			$response->getBody()->write(Translations::translate('the requested file does not exist.'));
			return $response->withStatus(404);
		}

		$storage 		= new StorageWrapper('\Typemill\Models\Storage');
		$restrictions 	= $storage->getYaml('fileFolder', '', 'filerestrictions.yaml');

		$filepath 		= $storage->getFolderPath('fileFolder');
		$filefolder 	= 'media/files/';

		# validate
		$allowedFiletypes = [];
		if(!$this->validate($filepath, $filename, $allowedFiletypes))
		{
			$response->getBody()->write(Translations::translate('the requested filetype does not exist.'));
			return $response->withStatus(404);
		}

		if($restrictions && isset($restrictions[$filefolder . $filename]))
		{
			$userrole 			= $request->getAttribute('c_userrole');
			$allowedrole 		= $restrictions[$filefolder . $filename];

			if(!$userrole)
			{
				$this->c->get('flash')->addMessage('error', Translations::translate('To download this file you need to be authenticated with the role') . ' ' . $allowedrole );
				
				return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
			}
			elseif(
				$userrole != 'administrator'
				AND $userrole != $allowedrole 
				AND !$this->c->get('acl')->inheritsRole($userrole, $allowedrole)
			)
			{
				$this->c->get('flash')->addMessage('error', Translations::translate('To download this file you need to be authenticated with the role') . ' ' . $allowedrole );

				return $response->withHeader('Location', $this->routeParser->urlFor('auth.show'))->withStatus(302);
			}
		}

		$file = $filepath . $filename;

	    # Dynamically determine MIME type based on the file extension
	    $pathinfo   = pathinfo($file);
	    $extension  = strtolower($pathinfo['extension']);

	   	# You can extend this list based on the file types you expect to serve
	   	# http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types
	    $mime_types = [
	        'zip' => 'application/zip',
	        'pdf' => 'application/pdf',
	        'jpeg' => 'image/jpeg',
	        'jpg' => 'image/jpeg',
	        'png' => 'image/png',
	        'default' => 'application/octet-stream',
	    ];

	   	$mimetype = $mime_types[$extension] ?? $mime_types['default'];

	    # Disable zlib.output_compression for this download
	    if (ini_get('zlib.output_compression'))
	    {
	        ini_set('zlib.output_compression', 'Off');
	    }

	    try {

	        # Read the file content
	        $fileContent = file_get_contents($file);
	        if ($fileContent === false) {
	            throw new Exception('Failed to read file content.');
	        }
	        
	        # Clear the response body and write the file content
	        $body = new \Slim\Psr7\Stream(fopen('php://temp', 'r+'));
	        $body->write($fileContent);
	        $response = $response->withBody($body);

	        # Set headers
	        $response = $response->withBody($body)
	                             ->withHeader('Content-Type', $mimetype)
	                             ->withHeader('Content-Disposition', 'attachment; filename="' . basename($file) . '"')
	                             ->withHeader('Content-Length', filesize($file))
	                             ->withHeader('Pragma', 'public')
	                             ->withHeader('Cache-Control', 'max-age=0, no-cache, no-store, must-revalidate')
	                             ->withHeader('Content-Encoding', 'none')
	                             ->withHeader('Accept-Ranges', 'bytes');

	        return $response;

	    } catch (Exception $e) {
	        
	        # Log the error
	        error_log('Error sending file: ' . $e->getMessage());
	        
	        # Return an error response
	        $response->getBody()->write("Error downloading file. Please try again later.");
	        return $response->withStatus(500);
	    }
	}

	/**
	 * Validate if the file exists and if
	 * there is a permission (download dir) to download this file
	 *
	 * You should ALWAYS call this method if you don't want
	 * somebody to download files not intended to be for the public.
	 *
	 * @param string $file GET parameter
	 * @param array $allowedFiletypes (defined in the head of this file)
	 * @return bool true if validation was successfull
	 */
	private function validate($path, $filename, $allowedFiletypes) 
	{
		$filepath = $path . $filename;

		# check if file exists
		if (!isset($filepath)  || empty($filepath)  || !file_exists($filepath) )
		{
			return false;
		}

		# check allowed filetypes
		if(!empty($allowedFiletypes))
		{
			$fileAllowed = false;
			foreach ($allowedFiletypes as $filetype) 
			{
				if (strpos($filename, $filetype) === (strlen($filename) - strlen($filetype))) 
				{
					$fileAllowed = true; //ends with $filetype
				}
			}
			
			if (!$fileAllowed) return false;
		}

		# check download directory
		if (strpos($filename, '..') !== false)
		{
			return false;
		}

		return true;
	}
}