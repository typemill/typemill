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
      new \Twig_SimpleFilter('__', [$this,'translate'] ),
      new \Twig_SimpleFilter('ta', [$this,'translate_array'] )
    ];
  }

	public function getFunctions()
	{
		return [
			new \Twig_SimpleFunction('__', array($this, 'translate' )),
      new \Twig_SimpleFunction('ta', [$this,'translate_array'] )
		];
	}


  public function translate_array( $label )
  {
    /* In reality the function does not Translate an Array but a string, temporarily transformed into an array.
     * I saw a filter/function with this name in Grav.
     * Example:
      
       $label -> placeholder="Add Label for Start-Button" value="Start"
      
       after explode:
       {
        [0]=> string(13) " placeholder="
        [1]=> string(26) "Add Label for Start-Button"
        [2]=> string(7) " value="
        [3]=> string(5) "Start"
        [4]=> string(0) ""
      }
      
     */
    $translated_label = '';
    $items = explode('"',$label);
    foreach($items as $item){
      // skip empty string
      if(!empty($item)){
        $pos = strpos($item, '=');
        //skip string containing equal sign
        if ($pos === false) {
          // translate with previous function in this class
          $translated = $this->translate($item);
          // add the translated string
          $translated_label .= '"'.$translated.'"';
        } else {
          // adds the string containing the equal sign
          $translated_label .= $item;
        }
      }
    }
    return $translated_label;
  }

  
  public function translate( $label )
	{
    // replaces spaces, dots, comma and dash with underscores 
    $string = str_replace(" ", "_", $label);
    $string = str_replace(".", "_", $string);
    $string = str_replace(",", "_", $string);
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
