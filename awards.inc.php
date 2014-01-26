<?php

function award_load($mysqli, $id , $data = NULL)
{
		$id = (int)$id;
	if($id != 0) {
		$q = $mysqli->query("SELECT * FROM awards WHERE id='$id'");
		$a = $q->fetch_assoc();
		print($mysqli->error);
	} else {
		$a = $data;
		$id = $a['id'];
	}

	$a['categories'] = explode(',',$a['categories']);

	$a['prizes'] = array();
	$q = $mysqli->query("SELECT * FROM award_prizes WHERE award_id='$id' ORDER BY `order`");
	while($p = $q->fetch_assoc()) {
		$a['prizes'][] = prize_load($mysqli, 0, $p);
	}

	unset($a['original']);
	$original = $a;
	$a['original'] = $original;
	return $a;
}

function award_load_all($mysqli)
{
	global $config;
	$q = $mysqli->query("SELECT * FROM awards WHERE year='{$config['year']}' ORDER BY `type`,`order`");
	$awards = array();
	while($d = $q->fetch_assoc()) {
		$awards[] = award_load($mysqli, 0, $d);
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
	print($mysqli->error);
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
	unset($p['original']);
	$original = $p;
	$p['original'] = $original;
	return $p;
}

function award_save($mysqli, $a)
{
	generic_save($mysqli, $a, "awards", "id");
}

?>
