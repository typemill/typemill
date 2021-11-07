<?php

namespace Typemill\Controllers;

use Typemill\Models\WriteYaml;

class ControllerDownload extends ControllerShared
{
	public function download($request, $response, $args)
	{

		$filename 	= isset($args['params']) ? $args['params'] : false;
		$root 		= $this->c->get('settings')['rootPath'];
		$mediapath 	= 'media/files/';
		$filepath 	= $root . $mediapath;

		if(!$filename)
		{
			die('the requested file does not exist.');
		}

		# validate
		$allowedFiletypes = [];
		if(!$this->validate($filepath, $filename, $allowedFiletypes))
		{
			die('the requested file is not allowed.');
		}

		$writeYaml		= new WriteYaml();
		$restrictions 	= $writeYaml->getYaml('media' . DIRECTORY_SEPARATOR . 'files', 'filerestrictions.yaml');

		if($restrictions && isset($restrictions[$mediapath . $filename]))
		{
			$allowedrole = $restrictions[$mediapath . $filename];

			if(!isset($_SESSION['role']))
			{
				$this->c->flash->addMessage('error', "You have to be an authenticated $allowedrole to download this file.");
				return $response->withRedirect($this->c->router->pathFor('auth.show'));
			}
			elseif(
				$_SESSION['role'] != 'administrator'
				AND $_SESSION['role'] != $allowedrole 
				AND !$this->c->acl->inheritsRole($_SESSION['role'], $allowedrole)
			)
			{
				$this->c->flash->addMessage('error', "You have to be a $allowedrole to download this file.");
				return $response->withRedirect($this->c->router->pathFor('auth.show'));
			}
		}

		$file = $filepath . $filename;

		# for now we only allow one download
		$this->sendDownload($file);
		exit;
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
	private function validate($path, $file, $allowedFiletypes) 
	{
		$filepath = $path . $file;

		# check if file exists
		if (!isset($filepath) || empty($filepath) || !file_exists($filepath))
		{
			return false;
		}
		
		# check allowed filetypes
		if(!empty($allowedFiletypes))
		{
			$fileAllowed = false;
			foreach ($allowedFiletypes as $filetype) 
			{
				if (strpos($file, $filetype) === (strlen($file) - strlen($filetype))) 
				{
					$fileAllowed = true; //ends with $filetype
				}
			}
			
			if (!$fileAllowed) return false;
		}

		# check download directory
		if (strpos($file, '..') !== false)
		{
			return false;
		}
		return true;
	}

	/**
	 * Download function.
	 * Sets the HTTP header and supplies the given file
	 * as a download to the browser.
	 *
	 * @param string $file path to file
	 */
	private function sendDownload($file) 
	{
		# Parse information
		$pathinfo 	= pathinfo($file);
		$extension 	= strtolower($pathinfo['extension']);
		$mimetype 	= null;
		
		# Get mimetype for extension
		# This list can be extended as you need it.
		# A good start to find mimetypes is the apache mime.types list
		# http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types
		switch ($extension) {
			case 'zip':     $mimetype = "application/zip"; break;
			default:        $mimetype = "application/force-download";
		}
		
		# Required for some browsers like Safari and IE
		if (ini_get('zlib.output_compression'))
		{
			ini_set('zlib.output_compression', 'Off');
		}

		header('Pragma: public');
		header('Content-Encoding: none');
		header('Accept-Ranges: bytes');  # Allow support for download resume
		header('Expires: 0');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file)) . ' GMT');
		header_remove("Last-Modified");
		header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
		header('Cache-Control: private', false); # required for some browsers
		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename="'.basename($file).'";'); # Make the browser display the Save As dialog
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: '.filesize($file));
		ob_end_flush();
		readfile($file); # This is necessary in order to get it to actually download the file, otherwise it will be 0Kb
	}
}