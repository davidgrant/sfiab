<?php
require_once('filter.inc.php');
require_once('project.inc.php');
require_once('remote.inc.php');


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
	filter_bool($p['upstream_register_winners']);
	filter_int($p['upstream_prize_id']);

	unset($p['original']);
	$original = $p;
	$p['original'] = $original;
	return $p;
}

function prize_delete($mysqli, &$p)
{
	$mysqli->real_query("DELETE FROM award_prizes WHERE id='{$p['id']}");
}

function award_delete($mysqli, &$a)
{
	foreach($a['prizes'] as $pid=>&$p) {
		prize_delete($mysqli, $p);
	}
	$mysqli->real_query("DELETE FROM awards WHERE id='{$a['id']}");
}

function award_save($mysqli, &$a)
{
	$original_feeder_fairs = $a['original']['feeder_fair_ids'];

	generic_save($mysqli, $a, "awards", "id");

	/* Does this award have any feeder fairs? or did the feeder fairs change? */
	if($original_feeder_fairs != $a['feeder_fair_ids'] || count($a['feeder_fair_ids']) > 0) {
		/* Feeder fairs changed, broadcast this award to all feeder
		 * fairs, fairs that aren't allowed * to have it will be sent a
		 * delete */
		
		remote_push_award_to_all_fairs($mysqli, $a);
	}
}
function prize_save($mysqli, &$p)
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


function award_sync($mysqli, $fair, $incoming_award)
{
	$year = intval($incoming_award['year']);
	$incoming_award_id = intval($incoming_award['id']);
	if($year <= 0) exit();
	if($incoming_award_id <= 0) exit;

	categories_load($mysqli, $year);


	$q = $mysqli->query("SELECT * FROM awards WHERE upstream_fair_id='{$fair['id']}' AND upstream_award_id='$incoming_award_id' AND year='$year'");
	if($q->num_rows > 0) {
		/* Award exists, we can load and update */
		$data = $q->fetch_assoc();
		$a = award_load($mysqli, -1, $data);
	} else {
		/* Create a new award */
		$aid = award_create($mysqli);
		$a = award_load($mysqli, $aid);
	}

	if($incoming_award['delete'] == 1) {
		award_delete($mysqli, $a);
		return;
	}

	$a['name'] = $incoming_award['name'];
	$a['s_desc'] = $incoming_award['s_desc'];
	$a['j_desc'] = $incoming_award['j_desc'];
	$a['schedule_judges'] = $incoming_award['schedule_judges'];
	$a['include_in_script'] = $incoming_award['include_in_script'];
	$a['self_nominate'] = $incoming_award['self_nominate'];
	$a['type'] = $incoming_award['type'];
	$a['upstream_fair_id'] = $fair['id'];
	$a['upstream_award_id'] = $incoming_award_id;
	$a['sponsor_uid'] = sponsor_create_or_get($mysqli, $incoming_award['sponsor_organization'], $year);

	/* Map grades to categories */
	$a['categories'] = array();
	foreach($incoming_award['grades'] as $g) {
		$cat_id = category_get_from_grade($g);
		if(!in_array($cat_id, $a['categories'])) {
			$a['categories'][] = $cat_id;
		}
	}

	/* upstream prize id -> our prize id */
	$upstream_prize_id_map = array();
	foreach($a['prizes'] as $pid=>&$p) {
		$upstream_prize_id_map[$p['upstream_prize_id']] = $pid;
		$p['seen'] = false;
	}

	$seen_prize_ids = array();
	foreach($incoming_award['prizes'] as &$incoming_prize) {
		if(array_key_exists($incoming_prize['id'], $upstream_prize_id_map)) {
			$prize_id = $upstream_prize_id_map[$incoming_prize['id']];
			$p = &$a['prizes'][$prize_id];
		} else {
			/* Create it */
			$prize_id = prize_create($mysqli, $a);
			$p = prize_load($mysqli, $prize_id);
		}
		$p['name'] = $incoming_prize['name'];
		$p['cash'] = $incoming_prize['cash'];
		$p['scholarship'] = $incoming_prize['scholarship'];
		$p['value'] = $incoming_prize['value'];
		$p['trophies'] = $incoming_prize['trophies'];
		$p['number'] = $incoming_prize['number'];
		$p['upstream_register_winners'] = $incoming_prize['upstream_register_winners'];
		prize_save($mysqli, $p);
		$p['seen'] = true;
	}

	/* Any prizes we didn't see have been removed */
	foreach($a['prizes'] as $pid=>&$p) {
		if($p['seen'] == false) {
			prize_delete($mysqli, $p);
		}
	}
}

/* Get a copy of an award for exporting, basically just make a copy and
 * delete stuff we don't want or need to export */
function award_get_export(&$a) 
{
	global $categories;
	categories_load($mysqli);

	/* Is this fair allowed to have this award?  if not, just send
	 * the award id, year, and a delete flag */
	if(in_array($fair_id, $award['feeder_fair_ids'])) {
		$export_a = array();
		$export_a['id'] = $a['id'];
		$export_a['year'] = $a['year'];
		$export_a['delete'] = 1;
		return $export_a;
	}

	$export_a = $a;
	foreach($export_a['prizes'] as $pid=>&$p) {
		unset($p['original']);
		unset($p['ord']);
		unset($p['upstream_prize_id']);
	}
	unset($export_a['c_desc']);
	unset($export_a['presenter']);
	unset($export_a['cwsf_award']);

	$export_a['grades'] = array();
	/* Turn categories into grades */
	foreach($a['categories'] as $cat_id) {
		$cat = $categories['cat_id'];
		for($g=$cat['min_grade'] ; $g<=$cat['max_grade']; $g++) {
			$export_a['grades'][] = $g;
		}
	}
	return $export_a;
}

?>
