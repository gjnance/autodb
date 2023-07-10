
<? include "adb_config.php"; ?>

function Submit(bClear, bForceSelect) {
	if (bForceSelect && document.autodb_form.dbaction)
		document.autodb_form.dbaction[1].selected = true;

	// Clear where, limit, and order when changing databases or tables
	if (bClear) {
		if (document.autodb_form.where)
			document.autodb_form.where.value = "";
		if (document.autodb_form.prev_where)
			document.autodb_form.prev_where.value = "";
		if (document.autodb_form.limit)
			document.autodb_form.limit.value = "";
		if (document.autodb_form.order)
			document.autodb_form.order.value = "";
	}

	document.autodb_form.submit();
}

function UpdateRow(key_cols) {
	for (col in key_cols)
		document.getElementById("_adbkeycol_" + col).value = key_cols[col];

	// Select "INSERT" mode and submit the form
	document.autodb_form.dbaction[2].selected = true;
	document.autodb_form.submit();
}

function CopyRow(key_cols) {
	for (col in key_cols)
		document.getElementById("_adbkeycol_" + col).value = key_cols[col];

	// Select "INSERT" mode, set the copyrow variable, and submit the form
	document.autodb_form.copyrow.value = 1;
	document.autodb_form.dbaction[2].selected = true;
	document.autodb_form.submit(true);
}

function DeleteRow(key_cols) {
	if (confirm('Really Delete This Row?')) {
		for (col in key_cols)
			document.getElementById("_adbkeycol_" + col).value = key_cols[col];
		document.autodb_form.deleterow.value = 1;
		document.autodb_form.submit();
	} else {
		return false;
	}
}

function GetSuggestions(table, col, value)
{
	// Instantiate an XML HTTP Request object
	try {
		request = new XMLHttpRequest();
	} catch (trymicrosoft) {
		try {
			request = new ActiveXObject("Msxml2.XMLHTTP");
		} catch (othermicrosoft) {
			try {
				request = new ActiveXObject("Microsoft.XMLHTTP");
			} catch (failed) {
				request = false;
			}
		}
	}

	// Initialized?
	if (!request)
		return alert("XML HTTP Request Initialization Failure");

	url = '<?= AUTODB_BASEURL ?>/suggest.php?dbtable=' + table + '&col=' + col + '&value=' + value;

	request.open('GET', url, true);

	// Response Handler
	request.onreadystatechange = function()
	{
		if (request.readyState == 4) {
			// Set the layer's innerHTML to the content returned by suggest.php
			document.getElementById('_adbdiv_' + col).innerHTML = request.responseText;

			// Make the layer visible
			document.getElementById('_adbdiv_' + col).style.visibility = "visible";

			document.getElementById('_adbdiv_' + col).style.width =
				document.getElementById('_adbdsp_' + col).style.width;
		}
	}

	// Make the request
	request.send(null);
}

function KeyPress(divname, keyCode)
{
	// Loop through options in the master list
	odiv = document.getElementById("_adbdiv_" + divname);

	var prev = odiv.lastChild;
	var next = odiv.firstChild;
	var cur = null;

	for(i=0; i<odiv.childNodes.length; i++) {
		o = odiv.childNodes[i];

		// Is this option selected?
		if (o.selected) {
			cur = o;
			prev = o.previousSibling;
			next = o.nextSibling;
		}
	}

	switch(keyCode) {
	case 13:
		// Enter
		SelectRow(divname, cur.innerHTML, cur.id);
		return true;
	case 40:
		// Down arrow
		if (next)
			HighlightRow(next);
		return true;
	case 38:
		// Up arrow
		if (prev)
			HighlightRow(prev);
		return true;
	// Gobble up keystrokes that we don't need or want processed
	case 16:		// Shift
	case 17:		// Ctrl
		return true;
	default:
		return false;
	}
}

function HighlightRow(o)
{
	// Make sure all other rows are "unhovered"
	pdiv = o.parentNode;

	for(i=0; i<pdiv.childNodes.length; i++) {
		pdiv.childNodes[i].style.background = '#FFFF99';
		pdiv.childNodes[i].style.color = 'black';
		pdiv.childNodes[i].selected = false;
	}

	o.style.background = 'black';
	o.style.color = 'white';
	o.style.cursor = 'pointer';
	o.selected = true;
}

function SelectRow(adb_t1_relcol, dsp_value, db_value)
{
	//alert(document.getElementById('_adbdsp_' + adb_t1_relcol) + ',' +
		//document.getElementById('_adb_' + adb_t1_relcol) + ',' +
		//document.getElementById('_adbdiv_' + adb_t1_relcol));
	document.getElementById('_adbdsp_' + adb_t1_relcol).value = dsp_value;
	document.getElementById('_adb_' + adb_t1_relcol).value = db_value;
	document.getElementById('_adbdiv_' + adb_t1_relcol).style.visibility = "hidden";
}
