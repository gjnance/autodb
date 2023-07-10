
<?
if(isset($bInclude) && $bInclude) {
	echo "Export Selected as VCARDs for Sony Ericsson W810i";
	return;
}

include("../adb_config.php");
include("../adb_functions.php");

// Debug causes the script to echo vcards instead of forcing a file download
$debug = 0;

$adb_dblink = mysql_connect("localhost", "gnanceco_greg", "00zfdc");
mysql_select_db("gnanceco_playground");

$qDBTable = "gnance_playground.contacts";
$qWhere = GetCachedVar($qDBTable, "where");
$qWhere = ($qWhere ? " AND " : "") . " export_gregs_mobile = 1";
$qOrder = GetCachedVar($qDBTable, "order");
$qLimit = GetCachedVar($qDBTable, "limit");
$query = BuildQuery($joins, $where, $rcols);

$res = DBQuery($query);

$content = '';

function FormatPhone($number) {
	if (ereg("^\+1", $number)) {
		$number = ereg_replace("^\+1 *([0-9]{3}) *([0-9]{3})[ -]*([0-9]{4})", "(\\1) \\2-\\3", $number);
	} else if(ereg("^\+45", $number)) {
		$number = ereg_replace(" ", "", $number);
		$number = ereg_replace("^\+45", "0045", $number);
		}
	return $number;
}

while($row = mysql_fetch_assoc($res)) {
	foreach($row as $name=>$val)
		$row[$name] = ereg_replace("[\n\r]+", ", ", $val);

	$row['phone_mobile'] = FormatPhone($row['phone_mobile']);
	$row['phone_home'] = FormatPhone($row['phone_home']);
	$row['phone_work'] = FormatPhone($row['phone_work']);
	$row['phone_fax'] = FormatPhone($row['phone_fax']);

	$content .= "BEGIN:vCard
VERSION:2.1
N:{$row['name_last']};{$row['name_first']}
TEL;CELL:{$row['phone_mobile']}
TEL;HOME:{$row['phone_home']}
TEL;WORK:{$row['phone_work']}
TEL;FAX:{$row['phone_fax']}
END:vCard

";
//EMAIL;INTERNET,PREF:{$row['email1']}
//EMAIL;INTERNET:{$row['email2']}
// Address: PO box, extended addr, street addr, locality (city)
//          region (state/province), post code, country name
}

if($debug) {
	echo nl2br($content);
} else {
	header("Cache-Control: no-store, no-cache, must-revalidate, private");
	header("Pragma: no-cache");
	header("Content-Type: text/directory");
	header("Content-Length: " . strlen($content));
	header("Content-Disposition: attachment; filename=PB_Backup.vcf"); 
	echo $content;
}
?>
