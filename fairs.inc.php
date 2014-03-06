<?php


function fair_load($mysqli, $username) 
{
	$username = $mysqli->real_escape_string($username);
	$r = $mysqli->query("SELECT * FROM fairs WHERE username='$username' LIMIT 1");
	print($mysqli->error);

	if($r->num_rows == 0) {
		return NULL;
	}
	$f = $r->fetch_assoc();

	if($f['award_ids'] == '' or $f['award_ids'] === NULL) {
		$f['award_ids'] = array();
	} else {
		$f['award_ids'] = explode(',', $f['award_ids']);
	}

	/* Store an original copy so save() can figure out what (if anything) needs updating */
	unset($f['original']);
	$original = $f;
	$f['original'] = $original;

	return $f;
}

function fair_save($mysqli, &$f) 
{
	generic_save($mysqli, "fairs", $f);
}
