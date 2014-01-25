<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start();

/* Check access, but skip the expiry check */
sfiab_check_access($mysqli, array(), true);
$u = user_load($mysqli);

sfiab_page_begin("Change Password", "a_change_password");


#$fields = incomplete_fields($mysqli, 'account', $u, true);

?>

<div data-role="page" id="a_change_password"><div data-role="main" class="sfiab_page" > 
<?php
	$form_id = 'a_change_password_form';

	$initial_error = "";
	if($_SESSION['password_expired']) {
		$initial_error = "Your password has expired, please enter a new password";
	}

	form_begin($form_id, 'login.php', array(), $initial_error);
	$pw1 = '';
	$pw2 = '';
	form_text($form_id, 'pw1','New Password',$pw1, 'password');
	form_text($form_id, 'pw2','New Password Again',$pw2, 'password');
	form_submit($form_id, 'change_pw', "Change Password", 'Password Saved');
	form_end($form_id);
?>

</div></div>

<?php
sfiab_page_end();
?>
