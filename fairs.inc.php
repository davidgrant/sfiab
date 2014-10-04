<?php
require_once('user.inc.php');
require_once('project.inc.php');

function fair_load($mysqli, $fair_id, $username=NULL, $data=NULL) 
{
	if($fair_id > 0) {
		$fair_id = (int)$fair_id;
		$r = $mysqli->query("SELECT * FROM fairs WHERE id='$fair_id'");
	} else if($username !== NULL) {
		$username = $mysqli->real_escape_string($username);
		$r = $mysqli->query("SELECT * FROM fairs WHERE username='$username' LIMIT 1");
	} 

	/* fetch the fair data from sql, or from $data if specified */
	if($data === NULL) {
		print($mysqli->error);

		if($r->num_rows == 0) {
			return NULL;
		}
		$f = $r->fetch_assoc();

	} else {
		$f = $data;
	}

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

function fair_load_all($mysqli) 
{
	global $config;
	$q = $mysqli->query("SELECT * FROM fairs ORDER BY name");
	$fairs = array();
	while($f = $q->fetch_assoc()) {
		$fairs[(int)$f['id']] = fair_load($mysqli, 0, NULL, $f);
	}
	return $fairs;
}

function fair_load_all_feeder($mysqli)
{
	global $config;
	$q = $mysqli->query("SELECT * FROM fairs WHERE `type`='sfiab_feeder' ORDER BY name");
	$fairs = array();
	while($f = $q->fetch_assoc()) {
		$fairs[(int)$f['id']] = fair_load($mysqli, 0, NULL, $f);
	}
	return $fairs;
}


function fair_load_by_username($mysqli, $username)
{
	return fair_load($mysqli, NULL, $username);
}

function fair_save($mysqli, &$f) 
{
	generic_save($mysqli, $f, "fairs", "id");
}

function fair_create($mysqli)
{
	$p = user_new_password();
	$mysqli->query("INSERT INTO fairs (`password`) VALUES ('$p')");
	$fair_id = $mysqli->insert_id;
	return $fair_id;
}

?>
