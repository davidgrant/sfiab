<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('login.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start();

/* Check access, but skip the expiry check */
sfiab_check_access($mysqli, array(), true);
$u = user_load($mysqli);

$action = '';
if(array_key_exists('action', $_GET)) {
	$action = $_GET['action'];
}

switch($action) {
case 'delete':
	$u['state'] = 'deleted';
	user_save($mysqli, $u);
	login_logout($mysqli, $u);
	header("Location: index.php#account_deleted");
	exit();

}

$page_id = 'a_delete_account';

sfiab_page_begin("Delete Account", $page_id);
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 
<?php
	$homepage = user_homepage($u);

?>
	<p>Really delete your account?  This action cannot be undone.

	<div class="ui-grid-a">
	<div class="ui-block-a"> 
	<a href="<?=$homepage?>" data-role="button" data-icon="check" data-theme="g">No, Cancel</a>
	</div>
	<div class="ui-block-b"> 
	<a href="a_delete_account.php?action=delete" data-role="button" data-icon="delete" data-ajax="false" data-theme="r">Yes, Delete Account</a>
	</div></div>

</div></div>

<?php
sfiab_page_end();
?>
