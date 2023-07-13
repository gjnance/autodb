<?php
// Diagnostic function to print an array formatted for HTML similar to the function print_r
function html_print_r($a)
{
	echo nl2br(str_replace(" ", "&nbsp;", htmlentities(print_r($a, TRUE))));
}

function POST($var) {
	return (isset($_POST[$var]) ? $_POST[$var] : '');
}

function GET($var) {
	return (isset($_GET[$var]) ? $_GET[$var] : '');
}

// Update/retrieve preference for this variable
function GetCachedVar($qDBTable, $var, $default = '')
{
	$dblink = $GLOBALS['adb_dblink'];

	// See if the variable was specified in GET or POST
	$value = GetVar($var);

	$prev_value = GetVar("prev_" . $var);

	// If logged in, use user specific settings
	$user = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : "";
	$user_where = $user ? " && user = " . my_esc($user) : "";

	// Is there a previous entry for this table/variable?
	$query = "SELECT * FROM " . AUTODB_PREFS . " WHERE " .
		"dbtable=" . my_esc($qDBTable) . " && var=" . my_esc($var) . $user_where;

	$res = DBQuery($query);

	if (!is_null($res)) {
	    $row = mysqli_fetch_array($res);
		$cached = $row['value'];
	} else {
		$cached = null;
	}

	if ($value || $value != $prev_value) {
		// Value specified in GET or POST, insert or update new preference
		if (isset($cached))
			$query = "UPDATE " . AUTODB_PREFS . " SET value=" . my_esc($value) . " WHERE " .
				"dbtable=" . my_esc($qDBTable) . " && var=" . my_esc($var) . $user_where;
		else
			$query = "INSERT INTO " . AUTODB_PREFS . " (dbtable, var, value, user) VALUES (" .
				my_esc($qDBTable) . "," . my_esc($var) . "," . my_esc($value) . "," . my_esc($user) . ")";

		// Execute query
		DBQuery($query);
		
	} else {
		// Variable not specified in GET or POST, use cached value if any, otherwise use default
		$value = isset($cached) ? $cached : $default;
	}

	return $value;
}

function GetVar($var, $default = '')
{
	return isset($_POST[$var]) ? $_POST[$var] : (isset($_GET[$var]) ? $_GET[$var] : $default);
}

// my_esc() - a safe version of mysqli_escape_string that quotes a variable to make it safe depending on
// whether get_magic_quotes_gpc is enabled or not
function my_esc($value)
{
    $dblink = $GLOBALS['adb_dblink'];

	// Quote if not a number or a numeric string
	if (!is_numeric($value))
		 $value = "'" . mysqli_real_escape_string($dblink, $value) . "'";

	return $value;
}

// GetDatabases() - Returns a list of databases available on the server we're connected to
function GetDatabases()
{
	$dblink = $GLOBALS['adb_dblink'];

	$dbs = array();

	// Get list of available databses
	$res = mysqli_query($dblink, "SHOW DATABASES");

	while ($row = mysqli_fetch_object($res))
		array_push($dbs, $row->Database);

	return $dbs;
}

// GetTables() - Returns a list of tables available in the database provided
function GetTables($db, $dblink=false)
{
	if(!$dblink)
		$dblink = $GLOBALS['adb_dblink'];

	$tables = array();

	$res = DBQuery("SHOW TABLES FROM " . mysqli_escape_string($dblink, $db));

	while ($row = mysqli_fetch_row($res))
		array_push($tables, $row[0]);

	return $tables;
}

function Error($err)
{
	return '<table class="red" cellpadding="0" cellspacing="0" width="100%">' .
		'<tr><td><img src="' . AUTODB_BASEURL . 'gfx/network-error.gif" align="left">' . $err . '</td></tr>' .
		'</table><p>';
}

// Retrieve display rules for the given database and table
function FetchRelations($rel_table, $t_row)
{
	$dblink = $GLOBALS['adb_dblink'];

	// Array that will be returned mapping columns to related column data in other tables
	$adb_cols = array();

	foreach($t_row as $column=>$value) {

		// Is there an autodb table describing how to display this table?
		$query = "SELECT * FROM " . AUTODB_REL . " " .
			"WHERE adb_t1 = '" . mysqli_escape_string($dblink, $rel_table) . "' AND " .
				"adb_t1_relcol = '" . $column . "' LIMIT 1";

		$res = DBQuery($query);

		if ($res) {
			// One or more rules for displaying this table were found. Loop through them
			// and get the necessary data from the related tables.
			while($row = mysqli_fetch_assoc($res)) {

				// Don't include this display rule if row doesn't include the column from t1
				if (!isset($t_row[$row['adb_t1_relcol']]))
					continue;

				// If the rule specified that the relation comes from a different server, make a connection
				// to the remote server and save a link to the database in an array for later use
				if($row['adb_t2_remhost'] && $row['adb_t2_remuser']) {
					$dblink = mysql_connect($row['adb_t2_remhost'], $row['adb_t2_remuser'], $row['adb_t2_rempass']);

					if(!$dblink)
						die("<div class=\"red\">" . mysqli_error($dblink) . "<br>$query</div>");
				}
				else
					$dblink = $GLOBALS['adb_dblink'];

				// Fetch data from related column
				$query =
					"SELECT " .
						$row['adb_t2'] . "." . $row['adb_t2_relcol'] . "," .
						$row['adb_t2'] . "." . $row['adb_t2_dspcol'] . " " .
					"FROM " .
						$row['adb_t2'] . " " .
					"WHERE " .
						$row['adb_t2'] . "." . $row['adb_t2_relcol'] . "=" . my_esc($t_row[$row['adb_t1_relcol']]);
				
				$adbrel_result = DBQuery($query, $dblink);

				// Create an associative array of related data for use when displaying the table
				while ($adbrel_row = mysqli_fetch_assoc($adbrel_result))
					$adb_cols[$row['adb_t1_relcol']][$adbrel_row[$row['adb_t2_relcol']]] =
						$adbrel_row[$row['adb_t2_dspcol']];
			}
		}
	}

	return $adb_cols;
}

function GetZeroPaddedInt($field, $w=2) {
	$n = isset($_POST[$field]) ? intval($_POST[$field]) : 0;
	return str_pad($n, $w, '0', STR_PAD_LEFT);
}

function ConstructDateField($type, $field)
{
	$field = '_adb_' . $field;

	$date = $time = '';

	switch($type) {
		case 'timestamp':
		case 'datetime':
			$bDateTime = TRUE;

		case 'time':
			$time = GetZeroPaddedInt($field . '_h') . ':' .
				GetZeroPaddedInt($field . '_m') . ':' .
				GetZeroPaddedInt($field . '_s');
			if (!isset($bDateTime))
				break;

		case 'date':
			$date = GetZeroPaddedInt($field . '_Y') . '-' .
				GetZeroPaddedInt($field . '_M') . '-' .
				GetZeroPaddedInt($field . '_D');
			break;
	}
	return $date . ' ' . $time;
}

function BeginDateTypeOptions($field, $type, $style = '')
{
	$id = "_adb_" . htmlspecialchars($field) . "_type_";
	$id_opt = "_adb_" . htmlspecialchars($field) . "_options";

	$input = '';
	$input .= '<select style="' . $style . '" name="' . $id . '" id="' . $id . '" ' .
		'onChange="document.getElementById(\'' . $id_opt . '\').style.visibility = ' .
		'(this.value == \'Specify\' ? \'visible\' : \'hidden\');">';

	$input .= '<option value="Now">Now</option>';
	$input .= '<option value="Specify"' .
		($type == "Specify" ? ' selected' : '') . '>Specify</option>';
	$input .= '<option value="Empty"' .
		($type == "Empty" ? ' selected' : '') . '>Empty</option>';
	$input .= '</select>&nbsp;&nbsp;';
	$input .= '<span name="' . $id_opt . '" id="' . $id_opt . '" ' .
		'style="visibility: ' . ($type == "Specify" ? 'visible' : 'hidden') . ';">';
	return $input;
}
function EndDateTypeOptions()
{
	return "</span>";
}

function YearOptions($field, $sel, $style = '')
{
	$id = "_adb_" . htmlspecialchars($field) . "_Y";
	$input = '<select style="' . $style . '" name="' . $id . '" id="' . $id . '">';
	for($i=date('Y')+10; $i>=date('Y')-100; $i--)
		$input .= '<option value="' . $i . '"' . ($i==$sel ? ' selected' : '') . '>' . $i . '</option>';
	$input .= '</select>';
	return $input;
}

function MonthOptions($field, $sel, $style = '')
{
	$id = "_adb_" . htmlspecialchars($field) . "_M";
	$input = '<select style="' . $style . '" name="' . $id . '" id="' . $id . '">';
	for($i=1; $i<=12; $i++)
		$input .= '<option value="' . $i . '"' . ($i==$sel ? ' selected' : '') . '>' .
			date('M', mktime(0,0,0,$i)) . '</option>';
	$input .= '</select>';
	return $input;
}

function DayOptions($field, $sel, $style = '')
{
	$id = "_adb_" . htmlspecialchars($field) . "_D";
	$input = '<select style="' . $style . '" name="' . $id . '" id="' . $id . '">';
	for($i=1; $i<=31; $i++)
		$input .= '<option value="' . $i . '"' . ($i==$sel ? ' selected' : '') . '>' . $i . '</option>';
	$input .= '</select>';
	return $input;
}

function TimeInput($field, $sel, $type, $style = '')
{
	$id = "_adb_" . htmlspecialchars($field) . "_" . $type;
	$input = '<input type="text" style="' . $style . '"' .
		'name="' . $id . '" id="' . $id . '" maxlength="2" value="' . $sel . '">';
	return $input;
}

function GetTableFields($DBTable)
{
	$dblink = $GLOBALS['adb_dblink'];
	$query = "SELECT * FROM " . mysqli_escape_string($dblink, $DBTable) . " LIMIT 0";
	return GetTableFieldsFromQuery($query, $DBTable);
}

// GetTableFieldsFromQuery - Execute $query and return an array structured similarly to that returned by
// GetTableDescription. Used to get a list of fields for a particular query only and not all fields for a table
function GetTableFieldsFromQuery($query, $DBTable = '')
{
	$dblink = $GLOBALS['adb_dblink'];

	// Also run a DESCRIBE from the table to get a bit more info if DBTable is present
	$desc_fields = array();
	if($DBTable) {
		$desc_query = "DESCRIBE " . mysqli_escape_string($dblink, $DBTable);

		$res = DBQuery($desc_query);

		while ($row = mysqli_fetch_assoc($res))
			$desc_fields[$row['Field']] = $row;
	}

	// Execute the query
	$res = DBQuery($query);

	// Build an array of fields from the result set
	$fields = array();

	for($i=0; $i<mysqli_num_fields($res); $i++) {
		$data = mysqli_fetch_field($res);

		if (isset($desc_fields[$data->name]) && $desc_fields[$data->name]['Extra'] == 'auto_increment')
			$data->auto_increment = 1;
		else
			$data->auto_increment = 0;

		if (isset($desc_fields[$data->name]))
			$data->def = $desc_fields[$data->name]['Default'];
		array_push($fields, $data);
	}
	
	return $fields;
}

// Get relation columns for $DBTable and return them as an array
function GetRelations($DBTable)
{
	$dblink = $GLOBALS['adb_dblink'];

	$query = "SELECT * FROM " . AUTODB_REL . " " .
		"WHERE adb_t1 = '" . mysqli_escape_string($dblink, $DBTable) . "'";

	$res = DBQuery($query);

	$rcols = array();

	while($row = mysqli_fetch_assoc($res))
		$rcols[$row['adb_t1_relcol']] = $row;

	return $rcols;
}

function BuildQuery(&$joins, &$where, &$rcols)
{
	global $qDatabase, $qDBTable, $qWhere, $qOrder, $qLimit;
	$dblink = $GLOBALS['adb_dblink'];

	// Get relation columns for this table
	$rcols = GetRelations($qDBTable);

	// Column names to display
	$cols = '';

	// Joins to make when selecting the data from the relation tables
	$joins = "";

	$fields = GetTableFields($qDBTable);

	foreach($fields as $field) {
		
		$cols .= ($cols ? ", " : "");

		// Is there a relation rule for this column?
		if (isset($rcols[$field->name])) {

			$rcol = $rcols[$field->name];

			if ($rcol['adb_t2_remhost'] && $rcol['adb_t2_remuser']) {
				// Column data comes from a remote server. Connect, get the table description, and create a
				// temporary table locally to store the required data in if we haven't already done so.
				$tmp_table = $rcol['adb_t1_relcol'] . "_" . preg_replace("\.", "_", $rcol['adb_t2']);

				$rem_dblink = mysql_connect($rcol['adb_t2_remhost'], $rcol['adb_t2_remuser'], $rcol['adb_t2_rempass']);

				if(!$rem_dblink)
					die("<div class=\"red\">" . mysqli_error($dblink) . "<br>$query</div>");

				// Get CREATE TABLE syntax for creating a local (temporary) copy of the remote table
				$query = "SHOW CREATE TABLE " . $rcol['adb_t2'];
				$row = DBQueryGetRow($query, $rem_dblink);

				mysql_select_db($qDatabase, $GLOBALS['adb_dblink']);
				$query = $row['Create Table'];
				$query = preg_replace("/^CREATE TABLE `.*`/U", "CREATE TEMPORARY TABLE `$tmp_table`", $query);
				$query = preg_replace("auto_increment", "", $query);
				$query = preg_replace("ENGINE=[A-Za-z]*", "", $query);
				$query = preg_replace("DEFAULT CHARSET=[[:alnum:]]*", "", $query);

				// Execute the query, creating a local copy of the remote table
				DBQuery($query);

				// Modify rcol table name to point to local table name
				$rcol['adb_t2'] = $tmp_table;
			}

			$dspcol = $rcol['adb_t2'] . "." . $rcol['adb_t2_dspcol'];
			$relcol = $rcol['adb_t2'] . "." . $rcol['adb_t2_relcol'];

			// Add column, tables, and an appropriate join linking the two
			$cols .= "$dspcol AS " . $field->name . ", " . mysqli_escape_string($dblink, $qDBTable) . "." .
				mysqli_escape_string($dblink, $field->name) . " AS _adbrel_" . mysqli_escape_string($dblink, $field->name);

			if(!preg_match("/LEFT JOIN " . $rcol['adb_t2'] . "/", $joins)) {
				$joins .= "\nLEFT JOIN " . $rcol['adb_t2'] . " ON " .
					$qDBTable . "." . $field->name . " = " . $relcol;
			}

			// Replace columns in where with appropriate relation columns
			$qWhere = preg_replace("/" . $field->name . "/", $dspcol, $qWhere);

		} else {
			$cols .= mysqli_escape_string($dblink, $qDBTable) . "." . $field->name;
		}
	}

	// Add WHERE, ORDER, and LIMIT
	$where = $qWhere ? "\nWHERE " . $qWhere : "";
	$order = $qOrder ? "\nORDER BY " . mysqli_escape_string($dblink, $qOrder) : "";
	$limit = ($qLimit && $qLimit != 'all') ? "\nLIMIT " . intval($qLimit) : "";

	$query = "SELECT " . $cols . "\nFROM " . mysqli_escape_string($dblink, $qDBTable) .
		$joins . $where . $order . $limit;

	$rows = DBQueryGetRows($query);

	// No rows returned by the query means no remote columns to lookup and insert, just return the query
	if(!count($rows))
		return $query;

	foreach($rcols as $col=>$rcol) {
		if ($rcol['adb_t2_remhost'] && $rcol['adb_t2_remuser']) {

			// Connect to remote database again (connection is re-used by default)
			$rem_dblink = mysql_connect($rcol['adb_t2_remhost'], $rcol['adb_t2_remuser'], $rcol['adb_t2_rempass']);

			if(!$rem_dblink)
				die("<div class=\"red\">" . mysqli_error($dblink) . "<br>$query</div>");

			$values = array();
			foreach($rows as $row) {
				$value = my_esc($row['_adbrel_' . $rcol['adb_t1_relcol']]);
				if(!in_array($value, $values))
					array_push($values, $value);
			}

			$rquery = "SELECT " . $rcol['adb_t2_relcol'] . "," . $rcol['adb_t2_dspcol'] . " " .
				"FROM " . $rcol['adb_t2'] . " " .
				"WHERE " . $rcol['adb_t2_relcol'] . " IN (" . join(",", $values) . ")";

			$tmp_rows = DBQueryGetRows($rquery, $rem_dblink);

			// Insert each row into the local temporary table.
			foreach($tmp_rows as $row) {
				$tmp_table = $rcol['adb_t1_relcol'] . "_" . ereg_replace("\.", "_", $rcol['adb_t2']);
				$bEqual = $rcol['adb_t2_relcol'] == $rcol['adb_t2_dspcol'];
				$rquery = "INSERT IGNORE INTO " . $tmp_table . " " .
					"(" . $rcol['adb_t2_relcol'] . (!$bEqual ? "," . $rcol['adb_t2_dspcol'] : "") . ")" . " " .
					"VALUES(" . my_esc($row[$rcol['adb_t2_relcol']]) .
						(!$bEqual ? "," . my_esc($row[$rcol['adb_t2_dspcol']]) : "") . ")";
				DBQuery($rquery);
			}
		}
	}

	return $query;
}

function GetReports($qDBTable)
{
	$reports = array();

	if(!file_exists("./reports"))
		return $reports;

	$rdir = opendir("./reports");
	
	while($file = readdir($rdir)) {
		if(preg_match("/^" . $qDBTable . "\..*\.php$/", $file))
			array_push($reports, "./reports/" . $file);
	}
	return $reports;
}

function DBBuildWhere($where_data)
{
	$dblink = $GLOBALS['adb_dblink'];
	$where = '';

	// Build up where if necessary
	if($where_data) {
		if(is_string($where_data)) {
			$where = " WHERE $where_data";
		}
		else if(is_array($where_data)) {
			$where = " WHERE ";
			foreach($where_data as $name=>$value)
				$where .= $name . " = '" . mysqli_escape_string($dblink, $value) . "' AND ";

			// Remove extra " AND "
			$where = ereg_replace(" AND $", "", $where);
		}
	}
	return $where;
}

function DBQuery($query, $dblink=false)
{
	if(!$dblink)
		$dblink = $GLOBALS['adb_dblink'];

	if(!($res = mysqli_query($dblink, $query))) {
		die('<table class="red">
				<tr>
					<td valign="top"><img src="gfx/network-error.gif"></td>
					<td>' . mysqli_error($dblink) . "<p>" .
							nl2br($query) . '</td>
				</tr>
			</table>');
	}

	return $res;
}

function DBQueryGetRows($query, $dblink=false)
{
	if(!$dblink)
		$dblink = $GLOBALS['adb_dblink'];

	$rows = array();
	$res = DBQuery($query, $dblink);
	while($row = mysqli_fetch_assoc($res))
		array_push($rows, $row);
	return $rows;
}

function DBGetRows($table, $where='', $order='', $dblink=false)
{
	$query = "SELECT * FROM " . $table . DBBuildWhere($where);

	if($order)
		$query .= " ORDER BY $order";

	$res = DBQuery($query, $dblink);
	$rows = array();

	while($row = mysqli_fetch_assoc($res))
		array_push($rows, $row);

	return $rows;
}

function DBQueryGetRow($query, $dblink = false)
{
	return mysqli_fetch_assoc(DBQuery($query, $dblink));
}

function DBGetRow($table, $where='', $dblink = false)
{
	$query = "SELECT * FROM " . $table . DBBuildWhere($where) . " LIMIT 1";
	return mysqli_fetch_assoc(DBQuery($query, $dblink));
}

function DBGetRowValue($table, $value, $where='', $dblink = false)
{
	$query = "SELECT $value FROM " . $table . DBBuildWhere($where) . " LIMIT 1";
	$row = mysqli_fetch_assoc(DBQuery($query, $dblink));
	return $row[$value];
}

?>
