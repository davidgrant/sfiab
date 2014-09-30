<?php
require_once('filter.inc.php');
require_once('project.inc.php');

$award_types = array('divisional' => 'Divisional',
			'special' => 'Special',
			'other' => 'Other',
			'grand' => 'Grand');

$award_trophies = array('keeper'=>'Student Keeper',
			'return'=>'Student Return',
			'school_keeper'=>'School Keeper',
			'school_return'=>'School Return');


function award_create($mysqli, $year)
{
	$mysqli->query("INSERT INTO awards(`year`) VALUES('$year')");
	$aid = $mysqli->insert_id;
	return $aid;
}

function award_load($mysqli, $id , $data = NULL)
{
	$id = (int)$id;
	if($data !== NULL) {
		$a = $data;
		$id = $a['id'];
	} else {
		$q = $mysqli->query("SELECT * FROM awards WHERE id='$id'");
		$a = $q->fetch_assoc();
		print($mysqli->error);
	}

	$a['categories'] = explode(',',$a['categories']);
	filter_bool_or_null($a['schedule_judges']);
	filter_bool_or_null($a['self_nominate']);
	filter_bool_or_null($a['include_in_script']);
	filter_int_or_null($a['ord']);
	filter_int($a['sponsor_uid']);
	filter_int($a['upstream_fair_id']);
	filter_int($a['upstream_award_id']);
	filter_int_list($a['feeder_fair_ids']);

	/* Make a copy for the original */
	unset($a['original']);
	$original = $a;
	$a['original'] = $original;

	/* This reverse-links $prize['award'] to a reference to the original award, 
	 * so it does create a recursion, but can't really avoid that unless we make 
	 * more copies */
	$a['prizes'] = array();
	$a['prizes_in_order'] = array();
	$q = $mysqli->query("SELECT * FROM award_prizes WHERE award_id='$id' ORDER BY `ord`");
	while($p = $q->fetch_assoc()) {
		$prize = prize_load($mysqli, 0, $p);
		$pid = $prize['id'];

		$a['prizes'][$pid] = $prize;
		/* Link back to award */
		$a['prizes'][$pid]['award'] = &$a;
		/* Also store it in order as a reference */
		$a['prizes_in_order'][] = &$a['prizes'][$pid];
	}


	return $a;
}

function award_load_by_prize($mysqli, $prize_id)
{
	/* Find the award to load */
	$q = $mysqli->query("SELECT award_id FROM award_prizes WHERE id='$pid'");
	$p = $q->fetch_assoc();
	return award_load($mysqli, $p['award_id']);
}

function award_load_all($mysqli)
{
	global $config;
	$q = $mysqli->query("SELECT * FROM awards WHERE year='{$config['year']}' ORDER BY `ord`");
	$awards = array();
	while($d = $q->fetch_assoc()) {
		$awards[intval($d['id'])] = award_load($mysqli, 0, $d);
	}
	return $awards;
}

function award_load_special_for_select($mysqli)
{
	global $config;
	$q = $mysqli->query("SELECT * FROM awards WHERE year='{$config['year']}' AND schedule_judges='1' ORDER BY `name`");
	$awards = array();
	while($d = $q->fetch_assoc()) {
		$awards[$d['id']] = award_load($mysqli, 0, $d);
	}
	return $awards;
}

function award_load_special_for_project_select($mysqli, &$p)
{
	global $config;
	$q = $mysqli->query("SELECT * FROM awards WHERE `type`='special' 
					AND FIND_IN_SET('{$p['cat_id']}',`categories`)>0
					AND `self_nominate` = 1
					AND year='{$config['year']}'
					ORDER BY `name`");
//	print($mysqli->error);
	$awards = array();
	while($d = $q->fetch_assoc()) {
		$awards[$d['id']] = award_load($mysqli, 0, $d);
	}
	return $awards;
}


function prize_create($mysqli, $award_id)
{
	$q = $mysqli->query("SELECT MAX(ord) AS c FROM award_prizes WHERE award_id='$award_id'");
	$r = $q->fetch_assoc();
	$ord = (int)$r['c'] + 1;

	$mysqli->query("INSERT INTO award_prizes(`award_id`,`ord`) VALUES('$award_id','$ord')");
	$prize_id = $mysqli->insert_id;
	return $prize_id;
}

function prize_load($mysqli, $pid, $data=NULL)
{
	if($data === NULL) {
		$q = $mysqli->query("SELECT * FROM award_prizes WHERE id='$pid'");
		$p = $q->fetch_assoc();
	} else {
		$p = $data;
		$pid = $p['id'];
	}
	
	filter_str_list($p['trophies']);
	filter_int($p['id']);
	filter_bool($p['external_register_winners']);

	unset($p['original']);
	$original = $p;
	$p['original'] = $original;
	return $p;
}

function award_save($mysqli, $a)
{
	generic_save($mysqli, $a, "awards", "id");
}
function prize_save($mysqli, $p)
{
	generic_save($mysqli, $p, "award_prizes", "id");
}


function award_load_cwsf($mysqli)
{
	global $config;
	$q = $mysqli->query("SELECT * FROM awards WHERE year='{$config['year']}' AND cwsf_award='1'");
	if($q->num_rows != 1) return NULL;

	return award_load($mysqli, -1, $q->fetch_assoc());
}

function prize_load_winners($mysqli, &$prize)
{
	$q = $mysqli->query("SELECT * FROM winners WHERE award_prize_id='{$prize['id']}'");
	$projects = array();
	while($r = $q->fetch_assoc()) {
		$pid = (int)$r['pid'];
		$projects[$pid] = project_load($mysqli, $pid);
		project_load_students($mysqli, $projects[$pid]);
	}
	return $projects;
}

?>
