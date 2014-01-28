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

$help = '<p>This is the main student page.  Use the menu on the left to guide your registration process.  Your registration status will change on this page when your registration is complete.';

sfiab_page_begin("Student Main", 's_home', $help);
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

	<h3>Hello <?=$u['firstname']?>,</h3>

	<p>Welcome to the new GVRSF registration system.  Help for most pages is available by clicking the information icon on the top right of the page.

<?php
	/* Check for any incoming project requests */
	$q = $mysqli->query("SELECT * FROM partner_requests WHERE to_uid='{$u['uid']}'");
	if($q->num_rows > 0) { ?>
		<h3>Partner Request</h3>
		<p>You have a partner request waiting, go to the <a href="student_partner.php">Project Partner</a> page to accept or reject it.
<?php	} ?>


<?php	if($u['s_status'] == 'incomplete') { ?>
		<h3>Registration Status: <font color="red">Incomplete</font></h3>

		<p>The red boxes in the left hand menu indicate which pages
		still have information that needs to be filled out.
<?php	} else if($u['s_status'] == 'complete') { ?>

		<h3>Registration Status: <font color="orange">Almost Complete</font></h3>

		<p>Thank you for completing your registration.  The final step of
		registration is to print the signature page and send it in.

		<p> We will be processing all registration forms during the two weeks
		after registration closes 



<?php	} else if($u['s_status'] == 'accepted') { ?>

		<h3>Registration Status: <font color="green">Forms Received</font></h3>
		
		All your registration forms have been received and processed.
<?php	} ?>

	<h3>Forms and Documents</h3>
	<p>We will be putting forms and documents here.  Most forms can already be found in the student handbook on <a href="http://www.gvrsf.ca">www.gvrsf.ca</a>
	

</div></div>

<?php
sfiab_page_end();
?>
