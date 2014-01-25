<?php 

require_once('common.inc.php');
require_once('email.inc.php');
require_once('incomplete.inc.php');
require_once('form.inc.php');

if(!array_key_exists('action', $_POST)) { 
	$action = "logout";
} else {	
	$action = $_POST['action'];
}

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);


function check_username($username)
{
	$m = preg_match("/^[a-zA-Z0-9_\-@\.]+$/", $username); 
	return ($m == 1) ? true : false;
}

function check_email($email)
{
	return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function filter_hash($hash)
{
	return preg_replace("/[^a-fA-F0-9]+/","", $hash);
}

function check_attempts($mysqli, $uid)
{
	$interval = 60 * 30; // 30 minutes;
 
	$q = $mysqli->prepare("SELECT time FROM log WHERE uid = ? AND type = 'login bad pass' AND time > DATE_SUB(NOW(), INTERVAL $interval SECOND)"); 
	$q->bind_param('i', $uid); 
	$q->execute();
	$q->store_result();
	if($q->num_rows > 5) return true;
	return false;
}

switch($action) {
case 'salt':
	$username = $mysqli->real_escape_string($_POST['username']);
	/* Create a salt and send it back */
	if(!check_username($username)) {
		print('');
		exit();
	}

	$salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
	if(!$mysqli->query("UPDATE users SET salt='$salt' WHERE username='$username'")) {
		/* If it fails, log it, but send a salt anyway so the queryer (attacker?)
		 * can't tell if the user exists or not */
		sfiab_log($mysqli, -1, 'login bad salt', "username: $username");
	}
	print($salt);
	exit();

case 'register':
	global $sfiab_roles;
	sfiab_session_start();

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

	if(!check_username($username) || !check_email($email) || !array_key_exists($as, $sfiab_roles) || array_key_exists($as, $not_allowed_roles)) {
		/* Validation form isn't doing it's job */
		print('');
		$a = $mysqli->real_escape_string($as);
		sfiab_log($mysqli, "bad register", "Invalid data username: {$username}, email: {$email}, as: $as");
		exit();
	}

	/* Get the most recent username from any year that isn't deleted or new
	 * Deleted users can be created in the same year (so there could be more than one
	 *  username result for the same year, we don't want the username check to keep
	 *  returning the deleted user when another exists 
	 * New users are also fair game for overwriting the username , maybe the user
	 *  put in the wrong email 
	 * If the latest status for a user in the latest year is anything other than
	 *  new or deleted, t<T-F8>*/
	$q = $mysqli->prepare("SELECT `uid`,`year`,`state` FROM users WHERE username = ? ORDER BY `year`");
	$q->bind_param('s', $username); 
	$q->execute(); 
	$q->store_result();
	$q->bind_result($db_uid, $db_year, $db_state); 

	if($q->num_rows > 0) { 
		$latest_year = 0;
		$latest_state = NULL;
		while($q->fetch()) {
			$year = (int)$db_year;
			if($year > $latest_year) {
				$latest_year = $year;
				$latest_status = $db_state;
				continue;
			} else if ($year == $db_year) {
				if($latest_state == 'deleted' || $latest_status == 'new') {
					$latest_state = $db_state;
				}
			}
		}

		if($latest_state == 'deleted' || $latest_status == 'new') {
			/* Ok, the latest state we have is that the user was deleted or new, so
			 * they can be overridden */
		} else {
			print('Sorry, username already exists');
			exit();
		}
	}

	/* Delete user, this could delete 'new' usernames from past years too, that's ok
	 * since the registration was never finished just delete it (and maybe save some space) */
	$q = $mysqli->real_query("DELETE FROM users WHERE `username`='$username' AND `state`='new'");
	print($mysqli->error);

	/* Create new 9 character scrambled password */
	$password = substr(hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true)), 0, 9);
	$password_hash = hash('sha512', $password);
	$q = $mysqli->real_query("INSERT INTO users (`username`,`state`,`email`,`year`,`roles`,`firstname`,`lastname`,`password`,`salt`,`password_expired`) 
				VALUES('$username', 'new','$email',{$config['year']},'$as','$fn','$ln','$password_hash','','1')");
	$uid = $mysqli->insert_id;

	/* Since this is a new user, set the unique id == uid */
	$mysqli->query("UPDATE users SET unique_uid=$uid WHERE uid=$uid");

	/* Send an email */
	$result = email_send($mysqli, "New Registration", $uid, array('PASSWORD'=>$password) );

	sfiab_log($mysqli, "register", "username: {$username}, email: {$email}, as: $as, email status: $result");
	
	print('0');
	exit();

	
case 'login':
	$username = strtolower($mysqli->real_escape_string($_POST['username']));
	$hash = $mysqli->real_escape_string(filter_hash($_POST['password']));

	if(!check_username($username)) {
		print("");
		exit();
	}

	$u = user_load_by_username($mysqli, $username);

	/* User exists? */
	if($u == NULL) { 
		sfiab_log($mysqli, 'login no user', $username);
		print('Sorry, invalid username or password.1');
		exit();
	}

	/* user must be active */
	switch($u['state']) {
	case 'active': case 'new':
		break;
	case 'disabled':
		print('Sorry, invalid username or password.2');
		exit();
	default: 
		print('');
		exit();
	}

	/* Salt must be valid */
	if(strlen($u['salt']) != 128) {
		sfiab_log($mysqli, 'login bad salt', $username);
		print('Sorry, invalid username or password.4');
		exit();
	}

	/* Wipe out the salt so the same hash can't be used twice */
	$mysqli->query("UPDATE users SET salt='0' WHERE uid={$u['uid']}");

	/* Check for too many login attempts */
	if(check_attempts($mysqli, $u['uid']) == true) { 
		sfiab_log($mysqli, 'login locked', $username, $u['uid']);
		print('This account has been locked due to too many failed login attempts.  It will be unlocked in 30 minutes, or use the password recovery link below to unlock it immediately');
		exit();
	}

	/* Passwords match? */
	$password_hash = hash('sha512', $u['password'].$u['salt']); // hash the password with the unique salt.
	if($password_hash != $hash) {
		sfiab_log($mysqli, 'login bad pass', $username, $u['uid']);
		print('Sorry, invalid username or password.3');
		exit();
	}

	/* User is new? */
	if($u['state'] == 'new') {
		$u['state'] = 'active';
		user_save($mysqli, $u);
	}

	sfiab_session_start();
	$_SESSION['uid'] = $u['uid'];
	$_SESSION['unique_uid'] = $u['unique_uid'];
	$_SESSION['username'] = $username;
	$_SESSION['roles'] = $u['roles'];
	$_SESSION['password_expired'] = $u['password_expired'];
	$_SESSION['u'] = $u;

	/* Hash the passwd with the browser, the browser shouldn't change. we can check
	 * it every page load */
	$_SESSION['session_hash'] = hash('sha512', $password_hash.$_SERVER['HTTP_USER_AGENT']);

	/* Populate the complete status of all fields */
	$_SESSION['incomplete'] = array();
	$_SESSION['complete'] = false;
	incomplete_check($mysqli, $u);

	sfiab_log($mysqli, 'login ok', $username);
	print(0);
	exit();

case 'change_pw':
	sfiab_session_start();
	$pw1 = $mysqli->real_escape_string($_POST['pw1']);
	$pw2 = $mysqli->real_escape_string($_POST['pw2']);
	$letters = 'abcdefgjijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$numbers = '1234567890';
	if(strlen($pw1) < 8 || !strpbrk($pw1, $letters) || !strpbrk($pw1, $numbers) || strspn($pw1, $letters.$numbers) == strlen($pw1) ) {
		form_ajax_response_error(1, 'Passwords must be at least 8 characters long and contain at least one letter, one number, and one non-alphanumberic character (something other than a letter and a number)');	
		exit();
	}

	if($pw1 != $pw2) {
		form_ajax_response_error(1, 'Passwords don\'t match');
		exit();
	}

	$hash = hash('sha512', $pw1);
	$uid = $_SESSION['uid'];
	$mysqli->query("UPDATE users SET password='$hash', password_expired='0' WHERE uid=$uid");
	sfiab_log($mysqli, 'change pw', "");

	if($_SESSION['password_expired']) {
		$_SESSION['password_expired'] = false;
		form_ajax_response(array('status'=>0, 'location'=>'main.php'));
	} else {
		form_ajax_response(array('status'=>0,'happy'=>'Password changed'));
	}
	exit();

case 'logout':
	sfiab_session_start();
	sfiab_log($mysqli, 'logout', $_SESSION['username']);
	// Unset all session values
	$_SESSION = array();
	// get session parameters 
	$params = session_get_cookie_params();
	// Delete the actual cookie.
	setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
	// Destroy session
	session_destroy();
	print(0);
	exit();
}

?>
