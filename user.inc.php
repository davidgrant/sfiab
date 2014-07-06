<?php
require_once('filter.inc.php');


function user_load_all_for_project($mysqli, $pid)
{
	$r = $mysqli->query("SELECT * FROM users WHERE s_pid=$pid");
	$us = array();
	while($d = $r->fetch_assoc()) {
		$u = user_load($mysqli, -1, -1, NULL, $d);
		$us[] = $u;
	}
	return $us;
}

function user_new_password()
{
	/* Create new 9 character scrambled password */
	$password = substr(hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true)), 0, 9);
	return $password;
}


function user_create($mysqli, $username, $email, $role, $year, &$password)
{
	if($password == '' or $password === NULL) {
		
		$password = user_new_password();
	}
	$password_hash = hash('sha512', $password);

	$u = ($username === NULL) ? 'NULL' : "'$username'";

	$q = $mysqli->real_query("INSERT INTO users (`username`,`state`,`email`,`year`,`roles`,`password`,`password_expired`) 
				VALUES($u, 'new','$email','$year','$role','$password_hash', '1')");
	$uid = $mysqli->insert_id;
	print($mysqli->error);
	/* Since this is a new user, set the unique id == uid */
	$mysqli->query("UPDATE users SET unique_uid=$uid WHERE uid=$uid");
	print($mysqli->error);
	return $uid;
}

function user_load($mysqli, $uid=-1, $unique_uid=-1, $username=NULL, $data=NULL)
{
	$u = NULL;
	if((int)$uid > 0) {
		$id = (int)$uid;
		$r = $mysqli->query("SELECT * FROM users WHERE uid=$id LIMIT 1");
	} else if((int)$unique_uid > 0) {
		$id = (int)$unique_uid;
		$r = $mysqli->query("SELECT * FROM users WHERE unique_uid=$id ORDER BY `year` DESC LIMIT 1");
	} else if($username !== NULL) {
		$username = $mysqli->real_escape_string($username);
		$r = $mysqli->query("SELECT * FROM users WHERE `username`='$username' ORDER BY `year` DESC LIMIT 2");
	} else if ($data !== NULL) {
		/* If data is specifed, skip all SQL and the fetch below, and go
		 * right into filtering the data */
		$u = $data;
	} else {
		if(sfiab_session_is_active()) {
			if(array_key_exists('edit_uid', $_SESSION)) {
				$r = $mysqli->query("SELECT * FROM users WHERE uid={$_SESSION['edit_uid']} LIMIT 1");
			} else if($_SESSION['uid'] > 0) {
				$r = $mysqli->query("SELECT * FROM users WHERE uid={$_SESSION['uid']} LIMIT 1");
			} else {
				return NULL;
			}
		} else {
			return NULL;
		}
	}

	if($u === NULL) {
		if($r->num_rows == 0) {
			return NULL;
		}
		$u = $r->fetch_assoc();

		/* There can be at most 2 entries for same username in the same year.
		 * If the entry we just loaded is 'deleted', then grab the next
		 * one, and see if it's in the same year (results are returned
		 * sorted by year).  If it is, return the new one since it's
		 * the valid one */
		if($r->num_rows == 2 && $u['state'] == 'deleted') {
			$u2 = $r->fetch_assoc();
			if($u2['year'] == $u['year']) 
				$u = $u2;
			/* If it's not the same year, return the deleted entry */
		}
	}

	/* Sanitize some fields */
	$u['uid'] = (int)$u['uid'];
	$u['name'] = ($u['firstname'] ? "{$u['firstname']} " : '').$u['lastname'];
	$u['roles'] = explode(",", $u['roles']);
	$u['password_expired'] = ((int)$u['password_expired'] == 1) ? true : false;
	filter_bool($u['not_attending']);
	filter_bool($u['j_complete']);
	filter_bool($u['s_complete']);

	/* Student filtering */
	filter_int_or_null($u['schools_id']);
	filter_int_or_null($u['grade']);
	/* Clear out invalid input so the placeholder is shown again */
	if($u['birthdate'] == '0000-00-00') $u['birthdate'] = NULL;
	if($u['reg_close_override'] == '0000-00-00') $u['reg_close_override'] = NULL;

	if($u['tour_id_pref'] === NULL)
		$u['tour_id_pref'] = array(NULL,NULL,NULL);
	else {
		$a = explode(',',$u['tour_id_pref']);
		$u['tour_id_pref'] = array(NULL, NULL, NULL);
		$i = 0;
		foreach($a as $id) {
			$u['tour_id_pref'][$i] = (int)$id;
			$i++;
		}
	}

	filter_int_or_null($u['tour_id']);
	filter_bool($u['s_web_firstname']);
	filter_bool($u['s_web_lastname']);
	filter_bool($u['s_web_photo']);


	/* Judge filtering */
	filter_int_list($u['j_div_pref']);
	filter_int_or_null($u['j_cat_pref']);
	filter_int_or_null($u['j_years_school']);
	filter_int_or_null($u['j_years_regional']);
	filter_int_or_null($u['j_years_national']);
	filter_bool_or_null($u['j_sa_only']);
	filter_int_list($u['j_sa']);
	filter_bool_or_null($u['j_willing_lead']);
	filter_bool_or_null($u['j_dinner']);
	filter_bool_or_null($u['j_mentored']);
	if($u['j_rounds'] === NULL) 
		$u['j_rounds'] = array(NULL,NULL);
	else {
		$a = explode(',',$u['j_rounds']);
		$u['j_rounds'] = array(0,0);
		foreach($a as $r) {
			$u['j_rounds'][$r] = 1;
		}
	}

	filter_languages($u['languages']);

	/* Volutneer */
	filter_bool_or_null($u['v_complete']);
	filter_bool_or_null($u['v_tour_match_username']);

	/* Store an original copy so save() can figure out what (if anything) needs updating */
	unset($u['original']);
	$original = $u;
	$u['original'] = $original;

	return $u;
}

function user_load_by_username($mysqli, $username)
{
	return user_load($mysqli, -1, -1, $username);
}

function user_load_from_data($mysqli, $data)
{
	return user_load($mysqli, -1, -1, NULL, $data);
}



function user_save($mysqli, &$u) 
{
	global $sfiab_roles;
	/* Find any fields that changed */
	/* Construct a query to update just those fields */
	/* Always save in the current year */
	$set = "";
	foreach($u as $key=>$val) {
		if($key == 'original') continue;
		if(!array_key_exists($key, $u['original'])) continue;

		if($val !== $u['original'][$key]) {
			/* Key changed */
			if($set != '') $set .= ',';

			switch($key) {
			case 'roles':
				/* Make a list of comma-separated roles, sanity checking
				 * them all first */
				foreach($u['roles'] as $r) {
					if(!array_key_exists($r, $sfiab_roles)) {
						print("Error 1002: $r");
						exit();
					}
				}
				/* It's all ok, join it with commas so the query
				 * looks like ='teacher,committee,judge' */
				$v = implode(',', $r);
				break;

			case 'j_rounds':
				$a = array();
				foreach($val as $id=>$enabled) {
					if($enabled == 1) $a[] = $id;
				}
				$v = implode(',', $a);
				break;

			default:
				/* Join non-special arrays */
				if(is_array($val)) {
					if(count($val) == 0) {
						$v = NULL;
					} else {
						$a = array();
						foreach($val as $index=>$id) {
							if($id !== NULL) $a[] = $id;
						}
						$v = implode(',', $a);
					}
				} else if(is_null($val)) 
					$v = NULL;
				else 
					$v = $val;
				break;
			}

			if(is_null($v)) {
				$set .= "`$key`=NULL";
			} else {
				$v = stripslashes($v);
				$v = $mysqli->real_escape_string($v);
				$set .= "`$key`='$v'";
			}

			/* Set the original to the unprocessed value */
			$u['original'][$key] = $val;
		}
	}

//	print_r($u);

	if($set != '') {
		$query = "UPDATE users SET $set WHERE uid='{$u['uid']}'";
//		print($query);
		$mysqli->query($query);
		print($mysqli->error);
	}
}

function user_homepage(&$u) 
{
	global $config;
	$page = ''; //$config['fair_url'] . '/';

	/* In order of priority */
	if(in_array('student', $u['roles']))
		$page .= 'student_main.php';
	else if(in_array('committee', $u['roles']))
		$page .= 'c_main.php';
	else if(in_array('judge', $u['roles']))
		$page .= 'judge_main.php';
	else if(in_array('teacher', $u['roles']))
		$page .= 't_main.php';
	else if(in_array('volunteer', $u['roles']))
		$page .= 'v_main.php';
	else
		$page .= 'index.php';
	return $page;
}

?>
