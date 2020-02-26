<?php

namespace Typemill\Extensions;

use Typemill\Models\WriteYaml;

class TwigLanguageExtension extends \Twig_Extension
{
  protected $labels;
  
  public function __construct($labels)
	{
		$this->labels = $labels;
	}
  
  public function getFilters()
  {
    return [
      new \Twig_SimpleFilter('__', [$this,'translate'] )
    ];
  }

	public function getFunctions()
	{
		return [
			new \Twig_SimpleFunction('__', array($this, 'translate' ))
		];
	}

  public function translate( $label )
	{
    // replaces spaces, dots and dash with underscores 
    $string = str_replace(" ", "_", $label);
    $string = str_replace(".", "_", $string);
    $string = str_replace("-", "_", $string);

    // transforms to uppercase
    $string = strtoupper( $string );

    //translates the string
    $translated_label = isset($this->labels[$string]) ? $this->labels[$string] : null;

    // if the string is not present, set the original string
    if( empty($translated_label) ){
      $translated_label = $label;
    }

    // returns the string in the set language
    return $translated_label;

	}

}
