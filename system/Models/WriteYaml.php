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
}