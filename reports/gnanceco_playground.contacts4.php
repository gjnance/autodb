
<?
if(isset($bInclude) && $bInclude) {
	echo "Export for Yahoo! Address Book";
	return;
}

include("../adb_config.php");
include("../adb_functions.php");

$adb_dblink = mysql_connect("localhost", "gnanceco_greg", "00zfdc");
mysql_select_db("gnanceco_playground");

// Debug causes the script to echo data instead of forcing a file download
$debug = 0;

$res = DBQuery("SELECT * FROM gnanceco_playground.contacts, gnanceco_playground.exports " .
	"WHERE contacts.id = exports.contact_id && exports.mobile = 1 && exports.user = 1");

if(!$res)
	die(mysql_error());

$content = '"First","Middle","Last","Nickname","Email","Category","Distribution Lists","Messenger ID","Home","Work","Pager","Fax","Mobile","Other","Yahoo! Phone","Primary","Alternate Email 1","Alternate Email 2","Personal Website","Business Website","Title","Company","Work Address","Work City","Work State","Work ZIP","Work Country","Home Address","Home City","Home State","Home ZIP","Home Country","Birthday","Anniversary","Custom 1","Custom 2","Custom 3","Comments","Messenger ID1","Messenger ID2","Messenger ID3","Messenger ID4","Messenger ID5","Messenger ID6","Messenger ID7","Messenger ID8","Messenger ID9","Skype ID","IRC ID","ICQ ID","Google ID","MSN ID","AIM ID","QQ ID"' . "\n";

while($row = mysql_fetch_assoc($res)) {
	$content .= '"' . $row['name_first'] . '",' .
		'"",' .
		'"' . $row['name_last'] . '",' .
		'"",' .
		'"' . $row['email1'] . '",' .
		'"",' .
		'"",' .
		'"",' .
		'"' . $row['phone_home'] . '",' .
		'"' . $row['phone_work'] . '",' .
		'"",' .
		'"' . $row['phone_fax'] . '",' .
		'"' . $row['phone_mobile'] . '",' .
		'"",' .
		'"",' .
		'"mobile",' .
		'"' . $row['email2'] . '",' .
		'"",' .
		'"",' .
		'"",' .
		'"",' .
		'"",' .
		'"",' .
		'"",' .
		'"",' .
		'"",' .
		'"",' .
		'"' . $row['street'] . '",' .
		'"' . $row['locality'] . '",' .
		'"' . $row['region'] . '",' .
		'"' . $row['postcode'] . '",' .
		'"' . $row['country_id'] . '",' .
		'"' . $row['birthdate'] . '",' .
		'"",' .
		'"",' .
		'"",' .
		'"",' .
		'"' . $row['notes'] . '",' .
		'"",' .
		'"",' .
		'"",' .
		'"",' .
		'"",' .
		'"",' .
		'"",' .
		'"",' .
		'"",' .
		'"",' .
		'"",' .
		'"",' .
		'"",' .
		'"",' .
		'"",' .
		'""' . "\n";
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
