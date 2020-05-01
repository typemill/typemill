<?php

namespace Typemill\Extensions;

use \URLify;

class ParsedownExtension extends \ParsedownExtra
{
	function __construct($showAnchor = NULL)
    {
		parent::__construct();

        # show anchor next to headline? 
        $this->showAnchor = $showAnchor;

        # math support
        $this->BlockTypes['\\'][] = 'Math';
        $this->BlockTypes['$'][] = 'Math';
        
        $this->InlineTypes['\\'][] = 'Math';
        $this->InlineTypes['$'][] = 'Math';
        $this->inlineMarkerList .= '\\';
        $this->inlineMarkerList .= '$';

        $this->visualMode = false;

		# table of content support
        array_unshift($this->BlockTypes['['], 'TableOfContents');
    }
	
    public function setVisualMode()
    {
        $this->visualMode = true;
    }

	public function text($text, $relurl = null)
	{
        $this->relurl = $relurl ? $relurl : '';

        $Elements = $this->textElements($text);
		
		return $Elements;
	}
	
	public function markup($Elements, $relurl)
	{

		# make relurl available for other functions
		$this->relurl = $relurl;
		
        # convert to markup
        $markup = $this->elements($Elements);

        # trim line breaks
        $markup = trim($markup, "\n");

        # merge consecutive dl elements
        $markup = preg_replace('/<\/dl>\s+<dl>\s+/', '', $markup);

		# create table of contents
        if(isset($this->DefinitionData['TableOfContents']))
        {
			$TOC = $this->buildTOC($this->headlines);
			
			$markup = preg_replace('%(<p[^>]*>\[TOC\]</p>)%i', $TOC, $markup);
        }
		
        # add footnotes
        if (isset($this->DefinitionData['Footnote']))
        {
            $Element = $this->buildFootnoteElement();
            
            $markup .= "\n" . $this->element($Element);
        }
		
		return $markup;
    }
    

    public function getFootnotes()
    {
        # add footnotes
        if (isset($this->DefinitionData['Footnote']))
        {
            $Element = $this->buildFootnoteElement();

            $footnotes = "\n" . $this->element($Element);
        }

        return $footnotes;
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
	
	public $headlines = array();
	
    protected function blockHeader($Line)
    {
        if (isset($Line['text'][1]))
        {
            $level = strspn($Line['text'], '#');

            if ($level > 6)
            {
                return;
            }

            $text = trim($Line['text'], '#');
			$headline = URLify::filter($Line['text']);

            if ($this->strictMode and isset($text[0]) and $text[0] !== ' ')
            {
                return;
            }

            $text = trim($text, ' ');

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

            if($this->showAnchor && $level > 1)
            {
                $Block['element']['elements'] = array(
                            array(
                                'name' => 'a',
                                'attributes' => array(
                                    'href' => $this->relurl . "#" . $headline,
                                    'class' => 'tm-heading-anchor',
                                ),
                                'text' => '#',
                            ),
                            array(
                                'text' => $text,
                            )
                        );
            }

			$this->headlines[]	= array('level' => $level, 'name' => $Block['element']['name'], 'attribute' => $Block['element']['attributes']['id'], 'text' => $text);

            return $Block;
        }
    }
	
    # build the markup for table of contents 
    
	public function buildTOC($headlines)
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
			
			$markup .= '<li class="' . $headline['name'] . '"><a href="' . $this->relurl . '#' . $headline['attribute'] . '">' . $headline['text'] . '</a>';
			
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
    # Footnote Marker
    # add absolute url

    protected function inlineFootnoteMarker($Excerpt)
    {

        $element = parent::inlineFootnoteMarker($Excerpt);

        if ( ! isset($element))
        {
            return null;
        }
   
        $href = $element['element']['element']['attributes']['href'];

        $element['element']['element']['attributes']['href'] = $this->relurl . $href;

        return $element;
    }
	
	public $footnoteCount = 0;
	
    public function buildFootnoteElement()
    {
        $Element = array(
            'name' => 'div',
            'attributes' => array('class' => 'footnotes'),
            'elements' => array(
                array('name' => 'hr'),
                array(
                    'name' => 'ol',
                    'elements' => array(),
                ),
            ),
        );

        uasort($this->DefinitionData['Footnote'], 'self::sortFootnotes');

        foreach ($this->DefinitionData['Footnote'] as $definitionId => $DefinitionData)
        {
            if ( ! isset($DefinitionData['number']))
            {
                # fix the footnote logic in visual mode, this might break for more complex footnotes.
                if($this->visualMode)
                {
                    $DefinitionData['number'] = $definitionId;
                    $DefinitionData['count'] = 1;
                }
                else
                {
                    continue;
                }
            }

            $text = $DefinitionData['text'];

            $textElements = parent::textElements($text);

            $numbers = range(1, $DefinitionData['count']);

            $backLinkElements = array();

            foreach ($numbers as $number)
            {
                $backLinkElements[] = array('text' => ' ');
                $backLinkElements[] = array(
                    'name' => 'a',
                    'attributes' => array(
                        'href' => $this->relurl . "#fnref$number:$definitionId",
                        'rev' => 'footnote',
                        'class' => 'footnote-backref',
                    ),
                    'rawHtml' => '&#8617;',
                    'allowRawHtmlInSafeMode' => true,
                    'autobreak' => false,
                );
            }

            unset($backLinkElements[0]);

            $n = count($textElements) -1;

            if ($textElements[$n]['name'] === 'p')
            {
                $backLinkElements = array_merge(
                    array(
                        array(
                            'rawHtml' => '&#160;',
                            'allowRawHtmlInSafeMode' => true,
                        ),
                    ),
                    $backLinkElements
                );

                unset($textElements[$n]['name']);

                $textElements[$n] = array(
                    'name' => 'p',
                    'elements' => array_merge(
                        array($textElements[$n]),
                        $backLinkElements
                    ),
                );
            }
            else
            {
                $textElements[] = array(
                    'name' => 'p',
                    'elements' => $backLinkElements
                );
            }

            $Element['elements'][1]['elements'] []= array(
                'name' => 'li',
                'attributes' => array('id' => 'fn:'.$definitionId),
                'elements' => array_merge(
                    $textElements
                ),
            );
        }

        return $Element;
    }

    protected function paragraph($Line)
    {
        $paragraph = array(
            'type' => 'Paragraph',
            'element' => array(
                'name' => 'p',
                'handler' => array(
                    'function' => 'lineElements',
                    'argument' => $Line['text'],
                    'destination' => 'elements',
                ),
            ),
        );

        if(isset($Line['text'][1]) && $Line['text'][0] == '!' && $Line['text'][1] == '[')
        {
            $paragraph['element']['attributes']['class'] = 'p-image';
        }
        return $paragraph;
    }

    
    # Inline Math
    # check https://github.com/BenjaminHoegh/ParsedownMath
    # check https://github.com/cben/mathdown/wiki/math-in-markdown

    protected function inlineMath($Excerpt)
    {
        if(preg_match('/^(?<!\\\\)(?<!\\\\\()\\\\\((.*?)(?<!\\\\\()\\\\\)(?!\\\\\))/s', $Excerpt['text'], $matches) OR preg_match('/\$(?!\$)([^ ][^\$\n]+)(?<! )\$(?![1-9])/s', $Excerpt['text'], $matches))
        {
            return array(
                'extent' => strlen($matches[0]),
                'element' => array(
                    'text' => '\(' . $matches[1] . '\)',
                ),
            );        
        }
    }
    
    protected $specialCharacters = array(
        '\\', '`', '*', '_', '{', '}', '[', ']', '(', ')', '<', '>', '#', '+', '-', '.', '!', '|', '~', '^', '='
    );

    //
    // Inline Escape
    // -------------------------------------------------------------------------
    protected function inlineEscapeSequence($Excerpt)
    {
        if (isset($Excerpt['text'][1]) 
            && in_array($Excerpt['text'][1], $this->specialCharacters) 
            && !preg_match('/(?<!\\\\)((?<!\\\\\()\\\\\((?!\\\\\())(.*?)(?<!\\\\)(?<!\\\\\()((?<!\\\\\))\\\\\)(?!\\\\\)))(?!\\\\\()/s', $Excerpt['text'])
            && !preg_match('/\$(?!\$)([^ ][^\$\n]+)(?<! )\$(?![1-9])/s', $Excerpt['text'])
        ){
            return array(
                'element' => array(
                    'rawHtml' => $Excerpt['text'][1],
                ),
                'extent' => 2,
            );
        }
    }

    # Block Math
    protected function blockMath($Line)
    {
        $Block = array(
            'element' => array(
                'text' => '',
            ),
        );
        if (preg_match('/^(?<!\\\\)(\\\\\[)(?!.)$/', $Line['text'])) 
        {
            $Block['end'] = '\]';
            return $Block;
        } 
        elseif (preg_match('/^(?<!\\\\)(\$\$)(?!.)$/', $Line['text']))
        {
            $Block['end'] = '$$';
            return $Block;
        }
    }
   
    // ~
    protected function blockMathContinue($Line, $Block)
    {
        if (isset($Block['complete'])) 
        {
            return;
        }
        if (isset($Block['interrupted'])) 
        {
            $Block['element']['text'] .= str_repeat("\n", $Block['interrupted']);
            unset($Block['interrupted']);
        }
        if ($Block['end'] === '\]' && preg_match('/^(?<!\\\\)(\\\\\])$/', $Line['text']))
        {
            $Block['complete'] = true;
            $Block['latex'] = true;
            $Block['element']['name'] = 'div';
            $Block['element']['text'] = "\\[".$Block['element']['text']."\n\\]";
            $Block['element']['attributes'] = array('class' => 'math');

            return $Block;
        } 
        elseif ($Block['end'] === '$$' && preg_match('/^(?<!\\\\)(\$\$)$/', $Line['text'])) 
        {
            $Block['complete'] = true;
            $Block['latex'] = true;
            $Block['element']['name'] = 'div';
            $Block['element']['text'] = "$$".$Block['element']['text']."\n$$";
            $Block['element']['attributes'] = array('class' => 'math');

            return $Block;
        }

        $Block['element']['text'] .= "\n" . $Line['body'];
        
        // ~
        return $Block;
    }

    // ~
    protected function blockMathComplete($Block)
    {
        return $Block;
    }
        
	# advanced attribute data, check parsedown extra plugin: https://github.com/tovic/parsedown-extra-plugin
    protected function parseAttributeData($text) {
        // Allow compact attributes ...
        $text = str_replace(array('#', '.'), array(' #', ' .'), $text);
        if (strpos($text, '="') !== false || strpos($text, '=\'') !== false) {
            $text = preg_replace_callback('#([-\w]+=)(["\'])([^\n]*?)\2#', function($m) {
                $s = str_replace(array(
                    ' #',
                    ' .',
                    ' '
                ), array(
                    '#',
                    '.',
                    "\x1A"
                ), $m[3]);
                return $m[1] . $m[2] . $s . $m[2];
            }, $text);
        }
        $attrs = array();
        foreach (explode(' ', $text) as $v) {
            if (!$v) continue;
            // `{#foo}`
            if ($v[0] === '#' && isset($v[1])) {
                $attrs['id'] = substr($v, 1);
            // `{.foo}`
            } else if ($v[0] === '.' && isset($v[1])) {
                $attrs['class'][] = substr($v, 1);
            // ~
            } else if (strpos($v, '=') !== false) {
                $vv = explode('=', $v, 2);
                // `{foo=}`
                if ($vv[1] === "") {
                    $attrs[$vv[0]] = "";
                // `{foo="bar baz"}`
                // `{foo='bar baz'}`
                } else if ($vv[1][0] === '"' && substr($vv[1], -1) === '"' || $vv[1][0] === "'" && substr($vv[1], -1) === "'") {
                    $attrs[$vv[0]] = str_replace("\x1A", ' ', substr(substr($vv[1], 1), 0, -1));
                // `{foo=bar}`
                } else {
                    $attrs[$vv[0]] = $vv[1];
                }
            // `{foo}`
            } else {
                $attrs[$v] = $v;
            }
        }
        if (isset($attrs['class'])) {
            $attrs['class'] = implode(' ', $attrs['class']);
        }
        return $attrs;
    }

    protected $regexAttribute = '(?:[#.][-\w:\\\]+[ ]*|[-\w:\\\]+(?:=(?:["\'][^\n]*?["\']|[^\s]+)?)?[ ]*)';

	# turn markdown into an array of markdown blocks for typemill edit mode	
	function markdownToArrayBlocks($markdown)
	{
        # standardize line breaks
        $markdown = str_replace(array("\r\n", "\r"), "\n", $markdown);

        # remove surrounding line breaks
        $markdown = trim($markdown, "\n");

		# trim to maximum two linebreaks
		
        # split text into blocks
        $blocks = explode("\n\n", $markdown);
		
		# clean up code blocks
		$cleanBlocks = array();
		
		# holds the content of codeblocks
		$codeBlock = '';
		
		# flag if codeblock is on or off.
		$codeBlockOn = false;
        
        # holds the content of a definition list
        $definitionList = "";

        # flag if definition-list is on or off.
        $definitionListOn = false;

		foreach($blocks as $block)
		{
			# remove empty lines
			if (chop($block) === '') continue;
			
			# if the block starts with a fenced code
			if(substr($block,0,2) == '``')
			{
				# and if we are in an open code-block
				if($codeBlockOn)
				{
					# it must be the end of the codeblock, so add it to the codeblock
					$block = $codeBlock . "\n" . $block;
					
					# reset codeblock-value and close the codeblock.
					$codeBlock = '';
					$codeBlockOn = false;
				}
				else
				{
					# it must be the start of the codeblock.
					$codeBlockOn = true;
				}
			}
			if($codeBlockOn)
			{
				# if the codeblock is complete
				if($this->isComplete($block))
				{
					$block = $codeBlock . "\n" . $block;

					# reset codeblock-value and close the codeblock.
					$codeBlock = '';
					$codeBlockOn = false;
				}
				else
				{
					$codeBlock .= "\n" . $block;
					continue;
				}
            }
            
            # handle definition lists
            $checkDL = preg_split('/\r\n|\r|\n/',$block);
            if(isset($checkDL[1]) && substr($checkDL[1],0,2) == ': ')
            {
                $definitionList .= $block . "\n\n";
                $definitionListOn = true;
                continue; 
            }
            elseif($definitionListOn)
            {
                $cleanBlocks[] = $definitionList;
                $definitionList = "";
                $definitionListOn = false;
            }
            
			$block = trim($block, "\n");
						
			$cleanBlocks[] = $block;
		}
		return $cleanBlocks;
	}
	
	public function arrayBlocksToMarkdown(array $arrayBlocks)
	{	
		$markdown = '';
		
		foreach($arrayBlocks as $block)
		{
			$markdown .=  $block . "\n\n";
		}
		
		return $markdown;
	}	
	
	protected function isComplete($codeblock)
	{
		$lines = explode("\n", $codeblock);
		if(count($lines) > 1)
		{
			$lastLine = array_pop($lines);
			if(substr($lastLine,0,2) == '``')
			{
				return true;
			}
			return false;
		}
		return false;
	}
}