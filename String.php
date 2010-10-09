<?php

class String
{
	protected $string;
	protected $modified;
	
	public function __construct($str)
	{
		$this->string = $str;
		$this->modified = $str;
	}
	
	public function toUpper()
	{
		$this->modified = strtoupper($this->modified);
		return $this;
	}
	
	public function toLower()
	{
		$this->modified = strtolower($this->modified);
		return $this;
	}
	
	public function replaceAll($search, $replace)
	{
		$this->modified = str_replace($search, $replace, $this->modified);
		return $this;
	}
	
	public function reverse()
	{
		$this->modified = strrev($this->modified);
		return $this;		
	}
	
	public function matchesRegex($regex)
	{
		return preg_match("/{$regex}/", $this->modified);
	}
	
	public function equalTo($str)
	{
		if (is_object($str)) {
			return $str == $this;
		} else {
			return $str == $this->modified;
		}
	}
	
	public function explode($by, $max=false)
	{
		if ($max !== false) {
			return explode($by, $this->modified, $max);
		} else {
			return explode($by, $this->modified);
		}
	}
	
	public function escape()
	{
		return addslashes($this->modified);
	}
	
	public function utfencode()
	{
		return utf8_encode($this->modified);
	}
	
	public function repeat($times)
	{
		return str_repeat($this->modified, (int)$times);
	}
	
	public function stripHTML()
	{
		$this->modified = strip_tags($this->modified);
		return $this;
	}
	
	public function toHTML()
	{
		return htmlentities($this->modified);
	}
	
	public function find($str)
	{
		return strpos($this->modified, $str);
	}
	
	public function findAll($str, $case_sensitive=true)
	{
		$found = array();
		$offset = 0;
		$strpos = 'strpos';
		
		if (!$case_sensitive) {
			$strpos = 'stripos';
		}
		
		while(($pos = $strpos($this->modified, $str, $offset)) !== false) {
			$found[] = $pos;
			$offset = $pos+1;
		}
		
		if (count($found) < 1) {
			return false;
		} else {
			return $found;
		}
	}
	
	public function length()
	{
		return strlen($this->modified);
	}
	
	public function toInteger()
	{
		return intval($this->modified);
	}
	
	public function toFloat()
	{
		return floatval($this->modified);
	}
	
	public function email($to, $from, $subject=null)
	{
		if (is_null($subject)) {
			$subject = substr($this->modified, 0, 10).'...';
		}
		$headers = "From: $from";
		
		return mail($to, $subject, $this->modified, $headers);
	}
	
	public function original()
	{
		return $this->string;
	}

	public function reset()
	{
		$this->modified = $this->string;
		return $this;
	}
	
	public function words()
	{
		return preg_split("/\s+/", preg_replace('/[,.!:?;]/', '', $this->modified));
	}
	
	public function wordCount()
	{
		$counts = array_count_values($this->words());
		krsort($counts);
		return $counts;
	}
	
	public function sentences()
	{
		return preg_split("/[.!?]+\s*/", $this->modified, -1, PREG_SPLIT_NO_EMPTY);
	}
	
	public function toLines()
	{
		return preg_split("/[\r\n]+/", $this->modified);
	}
	
	public function translate($to='spanish', $from='english')
	{
		$langs = array(
			'english'=>'en',
			'spanish'=>'es',
			'japanese'=>'js',
			'chinese'=>'cn'
		);
		
		if (strlen($to) > 2) {
			if (isset($langs[$to])) {
				$to = $langs[$to];
			} else{
				return false;
			}
		}
		if (strlen($from) > 2) {
			if (isset($langs[$from])) {
				$from = $langs[$from];
			} else{
				return false;
			}
		}
		
		$lang_pair = sprintf("%s|%s", $from, $to);
		$payload   = array(
						'v'=>'1.0',
						'ie'=>'UTF8', 
						'q'=>utf8_encode(substr($this->modified, 0, 5000)),
						'langpair'=>$lang_pair
					);
		$url = sprintf("http://ajax.googleapis.com/ajax/services/language/translate?%s",
						http_build_query($payload)
					);
		$reply = json_decode(file_get_contents($url));
		
		if (isset($reply->responseData)) {
			$this->modified = $reply->responseData->translatedText;
		} else {
			throw new Exception("Failed to translate text.");
		}
		return $this;
	}
	
	public function saveTo($path)
	{
		return file_put_contents($path, $this->modified);
	}
	
	public static function fromURL($url, $stripHTML=false)
	{
		$data = file_get_contents($url);
		$s = new TrickyString($data);
		
		if ($stripHTML) {
			$s->stripHTML();
		}
		return $s;
	}
	
	public function __call($name, $args) {
		if (function_exists($name)) {
			array_unshift($args, $this->modified);
			$this->modified = call_user_func_array($name, $args);
			return $this;
		} else {
			throw new Exception("function '{$name}' does not exist.");
		}
	}
	
	public function __toString() {
		return $this->modified;
	}
}