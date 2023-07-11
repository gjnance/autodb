<?php
// AutoDB - Allows a user to browse through a MySQL server's databases and their tables. Also provides a
// rudimentary form for inserting data into any of the tables of the database
//
// WARNING: This script is intentionally insecure and would quite easily, and purposefully, allow injection
// of arbitrary SQL statements. It should always be kept under access control of some kind (ie .htaccess)
//
// Icons courtesy of http://tango.freedesktop.org/Tango_Icon_Gallery

// Include configuration file (mostly database stuff)
include "adb_config.php";

// Include functions
include "adb_functions.php";

// Report all errors and warnings
error_reporting(E_ALL);

// Set an execution limit of 60 seconds
set_time_limit(60);

mysqli_report(MYSQLI_REPORT_OFF);

// Make database connection
$adb_dblink = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS);

if (!$adb_dblink) {
	die(Error('Connection error: ' . mysqli_connect_error()));
}

// Start buffering output
ob_start();

$debug = false;

// Diagnostic information
if ($debug) {
	echo "POST:<br>\n";
	html_print_r($_POST);
	echo "GET: ";
	html_print_r($_GET);
}

$bForceDB = strlen(AUTODB_DB) > 0;

// Get variables from POST or GET
$qDatabase = $bForceDB ? AUTODB_DB : GetCachedVar("", 'db');
$qTable = GetCachedVar("", 'table');
$qDBTable = ($qDatabase && $qTable ? $qDatabase . "." . $qTable : '');
$qDBAction = GetVar('dbaction');
$qDeleteRow = GetVar('deleterow');
$qCopyRow = GetVar('copyrow');
$qLimit = GetCachedVar($qDBTable, 'limit', 100);
$qWhere = GetCachedVar($qDBTable, 'where');
$qOrder = GetCachedVar($qDBTable, 'order');
$qTitle = GetVar('title');
$qCols = explode(",", GetVar('cols', '*'));
$qExport = GetVar('export');
$qS = GetVar('s');
$reports = GetReports($qDBTable);

// If there are GET parameters, display a simple report showing the requested database and table
$bReport = isset($_GET['title']) ? TRUE : FALSE;

// WARNING: This script is *INTENTIONALLY* unsafe to allow user to inject arbitrary SQL so that they can, for example,
// enter something like "account_username like '%foo%'" without having it be changed to "... \'%foo\'",
//if (get_magic_quotes_gpc() && $qWhere)
//	$qWhere = stripslashes($qWhere);

// Strip "WHERE " if entered by the user
$qWhere = preg_replace("/^WHERE */", "", $qWhere);

// If database was selected, get a list of available tables
$db_tables = array();

if ($qDatabase) {
	$db_tables = GetTables($qDatabase);

	// If table is invalid for the specified database, reset table specific variables
	if (!in_array($qTable, $db_tables))
		$qTable = $qOrder = $qReverse = $qDBTable = '';
}

// Default action is 'select' if none was provided
if (($qDatabase && $qTable && !$qDBAction) || $bReport)
	$qDBAction = 'select';

// Action 'Export' is just a special case of select
$bExport = false;
$csvdata = '';
if ($qDBAction == 'export') {
	$bExport = true;
	$qDBAction = 'select';
}

// Number of Primary key values that are present in the POST data. This is used to
// determine whether we have sufficient data to allow UPDATE or DELETE commands.
$nKeysPresent = 0;

// If database and table have been selected, get data about the table
if ($qDatabase && $qTable) {

	// Get a description of the fields in the table
	$fields = GetTableFields($qDatabase . "." . $qTable);

	// Build a list of columns comprising the primary key for the table
	$pkey_cols = array();

	// A WHERE statement for use on UPDATE or DELETE
	$key_where = '';

	foreach($fields as $field) {
		if ($field->flags & 2)
			array_push($pkey_cols, $field->name);

		// Check for date or time fields and reconstruct MySQL strings from component form fields
		if (!isset($_POST['_adb_' . $field->name])) {
			if (preg_match("/^(time|date|datetime|timestamp)/", $field->type)) {
				$_POST['_adb_' . $field->name] = ConstructDateField($field->type, $field->name);
			}
			else if (preg_match("/^year/", $field->type))
				$_POST['_adb_' . $field->name] = isset($_POST['_adb_' . $field->name . '_Y']) ?
					$_POST['_adb_' . $field->name . '_Y'] : '';
		}

		if (isset($_POST['_adbkeycol_' . $field->name]) && strlen($_POST['_adbkeycol_' . $field->name])) {
			$key_where .= ($key_where ? ' AND ' : '') . $field->name . ' = ' .
				my_esc($_POST['_adbkeycol_' . $field->name]);
			$nKeysPresent++;
		}
	}

	// If any primary keys were missing from POST data, do not allow UPDATE or DELETE
	if (!$nKeysPresent || $nKeysPresent != count($pkey_cols))
		$key_where = '';
}

$title = 'AutoDB' . ($qDatabase ? " :: $qDatabase" : "") . ($qTable ? " :: $qTable" : "") .
	($qWhere ? ' :: WHERE ' . $qWhere : '');

// Start HTML output
?>
<html>
<title><?php $title ?></title>
<link href="adb.css" rel="stylesheet" type="text/css">
<body background="gfx/adb-background.gif">
<script language="JavaScript" src="<?php AUTODB_BASEURL ?>/adb_js.php"></script>

<?php
// INSERT or UPDATE action?
$status = "";
if ($qS) {
	if ($key_where && !$qCopyRow) {
		// Update. In order to preserve fields that don't update politely when data is simply displayed directly
		// from the database to the user (for example, a password), only update values that have changed in the row.
		$query = "SELECT * FROM " . $qDBTable . " WHERE " . $key_where . " LIMIT 1";

		$row = mysqli_fetch_assoc(mysqli_query($adb_dblink, $query));

		$query = 'UPDATE ' . mysqli_escape_string($adb_dblink, $qDBTable) . ' SET ';

		$q_fields = '';

		foreach ($fields as $field) {
			$type = isset($_POST['_adb_' . $field->name . '_type_']) ? $_POST['_adb_' . $field->name . '_type_'] : '';

			// If type is "Specify", treat like any other field
			if ($type == "Specify")
				$type = "";

			if ($type || (isset($_POST["_adb_" . $field->name]) && $_POST["_adb_" . $field->name] != $row[$field->name])) {

				$q_fields .= ($q_fields ? ',' : '');

				if ($type == "Now")
					$q_fields .= $field->name . "=NOW()";
				else if ($type == "Empty" || $type == "Default")
					$q_fields .= $field->name . "=''";
				// TODO: This is lame. Do something clever with password fields besides guessing based on the field name
				// Perhaps allow a function to be selected from a drop down box or something?
				else if (preg_match("/password/", $field->name))
					$q_fields .= $field->name . "=PASSWORD(" . my_esc($_POST["_adb_" . $field->name]) . ")";
				else
					$q_fields .= $field->name . "=" . my_esc($_POST["_adb_" . $field->name]);
			}
		}

		// Add WHERE to query if there are fields to be updated
		if (!$q_fields)
			$query = '';
		else
			$query .= $q_fields . " WHERE " . $key_where;

	} else {
		// Insert or copy
		$q_fields = $q_values = '';

		foreach ($fields as $field) {
			if (isset($_POST["_adb_" . $field->name])) {

				// Don't specify, allow default selection by database
				if (isset($_POST['_adb_' . $field->name . '_type_']) &&
					      $_POST['_adb_' . $field->name . '_type_'] == "Default")
					continue;

				$q_fields .= (strlen($q_fields) ? ',' : '') . $field->name;
				$q_values .= (strlen($q_values) ? ',' : '');

				if (isset($_POST['_adb_' . $field->name . '_type_']) &&
					      $_POST['_adb_' . $field->name . '_type_'] == "Now")
					$q_values .= "NOW()";
				else if (isset($_POST['_adb_' . $field->name . '_type_']) &&
					           $_POST['_adb_' . $field->name . '_type_'] == "Empty")
					$q_values .= "''";
				// TODO: This is lame, allow something more clever
				else if (preg_match("/password/", $field->name))
					$q_values .= "PASSWORD(" . my_esc($_POST["_adb_" . $field->name]) . ")";
				else
					$q_values .= my_esc($_POST["_adb_" . $field->name]);
			}
		}

		$query = 'INSERT INTO ' . mysqli_escape_string($adb_dblink, $qDBTable) . ' (' . $q_fields . ')' . ' VALUES (' . $q_values . ')';
	}

	if ($query && !mysqli_query($adb_dblink, $query))
		$status = Error(mysqli_error($adb_dblink) . '<br>' . $query);
	else
		$status = "<div class=\"green\">Record " . ($key_where && !$qCopyRow ? 'Updated' : 'Inserted') . "</div><p>";
		
	// Back to select after update/copy, display insert form again on insert
	if($key_where || $qCopyRow)
		$qDBAction = "select";

} else if ($qDeleteRow && $key_where) {
	$query = 'DELETE FROM ' . mysqli_escape_string($adb_dblink, $qDBTable) . " WHERE " . $key_where;

	if (!mysqli_query($adb_dblink, $query))
		$status = Error(mysqli_error($adb_dblink) . '<br>' . $query);
	else
		$status = "<div class=\"green\">Record Deleted</div><p>";

	// Fall through to select after deletion
	$qDBAction = "select";
}

if ($bReport) {
	echo '<center><h1>' . (isset($qTitle) ? $qTitle : 'Report Untitled') . '</h1></center>';
} else {
	include "adb_auth_banner.php";
	include "adb_form.php";
}

echo $status;

// Generate form for inserting data
if ($qDBTable && $qDBAction == "insert") {
?>
	<form name="autodb_insert_form" action="" method="POST">
	<input type="hidden" name="db" value="<?php htmlspecialchars($qDatabase) ?>">
	<input type="hidden" name="table" value="<?php htmlspecialchars($qTable) ?>">
	<input type="hidden" name="dbaction" value="insert">
	<input type="hidden" name="copyrow" value="<?php $qCopyRow ?>">
	<input type="hidden" name="s" value="1">
<?php
	// If all primary keys were provided, user is updating an existing entry. Output hidden form fields for
	// the primary key values so we can update the right record when the form is submitted.
	if ($key_where && $pkey_cols) {
		foreach ($pkey_cols as $key_col)
			echo '<input type="hidden" name="_adbkeycol_' . $key_col . '" value="' .
				htmlspecialchars($_POST['_adbkeycol_' . $key_col]) . '">';

		$query = "SELECT * FROM " . mysqli_escape_string($adb_dblink, $qDBTable) . " WHERE " . $key_where;

		$result = mysqli_query($adb_dblink, $query);

		if (mysqli_num_rows($result) != 1)
			die(Error("Wrong number of rows (" . mysqli_num_rows($result) . ", need 1) to update entry<br>" . $query));
		else
			$row = mysqli_fetch_assoc($result);
	} else {
		// Nothing to update
		unset($row);
	}

?>
	<table class="data" cellpadding="0" cellspacing="0" border="0">
	<tr class="data" style="font-weight: bold;"><td style="border-left: 0px;">Column</td><td width="600">Value</td><!--<td>Type</td>--></tr>
<?php

	$nRows = count($fields);
	$nRow = 0;

	$query = "SELECT * FROM " . AUTODB_REL . " WHERE adb_t1 = " . my_esc($qDBTable) . "";

	if(!($res = mysqli_query($adb_dblink, $query)))
		die(Error(mysqli_error($adb_dblink) . '<br>' . $query));

	// Array that will be returned mapping $qTable columns to related column data in other tables
	$adb_rule_cols = array();

	if ($rule_res = mysqli_query($adb_dblink, $query))
		while ($rule_row = mysqli_fetch_assoc($rule_res))
			$adb_rule_cols[$rule_row['adb_t1_relcol']] = $rule_row;

	$focus_field = '';

	foreach ($fields as $field) {

		$nRow++;
		$alt_focus = '';

		$td_style = ($nRow == $nRows ? 'border-bottom: 0px;' : '');

		echo '<tr class="data" height="30"><td style="border-left: 0px; ' . $td_style . '">' . htmlspecialchars($field->name) . '</td>';
		$td_style = ($td_style ? ' style="' . $td_style . '"' : '');

		if (isset($adb_rule_cols[$field->name])) {
			// Input box with an onKeyUp handler to retrieve a list of possible suggestions for the field each
			// time the user stops typing for more than a second
			$rule = $adb_rule_cols[$field->name];

			// Get value from related table instead of displaying row value directly
			if(isset($row))
				$adb_cols = FetchRelations($qDBTable, $row);

			$adb_id = "_adb_" . htmlspecialchars($field->name);
			$adbdsp_id = "_adbdsp_" . htmlspecialchars($field->name);
			$adbdiv_id = "_adbdiv_" . htmlspecialchars($field->name);

			// Text field that is displayed to the user
			$input = '<input type="text" name="' . $adbdsp_id . '" id="' . $adbdsp_id . '"' .
				'onKeyDown="if(event.keyCode == 40) return false;" ' .
				'onKeyPress="if(event.keyCode == 13) return false;" ' .
				'onKeyUp="if(!KeyPress(\'' . htmlspecialchars($field->name) . '\', event.keyCode)) { ' .
					'GetSuggestions(\'' . htmlspecialchars($qDBTable) . '\', ' .
					'\'' . htmlspecialchars($field->name) . '\', this.value) }" ' .
				'onFocus="GetSuggestions(\'' . htmlspecialchars($qDBTable) . '\', ' .
					'\'' . htmlspecialchars($field->name) . '\', this.value)" ' .
				'onClick="GetSuggestions(\'' . htmlspecialchars($qDBTable) . '\', ' .
					'\'' . htmlspecialchars($field->name) . '\', this.value)" ' .
				'onBlur="document.getElementById(\'' . $adbdiv_id . '\').style.visibility = \'hidden\'"' .
				(isset($row) && isset($adb_cols[$field->name][$row[$field->name]]) ?
					' value="' . $adb_cols[$field->name][$row[$field->name]] . '"' : '' ) .
					' style="font-style: italic; width: 600; background: #FFFF99;">';

			// Hidden field to hold the actual value to be inserted into the database
			$input .= '<input type="hidden" name="' . $adb_id . '" id="' . $adb_id . '"' .
				(isset($row) ? ' value="' . $row[$field->name] . '"' : '') . '>';

			// Div to hold Suggestions when the user clicks into the display input box
			$input .= '<br><div name="' . $adbdiv_id . '" id="' . $adbdiv_id . '" style="visibility: hidden; position: absolute; border: 1px solid black; background: white; padding: 0px; font-size: 11;"></div>';

			if (!$focus_field)
				$focus_field = $adbdsp_id;

		} else {

			// If field cannot be null (NOT_NULL_FLAG), set the background color of the input element(s) to red
			$input_style = ($field->flags & 1 ? 'background: #FFAAAA;' : '');

			$value = htmlspecialchars((isset($row) ? $row[$field->name] :
				(isset($field->def) ? $field->def : '')));

			$input = '';
			$type = '';

			$adb_id = "_adb_" . htmlspecialchars($field->name);

			switch($field->type) {

				case 'tinytext':
				case 'text':
				case 'mediumtext':
				case 'longtext':
				case 'tinyblob':
				case 'blob':
				case 'mediumblob':
				case 'longblob':
					$input = '<textarea rows="10" style="' . $input_style . '" ' .
						'name="' . $adb_id . '" id="' . $adb_id . '"';

					if ($field->max_length)
						$input .= ' maxlength="' . $field->max_length . '"';

					$input .= '>' . $value . '</textarea>';
					break;

				case 'year':
					$input .= YearOptions($field->name, $value, $input_style);
					$alt_focus = $adb_id . '_Y';
					break;

				case 'timestamp':
					// MySQL 4.0 and earlier displays timestamps in the format YYYYMMDDhhmmss while newer versions
					// display them as 'YYYY-MM-DD hh:mm:ss'. Here we convert older values to behave like newer ones
					if(ereg("^([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})", $value, $m))
						$value = "{$m[1]}-{$m[2]}-{$m[3]} {$m[4]}:{$m[5]}:{$m[6]}";

					// Treat the same as datetime from this point forward

				case 'datetime':

					// Split date and time apart from value and fall through to 'time' and 'date' cases
					if ($value == "CURRENT_TIMESTAMP") {
						$type = "Now";
						$value = '0000-00-00 00:00:00';
					} else if (empty($value) || $value == "0000-00-00 00:00:00") {
						$type = "Empty";
						$value = '0000-00-00 00:00:00';
					} else {
						$type = "Specify";
					}

					list($date, $time) = split(" ", $value);
					$bDateTime = TRUE;
					
				case 'time':
					// Build options for entering a time value as input boxes. Was time set above?
					if(isset($time) && $time)
						$value = $time;

					// If no existing value being edited, use the current time
					if (!$value)
						$value = date('H:i:00');

					if(!$type)
						$type = ($value == "00:00:00" ? "Empty" : "Specify");

					// Add a select box for choosing to use the default or to specify a value
					$input .= BeginDateTypeOptions($field->name, $type, $input_style);

					if($value == "00:00:00")
						$value = date('H:i:00');

					list($h, $m, $s) = split(":", $value);
					$input .= TimeInput($field->name, $h, 'h', $input_style . "; width: 20px;");
					$input .= ":" . TimeInput($field->name, $m, 'm', $input_style . "; width: 20px;");
					$input .= ":" . TimeInput($field->name, $s, 's', $input_style . "; width: 20px;");
					$alt_focus = $adb_id . '_h';

					if (!isset($bDateTime)) {
						$input .= EndDateTypeOptions();
						break;
					}

				case 'date':
					// Build options for entering the date as select boxes. Was date set above?
					if(isset($date) && $date)
						$value = $date;

					// If no existing value being edited, use the current date
					if(!$value)
						$value = date('Y-m-d');

					if(!$type)
						$type = ($value == "0000-00-00" ? "Empty" : "Specify");

					// Begin date option span if it hasn't already been started
					if (empty($input))
						$input = BeginDateTypeOptions($field->name, $type, $input_style);

					if($value == "0000-00-00")
						$value = date('Y-m-d');

					list($Y, $M, $D) = split("-", $value);
					$input .= (isset($bDateTime) ? '&nbsp;&nbsp;' : '');
					$input .= DayOptions($field->name, $D, $input_style);
					$input .= MonthOptions($field->name, $M, $input_style);
					$input .= YearOptions($field->name, $Y, $input_style);
					$alt_focus = ($alt_focus ? $alt_focus : $adb_id . '_D');
					$input .= EndDateTypeOptions();
					break;

				default:
					$input = '<input type="text" style="' . $input_style . '"' .
						' name="' . $adb_id . '" id="' . $adb_id . '"';

					if ($field->max_length)
						$input .= ' maxlength="' . $field->max_length . '"';

					$input .= ' value="' . $value . '">';
					break;
			}

			// If field is an auto_increment column, don't give any input fields
			if ($field->auto_increment) {
				if (isset($row) && !$qCopyRow)
					$input = "<i>" . $value . "</i>";
				else
					$input = '<i>Auto Increment</i>';
			} else if (!$focus_field) {
				$focus_field = ($alt_focus ? $alt_focus : $adb_id);
			}
		}
		echo '<td' . $td_style . '>' . $input . '</td>';
		//echo '<td' . $td_style . '>' . htmlspecialchars($field->type) . '</td></tr>';
	}

?>
	</table>

	<p><div style="font-style: italic; background: #FFAAAA; border: 1px solid gray; padding: 2px; width: 182;">Input fields in red are required</div><p>

	<input type="submit" value="<?php (isset($row) && !$qCopyRow ? 'Update' : 'Insert') ?>"
	       style="border: 1px solid #9933FF; height: 25px; width: 150px; cursor: pointer;">
<?php
} else if ($qDBTable && ($qDBAction == "select" || $qDBAction == "export" || !$qDBAction)) {

	// Build the query and execute it
	$query = BuildQuery($joins, $where, $rcols);
	$result = mysqli_query($adb_dblink, $query);

	if (!$result)
		die(Error(mysqli_error($adb_dblink) . '<br>' . $query));

	// Output the table data
	$data = array();
	while ($row = mysqli_fetch_assoc($result))
		array_push($data, $row);
	
	$bInteractive = !$bReport;
	$max_data_len = 500;
	$rel_table = $qDBTable;

	// Output the table
	include "adb_dtable.php";

	if ($qLimit && $qLimit != "all") {
		$result = DBQuery("SELECT COUNT(*) FROM " . mysqli_escape_string($adb_dblink, $qDBTable) .
			$joins . $where);

		$row = mysqli_fetch_array($result);
		$nRowsTotal = $row['COUNT(*)'];

		if ($nRowsTotal > $nRow) {
			$notshown = $nRowsTotal - $nRow;
			echo '<br><i>(' . $notshown . ($notshown > 1 ? ' Rows' : ' Row') . ' not shown.)</i><br>';
		}
	}
} else if ($qDBTable && ($qDBAction == "reports")) {
	if(count($reports)) {
		echo "Select Report:\n";
		echo "<ul>";
		$bInclude = true;
		foreach($reports as $report) {
			echo '<li><a href="' . $report . '">';
			include($report);
			echo '</a></li>';
		}
		echo "<ul>";
	} else {
		echo Error("There are no report scripts for $qDBTable");
	}
}
?>

<script language="JavaScript">
	<?php if ($qDatabase && $qTable) { ?>
	document.autodb_form.dbaction.value='<?php $qDBAction ?>';
	<?php } ?>

	<?php isset($focus_field) && $focus_field ?
		"document.getElementById('" . $focus_field . "').focus();\n" : "" ?>

	// Internet explorer does not support ":hover" CSS attribute. The script below adds an onMouseOver/Out
	// handler for every <tr> element whose class is "data" and every <td> element whose class is "clickable".
	// The handlers change the class of the element to simulate the ':hover' style.
	if(document.getElementsByTagName) {
		var className = 'hovered';
		//var pattern = new RegExp('(^|\\s+)' + className + '(\\s+|$)');
		var rows = document.getElementsByTagName('tr');

		for(var i=0; i<rows.length; i++) {
			if(rows[i].className == "data") {
				<?php if($qDBAction == "select") { ?>
				rows[i].onmouseover = function() { this.className = "data_hovered"; };
				rows[i].onmouseout = function() { this.className = "data"; };
				<?php } ?>
			}
		}
		rows = document.getElementsByTagName('td');

		for(var i=0; i<rows.length; i++) {
			if(rows[i].className == "clickable") {
				rows[i].onmouseover = function() { this.className = "clickable_hovered"; };
				rows[i].onmouseout = function() { this.className = "clickable"; };
			}
		}
		rows = null;
	}
</script>

</form>
</html>

<?php
if($bExport) {
	ob_end_clean();
	header("Cache-Control: no-store, no-cache, must-revalidate, private");
	header("Pragma: no-cache");
	header("Content-Type: text/csv");
	header("Content-Length: " . strlen($csvdata));
	header("Content-Disposition: attachment; filename=data.csv"); 
	echo $csvdata;
} else {
	ob_end_flush();
}
?>
