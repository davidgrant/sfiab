<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');

function email_load($mysqli, $email_name, $eid=-1, $data=NULL)
{
	if($email_name != '') {
		$n = $mysqli->real_escape_string($email_name);
		$q = $mysqli->query("SELECT * FROM emails WHERE name='$n'");
		$e = $q->fetch_assoc();
	} else if($eid > 0) {
		$q = $mysqli->query("SELECT * FROM emails WHERE id='$eid'");
		$e = $q->fetch_assoc();
	} else {
		$e = $data;
	}

	unset($e['original']);
	$original = $e;
	$e['original'] = $original;
	return $e;
}

function email_save($mysqli, &$e)
{
	generic_save($mysqli, $e, 'emails', 'id');
}

function email_create($mysqli)
{
	global $config;
	$r = $mysqli->real_query("INSERT INTO emails(`section`) VALUES('Uncategorized')");
	$eid = $mysqli->insert_id;
	return $eid;
}

function email_id($mysqli, $email_name)
{
	/* Lookup the ID of this email */
	$q = $mysqli->query("SELECT `id` FROM `emails` WHERE `name`='$email_name'");
	if($q->num_rows != 1) {
		/* Not found */
		$uid = 0;
		sfiab_log($mysqli, "email_not_found", $uid, 0, $email_name, "Email \"$email_name\" not found.");
		return false;
	}
	$r = $q->fetch_assoc();
	$db_id = $r['id'];
	return $db_id;
	
}


/* Send to an array of users, no indexing just users = [ $u, $u, $u, etc. ] */
function email_send_to_list($mysqli, $email_name, &$users, $additional_replace = array()) 
{
	global $config;

	$db_id = email_id($mysqli, $email_name);
	if($db_id == false) {
		return false;
	}

	/* Fill in additional replace vars that the email send script can't
	 * calculate from the command line, like the fair URL */
	$additional_replace['fair_url'] = $config['fair_url'];
	$ad = $mysqli->real_escape_string(serialize($additional_replace));

	$query_str = '';
	$c = 0;

	foreach($users as &$user) {
		if(!$user['enabled']) {
			debug("email_send: user {$user['uid']} is not enabled\n");
			continue;
		}

		$n = $mysqli->real_escape_string($user['name']);
		$em = $mysqli->real_escape_string($user['email']);
		$uid = (int)$user['uid'];

		/* Build up 10 queries before sending running the mysql insert */
		if($query_str == '') {
			$query_str = 'INSERT INTO queue(`command`,`emails_id`,`to_uid`,`to_email`,`to_name`,`additional_replace`,`result`) VALUES ';
		} else {
			$query_str .= ',';
		}
		$query_str .= "('email','$db_id','$uid','$em','$n','$ad','queued')";

		if($c == 10) {
			$mysqli->real_query($query_str);
			if($mysqli->error != '') {
				debug("email_send: query failed: $query_str\n");
				debug("email_send: {$mysqli->error}\n");
			}		
			$query_str = '';
			$c = 0;
		}

		$c += 1;

		debug("email_send: queued email $db_id, uid=$uid, em=$em, name=$n, replace=$ad\n");
	}

	/* $query_str probably has stuff in it for the last query we were building.  Every incremental
	 * step is a valid query, so just send it */
	if($query_str != '') {
		$mysqli->real_query($query_str);
		if($mysqli->error != '') {
			debug("email_send: query failed: $query_str\n");
			debug("email_send: {$mysqli->error}\n");
		}		
	}

	queue_start($mysqli);
}

/* Send a single email to a single user.  Can specify uid or $u if it's already loaded.  Just turns the user into a list and calls 
 * email_send_to_list */
 function email_send($mysqli, $email_name, &$uid_or_u, $additional_replace = array()) 
{
	if(is_array($uid_or_u) && array_key_exists('uid', $uid_or_u) ) {
		$users = array($uid_or_u);
	} else {
		/* Lookup the user */
		$u = user_load($mysqli, $uid_or_u);
		if($u == NULL) {
			debug("email_send: user is null\n");
			return false;
		}	
		$users = array($u);
	}

	email_send_to_list($mysqli, $email_name, $users, $additional_replace);
	return true;
}

function email_send_to_non_user($mysqli, $email_name, $to_name, $to_email, $additional_replace = array())
{
	/* Create a fake user */
	$u = array();
	$u['uid'] = 0;
	$u['enabled'] = 1;
	$u['email'] = filter_var($to_email, FILTER_VALIDATE_EMAIL);
	$u['name'] = $to_name;

	if($u['email'] == false) {
		debug("email is invalid");
		return false;
	}

	$users = array($u);
	email_send_to_list($mysqli, $email_name, $users, $additional_replace);
	return true;
}


function queue_stopped($mysqli) 
{
	$qstop = $mysqli->query("SELECT val FROM config WHERE var='queue_stop'");
	$vstop = $qstop->fetch_assoc();
	if((int)$vstop['val'] == 1) {
		return true;
	}
	return false;
}

function queue_stop($mysqli) 
{
	$mysqli->query("UPDATE config SET val='1' WHERE var='queue_stop'");
}

function queue_start($mysqli) 
{
	$mysqli->query("UPDATE config SET val='0' WHERE var='queue_stop'");
	exec("php -q scripts/sfiab_queue_runner.php 1>/dev/null 2>&1 &");
}


function find_users_needing_registration_email($mysqli)
{
	global $config;

	$email_id = email_id($mysqli, "New Registration");

	/* Select all successful welcome emails, index by user id */
	$welcome_emails = array();
	$q = $mysqli->query("SELECT `uid` FROM `log` WHERE `type`='email_send' AND `email_id`='$email_id' AND `result`='1' AND `year`='{$config['year']}'");
	print($mysqli->error);
	while($r = $q->fetch_row()) {
		$uid = (int)$r[0];
		$welcome_emails[$uid] = 1;
	}

	$new_users = array();
	/* Find all new users, and for each, check if there is a welcome email in the queue that was successfully sent */
	$q = $mysqli->query("SELECT * FROM users WHERE `fair_id`>0 AND FIND_IN_SET('student',`roles`)>0 AND `password_expired`='1' AND `year`='{$config['year']}'");
	while($r = $q->fetch_assoc()) {
		$uid = (int)$r['uid'];
		if(!array_key_exists($uid, $welcome_emails)) {
			/* New user, but no welcome email has been sent */
			$new_users[$uid] = user_load_from_data($mysqli, $r);
		}
	}
	return $new_users;

}

/* Scramble the user's password and send a welcome email */
function email_send_welcome_email($mysqli, &$user) 
{
	user_scramble_and_expire_password($mysqli, $user);
	$result = email_send($mysqli, "New Registration", $user, array('password'=>$user['scrambled_password']) );
	return $result;
}

?>
