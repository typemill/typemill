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

		Validator::addRule('userAvailable', function($field, $value, array $params, array $fields) use ($user)
		{
			$userdata = $user->getUser($value);
			if($userdata){ return false; }
			return true;
		}, 'taken');

		Validator::addRule('userExists', function($field, $value, array $params, array $fields) use ($user)
		{
			$userdata = $user->getUser($value);
			if($userdata){ return true; }
			return false;
		}, 'does not exist');
		
		Validator::addRule('checkPassword', function($field, $value, array $params, array $fields) use ($user)
		{
			$userdata = $user->getUser($fields['username']);
			if($userdata && password_verify($value, $userdata['password'])){ return true; }
			return false;
		}, 'wrong password');
		
		Validator::addRule('emailAvailable', function($field, $value, array $params, array $fields)
		{
			$email = 'testmail@gmail.com';
			if($email){ return false; }
			return true;
		}, 'taken');

		Validator::addRule('emailKnown', function($field, $value, array $params, array $fields)
		{
			$email = 'testmail@gmail.com';
			if(!$email){ return false; }
			return true;
		}, 'unknown');

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
			$value = preg_replace('/[````][\s\S]+?[````]/', '', $value);
			$value = preg_replace('/[```][\s\S]+?[```]/', '', $value);
			$value = preg_replace('/[``][\s\S]+?[``]/', '', $value);
			$value = preg_replace('/`[\s\S]+?`/', '', $value);
			$value = preg_replace('/>[\s\S]+?[\n\r]/', '', $value);
						
			if ( $value == strip_tags($value) )
			{
				return true;
			}
			return false;
		}, 'not secure. For code please use markdown `inline-code` or ````fenced code blocks````.');
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
		$v->rule('email', 'email')->message("e-mail is invalid");
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
		$v->rule('email', 'email')->message("e-mail is invalid");
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
	* validation for system settings
	* 
	* @param array $params with form data.
	* @return obj $v the validation object passed to a result method.
	*/

	public function settings(array $params, array $copyright, $name = false)
	{
		$v = new Validator($params);
		
		$v->rule('required', ['title', 'author', 'copyright', 'year']);
		$v->rule('lengthBetween', 'title', 2, 20);
		$v->rule('lengthBetween', 'author', 2, 40);
		$v->rule('regex', 'title', '/^[\pL0-9_ \-]*$/u');
		$v->rule('regex', 'author', '/^[\pL_ \-]*$/u');
		$v->rule('integer', 'year');
		$v->rule('length', 'year', 4);
		$v->rule('in', 'copyright', $copyright);
		
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
		$v->rule('noSpecialChars', 'item_name');
		$v->rule('lengthBetween', 'item_name', 1, 20);
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
	
	public function objectField($fieldName, $fieldValue, $objectName, $fieldDefinitions)
	{	
		$v = new Validator(array($fieldName => $fieldValue));

		
		if(isset($fieldDefinitions['required']))
		{
			$v->rule('required', $fieldName);
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
				/* create array with option keys as value */
				$options = array();
				foreach($fieldDefinitions['options'] as $key => $value){ $options[] = $key; }
				/* loop over input values and check, if the options of the field definitions (options for checkboxlist) contains the key (input from user, key is used as value, value is used as label) */
				foreach($fieldValue as $key => $value)
				{
					$v->rule('in', $key, $options);
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
				$v->rule('accepted', $fieldName);
				break;
			case "url":
				$v->rule('lengthMax', $fieldName, 200);
				$v->rule('url', $fieldName);
				break;
			case "text":
				$v->rule('lengthMax', $fieldName, 200);
				$v->rule('regex', $fieldName, '/^[\pL0-9_ \-\.\?\!]*$/u');
				break;
			case "textarea":
				$v->rule('lengthMax', $fieldName, 1000);
				$v->rule('noHTML', $fieldName);
				// $v->rule('regex', $fieldName, '/<[^<]+>/');
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
	
	public function validationResult($v, $name = false)
	{
		if($v->validate())
		{
			return true;
		}
		else
		{
			if($name)
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