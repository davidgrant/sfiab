<?php
require_once('filter.inc.php');
require_once('debug.inc.php');

function projects_load_all($mysqli, $only_accepted=true, $year = NULL)
{
	global $config;
	if($year == NULL) $year = $config['year'];
	$acc = $only_accepted ? " accepted='1' AND ": '';

	$q = $mysqli->query("SELECT * FROM projects WHERE $acc year='$year' ORDER BY number_sort");

	$projects = array();
	while($p = $q->fetch_assoc()) {
		$pr = project_load($mysqli, -1, $p);
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
	filter_int($p['accepted']);
	filter_int($p['disqualified_from_awards']);
	filter_bool($p['ethics_approved']);
	filter_str_list($p['unavailable_timeslots']);
	filter_int_list($p['sa_nom']);
	filter_bool_or_null($p['cwsf_rsf_has_competed']);
	filter_bool_or_null($p['cwsf_rsf_will_compete']);

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
	$r = $mysqli->query("SELECT * FROM users WHERE s_pid='{$p['pid']}'");
	while($u = $r->fetch_assoc()) {
		$users[] = user_load($mysqli, -1, -1, NULL, $u);
	}
	$p['students'] = $users;
}

function project_create($mysqli, $year = NULL) 
{
	global $config;
	if($year === NULL) $year = $config['year'];
	$r = $mysqli->real_query("INSERT INTO projects(`year`) VALUES('$year')");
	$pid = $mysqli->insert_id;
	return $pid;
}

/* Sets the project age category based on the grades of the student(s).  
 * If different from the current one, clears any special award selections too */
function project_update_category($mysqli, &$p)
{
	$q = $mysqli->query("SELECT MAX(`grade`) FROM `users` WHERE `s_pid`='{$p['pid']}'");
	if($q->num_rows > 0) {
		$r = $q->fetch_row();
		$cat_id = category_get_from_grade($mysqli, (int)$r[0]);
	} else {
		$cat_id = 0;
	}

	if($cat_id != $p['cat_id']) {
		$p['cat_id'] = $cat_id;
		$p['sa_nom'] = NULL;
	}
}

/* Removes student uid from project P, creates them a new project, and sync's the project cat_id.
 * Returns the new project id for the student that was removed */
function project_remove_student($mysqli, &$p, $uid)
{
	$remove_u = user_load($mysqli, $uid);
	if($remove_u['s_pid'] == $p['pid']) {
		/* Create a new project and set that to be the user's project */
		$new_pid = project_create($mysqli);
		$remove_u['s_pid'] = $new_pid;
		user_save($mysqli, $remove_u);

		/* Load the new project, and try to set the project cat id */
		$new_p = project_load($mysqli, $new_pid);
		project_update_category($mysqli, $new_p);
		project_save($mysqli, $new_p);
		return $new_pid;
	}
	return NULL;
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

			if($key == 'ethics' || $key == 'safety') {
				/* Serialize associative arrays */
				$v = serialize($val);
			} else {
				/* Just implode normal arrays with a comma */
				if(is_array($val)) 
					$v = implode(',', $val);
				else if(is_null($val)) 
					$v = NULL;
				else 
					$v = $val;
			}
			if(is_null($v)) {
				$set .= "`$key`=NULL";
			} else {
				$v = $mysqli->real_escape_string($v);
				$set .= "`$key`='$v'";
			}
			/* Set the original to the unprocessed value */
			$p['original'][$key] = $val;
		}
	}
//	print_r($p);
	if($set != '') {
		$query = "UPDATE $table SET $set WHERE `$table_key`='{$p[$table_key]}'";
//		print($query);
		$mysqli->real_query($query);
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
	$q = $mysqli->query("SELECT * FROM tours WHERE year='{$config['year']}' ORDER BY `num`");
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

function tour_create($mysqli, $year=NULL) 
{
	global $config;
	if($year === NULL) $year = $config['year'];
	$r = $mysqli->real_query("INSERT INTO tours(`year`) VALUES('$year')");
	$tid = $mysqli->insert_id;
	return $tid;
}

function tour_get_for_student_select($mysqli, &$u)
{
	global $config;

	$grade = ($u['grade'] === NULL) ? 0 : (int)$u['grade'];

	$q = $mysqli->query("SELECT * FROM tours WHERE year='{$config['year']}'
					AND `grade_min` <= '$grade'
					AND `grade_max` >= '$grade'");
	$tours = array();
	while($d = $q->fetch_assoc()) {
		$t = tour_load($mysqli, false, $d);
		/* Change the name */
		$t['name'] = "#{$t['num']}. {$t['name']}";
		$tours[$t['id']] = $t;
	}
	return $tours;
}


function emergency_contact_load($mysqli, $id, $data=NULL)
{
	$id = (int)$id;
	if($data !== NULL) {
		$m = $data;
	} else {
		$q = $mysqli->query("SELECT * FROM emergency_contacts WHERE id='$id'");
		$m = $q->fetch_assoc();
		print($mysqli->error);
	}
	filter_phone($m['phone1']);
	filter_phone($m['phone2']);
	filter_phone($m['phone3']);

	unset($m['original']);
	$original = $m;
	$m['original'] = $original;
	
	return $m;
}

function emergency_contact_load_for_user($mysqli, &$u)
{
	$q = $mysqli->query("SELECT * FROM emergency_contacts WHERE uid='{$u['uid']}' LIMIT 2");
	$contacts = array();
	while($d = $q->fetch_assoc()) {
		$contacts[(int)$d['id']] = emergency_contact_load($mysqli, -1, $d);
	}
	return $contacts;

}

function emergency_contact_save($mysqli, $ec)
{
	generic_save($mysqli, $ec, "emergency_contacts", "id");
}



function project_sync($mysqli, &$fair, &$incoming_project)
{
	/* First find the project or create one */
	$year = intval($incoming_project['year']);
	$incoming_pid = intval($incoming_project['pid']);
	debug("project_sync: year=$year, incoming_pid=$incoming_pid\n");
	if($year <= 0) exit();
	if($incoming_pid <= 0) exit();

	$chals = challenges_load($mysqli, $year);
	

	$q = $mysqli->query("SELECT * FROM projects WHERE feeder_fair_id='{$fair['id']}' AND feeder_fair_pid='$incoming_pid' AND year='$year'");
	print($mysqli->error);
	if($q->num_rows > 0) {
		/* project exists, we can load and update */
		$data = $q->fetch_assoc();
		$p = project_load($mysqli, -1, $data);
		debug("project_sync: found local project id={$p['pid']}\n");
	} else {
		/* Create a project */
		$pid = project_create($mysqli, $year);
		$p = project_load($mysqli, $pid);
		debug("project_sync: create new project, pid={$p['pid']}\n");
	}
	
	$p['feeder_fair_pid'] = $incoming_project['pid'];
	$p['feeder_fair_id'] = $fair['id'];
	$p['title'] = $incoming_project['title'];
	$p['tagline'] = $incoming_project['tagline'];
	$p['abstract'] = $incoming_project['abstract'];
	$p['language'] = $incoming_project['language'];
	$p['number'] = $incoming_project['number'];
	$p['req_electricity'] = $incoming_project['req_electricity'];

	/* try to match the challenge */
	$best_chal_id = 1;
	$best_lev = 10000;
	foreach($chals as $chal_id=>$c) {
		$lev = levenshtein($c['name'], $incoming_project['challenge']);
		if($lev < $best_lev) {
			$best_lev = $lev;
			$best_chal_id = $chal_id;
		}
	}
	$p['challenge_id'] = $best_chal_id;
	debug("project_sync: best challenge match for {$incoming_project['challenge']} is {$chals[$best_chal_id]['name']} with lev=$best_lev\n");

	if(count($incoming_project['mentors']) > 0) {
		debug("project_sync: update mentors\n");
		$mysqli->real_query("DELETE FROM mentors WHERE pid='{$p['pid']}'");
		foreach($incoming_project['mentors'] as $incoming_mid=>&$incoming_m) {
			$mid = mentor_create($mysqli, $p['pid']);
			$m = mentor_load($mysqli, $mid);
			$m['firstname'] = $incoming_m['firstname'];
			$m['lastname'] = $incoming_m['lastname'];
			$m['email'] = $incoming_m['email'];
			$m['phone'] = $incoming_m['phone'];
			$m['organization'] = $incoming_m['organization'];
			$m['position'] = $incoming_m['position'];
			$m['desc'] = $incoming_m['desc'];
			mentor_save($mysqli, $m);
		}
	}
	$p['num_mentors'] = count($incoming_project['mentors']);

	debug("project_sync: update students\n");
	foreach($incoming_project['students'] as $incoming_sid=>&$incoming_s) {
		$u = user_sync($mysqli, $fair, $incoming_s);

		$u['s_pid'] = $p['pid'];

		user_save($mysqli, $u);
	}
	$p['num_students'] = count($incoming_project['students']);
	/* Update the category, requires s_pid set for each student */
	project_update_category($mysqli, $p);

	debug("project_sync: save\n");
	project_save($mysqli, $p);
	return $p;
}

function project_get_export($mysqli, &$fair, &$project)
{
	$chals = challenges_load($mysqli, $project['year']);

	debug("project_get_export: feeder fair: {$fair['id']}\n");

	$export_p = array();

	$export_p['pid'] = $project['pid'];
	$export_p['number'] = $project['number'];
	$export_p['title'] = $project['title'];
	$export_p['tagline'] = $project['tagline'];
	$export_p['abstract'] = $project['abstract'];
	$export_p['language'] = $project['language'];
	$export_p['challenge'] = $chals[$project['challenge_id']]['name'];
	$export_p['req_electricity'] = $project['req_electricity'];
	$export_p['year'] = $project['year'];

	$export_p['mentors'] = array();
	$mentors = mentor_load_all($mysqli, $project['pid']);
	foreach($mentors as $mid=>&$m) {
		$export_p['mentors'][$mid]['firstname'] = $m['firstname'];
		$export_p['mentors'][$mid]['lastname'] = $m['lastname'];
		$export_p['mentors'][$mid]['email'] = $m['email'];
		$export_p['mentors'][$mid]['phone'] = $m['phone'];
		$export_p['mentors'][$mid]['organization'] = $m['organization'];
		$export_p['mentors'][$mid]['position'] = $m['position'];
		$export_p['mentors'][$mid]['desc'] = $m['desc'];
	}

	$export_p['students'] = array();
	$r = $mysqli->query("SELECT * FROM users WHERE s_pid={$project['pid']}");
	while($user_data = $r->fetch_assoc()) {
		$user = user_load($mysqli, -1, -1, NULL, $user_data);
		$export_p['students'][$user['uid']] = user_get_export($mysqli, $user);
	}

	return $export_p;
}


function signature_load($mysqli, $key, $data = NULL)
{
	/* Check for something other than a base64 character A-Za-z9-0+/= */
	if($data === NULL) {
		if(strlen($key) != 32) exit();
		if(preg_match("/[^A-Za-z0-9_\=]/", $key)) exit();
		$k = $mysqli->real_escape_string($key);
		$q = $mysqli->query("SELECT * FROM signatures WHERE `key`='$key'");
		print($mysqli->error);
		$sig = $q->fetch_assoc();
	} else {
		$sig = $data;
	}

	filter_int($sig['uid']);
	
	unset($sig['original']);
	$original = $sig;
	$sig['original'] = $original;
	
	return $sig;
}

function signature_save($mysqli, $sig)
{
	generic_save($mysqli, $sig, "signatures", "key");
}

function signature_create($mysqli, $year = NULL) 
{
	global $config;
	$year = ($year === NULL) ? $config['year'] : int($year);

	/* Generate a 32 character key and insert it, try again if the insert fails  (duplicate key) */
	for($x=0;$x<100;$x++) {
		$key = base64_encode(mcrypt_create_iv(24, MCRYPT_DEV_URANDOM));
		/* Replace base64 chars that aren't nice on the commandline 
		 * + and / -> _ */
		$key = str_replace( array('+', '/'), '_', $key);

		$r = $mysqli->real_query("INSERT INTO signatures(`key`,`year`) VALUES('$key','$year')");
		if($r == true) {
			return $key;
		}
	}
	print("Unable to create a database key for an electronic signature form.  Please reload the page and try again.");
	exit();
}




?>
