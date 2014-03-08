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

	<p>Welcome to the new GVRSF registration system.
	
	<p>If you get stuck and aren't sure what to do:
	<ul>
	<li>First, try the help menu by pressing the information icon <a href="#help_panel_s_home" data-role="button" data-icon="info" data-inline="true" data-iconpos="notext" class="ui-nodisc-icon ui-alt-icon"></a> on the top right of the page.  All pages have a help menu.
	<li>Second, please contact the Science Fair Committee.  Registration issues can be sent to: registration@gvrsf.ca.
	General inquires sent to: chair@gvrsf.ca.  If you're not sure, send an
	email to both, we're all friendly people.
	</ul>

	<p>A few things have changed in the registration system.  Most significantly:
	<ul>
	<li>You no longer need a registration ID.  You just need a username and a password.  Parents/Teachers: Multiple students can share the same email address now.
	<li>For partner projects, each student needs a separate account now.  One student then invites the other to the project at which point the projects are linked (but the student information, emergency contact, and tour selection remains separate for each student).
	</ul>


<?php
	/* Check for any incoming project requests */
	$q = $mysqli->query("SELECT * FROM partner_requests WHERE to_uid='{$u['uid']}'");
	if($q->num_rows > 0) { ?>
		<h3>Partner Request</h3>
		<p>You have a partner request waiting, go to the <a href="student_partner.php">Project Partner</a> page to accept or reject it.
<?php	} ?>


<?php	if($u['s_accepted'] == 1) { ?>

		<h3>Registration Status: <font color="green">Forms Received</font></h3>
		
		All your registration forms have been received and processed.

<?php	} else if($u['s_complete'] == 0) { ?>
		<h3>Registration Status: <font color="red">Incomplete</font></h3>

		<p>The red boxes in the left hand menu indicate which pages
		still have information that needs to be filled out.

<?php	} else { ?>

		<h3>Registration Status: <font color="orange">Almost Complete</font></h3>

		<p>Thank you for completing your registration.  The final step of
		registration is to print the <a href="student_signature.php" data-rel="external" data-ajax="false">Signature Form</a> and send it in.

		<p> We will be processing all registration forms during the two weeks
		after registration closes 
<?php	
	}
?>

	<h3>Forms and Documents</h3>
	<p>We will be putting forms and documents here.  Most forms can already be found in the student handbook on <a target="_blank" href="http://www.gvrsf.ca">www.gvrsf.ca</a>
	

</div></div>

<?php
sfiab_page_end();
?>
