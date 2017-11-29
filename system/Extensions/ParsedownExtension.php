<?php

namespace Typemill\Extensions;

class ParsedownExtension extends \ParsedownExtra
{
	function __construct()
    {
		parent::__construct();
		
        array_unshift($this->BlockTypes['['], 'TableOfContents');
    }
	
    function text($text)
    {
        # make sure no definitions are set
        $this->DefinitionData = array();
				
        # standardize line breaks
        $text = str_replace(array("\r\n", "\r"), "\n", $text);

        # remove surrounding line breaks
        $text = trim($text, "\n");

        # split text into lines
        $lines = explode("\n", $text);

        # iterate through lines to identify blocks
        $markup = $this->lines($lines);

        # trim line breaks
        $markup = trim($markup, "\n");
		
        if (isset($this->DefinitionData['TableOfContents']))
        {
			$TOC = $this->buildTOC($this->headlines);
			
			$markup = preg_replace('%(<p[^>]*>\[TOC\]</p>)%i', $TOC, $markup);
        }

        # merge consecutive dl elements

        $markup = preg_replace('/<\/dl>\s+<dl>\s+/', '', $markup);

        # add footnotes
		
        if (isset($this->DefinitionData['Footnote']))
        {
            $Element = $this->buildFootnoteElement();

            $markup .= "\n" . $this->element($Element);
        }
				
        return $markup;
    }
		
    # TableOfContents

    protected function blockTableOfContents($line, $block)
    {
        if ($line['text'] == '[TOC]')
        {
			$this->DefinitionData['TableOfContents'] = true;
        }
    }

	
    #
    # Header
	
	private $headlines 			= array();
	private $headlinesCount 	= 0;
	
    protected function blockHeader($Line)
    {
        if (isset($Line['text'][1]))
        {
            $level = 1;

            while (isset($Line['text'][$level]) and $Line['text'][$level] === '#')
            {
                $level ++;
            }

            if ($level > 6)
            {
                return;
            }

            $text = trim($Line['text'], '# ');

			$this->headlinesCount++;
			
			$Block = array(
				'element' => array(
					'name' => 'h' . min(6, $level),
					'text' => $text,
					'handler' => 'line',
					'attributes' => array(
						'id' => "headline-$this->headlinesCount"
					)
				)
			);
			
			$this->headlines[]	= array('level' => $level, 'name' => $Block['element']['name'], 'attribute' => $Block['element']['attributes']['id'], 'text' => $text);

            return $Block;
        }
    }

	# build the markup for table of contents 
	
	protected function buildTOC($headlines)
	{
		
		$markup = '<ul class="TOC">';
		
		foreach($headlines as $key => $headline)
		{
			$thisLevel = $headline['level'];
			$prevLevel = $key > 0 ? $headlines[$key-1]['level'] : 1;
			$nextLevel = isset($headlines[$key+1]) ? $headlines[$key+1]['level'] : 0;
			
			if($thisLevel > $prevLevel)
			{
				$markup .= '<ul>';
			}
			
			$markup .= '<li class="' . $headline['name'] . '"><a href="#' . $headline['attribute'] . '">' . $headline['text'] . '</a>';
			
			if($thisLevel == $nextLevel)
			{
				$markup .= '</li>';
			}
			elseif($thisLevel > $nextLevel)
			{
				while($thisLevel > $nextLevel)
				{
					$markup .= '</li></ul>';
					$thisLevel--;
				}
			}			
		}
		
		$markup .= '</ul>';
		
		return $markup;
	}
}