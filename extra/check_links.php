<?php
// Live PHP Manual: checks each entry's link.

// Script used to check that the links for all the entries are correct.
// Invalid links are displayed.

// This script is meant to be run from the CLI.

require '../config.inc.php';
require '../functions.inc.php';

foreach ($allEntries as $entryName => $entryURL) {
    $entryURL = getEntryURL($entryName);
    if(@fopen($entryURL, 'r') === false) {
        echo "$entryName => $entryURL\n";
    }
}
