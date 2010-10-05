#!/usr/bin/env php -q
<?php

$host     = "localhost";
$sockets  = array();
$max_sock = 20;

$sockets = open_sockets( $host, 80, $max_sock );
$count   = count( $sockets );

// Open sockets
print("$count sockets opened to $host:80! Let's do something..\n");
// Send requests
print("Sending page request on sockets (".send_requests($sockets, "/").").\n");
// Read replies
$replies = read_sockets($sockets);
print("Read ".count($replies)." read from server.\n");
// Close everything
print("Closing up shop (".close_sockets($sockets).")\n");

foreach( $replies as $reply ) {
	print("Md5 of reply: ".md5($reply)."\n");
}
unset($replies);


function open_sockets( $hostname, $port, $max_sock = 2 )
{
	$i = 0;

	while( $i++ < $max_sock ) {
		
		$sock = @fsockopen( $hostname, 80, $errno, $errstr, 30);
		if ($sock) {
			print("Socket[$i] connected. ($sock)\n");
			$sockets[] = $sock;
		} else {
			print("Socket failed: $errstr\n");
		}
	}
	return $sockets;
}

function send_requests( $socket_array, $url )
{
	$success = 0;

	extract(parse_url( $url ));
	for($i=0, $max=count($socket_array); $i < $max; $i++)
	{
		unset( $sock );
		$sock = $socket_array[$i];
		if ($sock) {
			if (fwrite($sock, "GET / HTTP/1.0\r\n\r\n"))
				$success++;
		}
	}
	return $success;
}

function read_sockets( $socket_array )
{
	$success = 0;

	for($i=0, $max=count($socket_array); $i < $max; $i++)
	{
		unset( $sock );
		$buffer = '';
		
		$sock = $socket_array[$i];
		
		if ($sock) {
			while(!feof($sock)) {
				$buffer .= fread($sock, 200);
			}
			
			if (!empty($buffer)) {
				list($header, $content) = explode("\r\n\r\n", $buffer, 2);
				$content = trim($content);
				$replies[] = $content;
				$success++;
			}
		}
	}
	
	return $replies;
}

function close_sockets( $socket_array )
{
	foreach( $socket_array as $socket ) { 
		if (fclose($socket))
			$success++;
	}
	return $success;
}
