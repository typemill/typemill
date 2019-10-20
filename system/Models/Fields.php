<?php

namespace Typemill\Models;

use Typemill\Models\Field;

class Fields
{
	public function getFields($userSettings, $objectType, $objectName, $objectSettings, $formType = false)
	{
		# hold all fields in array 
		$fields = array();

		# formtype are backend forms or public forms, only relevant for plugins for now
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
				# For label, helptext and description you can use the value of another field. This is useful e.g. to localize the label of public forms via plugin settings.
				if(isset($fieldConfigurations['label']) && isset($userSettings[$objectType][$objectName][$fieldConfigurations['label']]))
				{
					$fieldConfigurations['label'] = $userSettings[$objectType][$objectName][$fieldConfigurations['label']];
				}
				if(isset($fieldConfigurations['help']) && isset($userSettings[$objectType][$objectName][$fieldConfigurations['help']]))
				{
					$fieldConfigurations['help'] = $userSettings[$objectType][$objectName][$fieldConfigurations['help']];
				}
				if(isset($fieldConfigurations['description']) && isset($userSettings[$objectType][$objectName][$fieldConfigurations['description']]))
				{
					$fieldConfigurations['description'] = $userSettings[$objectType][$objectName][$fieldConfigurations['description']];
				}

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
				if($field->getType() == "textarea" || $field->getType() == "paragraph")
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
						$field->setAttribute('checked', 'true');						
					}
					else
					{
						$field->unsetAttribute('chhecked');
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