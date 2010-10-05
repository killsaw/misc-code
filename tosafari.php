#!/usr/bin/env php -q
<?php

$data = file_get_contents('php://stdin');
$tmpfile = tempnam('/tmp/', 'safari_').'.html';
file_put_contents($tmpfile, $data);
system("open -a Safari.app 'file://{$tmpfile}'");
