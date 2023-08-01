<?php

namespace Typemill\Models;

use Typemill\Models\StorageWrapper;
use Typemill\Extensions\ParsedownExtension;

class Content
{
	private $storage;

	public function __construct($baseurl = NULL)
	{
		$this->storage 				= new StorageWrapper('\Typemill\Models\Storage');
		$this->parsedown 			= new ParsedownExtension($baseurl);
	}

	public function getDraftMarkdown($item)
	{
		# needed for ToC links
		# $relurl = '/tm/content/' . $this->settings['editor'] . '/' . $this->item->urlRel;
		
		# to fix footnote-logic in parsedown, set visual mode to true
		# $this->parsedown->setVisualMode();

		# make sure you get the txt version unless page is published
#		$filetype = ($item->status == 'published') ? '.md' : '.txt';

		$filetype = '.txt';

		$markdown = $this->storage->getFile('contentFolder', '', $item->pathWithoutType . $filetype);

		if(!$markdown)
		{
			$filetype = '.md';
			$markdown = $this->storage->getFile('contentFolder', '', $item->pathWithoutType . $filetype);
		}

		# if !$mardkown ?

		if($markdown == '')
		{
			$markdownArray = [];
		}
		elseif($filetype == '.txt')
		{
			$markdownArray = json_decode($markdown);
		}
		else
		{
			$markdownArray = $this->parsedown->markdownToArrayBlocks($markdown);
		}

		return $markdownArray;
	}

	public function getLiveMarkdown($item)
	{
		$filetype = '.md';

		$markdown = $this->storage->getFile('contentFolder', '', $item->pathWithoutType . $filetype);

		return $markdown;
	}

	public function saveDraftMarkdown($item, array $markdownArray)
	{
		$markdown = json_encode($markdownArray);

		if($this->storage->writeFile('contentFolder', '', $item->pathWithoutType . '.txt', $markdown))
		{
			return true;
		}

		return $this->storage->getError();
	}

	public function publishMarkdown($item, array $markdownArray)
	{
		$markdown = $this->parsedown->arrayBlocksToMarkdown($markdownArray);

		if($this->storage->writeFile('contentFolder', '', $item->pathWithoutType . '.md', $markdown))
		{
			$this->storage->deleteFile('contentFolder', '', $item->pathWithoutType . '.txt');
			
			return true;
		}

		return $this->storage->getError();
	}

	public function unpublishMarkdown($item, array $markdownArray)
	{
		$markdown = json_encode($markdownArray);

		if($this->storage->writeFile('contentFolder', '', $item->pathWithoutType . '.txt', $markdown))
		{
			$this->storage->deleteFile('contentFolder', '', $item->pathWithoutType . '.md');

			return true;
		}

		return $this->storage->getError();
	}

	public function deleteDraft($item)
	{
		if($this->storage->deleteFile('contentFolder', '', $item->pathWithoutType . '.txt'))
		{	
			return true;
		}

		return $this->storage->getError();
	}

	public function deletePage($item)
	{
		$extensions = ['.md', '.txt', '.yaml'];

		$result = true;
		foreach($extensions as $extension)
		{
			$result = $this->storage->deleteFile('contentFolder', '', $item->pathWithoutType . $extension);
		}

		if($result)
		{
			return true;
		}

		return $this->storage->getError();
	}

	public function addDraftHtml($markdownArray)
	{
		$content = [];

		$toc_id = false;

		foreach($markdownArray as $key => $markdown)
		{
			if($markdown == "[TOC]")
			{
				$toc_id = $key;
			}

			$contentArray 	= $this->parsedown->text($markdown);
			$html			= $this->parsedown->markup($contentArray);

			$content[$key] = [
				'id' 		=> $key,
				'markdown'	=> $markdown,
				'html'		=> $html
			];
		}

		if($toc_id)
		{
			# generate the toc markup
			$tocMarkup = $this->parsedown->buildTOC($this->parsedown->headlines);

			# add to content html
			$content[$toc_id]['html'] =  $tocMarkup;
		}

		return $content;
	}

	public function getDraftHtml($markdownArray)
	{
		foreach($markdownArray as $key => $block)
		{
			# parse markdown-file to content-array
			$contentArray 	= $this->parsedown->text($block);

			# parse markdown-content-array to content-string
			$content[$key]	= $this->parsedown->markup($contentArray);
		}

		return $content;
	}

	public function getContentArray($markdown)
	{
		return $this->parsedown->text($markdown);
	}

	public function getContentHtml($contentArray)
	{
		return $this->parsedown->markup($contentArray);
	}

	public function arrayBlocksToMarkdown($arrayBlocks)
	{
		die("please use markdownToArrayText in content model");

        $markdown = '';
        
        foreach($arrayBlocks as $block)
        {
            $markdown .=  $block . "\n\n";
        }
        
        return $markdown;
	}

	public function markdownArrayToText(array $markdownArray)
	{	
		return $this->parsedown->arrayBlocksToMarkdown($markdownArray);
	}

	public function markdownTextToArray(string $markdown)
	{
		return $this->parsedown->markdownToArrayBlocks($markdown);
	}

	public function getFirstImage(array $contentArray)
	{
		foreach($contentArray as $block)
		{
			if(isset($block['name']) && $block['name'] == 'p')
			{
				if(isset($block['handler']['argument']) && substr($block['handler']['argument'], 0, 2) == '![' )
				{
					return $block['handler']['argument'];	
				}
			}
		}
		
		return false;
	}


########## FIX
	public function generateToc($content, $relurl)
	{
		die('Please fix generateToc in content.php');

		# we assume that page has no table of content
		$toc = false;
		
		# needed for ToC links
		$relurl = '/tm/content/' . $this->settings['editor'] . '/' . $this->item->urlRel;
		
		# loop through mardkown-array and create html-blocks
		foreach($content as $key => $block)
		{
			# parse markdown-file to content-array
			$contentArray 	= $parsedown->text($block);
			
			if($block == '[TOC]')
			{
				# toc is true and holds the key of the table of content now
				$toc = $key;
			}

			# parse markdown-content-array to content-string
			$content[$key]	= ['id' => $key, 'html' => $parsedown->markup($contentArray)];
		}

		# if page has a table of content
		if($toc)
		{
			# generate the toc markup
			$tocMarkup = $parsedown->buildTOC($parsedown->headlines);

			# toc holds the id of the table of content and the html-markup now
			$toc = ['id' => $toc, 'html' => $tocMarkup];
		}

		return $toc;
	}

	# MOVE SOMEWHERE ELSE
	public function checkCustomCSS($theme)
	{
		return $this->storage->checkFile('cacheFolder', '', $theme . '-custom.css');
	}

	public function checkLogoFile($logo)
	{
		return $this->storage->checkFile('basepath', '', $logo);
	}

	public function getTitle(array $markdown)
	{
		if(!is_array($markdown))
		{
			$markdown = $this->markdownTextToArray($markdown);
		}

		return trim($markdown[0], "# ");
	}

	public function getDescription(array $markdown)
	{
		if(!is_array($markdown))
		{
			$markdown = $this->markdownTextToArray($markdown);
		}

		$description = isset($markdown[1]) ? $markdown[1] : '';

		# create description or abstract from content
		if($description !== '')
		{
			$firstLineArray = $this->parsedown->text($description);
			$description 	= strip_tags($this->parsedown->markup($firstLineArray));

			# if description is very short
			if(strlen($description) < 100 && isset($markdown[2]))
			{
				$secondLineArray = $this->parsedown->text($markdown[2]);
				$description 	.= ' ' . strip_tags($this->parsedown->markup($secondLineArray));
			}

			# if description is too long
			if(strlen($description) > 160)
			{
				$description	= substr($description, 0, 160);
				$lastSpace 		= strrpos($description, ' ');
				$description 	= substr($description, 0, $lastSpace);
			}
		}

		return $description;
	}
}