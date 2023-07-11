<?
// Start buffering output
ob_start();

if(isset($bInclude) && $bInclude) {
	echo "Export Names and E-mails Only as CSV (Comma Separated Values)";
	return;
}

include("../adb_config.php");
include("../adb_functions.php");

// Debug causes the script to echo data instead of forcing a file download
$debug = 0;

$adb_dblink = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS);
mysqli_select_db($adb_dblink, "gnanceco_demo");

$qDBTable = "gnanceco_demo.customers";
$qWhere = GetCachedVar($qDBTable, "where");
$qOrder = GetCachedVar($qDBTable, "order");
$qLimit = GetCachedVar($qDBTable, "limit");
$query = BuildQuery($joins, $where, $rcols);

$res = mysqli_query($adb_dblink, $query);
if(!$res)
	die(mysqli_error($adb_dblink));

$content = "Last Name, First Name, Email Address\n";

while($row = mysqli_fetch_assoc($res)) {
	$content .= "{$row['cst_name_last']},{$row['cst_name_first']},{$row['cst_email']}\n";
}

if($debug) {
	echo nl2br($content);
} else {
	header("Cache-Control: no-store, no-cache, must-revalidate, private");
	header("Pragma: no-cache");
	header("Content-Type: text/csv");
	header("Content-Length: " . strlen($content));
	header("Content-Disposition: attachment; filename=contacts.csv");
	echo $content;
}
?>
