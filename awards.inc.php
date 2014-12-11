<?php
require_once('filter.inc.php');
require_once('project.inc.php');
require_once('remote.inc.php');
require_once('sponsors.inc.php');
require_once('debug.inc.php');

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


function prize_create($mysqli, &$a)
{
	$q = $mysqli->query("SELECT MAX(ord) FROM award_prizes WHERE award_id='{$a['id']}'");
	$r = $q->fetch_row();
	$ord = (int)$r[0] + 1;

	$mysqli->query("INSERT INTO award_prizes(`award_id`,`ord`) VALUES('{$a['id']}','$ord')");
	$prize_id = $mysqli->insert_id;

	debug("prize_create: created new prize={$prize_id}, ord=$ord for award {$a['id']}\n");
	
	$a['prizes'][$prize_id] = prize_load($mysqli, $prize_id);
	return $prize_id;
}

/* Can be called independently, but not a good idea, can't save a prize indepedently */
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

/* Remember to save the award after doing this */
function prize_delete($mysqli, &$a, $pid)
{
	$mysqli->real_query("DELETE FROM award_prizes WHERE id='$pid'");

	unset($a['prizes'][$pid]);
	/* Remove it from the prizes_in_order too */

}

function award_delete($mysqli, &$a)
{
	$mysqli->real_query("DELETE FROM award_prizes WHERE award_id='{$a['id']}'");
	$mysqli->real_query("DELETE FROM awards WHERE id='{$a['id']}'");
}

function award_save($mysqli, &$a)
{
	$original_feeder_fairs = $a['original']['feeder_fair_ids'];

	foreach($a['prizes'] as $pid=>&$p) {
		generic_save($mysqli, $p, "award_prizes", "id");
	}
	generic_save($mysqli, $a, "awards", "id");

	/* Does this award have any feeder fairs? or did the feeder fairs change? */
	if($original_feeder_fairs != $a['feeder_fair_ids'] || count($a['feeder_fair_ids']) > 0) {
		/* Broadcast this award to all feeder fairs, using the queue.
		 * Any fairs that aren't allowed to have it will be sent a delete */
		remote_queue_push_award_to_all_fairs($mysqli, $a);
	}
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
	global $config;
	$year = intval($incoming_award['year']);
	$incoming_award_id = intval($incoming_award['id']);
	if($year <= 0) exit();
	if($incoming_award_id <= 0) exit;

	$q = $mysqli->query("SELECT * FROM awards WHERE upstream_fair_id='{$fair['id']}' AND upstream_award_id='$incoming_award_id' AND year='$year'");
	if($q->num_rows > 0) {
		/* Award exists, we can load and update */
		$data = $q->fetch_assoc();
		$a = award_load($mysqli, -1, $data);
		debug("award_sync: found local award id={$a['id']}\n");
	} else {
		/* Create a new award */
		$aid = award_create($mysqli, $year);
		$a = award_load($mysqli, $aid);
		debug("award_sync: created new award id={$a['id']}\n");
	}

	if(array_key_exists('delete', $incoming_award)) {
		debug("award_sync: deleting award.\n");
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
		$cat_id = category_get_from_grade($mysqli, $g);
		if(!in_array($cat_id, $a['categories'])) {
			$a['categories'][] = $cat_id;
		}
	}


	/* upstream prize id -> our prize id */
	$upstream_prize_id_map = array();
	$local_prizes_seen = array();
	foreach($a['prizes'] as $pid=>&$p) {
		$upstream_prize_id_map[$p['upstream_prize_id']] = $pid;
		$local_prizes_seen[$pid] = false;
	}

	$seen_prize_ids = array();
	foreach($incoming_award['prizes'] as &$incoming_prize) {
		if(array_key_exists($incoming_prize['id'], $upstream_prize_id_map)) {
			$prize_id = $upstream_prize_id_map[$incoming_prize['id']];
			debug("award_sync: found existing prize={$prize_id}\n");
		} else {
			/* Create it */
			$prize_id = prize_create($mysqli, $a);
			debug("award_sync: created new prize={$prize_id}\n");
		}
		$p = &$a['prizes'][$prize_id];

		/* Overwrite or set prize feields */
		$p['name'] = $incoming_prize['name'];
		$p['cash'] = $incoming_prize['cash'];
		$p['scholarship'] = $incoming_prize['scholarship'];
		$p['value'] = $incoming_prize['value'];
		$p['trophies'] = $incoming_prize['trophies'];
		$p['number'] = $incoming_prize['number'];
		$p['upstream_register_winners'] = $incoming_prize['upstream_register_winners'];
		$p['upstream_prize_id'] = $incoming_prize['id'];
		$local_prizes_seen[$prize_id] = true;
	}

	/* Any prizes we didn't see have been removed from this award */
	foreach($local_prizes_seen as $pid=>$seen) {
		if($seen == false) {
			debug("award_sync: delete prize={$pid}\n");
			prize_delete($mysqli, $a, $pid);	
		}
	}
//	debug("award_sync: save award: ".print_r($a, true)."\n");
	award_save($mysqli, $a);
}

/* Get a copy of an award for exporting, basically just make a copy and
 * delete stuff we don't want or need to export */
function award_get_export($mysqli, &$fair, &$a) 
{
	global $config;
	$categories = categories_load($mysqli, $a['year']);

	/* Is this fair allowed to have this award?  if not, just send
	 * the award id, year, and a delete flag */
	debug("award_get_export: feeder fair: {$fair['id']}, ids=".print_r($a['feeder_fair_ids'], true)."\n");

	if(!in_array($fair['id'], $a['feeder_fair_ids'])) {
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
		unset($p['award']);
	}
	unset($export_a['c_desc']);
	unset($export_a['presenter']);
	unset($export_a['cwsf_award']);
	unset($export_a['original']);
	unset($export_a['prizes_in_order']);

	/* Turn categories into grades */
	$export_a['grades'] = array();
	foreach($a['categories'] as $cat_id) {
		$cat = $categories[$cat_id];
		for($g=$cat['min_grade'] ; $g<=$cat['max_grade']; $g++) {
			$export_a['grades'][] = $g;
		}
	}

	/* Turn any sponsor into just an organization name */
	$export_a['sponsor_organization'] = $config['fair_abbreviation'];
	if($a['sponsor_uid'] > 0) {
		$u_sponsor = user_load($mysqli, $a['sponsor_uid']);
		$export_a['sponsor_organization'] = $u_sponsor['organization'];
	}
	return $export_a;
}

?>
