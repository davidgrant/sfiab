<?php
require_once('filter.inc.php');
require_once('debug.inc.php');

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

function user_change_password($mysqli, &$u, $new_password)
{
	$u['salt'] = base64_encode(mcrypt_create_iv(96, MCRYPT_DEV_URANDOM));
	$hashed_pw = hash('sha512', $new_password);
	$u['password'] = hash('sha512', $hashed_pw.$u['salt']);
	$u['password_expired'] = 0;
	sfiab_log($mysqli, 'change pw', "");
	user_save($mysqli, $u);
}

function user_scramble_and_expire_password($mysqli, &$u)
{
	/* Scramble the user's password.  Save the plaintext password in $u['scrambled_password'] which doesn't get saved anywhere or reloaded.  THings like the mailer
	 * need it to send to the user after a password reste */

	sfiab_log($mysqli, 'scramble pw', "");
	/* Get a new salt and password */
	$u['scrambled_password'] = substr(hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true)), 0, 9);
	user_change_password($mysqli, $u, $u['scrambled_password']);

	/* changing the password unexpires it, we want it expired */
	$u['password_expired'] = 1;
	user_save($mysqli, $u);
}



/* roles can be a single role, or a comma separated list of roles */
function user_create($mysqli, $username, $email, $role, $year)
{
	$u = ($username === NULL) ? 'NULL' : "'$username'";

	$q = $mysqli->real_query("INSERT INTO users (`username`,`new`,`enabled`,`email`,`year`,`roles`,`password`,`salt`,`password_expired`) 
				VALUES($u, '1','1','$email','$year','$role','','', '1')");
	$uid = $mysqli->insert_id;
	print($mysqli->error);
	/* Since this is a new user, set the unique id == uid */
	$mysqli->query("UPDATE users SET unique_uid=$uid WHERE uid=$uid");
	print($mysqli->error);

	$u = user_load($mysqli, $uid);

	/* Scramble password, set $u['scrambled_assword'] */
	user_scramble_and_expire_password($mysqli, $u);

	return $u;
}

function user_load($mysqli, $uid=-1, $unique_uid=-1, $username=NULL, $data=NULL)
{
	global $config;
	$u = NULL;
	if((int)$uid > 0) {
		$id = (int)$uid;
		$r = $mysqli->query("SELECT * FROM users WHERE uid=$id LIMIT 1");
	} else if((int)$unique_uid > 0) {
		$id = (int)$unique_uid;
		$r = $mysqli->query("SELECT * FROM users WHERE unique_uid=$id ORDER BY `year` DESC, `enabled` DESC LIMIT 1");
	} else if($username !== NULL) {
		$username = $mysqli->real_escape_string($username);
		$r = $mysqli->query("SELECT * FROM users WHERE `username`='$username' ORDER BY `year` DESC, `enabled` DESC LIMIT 1");
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
		/* Did we find a user? */
		if($r->num_rows == 0) {
			return NULL;
		}

		/* Load them */
		$u = $r->fetch_assoc();

		/* Check that the user is enabled. */
		if(!$u['enabled']) {
			/* Nope, there are no enabled users in the most recent year.  Therefore can't load anything */
			return NULL;
		}
	}

	/* Sanitize some fields */
	$u['uid'] = (int)$u['uid'];
	$u['roles'] = explode(",", $u['roles']);
	filter_bool($u['password_expired']);
	filter_bool($u['attending']);
	filter_bool($u['new']);
	filter_bool($u['enabled']);
	filter_bool($u['s_complete']);

	filter_languages($u['languages']);
	

	/* Student filtering */
	filter_int_or_null($u['schools_id']);
	filter_int_or_null($u['grade']);
	/* Clear out invalid input so the placeholder is shown again */
	if($u['birthdate'] == '0000-00-00') $u['birthdate'] = NULL;
	if($u['reg_close_override'] == '0000-00-00') $u['reg_close_override'] = NULL;

	filter_int_list($u['tour_id_pref'], 3);

	filter_int_or_null($u['tour_id']);
	filter_bool($u['s_web_firstname']);
	filter_bool($u['s_web_lastname']);
	filter_bool($u['s_web_photo']);

	/* Judge filtering */
	filter_bool($u['j_complete']);
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
	filter_str_list($u['j_languages']);
	filter_int_list($u['j_rounds']);

	/* Volutneer */
	filter_bool_or_null($u['v_complete']);
	filter_bool_or_null($u['v_tour_match_username']);

	/* Store an original copy so save() can figure out what (if anything) needs updating */
	unset($u['original']);
	$original = $u;
	$u['original'] = $original;

	/* After saving the original, make up some additional fields for conveinece.  Changing 
	 * these will do nothing because they aren't in the original and we don't want to 
	 * have to try and parse these back to fields we can save */
	$u['name'] = ($u['firstname'] ? "{$u['firstname']} " : '').$u['lastname'];
	

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


function user_save_array_str(&$val, $allow_null = false)
{
	if($val === NULL || count($val) == 0) {
		return NULL;
	} else {
		$a = array();
		foreach($val as $index=>$id) {
			if($id === NULL) {
				/* Store it, or skip it? */
				if($allow_null) {
					$a[] = '';
				} else {
					continue;
				}
			} else {
				$a[] = $id;
			}
		}
		return implode(',', $a);
	}
	
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
				$v = implode(',', $u['roles']);
				debug("user_save: roles=$v, ".print_r($r, true)."\n");
				break;

			case 'j_rounds':
				/* Create an array, but allow NULL entries, stored as ''.  When filtered on read, 
				 * any '' element is translated back to NULL */
			 	$v = user_save_array_str($val, true);
				break;

			default:
				/* Join non-special arrays */
				if(is_array($val)) {
					/* Join an array, but filter NULLs */
					$v = user_save_array_str($val, false);
				} else if(is_null($val)) {
					$v = NULL;
				} else {
					$v = $val;
				}
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
		debug("user_save: $query\n");
		$mysqli->real_query($query);
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

/* Copy a user to a new year.  Don't take a reference to $u because
 * we want to return a completely new user with the original untouched
 * mostly though, we'll do $u = user_copy($mysqli, $u, $config['year']); */
function user_copy($mysqli, $u, $new_year) 
{
	global $config;
	$new_u = user_create($mysqli, $u['username'], $u['email'], join(',',$u['roles']), $config['year']);
	$new_uid = $new_u['uid'];

	$old_uid = $u['uid'];
	$old_year = $u['year'];

	/* Bring the user with all the existing data up-to-date */
	$u['uid'] = $new_u['uid'];
	$u['year'] = $new_u['year'];
	$u['s_pid'] = NULL; /* We don't copy the project */
	$u['tour_id_pref'] = NULL; /* Tours have different IDs */
	$u['tour_id'] = NULL; /* Tours have different IDs */
	$u['tshirt'] = NULL; /* Force re-selection of tshirt */
	if($u['grade'] > 0) {
		$u['grade'] += ($new_year - $old_year); /* Normally grade is increased one per year*/
	}

	if($u['schools_id'] > 0) {
		/* Update the school */
		$q = $mysqli->query("SELECT `id` FROM `schools` WHERE year='$new_year' AND `common_id`=(SELECT `common_id` FROM `schools` WHERE `id`='{$u['schools_id']}')");
		$r = $q->fetch_row();
		$u['schools_id'] = $r[0];
	}

	$u['reg_close_override'] = NULL; /* Don't copy an existing reg override for last year */

	/* Copy any emergency contacts too, and relink them to the new user */
	$q = $mysqli->query("SELECT * FROM emergency_contacts WHERE `uid`='$old_uid'");
	while($r = $q->fetch_assoc()) {
		unset($r['id']);
		$r['uid'] = $new_uid;
		foreach($r as $k=>$v) {
			$r[$k] = $mysqli->real_escape_string($v);
		}
		$mysqli->real_query("INSERT INTO emergency_contacts(`".join('`,`',array_keys($r))."`) VALUES ('".join("','", array_values($r))."')");
	}
	$u['s_complete'] = 0;
	$u['s_accepted'] = 0;
	$u['s_paid'] = 0;

	$u['j_rounds'] = NULL;
	$u['j_mentored'] = NULL;
	$u['j_willing_lead'] = NULL;
	$u['j_dinner'] = NULL;
	$u['j_complete'] = 0;

	$u['v_complete'] = 0;
	

	/* Copy the new user original data into the user so that
	 * user_save detects that  everything has changed and re-saves it
	 * all, but saves it under the new uid from above */
	$u['original'] = $new_u['original'];

	user_save($mysqli, $u);
	return $u;
}

/* Export a user for remote transmission */
function user_get_export($mysqli, &$user)
{
	$export_u = array();

	$export_u['uid'] = $user['uid'];
	$export_u['unique_uid'] = $user['unique_uid'];
	$export_u['year'] = $user['year'];
	$export_u['salutation'] = $user['salutation'];
	$export_u['firstname'] = $user['firstname'];
	$export_u['lastname'] = $user['lastname'];
	$export_u['pronounce'] = $user['pronounce'];
	$export_u['username'] = $user['username'];
	$export_u['email'] = $user['email'];
	$export_u['sex'] = $user['sex'];
	$export_u['grade'] = $user['grade'];
	$export_u['language'] = $user['language'];
	$export_u['birthdate'] = $user['birthdate'];
	$export_u['address'] = $user['address'];
	$export_u['city'] = $user['city'];
	$export_u['postalcode'] = $user['postalcode'];
	$export_u['phone1'] = $user['phone1'];
	$export_u['phone2'] = $user['phone2'];
	$export_u['organization'] = $user['organization'];
	$export_u['medicalert'] = $user['medicalert'];
	$export_u['food_req'] = $user['food_req'];
	$export_u['roles'] = $user['roles'];
	
	$export_u['s_teacher'] = $user['s_teacher'];
	$export_u['s_teacher_email'] = $user['s_teacher_email'];
	if($user['schools_id'] > 0) {
		$q = $mysqli->query("SELECT school,city,province from schools WHERE id='{$user['schools_id']}' and year='{$user['year']}'");
		$school = $q->fetch_assoc();
		$export_u['school'] = array();
		$export_u['school']['school'] = $school['school'];
		$export_u['school']['city'] = $school['city'];
		$export_u['school']['province'] = $school['province'];
	}

	/* emergency contacts */
	$es = emergency_contact_load_for_user($mysqli, $user);
	foreach($es as $id=>$e) {
		$export_u['emergency_contacts'][$id] = array();
		$export_u['emergency_contacts'][$id]['firstname'] = $e['firstname'];
		$export_u['emergency_contacts'][$id]['lastname'] = $e['lastname'];
		$export_u['emergency_contacts'][$id]['relation'] = $e['relation'];
		$export_u['emergency_contacts'][$id]['email'] = $e['email'];
		$export_u['emergency_contacts'][$id]['phone1'] = $e['phone1'];
		$export_u['emergency_contacts'][$id]['phone2'] = $e['phone2'];
		$export_u['emergency_contacts'][$id]['phone3'] = $e['phone3'];
	}

	return $export_u;
}

/* Sync incoming_user from fair locally */
function user_sync($mysqli, &$fair, &$incoming_user)
{
	/* First find the user or create one */
	$year = intval($incoming_user['year']);
	$incoming_user_id = intval($incoming_user['uid']);
	if($year <= 0) exit();
	if($incoming_user_id <= 0) exit();

	/* Only allow synching certain roles (not committee) */
	$roles = array();
	foreach($incoming_user['roles'] as $r) {
		if(in_array($r, array('student','judge','sponsor','teacher'))) {
			$roles[] = $r;
		}
	}
	if(count($roles) == 0) exit();
	debug("user_sync: roles = ".print_r($roles, true)."\n");

	$q = $mysqli->query("SELECT * FROM users WHERE fair_id='{$fair['id']}' AND fair_uid='$incoming_user_id' AND year='$year'");
	if($q->num_rows > 0) {
		/* User exists, we can load and update */
		$data = $q->fetch_assoc();
		$u = user_load_from_data($mysqli, $data);
		debug("user_sync: found local user id={$u['uid']}\n");
	} else {
		/* Create a new user */
		$username = $mysqli->real_escape_string($incoming_user['username']);
		$check_username = $username;
		$x = 1;
		while(1) {
			$q = $mysqli->query("SELECT * FROM users WHERE username='$check_username' AND year='$year'");
			if($q->num_rows == 0) {
				$username = $check_username;
				break;
			}
			$check_username = $username.".".$x;
			$x++;
		}
		$u = user_create($mysqli, $username, $incoming_user['email'], $roles[0], $year);
		debug("user_sync: created new user id={$u['uid']}\n");
	}

	$u['fair_id'] = $fair['id'];
	$u['fair_uid'] = $incoming_user['uid'];
	$u['fair_unique_uid'] = $incoming_user['unique_uid']; /* Currently not in db */

	$u['new'] = 0;
	$u['year'] = $incoming_user['year'];
	$u['salutation'] = $incoming_user['salutation'];
	$u['firstname'] = $incoming_user['firstname'];
	$u['lastname'] = $incoming_user['lastname'];
	$u['pronounce'] = $incoming_user['pronounce'];
	$u['email'] = $incoming_user['email'];
	$u['sex'] = $incoming_user['sex'];
	$u['grade'] = $incoming_user['grade'];
	$u['language'] = $incoming_user['language'];
	$u['birthdate'] = $incoming_user['birthdate'];
	$u['address'] = $incoming_user['address'];
	$u['city'] = $incoming_user['city'];
	$u['postalcode'] = $incoming_user['postalcode'];
	$u['phone1'] = $incoming_user['phone1'];
	$u['phone2'] = $incoming_user['phone2'];
	$u['organization'] = $incoming_user['organization'];
	$u['medicalert'] = $incoming_user['medicalert'];
	$u['food_req'] = $incoming_user['food_req'];
	$u['roles'] = $roles;
	
	$u['s_teacher'] = $incoming_user['s_teacher'];
	$u['s_teacher_email'] = $incoming_user['s_teacher_email'];


	if(is_array($incoming_user['school'])) {
		$school_name = $mysqli->real_escape_string($incoming_user['school']['school']);
		$school_city = $mysqli->real_escape_string($incoming_user['school']['city']);
		$school_province = $mysqli->real_escape_string($incoming_user['school']['province']);
		$q = $mysqli->query("SELECT id FROM schools WHERE school='$school_name' AND city='$school_city' AND province='$school_province' AND year='$year' LIMIT 1");
		if($q->num_rows == 1) {
			/* Update the school, just in case */
			$r = $q->fetch_row();
			$school_id = (int)$r[0];
			debug("sync_user: found school id $school_id\n");
		} else {
			/* Create the school */
			$mysqli->real_query("INSERT INTO schools(`school`,`city`,`province`,`year`) VALUES('$school_name','$school_city','$school_province','$year')");
			$school_id = $mysqli->insert_id;
			$u['schools_id'] = (int)$schools_id;
			debug("sync_user: created new shcool id $school_id\n");
		}
		$u['schools_id'] = $school_id;
	} else {
		$u['schools_id'] = NULL;
	}

	if(count($incoming_user['emergency_contacts']) > 0) {
		$mysqli->real_query("DELETE FROM emergency_contacts WHERE uid='{$u['uid']}'");
		foreach($incoming_user['emergency_contacts'] as $id=>$e) {
			$fn = $mysqli->real_escape_string($e['firstname']);
			$ln = $mysqli->real_escape_string($e['lastname']);
			$re = $mysqli->real_escape_string($e['relation']);
			$em = $mysqli->real_escape_string($e['email']);
			$p1 = $mysqli->real_escape_string($e['phone1']);
			$p2 = $mysqli->real_escape_string($e['phone2']);
			$p3 = $mysqli->real_escape_string($e['phone3']);
			$mysqli->real_query("INSERT INTO emergency_contacts(`firstname`,`lastname`,`relation`,`phone1`,`phone2`,`phone3`)
					VALUES('$fn','$ln','$re','$em','$p1','$p2','$p3')");
		}
	}

	user_save($mysqli, $u);

	return $u;
}


?>
