<?php
// Live PHP Manual: main page

require_once 'functions.inc.php';

// The form below has been submitted
if (!empty($_GET['q'])) {
	$q = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH|FILTER_FLAG_NO_ENCODE_QUOTES);
	
	// First, we search for functions, with or without parentheses.
	// If one and only one result is found, redirect to the manual page
	// For example, "date" and "date()" will redirect to "function.date.ext".
	// To go on "ref.datetime.ext", you must choose "date" in the list (JS required for this).
	$function = substr($q, -2) != '()' ? $q . '()' : $q;
	if (isset($allEntries[$function])) {
		header('Location: ' . getEntryURL($function));
		exit;
	}
	// Not an exact function name
	else {
		// Search for entries containing the searched string
		$results = searchEntries($q, 2);
		if (count($results) == 1)
		{
			header('Location: ' . getEntryURL($results[0]));
			exit;
		}
		
		// No result or multiple results, the list is displayed (see below)
	}
}

$version = '3.0-beta';
$headTitle = 'Live PHP Manual';
$title = $headTitle . ' (<a href="index.php?c">' . count($allEntries) . ' entries</a>)';

header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html>
<html dir="ltr">
	<head>
		<meta charset="utf-8">
		<title><?php echo $headTitle ?></title>
		<link rel="stylesheet" type="text/css" href="style.css">
		<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
		<script src="search.js"></script>
	</head>
	<body>
		<h1><?php echo $title; ?></h1>
<?php
// List of all entries containing the searched string in their name 
if (!empty($_GET['q'])) {
	$results = searchEntries($_GET['q']);
?>
		<div id="pseudo-form">
			<div id="pseudo-input"><?php echo htmlspecialchars($_GET['q']); ?><a href="index.php" title="clear form">←</a></div>
		</div>
		<ul>
<?php
	if (count($results) == 0)
		echo "			<li><em>No result</em></li>\n";
	else {
		foreach($results as $functionName) {
			$URL = getEntryURL($functionName);
			$shortType = getEntryType($functionName);
			$type = getTypeText($shortType);
			$functionName = strtr($functionName, '#', ' ');
			echo "			<li><a href=\"$URL\" title=\"Type: $type\" class=\"type_$shortType\">" . htmlspecialchars($functionName) . "</a></li>\n";
		}
	}
	echo "		</ul>\n";
}

// The complete list of all entries
else if (isset($_GET['c'])) {
?>
		<div id="pseudo-form">
			<div id="pseudo-input">(complete list) <a href="index.php" title="clear form">←</a></div>
		</div>
		<ul>
<?php
	foreach ($allEntries as $entry => $page) {
		$URL = getEntryURL($entry);
		$shortType = getEntryType($entry);
		$type = getTypeText($shortType);
		$entry = strtr($entry, '#', ' ');
		echo "			<li><a href=\"$URL\" title=\"Type: $type\" class=\"type_$shortType\">" . htmlspecialchars($entry) . "</a></li>\n";
	}
	echo "		</ul>\n";
}

// The form, with the search-as-you-type feature
else {
?>
		<form action="index.php" method="get" id="searchform">
			<input type="text" name="q" id="lpmq" size="30" maxlength="50" autocomplete="off">
		</form>
		<div id="lpms"></div>
		<script>
			$(document).ready(function()
      {
				document.getElementById('lpmq').focus();
				lpmSearchInit(<?php echo RESULTS_MAX_SIZE ?>);
      });
		</script>
<?php
}
?>
		<hr class="hidden" />
		<div id="help">
			<strong>Types</strong><br />
			<br />
			<tt>c</tt> &ndash; classes<br />
			<tt>C</tt> &ndash; constants<br />
			<tt>f</tt> &ndash; functions, methods<br />
			<tt>i</tt> &ndash; php.ini directives<br />
			<tt>k</tt> &ndash; keywords<br />
			<tt>m</tt> &ndash; modules<br />
			<tt>o</tt> &ndash; operators<br />
			<tt>s</tt> &ndash; syntax elements<br />
			<tt>t</tt> &ndash; types, casts<br />
			<tt>v</tt> &ndash; variables<br />
			<br />
			<strong>X,word</strong> to limit search to type <tt>X</tt>.<br />
			<strong>X,</strong> for all entries of type <tt>X</tt>.<br />
		</div>
		<hr class="hidden" />
		<div id="copy">Version <?php echo $version; ?>. Copyright &copy; 2005-2017 Maje/TZX.</div>
	</body>
</html>