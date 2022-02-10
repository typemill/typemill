<?php

namespace Typemill\Models;

use Typemill\Models\User;
use Valitron\Validator;

class Validation
{
	/**
	* Constructor with custom validation rules 
	*
	* @param obj $db the database connection.
	*/

	public function __construct()
	{
		$user = new User();
		
		Validator::langDir(__DIR__.'/../vendor/vlucas/valitron/lang'); // always set langDir before lang.
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
			if($user->findUsersByEmail($email)){ return false; }
			return true;
		}, 'taken');

		# checks if email is available if userdata is updated
		Validator::addRule('emailChanged', function($field, $value, array $params, array $fields) use ($user)
		{
			$userdata = $user->getSecureUser($fields['username']);
			if($userdata['email'] == $value){ return true; } # user has not updated his email

			$email = trim($value);
			if($user->findUsersByEmail($email)){ return false; }
			return true;
		}, 'taken');

		# checks if username is free when create new user
		Validator::addRule('userAvailable', function($field, $value, array $params, array $fields) use ($user)
		{
			$activeUser 	= $user->getUser($value);
			$inactiveUser 	= $user->getUser("_" . $value);
			if($activeUser OR $inactiveUser){ return false; }
			return true;
		}, 'taken');

		# checks if user exists when userdata is updated
		Validator::addRule('userExists', function($field, $value, array $params, array $fields) use ($user)
		{
			$userdata = $user->getUser($value);
			if($userdata){ return true; }
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
			$userdata = $user->getUser($fields['username']);
			if($userdata && password_verify($value, $userdata['password'])){ return true; }
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
	}

	# return valitron standard object
	public function returnValidator(array $params)
	{
		return new Validator($params);
	}


	/**
	* validation for signup form
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
	* validation for signup form
	* 
	* @param array $params with form data.
	* @return obj $v the validation object passed to a result method.
	*/
	
	public function newUser(array $params, $userroles)
	{
		$v = new Validator($params);
		$v->rule('required', ['username', 'email', 'password'])->message("required");
		$v->rule('alphaNum', 'username')->message("invalid characters");
		$v->rule('lengthBetween', 'password', 5, 20)->message("Length between 5 - 20");
		$v->rule('lengthBetween', 'username', 3, 20)->message("Length between 3 - 20"); 
		$v->rule('userAvailable', 'username')->message("User already exists");
		$v->rule('noHTML', 'firstname')->message(" contains HTML");
		$v->rule('lengthBetween', 'firstname', 2, 40);
		$v->rule('noHTML', 'lastname')->message(" contains HTML");
		$v->rule('lengthBetween', 'lastname', 2, 40);
		$v->rule('email', 'email')->message("e-mail is invalid");
		$v->rule('emailAvailable', 'email')->message("Email already taken");
		$v->rule('in', 'userrole', $userroles);
		
		return $this->validationResult($v);
	}
	
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

		return $this->validationResult($v);
	}
	
	public function username($username)
	{
		$v = new Validator($username);
		$v->rule('alphaNum', 'username')->message("Only alpha-numeric characters allowed");
		$v->rule('lengthBetween', 'username', 3, 20)->message("Length between 3 - 20"); 

		return $this->validationResult($v);
	}

	/**
	* validation for changing the password
	* 
	* @param array $params with form data.
	* @return obj $v the validation object passed to a result method.
	*/
	
	public function newPassword(array $params)
	{
		$v = new Validator($params);
		$v->rule('required', ['password', 'newpassword']);
		$v->rule('lengthBetween', 'newpassword', 5, 20);
		$v->rule('checkPassword', 'password')->message("Password is wrong");
		
		return $this->validationResult($v);
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
		$v->rule('lengthBetween', 'password', 5, 20);
		$v->rule('equals', 'passwordrepeat', 'password');
		
		return $this->validationResult($v);
	}

	/**
	* validation for system settings
	* 
	* @param array $params with form data.
	* @return obj $v the validation object passed to a result method.
	*/

	public function settings(array $params, array $copyright, array $formats, $name = false)
	{
		$v = new Validator($params);
		
		$v->rule('required', ['title', 'author', 'copyright', 'year', 'editor']);
		$v->rule('lengthBetween', 'title', 2, 50);
		$v->rule('lengthBetween', 'author', 2, 50);
		$v->rule('noHTML', 'title');
		# $v->rule('regex', 'title', '/^[\pL0-9_ \-]*$/u');
		$v->rule('regex', 'author', '/^[\pL_ \-]*$/u');
		$v->rule('integer', 'year');
		$v->rule('length', 'year', 4);
		$v->rule('length', 'langattr', 2);		
		$v->rule('in', 'editor', ['raw', 'visual']);
		$v->rule('values_allowed', 'formats', $formats);
		$v->rule('in', 'copyright', $copyright);
		$v->rule('noHTML', 'restrictionnotice');
		$v->rule('lengthBetween', 'restrictionnotice', 2, 1000 );
		$v->rule('email', 'recoverfrom');
		$v->rule('noHTML', 'recoversubject');
		$v->rule('lengthBetween', 'recoversubject', 2, 80 );
		$v->rule('noHTML', 'recovermessage');
		$v->rule('lengthBetween', 'recovermessage', 2, 1000 );
		$v->rule('iplist', 'trustedproxies');

		return $this->validationResult($v, $name);
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

	/**
	* validation for resort navigation
	* 
	* @param array $params with form data.
	* @return true or $v->errors with array of errors to use in json-response
	*/
	
	public function navigationSort(array $params)
	{
		$v = new Validator($params);
				
		$v->rule('required', ['item_id', 'parent_id_from', 'parent_id_to']);
		$v->rule('regex', 'item_id', '/^[0-9.]+$/i');
		$v->rule('regex', 'parent_id_from', '/^[a-zA-Z0-9.]+$/i');
		$v->rule('regex', 'parent_id_to', '/^[a-zA-Z0-9.]+$/i');
		$v->rule('integer', 'index_new');
		
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
	* validation for new navigation items
	* 
	* @param array $params with form data.
	* @return true or $v->errors with array of errors to use in json-response
	*/

	public function navigationItem(array $params)
	{
		$v = new Validator($params);

		$v->rule('required', ['folder_id', 'item_name', 'type', 'url']);
		$v->rule('regex', 'folder_id', '/^[0-9.]+$/i');
#		$v->rule('noSpecialChars', 'item_name');
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

	public function navigationBaseItem(array $params)
	{
		$v = new Validator($params);
						
		$v->rule('required', ['item_name', 'type', 'url']);
#		$v->rule('noSpecialChars', 'item_name');
		$v->rule('navigation', 'item_name');
		$v->rule('lengthBetween', 'item_name', 1, 40);
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
	
	/**
	* validation for dynamic fields ( settings for themes and plugins)
	* 
	* @param string $fieldName with the name of the field.
	* @param array or string $fieldValue with the values of the field.
	* @param string $objectName with the name of the plugin or theme.
	* @param array $fieldDefinitions with the field definitions as multi-dimensional array.
	* @return obj $v the validation object passed to a result method.
	*/
	
	public function objectField($fieldName, $fieldValue, $objectName, $fieldDefinitions, $skiprequired = NULL)
	{
		$v = new Validator(array($fieldName => $fieldValue));
		
		if(isset($fieldDefinitions['required']) && !$skiprequired)
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
			case "select":
				/* create array with option keys as value */
				$options = array();
				foreach($fieldDefinitions['options'] as $key => $value){ $options[] = $key; }
				$v->rule('in', $fieldName, $options);
				break;
			case "radio":
				$v->rule('in', $fieldName, $fieldDefinitions['options']);
				break;
			case "checkboxlist":				
				if(isset($fieldValue) && is_array($fieldValue))
				{
					/* create array with option keys as value */
					$options = array();
					foreach($fieldDefinitions['options'] as $key => $value){ $options[] = $key; }

					/* loop over input values and check, if the options of the field definitions (options for checkboxlist) contains the key (input from user, key is used as value, value is used as label) */
					foreach($fieldValue as $key => $value)
					{
						$v->rule('in', $key, $options);
					}
				}
				break;
			case "color":
				$v->rule('regex', $fieldName, '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/');
				break;
			case "email":
				$v->rule('email', $fieldName);
				break;
			case "date":
				$v->rule('date', $fieldName);
				break;
			case "checkbox":
				if(isset($fieldDefinitions['required']))
				{
				 	$v->rule('accepted', $fieldName);
				}
				break;
			case "url":
				$v->rule('url', $fieldName);
				$v->rule('lengthMax', $fieldName, 200);
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
			case "paragraph":
				$v->rule('noHTML', $fieldName);
				$v->rule('lengthMax', $fieldName, 10000);
				break;
			case "password":
				$v->rule('lengthMax', $fieldName, 100);
				break;
			case "image":
				$v->rule('noHTML', $fieldName);
				$v->rule('lengthMax', $fieldName, 1000);
				$v->rule('image_types', $fieldName);
				break;
			case "customfields":
				$v->rule('array', $fieldName);
				$v->rule('customfields', $fieldName);
				break;			
			default:
				$v->rule('lengthMax', $fieldName, 1000);
				$v->rule('regex', $fieldName, '/^[\pL0-9_ \-]*$/u');
		}
		return $this->validationResult($v, $objectName);
	}
	
	/**
	* result for validation
	* 
	* @param obj $v the validation object.
	* @return bool
	*/

	public function checkArray($arrayvalues, $v)
	{		
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