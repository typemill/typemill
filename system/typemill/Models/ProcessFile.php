<?php

namespace Typemill\Models;

class ProcessFile extends ProcessAssets
{
	public function storeFile($file, $name)
	{

		$this->clearTempFolder();

		$this->setPathInfo($name);

		$this->decode($file);

		$fullpath = $this->getFullPath();

		if($this->filedata !== false && file_put_contents($fullpath, $this->filedata))
		{
			$size = filesize($this->getFullPath());
			$size = $this->formatSizeUnits($size);

			$title = str_replace('-', ' ', $this->filename);
			$title = $title . ' (' . strtoupper($this->extension) . ', ' . $size .')';

			return [
				'title' 		=> $title, 
				'name' 			=> $this->filename, 
				'extension' 	=> $this->extension, 
				'size' 			=> $size, 
				'url' 			=> 'media/files/' . $this->getFullName()
			];
		}

		return false;
	}





	/**
	 * Moves the uploaded file to the upload directory. Only used for settings / NON VUE.JS uploads
	 *
	 * @param string $directory directory to which the file is moved
	 * @param UploadedFile $uploadedFile file uploaded file to move
	 * @return string filename of moved file
	 */
	public function moveUploadedFile(UploadedFile $uploadedFile, $overwrite = false, $name = false, $folder = NULL)
	{
		$this->setFileName($uploadedFile->getClientFilename(), 'file');
		
		if($name)
		{
			$this->setFileName($name . '.' . $this->extension, 'file', $overwrite);
		}
		
	    $uploadedFile->moveTo($this->fileFolder . $this->getFullName());

	    return $this->getFullName();
	}

	public function publishFile()
	{
		$files 			= scandir($this->tmpFolder);
		$success		= true;
		
		foreach($files as $file)
		{
			if (!in_array($file, array(".","..")))
			{
				$success = rename($this->tmpFolder . $file, $this->fileFolder . $file);
			}
		}
		
		return $success;
	}






	public function deleteFile($name)
	{
		# validate name 
		$name = basename($name);

		if(file_exists($this->fileFolder . $name) && unlink($this->fileFolder . $name))
		{
			return true;
		}

		return false;
	}


	public function deleteFileWithName($name)
	{
		# e.g. delete $name = 'logo';

		$name = basename($name);

		if($name != '' && !in_array($name, array(".","..")))
		{
			foreach(glob($this->fileFolder . $name) as $file)
			{
				unlink($file);
			}
		}
	}


	/*
	* scans content of a folder (without recursion)
	* vars: folder path as string
	* returns: one-dimensional array with names of folders and files
	*/
	public function scanFilesFlat()
	{
		$files 		= scandir($this->fileFolder);
		$filelist	= array();

		foreach ($files as $key => $name)
		{
			if (!in_array($name, array(".","..","filerestrictions.yaml")) && file_exists($this->fileFolder . $name))
			{
				$filelist[] = [
					'name' 		=> $name,
					'timestamp'	=> filemtime($this->fileFolder . $name),
					'info'		=> pathinfo($this->fileFolder . $name),
					'url'		=> 'media/files/' . $name,
				];
			}
		}

		$filelist = Helpers::array_sort($filelist, 'timestamp', SORT_DESC);

		return $filelist;
	}


	public function getFileDetails($name, $structure)
	{
		$name = basename($name);

		if (!in_array($name, array(".","..")) && file_exists($this->fileFolder . $name))
		{
			$filedetails = [
				'name' 		=> $name,
				'timestamp'	=> filemtime($this->fileFolder . $name),
				'bytes' 	=> filesize($this->fileFolder . $name),
				'info'		=> pathinfo($this->fileFolder . $name),
				'url'		=> 'media/files/' . $name,
				'pages'		=> $this->findPagesWithUrl($structure, $name, $result = [])
			];

			return $filedetails;
		}

		return false;
	}

}