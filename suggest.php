<?
// Include configuration file (mostly database stuff)
include "adb_config.php";

// Include functions
include "adb_functions.php";

// Make database connection
$adb_dblink = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS);

// Report all errors and warnings
error_reporting(E_ALL);

// Set an execution limit of 60 seconds
set_time_limit(60);

$qDBTable = GetVar('dbtable');
$qCol = GetVar('col');
$qValue = GetVar('value');

// Ensure all necessary variables were supplied
if(!$qDBTable || !$qCol)
	die("<div class=\"red\">Missing Parameters (dbtable, col, [value])</div>");

// Retrieve the rule for this table from the database
$query = "SELECT * FROM " . AUTODB_REL . " " .
	"WHERE adb_t1 = " . my_esc($qDBTable) . " && adb_t1_relcol = " . my_esc($qCol) . " LIMIT 1";

$relrow = DBQueryGetRow($query);

$qDBTable = $relrow['adb_t2'];

$t2_relcol = $relrow['adb_t2_relcol']; 
$t2_dspcol = $relrow['adb_t2_dspcol'];
$t2 = $relrow['adb_t2'];

// If the rule references data from a different server, make the connection
if($relrow['adb_t2_remhost'] && $relrow['adb_t2_remuser'])
	$rem_dblink = mysqli_connect($relrow['adb_t2_remhost'], $relrow['adb_t2_remuser'], $relrow['adb_t2_rempass']);

// Get last sort order and WHERE from preferences
$qOrder = GetCachedVar($t2, 'order');
$order = $qOrder ? ' ORDER BY ' . mysqli_escape_string($rem_dblink, $qOrder) : '';

$qWhere = GetCachedVar($t2, 'where');
//if (get_magic_quotes_gpc() && $qWhere)
//	$qWhere = stripslashes($qWhere);

// Strip "WHERE " if entered by the user
$qWhere = preg_replace("/^WHERE */", "", $qWhere);

// Append user's search text to the query
$qWhere = $qWhere . ($qWhere ? " && " : "") .
	mysqli_escape_string($adb_dblink, $t2_dspcol) . " LIKE '" . mysqli_escape_string($adb_dblink, $qValue) . "%'";

$where = $qWhere ? ' WHERE ' . $qWhere : '';

// Limit returned results to 15
$qLimit = "15";
$limit = ($qLimit && $qLimit != 'all') ? ' LIMIT ' . intval($qLimit) : '';

$query = "SELECT " . mysqli_escape_string($adb_dblink, $t2_relcol) . "," . mysqli_escape_string($adb_dblink, $t2_dspcol) . " " .
	"FROM " . mysqli_escape_string($adb_dblink, $t2) . $where . $order . $limit;

$rows = DBQueryGetRows($query, isset($rem_dblink) ? $rem_dblink : $adb_dblink);

if (count($rows)) {
	foreach($rows as $row) {
		echo '<div id="' . htmlspecialchars($row[$t2_relcol]) .
			'" style="cursor: pointer; width: 100%; font-style: italic; background: #FFFF99;" ' .
			'onMouseOver="HighlightRow(this); " ' .
			'onMouseDown="SelectRow(\'' . $qCol . '\', \'' . $row[$t2_dspcol] . '\', \'' . htmlspecialchars($row[$t2_relcol]) . '\');">';
		echo htmlspecialchars($row[$t2_dspcol]);
		echo '</div>';
	}
} else {
	if ($qValue)
		die('<div class="red">No entries found in ' . htmlspecialchars($t2) . ' that match "' .
			htmlspecialchars($qValue) . '"</div>');
	else
		die('<div class="red">Table ' . htmlspecialchars($t2) . ' is empty, please add something to it</div>');
}
?>