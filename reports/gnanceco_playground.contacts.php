
<?
if(isset($bInclude) && $bInclude) {
	echo "Export Selected as VCARDs";
	return;
}

include("../adb_config.php");
include("../adb_functions.php");

// Debug causes the script to echo vcards instead of forcing a file download
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

$content = '';

while($row = mysql_fetch_assoc($res)) {
	foreach($row as $name=>$val)
		$row[$name] = ereg_replace("[\n\r]+", ", ", $val);

	$content .= "BEGIN:vCard
VERSION:3.0
FN:{$row['name_first']} {$row['name_last']}
N:{$row['name_last']};{$row['name_first']}
TEL;TYPE=cell:{$row['phone_mobile']}
TEL;TYPE=home:{$row['phone_home']}
TEL;TYPE=work:{$row['phone_work']}
TEL;TYPE=fax:{$row['phone_fax']}
ADR;TYPE=home:;;{$row['street']};{$row['locality']};{$row['region']};{$row['postcode']};{$row['country_id']}
EMAIL;TYPE=INTERNET,PREF:{$row['email1']}
EMAIL;TYPE=INTERNET:{$row['email2']}
END:vCard

";
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
	header("Content-Disposition: attachment; filename=contacts.vcf"); 
	echo $content;
}
?>
