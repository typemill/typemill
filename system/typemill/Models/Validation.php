<?php

namespace Typemill\Models;

use Valitron\Validator;
use Typemill\Models\User;
use Typemill\Models\StorageWrapper;

class Validation
{

	# only used for recursive validation
	public $errors = [];

	/**
	* Constructor with custom validation rules 
	*
	* @param obj $db the database connection.
	*/

	public function __construct()
	{
		$user = new User();
		
		Validator::langDir(getcwd() . '/system/vendor/vlucas/valitron/lang'); // always set langDir before lang.
		Validator::lang('en');

		Validator::addRule('values_allowed', function($field, $value, array $params, array $fields) use ($user)
		{
			$badvalues = array_diff($value, $params[0]);
			if(empty($badvalues)){ return true; }
			return false;
		}, 'invalid values');

		Validator::addRule('image_types', function($field, $value, array $params, array $fields) use ($user)
		{
    		$allowed 	= ['jpg', 'jpeg', 'png', 'webp', 'svg'];
			$pathinfo	= pathinfo($value);
			$extension 	= strtolower($pathinfo['extension']);
			if(in_array($extension, $allowed)){ return true; }
			return false;
		}, 'only jpg, jpeg, png, webp, svg allowed');

		# checks if email is available if user is created
		Validator::addRule('emailAvailable', function($field, $value, array $params, array $fields) use ($user)
		{
			$email = trim($value);
			if($email == '' OR $user->findUsersByEmail($email)){ return false; }
			return true;
		}, 'taken');

		# checks if email is available if userdata is updated
		Validator::addRule('emailChanged', function($field, $value, array $params, array $fields) use ($user)
		{
			$user->setUserWithPassword($fields['username']);
			$userdata = $user->getUserData();
			if($userdata['email'] == $value){ return true; } # user has not updated his email

			$email = trim($value);
			if($user->findUsersByEmail($email)){ return false; }
			return true;
		}, 'taken');

		# checks if username is free when create new user
		Validator::addRule('userAvailable', function($field, $value, array $params, array $fields) use ($user)
		{
			$activeUser 	= $user->setUser($value);
			$inactiveUser 	= $user->setUser("_" . $value);
			if($activeUser OR $inactiveUser){ return false; }
			return true;
		}, 'taken');

		# checks if user exists when userdata is updated
		Validator::addRule('userExists', function($field, $value, array $params, array $fields) use ($user)
		{
			if($user->setUser($value)){ return true; }
			return false;
		}, 'does not exist');
		
		Validator::addRule('iplist', function($field, $value, array $params, array $fields) use ($user)
		{
			$iplist = explode(",", $value);
			foreach($iplist as $ip)
			{
		        if( filter_var( trim($ip), \FILTER_VALIDATE_IP) === false)
		        {
		        	return false;
		        }
			}
			return true;
		}, 'contains one or more invalid ip-adress.');

		Validator::addRule('customfields', function($field, $customfields, array $params, array $fields) use ($user)
		{
			if(empty($customfields))
			{
				return true;
			}
			foreach($customfields as $key => $value)
			{
				if(!isset($key) OR empty($key) OR (preg_match('/^([a-z0-9])+$/i', $key) == false) )
				{
		        	return false;
		        }

				if (!isset($value) OR empty($value) OR ( $value != strip_tags($value) ) )
				{
					return false;
				}
			}
			return true;
		}, 'some fields are empty or contain invalid values.');

		Validator::addRule('checkPassword', function($field, $value, array $params, array $fields) use ($user)
		{
			if($user->setUserWithPassword($fields['username']))
			{
				$userdata = $user->getUserData();
				if(password_verify($value, $userdata['password']))
				{ 
					return true; 
				}
			}
			return false;
		}, 'wrong password');
		
		Validator::addRule('navigation', function($field, $value, array $params, array $fields)
		{
			$format = '/[@#^*()=\[\]{};:"\\|,.<>\/]/';
			if ( preg_match($format, $value))
			{
				return false;
			}
			return true;
		}, 'contains special characters');

		Validator::addRule('noSpecialChars', function($field, $value, array $params, array $fields)
		{
			$format = '/[!@#$%^&*()_+=\[\]{};\':"\\|,.<>\/?]/';
			if ( preg_match($format, $value))
			{
				return false;
			}
			return true;
		}, 'contains special characters');
		
		Validator::addRule('noHTML', function($field, $value, array $params, array $fields)
		{
			if ( $value == strip_tags($value) )
			{
				return true;
			}
			return false;
		}, 'contains html');
		
		Validator::addRule('markdownSecure', function($field, $value, array $params, array $fields)
		{
			/* strip out code blocks and blockquotes */
			$value = preg_replace('/`{4,}[\s\S]+?`{4,}/', '', $value);
			$value = preg_replace('/`{3,}[\s\S]+?`{3,}/', '', $value);
			$value = preg_replace('/`{2,}[\s\S]+?`{2,}/', '', $value);
			$value = preg_replace('/`{1,}[\s\S]+?`{1,}/', '', $value);
			$value = preg_replace('/>[\s\S]+?[\n\r]/', '', $value);			
			
			if ( $value == strip_tags($value) )
			{
				return true;
			}
			return false;
		}, 'not secure. For code please use markdown `inline-code` or ````fenced code blocks````.');

		Validator::addRule('checkLicense', function($field, $value, array $params, array $fields)
		{
			$parts = explode("-",$value);
			if(count($parts) != 5)
			{
				return false;
			}
			if($parts[0] != "TM2")
			{
				return false;
			}
			unset($parts[0]);
			foreach($parts as $key => $part)
			{
				if(strlen($part) != 5 OR !preg_match("/^[A-Z0-9]*$/",$part) )
				{
					return false;
				}
			}

			return true;
		}, 'format is not valid.');

		Validator::addRule('version', function($field, $value, array $params, array $fields)
		{
			if( version_compare( $value, '0.0.1', '>=' ) >= 0 )
			{
				return true;
			}
			return false;
		}, 'not a valid version format.');
	
		Validator::addRule('version_array', function($field, $value, array $params, array $fields)
		{
			foreach($value as $name => $version)
			{
				if(!preg_match("/^[A-Za-z0-9_\- ]+$/", $name))
				{
					return false;
				}

				if( version_compare( $version, '0.0.1', '>=' ) <= 0 )
				{
					return false;
				}
			}

			return true;
		}, 'not a valid version format.');
	}

	# return valitron standard object
	public function returnValidator(array $params)
	{
		return new Validator($params);
	}

	public function returnFirstValidationErrors($errors)
	{
		foreach($errors as $key => $error)
		{
			$errors[$key] = $error[0];
		}

		return $errors;
	}

	/**
	* validation for signin form
	* 
	* @param array $params with form data.
	* @return obj $v the validation object passed to a result method.
	*/
	
	public function signin(array $params)
	{
		$v = new Validator($params);
		$v->rule('required', ['username', 'password'])->message("Required");
		$v->rule('alphaNum', 'username')->message("Invalid characters");
		$v->rule('lengthBetween', 'password', 5, 20)->message("Length between 5 - 20");
		$v->rule('lengthBetween', 'username', 3, 20)->message("Length between 3 - 20");
		
		if($v->validate())
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	* validation for new user (in backoffice)
	* 
	* @param array $params with form data.
	* @return obj $v the validation object passed to a result method.
	*/
	
	public function newUser(array $params, array $userroles)
	{
		$v = new Validator($params);
		$v->rule('required', ['username', 'email', 'password'])->message("required");
		$v->rule('alphaNum', 'username')->message("invalid characters");
		$v->rule('lengthBetween', 'password', 5, 40)->message("Length between 5 - 40");
		$v->rule('lengthBetween', 'username', 3, 20)->message("Length between 3 - 20"); 
		$v->rule('userAvailable', 'username')->message("User already exists");
		$v->rule('noHTML', 'firstname')->message(" contains HTML");
		$v->rule('lengthBetween', 'firstname', 2, 40);
		$v->rule('noHTML', 'lastname')->message(" contains HTML");
		$v->rule('lengthBetween', 'lastname', 2, 40);
		$v->rule('email', 'email')->message("e-mail is invalid");
		$v->rule('emailAvailable', 'email')->message("Email already taken");
		$v->rule('in', 'userrole', $userroles);
		
		if($v->validate()) 
		{
			return true;
		}

		return $v->errors();
	}
	
	# change user in backoffice
	public function existingUser(array $params, $userroles)
	{
		$v = new Validator($params);
		$v->rule('required', ['username', 'email', 'userrole'])->message("required");
		$v->rule('alphaNum', 'username')->message("invalid");
		$v->rule('lengthBetween', 'username', 3, 20)->message("Length between 3 - 20"); 
		$v->rule('userExists', 'username')->message("user does not exist");
		$v->rule('noHTML', 'firstname')->message(" contains HTML");
		$v->rule('lengthBetween', 'firstname', 2, 40);
		$v->rule('noHTML', 'lastname')->message(" contains HTML");
		$v->rule('lengthBetween', 'lastname', 2, 40);		
		$v->rule('email', 'email')->message("e-mail is invalid");
		$v->rule('emailChanged', 'email')->message("Email already taken");
		$v->rule('in', 'userrole', $userroles);

		if($v->validate()) 
		{
			return true;
		}

		return $v->errors();
	}

	public function newLicense(array $params)
	{
		$v = new Validator($params);
		$v->rule('required', ['license', 'email', 'domain']);
		$v->rule('checkLicense', 'license');
		$v->rule('email', 'email');
		$v->rule('url', 'domain');
		
		if($v->validate()) 
		{
			return true;
		}

		return $v->errors();
	}

	public function activateExtension(array $params)
	{
		$v = new Validator($params);
		$v->rule('required', ['name', 'type', 'checked']);
		$v->rule('in', 'type', ['plugins', 'themes']);
		$v->rule('boolean', 'checked');
		
		if($v->validate()) 
		{
			return true;
		}

		return $v->errors();
	}

	public function checkVersions(array $params)
	{
		$v = new Validator($params);
		$v->rule('required', ['type', 'data']);
		$v->rule('in', 'type', ['plugins', 'themes', 'system']);
		
		if(!$v->validate()) 
		{
			return $v->errors();
		}

		if($params['type'] == 'plugins' OR $params['type'] == 'themes')
		{
			$v->rule('version_array', 'data');
		}
		else
		{
			$v->rule('version', 'data');
		}

		if(!$v->validate()) 
		{
			return $v->errors();
		}

		return true;
	}

	public function navigationSort(array $params)
	{
		$v = new Validator($params);
				
		$v->rule('required', ['item_id', 'parent_id_from', 'parent_id_to']);
		$v->rule('regex', 'item_id', '/^[0-9.]+$/i');
		$v->rule('regex', 'parent_id_from', '/^[a-zA-Z0-9.]+$/i');
		$v->rule('regex', 'parent_id_to', '/^[a-zA-Z0-9.]+$/i');
		$v->rule('integer', 'index_new');
		$v->rule('integer', 'index_old');
		
		if($v->validate()) 
		{
			return true;
		} 

		return $v->errors();
	}

	public function navigationItem(array $params)
	{
		$v = new Validator($params);

		$v->rule('required', ['folder_id', 'item_name', 'type']);
		$v->rule('regex', 'folder_id', '/^(root)|([0-9.]+)$/i');
		$v->rule('navigation', 'item_name');
		$v->rule('lengthBetween', 'item_name', 1, 60);
		$v->rule('in', 'type', ['file', 'folder']);
		
		if($v->validate()) 
		{
			return true;
		} 
		else
		{
			return $v->errors();
		}
	}	

	public function blockInput(array $params)
	{
		$v = new Validator($params);
		
		$v->rule('required', ['markdown', 'block_id', 'url']);
		$v->rule('markdownSecure', 'markdown');
		$v->rule('regex', 'block_id', '/^[0-9.]+$/i');
		
		if($v->validate())
		{
			return true;
		} 
		else
		{
			return $v->errors();
		}
	}

	public function blockMove(array $params)
	{
		$v = new Validator($params);
		
		$v->rule('required', ['index_new', 'index_old', 'url']);
		$v->rule('regex', 'index_new', '/^[0-9.]+$/i');
		$v->rule('regex', 'index_old', '/^[0-9.]+$/i');
		
		if($v->validate())
		{
			return true;
		} 
		else
		{
			return $v->errors();
		}
	}

	public function blockDelete(array $params)
	{
		$v = new Validator($params);
		
		$v->rule('required', ['block_id', 'url']);
		$v->rule('regex', 'block_id', '/^[0-9.]+$/i');
		
		if($v->validate())
		{
			return true;
		} 
		else
		{
			return $v->errors();
		}
	}

	public function articlePublish(array $params)
	{
		$v = new Validator($params);

		# special conditions for startpage
		if(isset($params['item_id']) && $params['item_id'] == '')
		{
			$v->rule('required', ['url']);
			$v->rule('markdownSecure', 'markdown');			
		}
		else
		{
			$v->rule('required', ['item_id', 'url']);
			$v->rule('regex', 'item_id', '/^[0-9.]+$/i');
			$v->rule('markdownSecure', 'markdown');
		}
				
		if($v->validate())
		{
			return true;
		} 
		else
		{
			return $v->errors();
		}
	}

	public function articleUpdate(array $params)
	{
		$v = new Validator($params);

		# special conditions for startpage
		if(isset($params['item_id']) && $params['item_id'] == '')
		{
			$v->rule('required', ['url', 'title', 'body']);
			$v->rule('markdownSecure', 'title');
			$v->rule('markdownSecure', 'body');
		}
		else
		{
			$v->rule('required', ['item_id', 'url', 'title', 'body']);
			$v->rule('regex', 'item_id', '/^[0-9.]+$/i');
			$v->rule('markdownSecure', 'title');
			$v->rule('markdownSecure', 'body');
		}
		
		if($v->validate())
		{
			return true;
		} 
		else
		{
			return $v->errors();
		}
	}

	public function articleRename(array $params)
	{
		$v = new Validator($params);
		
		$v->rule('required', ['url', 'slug', 'oldslug']);
		$v->rule('regex', 'slug', '/^[a-z0-9\-]*$/');
		$v->rule('lengthBetween', 'slug', 1, 50)->message("Length between 1 - 50"); 
		$v->rule('different', 'slug', 'oldslug');

		if($v->validate())
		{
			return true;
		} 
		else
		{
			return $v->errors();
		}
	}

	public function metaInput(array $params)
	{
		$v = new Validator($params);
		
		$v->rule('required', ['url', 'tab', 'data']);

		if($v->validate())
		{
			return true;
		} 
		else
		{
			return $v->errors();
		}
	}

	/**
	* validation for password recovery
	* 
	* @param array $params with form data.
	* @return obj $v the validation object passed to a result method.
	*/
	
	public function recoverPassword(array $params)
	{
		$v = new Validator($params);
		$v->rule('required', ['password', 'passwordrepeat']);
		$v->rule('lengthBetween', 'password', 5, 50);
		$v->rule('equals', 'passwordrepeat', 'password');
		
		return $this->validationResult($v);
	}


	/**
	* validation for content editor
	* 
	* @param array $params with form data.
	* @return true or $v->errors with array of errors to use in json-response
	*/
	
	public function editorInput(array $params)
	{
		$v = new Validator($params);
		
		$v->rule('required', ['title', 'content', 'url']);
		$v->rule('lengthBetween', 'title', 2, 100);
		$v->rule('noHTML', 'title');
		$v->rule('markdownSecure', 'content');
		
		if($v->validate()) 
		{
			return true;
		} 
		else
		{
			return $v->errors();
		}		
	}
	
	/**
	* validation for dynamic fields ( settings for themes and plugins)
	* 
	* @param string $fieldName with the name of the field.
	* @param array or string $fieldValue with the values of the field.
	* @param string $objectName with the name of the plugin or theme.
	* @param array $fieldDefinitions with the field definitions as multi-dimensional array.
	* @return obj $v the validation object passed to a result method.
	*/
	
	public function field($fieldName, $fieldValue, $fieldDefinitions)
	{
		$v = new Validator(array($fieldName => $fieldValue));
		
		if(isset($fieldDefinitions['required']))
		{
			$v->rule('required', $fieldName);
		}
		if(isset($fieldDefinitions['maxlength']))
		{
			$v->rule('lengthMax', $fieldName, $fieldDefinitions['maxlength']);
		}
		if(isset($fieldDefinitions['max']))
		{
			$v->rule('max', $fieldName, $fieldDefinitions['max']);
		}
		if(isset($fieldDefinitions['min']))
		{
			$v->rule('min', $fieldName, $fieldDefinitions['min']);
		}
		if(isset($fieldDefinitions['pattern']))
		{
			$v->rule('regex', $fieldName, '/^' . $fieldDefinitions['pattern'] . '$/');
		}

		switch($fieldDefinitions['type'])
		{
			case "checkbox":
				if(isset($fieldDefinitions['required']))
				{
				 	$v->rule('accepted', $fieldName);
				}
				break;
			case "checkboxlist":				
				if(isset($fieldValue) && is_array($fieldValue))
				{
					# create array with option keys as value
					$options = array();
					foreach($fieldDefinitions['options'] as $key => $value){ $options[] = $key; }

					# loop over input values and check, if the options of the field definitions (options for checkboxlist) contains the key (input from user, key is used as value, value is used as label)
					foreach($fieldValue as $key => $value)
					{
						$v->rule('in', $key, $options);
					}
				}
				break;
			case "codearea":
				$v->rule('lengthMax', $fieldName, 10000);
				break;
			case "color":
				$v->rule('regex', $fieldName, '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/');
				break;
			case "customfields":
				$v->rule('array', $fieldName);
				$v->rule('customfields', $fieldName);
				break;
			case "date":
				$v->rule('date', $fieldName);
				break;
			case "email":
				$v->rule('email', $fieldName);
				break;
			case "image":
				$v->rule('noHTML', $fieldName);
				$v->rule('lengthMax', $fieldName, 1000);
				$v->rule('image_types', $fieldName);
				break;
			case "number":
				$v->rule('integer', $fieldName);
				break;
			case "paragraph":
				$v->rule('noHTML', $fieldName);
				$v->rule('lengthMax', $fieldName, 10000);
				break;
			case "password":
				$v->rule('lengthMax', $fieldName, 100);
				break;
			case "radio":
				$v->rule('in', $fieldName, $fieldDefinitions['options']);
				break;
			case "select":
				# create array with option keys as value
				$options = array();
				foreach($fieldDefinitions['options'] as $key => $value){ $options[] = $key; }
				$v->rule('in', $fieldName, $options);
				break;
			case "text":
				$v->rule('noHTML', $fieldName);
				$v->rule('lengthMax', $fieldName, 500);
#				$v->rule('regex', $fieldName, '/^[\pL0-9_ \-\.\?\!\/\:]*$/u');
				break;
			case "textarea":
				# it understands array, json, yaml
				if(is_array($fieldValue))
				{
					$v = $this->checkArray($fieldValue, $v);
				}
				else
				{
					$v->rule('noHTML', $fieldName);
					$v->rule('lengthMax', $fieldName, 10000);
				}
				break;
			case "url":
				$v->rule('url', $fieldName);
				$v->rule('lengthMax', $fieldName, 200);
				break;
			default:
				$v->rule('lengthMax', $fieldName, 1000);
				$v->rule('regex', $fieldName, '/^[\pL0-9_ \-]*$/u');
		}

		if(!$v->validate())
		{
			return $v->errors();
		}
		
		return true;
	}
	

	# validate a whole formdefinition with all values
	public function recursiveValidation(array $formdefinitions, $input, $output = [])
	{
		# loop through form-definitions, ignores everything that is not defined in yaml
		foreach($formdefinitions as $fieldname => $fielddefinitions)
		{
			if(is_array($fielddefinitions) && $fielddefinitions['type'] == 'fieldset')
			{
				$output = $this->recursiveValidation($fielddefinitions['fields'], $input, $output);
			}

			# do not store values for disabled fields
			if(isset($fielddefinitions['disabled']) && $fielddefinitions['type'])
			{
				continue;
			}

			if(isset($input[$fieldname]))
			{
				$fieldvalue = $input[$fieldname];

				# fix false or null values for selectboxes
				if($fielddefinitions['type'] == "select" && ($fieldvalue === 'NULL' OR $fieldvalue === false))
				{ 
					$fieldvalue = NULL; 
				}

				$validationresult = $this->field($fieldname, $fieldvalue, $fielddefinitions);

				if($validationresult === true)
				{
					# MOVE THIS TO A SEPARATE FUNCTION SO YOU CAN STORE IMAGES ONLY IF ALL FIELDS SUCCESSFULLY VALIDATED
					# images have special treatment, check ProcessImage-Model and ImageApiController
					if($fielddefinitions['type'] == 'image')
					{
						# then check if file is there already: check for name and maybe correct image extension (if quality has been changed)
						$storage = new StorageWrapper('\Typemill\Models\Storage');
						$existingImagePath = $storage->checkImage($fieldvalue);
						
						if($existingImagePath)
						{
							$fieldvalue = $existingImagePath;
						}
						else
						{
							# there is no published image with that name, so check if there is an unpublished image in tmp folder and publish it
							$newImagePath = $storage->publishImage($fieldvalue);
							if($newImagePath)
							{
								$fieldvalue = $newImagePath;
							}
							else
							{
								$fieldvalue = '';
							}
						}
					}

					$output[$fieldname] = $fieldvalue;
				}
				else
				{
					$this->errors[$fieldname] = $validationresult[$fieldname][0];
				}
			}
		}

		return $output;
	}


	/**
	* result for validation
	* 
	* @param obj $v the validation object.
	* @return bool
	*/

	public function checkArray($arrayvalues, $v)
	{		
		die('I think checkArray not in use anymore');

		foreach($arrayvalues as $key => $value)
		{
			if(is_array($value))
			{
				$this->checkArray($value, $v);
			}
			$v->rule('noHTML', $value);
			$v->rule('lengthMax', $value, 1000);
		}
		return $v;
	}
	
	public function validationResult($v, $name = false)
	{
		die("do not use validationResults in validation model anymore");
		
		if($v->validate())
		{
			return true;
		}
		else
		{
			if($name == 'meta')
			{
				return $v->errors();
			}
			elseif($name)
			{
				if(isset($_SESSION['errors'][$name]))
				{
					foreach ($v->errors() as $key => $val)
					{
						$_SESSION['errors'][$name][$key] = $val;
						break;
					}
				}
				else
				{
					$_SESSION['errors'][$name] = $v->errors();
				}
			}
			else
			{
				$_SESSION['errors'] = $v->errors();
			}
			return false;
		}
	}
}