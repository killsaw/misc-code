<?php
/**
  More user-friendly superglobals.

  Author: Steven Bredenberg
         <steven@killsaw.com>
**/
class Superglobal implements ArrayAccess, Serializable, Iterator
{
	private $position = 0;
	private $sg;
	
	public function __construct(array &$sg) {
		$this->sg = (array)$sg;
		$sg = $this;
	}
	
	public function offsetExists($offset) {
		return array_key_exists($offset, $this->sg);
	}
	
	public function offsetGet($offset) {
		if (isset($this->sg[$offset])) {
			return $this->sg[$offset];
		}
		return NULL;
	}
	
	public function offsetSet($offset, $value) {
		return $this->sg[$offset] = $value;
	}
	
	public function offsetUnset($offset) {
		unset($this->sg[$offset]);
	}
	
	public function serialize() {
		return serialize($this->sg);
	}
	
	public function unserialize($serialized) {
		$this->sg = unserialize($serialized);
	}
	
	public function rewind() {
        $this->position = 0;
    }

    public function current() {
    	$keys = array_keys($this->sg);
    	
    	if (!isset($keys[$this->position])) {
    		return null;
    	} else {
    		$key = $keys[$this->position];
        	return $this->sg[$key];
        }
    }

    public function key() {
    	$keys = array_keys($this->sg);
    	if (isset($keys[$this->position])) {
			return $keys[$this->position];
        } else {
        	return null;
        }
    }

    public function next() {
        ++$this->position;
    }

    public function valid() {
		$keys = array_keys($this->sg);
    	if (!isset($keys[$this->position])) {
    		return false;
    	} else {
    		$key = $keys[$this->position];
        	return isset($this->sg[$key]);
        }
    }
    
    public function stripHTML() {
    	foreach($this->sg as &$v) {
    		$v = strip_tags($v);
    	}
    }
    
    public function cast($keys, $type=null) {
    	
    	if (!is_array($keys)) {
    		$keys = array($keys=>$type);
    	}
    	foreach($keys as $key=>$type) {
			if (array_key_exists($key, $this->sg)) {
				settype($this->sg[$key], $type);
			}
		}
    }
    
    public function allow($vars) {
    	$allowed = array();
    	if (is_array($vars)) {
			foreach($vars as $k) {
				if (array_key_exists($k, $this->sg)) {
					$allowed[$k] = $this->sg[$k];
				}
			}
    	} else{
			if (array_key_exists($vars, $this->sg)) {
				$allowed[$vars] = $this->sg[$vars];
			}
    	}
    	$this->sg = $allowed;
    }
    
    public function __set_state($array)
    {
    	return $this->sg;
    }
    
    public function __toString() {
    	return $this->toString();
    }
    
    public function toString() {
    	return http_build_query($this->sg);
    }
}

/*
$_GET = new Superglobal($_GET);
$_GET->stripHTML();
$_GET->allow(array('t', 'test'));
$_GET->cast(array('t'=>'integer', 'test'=>'bool'));

echo $_GET;
*/