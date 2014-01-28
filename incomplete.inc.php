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
		incomplete_check_text($ret, $u, array('s_tshirt'));
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
		$w = str_word_count($p['summary']);
		if($w < 200 || $w > 1000) {
			$incomplete_errors[] += array("Project summary must contain between 200 and 1000 words");
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

		incomplete_check_bool($ret, $e, array('human1', 'animals'));

		
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
			if($e['animal_vertebrate']) {
				incomplete_check_bool($ret, $e, array('animal_ceph'));
			}
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
			if($user['s_status'] != 'accepted') $ret[] = 'user_'.$user['uid'];
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

		$num_enabled = 0;
		foreach($u['j_languages'] as $l=>$en) {
			if($en === NULL)  
				$ret[] = "j_lang_$l";
			if($en == 1) 
				$num_enabled ++;
		}

		/* Nothing enabled, turn them all on */
		if($num_enabled == 0) {
			foreach($u['j_languages'] as $l=>$en) {
				$ret[] = "j_lang_$l";
			}
			/* Add to array only if it doesn't already exist, that's what += does */
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
			if(count($ret) == 0) {
				/* No missing fields here, and session is already complete? */
				if($_SESSION['complete'] == true) {
					return $ret;
				}
			} else {
				/* Missing fields and session is already incomplete? */
				if($_SESSION['complete'] == false) {
					return $ret;
				}
			}
		}
		$force = true;
		/* Fall through and force-check the whole session */
	}

	if($u['uid'] == $_SESSION['uid']) {
		/* Set to true, then back to false below */
		$_SESSION['complete'] = true;
	}

	/* Set session to complete.  Each of the checks below will set it to incomplete
	 *  if they find an incomplete item. */
	$ret = array();
	$total_c = 0;
	if(sfiab_user_is_a('student')) {
		$c = 0;
		$c += incomplete_fields_check($mysqli, $ret, 's_personal', $u, $force);
		$c += incomplete_fields_check($mysqli, $ret, 's_reg_options', $u, $force);
		$c += incomplete_fields_check($mysqli, $ret, 's_tours', $u, $force);
		$c += incomplete_fields_check($mysqli, $ret, 's_emergency', $u, $force);
		$c += incomplete_fields_check($mysqli, $ret, 's_project', $u, $force);
		$c += incomplete_fields_check($mysqli, $ret, 's_partner', $u, $force);
		$c += incomplete_fields_check($mysqli, $ret, 's_mentor', $u, $force);
		$c += incomplete_fields_check($mysqli, $ret, 's_ethics', $u, $force);
		$c += incomplete_fields_check($mysqli, $ret, 's_safety', $u, $force);
		$c += incomplete_fields_check($mysqli, $ret, 's_awards', $u, $force);
		/* Signature page doesn't count against' complete status */
		$c1 = incomplete_fields_check($mysqli, $ret, 's_signature', $u, $force);

		$student_complete = ($c == 0) ? true : false;

		/* Adjust student status in the user if it changed */
		if($student_complete && $u['s_status'] != 'complete') {
			$u['s_status'] = 'complete';
			user_save($mysqli, $u);
		} else if($student_complete == false && $u['s_status'] == 'incomplete') {
			$u['s_status'] = 'incomplete';
			user_save($mysqli, $u);
		}
		$total_c += $c + $c1;
	}

	if(sfiab_user_is_a('judge')) {
		$c = 0;
		$c += incomplete_fields_check($mysqli, $ret, 'j_personal', $u, $force);
		$c += incomplete_fields_check($mysqli, $ret, 'j_expertise', $u, $force);
		$c += incomplete_fields_check($mysqli, $ret, 'j_options', $u, $force);
		$c += incomplete_fields_check($mysqli, $ret, 'j_mentorship', $u, $force);
		$judge_complete = ($c == 0) ? true : false;
		if(!$judge_complete) $_SESSION['complete'] = false;

		/* Adjust judge status in the user if it changed */
		if($judge_complete && $u['j_status'] != 'complete') {
			$u['j_status'] = 'complete';
			user_save($mysqli, $u);
		} else if($judge_complete == false && $u['j_status'] != 'incomplete') {
			/* CHeck for != incomplete because we could be coming off a notattedning */
			$u['j_status'] = 'incomplete';
			user_save($mysqli, $u);
		}
		$total_c += $c;
	}
	return $total_c;
}
?>
