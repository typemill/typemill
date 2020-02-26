<?php

namespace Typemill\Models;

use Typemill\Extensions\ParsedownExtension;

class WriteYaml extends Write
{
	/**
	 * Get the a yaml file.
	 * @param string $fileName is the name of the Yaml Folder.
	 * @param string $yamlFileName is the name of the Yaml File.
	 */
	public function getYaml($folderName, $yamlFileName)
	{
		$yaml = $this->getFile($folderName, $yamlFileName);
			
		if($yaml)
		{
			return \Symfony\Component\Yaml\Yaml::parse($yaml);
		}
		return false;
	}

	/**
	 * Writes a yaml file.
	 * @param string $fileName is the name of the Yaml Folder.
	 * @param string $yamlFileName is the name of the Yaml File.
	 * @param array $contentArray is the content as an array.
	 */	
	public function updateYaml($folderName, $yamlFileName, $contentArray)
	{
		$yaml = \Symfony\Component\Yaml\Yaml::dump($contentArray,6);
		if($this->writeFile($folderName, $yamlFileName, $yaml))
		{
			return true;
		}
		return false;
	}

	# used by contentApiController (backend) and pageController (frontend)
	public function getPageMeta($settings, $item)
	{
		$meta = $this->getYaml($settings['contentFolder'], $item->pathWithoutType . '.yaml');

		if(!$meta)
		{
			return false;
		}

		# compare with meta that are in use right now (e.g. changed theme, disabled plugin)
		$metascheme = $this->getYaml('cache', 'metatabs.yaml');

		if($metascheme)
		{
			$meta = $this->whitelistMeta($meta,$metascheme);
		}

		$meta = $this->addFileTimeToMeta($meta, $item, $settings);

		return $meta;
	}

	# used by contentApiController (backend) and pageController (frontend)
	public function getPageMetaDefaults($content, $settings, $item)
	{
		# initialize parsedown extension
		$parsedown = new ParsedownExtension();

		# if content is not an array, then transform it
		if(!is_array($content))
		{
			# turn markdown into an array of markdown-blocks
			$content = $parsedown->markdownToArrayBlocks($content);
		}

		$title = false;

		# delete markdown from title
		if(isset($content[0]))
		{
			$title = trim($content[0], "# ");
		}

		$description = false;

		# delete markdown from title
		if(isset($content[1]))
		{
			$firstLineArray = $parsedown->text($content[1]);
			$description 	= strip_tags($parsedown->markup($firstLineArray, $item->urlAbs));
			$description	= substr($description, 0, 300);
			$lastSpace 		= strrpos($description, ' ');
			$description 	= substr($description, 0, $lastSpace);
		}

		$author = $settings['author'];

		if(isset($_SESSION))
		{
			if(isset($_SESSION['firstname']) && $_SESSION['firstname'] !='' && isset($_SESSION['lastname']) && $_SESSION['lastname'] != '')
			{
				$author = $_SESSION['firstname'] . ' ' . $_SESSION['lastname'];
			}
			elseif(isset($_SESSION['user']))
			{
				$author = $_SESSION['user'];
			}
		}

		# create new meta-file
		$meta = [
			'meta' => [
				'title' 		=> $title,
				'description' 	=> $description,
				'author' 		=> $author,
				'created'		=> date("Y-m-d"),
				'time'			=> date("H-i-s"),
			]
		];

		$this->updateYaml($settings['contentFolder'], $item->pathWithoutType . '.yaml', $meta);
		
		$meta = $this->addFileTimeToMeta($meta, $item, $settings);

		return $meta;
	}


	private function whitelistMeta($meta, $metascheme)
	{
		# we have only 2 dimensions, so no recursive needed
		foreach($meta as $tab => $values)
		{
			if(!isset($metascheme[$tab]))
			{
				unset($meta[$tab]);
			}
			foreach($values as $key => $value)
			{
				if(!isset($metascheme[$tab][$key]))
				{
					unset($meta[$tab][$key]);
				}
			}
		}
		return $meta;
	}

	private function addFileTimeToMeta($meta, $item, $settings)
	{
		$filePath = $settings['contentFolder'] . $item->path;
		$fileType = isset($item->fileType) ? $item->fileType : 'md';
		
		# check if url is a folder.
		if($item->elementType == 'folder')
		{
			$filePath = $settings['contentFolder'] . $item->path . DIRECTORY_SEPARATOR . 'index.'. $fileType; 
		}

		# add the modified date for the file
		$meta['meta']['modified'] = file_exists($filePath) ? date("Y-m-d",filemtime($filePath)) : false;

		return $meta;
	}


	public function transformPagesToPosts($folder){

		$filetypes			= array('md', 'txt', 'yaml');

		foreach($folder->folderContent as $page)
		{
			# create old filename without filetype
			$oldFile 	= $this->basePath . 'content' . $page->pathWithoutType;

			# set default date
			$date 		= date('Y-m-d', time());
			$time		= date('H-i', time());

			$meta 		= $this->getYaml('content', $page->pathWithoutType . '.yaml');

			if($meta)
			{
				# get dates from meta
				if(isset($meta['meta']['manualdate'])){ $date = $meta['meta']['manualdate']; }
				elseif(isset($meta['meta']['created'])){ $date = $meta['meta']['created']; }
				elseif(isset($meta['meta']['modified'])){ $date = $meta['meta']['modified']; }

				# set time
				if(isset($meta['meta']['time']))
				{
					$time = $meta['meta']['time'];
				}
			}

			$datetime 	= $date . '-' . $time;
			$datetime 	= implode(explode('-', $datetime));
			$datetime	= substr($datetime,0,12);

			# create new file-name without filetype
			$newFile 	= $this->basePath . 'content' . $folder->path . DIRECTORY_SEPARATOR . $datetime . '-' . $page->slug;

			$result 	= true;

			foreach($filetypes as $filetype)
			{
				$oldFilePath = $oldFile . '.' . $filetype;
				$newFilePath = $newFile . '.' . $filetype;
				
				#check if file with filetype exists and rename
				if($oldFilePath != $newFilePath && file_exists($oldFilePath))
				{
					if(@rename($oldFilePath, $newFilePath))
					{
						$result = $result;
					}
					else
					{
						$result = false;
					}
				}
			}
		}
	}

	public function transformPostsToPages($folder){

		$filetypes			= array('md', 'txt', 'yaml');
		$index				= 0;

		foreach($folder->folderContent as $page)
		{
			# create old filename without filetype
			$oldFile 	= $this->basePath . 'content' . $page->pathWithoutType;

			$order 		= $index;

			if($index < 10)
			{
				$order = '0' . $index;
			}

			# create new file-name without filetype
			$newFile 	= $this->basePath . 'content' . $folder->path . DIRECTORY_SEPARATOR . $order . '-' . $page->slug;

			$result 	= true;

			foreach($filetypes as $filetype)
			{
				$oldFilePath = $oldFile . '.' . $filetype;
				$newFilePath = $newFile . '.' . $filetype;
				
				#check if file with filetype exists and rename
				if($oldFilePath != $newFilePath && file_exists($oldFilePath))
				{
					if(@rename($oldFilePath, $newFilePath))
					{
						$result = $result;
					}
					else
					{
						$result = false;
					}
				}
			}

			$index++;
		}
	}
}