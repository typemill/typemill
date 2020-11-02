<?php

namespace Typemill\Extensions;

use \URLify;

class ParsedownExtension extends \ParsedownExtra
{
    function __construct($baseUrl = '', $showAnchor = NULL)
    {
        parent::__construct();

        # show anchor next to headline? 
        $this->showAnchor = $showAnchor;

        # base url is needed for media/images and relative links (e.g. if www.mydomain.com/mywebsite)
        $this->baseUrl = $baseUrl;

        # math support
        $this->BlockTypes['\\'][] = 'Math';
        $this->BlockTypes['$'][] = 'Math';
        
        $this->InlineTypes['\\'][] = 'Math';
        $this->InlineTypes['$'][] = 'Math';
        $this->inlineMarkerList .= '\\';
        $this->inlineMarkerList .= '$';

        $this->BlockTypes['!'][] = 'Image';
        $this->BlockTypes['!'][] = "Notice";        

        $this->visualMode = false;

        # identify Table Of contents after footnotes and links      
        array_unshift($this->BlockTypes['['], 'TableOfContents');
    }
    
    public function setVisualMode()
    {
        $this->visualMode = true;
    }

    public function text($text, $relurl = null)
    {
 #       $this->relurl = $relurl ? $relurl : '';

        $Elements = $this->textElements($text);
        
        return $Elements;
    }
    
    public function markup($Elements)
    {

        # make relurl available for other functions
#        $this->relurl = $relurl;
        
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

    # BlockImages with html5 figure and figcaption
    # No, this is not the most elegant code on planet earth!!
    protected function blockImage($line, $block)
    {
        if (preg_match('/^\!\[/', $line['text'], $matches))
        {

            $Block = array(
                'element' => array(
                    'name' => 'figure',
                    'elements' => array(
                    )
                ),
            );

            $Elements = array(
                'handler' => array(
                    'function' => 'lineElements',
                    'argument' => $line['text'],
                    'destination' => 'elements',
                )
            );

            if (preg_match('/[ ]*{('.$this->regexAttribute.'+)}/', $line['text'], $matches, PREG_OFFSET_CAPTURE))
            {
                $attributeString = $matches[1][0];
                $dataAttributes = $this->parseAttributeData($attributeString);

                # move classes and ids from img to the figure element
                $figureAttributes = array();
                if(isset($dataAttributes['class']))
                {
                    $figureAttributes['class'] = $dataAttributes['class'];
                    $classes = explode(' ', $dataAttributes['class']);
                    foreach($classes as $class)
                    {
                        $attributeString = str_replace('.'.$class, '', $attributeString);
                    }
                }
                if(isset($dataAttributes['id']))
                {
                    $figureAttributes['id'] = $dataAttributes['id'];
                    $attributeString = str_replace('#'.$dataAttributes['id'], '', $attributeString);
                }

                $attributeString = trim(str_replace('  ', ' ', $attributeString));
                $line['text'] = substr($line['text'], 0, $matches[0][1]);
                if(str_replace(' ', '', $attributeString) != '')
                {
                    $line['text'] .= '{' . $attributeString . '}';
                }

                $Block['element']['attributes'] = $figureAttributes;

                $Elements['handler']['argument'] = $line['text'];
            }

            $Block['element']['elements'][] = $Elements;
  
            return $Block;
        }
    }

    protected function blockImageContinue($line, $block)
    {
        if (isset($block['complete']))
        {
            return;
        }

        # A blank newline has occurred, so it is a new content-block and not a caption
        if (isset($block['interrupted']))
        {
            return;
        }

        $block['element']['elements'][] = array(
                'name' => 'figcaption',
                'handler' => array(
                    'function' => 'lineElements',
                    'argument' => $line['text'],
                    'destination' => 'elements',
                )
        );
        
        $block['complete'] = true;

        return $block;
    }

    protected function blockImageComplete($block)
    {
        return $block;
    }

    protected function blockTable($Line, array $Block = null)
    {

        $Block = parent::blockTable($Line, $Block);

        if($Block)
        {
            $table = $Block['element'];

            $Block['element'] = [
                    'name' => 'div',
                    'element' => $table,
                    'attributes' => [
                        'class' => "tm-table",
                    ],
            ];
        }
        
        return $Block;
    }    

    protected function blockTableContinue($Line, array $Block)
    {
        if (isset($Block['interrupted']))
        {
            return;
        }

        if (count($Block['alignments']) === 1 or $Line['text'][0] === '|' or strpos($Line['text'], '|'))
        {
            $Elements = array();

            $row = $Line['text'];

            $row = trim($row);
            $row = trim($row, '|');

            preg_match_all('/(?:(\\\\[|])|[^|`]|`[^`]++`|`)++/', $row, $matches);

            $cells = array_slice($matches[0], 0, count($Block['alignments']));

            foreach ($cells as $index => $cell)
            {
                $cell = trim($cell);

                $Element = array(
                    'name' => 'td',
                    'handler' => array(
                        'function' => 'lineElements',
                        'argument' => $cell,
                        'destination' => 'elements',
                    )
                );

                if (isset($Block['alignments'][$index]))
                {
                    $Element['attributes'] = array(
                        'style' => 'text-align: ' . $Block['alignments'][$index] . ';',
                    );
                }

                $Elements []= $Element;
            }

            $Element = array(
                'name' => 'tr',
                'elements' => $Elements,
            );

            $Block['element']['element']['elements'][1]['elements'] []= $Element;

            return $Block;
        }
    }

    # Handle notice blocks
    # adopted from grav: https://github.com/getgrav/grav-plugin-markdown-notices/blob/develop/markdown-notices.php
    # and yellow / datenstrom: https://raw.githubusercontent.com/datenstrom/yellow-extensions/master/features/markdown/markdownx.php
    protected function blockNotice($Line, $Block) 
    {
        if (preg_match("/^!(?!\[)[ ]?+(.*+)/", $Line["text"], $matches)) 
        {
            $level  = strspn(str_replace(array("![", " "), "", $Line["text"]), "!");
            $text   = substr($matches[0], $level);

            $Block = [
                'element' => [
                    'name' => 'div',
                    'handler' => array(
                        'function' => 'linesElements',
                        'argument' => (array) $text,
                        'destination' => 'elements',
                    ),
                    'attributes' => [
                        'class' => "notice$level",
                    ],
                ],
            ];

            return $Block;
        }
    }

    # Handle notice blocks over multiple lines
    # adopted from grav: https://github.com/getgrav/grav-plugin-markdown-notices/blob/develop/markdown-notices.php
    # and yellow / datenstrom: https://raw.githubusercontent.com/datenstrom/yellow-extensions/master/features/markdown/markdownx.php
    protected function blockNoticeContinue($Line, $Block) 
    {
        if (isset($Block['interrupted']))
        {
            return;
        }

        if (preg_match("/^!(?!\[)[ ]?+(.*+)/", $Line["text"], $matches) )
        {
            $level  = strspn(str_replace(array("![", " "), "", $Line["text"]), "!");
            $text   = substr($matches[0], $level);

            $Block['element']['handler']['argument'][] = $text;
            return $Block;
        }
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
                        'id' => "h-$headline"
                    )
                )
            );

            if($this->showAnchor && $level > 1)
            {
                $Block['element']['elements'] = array(
                            array(
                                'name' => 'a',
                                'attributes' => array(
#                                    'href' => $this->relurl . "#" . $headline,
                                    'href'     =>  "#h-" . $headline,
                                    'class'    => 'tm-heading-anchor',
                                ),
                                'text' => '#',
                            ),
                            array(
                                'text' => $text,
                            )
                        );
            }

            $this->headlines[]  = array('level' => $level, 'name' => $Block['element']['name'], 'attribute' => $Block['element']['attributes']['id'], 'text' => $text);

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
            
#            $markup .= '<li class="' . $headline['name'] . '"><a href="' . $this->relurl . '#' . $headline['attribute'] . '">' . $headline['text'] . '</a>';
            $markup .= '<li class="' . $headline['name'] . '"><a href="#' . $headline['attribute'] . '">' . $headline['text'] . '</a>';
            
            if($thisLevel == $nextLevel )
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

                if($thisLevel > 0)
                {
                    $markup .= '</li>';
                }
            }
        }
        
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

#        $element['element']['element']['attributes']['href'] = $this->relurl . $href;
        $element['element']['element']['attributes']['href'] = $href;

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
#                        'href' => $this->relurl . "#fnref$number:$definitionId",
                        'href'  => "#fnref$number:$definitionId",
                        'rev'   => 'footnote',
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



    public function markdownToArrayBlocks($markdown)
    {
        # make sure no definitions are set
        $this->DefinitionData = array();

        # standardize line breaks
        $text = str_replace(array("\r\n", "\r"), "\n", $markdown);

        # remove surrounding line breaks
        $text = trim($text, "\n");

        # split text into lines
        $lines = explode("\n", $text);

        # manipulated method linesElements

        # $Elements = array();

        # the block that's being built
        # when a block is complete we add it to $Elements
        $CurrentBlock = null;

        # ++
        $strings = array();
        $currentString = null;

        foreach ($lines as $line) {

            # is it a blank line
            if (chop($line) === '') {
                # mark current block as interrupted
                if (isset($CurrentBlock)) {
                    # set or increment interrupted
                    $CurrentBlock['interrupted'] = (isset($CurrentBlock['interrupted'])
                        ? $CurrentBlock['interrupted'] + 1 : 1
                    );
                }

                continue;
            }

            # ~

            # figure out line indent and text

            while (($beforeTab = strstr($line, "\t", true)) !== false) {
                $shortage = 4 - mb_strlen($beforeTab, 'utf-8') % 4;

                $line = $beforeTab
                    . str_repeat(' ', $shortage)
                    . substr($line, strlen($beforeTab) + 1);
            }

            $indent = strspn($line, ' ');

            $text = $indent > 0 ? substr($line, $indent) : $line;

            # ~

            $Line = array('body' => $line, 'indent' => $indent, 'text' => $text);

            # ~

            if (isset($CurrentBlock['continuable'])) {
                # current block is continuable
                # let's attempt to continue it
                $methodName = 'block' . $CurrentBlock['type'] . 'Continue';
                $Block = $this->$methodName($Line, $CurrentBlock);

                if (isset($Block)) {
                    # attempt to continue it was successful
                    # let's update it
                    $CurrentBlock = $Block;

                    # ++
                    $currentString .= "\n$line";

                    continue;
                } else {
                    # current block is continuable but we were unable to continue it
                    # this means that it's complete
                    # let's call it's "complete" method if it has one
                    if ($this->isBlockCompletable($CurrentBlock['type'])) {
                        $methodName = 'block' . $CurrentBlock['type'] . 'Complete';
                        $CurrentBlock = $this->$methodName($CurrentBlock);
                    }
                }
            }

            # ~

            $marker = $text[0];

            # ~

            # make a list of the block types that current line can start
            $blockTypes = $this->unmarkedBlockTypes;
            if (isset($this->BlockTypes[$marker])) {
                foreach ($this->BlockTypes[$marker] as $blockType) {
                    $blockTypes [] = $blockType;
                }
            }

            #
            # ~

            foreach ($blockTypes as $blockType) {
                # let's see if current line can start a block of type $blockType
                $Block = $this->{"block$blockType"}($Line, $CurrentBlock);

                if (isset($Block)) {
                    # echo "[$blockType]";
                    # current line managed to start a block of type $blockType
                    # let's set its type
                    $Block['type'] = $blockType;

                    # on start block, we "ship" current block and flag started block as identified
                    # except when the started block has already flagged itself as identified
                    # this is the case of table
                    # blocks flag themselves as identified to "absorb" current block
                    # setext function doesn't set "identified" but it inherits it from the $Block param
                    if (!isset($Block['identified'])) {
                        # if (isset($CurrentBlock)) {
                            # $Elements[] = $this->extractElement($CurrentBlock);
                        # }

                        # ++
                        # currentString would be null if this is the first block
                        if ($currentString !== null) {
                            $strings[] = $currentString;
                        }

                        # ++
                        # line doesn't belong to currentString
                        # we've shipped
                        $currentString = $line;

                        $Block['identified'] = true;
                    } else {
                        # ++
                        $currentString .= "\n$line";
                    }

                    # does block have a "continue" method
                    if ($this->isBlockContinuable($blockType)) {
                        $Block['continuable'] = true;
                    }

                    $CurrentBlock = $Block;

                    # we're done with this line
                    # move on to next line
                    continue 2;
                }
            }

            # ~

            if (isset($CurrentBlock) and $CurrentBlock['type'] === 'Paragraph') {
                # we continue paragraphs here because they are "lazy"
                # they "eat" the line only if no other block type has "eaten" it
                $Block = $this->paragraphContinue($Line, $CurrentBlock);
            }

            if (isset($Block)) {
                $CurrentBlock = $Block;

                # ++
                $currentString .= "\n$line";
            } else {
                # is this "isset" might be here to handle $lines[0] (first line)
                # version 1.7.x doesn't have it but it does unset($Blocks[0])
                if (isset($CurrentBlock)) {
                    # $Elements[] = $this->extractElement($CurrentBlock);

                    # ++
                    $strings[] = $currentString;
                }

                $CurrentBlock = $this->paragraph($Line);

                # ++
                $currentString = $line;

                $CurrentBlock['identified'] = true;
            }
            if($blockType == "DefinitionList")
            {
                die('def');
                echo 'currentLine';
                echo $currentString;  
            }

        }
        die();
        # ~

        # at this point, we're out of the $lines loop

        # handles the case where the last block is continuable
        # since there are no more lines, it won't get completed in the loop
        # we need to complete it here
        if (isset($CurrentBlock['continuable']) and $this->isBlockCompletable($CurrentBlock['type'])) {
            $methodName = 'block' . $CurrentBlock['type'] . 'Complete';
            $CurrentBlock = $this->$methodName($CurrentBlock);
        }

        # ~

        if (isset($CurrentBlock)) {
            # $Elements[] = $this->extractElement($CurrentBlock);

            # ++
            $strings[] = $currentString;
        }

        # ~

        # return $Elements;

        ## ++
        echo '<pre>';
        print_r($strings);
        return $strings;
    }






    # manipulated method linesElements, returns array of markdown blocks
    public function markdownToArrayBlocksNew($markdown)
    {

        # make sure no definitions are set
        $this->DefinitionData = array();

        # standardize line breaks
        $text = str_replace(array("\r\n", "\r"), "\n", $markdown);

        # remove surrounding line breaks
        $text = trim($text, "\n");

        # split text into lines
        $lines = explode("\n", $text);

        # manipulated method linesElements

        $Elements = array();
        $CurrentBlock = null;
/*new*/ $mdElements = array();

        foreach ($lines as $line)
        {
            if (chop($line) === '')
            {
                if (isset($CurrentBlock))
                {
                    $CurrentBlock['interrupted'] = (isset($CurrentBlock['interrupted'])
                        ? $CurrentBlock['interrupted'] + 1 : 1
                    );
                }

                continue;
            }

            while (($beforeTab = strstr($line, "\t", true)) !== false)
            {
                $shortage = 4 - mb_strlen($beforeTab, 'utf-8') % 4;

                $line = $beforeTab
                    . str_repeat(' ', $shortage)
                    . substr($line, strlen($beforeTab) + 1)
                ;
            }

            $indent = strspn($line, ' ');

            $text = $indent > 0 ? substr($line, $indent) : $line;

            # ~

            $Line = array('body' => $line, 'indent' => $indent, 'text' => $text);

            # ~

            # if we are in a multiline block element
            if (isset($CurrentBlock['continuable']))
            {
                $methodName = 'block' . $CurrentBlock['type'] . 'Continue';

/* fix definition list */
                if($CurrentBlock['type'] == 'DefinitionList' && isset($CurrentBlock['interrupted']))
                {
                    $mdCurrentBlock = $mdCurrentBlock . "\n\n";
                }

                $Block = $this->$methodName($Line, $CurrentBlock);

                # if this line still belongs to the current multiline block
                if (isset($Block))
                {
                    $CurrentBlock = $Block;
/*new*/             $mdCurrentBlock = $mdCurrentBlock . "\n" . $line;
                    continue;
                }
                # if this line does not belong to the current multiline block
                else
                {
                    # if multiline block element is finished
                    if ($this->isBlockCompletable($CurrentBlock['type']))
                    {
                        $methodName = 'block' . $CurrentBlock['type'] . 'Complete';
                        $CurrentBlock = $this->$methodName($CurrentBlock);
/*new*/                 $mdCurrentBlock = $mdCurrentBlock;
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
                $Block = $this->{"block$blockType"}($Line, $CurrentBlock);

/* new */       $mdBlock = $line;

/* dirty fix for tables and definition lists, not sure why this happens */     
                if ( ($blockType == "Table" OR $blockType == "DefinitionList") && isset($mdCurrentBlock) ) 
                {
                   $mdBlock = $mdCurrentBlock . "\n" . $line;
                }
/* fix end */

                if (isset($Block))
                {
                    $Block['type'] = $blockType;
                    if ( ! isset($Block['identified']))
                    {
                        if (isset($CurrentBlock))
                        {
                            $Elements[] = $this->extractElement($CurrentBlock);
/*new*/                     $mdElements[] = $mdCurrentBlock;
                        }

                        $Block['identified'] = true;
                    }

                    if ($this->isBlockContinuable($blockType))
                    {
                        $Block['continuable'] = true;
                    }

                    $CurrentBlock = $Block;
/*new*/             $mdCurrentBlock = $mdBlock;
 
                    continue 2;
                }
            }

            # ~

            if (isset($CurrentBlock) and $CurrentBlock['type'] === 'Paragraph')
            {
                $Block = $this->paragraphContinue($Line, $CurrentBlock);
/*new*/         $mdBlock = $mdCurrentBlock . "\n" . $line;
            }

            if (isset($Block))
            {
                $CurrentBlock = $Block;
/*new*/         $mdCurrentBlock = $mdBlock;
            }
            else
            {
                if (isset($CurrentBlock))
                {
                    $Elements[] = $this->extractElement($CurrentBlock);
/*new*/             $mdElements[] = $mdCurrentBlock;
                }

                $CurrentBlock = $this->paragraph($Line);
/*new*/         $mdCurrentBlock = $line;
                $CurrentBlock['identified'] = true;
            }

        }

        # ~

        if (isset($CurrentBlock['continuable']) and $this->isBlockCompletable($CurrentBlock['type']))
        {
            $methodName = 'block' . $CurrentBlock['type'] . 'Complete';
            $CurrentBlock = $this->$methodName($CurrentBlock);
/*new*/     $mdCurrentBlock = $mdCurrentBlock;
        }

        # ~

        if (isset($CurrentBlock))
        {            
            $Elements[] = $this->extractElement($CurrentBlock);
/*new*/     $mdElements[] = $mdCurrentBlock;
        }

        # ~
/*        echo '<pre>';
        print_r($mdElements);
        die();
/*new*/ return $mdElements;
#       return $Elements;
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


    protected function inlineLink($Excerpt)
    {
        $Element = array(
            'name' => 'a',
            'handler' => array(
                'function' => 'lineElements',
                'argument' => null,
                'destination' => 'elements',
            ),
            'nonNestables' => array('Url', 'Link'),
            'attributes' => array(
                'href' => null,
                'title' => null,
            ),
        );

        $extent = 0;

        $remainder = $Excerpt['text'];

        if (preg_match('/\[((?:[^][]++|(?R))*+)\]/', $remainder, $matches))
        {
            $Element['handler']['argument'] = $matches[1];

            $extent += strlen($matches[0]);

            $remainder = substr($remainder, $extent);
        }
        else
        {
            return;
        }

        if (preg_match('/^[(]\s*+((?:[^ ()]++|[(][^ )]+[)])++)(?:[ ]+("[^"]*+"|\'[^\']*+\'))?\s*+[)]/', $remainder, $matches))
        {
            # start typemill: if relative link or media-link
            $href = $matches[1];
            if($href[0] == '/')
            {
                $href = $this->baseUrl . $href;
            }
            elseif(substr( $href, 0, 6 ) === "media/")
            {
                $href = $this->baseUrl . '/' . $href;
            }
            # end typemill
            
            $Element['attributes']['href'] = $href;

            if (isset($matches[2]))
            {
                $Element['attributes']['title'] = substr($matches[2], 1, - 1);
            }

            $extent += strlen($matches[0]);
        }
        else
        {
            if (preg_match('/^\s*\[(.*?)\]/', $remainder, $matches))
            {
                $definition = strlen($matches[1]) ? $matches[1] : $Element['handler']['argument'];
                $definition = strtolower($definition);

                $extent += strlen($matches[0]);
            }
            else
            {
                $definition = strtolower($Element['handler']['argument']);
            }

            if ( ! isset($this->DefinitionData['Reference'][$definition]))
            {
                return;
            }

            $Definition = $this->DefinitionData['Reference'][$definition];

            $Element['attributes']['href'] = $Definition['url'];
            $Element['attributes']['title'] = $Definition['title'];
        }

        $Link = array(
            'extent' => $extent,
            'element' => $Element,
        );

        # Parsedown Extra
        $remainder = $Link !== null ? substr($Excerpt['text'], $Link['extent']) : '';

        if (preg_match('/^[ ]*{('.$this->regexAttribute.'+)}/', $remainder, $matches))
        {
            $Link['element']['attributes'] += $this->parseAttributeData($matches[1]);

            $Link['extent'] += strlen($matches[0]);
        }

        return $Link;

    }

}