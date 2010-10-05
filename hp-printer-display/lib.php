<?php

function run_weatherstation()
{
	$opts = getopt('t:m:');
	
	// Display usage.
	if (!isset($opts['t'])) {
		die("Usage: sethp.php -t <target>\n");
	}

	$temp = get_my_temperature();
	$printer->setDisplayMessage("Outside temp: {$temp}F");
}

function run_setmessage()
{
	$opts = getopt('t:m:');
	
	// Display usage.
	if (!isset($opts['t']) || !isset($opts['m'])) {
		die("Usage: sethp.php -t <target> -m <message>\n");
	}
	
	if (strlen($opts['m']) > 32) {
		die("Message is too long (".strlen($opts['m'])." chars). Must be 32 characters or less.\n");
	}
	
	$printer = new HpPrinter($opts['t']);
	$printer->setDisplayMessage($opts['m']);
}

function get_my_temperature($fahrenheit=true)
{
	$geoip =json_decode(
		file_get_contents('http://freegeoip.net/json/'),
		TRUE
	);
	
	$weather=json_decode(
		file_get_contents(
			'http://ws.geonames.org/findNearByWeatherJSON?'.
			http_build_query(
				array(
					'username'=>$geoip['ip'],
					'lat'=>$geoip['latitude'],
					'lng'=>$geoip['longitude']
				)
			)
		),
		TRUE
	);
	
	$temp_c = $weather['weatherObservation']['temperature'];
	
	if ($fahrenheit) {
		return ($temp_c*1.8)+32;
	} else {
		return $temp_c;
	}
}


class HpPrinter
{
	private $_host;
	private $_port;
	private $_sock;
	
	private $_escapeCommands;

	public function __construct($host, $port=9100)
	{
		$this->setHost($host, $port);
		$this->connect();
		
		// Some HP printers do not like escape codes. Some do.
		$this->_escapeCommands = true;
	}

	public function __destruct()
	{
		if (is_resource($this->_sock)) {
			fclose($this->_sock);
		}
	}
	
	public function setHost($host, $port=9100)
	{
		// Reset socket handle.
		if (is_resource($this->_sock)) {
			fclose($this->_sock);
		}
		$this->_sock = false;
		$this->_host = $host;
		$this->_port = $port;
	}
	
	public function connect()
	{
		$this->_sock = @fsockopen($this->_host, $this->_port, 
							      &$errno, &$errstr, 
							      $timeout=30);
		
		if (!$this->_sock) {
			throw new Exception("Failed to connect to HP printer: $errstr\n", $errno);			
		}
	}
		
	public function getDisplayMessage()
	{
		$this->sendCommand("INFO STATUS");
		
		// Printer reply ends with the following char.
		$termination_char = chr(12);
		$reply_buffer = '';
		
		// Read command result.
		while (!feof($this->_sock)) {
			$char = fgetc($this->_sock);
			if ($char == $termination_char) {
				break;
			}
			$reply_buffer .= $char;
		}
		
		// Parse command result.
		if ($lines = parse_ini_string($reply_buffer)) {
			if (isset($lines['DISPLAY'])) {
				return $lines['DISPLAY'];
			}
		}
		return false;
	}
	
	public function setDisplayMessage($message)
	{	
		$this->sendCommand(sprintf('RDYMSG DISPLAY="%s"', $message));
		
		if ($this->getDisplayMessage() == $message) {
			return true;
		} else {
			return false;
		}
	}
	
	public function sendCommand($command)
	{
		if ($this->_escapeCommands) {
			return fwrite($this->_sock, "\033%-12345X@PJL {$command}\r\n\033%-12345X\r\n");
		} else {
			return fwrite($this->_sock, "@PJL {$command}\r\n");		
		}
	}
}
