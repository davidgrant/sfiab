<?php
require_once('filter.inc.php');

function timeslot_load($mysqli, $id , $data = NULL)
{
		$id = (int)$id;
	if($id != 0) {
		$q = $mysqli->query("SELECT * FROM timeslots WHERE id='$id'");
		$a = $q->fetch_assoc();
		print($mysqli->error);
	} else {
		$a = $data;
		$id = $a['id'];
	}

	filter_int_or_null($a['pid']);
	filter_int_or_null($a['judging_team_id']);
	filter_int_or_null($a['ord']);

	unset($a['original']);
	$original = $a;
	$a['original'] = $original;
	return $a;
}

function timeslots_load_all($mysqli)
{
	global $config;
	$q = $mysqli->query("SELECT * FROM timeslots WHERE year='{$config['year']}' ORDER BY `num`");
	$timeslots = array();
	while($d = $q->fetch_assoc()) {
		$timeslots[(int)$d['num']] = timeslot_load($mysqli, 0, $d);
	}
	return $timeslots;
}


function timeslot_save($mysqli, $a)
{
	generic_save($mysqli, $a, "timeslots", "id");
}

?>
