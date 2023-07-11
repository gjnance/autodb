<form name="autodb_form" action="<?php AUTODB_BASEURL ?>/" method="POST">
<input type="hidden" name="order" value="<?php htmlspecialchars($qOrder) ?>">
<input type="hidden" name="deleterow" value="0">
<input type="hidden" name="copyrow" value="0">
<div class="banner">
<?php
if (isset($pkey_cols)) {
	foreach ($pkey_cols as $key_col)
		echo '<input type="hidden" name="_adbkeycol_' . $key_col . '" id="_adbkeycol_' . $key_col . '">';
}

if (!$bForceDB) {
?>
	<select name="db" onChange="if(this.value) Submit(true, false);">
		<option value="">- Select Database -</option>
		<?php foreach (GetDatabases() as $db)
			echo '<option value="' . htmlspecialchars($db) . '"' .
				($db == $qDatabase ? ' selected' : '') . '>' . htmlspecialchars($db) . '</option>'; ?>
	</select>

<?php
}
// Show list of tables in the selected database if one has been selected
if ($qDatabase) {
	echo ($bForceDB ? '' : ' :: ') . '<select name="table" onChange="if(this.value) Submit(true, true);">';
	echo '<option value="">- Select Table -</option>';
	foreach ($db_tables as $table)
		echo '<option value="' . htmlspecialchars($table) . '"' .
			($table == $qTable ? ' selected' : '' ) . '>' . htmlspecialchars($table) . '</option>';
	echo '</select>';
}

if ($qDBTable) {

	//echo '<a href="' . $script . '">Run Script</a><p>';

	echo ' :: <select name="dbaction" onChange="if(this.value) Submit(false, false);">';
	echo '<option value="">- Action -</option>';
	echo '<option value="select"' . ($qDBAction == "select" ? ' selected' : '') . '>SELECT</option>';
	echo '<option value="insert"' . ($qDBAction == "insert" ? ' selected' : '') . '>INSERT</option>';
	if(count($reports))
		echo '<option value="reports"' . ($qDBAction == "reports" ? ' selected' : '') . '>REPORTS</option>';
	echo '<option value="export">EXPORT</option>';
	echo '</select>';

	if ($qDBAction == "select") {
		echo ' :: LIMIT <select name="limit" onChange="Submit(false, true);">';
		echo '<option value="1"' . ($qLimit == "1" ? ' selected' : '') . '>1 Row</option>';
		echo '<option value="10"' . ($qLimit == "10" ? ' selected' : '') . '>10 Rows</option>';
		echo '<option value="100"' . ($qLimit == "100" || !$qLimit ? ' selected' : '') . '>100 Rows</option>';
		echo '<option value="1000"' . ($qLimit == "1000" ? ' selected' : '') . '>1,000 Rows</option>';
		echo '<option value="all"' . ($qLimit == "all" ? ' selected' : '') . '>All Rows</option>';
		echo '</select>';

		echo '<input type="hidden" name="prev_where" value="' . ($qWhere ? htmlspecialchars($qWhere) : '') . '">';
		echo ' :: WHERE <input type="text" name="where" style="width: 300px;" value="' .
			($qWhere ? htmlspecialchars($qWhere) : '') . '" onKeyDown="if(event.keyCode==13) Submit(false, true);">';
	}
}
?>

</div>
</form>
<p>
