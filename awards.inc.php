<?php
require_once('filter.inc.php');
require_once('project.inc.php');

$award_types = array('divisional' => 'Divisional',
			'special' => 'Special',
			'other' => 'Other',
			'grand' => 'Grand');


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
	filter_int_or_null($a['order']);

	unset($a['original']);
	$original = $a;
	$a['original'] = $original;

	$a['prizes'] = array();
	$q = $mysqli->query("SELECT * FROM award_prizes WHERE award_id='$id' ORDER BY `order`");
	while($p = $q->fetch_assoc()) {
		$prize = prize_load($mysqli, 0, $p);
		$prize['award'] = &$a;
		$a['prizes'][] = $prize;
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
	$q = $mysqli->query("SELECT * FROM awards WHERE year='{$config['year']}' ORDER BY `type`,`order`");
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

function prize_load($mysqli, $pid, $data=NULL)
{
	if($data === NULL) {
		$q = $mysqli->query("SELECT * FROM award_prizes WHERE id='$pid'");
		$p = $q->fetch_assoc();
	} else {
		$p = $data;
		$pid = $p['id'];
	}
	$p['trophies'] = explode(',', $p['trophies']);
	filter_int($p['id']);
	filter_bool_or_null($p['include_in_script']);
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


function award_load_cwsf($mysqli)
{
	global $config;
	$q = $mysqli->query("SELECT * FROM awards WHERE year='{$config['year']}' AND cwsf_award='1'");
	if($q->num_rows != 1) return NULL;

	return award_load($mysqli, -1, $q->fetch_assoc());
}

function prize_load_winners($mysqli, &$prize)
{
	$q = $mysqli->query("SELECT * FROM winners WHERE awards_prizes_id='{$prize['id']}'");
	$projects = array();
	while($r = $q->fetch_assoc()) {
		$projects[(int)$r['projects_id']] = project_load($mysqli, $r['projects_id']);
		project_load_students($mysqli, $projects[(int)$r['projects_id']]);
	}
	return $projects;
}

?>
