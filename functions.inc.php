<?php
// Live PHP Manual: defines all entries, functions used by the search engine

require_once 'config.inc.php';


// **** HOW ENTRIES ARE STORED? ****

// All entries are kept in an array, keys are the entries, which are searched and
// displayed in the list. To allow a same keyword with multiple URLs, the "#"
// character can be used to add a description after the keyword, which will be
// displayed but not searched. For example, the "static" key is used for the
// procedural-style keyword while the "static#(OO)" key is used for the OO-style
// keyword, the latter will be displayed as "static (OO)" ("#" replaced by a space).
// Values contain the entry type and the entry page, separated by a comma. Types are:
//  c - classes
//  C - constants
//  f - functions, language constructs, OOP methods, anything with () at the end
//  i - php.ini directives
//  k - keywords, structures, anything that doesn't belong to others
//  m - modules, extensions
//  o - operators
//  s - syntax elements
//  t - types, casts
//  v - variables, anything starting with $
// For functions which page follows the default rule "function.<function-name>.ext",
// the page have to be empty (see the constant DEFAULT_FUNCTION). Functions must have
// parentheses at the end, to distinguish between functions and modules. For example,
// "iconv()" is the function and "iconv" is the module.
// Pages in the array have their extension replaced by the character @ (this character
// never appears in PHP manual file names), it will be replaced by the real extension
// as configured in the PHP_MANUAL_EXT constant, in the getEntryURL function.

// Anything can be added, as long as it respects this format.
$allEntries = array();


// **** FUNCTIONS ****
// Just add parentheses to function names.

$definedFunctions = get_defined_functions();
foreach ($definedFunctions['internal'] as $function)
	$allEntries[$function . '()'] = 'f,';


// **** MODULES ****
// Module pages are "book.<module-name>.ext", but some modules have a different page.
// For example, the "gd" module page is "book.image.ext".
// To add modules in the switch() below, there's two way, depending on modules:
//  - module with a different name
//      case '<module_name>': $module = '<module-name>'; break;
//  - module with no page
//      case '<module_name>'; continue 2;

$loadedModules = get_loaded_extensions();
foreach ($loadedModules as $module) {
	// The displayed module name, can be uppercase ("PDO", "SimpleXML")
	$originalModule = $module;
	// The module name used in URLs, always lowercase
	$module = strtolower($module);

	switch ($module) {
		// Renamed modules
		case 'apache2handler': $module = 'apache'; break;
		case 'bcmath':         $module = 'bc'; break;
		case 'bz2':            $module = 'bzip2'; break;
		case 'com_dotnet':     $module = 'com'; break;
		case 'date':           $module = 'datetime'; break;
		case 'db':             $module = 'dbm'; break;
		case 'gd':             $module = 'image'; break;
		case 'interbase':      $module = 'ibase'; break;
		case 'mime_magic':     $module = 'mime-magic'; break;
		case 'odbc':           $module = 'uodbc'; break;
		case 'pdo_dblib':      $module = 'pdo-dblib'; break;
		case 'pdo_firebird':   $module = 'pdo-firebird'; break;
		case 'pdo_informix':   $module = 'pdo-informix'; break;
		case 'pdo_mysql':      $module = 'pdo-mysql'; break;
		case 'pdo_oci':        $module = 'pdo-oci'; break;
		case 'pdo_odbc':       $module = 'pdo-odbc'; break;
		case 'pdo_pgsql':      $module = 'pdo-pgsql'; break;
		case 'pdo_sqlite':     $module = 'pdo-sqlite'; break;
		// Hidden modules
		case 'idn':             continue 2; // no ref page
		case 'openbase module': continue 2; // third party module (OpenBase SQL), all its functions are deleted below
		case 'reflection':      continue 2; // declared as a module, but there's no ref page, instead it's explained in the OOPv5 section below
		case 'standard':        continue 2; // not a real module
		// Some modules are probably missing, please tell me if you find one
		default: break;
	}
	$allEntries[$originalModule] = 'm,'. sprintf(DEFAULT_MODULE, $module);
}


// **** CLASSES ***
// Get declared classes to add their methods.
// Doesn't work so well (if at all), so it's disabled.

$definedClasses = get_declared_classes();
foreach ($definedClasses as $class) {
	// Some classes don't have a ref page
	if (($class == 'ArrayObject')) {
		continue;
	}
	
	$classMethods = get_class_methods($class);
	foreach ($classMethods as $method) {
		$entry = $class . '::' . $method . '()';
		
		// For URLs, trims underscores, then replace them with hyphen
		$_class  = strtolower(strtr(trim($class,  '_'), '_', '-'));
		$_method = strtolower(strtr(trim($method, '_'), '_', '-'));
		
		// Classes extending the Exception class don't have pages for the methods
		// so we redirect to the page in the manual, only for the parent methods
		if (preg_match('/Exception$/i', $class)) {
			if (($method == '__construct') || ($method == 'getMessage') || ($method == 'getCode')  || ($method == 'getFile')
				 || ($method == '__toString')  || ($method == 'getLine')    || ($method == 'getTrace') || ($method == 'getTraceAsString')) {
				$allEntries[$entry] = 'f,language.exceptions.@#language.exceptions.extending';
				continue;
			}
		}
		
		$allEntries[$entry] = 'f,function.' . $_class . '-' . $_method . '.@';
	}
}


// **** EXTRA ENTRIES ****
// If there's a file named 'extra_entries', we add it to the entries.
// The file must contain a serialized array, of the same form as the array described
// here.
// A file is provided, containing real URLs for php.ini directives.
// It's been generated by the script extra/generate_ini.php.

if (is_file(EXTRA_ENTRIES_FILE)) {
	$extraEntries = @file_get_contents(EXTRA_ENTRIES_FILE);
	if ($extraEntries !== false)
		$extraEntriesArray = @unserialize($extraEntries);
	if (is_array($extraEntriesArray)) {
		foreach ($extraEntriesArray as $entryName => $entryPage)
			$allEntries[$entryName] = $entryPage;
	}
}


// **** ANYTHING ELSE ***
// Finally, we add all the keywords, structures, etc. from the manual.
// Things not included in the additional entries:
//   - <?php, <?, <%, <?=, <%= and closing equivalents
//   - comments delimiters /*, */, // and #
//   - special characters "," and ";" (but the comma can be used in the search field, see searchEntries())
//   - the "use" keyword (which is not used in PHP)
//   - the cfunction & old_function keywords

$additionalEntries = array(
	// Types
	'boolean'  => 't,language.types.boolean.@',
	'TRUE'     => 'k,language.types.boolean.@',
	'FALSE'    => 'k,language.types.boolean.@',
	'integer'  => 't,language.types.integer.@',
	'float'    => 't,language.types.float.@',
	'double'   => 't,language.types.float.@', // same as float
	'NaN'      => 't,language.types.float.@#language.types.float.nan',
	'string'   => 't,language.types.string.@',
	"'"        => 's,language.types.string.@#language.types.string.syntax.single',
	'"'        => 's,language.types.string.@#language.types.string.syntax.double',
	'<<<'      => 's,language.types.string.@#language.types.string.syntax.heredoc',
	'<<<\''    => 's,language.types.string.@#language.types.string.syntax.nowdoc',
	'$#(parsing)' => 's,language.types.string.@#language.types.string.parsing.simple',
	'{$'       => 's,language.types.string.@#language.types.string.parsing.complex',
	'array'    => 't,language.types.array.@',
	'=>'       => 's,language.types.array.@#language.types.array.syntax',
	'['        => 's,language.types.array.@#language.types.array.syntax.modifying',
	'object'   => 't,language.types.object.@',
	'resource' => 't,language.types.resource.@',
	'NULL'     => 'k,language.types.null.@',
	// Pseudo-types
	'mixed'    => 't,language.pseudo-types.@#language.types.mixed',
	'number'   => 't,language.pseudo-types.@#language.types.number',
	'callback' => 't,language.pseudo-types.@#language.types.callback',
	'void'     => 't,language.pseudo-types.@#language.types.void',
	'...'      => 't,language.pseudo-types.@#language.types.dotdotdot',
	// Type juggling
	'(bool)'    => 't,language.types.boolean.@#language.types.boolean.casting',
	'(boolean)' => 't,language.types.boolean.@#language.types.boolean.casting',
	'(int)'     => 't,language.types.integer.@#language.types.integer.casting',
	'(integer)' => 't,language.types.integer.@#language.types.integer.casting',
	'(float)'   => 't,language.types.float.@#language.types.float.casting',
	'(double)'  => 't,language.types.float.@#language.types.float.casting',
	'(real)'    => 't,language.types.float.@#language.types.float.casting',
	'(string)'  => 't,language.types.string.@#language.types.string.casting',
	'(array)'   => 't,language.types.array.@#language.types.array.casting',
	'(object)'  => 't,language.types.object.@#language.types.object.casting',
	'(unset)'   => 't,language.types.null.@#language.types.null.casting',
	
	// Variables
	'$'      => 's,language.variables.@#language.variables.basics',
	'global' => 'k,language.variables.scope.@#language.variables.scope.global',
	'static' => 'k,language.variables.scope.@#language.variables.scope.static', // procedural style
	'$$'     => 's,language.variables.variable.@',
	
	// Constants
	'const'         => 'k,language.constants.syntax.@',
	'__LINE__'      => 'C,language.constants.predefined.@',
	'__FILE__'      => 'C,language.constants.predefined.@',
	'__DIR__'       => 'C,language.constants.predefined.@',
	'__FUNCTION__'  => 'C,language.constants.predefined.@',
	'__CLASS__'     => 'C,language.constants.predefined.@',
	'__TRAIT__'     => 'C,language.constants.predefined.@',
	'__METHOD__'    => 'C,language.constants.predefined.@',
	'__NAMESPACE__' => 'C,language.constants.predefined.@',

	// Operators
	'+'   => 'o,language.operators.arithmetic.@',
	'-'   => 'o,language.operators.arithmetic.@',
	'*'   => 'o,language.operators.arithmetic.@',
	'/'   => 'o,language.operators.arithmetic.@',
	'%'   => 'o,language.operators.arithmetic.@',
	'='   => 'o,language.operators.assignment.@',
	'+='  => 'o,language.operators.assignment.@',
	'-='  => 'o,language.operators.assignment.@',
	'*='  => 'o,language.operators.assignment.@',
	'/='  => 'o,language.operators.assignment.@',
	'%='  => 'o,language.operators.assignment.@',
	'&='  => 'o,language.operators.assignment.@',
	'|='  => 'o,language.operators.assignment.@',
	'^='  => 'o,language.operators.assignment.@',
	'<<=' => 'o,language.operators.assignment.@',
	'>>=' => 'o,language.operators.assignment.@',
	'= &' => 'o,language.operators.assignment.@#language.operators.assignment.reference',
	'&'   => 'o,language.operators.bitwise.@',
	'|'   => 'o,language.operators.bitwise.@',
	'^'   => 'o,language.operators.bitwise.@',
	'~'   => 'o,language.operators.bitwise.@',
	'<<'  => 'o,language.operators.bitwise.@',
	'>>'  => 'o,language.operators.bitwise.@',
	'=='  => 'o,language.operators.comparison.@',
	'===' => 'o,language.operators.comparison.@',
	'!='  => 'o,language.operators.comparison.@',
	'<>'  => 'o,language.operators.comparison.@',
	'!==' => 'o,language.operators.comparison.@',
	'<'   => 'o,language.operators.comparison.@',
	'>'   => 'o,language.operators.comparison.@',
	'<='  => 'o,language.operators.comparison.@',
	'>='  => 'o,language.operators.comparison.@',
	'?:'  => 'o,language.operators.comparison.@#language.operators.comparison.ternary',
	'@'   => 'o,language.operators.errorcontrol.@',
	'`'   => 'o,language.operators.execution.@',
	'++'  => 'o,language.operators.increment.@',
	'--'  => 'o,language.operators.increment.@',
	'and' => 'o,language.operators.logical.@',
	'or'  => 'o,language.operators.logical.@',
	'xor' => 'o,language.operators.logical.@',
	'!'   => 'o,language.operators.logical.@',
	'&&'  => 'o,language.operators.logical.@',
	'||'  => 'o,language.operators.logical.@',
	'.'   => 'o,language.operators.string.@',
	'.='  => 'o,language.operators.assignment.@',
	'instanceof' => 'o,language.operators.type.@',

	// Control structures
	'if'         => 'k,control-structures.if.@',
	'else'       => 'k,control-structures.else.@',
	'elseif'     => 'k,control-structures.elseif.@',
	// Alternative syntax for control structures
  'endif'      => 'k,control-structures.alternative-syntax.@',
  'endwhile'   => 'k,control-structures.alternative-syntax.@',
  'endfor'     => 'k,control-structures.alternative-syntax.@',
  'endforeach' => 'k,control-structures.alternative-syntax.@',
  'endswitch'  => 'k,control-structures.alternative-syntax.@',
	'while'      => 'k,control-structures.while.@',
	'do'         => 'k,control-structures.do.while.@',
	'for'        => 'k,control-structures.for.@',
	'foreach'    => 'k,control-structures.foreach.@',
	'as'         => 'k,control-structures.foreach.@',
	'break'      => 'k,control-structures.break.@',
	'continue'   => 'k,control-structures.continue.@',
	'switch'     => 'k,control-structures.switch.@',
	'case'       => 'k,control-structures.switch.@',
	'default'    => 'k,control-structures.switch.@',
	'declare'    => 'k,control-structures.declare.@',
	'goto'       => 'k,control-structures.goto.@',
	// Some language constructs, declared as functions
	'return()'       => 'f,',
	'require()'      => 'f,',
	'include()'      => 'f,',
	'require_once()' => 'f,',
	'include_once()' => 'f,',

	// Functions
	'function' => 'k,language.functions.@#functions.user-defined',
	'&$'       => 'o,functions.arguments.@#functions.arguments.by-reference',
	
	// Classes and Objects (PHP 5)
	'class'         => 'k,language.oop5.basic.@#language.oop5.basic.class',
	'$this'         => 'v,language.oop5.basic.@#language.oop5.basic.class',
	'new'           => 'k,language.oop5.basic.@#language.oop5.basic.new',
	'=&#(OO)'       => 'o,language.oop5.basic.@#language.oop5.basic.new',
	'->'            => 'o,language.oop5.basic.@#language.oop5.basic.class',
	'extends'       => 'k,language.oop5.basic.@#language.oop5.basic.extends',
	'__autoload()'  => 'f,language.oop5.autoload.@',
	'__construct()' => 'f,language.oop5.decon.@#language.oop5.decon.constructor',
	'__destruct()'  => 'f,language.oop5.decon.@#language.oop5.decon.destructor',
	'public'        => 'k,language.oop5.visibility.@',
	'private'       => 'k,language.oop5.visibility.@',
	'protected'     => 'k,language.oop5.visibility.@',
	'::'            => 'o,language.oop5.paamayim-nekudotayim.@',
	'self'          => 'k,language.oop5.paamayim-nekudotayim.@',
	'parent'        => 'k,language.oop5.paamayim-nekudotayim.@',
	'static#(OO)'   => 'k,language.oop5.static.@',
	'const#(OO)'    => 'k,language.oop5.constants.@',
	'abstract'      => 'k,language.oop5.abstract.@',
	'interface'     => 'k,language.oop5.interfaces.@',
	'implements'    => 'k,language.oop5.interfaces.@',
	'__set()'       => 'f,language.oop5.overloading.@#language.oop5.overloading.members',
	'__get()'       => 'f,language.oop5.overloading.@#language.oop5.overloading.members',
	'__isset()'     => 'f,language.oop5.overloading.@#language.oop5.overloading.members',
	'__unset()'     => 'f,language.oop5.overloading.@#language.oop5.overloading.members',
	'__call()'      => 'f,language.oop5.overloading.@#language.oop5.overloading.methods',
	'factory()'     => 'f,language.oop5.patterns.@#language.oop5.patterns.factory',
	'singleton()'   => 'f,language.oop5.patterns.@#language.oop5.patterns.singleton',
	'__sleep()'     => 'f,language.oop5.magic.@#language.oop5.magic.sleep',
	'__wakeup()'    => 'f,language.oop5.magic.@#language.oop5.magic.sleep',
	'__toString()'  => 'f,language.oop5.magic.@#language.oop5.magic.tostring',
	'__set_state()' => 'f,language.oop5.magic.@#language.oop5.magic.set-state',
	'final'         => 'k,language.oop5.final.@',
	'clone'         => 'k,language.oop5.cloning.@',
	'__clone()'     => 'f,language.oop5.cloning.@',
	
	// Reflection API
	'Reflection'          => 'c,language.oop5.reflection.@',
	'ReflectionException' => 'c,language.oop5.reflection.@#language.oop5.reflection.reflectionexception',
	'ReflectionFunction'  => 'c,language.oop5.reflection.@#language.oop5.reflection.reflectionfunction',
	'ReflectionParameter' => 'c,language.oop5.reflection.@#language.oop5.reflection.reflectionparameter',
	'ReflectionClass'     => 'c,language.oop5.reflection.@#language.oop5.reflection.reflectionclass',
	'ReflectionObject'    => 'c,language.oop5.reflection.@#language.oop5.reflection.reflectionobject',
	'ReflectionMethod'    => 'c,language.oop5.reflection.@#language.oop5.reflection.reflectionmethod',
	'ReflectionProperty'  => 'c,language.oop5.reflection.@#language.oop5.reflection.reflectionproperty',
	'ReflectionExtension' => 'c,language.oop5.reflection.@#language.oop5.reflection.reflectionextension',

	// Chapter 20. Exceptions
	'Exception' => 'c,language.exceptions.@',
	'try'       => 'k,language.exceptions.@',
	'catch'     => 'k,language.exceptions.@',
	'throw'     => 'k,language.exceptions.@',
	
	// Chapter 21. References Explained
	'=&'        => 'o,language.references.whatdo.@',
	'&$#(more)' => 'o,language.references.pass.@',

	// Chapter 38. Handling file uploads
	'UPLOAD_ERR_OK'         => 'C,features.file-upload.errors.@',
	'UPLOAD_ERR_INI_SIZE'   => 'C,features.file-upload.errors.@',
	'UPLOAD_ERR_FORM_SIZE'  => 'C,features.file-upload.errors.@',
	'UPLOAD_ERR_PARTIAL'    => 'C,features.file-upload.errors.@',
	'UPLOAD_ERR_NO_FILE'    => 'C,features.file-upload.errors.@',
	'UPLOAD_ERR_NO_TMP_DIR' => 'C,features.file-upload.errors.@',
	'UPLOAD_ERR_CANT_WRITE' => 'C,features.file-upload.errors.@',
	'UPLOAD_ERR_EXTENSION'  => 'C,features.file-upload.errors.@',

	// Chapter 43. Using PHP from the command line
	'STDIN'  => 'C,features.commandline.@#AEN8019',
	'STDOUT' => 'C,features.commandline.@#AEN8019',
	'STDERR' => 'C,features.commandline.@#AEN8019',

	// We add language constructs, just like functions
	'array()' => 'f,', 'die()'   => 'f,', 'echo()' => 'f,', 'empty()' => 'f,', 'eval()'  => 'f,',
	'exit()'  => 'f,', 'isset()' => 'f,', 'list()' => 'f,', 'print()' => 'f,', 'unset()' => 'f,',
	'__halt_compiler()' => 'f,', '__COMPILER_HALT_OFFSET__' => 'C,function.halt-compiler.@',

	// Appendix G. php.ini directives
	// See above

	// Appendix K. Predefined variables
	'$_SERVER'  => 'v,reserved.variables.@#reserved.variables.server',
	'$_ENV'     => 'v,reserved.variables.@#reserved.variables.environment',
	'$_COOKIE'  => 'v,reserved.variables.@#reserved.variables.cookies',
	'$_GET'     => 'v,reserved.variables.@#reserved.variables.get',
	'$_POST'    => 'v,reserved.variables.@#reserved.variables.post',
	'$_FILES'   => 'v,reserved.variables.@#reserved.variables.files',
	'$_REQUEST' => 'v,reserved.variables.@#reserved.variables.request',
	'$_SESSION' => 'v,reserved.variables.@#reserved.variables.session',
	'$php_errormsg' => 'v,reserved.variables.@#reserved.variables.phperrormsg',

	// Predefined classes
	'Directory'              => 'c,reserved.classes.@#reserved.classes.standard',
	'dir'                    => 'c,class.dir.@', // this class is instantiated from the previous one
	'stdClass'               => 'c,reserved.classes.@#reserved.classes.standard',
	'__PHP_Incomplete_Class' => 'c,reserved.classes.@#reserved.classes.standard',
	//'exception'              => 'c,reserved.classes.@#reserved.classes.php5', // this class is already declared above
	'php_user_filter'        => 'c,reserved.classes.@#reserved.classes.php5',
);


// Finally, add all additional entries to the main array
foreach ($additionalEntries as $entry => $Page)
	$allEntries[$entry] = $Page;

// Some functions are aliases for other functions, in this case a valid function
// name will lead to an invalid URL (404), so we fix the URL for these functions
// but only if they're defined. This mechanism can be used for any kind of entry.
// See http://www.zend.com/phpfunc/all_aliases.php
// Again, please tell me if some entries are missing here, thanks!
$alteredEntries = array(
	// Functions
	'dir()'                             => 'f,class.dir.@', // pseudo-object oriented mechanism for reading a directory
	'fbsql()'                           => 'f,function.fbsql-db-query.@',
	'fbsql_table_name()'                => 'f,function.fbsql-tablename.@',
	'imap_create()'                     => 'f,function.imap-createmailbox.@',
	'imap_fetchtext()'                  => 'f,function.imap-body.@',
	'imap_rename()'                     => 'f,function.imap-renamemailbox.@',
	'imap_scan()'                       => 'f,function.imap-listscan.@',
	'key_exists()'                      => 'f,function.array-key-exists.@', // aliased only in PHP 4.0.6
	'magic_quotes_runtime()'            => 'f,function.set-magic-quotes-runtime.@',
	'mbereg()'                          => 'f,function.mb-ereg.@',
	'mberegi()'                         => 'f,function.mb-eregi.@',
	'mberegi_replace()'                 => 'f,function.mb-eregi-replace.@',
	'mbereg_match()'                    => 'f,function.mb-ereg-match.@',
	'mbereg_replace()'                  => 'f,function.mb-ereg-replace.@',
	'mbereg_search()'                   => 'f,function.mb-ereg-search.@',
	'mbereg_search_getpos()'            => 'f,function.mb-ereg-search-getpos.@',
	'mbereg_search_getregs()'           => 'f,function.mb-ereg-search-getregs.@',
	'mbereg_search_init()'              => 'f,function.mb-ereg-search-init.@',
	'mbereg_search_pos()'               => 'f,function.mb-ereg-search-pos.@',
	'mbereg_search_regs()'              => 'f,function.mb-ereg-search-regs.@',
	'mbereg_search_setpos()'            => 'f,function.mb-ereg-search-setpos.@',
	'mbregex_encoding()'                => 'f,function.mb-regex-encoding.@',
	'mbsplit()'                         => 'f,function.mb-split.@',
	'memcache_add_server()'             => 'f,function.memcache-addserver.@',
	'memcache_get_extended_stats()'     => 'f,function.memcache-getextendedstats.@',
	'memcache_get_stats()'              => 'f,function.memcache-getstats.@',
	'memcache_get_version()'            => 'f,function.memcache-getversion.@',
	'memcache_set_compress_threshold()' => 'f,function.memcache-setcompressthreshold.@',
	'mysql()'                           => 'f,function.mysql-db-query.@',
	'mysql_dbname()'                    => 'f,function.mysql-result.@',
	'mysql_fieldflags()'                => 'f,function.mysql-field-flags.@',
	'mysql_fieldlen()'                  => 'f,function.mysql-field-len.@',
	'mysql_fieldname()'                 => 'f,function.mysql-field-name.@',
	'mysql_fieldtable()'                => 'f,function.mysql-field-table.@',
	'mysql_fieldtype()'                 => 'f,function.mysql-field-type.@',
	'mysql_freeresult()'                => 'f,function.mysql-free-result.@',
	'mysql_listdbs()'                   => 'f,function.mysql-list-dbs.@',
	'mysql_listfields()'                => 'f,function.mysql-list-fields.@',
	'mysql_listtables()'                => 'f,function.mysql-list-tables.@',
	'mysql_numfields()'                 => 'f,function.mysql-num-fields.@',
	'mysql_numrows()'                   => 'f,function.mysql-num-rows.@',
	'mysql_selectdb()'                  => 'f,function.mysql-select-db.@',
	'mysql_table_name()'                => 'f,function.mysql-result.@',
	'ocigetbufferinglob()'              => 'f,function.oci-lob-getbuffering.@', // not explicitly defined as an alias
	'ocipasswordchange()'               => 'f,function.oci-password-change.@',
	'ocisetbufferinglob()'              => 'f,function.oci-lob-setbuffering.@', // not explicitly defined as an alias
	'oci_free_collection()'             => 'f,function.ocifreecollection.@',
	'oci_free_cursor()'                 => 'f,function.oci-free-statement.@',
	'oci_free_descriptor()'             => 'f,function.ocifreedesc.@',
	'pdo_drivers()'                     => 'f,function.pdo-getavailabledrivers.@', // not explicitly defined as an alias
	'pg_clientencoding()'               => 'f,function.pg-client-encoding.@',
	'pg_cmdtuples()'                    => 'f,function.pg-affected-rows.@',
	'pg_errormessage()'                 => 'f,function.pg-last-error.@',
	'pg_exec()'                         => 'f,function.pg-query.@',
	'pg_fieldisnull()'                  => 'f,function.pg-field-is-null.@',
	'pg_fieldname()'                    => 'f,function.pg-field-name.@',
	'pg_fieldnum()'                     => 'f,function.pg-field-num.@',
	'pg_fieldprtlen()'                  => 'f,function.pg-field-prtlen.@',
	'pg_fieldsize()'                    => 'f,function.pg-field-size.@',
	'pg_fieldtype()'                    => 'f,function.pg-field-type.@',
	'pg_freeresult()'                   => 'f,function.pg-free-result.@',
	'pg_getlastoid()'                   => 'f,function.pg-last-oid.@',
	'pg_loclose()'                      => 'f,function.pg-lo-close.@',
	'pg_locreate()'                     => 'f,function.pg-lo-create.@',
	'pg_loexport()'                     => 'f,function.pg-lo-export.@',
	'pg_loimport()'                     => 'f,function.pg-lo-import.@',
	'pg_loopen()'                       => 'f,function.pg-lo-open.@',
	'pg_loreadall()'                    => 'f,function.pg-lo-read-all.@',
	'pg_loread()'                       => 'f,function.pg-lo-read.@',
	'pg_lounlink()'                     => 'f,function.pg-lo-unlink.@',
	'pg_lowrite()'                      => 'f,function.pg-lo-write.@',
	'pg_numfields()'                    => 'f,function.pg-num-fields.@',
	'pg_numrows()'                      => 'f,function.pg-num-rows.@',
	'pg_result()'                       => 'f,function.pg-fetch-result.@',
	'pg_setclientencoding()'            => 'f,function.pg-set-client-encoding.@',
	'posix_errno()'                     => 'f,function.posix-get-last-error.@',
	'set_socket_blocking()'             => 'f,function.socket-set-blocking.@',
	'socket_getopt()'                   => 'f,function.socket-get-option.@',
	'socket_setopt()'                   => 'f,function.socket-set-option.@',
	'_()'                               => 'f,function.gettext.@',
	// Class methods
	'Directory::close()'                => 'f,class.dir.@',
	'Directory::read()'                 => 'f,class.dir.@',
	'Directory::rewind()'               => 'f,class.dir.@',
);
foreach($alteredEntries as $entryName => $entryURL) {
	if(isset($allEntries[$entryName]))
		$allEntries[$entryName] = $entryURL;
}

// Some functions (or other entries) are not documented, we delete their entry.
$deletedEntries = array(
	'fbsql_rows_fetched()',
	'fbsql_set_characterset()',
	'imap_savebody()',
	'iterator_apply()',
	'mysqli_embedded_server_end()',
	'mysqli_embedded_server_start()',
	'mysqli_get_charset()',
	'mysqli_get_warnings()',
	'mysqli_set_local_infile_default()',
	'mysqli_set_local_infile_handler()',
	'mysqli_slave_query()',
	'mysqli_stmt_attr_get()',
	'mysqli_stmt_attr_set()',
	'mysqli_stmt_field_count()',
	'mysqli_stmt_get_warnings()',
	'mysqli_stmt_insert_id()',
	'openssl_csr_get_public_key()',
	'openssl_csr_get_subject()',
	'openssl_pkey_get_details()',
	'php_egg_logo_guid()', // alternate version of the PHP logo
	'php_real_logo_guid()', // returns the same ID as php_logo_guid()
	'posix_initgroups()',
	'snmp2_get()',
	'snmp2_getnext()',
	'snmp2_real_walk()',
	'snmp2_set()',
	'snmp2_walk()',
	'snmp3_get()',
	'snmp3_getnext()',
	'snmp3_real_walk()',
	'snmp3_set()',
	'snmp3_walk()',
	'snmp_set_oid_output_format()',
);
foreach($deletedEntries as $entryName) {
	if(isset($allEntries[$entryName]))
		unset($allEntries[$entryName]);
}

// Some modules aren't in the manual (i.e. third party modules), we delete functions
// for these modules
$deletedModules = array(
	'openbase module',
);
foreach($deletedModules as $module) {
	$functions = get_extension_funcs($module);
	if(!$functions)
		continue;
	foreach($functions as $function) {
		$function .= '()';
		if(isset($allEntries[$function]))
			unset($allEntries[$function]);
	}
}

// We sort entries, so that results returned by searchEntries() will already be
// sorted
uksort($allEntries, 'strnatcasecmp');

// Case-insensitive search in PHP 5
if (function_exists('stripos'))
	$findPosition = 'stripos';
else
	$findPosition = 'strpos';


/**
 * Search for entries containing a string in their name.
 * 
 * It returns an indexed array, values are the entries.
 * 
 * The string can contain a type of entries to limit search to. In this case, the string is of the form
 * "t,string". Finally, to search all entries of a given type, the string has to be of the form "t,".
 * Types are detailed at the top of this script.
 * 
 * @param string  $string the string to search in all entries
 * @param integer $resultsMaxSize the maximum number of entries to return, or null for all entries found
 * 
 * @return array an array containing all entries matching the string
 * 
 * @since 1.0
 */
function searchEntries($string, $resultsMaxSize = null, &$moreEntries = null)
{
	global $allEntries, $findPosition;
	
	$results = array();
	$string = stripslashes(trim($string));
	if ($string == '')
		return $results;
	
	// If the query is of the form "x," or "x,yyyy"
	// x is a type (see above) and yyyy the (optional) real string
	if (preg_match('/^([cCfikmostv]),(.*)/', $string, $matches)) {
		$type = $matches[1];
		$string = $matches[2];
	}
	else
		$type = '';
	
	$moreEntries = false;
	foreach ($allEntries as $entryName => $entryURL) {
		$entryType = $entryURL[0];
		if (strpos($entryName, '#') !== false) {
			$realEntryName = $entryName;
			$entryName = substr($entryName, 0, strpos($entryName, '#'));
		}
		else
			unset($realEntryName);
		
		// We add the entry to the results if:
		// - there is no type and the string is found
		// - or types match and the string is found
		// - or types match and the string is empty (i.e. "C," for all constants)
		// We also test if we have reached the maximum number of entries requested
		// In this case, the $moreEntries flag is set and the loop is ended
		$found = $findPosition($entryName, $string) !== false;
		if (($type == '' && $found) || ($type == $entryType && ($found || ($string == '')))) {
			if($resultsMaxSize == null)
				$results[] = isset($realEntryName) ? $realEntryName : $entryName;
			else {
				if(count($results) < $resultsMaxSize)
					$results[] = isset($realEntryName) ? $realEntryName : $entryName;
				else {
					$moreEntries = true;
					break;
				}
			}
		}
	}
	
	return $results;
}


/**
 * Returns the manual page URL for an entry.
 * 
 * For functions, parentheses must be present.
 * 
 * @param string $entryName the entry to get URL
 * 
 * @return string the entry URL
 * 
 * @since 2.0
 */
function getEntryURL($entryName)
{
	// Look in the array
	$entryPage = getEntryPage($entryName);
	
	// For functions the page is empty, we make it according to the PHP manual rules
	if ($entryPage == '') {
		// Remove trailing parentheses, leading and trailing underscores
		// Replace underscores with hyphens
		$functionName = strtr(trim(substr($entryName, 0, -2 ), '_' ), '_', '-');
		$entryPage = sprintf(DEFAULT_FUNCTION, $functionName);
	}
	
	// Replace the extension
	return PHP_MANUAL_ROOT . str_replace('.@', PHP_MANUAL_EXT, $entryPage);
}


/**
 * Returns the page for an entry.
 * 
 * Returns an empty string for functions with a page of the form DEFAULT_FUNCTION.
 * The page doesn't include the PHP manual URL, the extension is replaced by ".@".
 * 
 * @param string $entryName the entry to get page
 * 
 * @see getEntryURL
 * 
 * @since 2.0
 */
function getEntryPage($entryName)
{
	global $allEntries;
	return substr($allEntries[$entryName], 2);
}


/**
 * Returns an entry's type.
 * 
 * @param string $entryName the entry to get type
 * 
 * @return string the entry type, a single character
 * 
 * @see getTypeText
 * 
 * @since 2.0
 */
function getEntryType($entryName)
{
	global $allEntries;
	return $allEntries[$entryName][0];
}


/**
 * Return a type's text.
 * 
 * See the comment at the top of this script for full details
 * 
 * @param string $type a single character representing a type
 * 
 * @return string a word or two representing the type
 * 
 * @see getEntryType
 * 
 * @since 2.0
 */
function getTypeText($type)
{
	switch ($type) {
		case 'c': $text = 'class'; break;
		case 'C': $text = 'constant'; break;
		case 'f': $text = 'function/method'; break;
		case 'i': $text = 'ini directive'; break;
		case 'k': $text = 'keyword/language'; break;
		case 'm': $text = 'module'; break;
		case 'o': $text = 'operator'; break;
		case 's': $text = 'syntax'; break;
		case 't': $text = 'type/cast'; break;
		case 'v': $text = 'variable'; break;
		default:  $text = 'unknown'; break;
	}
	return $text;
}
?>