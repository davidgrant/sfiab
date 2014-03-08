<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('form.inc.php');
require_once('incomplete.inc.php');
require_once('config.inc.php');
require_once('project.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

$mysqli_old = new mysqli($dbhost, $dbuser, $dbpassword, "sfiab_gvrsf");

$year = $config['year'];

$mysqli->real_query("DELETE FROM users WHERE FIND_IN_SET('student',`roles`) > 0 AND year<$year");
$mysqli->real_query("DELETE FROM projects WHERE year<$year");
$mysqli->real_query("DELETE FROM categories WHERE year<$year");
$mysqli->real_query("DELETE FROM challenges WHERE year<$year");


/* Convert categories */
$q = $mysqli_old->query("SELECT * FROM projectcategories WHERE year<$year");
while($c = $q->fetch_assoc()) {
	$mysqli->query("INSERT INTO categories (`id`,`name`,`shortform`,`min_grade`,`max_grade`,`year`) VALUES (
				'{$c['id']}',
				'{$c['category']}','{$c['category_shortform']}','{$c['mingrade']}',
				'{$c['maxgrade']}','{$c['year']}')");
}

/* Convert challenges or divisions */
$q = $mysqli_old->query("SELECT * FROM projectdivisions WHERE year<$year");
while($c = $q->fetch_assoc()) {
	$mysqli->query("INSERT INTO challenges (`id`,`name`,`shortform`,`cwsfchallengeid`,`year`) VALUES (
				'{$c['id']}',
				'{$c['division']}','{$c['division_shortform']}',
				'{$c['cwsfdivisionid']}','{$c['year']}')");
}

$users = array();
$projects = array();

$q = $mysqli_old->query("SELECT * FROM students WHERE year=2013");
while($u = $q->fetch_assoc()) {

	$sid = $u['id'];
	$rid = $u['registrations_id'];

	/* Skip incomplete students */
	$q1 = $mysqli_old->query("SELECT * FROM registrations WHERE id=$rid");
	$r = $q1->fetch_assoc();
	if($r['status'] != 'complete') continue;

	filter_phone($u['phone']);
	filter_int($u['schools_id']);
	filter_int($u['grade']);

	$conv = array('phone' => 'phone1', 'dateofbirth' => 'birthdate', 
			'teachername' => 's_teacher', 'teacheremail' => 's_teacheremail',
			'medicalalert' => 'medicalert',
			'lang' => 'language', 'pronunciation' => 'pronounce');

	print("Processing: ". $u['firstname'] . ' '.$u['lastname'].' '.$u['year']. "\n");
	foreach($conv as $old=>$new) {
		$u[$new] = $u[$old];
		unset($u[$old]);
	}

	$u['medicalert'] .= $u['foodreq'];
	unset($u['foodreq']);

	$u['username'] = $u['year'].'_'.$u['id'];
	$u['password'] = '';
	$u['s_accepted'] = 1;
	$u['s_complete'] = 1;
	$u['state'] = 'active';
	$mysqli->real_query("INSERT INTO users(`roles`,`username`,`year`) VALUES ('student','{$u['username']}','{$u['year']}')");
	print($mysqli->error);
	$u['uid'] = $mysqli->insert_id;


	/* Attach emergency contacts */
	$q1 = $mysqli_old->query("SELECT * FROM emergencycontact WHERE students_id=$sid");
	$x = 1;
	while($e = $q1->fetch_assoc()) {
		$u["emerg{$x}_firstname"] = $e['firstname'];
		$u["emerg{$x}_lastname"] = $e['lastname'];
		$u["emerg{$x}_relation"] = $e['relation'];
		$u["emerg{$x}_phone1"] = $e['phone1'];
		$u["emerg{$x}_phone2"] = $e['phone2'];
		$u["emerg{$x}_phone3"] = $e['phone3'];
		$u["emerg{$x}_email"] = $e['email'];
		$x += 1;
		if($x == 3) break;
	}
	print("Loaded {$u['firstname']} {$u['lastname']}\n");

	/* Attach tour selections */
	$u['tour_id_pref'] = array();
	$q1 = $mysqli_old->query("SELECT * FROM tours_choice WHERE year={$u['year']} AND students_id=$sid AND rank>0 ORDER BY rank");
	while($t = $q1->fetch_assoc()) {
		$tid = $t['tour_id'];
		$u['tour_id_pref'][] = (int)$tid;
	}
	print("      Tour pref: (".count($u['tour_id_pref']).") ");
	foreach($u['tour_id_pref'] as $tid) {
		print(" $tid ");
	}
	print("\n");


	/* Load project if not loaded */
	if(array_key_exists($rid, $projects)) {
		$u['s_pid'] = $projects[$rid]['pid'];
		$new_p = project_load($mysqli, $projects[$rid]['pid']);
		$new_p['num_students'] += 1;
		project_save($mysqli, $new_p);
		print("      Adjust project $pid to {$new_p['num_students']} students\n");
	} else {
		$q1 = $mysqli_old->query("SELECT * FROM projects WHERE registrations_id=$rid");
		$x = 1;
		$p = $q1->fetch_assoc();
		$pid = project_create($mysqli);
		$new_p = project_load($mysqli, $pid);
		$projects[$rid] = $new_p;

		$new_p['number'] = $p['projectnumber'];
		$new_p['title'] = $p['title'];
		$new_p['titleshort'] = $p['shorttitle'];
		$new_p['summary'] = $p['summary'];
		$new_p['req_electricity'] = $p['req_electricity'] == 'yes' ? 1 : 0;
		$new_p['language'] = $p['language'];
		$new_p['num_students'] = 1;
		$new_p['num_mentors'] = 0;
		$new_p['number_sort'] = $p['projectsort'];
		$new_p['floor_number'] = $p['floornumber'];
		$new_p['year'] = $p['year'];
		$new_p['cat_id'] = $p['projectcategories_id'];
		$new_p['challenge_id'] = $p['projectdivisions_id'];

		/* Load mentors */
		$q2 = $mysqli_old->query("SELECT * FROM mentors WHERE registrations_id=$rid");
		while($m = $q2->fetch_assoc()) {
			$mid = mentor_create($mysqli, $pid);
			$new_m = mentor_load($mysqli, $mid);

			$new_m['firstname'] = $m['firstname'];
			$new_m['lastname'] = $m['lastname'];
			$new_m['email'] = $m['email'];
			$new_m['phone'] = $m['phone'];
			$new_m['position'] = $m['position'];
			$new_m['organization'] = $m['organization'];
			$new_m['desc'] = $m['description'];

			mentor_save($mysqli, $new_m);

			$new_p['num_mentors'] += 1;
		}
		project_save($mysqli, $new_p);
		print("      Save project $pid {$new_p['number']} with 1 student and {$new_p['num_mentors']} mentors\n");

		$u['s_pid'] = $pid;
	}

	unset($u['registrations_id']);
	unset($u['id']);
	unset($u['age']);
	unset($u['fairs_id']);
	unset($u['webfirst']);
	unset($u['weblast']);
	unset($u['webphoto']);
	unset($u['namecheck_complete']);

	$original = array();
	foreach($u as $key=>$v) {
		$original[$key] = NULL;
	}
	$u['original'] = $original;

	user_save($mysqli, $u);
}

?>
