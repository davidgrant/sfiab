<?php
require_once('common.inc.php');
require_once('email.inc.php');
require_once('form.inc.php');

$mysqli = sfiab_init(NULL);

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}


function try_send_usernames($mysqli, $email)
{
	global $config;
	if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		return false;
	}

	$email = $mysqli->real_escape_string($email);
	$q = $mysqli->query("SELECT username FROM users WHERE `email`='$email' AND `year`='{$config['year']}'");
	while($r = $q->fetch_assoc()) {
		$username = $r['username'];
		$usernames[] = $username;
	}
	if(count($usernames) > 0) {
		$u = user_load_by_username($mysqli, $usernames[0]);
		email_send($mysqli, "Forgot Username", $u, array('username_list'=>join(', ', $usernames)) );
		return true;
	}
	return false;
}

switch($action) {
case 'pw':
	$username = $_POST['user'];

	/* Send password */
	$u = user_load_by_username($mysqli, $username);
	if($u != NULL) {
		user_scramble_and_expire_password($mysqli, $u);
		email_send($mysqli, "Forgot Password", $u, array('password'=>$u['scrambled_password']) );
		form_ajax_response(array('status'=>0, 'location'=>$config['fair_url'].'/index.php#forgot_password_sent'));
		exit();
	} else {
		/* not found */
		if(try_send_usernames($mysqli, $username)) {
			form_ajax_response(array('status'=>1, 'error' => 'That username was not found, but we found a matching email address in our system.  We have emailed the username(s) attached to that email address.  Please use one of these usernames to reset your password.'));
			exit();
		}
		sleep(3);
		form_ajax_response(array('status'=>1, 'error' => 'That username was not found in the system.  Try recoving your username by entering your email address below'));
		exit();
	}
	exit();


case 'un':
	$email = $_POST['em'];
	if(try_send_usernames($mysqli, $email)) {
		form_ajax_response(array('status'=>0, 'location'=>$config['fair_url'].'/index.php#forgot_username_sent'));
		exit();
	} else {
		sleep(3);
		form_ajax_response(array('status'=>1, 'error' => 'That email was not found in the system.'));
		exit();
	}
	exit();
}


$page_id = 'forgot';

sfiab_page_begin('Forgot', $page_id);

?>
<div data-role="page" id="<?=$page_id?>" class="sfiab_page" > 

<?php
	form_page_begin($page_id, array());
?>

	<h3>Recover Password</h3>
	Enter your Username and we will email you a link to reset your password
<?php
	$blank = '';
	$form_id1 = $page_id.'password';
	form_begin($form_id1, 'main_forgot.php');
	form_text($form_id1, 'user', 'Username', $blank);
	form_submit($form_id1, 'pw', 'Reset My Password', 'Checking...');
	form_end($form_id1);
?>

	<hr/>

	<h3>Recover Username</h3>
	Enter your email address and we will email you your Username
<?php	
	$form_id2 = $page_id.'username';
	form_begin($form_id2, 'main_forgot.php');
	form_text($form_id2, 'em', 'Email', $blank);
	form_submit($form_id2, 'un', 'Recover My Username', 'Checking...');
	form_end($form_id2);
?>

</div>

<script>
	function <?=$form_id1?>_pre_submit(form) {
		$('#<?=$form_id1?>_submit_pw').addClass('ui-disabled');
	}
	function <?=$form_id2?>_pre_submit(form) {
		$('#<?=$form_id2?>_submit_un').addClass('ui-disabled');
	}
</script>

<?php
output_end();
?>
