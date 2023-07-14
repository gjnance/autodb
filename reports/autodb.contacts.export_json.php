<?php

// If bInclude is set, echo report title and exit.
if(isset($bInclude) && $bInclude) {
	echo "Export JSON";
	exit(0);
}

// Include adb configuration and necessary functions
include("../adb_config.php");
include("../adb_functions.php");

// Debug causes the script to echo data instead of forcing a file download
$debug = true;

// Connect to the database
$adb_dblink = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS);
mysqli_select_db($adb_dblink, AUTODB_DB);

$qDBTable = "autodb.contacts";
$qWhere = GetCachedVar($qDBTable, "where");
$qOrder = GetCachedVar($qDBTable, "order");
$qLimit = GetCachedVar($qDBTable, "limit");
$query = BuildQuery($joins, $where, $rcols);

$res = mysqli_query($adb_dblink, $query);
if(!$res)
	die(mysqli_error($adb_dblink));

$content = "Last Name, First Name, Email Address\n";

// Array to hold all rows
$rows = array();

while($row = mysqli_fetch_assoc($res)) {
	//$content .= "{$row['cst_name_last']},{$row['cst_name_first']},{$row['cst_email']}\n";
	$rows[] = $row;
}

if($debug) {
	$json = json_encode($rows, JSON_PRETTY_PRINT);
	echo "<pre>\n$json</pre>\n";
} else {
	$json = json_encode($rows);
	header("Cache-Control: no-store, no-cache, must-revalidate, private");
	header("Pragma: no-cache");
	header("Content-Type: application/json");
	header("Content-Length: " . strlen($json));
	header("Content-Disposition: attachment; filename=contacts.json");
	echo $json;
}
?>
