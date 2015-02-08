<?php 

require_once('common.inc.php');
require_once('email.inc.php');
require_once('incomplete.inc.php');
require_once('form.inc.php');
require_once('login.inc.php');

if(!array_key_exists('action', $_POST)) { 
	$action = "logout";
} else {	
	$action = $_POST['action'];
}

$mysqli = sfiab_init(NULL);

function check_username($username)
{
	/* Return false if there's a character thats isn't one of these: */
	$m = preg_match("/^[a-zA-Z0-9_\-@\.]+$/", $username); 
	if($m != 1) return false;

	/* Return false if the username is too short */
	if(strlen($username) < 2) return false;

	return true;
}

function check_email($email)
{
	return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function filter_hash($hash)
{
	return preg_replace("/[^a-fA-F0-9]+/","", $hash);
}

function ajax($status, $msg)
{
	return json_encode(array('s'=>$status, 'm'=>$msg));
}

function check_attempts($mysqli, $uid)
{
	$interval = 60 * 30; // 30 minutes;
	$uid = (int)$uid;
 
	$q = $mysqli->query("SELECT time FROM log WHERE uid = '$uid'
						AND type = 'login' AND result='0'
						AND time > DATE_SUB(NOW(), INTERVAL $interval SECOND)
						ORDER BY time DESC
						LIMIT 6"); 
	if($q->num_rows > 5) {
		/* Seek to the one 6 attempts ago */
		$q->data_seek(5);
		$d = $q->fetch_assoc();
		$t = $d['time'];

		/* Ok, has there been a password reset in there? */
		$q1 = $mysqli->query("SELECT time FROM log WHERE uid = '$uid' AND type = 'reset pw' AND time >= '$t' LIMIT 1"); 
		if($q1->num_rows > 0) {
			/* There is a password reset in the same interval, so account isn't locked */
			return false;
		}
		/* 6 bad login attempts, in the last 30 mins.  Account is locked. */
		return true;
	}
	/* Less than 6 attempts.  Not locked */
	return false;
}

switch($action) {

case 'register':
	global $sfiab_roles;

	if(sfiab_user_is_a('committee')) {
		$not_allowed_roles = array();
	} else {
		$not_allowed_roles = array('committee');
	}

	$username = $mysqli->real_escape_string($_POST['username']);
	$as = $mysqli->real_escape_string($_POST['as']);
	$email = $mysqli->real_escape_string($_POST['email']);
	$fn = $mysqli->real_escape_string($_POST['firstname']);
	$ln = $mysqli->real_escape_string($_POST['lastname']);

	if(!sfiab_user_is_a('committee') && sfiab_registration_is_closed(NULL, $as)) {
		/* Should never get here */
		exit();
	}

	if(!check_username($username) || !check_email($email) || !array_key_exists($as, $sfiab_roles) || array_key_exists($as, $not_allowed_roles)) {
		/* Validation form isn't doing it's job */
		print('');
		sfiab_log_register($mysqli, $username, $email, $as, "Invalid data provided", 0);
		exit();
	}


	/* Get the most recent username from any year that isn't deleted or new
	 * Deleted users can be created in the same year (so there could be more than one
	 *  username result for the same year, we don't want the username check to keep
	 *  returning the deleted user when another exists 
	 * New users are also fair game for overwriting the username , maybe the user
	 *  put in the wrong email 
	 * If the latest status for a user in the latest year is anything other than
	 *  new or deleted, */
	$q_username = $mysqli->real_escape_string($username);
 	$q = $mysqli->query("SELECT `uid`,`year`,`enabled`,`new` FROM `users` WHERE username='$q_username' ORDER BY `year` DESC,`enabled` DESC LIMIT 1");
	if($q->num_rows > 0) { 
		$r = $q->fetch_assoc();
		if(!$r['new']) {
			print('Sorry, username already exists');
			exit();
		}
	}

	/* Delete user, just in case.  This could delete 'new' usernames from
	 * past years too, that's ok * since the registration was never finished
	 * just delete it (and maybe save some space) */
	$q = $mysqli->real_query("DELETE FROM users WHERE `username`='$username' AND `new`='1'");
	print($mysqli->error);

	$u = user_create($mysqli, $username, $email, $as, $config['year']);
	$u['firstname'] = $fn;
	$u['lastname'] = $ln;
	user_save($mysqli, $u);

	/* Send an email, also re-scrambles their password */
	email_send_welcome_email($mysqli, $u);

	sfiab_log_register($mysqli, $u, $email, $as, "", 1);
	
	print('0');
	exit();

	
case 'login':
	
	$username = strtolower($mysqli->real_escape_string($_POST['username']));
	$hashed_pw = $mysqli->real_escape_string(filter_hash($_POST['password']));

	if(!check_username($username)) {
		print(ajax(1, 'Sorry, invalid username or password'));
		exit();
	}

	$u = user_load_by_username($mysqli, $username);

	/* User exists? */
	if($u == NULL) { 
		sfiab_log_login($mysqli, $username, 'no user', 0);
		print(ajax(1, 'Sorry, invalid username or password'));
		exit();
	}

	/* user must be active */
	if(!$u['enabled']) {
		sfiab_log_login($mysqli, $u, 'user not enabled', 0);
		print(ajax(1, 'Sorry, invalid username or password'));
		exit();
	}

	/* Hash must be valid, it gets read from the $_SESSION, so there's no 
	 * reason for it not to be valid*/
	if(strlen($u['salt']) != 128) {
		sfiab_log_login($mysqli, $u, 'bad salt', 0);
		print(ajax(1, 'Sorry, invalid username or password'));
		exit();
	}

	/* Wipe out the challenge hash so the same hash can't be used twice */
//	$mysqli->query("UPDATE users SET challenge_hash='0' WHERE uid={$u['uid']}");

	/* Check for too many login attempts */
	if(check_attempts($mysqli, $u['uid']) == true) { 
		sfiab_log_login($mysqli, $u, 'locked', 0);
		print(ajax(2, 'This account has been locked due to too many failed login attempts.  It will be unlocked in 30 minutes, or use the password recovery link below to unlock it immediately'));
		exit();
	}

	/* Passwords match? */
	/* user provides:       hash(hash(p).login_hash) 
	 * we compute   : hash( hash(hash(p).login_hash) . salt ) and see if it matches the hash in our db 
	 * we store:  salt, hash(salt.hash(p),  */

	/* Take the user's provided password hash (hash(p), and hash it with the salt, then see if that's what is in the
	 * database */
	$salted_hash = hash('sha512', $hashed_pw.$u['salt']);
	if($salted_hash != $u['password']) {
		sfiab_log_login($mysqli, $u, 'bad bass', 0);
		print(ajax(1, 'Sorry, invalid username or password'));
		exit();
	}

	/* If the year doesn't match, duplicate the user into the current year */
	if($u['year'] != $config['year']) {
		$u = user_copy($mysqli, $u, $config['year']);

		/* Pretend that they're new so if they're a student they get a new project */
		$u['new'] = 1;
	}

	/* Is the user a student?, if so also create a project */
	if($u['new']) {
		$u['new'] = 0;
		if(in_array('student', $u['roles'])) {
			$pid = project_create($mysqli);
			$u['s_pid'] = $pid;
		}
		user_save($mysqli, $u);
	}

//	sfiab_session_start();
	$_SESSION['uid'] = $u['uid'];
	$_SESSION['unique_uid'] = $u['unique_uid'];
	$_SESSION['username'] = $username;
	$_SESSION['roles'] = $u['roles'];
	$_SESSION['password_expired'] = $u['password_expired'];
	$_SESSION['u'] = $u;

	/* Populate the complete status of all fields */
	$_SESSION['incomplete'] = array();
	$_SESSION['complete'] = false;

	/* Force complete check on login */
	$reg = array();
	incomplete_check($mysqli, $reg, $u, false, true);
	
	sfiab_log_login($mysqli, $u, 'ok', 1);
	print(ajax(0, user_homepage($u)));
	exit();

case 'change_pw':
	/* The password goes through a hash, so we can let them do whatever they want */
	$pw1 = $_POST['pw1'];
	$pw2 = $_POST['pw2'];
	$letters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$numbers = '1234567890';
	if(strlen($pw1) < 8 || !strpbrk($pw1, $letters) || !strpbrk($pw1, $numbers) || strspn($pw1, $letters.$numbers) == strlen($pw1) ) {
		form_ajax_response_error(1, 'Passwords must be at least 8 characters long and contain at least one letter, one number, and one non-alphanumberic character (something other than a letter and a number)');
		exit();
	}
	if(strlen($pw1) > 256) {
		/* Is the user typing a novel? */
		form_ajax_response_error(1, 'Bad Password.');
		exit();
	}

	if($pw1 != $pw2) {
		form_ajax_response_error(1, 'Passwords don\'t match');
		exit();
	}

	/* Auto-load the user from the session */
	$u = user_load($mysqli);
	user_change_password($mysqli, $u, $pw1);

	if($_SESSION['password_expired']) {
		$_SESSION['password_expired'] = false;
		form_ajax_response(array('status'=>0, 'location'=> user_homepage($u)));
	} else {
		form_ajax_response(array('status'=>0,'happy'=>'Password changed'));
	}
	exit();

case 'logout':
	$u = user_load($mysqli);
	login_logout($mysqli, $u);
	print(0);
	exit();
}

?>
