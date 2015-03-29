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
		/* Make this a happy message because users are dumb..  I got
		 * about a dozen complaints each year from judges and parents
		 that were one of two things (no students have ever complained):
		 * 1 - users thought "expired" meant their temporary password
			expired and they needed to reset it again instead of
			following the directions on the screen that says they
			logged in successfully but need to set a new password
			before proceeding.
		 * 2 - the red message == login was unsuccessful, so they kept
			 trying to login until their account was locked and
			 complained that the temporary password didn't work 
	         */
		$initial_happy = "You have logged in successfully, but your password needs to be changed before continuing.  Please change your password below.";
	}
	form_page_begin($page_id, array(), '', $initial_happy);

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
