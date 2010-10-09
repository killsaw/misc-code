<?php
/**
  DBC-style function parameter check class.

  Author: Steven Bredenberg
         <steven@killsaw.com>
**/
class Param
{
	static protected $callback = null;

	static public function required($value, $required_type, $conditions=null)
	{
		$required_type = strtolower($required_type);
		switch($required_type) {
			case 'int': 
				$required_type = 'integer'; 
				break;
			case 'float': 
				$required_type = 'double'; 
				break;
			case 'double': 
			case 'decimal':
				$required_type = 'double'; 
				break;
			case 'bool':
				$required_type = 'boolean';
				break;
		}
		
		$real_type = gettype($value);
		$ok = false;
				
		if ($required_type != $real_type) {	
			// Support class requirements.
			if ($real_type == 'object') {
				if (strtolower(get_class($value)) == $required_type) {
					$ok = true;
				}
				if (class_implements($value, $required_type)) {
					$ok = true;
				}
			}
			
			if (!$ok) {
				self::failure(sprintf("Type: %s != %s\n", $required_type, gettype($value)));
			}
		}
		
		if (!is_null($conditions)) {
			self::evalExpression($value, $real_type, $conditions);
		}
	}
	
	static public function ensure($var, $conditions=null) {
		return self::required($var, gettype($var), $conditions);
	}
	
	static protected function evalExpression($value, $type, $conditions)
	{
		$subconditions = explode(' AND ', $conditions);
		
		foreach($subconditions as $s) {
			if (is_string($value)) {
				$value = strlen($value);
			}
			
			$expression = 'return $value '."$s;";
			$result = eval($expression);
			
			if (!$result) {
				return self::failure("Passed parameter does not meet requirements. ($expression)");
			}
		}
	}
	
	static public function setFailureDelegate($callback) {
		if (is_callable($callback)) {
			self::$callback = $callback;
		} else {
			self::failure("Passed callback is not callable.");
		}
	}
	
	static public function failure($message) {
		if (is_callable(self::$callback)) {
			return self::$callback($message);
		} else {
			throw new Exception($message);
		}
	}
}

function example($var1, $var2, $var3)
{
	// Check vars
	Param::required($var1, 'float', '>=1.0 AND <= 54.3');
	Param::required($var2, 'bool');
	Param::required($var3, 'string', '!= "Wompus" AND > 10');
}

example(1.0, true, "Steven");
