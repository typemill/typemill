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
		
	public function text($text)
	{
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
			
    # TableOfContents

    protected function blockTableOfContents($line, $block)
    {
        if ($line['text'] == '[TOC]')
        {
			$this->DefinitionData['TableOfContents'] = true;
        }
    }

    # Header
	
	private $headlines = array();
	
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

    protected function inlineFootnoteMarker($Excerpt)
    {
        if (preg_match('/^\[\^(.+?)\]/', $Excerpt['text'], $matches))
        {
            $name = $matches[1];

            if ( ! isset($this->DefinitionData['Footnote'][$name]))
            {
                return;
            }

            $this->DefinitionData['Footnote'][$name]['count'] ++;

            if ( ! isset($this->DefinitionData['Footnote'][$name]['number']))
            {
                $this->DefinitionData['Footnote'][$name]['number'] = ++ $this->footnoteCount; # » &
            }

            $Element = array(
                'name' => 'sup',
                'attributes' => array('id' => 'fnref'.$this->DefinitionData['Footnote'][$name]['count'].':'.$name),
                'element' => array(
                    'name' => 'a',
                    'attributes' => array('href' => $this->relurl . '#fn:' . $name, 'class' => 'footnote-ref'),
                    'text' => $this->DefinitionData['Footnote'][$name]['number'],
                ),
            );

            return array(
                'extent' => strlen($matches[0]),
                'element' => $Element,
            );
        }
    }
	
	public $footnoteCount = 0;
	
    protected function buildFootnoteElement()
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
                continue;
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
		
		foreach($blocks as $block)
		{
			// remove empty lines
			if (chop($block) === '') continue;
			
			// if the block starts with a fenced code
			if(substr($block,0,2) == '``')
			{
				// and if we are in an open code-block
				if($codeBlockOn)
				{
					// it must be the end of the codeblock, so add it to the codeblock
					$block = $codeBlock . "\n" . $block;
					
					// reset codeblock-value and close the codeblock.
					$codeBlock = '';
					$codeBlockOn = false;
				}
				else
				{
					// it must be the start of the codeblock.
					$codeBlockOn = true;
				}
			}
			if($codeBlockOn)
			{
				// if the codeblock is complete
				if($this->isComplete($block))
				{
					$block = $codeBlock . "\n" . $block;

					// reset codeblock-value and close the codeblock.
					$codeBlock = '';
					$codeBlockOn = false;
				}
				else
				{
					$codeBlock .= "\n" . $block;
					continue;
				}
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