<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
$mysqli = sfiab_init(NULL, true);

$u = user_load($mysqli);

sfiab_page_begin("Change Password", "a_change_password");

?>

<div data-role="page" id="a_change_password"><div data-role="main" class="sfiab_page" > 
<?php
	$page_id = 'a_change_password';
	$form_id = 'a_change_password_form';

	$initial_error = "";
	if($_SESSION['password_expired']) {
		$initial_error = "You have logged in successfully, but your password needs to be changed before continuing.  Please change your password below.";
	}
	form_page_begin($page_id, array(), $initial_error);

	form_begin($form_id, 'login.php');

?>
	<p>Passwords must be at least 8 characters long and contain at least one letter, one number, and one non-alphanumberic character (something other than a letter and a number)
<?php
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
