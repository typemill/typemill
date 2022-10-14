<?php

namespace Typemill\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Typemill\Models\WriteYaml;

class TwigLanguageExtension extends AbstractExtension
{
	protected $labels;
	
	public function __construct($labels)
	{
		$this->labels = $labels;
	}
	
	public function getFilters()
	{
		return [
			new TwigFilter('translate', [$this, 'translate'] ),
		];
	}

	public function getFunctions()
	{
		return [
			new TwigFunction('translate', array($this, 'translate' ))
		];
	}

	public function translate( $label, $labels_from_plugin = NULL )
	{
		# replaces spaces, dots, comma and dash with underscores 
		$string = str_replace(" ", "_", $label);
		$string = str_replace(".", "_", $string);
		$string = str_replace(",", "_", $string);
		$string = str_replace("-", "_", $string);

		# transforms to uppercase
		$string = strtoupper( $string );

		# translates the string
		if(isset($labels_from_plugin))
		{
			$translated_label = isset($labels_from_plugin[$string]) ? $labels_from_plugin[$string] : null;
		}
		else
		{
			$translated_label = isset($this->labels[$string]) ? $this->labels[$string] : null;
		}

		# if the string is not present, set the original string
		if( empty($translated_label) )
		{
			$translated_label = $label;
		}

		# returns the string in the set language
		return $translated_label;
	}
}
