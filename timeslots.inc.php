<?php
require_once('filter.inc.php');

function timeslot_load($mysqli, $id , $data = NULL)
{
	global $config;
	$id = (int)$id;
	if($id != 0) {
		$q = $mysqli->query("SELECT * FROM timeslots WHERE id='$id'");
		$a = $q->fetch_assoc();
		print($mysqli->error);
	} else {
		$a = $data;
		$id = $a['id'];
	}

	filter_int($a['start']); /* in minutes */
	filter_int($a['num_timeslots']);
	filter_int($a['timeslot_length']); /* in minutes */
	filter_int($a['round']);

	unset($a['original']);
	$original = $a;
	$a['original'] = $original;

	/* Store when the round starts and stops as a timestamp 
	 * so it's easy to $str = date('Y-m-d H:i:s', $timestamp); */
	$fair_start = strtotime($config['date_fair_begins']);
	$a['start_timestamp'] = strtotime($config['date_fair_begins']) + (60*$a['start']);
	$a['end_timestamp'] = $a['start_timestamp'] + ($a['num_timeslots']*$a['timeslot_length']*60);

	/* Create timeslots */
	$a['timeslots'] = array();
	for($x=0;$x<$a['num_timeslots']; $x++) {
		$num = $x + 1;
		$ts[$num] = array();
		$ts[$num]['start_timestamp'] = $a['start_timestamp'] + (60 * $x * $a['timeslot_length']);
		$ts[$num]['num'] = $num;
		$ts[$num]['timeslot_id'] = $a['id'];
		$ts[$num]['round'] = $a['round'];
	}

	return $a;
}

function timeslots_load_all($mysqli)
{
	global $config;
	static $timeslots = NULL;
	if($timeslots === NULL) {
		$q = $mysqli->query("SELECT * FROM timeslots WHERE year='{$config['year']}' ORDER BY `round`");
		$timeslots = array();
		while($d = $q->fetch_assoc()) {
			$timeslots[(int)$d['id']] = timeslot_load($mysqli, 0, $d);
		}
	}
	return $timeslots;
}

function timeslot_create($mysqli)
{
	global $config;
	/* Count rounds, insert new timeslot with new round, update config */
	$q = $mysqli->query("SELECT COUNT(`id`) AS c FROM timeslots WHERE year='{$config['year']}'");
	$r = $q->fetch_assoc();
	$c = (int)$r['c'] + 1;
	$mysqli->query("INSERT INTO timeslots(`year`,`round`,`name`) VALUES ('{$config['year']}','{$r['c']}','Round $c')");
	return $mysqli->insert_id;
}

function timeslot_save($mysqli, $a)
{
	generic_save($mysqli, $a, "timeslots", "id");
}

function timeslot_delete($mysqli, $id)
{
	global $config;
	$ts = timeslot_load($mysqli, $id);
	$mysqli->real_query("UPDATE timeslots SET `round`=`round`-1 WHERE `round`>{$ts['round']} AND year='{$config['year']}'");
	$mysqli->real_query("DELETE FROM timeslots WHERE id='$id'");
}
?>
