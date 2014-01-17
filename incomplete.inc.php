<?php
require_once('project.inc.php');

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
	if(!is_array($data)) $ret = array_merge($ret, $fields);
	foreach($fields as $f) {
		$v = $data[$f];
		/* Using !== will make this fail if it's NULL and thus incomplete */
		if($v !== 0 && $v !== 1) {
			$ret[] = $f;
		}
	}
}

function incomplete_check_gt_zero(&$ret, &$data, $fields)
{
	if(!is_array($data)) $ret = array_merge($ret, $fields);
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

function incomplete_fields($mysqli, $section, &$u, $force_update=false)
{
	if(array_key_exists($section, $_SESSION['incomplete']) && !$force_update) {
		return $_SESSION['incomplete'][$section];
	}
		
	$ret = array();
	switch($section) {
	case 'account':
		if($_SESSION['password_expired']) {
			$ret[] = 'pw1';
			$ret[] = 'pw2';
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
	case 's_project':
		$p = project_load($mysqli, $u['s_pid']);
		incomplete_check_text($ret, $p, array('title','summary','language'));
		incomplete_check_bool($ret, $p, array('req_electricity'));
		incomplete_check_gt_zero($ret, $p, array('cat_id','challenge_id','isef_id'));
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
			$q = $mysqli->query("SELECT * FROM mentors WHERE pid='{$p['pid']}'");
			for($i=$q->num_rows; $i < $p['num_mentors']; $i++) {
				foreach($fields as $f) {
					$ret[] = "$f".$i;
				}
			}
		}
		break;

	case 'j_personal':
		incomplete_check_text($ret, $u, array('salutation', 'firstname', 'lastname', 'phone1',
				'city', 'province', 'language','j_psd'));
		break;
	case 'j_options':
		if($u['j_rounds'][0] !== 0 && $u['j_rounds'][0] !== 1) $ret[] = 'j_round0';
		if($u['j_rounds'][1] !== 0 && $u['j_rounds'][1] !== 1) $ret[] = 'j_round1';
		incomplete_check_bool($ret, $u, array('j_willing_lead','j_dinner'));
		foreach($u['j_languages'] as $l=>$en) {
			if($en === NULL) $ret[] = "j_lang_$l";
		}

		break;

	case 'j_expertise':
		/* If j_sa isn't entered, that's the one that's missing */
		if($u['j_sa'] === NULL) {
			$ret[] = 'j_sa_only';
			$ret[] = 'j_sa';
		}
		if($u['j_sa'] == '') {
			incomplete_check_gt_zero($ret, $u, array('j_pref_div1', 'j_pref_div2','j_pref_div3','j_pref_cat'));
		} 
		incomplete_check_ge_zero($ret, $u, array('j_years_school', 'j_years_regional','j_years_national'));
		break;
	case 'j_mentorship':
		incomplete_check_bool($ret, $u, array('j_mentored'));
		break;
	}

	/* Save for later */
	$_SESSION['incomplete'][$section] = $ret;

	if(count($ret)) $_SESSION['complete'] = false;
	return $ret;
}

function incomplete_check($mysqli, &$u) 
{
	/* Set session to complete.  Each of the checks below will set it to incomplete
	 *  if they find an incomplete item. */
	$_SESSION['complete'] = true;
	if(sfiab_user_is_a('student')) {
		
		incomplete_fields($mysqli, 's_personal', $u, true);
		incomplete_fields($mysqli, 's_reg_options', $u, true);
		incomplete_fields($mysqli, 's_emergency', $u, true);
		incomplete_fields($mysqli, 's_project', $u, true);
		incomplete_fields($mysqli, 's_partner', $u, true);
		incomplete_fields($mysqli, 's_mentor', $u, true);
		incomplete_fields($mysqli, 's_ethics', $u, true);
		incomplete_fields($mysqli, 's_safety', $u, true);
		incomplete_fields($mysqli, 's_awards', $u, true);
		incomplete_fields($mysqli, 's_signature', $u, true);
	}

	if(sfiab_user_is_a('judge')) {
		incomplete_fields($mysqli, 'j_personal', $u, true);
		incomplete_fields($mysqli, 'j_expertise', $u, true);
		incomplete_fields($mysqli, 'j_options', $u, true);
		incomplete_fields($mysqli, 'j_mentorship', $u, true);
	}
}
?>
