<?php
require_once('project.inc.php');

$incomplete_errors = array();

function incomplete_user_fields($mysqli, $required_fields, &$u)
{
	$ret = array();

	foreach($required_fields as $f) {
		/* === means same value and same type, no type conversions
		 * 0 == '' == NULL after type conversions */
		if($u[$f] === NULL || $u[$f] === '') $ret[] = $f;
	}
	return $ret;
}

function incomplete_check_bool(&$ret, &$data, $fields)
{
	if(!is_array($data)) {
		$ret = array_merge($ret, $fields);
		return;
	}
	foreach($fields as $f) {
		if(!array_key_exists($f, $data)) {
			$ret[] = $f;
			continue;
		}
		$v = $data[$f];
		/* Using !== will make this fail if it's NULL and thus incomplete */
		if($v !== 0 && $v !== 1) {
			$ret[] = $f;
		}
	}
}

function incomplete_check_gt_zero(&$ret, &$data, $fields)
{
	if(!is_array($data)) {
		$ret = array_merge($ret, $fields);
		return;
	}

	foreach($fields as $f) {
		$v = $data[$f];
		if(!is_int($v) || $v <= 0) {
			$ret[] = $f;
		}
	}
}

function incomplete_check_ge_zero(&$ret, &$data, $fields)
{
	if(!is_array($data)) $ret = array_merge($ret, $fields);
	foreach($fields as $f) {
		$v = $data[$f];
		if(!is_int($v) || $v < 0) {
			$ret[] = $f;
		}
	}
}


function incomplete_check_text(&$ret, &$data, $fields)
{
	if(!is_array($data)) $ret = array_merge($ret, $fields);
	foreach($fields as $f) {
		$v = $data[$f];
		if(!is_string($v) || $v == '') {
			$ret[] = $f;
		}
	}
}

function incomplete_fields_check($mysqli, &$ret_list, $section, &$u, $force_update=false)
{
	global $incomplete_errors;

	$ret = array();

	if($u['uid'] == $_SESSION['uid'] && array_key_exists($section, $_SESSION['incomplete']) && !$force_update) {
	   	$ret_list = array_merge($ret_list, $_SESSION['incomplete'][$section]);
		return count($_SESSION['incomplete'][$section]);
	}
		
	switch($section) {
	case 'account':
		if($u['uid'] == $_SESSION['uid']) {
			if($_SESSION['password_expired']) {
				$ret[] = 'pw1';
				$ret[] = 'pw2';
			}
		}
		break;

	case 's_personal':
		incomplete_check_text($ret, $u, array('firstname', 'lastname', 'sex', 'phone1',
				'birthdate', 'address', 'city', 'province', 
				'postalcode', 's_teacher', 's_teacheremail'));
		incomplete_check_gt_zero($ret, $u, array('schools_id', 'grade'));
		break;
	case 's_emergency':
		incomplete_check_text($ret, $u, array('emerg1_firstname','emerg1_lastname',
				'emerg1_relation','emerg1_email','emerg1_phone1'));
		break;
	case 's_reg_options':
		incomplete_check_text($ret, $u, array('tshirt'));
		break;

	case 's_tours':
		if(count($u['tour_id_pref']) > 0) {
			$x = 0;
			foreach($u['tour_id_pref'] as $tid) {
				if($tid === NULL or $tid == 0) {
					$ret[] = "tour$x";
				}
				$x++;
			}
		} else {
			$ret[] = "tour1";
			$ret[] = "tour2";
			$ret[] = "tour3";
		}
		break;

	case 's_project':
		$p = project_load($mysqli, $u['s_pid']);
		incomplete_check_text($ret, $p, array('title','summary','language'));
		incomplete_check_bool($ret, $p, array('req_electricity'));
		incomplete_check_gt_zero($ret, $p, array('cat_id','challenge_id','isef_id'));

		/* Check words in summary */
		$w = str_word_count(trim($p['summary']));
		if($w < 200 || $w > 1000) {
			$incomplete_errors += array("Project summary must contain between 200 and 1000 words");
			$ret[] = 'summary';
		}

		break;
	case 's_partner':
		$p = project_load($mysqli, $u['s_pid']);
		incomplete_check_gt_zero($ret, $p, array('num_students'));
		/* Check that there are the right number of students attached to the project */
		$q = $mysqli->query("SELECT uid FROM users WHERE `s_pid`='{$p['pid']}'");
		if($q->num_rows != $p['num_students']) {
			/* Missing students */
			for($i=$q->num_rows+1; $i<= $p['num_students']; $i++) {
				$ret[] = "invite_$i";
			}
		}
		/* Check for incoming requests, each one gets an incomplete */
		$q = $mysqli->query("SELECT id FROM partner_requests WHERE `to_uid`='{$u['uid']}'");
		if($q->num_rows > 0) {
			/* Requests */
			$ret[] = "partner_request";
		}
		break;
	case 's_mentor':
		$fields = array('firstname','lastname','phone','email','organization','position','desc');
		$p = project_load($mysqli, $u['s_pid']);
		incomplete_check_ge_zero($ret, $p, array('num_mentors'));
		if($p['num_mentors'] > 0) {
			$mentors = mentor_load_all($mysqli, $p['pid']);
			foreach($mentors as $mid=>$m) {
				$ret_temp = array();
				/* Check for missing fields, but then remap the
				missing fields to be the name plus the mentor
				id */
				incomplete_check_text($ret_temp, $m, $fields);
				foreach($fields as $f) {
					if(in_array($f, $ret_temp)) {
						$ret[] = "{$f}{$mid}";
					}
				}
			}
		}
		break;

	case 's_ethics':
		$p = project_load($mysqli, $u['s_pid']);
		$e = $p['ethics'];

		incomplete_check_bool($ret, $e, array('human1', 'animals', 'agree'));

		
		if($e['human1']) {
			incomplete_check_bool($ret, $e, array(
				'humansurvey1', 'humanfood1', 
				'humanfood2', 'humanfood6', 'humantest1'));

			if($e['humanfood1'] || $e['humanfood2']) {
				incomplete_check_bool($ret, $e, array(
					'humanfood3', 'humanfood4', 'humanfood5', 
					'humanfooddrug', 'humanfoodlow1', 'humanfoodlow2'));
			}
		}

		if($e['animals']) {
			incomplete_check_bool($ret, $e, array(
				'animal_vertebrate', 'animal_tissue', 'animal_drug'));
			if(!$e['animal_vertebrate']) {
				incomplete_check_bool($ret, $e, array('animal_ceph'));
			}
		}
		break;
	case 's_safety':
		$p = project_load($mysqli, $u['s_pid']);
		$e = $p['safety'];

		incomplete_check_bool($ret, $e, array('bio1','hazmat1','mech1','institution','display1','display2','display3', 'agree'));
		if($e['mech1'])
			incomplete_check_bool($ret, $e, array('electrical1','animals1','food1'));
		
		if($e['electrical1'] && $e['mech1']) {
			incomplete_check_bool($ret, $e, array("electrical2", "electrical3", "electrical4"));
		}
		if($e['bio1'] && $e['mech1']) {
			incomplete_check_bool($ret, $e, array("bio2", "bio3", "bio4", "bio5", "bio6"));
		}
		if($e['hazmat1'] && $e['mech1']) {
			incomplete_check_bool($ret, $e, array("hazmat2", "hazmat3", "hazmat4", "hazmat5"));
		}
		if($e['mech1'] && $e['mech1']) {
			incomplete_check_bool($ret, $e, array("mech2", "mech3", "mech4", "mech5" , "mech6", 'mech7'));
		}
		if($e['animals1'] && $e['mech1']) {
			incomplete_check_bool($ret, $e, array("animals2", "animals3"));
		}
		if($e['food1'] && $e['mech1']) {
			incomplete_check_bool($ret, $e, array("food2", "food3", 'food4','food5'));
		}
		break;


	case 's_awards':
		if(count($u['s_sa_nom']) > 0) {
//			if($u['s_sa_nom'][0] === NULL || $u['s_sa_nom'][0] === '') {
		} else {
			$ret[] = 'award';
		}
		break;
	case 's_signature':
		$users = user_load_all_for_project($mysqli, $u['s_pid']);
		/* Each user needs to be complete */
		foreach($users as $user) {
			if($user['s_accepted'] == 0) $ret[] = 'user_'.$user['uid'];
		}
		break;

	case 'j_personal':
		incomplete_check_text($ret, $u, array('firstname', 'lastname', 'phone1',
				'city', 'province', 'language','j_psd'));
		break;
	case 'j_options':
		if($u['j_rounds'][0] !== 0 && $u['j_rounds'][0] !== 1) $ret[] = 'j_round0';
		if($u['j_rounds'][1] !== 0 && $u['j_rounds'][1] !== 1) $ret[] = 'j_round1';
		incomplete_check_bool($ret, $u, array('j_willing_lead','j_dinner'));

		if(count($u['j_languages']) == 0) {
			$ret[] = "j_languages";
			$incomplete_errors += array("At least one language must be selected");
		}
		break;

	case 'j_expertise':
		/* If j_sa isn't entered, that's the one that's missing */
		if($u['j_sa_only'] === NULL)
			$ret[] = 'j_sa';

		if($u['j_sa_only']) {
			if($u['j_sa'] == array(NULL, NULL, NULL)) {
				$ret[] = 'j_sa';
			}
		} else {
			incomplete_check_gt_zero($ret, $u, array('j_pref_div1', 'j_pref_div2','j_pref_div3'));
			incomplete_check_ge_zero($ret, $u, array('j_pref_cat', 'j_years_school', 'j_years_regional','j_years_national'));
		}
		break;
	case 'j_mentorship':
		incomplete_check_bool($ret, $u, array('j_mentored'));
		break;


	case 'v_personal':
		incomplete_check_text($ret, $u, array('firstname', 'lastname', 'phone1',
				'city', 'province', 'language','v_relation'));
		break;

	case 'v_options':
		incomplete_check_text($ret, $u, array('tshirt', 'v_reason'));
		break;

	case 'v_tours':
		if($u['v_relation'] == 'parent') {
			incomplete_check_bool($ret, $u, array('v_tour_match_username'));
		} 

		if($u['v_tour_match_username'] == 1) {
			incomplete_check_text($ret, $u, array('v_tour_username'));
		} else {
			if(count($u['tour_id_pref']) > 0) {
				$x = 0;
				foreach($u['tour_id_pref'] as $tid) {
					if($tid === NULL or $tid == 0) {
						$ret[] = "tour$x";
					}
					/* Only check the first one */
					break;
					$x++;
				}
			} else {
				$ret[] = "tour1";
	//			$ret[] = "tour2";
	//			$ret[] = "tour3";
			}
		}
		break;

	}

	/* Save for later */
	if($u['uid'] == $_SESSION['uid']) {
		$_SESSION['incomplete'][$section] = $ret;
	}
   	$ret_list = array_merge($ret_list, $ret);
	return count($ret);
}


function incomplete_check($mysqli, &$ret, &$u, $page_id = false, $force = true)
{
	global $incomplete_errors;

	$ret = array();
	$incomplete_errors = array();

	if($page_id !== false) {
		incomplete_fields_check($mysqli, $ret, $page_id, $u, $force);

		/* If we're checking the current user we can also muck with the
		 * session and skip the evaluation below in 
		 * some cases */
		if($u['uid'] == $_SESSION['uid']) {
			/* Nothing else to do */
			if(count($ret) == 0 && $_SESSION['complete'] == true) {
			/* No missing fields here, and session is already complete? */
				return 0;
			} 
			if(count($ret) > 0 && $_SESSION['complete'] == false) {
				/* Missing fields and session is already incomplete? */
				return count($ret);
			}
		}
		/* Else, reevaluate everything */
	}

	if($u['uid'] == $_SESSION['uid']) {
		/* Set to true, then back to false below */
		$_SESSION['complete'] = true;
	}

	/* Set session to complete.  Each of the checks below will set it to incomplete
	 *  if they find an incomplete item. */

	$ret_t = array();
	$total_c = 0;
	if(in_array('student', $u['roles'])) {
		$c = 0;
		$c += incomplete_fields_check($mysqli, $ret_t, 's_personal', $u, $force);
		$c += incomplete_fields_check($mysqli, $ret_t, 's_reg_options', $u, $force);
		$c += incomplete_fields_check($mysqli, $ret_t, 's_tours', $u, $force);
		$c += incomplete_fields_check($mysqli, $ret_t, 's_emergency', $u, $force);
		$c += incomplete_fields_check($mysqli, $ret_t, 's_project', $u, $force);
		$c += incomplete_fields_check($mysqli, $ret_t, 's_partner', $u, $force);
		$c += incomplete_fields_check($mysqli, $ret_t, 's_mentor', $u, $force);
		$c += incomplete_fields_check($mysqli, $ret_t, 's_ethics', $u, $force);
		$c += incomplete_fields_check($mysqli, $ret_t, 's_safety', $u, $force);
		$c += incomplete_fields_check($mysqli, $ret_t, 's_awards', $u, $force);
		/* Signature page doesn't count against' complete status */
		$c1 = incomplete_fields_check($mysqli, $ret_t, 's_signature', $u, $force);

		$student_complete = ($c == 0) ? 1 : 0;

		if( ($u['uid'] == $_SESSION['uid']) && !$student_complete) 
			$_SESSION['complete'] = false;

		/* Adjust student status in the user if it changed */
		if($student_complete != $u['s_complete']) {
			$u['s_complete'] = $student_complete;
			user_save($mysqli, $u);
		}
		$total_c += $c + $c1;
	}

	if(in_array('judge', $u['roles'])) {
		$c = 0;
		$c += incomplete_fields_check($mysqli, $ret_t, 'j_personal', $u, $force);
		$c += incomplete_fields_check($mysqli, $ret_t, 'j_expertise', $u, $force);
		$c += incomplete_fields_check($mysqli, $ret_t, 'j_options', $u, $force);
		$c += incomplete_fields_check($mysqli, $ret_t, 'j_mentorship', $u, $force);
		$judge_complete = ($c == 0) ? 1 : 0;

		if( ($u['uid'] == $_SESSION['uid']) && !$judge_complete) 
			$_SESSION['complete'] = false;
		
		/* Adjust judge status in the user if it changed */
		if($judge_complete != $u['j_complete']) {
			$u['j_complete'] = $judge_complete;
			user_save($mysqli, $u);
		}
		$total_c += $c;
	}

	if(in_array('volunteer', $u['roles'])) {
		$c = 0;
		$c += incomplete_fields_check($mysqli, $ret_t, 'v_personal', $u, $force);
		$c += incomplete_fields_check($mysqli, $ret_t, 'v_tours', $u, $force);
		$v_complete = ($c == 0) ? 1 : 0;

		if( ($u['uid'] == $_SESSION['uid']) && !$v_complete) 
			$_SESSION['complete'] = false;
		
		/* Adjust judge status in the user if it changed */
		if($v_complete != $u['v_complete']) {
			$u['v_complete'] = $v_complete;
			user_save($mysqli, $u);
		}
		$total_c += $c;
	}

	/* If they didn't ask for a specific page, return all missing fields */
	if($page_id === false) {
		$ret = $ret_t;
		return $total_c;
	} else {
		return count($ret);
	}
}
?>
