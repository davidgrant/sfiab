<?php
require_once('common.inc.php');
require_once('user.inc.php');
$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start();

$page_id = "s_home";

$u = user_load($mysqli);

/* Check access, but skip the expiry check */
sfiab_check_access($mysqli, array('student'));

sfiab_page_begin("Student Main", 's_home');
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

	<h3>Student Home</h3>

	Under construction, only the first few menus on the left work, the rest throw an error.  

	Don't try student registration yet, I know it doesn't work.


<?php
	/* Check for any incoming project requests */
	$q = $mysqli->query("SELECT * FROM partner_requests WHERE to_uid='{$u['uid']}'");
	if($q->num_rows > 0) { ?>
		<h3>Partner Request</h3>
		<p>You have a partner request waiting, go to the <a href="student_partner.php">Project Partner</a> page to accept or reject it.
<?php	} ?>
	

</div></div>

<?php
sfiab_page_end();
?>
