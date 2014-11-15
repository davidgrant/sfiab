<?php


$students_map = array();
$projects_map = array();

function conv_students($mysqli, $old_prefix, $year)
{
	global $schools_map, $tours_map;
	global $students_map, $projects_map;
	
	$count = 0;

	print("Converting Students for $year...\n");

	$mysqli->real_query("DELETE FROM users WHERE FIND_IN_SET('student',`roles`)>0 AND year=$year");
	$mysqli->real_query("DELETE FROM projects WHERE year=$year");

	$registration_to_project_map = array();

	/* Load all students, skip "new" students. */
	$q = $mysqli->query("SELECT {$old_prefix}students.*,{$old_prefix}registrations.status FROM {$old_prefix}students 
					LEFT JOIN {$old_prefix}registrations ON {$old_prefix}students.registrations_id={$old_prefix}registrations.id 
					WHERE {$old_prefix}students.year='$year' AND {$old_prefix}registrations.status!='new'");
	while($old_s = $q->fetch_assoc()) {

		$sid = $old_s['id'];
		$rid = $old_s['registrations_id'];

		/* Create a new user */
		$password = NULL;
		$u = user_create($mysqli, NULL, $old_s['email'], 'student', $year, $password);
		$uid = $u['uid'];

		$u['phone1'] = $old_s['phone'];
		filter_phone($u['phone1']);

		$u['firstname'] = $old_s['firstname'];
		$u['lastname'] = $old_s['lastname'];
		$u['pronounce'] = $old_s['pronunciation'];
		$u['sex'] = $old_s['sex'];
		$u['address'] = $old_s['address'];
		$u['city'] = $old_s['city'];
		$u['province'] = $old_s['province'];
		$u['postalcode'] = $old_s['postalcode'];
		$u['grade'] = $old_s['grade'];
		$u['birthdate'] = $old_s['dateofbirth'];
		if(!array_key_exists((int)$old_s['schools_id'], $schools_map))
			$u['schools_id'] = 0;
		else
			$u['schools_id'] = $schools_map[(int)$old_s['schools_id']];

		$u['fair_id'] = $old_s['fairs_id'];
		$u['tshirt'] = $old_s['tshirt'];
		$u['medicalert'] = $old_s['medicalalert'];
		$u['food_req'] = $old_s['foodreq'];
		$u['s_teacher'] = $old_s['teachername'];
		$u['s_teacher_email'] = $old_s['teacheremail'];
		$u['s_web_first'] = $old_s['webfirst'] == 'yes' ? 1 : 0;
		$u['s_web_last'] = $old_s['weblast'] == 'yes' ? 1 : 0;
		$u['s_web_photo'] = $old_s['webphoto'] == 'yes' ? 1 : 0;
		$u['new'] = 0;
		$u['enabled'] = 1;
		if($old_s['status'] == 'complete') {
			$u['s_complete'] = 1;
			$u['s_accepted'] = 1;
		} else {
			$u['s_complete'] = 0;
			$u['s_accepted'] = 0;
		}

		/* Convert emergency contacts */
		$q1 = $mysqli->query("SELECT * FROM {$old_prefix}emergencycontact WHERE students_id=$sid");
		$x = 1;
		while($e = $q1->fetch_assoc()) {
			$fn = $mysqli->real_escape_string($e['firstname']);
			$ln = $mysqli->real_escape_string($e['lastname']);
			$re = $mysqli->real_escape_string($e['relation']);
			$p1 = $mysqli->real_escape_string($e['phone1']);
			$p2 = $mysqli->real_escape_string($e['phone2']);
			$p3 = $mysqli->real_escape_string($e['phone3']);
			$em = $mysqli->real_escape_string($e['email']);

			$mysqli->query("INSERT INTO emergency_contacts(`uid`,`firstname`,`lastname`,`relation`,`email`,`phone1`,`phone2`,`phone3`)
					VALUES('$uid','$fn','$ln','$re','$em','$p1','$p2','$p3')");
		}

		/* Attach tour selections */
		$u['tour_id_pref'] = array();
		$q1 = $mysqli->query("SELECT * FROM {$old_prefix}tours_choice WHERE year='$year' AND students_id=$sid AND rank>0 ORDER BY rank");
		while($t = $q1->fetch_assoc()) {
			$tid = (int)$t['tour_id'];
			if(!array_key_exists($tid, $tours_map)) {
				print("   Old Student $sid tour choice $tid doesn't exist.  Skipping.\n");
				continue;
			}
			$u['tour_id_pref'][] = $tours_map[$tid];
		}

		/* Should only be one tour assignment */
		$q1 = $mysqli->query("SELECT * FROM {$old_prefix}tours_choice WHERE year='$year' AND students_id=$sid AND rank=0");
		while($t = $q1->fetch_assoc()) {
			$tid = $t['tour_id'];
			$u['tour_id'] = $tours_map[(int)$tid];
		}

		/* Load project if not loaded */
		if(array_key_exists($rid, $registration_to_project_map)) {
			$pid = $registration_to_project_map[$rid];
			$u['s_pid'] = $pid;
			$new_p = project_load($mysqli, $pid);
			$new_p['num_students'] += 1;

			/* If the student isn't accepted, make sure the project isn't either (even if the other student is complete) */
			if($u['s_accepted'] == 0) {
				$new_p['accepted'] = 0;
			}

//			print("      Adjust project $pid to {$new_p['num_students']} students\n");
		} else {
			$q1 = $mysqli->query("SELECT * FROM {$old_prefix}projects WHERE registrations_id=$rid");
			$p = $q1->fetch_assoc();

//			print_r($p);

			$pid = project_create($mysqli, $year);
			$new_p = project_load($mysqli, $pid);
			$projects_map[(int)$p['id']] = $pid;
			$registration_to_project_map[$rid] = $pid;

			$new_p['number'] = $p['projectnumber'];
			$new_p['title'] = $p['title'];
			$new_p['tagline'] = $p['shorttitle'];
			$new_p['abstract'] = $p['summary'];
			$new_p['req_electricity'] = $p['req_electricity'] == 'yes' ? 1 : 0;
			$new_p['language'] = $p['language'];
			$new_p['num_students'] = 1;
			$new_p['num_mentors'] = 0;
			$new_p['number_sort'] = $p['projectsort'];
			$new_p['floor_number'] = $p['floornumber'];
			$new_p['year'] = $p['year'];
			$new_p['cat_id'] = $p['projectcategories_id'];
			$new_p['challenge_id'] = $p['projectdivisions_id'];
			$new_p['accepted'] = $u['s_accepted'];
			$new_p['fair_id'] = $p['fairs_id'];



			/* Load mentors */
			$q2 = $mysqli->query("SELECT * FROM {$old_prefix}mentors WHERE registrations_id=$rid");
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
//			print("      Save project $pid {$new_p['number']} with 1 student, {$new_p['num_mentors']} mentors, cat_id={$new_p['cat_id']}\n");

			$u['s_pid'] = $pid;
		}
//		print("      Save Student $sid {$u['firstname']} {$u['lastname']}, pid=$pid\n");

		$students_map[$sid] = $uid;

		user_save($mysqli, $u);

		/* Save the project after the student because the project_save queries the student grade to
		 * adjust the cat_id */
		project_save($mysqli, $new_p);
		
		$count += 1;
	}
	print("   Converted $count students\n");
}


?>
