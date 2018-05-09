<?php

namespace Typemill\Extensions;

use \URLify;

class ParsedownExtension extends \ParsedownExtra
{
	function __construct()
    {
		parent::__construct();

		# mathjax support
        $this->InlineTypes['`'][] = 'MathJaxLaTeX';
        $this->BlockTypes['`'][] = 'FencedMathJaxLaTeX';
		
		# table of content support
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

        # iterate through lines to identify blocks and return array of content
        $blocks = $this->getContentArray($lines);
		
		return $blocks;
	}
	
	function markup($blocks)
	{
		# iterate through array of content and get markup
		$markup = $this->getMarkup($blocks);
		
        # trim line breaks
        $markup = trim($markup, "\n");

        if(isset($this->DefinitionData['TableOfContents']))
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

    # Header
	
	private $headlines 			= array();
	
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

			$headline = URLify::filter($Line['text']);
						
			$Block = array(
				'element' => array(
					'name' => 'h' . min(6, $level),
					'text' => $text,
					'handler' => 'line',
					'attributes' => array(
						'id' => "$headline"
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
	
	
    #
    # Blocks
    #

    protected function getContentArray(array $lines)
    {
        $CurrentBlock = null;

        foreach ($lines as $line)
        {
            if (chop($line) === '')
            {
                if (isset($CurrentBlock))
                {
                    $CurrentBlock['interrupted'] = true;
                }

                continue;
            }

            if (strpos($line, "\t") !== false)
            {
                $parts = explode("\t", $line);

                $line = $parts[0];

                unset($parts[0]);

                foreach ($parts as $part)
                {
                    $shortage = 4 - mb_strlen($line, 'utf-8') % 4;

                    $line .= str_repeat(' ', $shortage);
                    $line .= $part;
                }
            }

            $indent = 0;

            while (isset($line[$indent]) and $line[$indent] === ' ')
            {
                $indent ++;
            }

            $text = $indent > 0 ? substr($line, $indent) : $line;

            # ~

            $Line = array('body' => $line, 'indent' => $indent, 'text' => $text);

            # ~

            if (isset($CurrentBlock['continuable']))
            {
                $Block = $this->{'block'.$CurrentBlock['type'].'Continue'}($Line, $CurrentBlock);

                if (isset($Block))
                {
                    $CurrentBlock = $Block;

                    continue;
                }
                else
                {
                    if ($this->isBlockCompletable($CurrentBlock['type']))
                    {
                        $CurrentBlock = $this->{'block'.$CurrentBlock['type'].'Complete'}($CurrentBlock);
                    }
                }
            }

            # ~

            $marker = $text[0];

            # ~

            $blockTypes = $this->unmarkedBlockTypes;

            if (isset($this->BlockTypes[$marker]))
            {
                foreach ($this->BlockTypes[$marker] as $blockType)
                {
                    $blockTypes []= $blockType;
                }
            }

            #
            # ~

            foreach ($blockTypes as $blockType)
            {
                $Block = $this->{'block'.$blockType}($Line, $CurrentBlock);

                if (isset($Block))
                {
                    $Block['type'] = $blockType;

                    if ( ! isset($Block['identified']))
                    {
                        $Blocks []= $CurrentBlock;

                        $Block['identified'] = true;
                    }

                    if ($this->isBlockContinuable($blockType))
                    {
                        $Block['continuable'] = true;
                    }

                    $CurrentBlock = $Block;

                    continue 2;
                }
            }

            # ~

            if (isset($CurrentBlock) and ! isset($CurrentBlock['type']) and ! isset($CurrentBlock['interrupted']))
            {
                $CurrentBlock['element']['text'] .= "\n".$text;
            }
            else
            {
                $Blocks []= $CurrentBlock;

                $CurrentBlock = $this->paragraph($Line);

                $CurrentBlock['identified'] = true;
            }
        }

        # ~

        if (isset($CurrentBlock['continuable']) and $this->isBlockCompletable($CurrentBlock['type']))
        {
            $CurrentBlock = $this->{'block'.$CurrentBlock['type'].'Complete'}($CurrentBlock);
        }

        # ~

        $Blocks []= $CurrentBlock;

        unset($Blocks[0]);

        # ~
		return $Blocks;
	}
	
	public function getMarkup($Blocks)
	{
        $markup = '';

        foreach ($Blocks as $Block)
        {
            if (isset($Block['hidden']))
            {
                continue;
            }

            $markup .= "\n";
            $markup .= isset($Block['markup']) ? $Block['markup'] : $this->element($Block['element']);
        }

        $markup .= "\n";

        # ~

        return $markup;
    }
	
	# math support. Check https://github.com/aidantwoods/parsedown/blob/mathjaxlatex/ParsedownExtensionMathJaxLaTeX.php


    protected function inlineCode($Excerpt)
    {
        $marker = $Excerpt['text'][0];
        if (preg_match('/^('.$marker.')[ ]*(.+?)[ ]*(?<!'.$marker.')\1(?!'.$marker.')/s', $Excerpt['text'], $matches))
        {
            $text = $matches[2];
            $text = preg_replace("/[ ]*\n/", ' ', $text);
            return array(
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'code',
                    'text' => $text,
                ),
            );
        }
    }	
	
    protected function inlineMathJaxLaTeX($Excerpt)
    {
        $marker = $Excerpt['text'][0];
        if (preg_match('/^('.$marker.'{2,})[ ]*(.+?)[ ]*(?<!'.$marker.')\1(?!'.$marker.')/s', $Excerpt['text'], $matches))
        {
            $text = $matches[2];
            $text = htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');
            $text = preg_replace("/[ ]*\n/", ' ', $text);
            return array(
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'span',
                    'text' => '\('.$text.'\)',
                ),
            );
        }
    }
	
    #
    # Fenced Code
    protected function blockFencedCode($Line)
    {
        if (preg_match('/^(['.$Line['text'][0].']{3,})[ ]*([\w-]+)?[ ]*$/', $Line['text'], $matches))
        {
            $Element = array(
                'name' => 'code',
                'text' => '',
            );
			
            if (isset($matches[2]))
            {
                if (strtolower($matches[2]) === 'latex')
                {
                    return;
                }
                $class = 'language-'.$matches[2];
                $Element['attributes'] = array(
                    'class' => $class,
                );
            }
            $Block = array(
                'char' => $Line['text'][0],
                'openerLength' => mb_strlen($matches[1]),
                'element' => array(
                    'name' => 'pre',
                    'element' => $Element,
                ),
            );
			
            return $Block;
        }
    }
	
    #
    # Fenced MathJax
    protected function blockFencedMathJaxLaTeX($Line)
    {
        if (preg_match('/^['.$Line['text'][0].']{3,}[ ]*([\w-]+)?[ ]*$/', $Line['text'], $matches))
        {
            if ( ! isset($matches[1]) or strtolower($matches[1]) !== 'latex')
            {
                return;
            }
            $Block = array(
                'char' => $Line['text'][0],
                'element' => array(
                    'name' => 'span',
                    'text' => '',
                ),
            );
            return $Block;
        }
    }
	
    protected function blockFencedMathJaxLaTeXContinue($Line, $Block)
    {
        if (isset($Block['complete']))
        {
            return;
        }
        if (isset($Block['interrupted']))
        {
            $Block['element']['text'] .= "\n";
            unset($Block['interrupted']);
        }
        if (preg_match('/^'.$Block['char'].'{3,}[ ]*$/', $Line['text']))
        {
            $Block['element']['text'] = substr($Block['element']['text'], 1);
            $Block['complete'] = true;
            return $Block;
        }
        $Block['element']['text'] .= "\n".$Line['body'];;
        return $Block;
    }
	
    protected function blockFencedMathJaxLaTeXComplete($Block)
    {
        $text = $Block['element']['text'];
        $Block['element']['text'] = "\$\$\n" . $text . "\n\$\$";
        return $Block;
    }	
}