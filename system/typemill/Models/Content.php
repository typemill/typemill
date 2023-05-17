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

	public function saveDraftMarkdown($item, array $markdownArray)
	{
		$markdown = json_encode($markdownArray);

		if($this->storage->writeFile('contentFolder', '', $item->pathWithoutType . '.txt', $markdown))
		{
			return true;
		}

		return $this->storage->getError();
	}

	public function markdownArrayToText(array $markdownArray)
	{
		return $this->parsedown->arrayBlocksToMarkdown($markdownArray);
	}

	public function markdownTextToArray(string $markdown)
	{
		return $this->parsedown->markdownToArrayBlocks($markdown);
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

	public function arrayBlocksToMarkdown($arrayBlocks)
	{
        $markdown = '';
        
        foreach($arrayBlocks as $block)
        {
            $markdown .=  $block . "\n\n";
        }
        
        return $markdown;
	}

	public function generateToc($content, $relurl)
	{
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
}