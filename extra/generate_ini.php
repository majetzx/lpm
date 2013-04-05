<?php
// Live PHP Manual: generate extra entries from ini.list.html.

// Script used to generate extra entries for php.ini directives.
// As there is no unique URL scheme for these entries, we parse ini.list.html to get
// directives URLs.
// The file ini.list.html is not provided, you can find it in the PHP manual.
// Of course, if the ini.list.html file format changes, this script won't work.

// This script is meant to be run from the CLI.

require '../config.inc.php';
require '../functions.inc.php';

if (!is_file(EXTRA_PHP_INI_HTML)) {
    echo 'No ' . EXTRA_PHP_INI_HTML . " file\n";
    exit;
}

$iniHTML = @file_get_contents(EXTRA_PHP_INI_HTML);
if ($iniHTML === false) {
    echo "Can't read " . EXTRA_PHP_INI_HTML . " file\n";
    exit;
}

// Goes to the table
$cPos = strpos($iniHTML, '<tbody');
if ($cPos === false) {
    echo "Can't find <tbody\n";
    exit;
}

// The very next </tbody means the end of the table
// If we exceed this position, we stop the loop
$endTBody = strpos($iniHTML, '</tbody', $cPos);

// Search for all directives
// Directives without a link won't be added
$directives = array();
while (true) {
    // A link
    $hrefPos = strpos($iniHTML, 'href=', $cPos);
    if ($hrefPos === false)
        break;

    // The link URL is between ""
    $startQuote = strpos($iniHTML, '"', $hrefPos);
    if ($startQuote === false)
        break;
    $startQuote++;
    $stopQuote = strpos($iniHTML, '"', $startQuote);
    if ($stopQuote === false)
        break;
    $href = substr($iniHTML, $startQuote, $stopQuote - $startQuote);

    // Now the directive, between ><
    $startDirective = strpos($iniHTML, '>', $stopQuote);
    if ($startDirective === false)
        break;
    $startDirective++;
    $stopDirective = strpos($iniHTML, '<', $startDirective);
    if ($stopDirective === false)
        break;
    $directive = substr($iniHTML, $startDirective, $stopDirective - $startDirective);

    $cPos = $stopDirective + 1;
    if ($cPos >= $endTBody)
        break;
    else {
        // The URL have a ".html" extension, we replace it with ".@"
        $directives[$directive] = 'i,' . str_replace('.html', '.@', $href);
    }
}

echo "File parsed\n";

// Now, add entries in the global extra entries file
// Or create the file if it doesn't exist
if (is_file(EXTRA_ENTRIES_FILE)) {
    $extraEntries = @file_get_contents(EXTRA_ENTRIES_FILE);
    if ($extraEntries === false) {
        echo "Can't read " . EXTRA_ENTRIES_FILE . " file\n";
        exit;
    }
    $extraEntriesArray = @unserialize($extraEntries);
    if (!is_array($extraEntriesArray)) {
        echo "Bad serialized format for " . EXTRA_ENTRIES_FILE . " file\n";
        exit;
    }
} else {
    $extraEntriesArray = array();
    if (!@touch(EXTRA_ENTRIES_FILE)) {
        echo "Can't create " . EXTRA_ENTRIES_FILE . " file\n";
        exit;
    }
}

$allExtraEntries = serialize(array_merge($extraEntriesArray, $directives));
if (@file_put_contents(EXTRA_ENTRIES_FILE, $allExtraEntries) === false) {
    echo "Can't write " . EXTRA_ENTRIES_FILE . " file\n";
    exit;
}

echo "php.ini directives merged into " . EXTRA_ENTRIES_FILE . "\n";