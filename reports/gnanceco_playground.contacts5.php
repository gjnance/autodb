
<?
if(isset($bInclude) && $bInclude) {
	echo "Manage exported contacts";
	return;
}

include("../adb_config.php");
include("../adb_functions.php");

$adb_dblink = mysql_connect("localhost", "gnanceco_greg", "00zfdc");
mysql_select_db("gnanceco_playground");

// Data submitted
if (POST('s'))
{
	// Delete Tiffany's exports
	DBQuery("DELETE FROM exports WHERE user = 1");

	foreach($_POST as $name => $value) {

		if(eregi("^c([0-9]*)$", $name, $matches) && is_array($value)) {

			// Always for Tiffany at the moment
			$query = "INSERT INTO exports SET user = 1, contact_id = " . $matches[1];

			foreach($value as $v) {
				if($v == 1)
					$query .= ", reminder = 1";
				else if($v == 2)
					$query .= ", mobile = 1";
				else if($v == 3)
					$query .= ", email = 1";
			}

			DBQuery($query);
		}
	}

	// Saved, redirect to the page that actually exports the data
	header("Location: gnanceco_playground.contacts4.php");
}
?>

<center>
<form method="POST" name="export" action="gnanceco_playground.contacts5.php">
<input type="hidden" name="s" value="1">
<table border="1" width="55%">
	<tr>
		<td>Contact</td>
		<td width="100">Reminder</td>
		<td width="100">Yahoo!</td>
		<td width="100">G-Mail</td>
	</tr>

<?
$res = DBQuery("SELECT contacts.id, contacts.name_last, contacts.name_first, exports.reminder, exports.mobile, exports.email 
	FROM contacts LEFT JOIN exports ON contacts.id = exports.contact_id ORDER BY contacts.name_first");

while(($row = mysql_fetch_assoc($res)) != NULL)
{
	echo "<tr>\n";
	echo "<td>" . $row['name_first'] . " " . $row['name_last'] . "</td>\n";
	echo "<td><input type='checkbox' name='c" . $row['id'] . "[]' " . ($row['reminder'] ? 'checked' : '') . " value='1'></td>\n";
	echo "<td><input type='checkbox' name='c" . $row['id'] . "[]' " . ($row['mobile'] ? 'checked' : '') . " value='2'></td>\n";
	echo "<td><input type='checkbox' name='c" . $row['id'] . "[]' " . ($row['email'] ? 'checked' : '') . " value='3'></td>\n";
	echo "</tr>\n";
}
?>

<tr><td colspan="4" align="right"><input type="submit" value="Export"></tr></tr>
</table>
</center>
</form>
