#!/usr/bin/php -q
<?php

if (!isset($argv[1])) {
	die("Usage: make_dbphp <filename>\n");
}

$db_code = '
// Establish database connection.
$db_host  = "localhost";
$db_user  = "-";
$db_pass  = "-";
$database = "-";

$conn = mysql_connect($db_host, $db_user, $db_pass)
			or die("Failed to connect to db. ".mysql_error()."\n");

mysql_select_db($database)
	or die("Failed to connect to db. ".mysql_error()."\n");

// Perform query.

$sql = "SELECT * FROM test";

if ($res = mysql_query($sql)) {
	$rows = array();
	while($row = mysql_fetch_assoc($res)) {
		print_r($row);
	}
} else {
	die("Query error: ".mysql_error()."\n");
}
';

$db_code = sprintf('<'."?php\n%s", $db_code);
file_put_contents($argv[1], $db_code);