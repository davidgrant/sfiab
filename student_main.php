<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start();

$page_id = "s_home";

$u = user_load($mysqli);
$closed = sfiab_registration_is_closed($u);

/* Check access, but skip the expiry check */
sfiab_check_access($mysqli, array('student'));

$help = '<p>This is the main student page.  
Use the menu on the left to guide your registration process.  
<p>Your registration status will change on this page when your registration is complete.';

sfiab_page_begin("Student Main", 's_home', $help);
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

	<h3>Hello <?=$u['firstname']?>,</h3>


<?php	if(!$closed) {
		/* Check for any incoming project requests */
		$q = $mysqli->query("SELECT * FROM partner_requests WHERE to_uid='{$u['uid']}'");
		if($q->num_rows > 0) { ?>
			<h3>Partner Request</h3>
			<p>You have a partner request waiting, go to the <a href="student_partner.php">Project Partner</a> page to accept or reject it.
<?php		}
	}

	if($u['s_accepted'] == 1) { ?>
		<h3>Registration Status: <font color="green">Forms Received</font></h3>
		<p>All your registration forms have been received and processed.  No further changes to registration data are allowed.  If something in your registration needs changing, please contact the committee.  

		<p>Please bring your project number to the fair with you.  The
		number is your floor location.  At the fair, you can proceed
		directly to your floor location (we can help you find it, there
		will be maps posted) and setup your project before signing in.

		<p>For safety reasons we are required to provide tour assignments to UBC and emergency contact information to the tour guides.  
		For this reason, we are unable to change your tour.

<?php		$p = project_load($mysqli, $u['s_pid']);
		$tours = tour_load_all($mysqli);
		if($p['number'] != '') { ?>
			<p>Project Number: <b><font size=+3><?=$p['number']?></font></b>
<?php		} 
		if($u['tour_id'] > 0) {
			$tour =& $tours[$u['tour_id']];
?>			<p>Tour: <b>#<?=$tour['num']?> - <?=$tour['name']?></b>
<?php		} else {
?>			<p>Tour: Not Assigned Yet
<?php		} 

	} else if($u['s_complete'] == 0) { ?>
		<h3>Registration Status: <font color="red">Incomplete</font></h3>

<?php		if($closed) { ?>
			<p>Registration is now closed.
<?php		} else { ?>
			<p>The red boxes in the left hand menu indicate which pages
			still have information that needs to be filled out.
<?php		}
	} else { ?>
		<h3>Registration Status: <font color="orange">Almost Complete</font></h3>

<?php		if($closed) { ?>
			<p>Thank you for completing your registration.
			Registration is now closed and no further changes are
			allowed to your registration.

			<p> We will be processing all registration forms during
			the two weeks after registration closes.

			<p> Your status will change to "Forms Received" when we have processed your signature form.

			<p> Approximately one week before the fair your project number and tour selection will be posted.
		
<?php		} else { ?>
			<p>Thank you for completing your registration.  The final step of
			registration is to print the <a href="student_signature.php" data-rel="external" data-ajax="false">Signature Form</a> and send it in.

			<p> We will be processing all registration forms during
			the two weeks after registration closes.

			<p>Note: After we process your signature form, no further changes to your registration account will be allowed.
<?php		}
	}
?>

	<h3>Information For Fair Days</h3>
<?php	
	$files = array();
	foreach(array('schedule', 'ubcmap', 'safety_checklist','ballroom','partyroom','judgeform') as $f) {
		if(file_exists("data/{$config['year']}_$f.pdf")) {
			$files[$f] = "<a href=\"data/{$config['year']}_$f.pdf\">.pdf</a>";
		} else {
			$files[$f] = "Coming Soon";
		}
	} ?>

	The schedule of events: <?=$files['schedule']?><br/>
 	A map of the UBC campus: <?=$files['ubcmap']?><br/>
	A safety checklist for your project display: <?=$files['safety_checklist']?><br/>
	Project Floor Plan for the Student Union Building (Ballroom): <?=$files['ballroom']?><br/>
	Project Floor Plan for the Student Union Building (Party room): <?=$files['partyroom']?><br/>
	The judging form that will be used by the judges: <?=$files['judgeform']?><br/>


	<h3>More Information:</h3>

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


</div></div>

<?php
sfiab_page_end();
?>
