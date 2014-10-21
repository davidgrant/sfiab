<?php
require_once('user.inc.php');
require_once('project.inc.php');

$fair_types = array('sfiab_feeder' => "Feeder Fair",
		    'sfiab_upstream' => 'Upstream Fair',
		    'ysc' => 'Youth Science Canada (Upstream)' );

function fair_load($mysqli, $fair_id, $username=NULL, $data=NULL, $hash=NULL) 
{
	$r = NULL;
	if($fair_id > 0) {
		$fair_id = (int)$fair_id;
		$r = $mysqli->query("SELECT * FROM fairs WHERE id='$fair_id'");
	} else if($username !== NULL) {
		$username = $mysqli->real_escape_string($username);
		$r = $mysqli->query("SELECT * FROM fairs WHERE username='$username' LIMIT 1");
	} else if($hash !== NULL) {
		$hash = $mysqli->real_escape_string($hash);
		$r = $mysqli->query("SELECT * FROM fairs WHERE password='$hash' LIMIT 1");
	}

	/* fetch the fair data from sql, or from $data if specified */
	if($data === NULL) {
		if($r === NULL) {
			debug_print_backtrace();

		}
		print($mysqli->error);

		if($r->num_rows == 0) {
			return NULL;
		}
		$f = $r->fetch_assoc();

	} else {
		$f = $data;
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

function fair_load_all_upstream($mysqli)
{
	global $config;
	$q = $mysqli->query("SELECT * FROM fairs WHERE `type`='sfiab_upstream' ORDER BY name");
	$fairs = array();
	while($f = $q->fetch_assoc()) {
		$fairs[(int)$f['id']] = fair_load($mysqli, 0, NULL, $f);
	}
	return $fairs;
}


function fair_load_by_username($mysqli, $username)
{
	return fair_load($mysqli, 0, $username);
}

function fair_load_by_hash($mysqli, $hash)
{
	return fair_load($mysqli, 0, NULL, NULL, $hash);
}

function fair_save($mysqli, &$f) 
{
	generic_save($mysqli, $f, "fairs", "id");
}

function fair_create($mysqli)
{
	/* 128 char password in base64 so no mysql funniness is necessary */
	$p = base64_encode(mcrypt_create_iv(96));
	$mysqli->query("INSERT INTO fairs (`password`) VALUES ('$p')");
	$fair_id = $mysqli->insert_id;
	return $fair_id;
}

?>
