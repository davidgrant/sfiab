<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('login.inc.php');

$mysqli = sfiab_init(NULL);

/* Check access, but skip the expiry check */
sfiab_check_access($mysqli, array(), true);
$u = user_load($mysqli);

$closed = sfiab_registration_is_closed($u);

$action = '';
if(array_key_exists('action', $_GET)) {
	$action = $_GET['action'];
}

switch($action) {
case 'delete':

	if($closed)  exit();

	$u['enabled'] = 0;
	$u['s_accepted'] = 0;
	$u['tour_id'] = NULL;
	if($u['s_pid'] > 0) {
		$p = project_load($mysqli, $u['s_pid']);
		$p['accepted'] = 0;
		$mysqli->real_query("DELETE FROM timeslot_assignments WHERE pid='{$u['s_pid']}'");
		project_save($mysqli, $p);
	}
	user_save($mysqli, $u);
	login_logout($mysqli, $u);
	header("Location: index.php#account_deleted");
	exit();

}

$page_id = 'a_delete_account';

sfiab_page_begin("Delete Account", $page_id);
?>

<div data-role="page" id="<?=$page_id?>" class="sfiab_page" > 
<?php
	$homepage = user_homepage($u);

	if($closed) { ?>
		<p>Accounts cannot be deleted after registration is closed.  Please contact the registration coordinator <?=mailto($config['email_registration'])?> to delete your account

<?php	} else {  ?>
		<p>Really delete your account?  This action cannot be undone.

		<table width="50%">
		<tr><td>
		<a href="a_delete_account.php?action=delete" data-role="button" data-icon="delete" data-ajax="false" data-theme="l">Yes, Delete Account</a><br/>
		</td></tr>
		<tr><td>
		<a href="<?=$homepage?>" data-role="button" data-icon="back" >No, Cancel</a>
		</td></tr>
		</table>
<?php	} ?>		
</div>

<?php
sfiab_page_end();
?>
