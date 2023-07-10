
<?
if(isset($bInclude) && $bInclude) {
	echo "Export Selected as Comma Separated Values (CSV - name/email only!)";
	return;
}

include("../adb_config.php");
include("../adb_functions.php");

// Debug causes the script to echo data instead of forcing a file download
$debug = 0;

$adb_dblink = mysql_connect("localhost", "gnanceco_greg", "00zfdc");
mysql_select_db("gnanceco_playground");

$qDBTable = "gnanceco_playground.contacts";
$qWhere = GetCachedVar($qDBTable, "where");
$qOrder = GetCachedVar($qDBTable, "order");
$qLimit = GetCachedVar($qDBTable, "limit");
$query = BuildQuery($joins, $where, $rcols);

$res = mysql_query($query);
if(!$res)
	die(mysql_error());

$content = "Last Name, First Name, Email Address\n";

while($row = mysql_fetch_assoc($res)) {
	if($row['email1'])
		$content .= "{$row['name_last']},{$row['name_first']},{$row['email1']}\n";
	if($row['email2'])
		$content .= "{$row['name_last']} (Alternate),{$row['name_first']},{$row['email2']}\n";
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
