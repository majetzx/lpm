<?php
// Live PHP Manual: performs searches and sends results as XML or JSON

require_once 'functions.inc.php';

// Use the query string
$queryString = filter_input(INPUT_SERVER, 'QUERY_STRING', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH|FILTER_FLAG_NO_ENCODE_QUOTES);

// Results can be sent back in XML or JSON format, the format is specified after a
// ";", XML is the default format if unspecified
if (strpos($queryString, ';') !== false) {
	list($query, $format) = explode(';', $queryString);
} else {
	$query = $queryString;
	$format = 'xml';
}

// Send Content-Type header, depending on format
if ($format == 'xml') {
	header('Content-Type: text/xml; charset=UTF-8');
	echo '<?xml version="1.0" encoding="UTF-8" ?>';
} else if ($format == 'json') {
	header('Content-Type: application/json; charset=UTF-8');
// Reject is the format is not supported
} else {
	exit;
}

// Do the search if the query is not empty
// The number of returned entries is limited by RESULTS_MAX_SIZE
if (!empty($query)) {
	$moreEntries = false;
	$results = searchEntries($query, RESULTS_MAX_SIZE, $moreEntries);
	$fullResults = array();
	foreach ($results as $entryName) {
		$fullResults[strtr($entryName, '#', ' ')] = array(
			'url'      => getEntryURL($entryName),
			'type'     => $type = getEntryType($entryName),
			'fullType' => getTypeText($type),
		);
	}
}

// Send the results in XML
if ($format == 'xml') {
	if (empty($query)) {
		echo '<result errcode="1" />';
	} else {
		// We have results
		if (count($fullResults) > 0) {
			echo '<result errcode="0">';
			foreach ($fullResults as $entryName => $entryDetails) {
				echo '<item><name>' . htmlspecialchars($entryName) . '</name>'
				   . "<type>{$entryDetails['type']}</type>"
				   . "<url>{$entryDetails['url']}</url>"
				   . "<title>Type: {$entryDetails['fullType']}</title></item>";
			}
			if($moreEntries)
				echo '<item><name>All results</name><type>n/a</type><url>index.php?q='.urlencode($query).'</url><title>All results</title></item>';
			echo '</result>';
		}
		// No result
		else {
			echo '<result errcode="2" />';
		}
	}
}

// Send the results in JSON
if ($format == 'json') {
	$jsonResults = array('query'=>$query, 'results'=>array());
	foreach ($fullResults as $entryName => $entryDetails)
		$jsonResults['results'][] = array('name'=>$entryName, 'type'=>$entryDetails['type'], 'url'=>$entryDetails['url'], 'title'=>$entryDetails['fullType']);
	if($moreEntries)
		$jsonResults['results'][] = array('name'=>'All results', 'type'=>'n/a', 'url'=>'index.php?q='.$query, 'title'=>'All results');
	echo json_encode($jsonResults);
}