<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');

$mysqli = sfiab_init('student');

$page_id = "s_home";

$u = user_load($mysqli);
$closed = sfiab_registration_is_closed($u);

$in_prereg = sfiab_preregistration_is_open($u);

$help = '<p>This is the main student page.  
Use the menu on the left to guide your registration process.  
<p>Your registration status will change on this page when your registration is complete.';

sfiab_page_begin($u, "Student Main", 's_home', $help);

function print_ethics_approval($mysqli, &$u) 
{
	global $config;
	$p = project_load($mysqli, $u['s_pid']);
	$e =& $p['ethics'];
	if($e['human1'] === NULL || $e['animals'] === NULL) {
		$a = '<font color="red">Unknown</font>. Please complete the <a href="s_ethics.php">Project Ethics</a> page';
	} else if($e['human1'] || $e['animals'] ) {
		if($p['ethics_approved']) {
			$a = '<font color="green">Approved</font>. Please remember to bring all the necessary forms with you to the fair.';
		} else {
			$a = '<font color="red">Required, not approved yet</font>. Please email '.mailto($config['email_ethics']).' if you have not already done so.';
		}
	} else {
		$a = '<font color="green">Not Required</font>';
	}
?>
	<h3>Ethics Approval: <?=$a?></h3>
<?php
}
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

	<h3>Hello <?=$u['firstname']?>,</h3>

<?php	if(!$closed) {
		/* Check for any incoming project requests */
		$q = $mysqli->query("SELECT * FROM partner_requests WHERE to_uid='{$u['uid']}'");
		if($q->num_rows > 0) { ?>
			<h3>Partner Request</h3>
			<p>You have a partner request waiting, go to the <a href="s_partner.php">Project Partner</a> page to accept or reject it.
<?php		}
	}

	if($in_prereg) { 
		$reg_opens = date('F d, Y', strtotime($config['date_student_registration_opens']));
			?>
		<h3>Registration Status: <font color="orange">In Ethics Pre-Registration</font></h3>
		<?=print_ethics_approval($mysqli, $u);?>

		<p>The system is open for ethics pre-registration.  This allows
		you use the <a href="s_ethics.php">Project Ethics</a> and <a href="s_safety.php">Project Safety</a> pages (in the menu on
		the left) to determine what forms, procedures, and approvals are required
		before you start your project.  Generally, ALL projects involving humans and 
		animals must receive ethics approval before the fair.

		<p>Some pages on the left are unavailable in pre-registration.
		Normal registration opens on <?=$reg_opens?>.  This pre-registration
		does not guarantee entrance into the <?=$config['fair_name']?>.
		Projects eligibility may be subject to decisions made by
		schools and/or district fairs.  

		<p>If the <a href="s_ethics.php">Project Ethics</a> and <a href="s_safety.php">Project Safety</a> pages tell you to email
		our ethics committee (<?=mailto($config['email_ethics'])?>) for ethics
		pre-approval or approval, then you need to do that.  Generally, ALL projects
		involving humans and animals must receive ethics pre-approval
		or they will not be permitted to attend the fair.  Some
		low-risk projects, like surveys, may receive approval after
		completion and prior to attendance at the fair.
		
		<p>If you're not sure, please email us.  We are friendly people.
		Ethics approval is not hard to get, this procedure just ensures
		that all projects involving humans and animals are done in
		accordance with National Ethics Guidelines.

<?php	} else if($u['s_accepted'] == 1) { ?>
		<h3>Registration Status: <font color="green">Forms Received</font></h3>
		<?=print_ethics_approval($mysqli, $u);?>

		<p>All your registration forms have been received and
		processed.  No further changes to registration data are
		allowed.  If something in your registration needs changing,
		please contact the committee.  

		<p>Please bring your project number to the fair with you.  The
		number is your floor location.  At the fair, you can proceed
		directly to your floor location (we can help you find it, there
		will be maps posted) and setup your project before signing in.

<?php
		$p = project_load($mysqli, $u['s_pid']);
		if($p['number'] != '') { ?>
			<p>Project Number: <b><font size=+3><?=$p['number']?></font></b>
<?php		} 
		if($config['tours_enable']) {
			$tours = tour_load_all($mysqli);
			if($u['tour_id'] > 0) {
				$tour =& $tours[$u['tour_id']];
?>				<p>Tour: <b>#<?=$tour['num']?> - <?=$tour['name']?></b>
				<p>Tour Note: For safety reasons we are
				required to provide tour assignments and
				emergency contact information to the tour
				guides.  For this reason, we are unable to
				change your tour.
<?php			} else {
?>				<p>Tour: Not Assigned Yet
<?php			} 
		}

	} else if($u['s_complete'] == 0) { ?>
		<h3>Registration Status: <font color="red">Incomplete</font></h3>

<?php		print_ethics_approval($mysqli, $u);

		if($closed) { ?>
			<p>Registration is now closed.
<?php		} else { ?>
			<p>The red boxes in the left hand menu indicate which pages
			still have information that needs to be filled out.
<?php		}
	} else { ?>
		<h3>Registration Status: <font color="orange">Almost Complete</font></h3>

<?php		print_ethics_approval($mysqli, $u);

		if($closed) { ?>
			<p>Thank you for completing your registration.
			Registration is now closed and no further changes are
			allowed to your registration.

			<p> We will be processing all registration forms during
			the two weeks after registration closes.

			<p> Your status will change to "Forms Received" when we have processed your signature form.

			<p> Approximately one week before the fair your project number and tour selection will be posted.
		
<?php		} else { ?>
			<p>Thank you for completing your registration.  The final step of
			registration is to print the <a href="s_signature.php" data-rel="external" data-ajax="false">Signature Form</a> and send it in.

			<p> We will be processing all registration forms during
			the two weeks after registration closes.

			<p>Note: After we process your signature form, no further changes to your registration account will be allowed.
<?php		}
	}

?>
	<p><?=cms_get($mysqli, 's_main', $u);?>

	<h3>More Information:</h3>

	<p>If you get stuck and aren't sure what to do:
	<ul>
	<li>First, try the help menu by pressing the information icon <a href="#help_panel_s_home" data-role="button" data-icon="info" data-inline="true" data-iconpos="notext" class="ui-nodisc-icon ui-alt-icon"></a> on the top right of the page.  All pages have a help menu.
	<li>Second, please contact the Science Fair Committee.  Registration issues can be sent to: <?=mailto($config['email_registration'])?>
	General inquires sent to: <?=mailto($config['email_chair'])?>. If you're not sure, send an
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
