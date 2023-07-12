<?php
// Display a banner indication authorization/security state and the server the user is connected to
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
	$color = "green";
	$txt = "&nbsp;AutoDB Directory protected by HTTP authentication " .
		"(logged in as " . $_SERVER['PHP_AUTH_USER'] . ")";
} else {
	$color = "red";
	$txt = "&nbsp;WARNING: AutoDB is INSECURE by design and is currently available to untrusted users. " .
		"Please configuration some form of authentication";
}
$host = (preg_match("/localhost/", MYSQL_HOST) ? `hostname` : MYSQL_HOST);
?>

<table class="<?= $color ?>" cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td width="72%"><?= $txt ?></td>
		<td align="right">
			<table class="<?= $color ?>" style="padding: 0px; border: 0px;" cellpadding="0" cellspacing="0" border="0">
				<tr>
					<td><img src="gfx/network-idle.gif"></td>
					<td style="padding-left: 5px;">Connected to <?= $host ?>&nbsp;</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<p>
