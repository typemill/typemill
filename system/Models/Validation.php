<?php

namespace Typemill\Models;

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
		Validator::langDir(__DIR__.'/../vendor/vlucas/valitron/lang'); // always set langDir before lang.
		Validator::lang('en');

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

		Validator::addRule('userAvailable', function($field, $value, array $params, array $fields)
		{
			$username = 'trendschau';
			if($username){ return false; }
			return true;
		}, 'taken');
		
		Validator::addRule('checkPassword', function($field, $value, array $params, array $fields)
		{
			if(password_verify($value, $fields['user_password'])){ return true; }
			return false;
		}, 'not valid');
		
		Validator::addRule('noHTML', function($field, $value, array $params, array $fields)
		{
			if ( $value == strip_tags($value) )
			{
				return true;
			}
			return false;
		}, 'contains html');
	}

	/**
	* validation for signup form
	* 
	* @param array $params with form data.
	* @return obj $v the validation object passed to a result method.
	*/
	
	public function validateSignin(array $params)
	{
		$v = new Validator($params);
		$v->rule('required', ['username', 'password'])->message("notwendig");
		$v->rule('alphaNum', 'username')->message("ungültig");
		$v->rule('lengthBetween', 'password', 5, 20)->message("Länge 5 - 20");
		$v->rule('lengthBetween', 'username', 5, 20)->message("Länge 5 - 20");
		
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
	
	public function validateSignup(array $params)
	{		
		$v = new Validator($params);
		$v->rule('required', ['signup_username', 'signup_email', 'signup_password'])->message("notwendig");
		$v->rule('alphaNum', 'signup_username')->message("ungültig");
		$v->rule('lengthBetween', 'signup_password', 5, 20)->message("Länge 5 - 20");
		$v->rule('lengthBetween', 'signup_username', 5, 20)->message("Länge 5 - 20"); 
		$v->rule('userAvailable', 'signup_username')->message("vergeben");
		$v->rule('email', 'signup_email')->message("ungültig");
		$v->rule('emailAvailable', 'signup_email')->message("vergeben");
		
		return $this->validationResult($v);
	}

	public function validateReset(array $params)
	{
		$v = new Validator($params);
		$v->rule('required', 'email')->message("notwendig");
		$v->rule('email', 'email')->message("ungültig");
		$v->rule('emailKnown', 'email')->message("unbekannt");
		
		return $this->validationResult($v);
	}

	/**
	* validation for changing the password
	* 
	* @param array $params with form data.
	* @return obj $v the validation object passed to a result method.
	*/
	
	public function validatePassword(array $params)
	{
		$v = new Validator($params);
		$v->rule('required', ['password', 'password_old']);
		$v->rule('lengthBetween', 'password', 5, 50);
		$v->rule('checkPassword', 'password_old');
		
		return $this->validationResult($v);
	}

	/**
	* validation for password reset
	* 
	* @param array $params with form data.
	* @return obj $v the validation object passed to a result method.
	*/
	
	public function validateResetPassword(array $params)
	{
		$v = new Validator($params);
		$v->rule('required', ['password', 'username']);
		$v->rule(['lengthBetween' => [['password', 5, 50], ['username', 3,20]]]);
		
		return $this->validationResult($v);
	}

	/**
	* validation for basic settings input
	* 
	* @param array $params with form data.
	* @return obj $v the validation object passed to a result method.
	*/

	public function settings(array $params, array $themes, array $copyright, $name = false)
	{
		$v = new Validator($params);
		
		$v->rule('required', ['title', 'author', 'copyright', 'year', 'theme']);
		$v->rule('lengthBetween', 'title', 2, 20);
		$v->rule('lengthBetween', 'author', 2, 40);
		$v->rule('regex', 'title', '/^[\pL0-9_ \-]*$/u');
		$v->rule('regex', 'author', '/^[\pL_ \-]*$/u');
		$v->rule('integer', 'year');
		$v->rule('length', 'year', 4);
		$v->rule('in', 'copyright', $copyright);
		$v->rule('in', 'theme', $themes);
		
		return $this->validationResult($v, $name);
	}
	
	public function pluginField($fieldName, $fieldValue, $pluginName, $fieldDefinitions)
	{	
		$v = new Validator(array($fieldName => $fieldValue));

		
		if(isset($fieldDefinitions['required']))
		{
			$v->rule('required', $fieldName);
		}
		
		switch($fieldDefinitions['type'])
		{
			case "select":
			case "radio":
			case "checkboxlist":
				$v->rule('in', $fieldName, $fieldDefinitions['options']);
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
		
		return $this->validationResult($v, $pluginName);
	}
	
	/**
	* validation for election input
	* 
	* @param array $params with form data.
	* @return obj $v the validation object passed to a result method.
	*/

	public function validateShowroom(array $params)
	{
		$v = new Validator($params);
		$v->rule('required', ['title']);
		$v->rule('lengthBetween', 'title', 2, 50);
		$v->rule('regex', 'title', '/^[^-_\-\s][0-9A-Za-zÄäÜüÖöß_ \-]+$/');
		$v->rule('integer', 'election' );
		$v->rule('email', 'email');
		$v->rule('length', 'invite', 40);
		$v->rule('alphaNum', 'invite');
		$v->rule('required', ['group_id', 'politician_id', 'ressort_id']);
		$v->rule('integer', ['group_id', 'politician_id', 'ressort_id']);
		
		return $this->validationResult($v);
	}


	public function validateGroup(array $params)
	{
//		$v->rule('date', 'deadline');
//		$v->rule('dateAfter', 'deadline', new \DateTime('now'));
//		$v->rule('dateBefore', 'deadline', new \DateTime($params['election_date']));

		return $this->validationResult($v);
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