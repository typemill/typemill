<?php
/**
 *  SVGSantiizer
 * 
 *  Whitelist-based PHP SVG sanitizer.
 * 
 *  @link https://github.com/alister-/SVG-Sanitizer}
 *  @author Alister Norris
 *  @copyright Copyright (c) 2013 Alister Norris
 *  @license http://opensource.org/licenses/mit-license.php The MIT License
 *  @package svgsanitizer
 */

namespace Typemill\Models;

class SvgSanitizer {
	
	private $xmlDoc;				// PHP XML DOMDocument

	// defines the whitelist of elements and attributes allowed.
	private static $whitelist = [
		'a' => ['class' => true, 'clip-path' => true, 'clip-rule' => true, 'fill' => true, 'fill-opacity' => true, 'fill-rule' => true, 'filter' => true, 'id' => true, 'mask' => true, 'opacity' => true, 'stroke' => true, 'stroke-dasharray' => true, 'stroke-dashoffset' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'stroke-miterlimit' => true, 'stroke-opacity' => true, 'stroke-width' => true, 'style' => true, 'systemLanguage' => true, 'transform' => true, 'xlink:title' => true],
		'circle' => ['class' => true, 'clip-path' => true, 'clip-rule' => true, 'cx' => true, 'cy' => true, 'fill' => true, 'fill-opacity' => true, 'fill-rule' => true, 'filter' => true, 'id' => true, 'mask' => true, 'opacity' => true, 'r' => true, 'requiredFeatures' => true, 'stroke' => true, 'stroke-dasharray' => true, 'stroke-dashoffset' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'stroke-miterlimit' => true, 'stroke-opacity' => true, 'stroke-width' => true, 'style' => true, 'systemLanguage' => true, 'transform' => true],
		'clipPath' => ['class' => true, 'clipPathUnits' => true, 'id' => true],
		'defs' => [],
	    'style' => ['type' => true],
		'desc' => [],
		'ellipse' => ['class' => true, 'clip-path' => true, 'clip-rule' => true, 'cx' => true, 'cy' => true, 'fill' => true, 'fill-opacity' => true, 'fill-rule' => true, 'filter' => true, 'id' => true, 'mask' => true, 'opacity' => true, 'requiredFeatures' => true, 'rx' => true, 'ry' => true, 'stroke' => true, 'stroke-dasharray' => true, 'stroke-dashoffset' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'stroke-miterlimit' => true, 'stroke-opacity' => true, 'stroke-width' => true, 'style' => true, 'systemLanguage' => true, 'transform' => true],
		'feGaussianBlur' => ['class' => true, 'color-interpolation-filters' => true, 'id' => true, 'requiredFeatures' => true, 'stdDeviation' => true],
		'filter' => ['class' => true, 'color-interpolation-filters' => true, 'filterRes' => true, 'filterUnits' => true, 'height' => true, 'id' => true, 'primitiveUnits' => true, 'requiredFeatures' => true, 'width' => true, 'x' => true, 'y' => true],
		'foreignObject' => ['class' => true, 'font-size' => true, 'height' => true, 'id' => true, 'opacity' => true, 'requiredFeatures' => true, 'style' => true, 'transform' => true, 'width' => true, 'x' => true, 'y' => true],
		'g' => ['class' => true, 'clip-path' => true, 'clip-rule' => true, 'id' => true, 'display' => true, 'fill' => true, 'fill-opacity' => true, 'fill-rule' => true, 'filter' => true, 'mask' => true, 'opacity' => true, 'requiredFeatures' => true, 'stroke' => true, 'stroke-dasharray' => true, 'stroke-dashoffset' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'stroke-miterlimit' => true, 'stroke-opacity' => true, 'stroke-width' => true, 'style' => true, 'systemLanguage' => true, 'transform' => true, 'font-family' => true, 'font-size' => true, 'font-style' => true, 'font-weight' => true, 'text-anchor' => true],
		'image' => ['class' => true, 'clip-path' => true, 'clip-rule' => true, 'filter' => true, 'height' => true, 'id' => true, 'mask' => true, 'opacity' => true, 'requiredFeatures' => true, 'style' => true, 'systemLanguage' => true, 'transform' => true, 'width' => true, 'x' => true, 'xlink:title' => true, 'y' => true],
		'line' => ['class' => true, 'clip-path' => true, 'clip-rule' => true, 'fill' => true, 'fill-opacity' => true, 'fill-rule' => true, 'filter' => true, 'id' => true, 'marker-end' => true, 'marker-mid' => true, 'marker-start' => true, 'mask' => true, 'opacity' => true, 'requiredFeatures' => true, 'stroke' => true, 'stroke-dasharray' => true, 'stroke-dashoffset' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'stroke-miterlimit' => true, 'stroke-opacity' => true, 'stroke-width' => true, 'style' => true, 'systemLanguage' => true, 'transform' => true, 'x1' => true, 'x2' => true, 'y1' => true, 'y2' => true],
		'linearGradient' => ['class' => true, 'id' => true, 'gradientTransform' => true, 'gradientUnits' => true, 'requiredFeatures' => true, 'spreadMethod' => true, 'systemLanguage' => true, 'x1' => true, 'x2' => true, 'y1' => true, 'y2' => true],
		'marker' => ['id' => true, 'class' => true, 'markerHeight' => true, 'markerUnits' => true, 'markerWidth' => true, 'orient' => true, 'preserveAspectRatio' => true, 'refX' => true, 'refY' => true, 'systemLanguage' => true, 'viewBox' => true],
		'mask' => ['class' => true, 'height' => true, 'id' => true, 'maskContentUnits' => true, 'maskUnits' => true, 'width' => true, 'x' => true, 'y' => true],
		'metadata' => ['class' => true, 'id' => true],
		'path' => ['class' => true, 'clip-path' => true, 'clip-rule' => true, 'd' => true, 'fill' => true, 'fill-opacity' => true, 'fill-rule' => true, 'filter' => true, 'id' => true, 'marker-end' => true, 'marker-mid' => true, 'marker-start' => true, 'mask' => true, 'opacity' => true, 'requiredFeatures' => true, 'stroke' => true, 'stroke-dasharray' => true, 'stroke-dashoffset' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'stroke-miterlimit' => true, 'stroke-opacity' => true, 'stroke-width' => true, 'style' => true, 'systemLanguage' => true, 'transform' => true],
		'pattern' => ['class' => true, 'height' => true, 'id' => true, 'patternContentUnits' => true, 'patternTransform' => true, 'patternUnits' => true, 'requiredFeatures' => true, 'style' => true, 'systemLanguage' => true, 'viewBox' => true, 'width' => true, 'x' => true, 'y' => true],
		'polygon' => ['class' => true, 'clip-path' => true, 'clip-rule' => true, 'id' => true, 'fill' => true, 'fill-opacity' => true, 'fill-rule' => true, 'filter' => true, 'id' => true, 'class' => true, 'marker-end' => true, 'marker-mid' => true, 'marker-start' => true, 'mask' => true, 'opacity' => true, 'points' => true, 'requiredFeatures' => true, 'stroke' => true, 'stroke-dasharray' => true, 'stroke-dashoffset' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'stroke-miterlimit' => true, 'stroke-opacity' => true, 'stroke-width' => true, 'style' => true, 'systemLanguage' => true, 'transform' => true],
		'polyline' => ['class' => true, 'clip-path' => true, 'clip-rule' => true, 'id' => true, 'fill' => true, 'fill-opacity' => true, 'fill-rule' => true, 'filter' => true, 'marker-end' => true, 'marker-mid' => true, 'marker-start' => true, 'mask' => true, 'opacity' => true, 'points' => true, 'requiredFeatures' => true, 'stroke' => true, 'stroke-dasharray' => true, 'stroke-dashoffset' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'stroke-miterlimit' => true, 'stroke-opacity' => true, 'stroke-width' => true, 'style' => true, 'systemLanguage' => true, 'transform' => true],
		'radialGradient' => ['class' => true, 'cx' => true, 'cy' => true, 'fx' => true, 'fy' => true, 'gradientTransform' => true, 'gradientUnits' => true, 'id' => true, 'r' => true, 'requiredFeatures' => true, 'spreadMethod' => true, 'systemLanguage' => true],
		'rect' => ['class' => true, 'clip-path' => true, 'clip-rule' => true, 'fill' => true, 'fill-opacity' => true, 'fill-rule' => true, 'filter' => true, 'height' => true, 'id' => true, 'mask' => true, 'opacity' => true, 'requiredFeatures' => true, 'rx' => true, 'ry' => true, 'stroke' => true, 'stroke-dasharray' => true, 'stroke-dashoffset' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'stroke-miterlimit' => true, 'stroke-opacity' => true, 'stroke-width' => true, 'style' => true, 'systemLanguage' => true, 'transform' => true, 'width' => true, 'x' => true, 'y' => true],
		'stop' => ['class' => true, 'id' => true, 'offset' => true, 'requiredFeatures' => true, 'stop-color' => true, 'stop-opacity' => true, 'style' => true, 'systemLanguage' => true],
		'svg' => ['class' => true, 'clip-path' => true, 'clip-rule' => true, 'filter' => true, 'id' => true, 'height' => true, 'mask' => true, 'preserveAspectRatio' => true, 'requiredFeatures' => true, 'style' => true, 'systemLanguage' => true, 'viewBox' => true, 'width' => true, 'x' => true, 'xmlns' => true, 'xmlns:se' => true, 'xmlns:xlink' => true, 'y' => true],
		'switch' => ['class' => true, 'id' => true, 'requiredFeatures' => true, 'systemLanguage' => true],
		'symbol' => ['class' => true, 'fill' => true, 'fill-opacity' => true, 'fill-rule' => true, 'filter' => true, 'font-family' => true, 'font-size' => true, 'font-style' => true, 'font-weight' => true, 'id' => true, 'opacity' => true, 'preserveAspectRatio' => true, 'requiredFeatures' => true, 'stroke' => true, 'stroke-dasharray' => true, 'stroke-dashoffset' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'stroke-miterlimit' => true, 'stroke-opacity' => true, 'stroke-width' => true, 'style' => true, 'systemLanguage' => true, 'transform' => true, 'viewBox' => true],
		'text' => ['class' => true, 'clip-path' => true, 'clip-rule' => true, 'fill' => true, 'fill-opacity' => true, 'fill-rule' => true, 'filter' => true, 'font-family' => true, 'font-size' => true, 'font-style' => true, 'font-weight' => true, 'id' => true, 'mask' => true, 'opacity' => true, 'requiredFeatures' => true, 'stroke' => true, 'stroke-dasharray' => true, 'stroke-dashoffset' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'stroke-miterlimit' => true, 'stroke-opacity' => true, 'stroke-width' => true, 'style' => true, 'systemLanguage' => true, 'text-anchor' => true, 'transform' => true, 'x' => true, 'xml:space' => true, 'y' => true],
		'textPath' => ['class' => true, 'id' => true, 'method' => true, 'requiredFeatures' => true, 'spacing' => true, 'startOffset' => true, 'style' => true, 'systemLanguage' => true, 'transform' => true],
		'title' => [],
		'tspan' => ['class' => true, 'clip-path' => true, 'clip-rule' => true, 'dx' => true, 'dy' => true, 'fill' => true, 'fill-opacity' => true, 'fill-rule' => true, 'filter' => true, 'font-family' => true, 'font-size' => true, 'font-style' => true, 'font-weight' => true, 'id' => true, 'mask' => true, 'opacity' => true, 'requiredFeatures' => true, 'rotate' => true, 'stroke' => true, 'stroke-dasharray' => true, 'stroke-dashoffset' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'stroke-miterlimit' => true, 'stroke-opacity' => true, 'stroke-width' => true, 'style' => true, 'systemLanguage' => true, 'text-anchor' => true, 'textLength' => true, 'transform' => true, 'x' => true, 'xml:space' => true, 'y' => true],
		'use' => ['class' => true, 'clip-path' => true, 'clip-rule' => true, 'fill' => true, 'fill-opacity' => true, 'fill-rule' => true, 'filter' => true, 'height' => true, 'id' => true, 'mask' => true, 'stroke' => true, 'stroke-dasharray' => true, 'stroke-dashoffset' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'stroke-miterlimit' => true, 'stroke-opacity' => true, 'stroke-width' => true, 'style' => true, 'transform' => true, 'width' => true, 'x' => true, 'y' => true],
	];

	function __construct() {
		$this->xmlDoc = new \DOMDocument();
		$this->xmlDoc->preserveWhiteSpace = false;
	}

	//Load the SVG data from a file
	function load($file) {
		$this->xmlDoc->load($file);
	}

	function loadSVG(string $string) {
		$result = $this->xmlDoc->loadXML($string);
	}
	
	//Remove any elements from the XML that are unrelated to SVGs
	function sanitize() {
		
		//Get every element in the document, and loop through them all
		$allElements = $this->xmlDoc->getElementsByTagName("*");

		for ($i = 0; $i < $allElements->length; $i++) {
			$currentNode = $allElements->item($i);
			
			//Remove any elements not on the whitelist
			if (!isset(self::$whitelist[$currentNode->tagName])) {
		        $currentNode->parentNode->removeChild($currentNode);
		        $i--;
		    
		    } else {
				$attributesWhitelist = self::$whitelist[$currentNode->tagName];
				$attributesToRemove = [];
				
				//If the element is allowed, loop through checking its attributes v.s. the attributes allowed for that element
				for ($j = 0; $j < $currentNode->attributes->length; $j++) {
					$attrName = $currentNode->attributes->item($j)->name;
					
					if (!isset($attributesWhitelist[$attrName])) {
						$attributesToRemove[] = $attrName;
					}
				}
				
				//Remove any blocked attributes
				if (!empty($attributesToRemove)) {
					foreach ($attributesToRemove as $attrName) {
						$currentNode->removeAttribute($attrName);
					}
				}
		    }	
		}
	}

	function saveSVG() {
		$this->xmlDoc->formatOutput = true;
		return($this->xmlDoc->saveXML());
	}

	function save($file) {
		$this->xmlDoc->formatOutput = true;
		return($this->xmlDoc->save($file));
	}
}
