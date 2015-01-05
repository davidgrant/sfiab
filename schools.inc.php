<?php
require_once('project.inc.php');

function school_load($mysqli, $id , $data = NULL)
{
	$id = (int)$id;
	if($id != 0) {
		$q = $mysqli->query("SELECT * FROM schools WHERE id='$id'");
		$t = $q->fetch_assoc();
		print($mysqli->error);
	} else {
		$t = $data;
		$id = $t['id'];
	}
	unset($t['original']);
	$original = $t;
	$t['original'] = $original;
	
	return $t;
}


function school_load_all($mysqli)
{
	global $config;
	$q = $mysqli->query("SELECT * FROM schools WHERE year='{$config['year']}' ORDER BY city,school");
	$schools = array();
	while($d = $q->fetch_assoc()) {
		$t = school_load($mysqli, false, $d);
		$schools[$t['id']] = $t;
	}
	return $schools;
}

function school_save($mysqli, &$t)
{
	generic_save($mysqli, $t, "schools", "id");
}

function school_create($mysqli, $year=NULL) 
{
	global $config;
	if($year === NULL) $year = $config['year'];
	$r = $mysqli->real_query("INSERT INTO schools(`year`) VALUES('$year')");
	$tid = $mysqli->insert_id;
	return $tid;
}

