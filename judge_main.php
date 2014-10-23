<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('form.inc.php');
require_once('incomplete.inc.php');

$mysqli = sfiab_init('judge');

$page_id = "j_home";

$u = user_load($mysqli);
$closed = sfiab_registration_is_closed($u);

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

switch($action) {
case 'save':
	$attending = NULL;
	post_bool($u['attending'], 'j_attending');
	user_save($mysqli, $u);
	if($u['attending']) {
		/* Do a full check, this will set the user
		 * status to complete or incomplete and save it */
		$fields = array();
		incomplete_check($mysqli, $fields, $u, false, true);
	}

	form_ajax_response(array('status'=>0, 'location'=>'judge_main.php'));
	exit();
}


$help = '
<ul><li><b>Attending the Fair</b> - If you are unable to attend the fair,
indicate that here and you will not be assigned to any judging
team, or we will remove you from any teams you have been assigned to.  
</ul>';

sfiab_page_begin("Judge Main", $page_id, $help);
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 
<?php
	form_page_begin($page_id, array());
?>
	<h3>Hello <?=$u['firstname']?>,</h3>

	<p> Welcome to the new <?=$config['fair_abbreviation']?> registration system.  
	
	<p>Help for all pages is available by pressing the information icon <a href="#help_panel_j_home" data-role="button" data-icon="info" data-inline="true" data-iconpos="notext" class="ui-nodisc-icon ui-alt-icon"></a> on the top right of the page.

<?php
	if(!$u['attending']) {
?>
		<h3>Registration Status: <font color="blue">Not Attending</font></h3>

		<p>You have indicated that you're not able to judge at the
		fair this year, thanks for letting us know.  You won't be
		assigned to judge anything and you don't need to fill out
		anything else.  
		
		<p>Your registration will still be here next year if you are
		able to judge again.  
		
<?php		if($closed) { ?>
			<p>Registration is now closed for this year.
<?php		} else { ?>
			<p>If your plans change for this year, just indicate below that
			you are able to judge at the fair again, and finish the registration
			process.  If the registration deadline has passed, please contact
			our chief judge <?=mailto($config['email_chiefjudge'])?>.
<?php		} ?>
		
		<p>Thank you.	
<?php
	} else if($u['j_complete'] == 1) {
?>
		<h3>Registration Status: <font color="green">Complete</font></h3>

		Thank you for completing your registration.  We will send out an email 
		when judging teams have been created and project abstracts are ready 
		for judges to view.

		<h3>Judging Team and Schedule</h3>

		<p>Judging Team and Schedules are available here: <a data-ajax="false" href="judge_schedule.php">Judging Team and Schedules</a>
		<p>If the page is blank it means you haven't been assigned to a
		judging team, yet.  You <b>will</b> be assigned to a judging
		team at or before fair, we're just not sure which one yet.
		e.g., some judges cancel at the last minute, some judging teams
		need extra expertise in certain areas, and some unlisted
		special awards still need judges.

		<?php
	} else {
?>
		<h3>Registration Status: <font color="red">Incomplete</font></h3>
		
<?php		if($closed) { ?>
			<p>Registration is now closed.
<?php		} else { ?>
			<p>The red numbers in the menu on the left indicate which sections
			have missing data.  Registration closes on <b><?=date('F d, Y', strtotime($config['date_judge_registration_closes']))?></b>.
			You have until this day to complete your registration.
			After this date, the registration system closes and you
			will not be assigned to a judging team.
<?php		}
	}
?>
		

	<hr/>

	<h3>Cancelling</h3>
	If you are regrettably unable to judge at the fair this year, just flip
	the switch below to let us know.  This helps us organize judging teams
	and numbers.  You can always flip the switch back again.
<?php
	if($closed) { ?>
		<p>Registration is closed and judging teams have been assigned.
		If you indicate below that you're not attending, you will be
		removed from any judging team you were on.  If you later change
		your status back to attending, you may not be assigned to the
		same judging team.  Last minute judging changes are all done
		manually by the chief judge. 
<?php	} 
		
	$sel = array('1'=>'Yes, I\'ll be there', '0'=>'No, I can\'t make it');

	$form_id = 'j_attending_form';
	form_begin($form_id, 'judge_main.php');
	form_radio_h($form_id, 'j_attending', "Judging at the fair", $sel, $u['attending']);
	form_submit($form_id, 'save', 'Save', 'Information Saved');
	form_end($form_id);
?>

</div></div>

<?php
sfiab_page_end();
?>
