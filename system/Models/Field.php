<?php

namespace Typemill\Models;

class Field
{
	private $type;
	
	private $label;	
	
	private $name; 
	
	private $content;
	
	/* holds all simple attributes for this field like "required" */
	private $attributes 		= array();
	
	/* holds all attribute value pairs for this field like "id=''" */
	private $attributeValues	= array();	
	
	/* holds all options for this field (e.g. select options) */
	private $options			= array();
	
	/* defines all field types, that are allowed */
	private $types 				= array(
									'checkbox',
									'checkboxlist',
									'color',
									'date',
									'datetime',
									'datetime-local',
									'email',
									'file',
									'hidden',
									'image',
									'month',
									'number',
									'password',
									'radio',
									'range',
									'tel',
									'text',
									'time',
									'url',
									'week',
									'textarea',
									'select'
								);
								
	/* defines all boolean attributes, that are allowed for fields */
	private $attr				= array(
									'autofocus',
									'checked',
									'disabled',
									'formnovalidate',
									'multiple',
									'readonly',
									'required'
								);

	/* defines all attribute value paires, that are allowed for fields */
	private $attrValues 		= array(
									'id',
									'autocomplete',
									'placeholder',
									'value',
									'size',
									'rows',
									'cols',
									'class',
									'pattern'
								);
	
	/* defines additional data, that are allowed for fields */
	private $helpers			= array(
									'help',
									'description'
								);
		
	public function __construct($fieldName, array $fieldConfigs)
	{
		$this->setName($fieldName);
		
		if(isset($fieldConfigs['type']))
		{
			$this->setType($fieldConfigs['type']);
		}
		
		if(isset($fieldConfigs['label']))
		{
			$this->setLabel($fieldConfigs['label']);
		}
				
		if(isset($fieldConfigs['options']))
		{
			$this->setOptions($fieldConfigs['options']);
		}
		
		$this->setAttributes($fieldConfigs);
		
		$this->setAttributeValues($fieldConfigs);
		
		$this->setHelpers($fieldConfigs);		
	}

	private function setName($name)
	{
		$this->name = $name;
	}

	public function getName()
	{
		return $this->name;
	}
	
	private function setType($type)
	{
		if(in_array($type, $this->types))
		{
			$this->type = $type;
		}
	}
	
	public function getType()
	{
		return $this->type;
	}
	
	private function setLabel($label)
	{
		$this->label = $label;
	}

	public function getLabel()
	{
		return $this->label;
	}
	
	public function setContent($content)
	{
		$this->content = $content;
	}

	public function getContent()
	{
		return $this->content;
	}
	
	private function setOptions(array $options)
	{
		foreach($options as $key => $value)
		{
			$this->options[$key] = $value;
		}
	}
	
	public function getOptions()
	{
		if(isset($this->options))
		{
			return $this->options;
		}
		return false;
	}
	
	private function setAttributes($fieldConfigs)
	{
		foreach($fieldConfigs as $key => $value)
		{
			if(is_string($key) && in_array($key, $this->attr))
			{
				$this->attributes[$key] = $value;
			}
		}
	}
	
	/* get all attributes of the field and return them as a string. For usage in templates */
	public function getAttributes()
	{
		$string = false;
		
		foreach($this->attributes as $key => $attribute)
		{
			$string .= ' ' . $key;
		}
		
		return $string;
	}
	
	/* set a single attribute. Used e.g. in controller to change the value */
	public function setAttribute($key, $value)
	{
		$this->attributes[$key] = $value;
	}
	
	/* get a single attribute, if it is defined. For usage in templates like getAttribute('required') */
	public function getAttribute($key)
	{
		if(isset($this->attributes[$key]))
		{
			return $this->attributes[$key];
		}
		
		return false;
	}
		
	private function setAttributeValues($fieldConfigs)
	{
		foreach($fieldConfigs as $key => $value)
		{
			if(is_string($key) && array_key_exists($key, $this->attrValues))
			{
				$this->attributeValues[$key] = $value;
			}
		}
	}

	/* get all attributes as string. For usage in template */
	public function getAttributeValues()
	{
		$string = false;
				
		foreach($this->attributeValues as $key => $attribute)
		{
			$string .= ' ' . $key . '="' . $attribute . '"';
		}
		
		return $string;
	}

	public function setAttributeValue($key, $value)
	{
		/* pretty dirty, but you should not add a value for a simple checkbox */
		if($key == 'value' && $this->type == 'checkbox')
		{
			return;
		}

		$this->attributeValues[$key] = $value;
	}
	
	private function getAttributeValue($key)
	{
		if(isset($this->attributeValues[$key]))
		{
			return $this->attributeValues[$key];
		}
		
		return false;
	}

	
	private function setHelpers($fieldConfigs)
	{
		foreach($fieldConfigs as $key => $config)
		{
			if(is_string($key) && in_array($key, $this->helpers))
			{
				$this->$key = $config;
			}
		}
	}
	
	public function getHelper($helperName)
	{
		if(isset($this->$helperName))
		{
			return $this->helperName;
		}
		return false;
	}	
}