#!/usr/bin/php -q
<?php

// csvdb.php - Imports a CSV into a new mySQL table.

ini_set('memory_limit', '250M');

// Usage.
if ($argc < 2) {
	die("Usage: csvdb <csv file>\n");
}

if ($opts = getopt("d:")) {
	$db_name = $opts['d'];
} else {
	$db_name = 'imports';
}

// Define database connection properties.
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', $db_name);

// Fancy footwork with argv.
if ($argv[1][0] == '-') {
	$prog_name = $argv[0];
	$argv = array_slice($argv, 3);
	array_unshift($argv, $prog_name);
}

// Do actual work.
db_connect();
import_csv(realpath($argv[1]));
exit;

function import_csv($file_path)
{
	$file_name = basename($file_path);

	// Enter main loop.
	if (!($fp = fopen($file_path, 'r'))) {
		die("Error: File cannot be opened.\n");
	}
	
	$header = fgetcsv($fp, 4056);
	$rows = array();
	
	if (($pos = array_search('id', $header)) !== false) {
		$header[$pos] = 'item_id';
	}
	
	while($row = fgetcsv($fp, 8112)) {
		$rows[] = array_combine($header, $row);
	}
	fclose($fp);
	
	$table_name = preg_replace('/[^a-zA-Z0-9_]+/', '', str_ireplace('.csv', '', $file_name));

	if (preg_match('/(_[0-9]+_[0-9]+)$/', $table_name, $matches)) {
		$table_name = str_replace($matches[1], '', $table_name);
	}
	
	$profile = profile_resultset($rows);	
	$table_sql = make_table_sql($table_name, $profile);
	
	kill_table($table_name);
	
	$res = mysql_query($table_sql);
	if (!$res) {
		throw new Exception("DB Error: ".mysql_error());
	}
	
	bulk_insert($table_name, $rows);
}

function db_connect()
{	
	// DB connect. Hurrah!
	if (!mysql_connect(DB_HOST, DB_USER, DB_PASS)) {
		throw new Exception("Failed to connect to db server.\n");
	}
	
	if (!mysql_select_db(DB_NAME)) {
		throw new Exception("Failed to select db.\n");
	}
}

function bulk_insert($table_name, array &$rows)
{
	$value_lines = array();
	
	$field_keys = array_keys($rows[0]);
	
	foreach($field_keys as $k=>$v) {
		$field_keys[$k] = clean_field_name($v);
	}
	
	$insert[] = "INSERT INTO {$table_name} \n";
	$insert[] = "(".join(', ', $field_keys).")";
	$insert[] = " VALUES ";
	
	// Loop through all rows.
	for($i=0, $total_rows = count($rows); $i < $total_rows; $i++) {
		
		$clean_row = array();
		foreach($rows[$i] as $key=>$value) {
			$clean_row[clean_field_name($key)] = "'".addslashes($value)."'";
		}
		
		$value_lines[] = "(".join(',', $clean_row).")";
		
		if (($i % 500) == 0 && $i > 0) {
			$insert_sql = join("\n", $insert) . join(",\n", $value_lines);
			if (!mysql_query($insert_sql) || mysql_affected_rows() <= 0) {
				throw new Exception("DB Error: ".mysql_error());
			}
			$value_lines = array();
		}
	}
	
	// For lack of a better idea.
	if (count($value_lines) > 0) {
		$insert_sql = join("\n", $insert) . join(",\n", $value_lines);
		if (!mysql_query($insert_sql) || mysql_affected_rows() <= 0) {
			throw new Exception("DB Error: ".mysql_error());
		}
	}
}

function kill_table($table_name)
{
	return @mysql_query("DROP TABLE {$table_name}");
}

function profile_resultset(array $rows)
{
	$profile = array();
	
	foreach($rows as $row) {
		foreach($row as $name=>$field) {
			
			$name = clean_field_name($name);
			
			// Initialize field profile with sensible defaults.
			if (!isset($profile[$name])) {
				$profile[$name] = array();
				$profile[$name]['required'] = true;
				$profile[$name]['length'] = 0;
				$profile[$name]['unsigned'] = false;
			}
			
			// Update field length.
			if ($profile[$name]['length'] < strlen($field)) {
				$profile[$name]['length'] = strlen($field);
			}
			
			// Determine whether field can be empty.
			if ($profile[$name]['required'] !== false && empty($field)) {
				$profile[$name]['required'] = false;
			}
			
			// Try to determine datatype. :)
			if (preg_match('/^[0-9\.\-]+$/', $field)) {
				if (!isset($profile[$name]['type'])) {
				
					// Double vs. Integer
					if (strpos($field, '.') !== false) {
						$profile[$name]['type'] = 'double';					
					} else {
						$profile[$name]['type'] = 'integer';
					}
					
					// Negative.
					if ($field[0] == '-') {
						$profile[$name]['unsigned'] = false;
					}
				}
				
			} else {
				// If we catch non-number characters, and the type is believed to be a number, update it to be a string.
				if (!isset($profile[$name]['type']) || $profile[$name]['type'] == 'integer' || $profile[$name]['type'] == 'float') {
					$profile[$name]['type'] = 'string';
				}
			}
		}
	}
	
	foreach($profile as &$field) {
		// Round up for padding.
		$remaining = 10 - ($field['length'] % 10);
		$field['length'] += $remaining;
		if ($field['type'] == 'string') {
			unset($field['unsigned']);
		}
	}
	
	return $profile;
}


function make_table_sql($table_name, array $profile)
{
	$sql = array();
	$sql[] = "CREATE TABLE `{$table_name}` (";
	$sql[] = "  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,";
	
	foreach($profile as $field_name=>$field) {
		
		$field_name = clean_field_name($field_name);
		
		$line = sprintf("  `%s`", $field_name);
		
		switch($field['type'])
		{
			case 'integer':				
				$line .= sprintf(" int(11)");

				if ($field['unsigned']) {
					$line .= " unsigned ";
				}

				break;
			case 'double':
				$line .= sprintf(" decimal(9,2)");
				
				if ($field['unsigned']) {
					$line .= " unsigned ";
				}
				
				break;
			case 'string':
				if ($field['length'] <= 255) {
					// For fields that have been empty but are still specified. Benefit of the doubt.
					if ($field['length'] == 0) {
						$field['length'] = 255;
					}
					
					$line .= sprintf(" varchar(%d)", $field['length']);
				} else {
					$line .= sprintf(" text");
				}
				break;
		}
		
		if ($field['required']) {
			$line .= " NOT NULL";
		} else {
			$line .= " DEFAULT NULL";
		}
		$sql[] = $line.",";
	}
	$sql[] = "  PRIMARY KEY (`id`)";
	$sql[] = ") ENGINE=InnoDB DEFAULT CHARSET=latin1";
	
	return join("\n", $sql);
}

function clean_field_name($field_name)
{		
	$field_name = str_replace(' ', '_', $field_name);
	$field_name = str_replace('-', '_', $field_name);
	$field_name = preg_replace('/[\(\)\/\"\']/', '', $field_name);
	$field_name = preg_replace('/[^(\x20-\x7F)]*/','', $field_name);
	$field_name = trim($field_name);
	
	return $field_name;
}