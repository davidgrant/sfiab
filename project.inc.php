<?php
require_once('filter.inc.php');

function projects_load_all($mysqli)
{
	global $config;
	$q = $mysqli->query("SELECT * FROM projects WHERE year={$config['year']} ORDER BY number_sort");
	$projects = array();
	while($p = $q->fetch_assoc()) {
		$pr = project_load($mysqli, $p['pid'], $p);
		$projects[(int)$p['pid']] = $pr;
	}
	return $projects;
}

function project_load($mysqli, $pid, $pdata = false)
{
	if($pid == NULL) return NULL;

	if($pdata == false) {
		$r = $mysqli->query("SELECT * FROM projects WHERE pid=$pid LIMIT 1");
		if($r->num_rows == 0) {
			return NULL;
		}
		$p = $r->fetch_assoc();
	} else {
		$p = $pdata;
	}

	/* Set all fields to sane values */
	project_filter($p);

	/* Store an original copy so save() can figure out what (if anything) needs updating */
	unset($p['original']);
	$original = $p;
	$p['original'] = $original;

	return $p;
}



function project_filter(&$p) 
{
	/* Filter all fields and set them to appropriate values.
	 * Useful after reading data from a _POST or from mysql
	 * when everythign is a string */
	filter_int($p['pid']);
	filter_int_or_null($p['cat_id']);
	filter_int_or_null($p['challenge_id']);
	filter_int_or_null($p['isef_id']);
	filter_int_or_null($p['req_electricity']);
	filter_int_or_null($p['num_students']);
	filter_int_or_null($p['num_mentors']);

	if(!is_array($p['ethics'])) {
		if($p['ethics'] == NULL) {
			$p['ethics'] = array();
		} else {
			$p['ethics'] = unserialize($p['ethics']);
		}

		$fields = array(
			'human1', 'animals', 'humansurvey1', 'humanfood',
			'humanfood1', 'humanfood2', 'humanfood6', 'humantest1',
			'humanfood3', 'humanfood4', 'humanfood5', 'animal_vertebrate',
			'animal_ceph', 'animal_tissue', 'animal_drug', 'humanfooddrug',
			'humanfoodlow1', 'humanfoodlow2', 'agree');
		foreach($fields as $f) {
			if(!array_key_exists($f, $p['ethics'])) {
				$p['ethics'][$f] = NULL;
			}
		}

	}

	if(!is_array($p['safety'])) {
		if($p['safety'] == NULL) {
			$p['safety'] = array();
		} else {
			$p['safety'] = unserialize($p['safety']);
		}
		$fields = array(
			'display1', 'display2', 'display3',
			'institution',
			'electrical1', 'electrical2', 'electrical3', 'electrical4',
			'animals1', 'animals2', 'animals3',
			'bio1', "bio2", "bio3", "bio4", "bio5", "bio6",
			'hazmat1', "hazmat2", "hazmat3", "hazmat4", "hazmat5",
			'food1', "food2", "food3", "food4", "food5",
			'mech1', "mech2", "mech3", "mech4", "mech5", "mech6", 'mech7',
			'agree'
			);

		foreach($fields as $f) {
			if(!array_key_exists($f, $p['safety'])) {
				$p['safety'][$f] = NULL;
			}
		}
	}
		
}

function project_load_students($mysqli, &$p) 
{
	$users = array();
	$r = $mysqli->query("SELECT * FROM users WHERE s_pid={$p['pid']}");
	while($u = $r->fetch_assoc()) {
		$users[] = user_load($mysqli, -1, -1, NULL, $u);
	}
	return $users;
}

function project_create($mysqli) 
{
	global $config;
	$r = $mysqli->real_query("INSERT INTO projects(`year`) VALUES('{$config['year']}')");
	$pid = $mysqli->insert_id;
	return $pid;
}

function generic_save($mysqli, &$p, $table, $table_key) 
{

	global $sfiab_roles;
	/* Find any fields that changed */
	/* Construct a query to update just those fields */
	/* Always save in the current year */
	$set = "";
	foreach($p as $key=>$val) {
		if($key == 'original') continue;
		if(!array_key_exists($key, $p['original'])) continue;

		if($val !== $p['original'][$key]) {
			/* Key changed */
			if($set != '') $set .= ',';

			if($key == 'categories' || $key == 'trophies') {
				/* For awards */
				$v = implode(',', $val);
			} else {
				/* Serialize any non-special arrays */
				if(is_array($val)) 
					$v = serialize($val);
				else 
					$v = $val;

				/* Then for everything, strip slashes and escape */
				$v = stripslashes($v);
				$v = $mysqli->real_escape_string($v);
			}
			$set .= "`$key`='$v'";

			/* Set the original to the unprocessed value */
			$p['original'][$key] = $val;
		}
	}
	//print_r($p);
	if($set != '') {
		$query = "UPDATE $table SET $set WHERE $table_key='{$p[$table_key]}'";
	//	print($query);
		$mysqli->query($query);
	}
}

function project_save($mysqli, &$p)
{
	generic_save($mysqli, $p, "projects", "pid");
}


/* What category IDs can this project register for? */
function project_get_legal_category_ids($mysqli, $pid)
{
	$cats = categories_load($mysqli);

	$highest_grade = 0;
	$q = $mysqli->query("SELECT MAX(`grade`) AS `max_grade` FROM users WHERE users.s_pid='$pid'");

	if($q->num_rows != 1) {
		return array_keys($cats);
	}

	$ret = array();
	$r = $q->fetch_assoc();

	$max_grade = (int)$r['max_grade'];
	foreach($cats as $cid=>$c) {
		if($c['min_grade'] <= $max_grade && $c['max_grade'] >= $max_grade) {
			$ret[] = $cid;
		} else if($c['min_grade'] > $max_grade) {
			$ret[] = $cid;
		}
	}
	return $ret;
}

function mentor_load($mysqli, $id , $data = NULL)
{
	$id = (int)$id;
	if($id != 0) {
		$q = $mysqli->query("SELECT * FROM mentors WHERE id='$id'");
		$m = $q->fetch_assoc();
		print($mysqli->error);
	} else {
		$m = $data;
		$id = $m['id'];
	}

	unset($m['original']);
	$original = $m;
	$m['original'] = $original;
	
	return $m;
}


function mentor_load_all($mysqli, $pid)
{
	$q = $mysqli->query("SELECT * FROM mentors WHERE pid='$pid'");
	$mentors = array();
	while($d = $q->fetch_assoc()) {
		$m = mentor_load($mysqli, false, $d);
		$mentors[$m['id']] = $m;
	}
	return $mentors;
}

function mentor_save($mysqli, &$m)
{
	generic_save($mysqli, $m, "mentors", "id");
}

function mentor_create($mysqli, $pid) 
{
	global $config;
	$r = $mysqli->real_query("INSERT INTO mentors(`pid`) VALUES('$pid')");
	$mid = $mysqli->insert_id;
	return $mid;
}

function tour_load($mysqli, $id , $data = NULL)
{
	$id = (int)$id;
	if($id != 0) {
		$q = $mysqli->query("SELECT * FROM tours WHERE id='$id'");
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


function tour_load_all($mysqli)
{
	global $config;
	$q = $mysqli->query("SELECT * FROM tours WHERE year='{$config['year']}'");
	$tours = array();
	while($d = $q->fetch_assoc()) {
		$t = tour_load($mysqli, false, $d);
		$tours[$t['id']] = $t;
	}
	return $tours;
}

function tour_save($mysqli, &$t)
{
	generic_save($mysqli, $t, "tours", "id");
}

function tour_create($mysqli) 
{
	global $config;
	$r = $mysqli->real_query("INSERT INTO tours(`year`) VALUES('{$config['year']}')");
	$tid = $mysqli->insert_id;
	return $tid;
}

function tour_get_for_student_select($mysqli, &$u)
{
	global $config;
	$q = $mysqli->query("SELECT * FROM tours WHERE year='{$config['year']}'
					AND `grade_min` <= '{$u['grade']}'
					AND `grade_max` >= '{$u['grade']}'");
	while($d = $q->fetch_assoc()) {
		$t = tour_load($mysqli, false, $d);
		/* Change the name */
		$t['name'] = "#{$t['num']}. {$t['name']}";
		$tours[$t['id']] = $t;
	}
	return $tours;
}

?>
