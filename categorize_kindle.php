<?php
//
// Descends through a directory of books, using the subdirectory names 
// to prefix the book names. e.g:
//     Books/Science/space_information.pdf
//             becomes
//     Kindle/[ Science ] space_information.pdf
//
$opts = getopt('f:t:c');

if (!isset($opts['f']) && !isset($opts['t'])) {
    die("Usage: categorize_kindle.php -f <from_dir> -t <to_kindle_dir>\n");
}

$from_dir = $opts['f'];
$to_dir = $opts['t'];

if (!is_dir($from_dir)) {
    fatal_error("From location must be a directory.");
}
if (!is_dir($to_dir)) {
    fatal_error("To location must be a directory.");
}

$files = prefix_files($from_dir);

foreach($files as $file) {
    $file['to'] = str_replace('//', '/', sprintf("%s/%s", $to_dir, $file['to']));

    printf("From: %s\nTo: %s\n\n", $file['from'], $file['to']);
    copy($file['from'], $file['to']);
}
echo "Done.\n";
exit;

function prefix_files($from_dir)
{
    $move = array();
    $from_dir = realpath($from_dir);

    $all = lsr($from_dir);

    foreach($all as $file) {

        $relative_path = str_replace($from_dir, '', $file);
        $parts = explode('/', $relative_path);

        if (empty($parts[0])) {
            array_shift($parts);
        }

        $ebook_file = array_pop($parts);
        $category = join(' - ', $parts);

        $new_path = sprintf("[ %s ] %s", $category, $ebook_file);

        $move[] = array('from'=>$file, 'to'=>$new_path);
    }
    return $move;
}

function lsr($dir)
{
    // Strip off ending / if it exists.
    if (substr($dir, -1) == '/') {
        $dir = substr($dir, 0, -1);
    }

    $ls_files = array();
    $files = glob("$dir/*");

    foreach($files as $file) {
        if (is_dir($file)) {
            $sub_files = lsr($file);
            $ls_files = array_merge($ls_files, $sub_files);
        } else {
            $ls_files[] = $file;
        }
    }
    return $ls_files;
}

function fatal_error($msg)
{
    sprintf(STDERR, "Error: %s\n", $msg);
    exit;
}
