<?php

if (!isset($argv[1])) {
	die("Usage: makevhosts <sites dir>\n");
}

$dir = $argv[1];

if (!is_dir($dir)) {
	die("Error: directory '$dir' doesn't exist.\n");
}

$sites = array();
$dp = opendir($dir);
while($item = readdir($dp)) {
	if ($item[0] == '.' || !is_dir($dir.$item)) {
		continue;
	}
	$sites[] = make_vhost(basename($item), realpath($dir));
}

echo join("\n\n", $sites);
exit;


function make_vhost($site, $site_dir)
{
	$site_path = $site_dir.'/'.$site;
	
	if (!is_dir($site_path)) {
		throw new Exception("Site dir {$site_path} does not exist.");
	}
	
	$dashes = str_repeat('-', 50-strlen($site));
	
	return "# {$site} {$dashes}
<VirtualHost *:80>
	DocumentRoot \"{$site_path}/htdocs\"
	ServerName {$site}
	ServerAlias *.{$site}
	ErrorLog {$site_path}/logs/error_log
	CustomLog {$site_path}/logs/access_log combined
</VirtualHost>";
}