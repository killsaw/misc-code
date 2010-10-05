<?php

function parse_yaml($config_data)
{
	$config_lines = preg_split("/[\n\r]+/", $config_data);
	$conf = Array();

	foreach( $config_lines as $line ) {
		$line = trim($line);

		if (preg_match('/[\'"]+/', $line)) {
			$type = "string";
			$line = preg_replace('/[\'"]/', '', $line);

		} elseif (strstr( $line, '.' ))
			$type = "float";
		elseif (preg_match('/^(true|false)$/', $line))
			$type = "boolean";
		elseif (preg_match('/[\[\]]+/', $line))
			$type = "list";
		elseif (preg_match('/[\{\}]+/', $line))
			$type = "block";
		elseif (empty($line))
			$type = "null";
		else
			$type = "integer";

		// Regex: [name] : [value] (whitespace optional)
		if (preg_match("/(.*)\s?\:\s?(.*)?/", $line, $matches)) {
			list(, $prop, $value) = $matches;

			if ($value == '{') {
				// Start of named block, create a new hash element
				$root = $prop;
				if (!isset($conf[$root])) {
					$conf[$root] = array();
				}
			} elseif ($value == '[') {
				// Start of named list
				$list = $prop;
				$conf[$root][$list] = array();
			} else {
				// Property
				$conf[$root][$prop] = $value;
			}
		} elseif (!empty($line)) {
			if ($line == '}')
				unset($root);
			elseif ($line == ']')
				unset($list);
			else {
				settype($line, $type);
				$conf[$root][$list][] = $line;
			}
		}
	}
	return $conf;
}

// Sample Usage:

$config_data = '
fruit: {
	type: "apple"
	density: 5.7
	worms: [
		"randy"
		"susan"
		"george"
	]
}
';
// Clean up input
$config_data  = str_replace("\r", "\n", $config_data);

print("<pre>");
print_r(parse_yaml($config_data));
print("</pre>");

?>