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

$page_id = 'a_delete_account';

sfiab_page_begin("Delete Account", $page_id);

?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 
	<p>
	This functionality will be coming soon.  In the meantime, if you need to delete your account please contact the chair.

<?php
/*	$pw1 = '';
	$pw2 = '';
	form_text($form_id, 'pw1','New Password',$pw1, 'password');
	form_text($form_id, 'pw2','New Password Again',$pw2, 'password');
	form_submit($form_id, 'change_pw', "Change Password", 'Password Saved');
	form_end($form_id);*/
?>

</div></div>

<?php
sfiab_page_end();
?>
