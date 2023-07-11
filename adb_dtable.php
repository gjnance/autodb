<?
// adb_dtable.php - Output a table of data. Expects the following to be defined:
//
// $fields = array(array('Field' => field1), array('Field' => field2), ...); 
//
//   Each entry may also have 'Sortable' => FALSE indicating that the column's data comes from another
//   table and should not allow for sort operations by clicking the column's heading.
//
// $data = array(
//   array(field1 => f1data, field2 => f2data, ...),    // row1
//   array(field1 => f1data, field2 => f2data, ...),    // row2
//   ...
// );
//
// $bInteractive	Allow edit/copy/delete or sorting of columns
// $max_data_len	Max number of bytes to display for any data cell
// $rel_table		The table to look for relation rules for when displaying the data

$bInteractive = isset($bInteractive) ? $bInteractive : FALSE;
$max_data_len = isset($max_data_len) ? $max_data_len : 500;

$debug = false;

if ($debug) {
	html_print_r($fields);
	html_print_r($data);
}

?>

<table class="data" cellpadding="0" cellspacing="0" width="50%">
<tr valign="top" class="data">

<?
// Output column headings
$nRows = count($data);
$nCol = 0;

foreach ($fields as $field) {

	// Skip columns not requested to be displayed
	if (isset($qCols) && !in_array("*", $qCols) && !in_array($field->name, $qCols))
		continue;

	// Is the data being sorted by this column already?
	$bSortcol = isset($qOrder) && preg_match("/^" . $field->name . "( DESC)?$/", $qOrder);
	
	// If displaying an interactive table, output an empty column for the link column
	if ($bInteractive && $nCol++ == 0)
		echo '<td style="border-left: 0px;' . ($nRows == 0 ? 'border-bottom: 0px;' : '') . '">&nbsp;</td>';

	$style = ($nRows == 0 ? 'border-bottom: 0px;' : '') . ($nCol == 0 ? 'border-left: 0px;' : '');
	$style = $style ? ' style="' . $style . '"' : '';

	// Add field name to CSV export data
	//$csvdata .= $field->name . ";";
	$csvdata .= "\"" . $field->name . "\"" . ",";

	if ($bInteractive) {

		// Prepare the onClick handler to sort data based on this column
		$onclick = "document.autodb_form.order.value='" . htmlspecialchars($field->name) .
			($bSortcol && $qOrder == $field->name ? ' DESC' : '') . "';";
		$onclick .= "document.autodb_form.dbaction.value='select';";
		$onclick .= "Submit(false, true);";

		echo '<td nowrap class="clickable"' . $style . ' onClick="' . $onclick . '" title="Sort results by ' .
			htmlspecialchars($field->name) . '">';

		// If qOrder has been set for this column, display a graphic to indicate sort order
		if ($bSortcol) {
			if ($qOrder == $field->name)  // Normal sort
				echo '<img src="gfx/arrow_up.gif">';
			else                           // Descending order
				echo '<img src="gfx/arrow_down.gif">';
			echo '&nbsp;';
		}


		echo $field->name . '</td>';
	} else {
		echo '<td nowrap ' . $style . '">' . $field->name . '</td>';
	}
	$nCol++;
}

$csvdata = preg_replace("/,$/", "\n", $csvdata);

echo '</tr>';

// Output table data
$nRow = 0;
$key_col_js = '';

foreach($data as $row) {
	echo '<tr class="data" valign="top" nowrap>';
	$nCol = 0;

	if ($bInteractive) {
		// Build javascript for edit/delete links
		$key_col_js .= 'key_cols_' . $nRow . ' = Array(); ';
		foreach($pkey_cols as $col) {
			$key_col_js .= 'key_cols_' . $nRow . '[\'' . $col . '\']=\'' .
				(isset($row['_adbrel_' . $col]) ? $row['_adbrel_' . $col] : $row[$col]) . '\'; ';
		}

		echo '<td nowrap style="border-left: 0px; padding-top: 1px; padding-bottom: 1px;' .
			($nRow + 1 == $nRows ? 'border-bottom: 0px;' : '') . '" width="62">';
		echo '<a href="javascript:UpdateRow(key_cols_' . $nRow . ');" title="Edit Row">' .
			'<img src="gfx/accessories-text-editor.gif" border="0"></a> ';
		echo '<a href="javascript:CopyRow(key_cols_' . $nRow . ');" title="Copy Row">' .
			'<img src="gfx/edit-copy.gif" border="0"></a> ';
		echo '<a href="javascript:DeleteRow(key_cols_' . $nRow . ');" title="Delete Row">' .
			'<img src="gfx/edit-delete.gif" border="0"></a>';
		$nCol++;
	}

	// Output table data
	foreach($row as $col=>$val) {

		// Skip columns not requested to be displayed
		if (isset($qCols) && !in_array("*", $qCols) && !in_array($col, $qCols))
			continue;

		// Skip extra "_adbrel_" columns
		if (preg_match("/^_adbrel_/", $col))
			continue;

		// Add value to CSV export data
		//$csvdata .= str_replace("\r\n", "", $val) . ";";
		$csvdata .= "\"" . str_replace("\r\n", "", $val) . "\"" . ",";

		// Display data from related table in italics, other table data in plain font
		if (isset($rcols[$col]) && strlen($val))
			$val = "<div title='" . $row['_adbrel_' . $col] . "'><i>" . nl2br(htmlspecialchars($val)) . "</i></div>";
		else if (isset($row['_adbrel_' . $col]))
			// Relation lookup failed, there's a busted reference in the database!
			$val = "<i><span style=\"color: red; font-weight: bold\">" . $row['_adbrel_' . $col] . "</span></i>";
		else
			$val = nl2br(htmlspecialchars($val));

		// Limit length of data output
		if (isset($max_data_len) && strlen($val) > $max_data_len)
			$val = substr($val, 0, $max_data_len) . '...';

		// Make data cells that would take up lots of horizontal space not so wide
		$width = (strlen($val) > 60 ? ' width="400"' : '');

		$style = '';
		$style .= ($nRow + 1 == $nRows ? 'border-bottom: 0px;' : '');
		$style .= ($nCol == 0 ? 'border-left: 0px;' : '');

		echo '<td nowrap' . ($style ? ' style="' . $style . '"' : '') . $width . '>' . $val . '&nbsp;</td>';

		$nCol++;
	}

	$csvdata = preg_replace("/,$/", "\n", $csvdata);
	echo '</tr>';
	$nRow++;
}
?>

<script language="JavaScript">
<?= $key_col_js ?>
</script>

</table>
