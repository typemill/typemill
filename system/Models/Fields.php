<?php

namespace Typemill\Models;

use Typemill\Models\Field;

class Fields
{
	public function getFields($userSettings, $objectType, $objectName, $objectSettings, $formType = false)
	{			
		# hold all fields in array 
		$fields = array();

		# formtype are backendforms or frontendforms, only relevant for plugins for now
		$formType = $formType ? $formType : 'forms';
		
		# iterate through all fields of the objectSetting (theme or plugin)
		foreach($objectSettings[$formType]['fields'] as $fieldName => $fieldConfigurations)
		{
			if($fieldConfigurations['type'] == 'fieldset')
			{
				# if it is a fieldset, then create a subset for the containing field and read them with a recursive function
				$subSettings 			= $objectSettings;
				$subSettings['forms']	= $fieldConfigurations;
				
				$fieldset 				= array();
				$fieldset['type'] 		= 'fieldset';
				$fieldset['legend']		= $fieldConfigurations['legend'];
				$fieldset['fields'] 	= $this->getFields($userSettings, $objectType, $objectName, $subSettings, $formType);
 				$fields[] 				= $fieldset;
			}
			else
			{
				# for each field generate a new field object with the field name and the field configurations
				$field = new Field($fieldName, $fieldConfigurations);
				
				# handle the value for the field
				$userValue = false;

				# first, add the default value from the original plugin or theme settings
				if(isset($objectSettings['settings'][$fieldName]))
				{
					$userValue = $objectSettings['settings'][$fieldName];
				}

				# now overwrite the default values with the user values stored in the user settings
				if(isset($userSettings[$objectType][$objectName][$fieldName]))
				{
					$userValue = $userSettings[$objectType][$objectName][$fieldName];
				}

				# now overwrite user-values, if there are old-input values from the actual form (e.g. after input error)
				if(isset($_SESSION['old'][$objectName][$fieldName]))
				{
					$userValue = $_SESSION['old'][$objectName][$fieldName];
				}
			
				# Now prepopulate the field object with the value */
				if($field->getType() == "textarea")
				{
					if($userValue)
					{
						$field->setContent($userValue);
					}
				}
				elseif($field->getType() == "checkbox")
				{
					# checkboxes need a special treatment, because field does not exist in settings if unchecked by user
					if(isset($userSettings[$objectType][$objectName][$fieldName]))
					{
						$field->setAttribute('checked', 'checked');
					}
					else
					{
						$field->unsetAttribute('checked');
					}
				}
				else
				{
					$field->setAttributeValue('value', $userValue);	
				}

				# add the field to the field-List
				$fields[] = $field;

			}
		}	
		return $fields;
	}
}