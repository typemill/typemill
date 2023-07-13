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
}