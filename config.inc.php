<?php
// Live PHP Manual: configuration file

// Maximum size of results list
define('RESULTS_MAX_SIZE', 30);

// URL templates for the PHP manual
// The root of all pages
define('PHP_MANUAL_ROOT',  'http://localhost/Documentations/PHP/');
// Functions page template, replace extension with @
define('DEFAULT_FUNCTION', 'function.%s.@');
// Modules page default template, replace extension with @
define('DEFAULT_MODULE',   'book.%s.@');
// Manual pages extension, for ALL pages, .@ will be replaced by this value (initial dot required)
define('PHP_MANUAL_EXT',   '.html');

// Advanced settings
// File containing extra entries (see functions.inc.php and extra/ for details)
define('EXTRA_ENTRIES_FILE', dirname(__FILE__) . '/extra_entries');
// Files in the extra/ directory
define('EXTRA_PHP_INI_HTML', 'ini.list.html');
