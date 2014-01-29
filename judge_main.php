<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('form.inc.php');
require_once('incomplete.inc.php');
$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('judge'));

$page_id = "j_home";

$u = user_load($mysqli);

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

switch($action) {
case 'save':
	$attending = NULL;
	post_bool($attending, 'j_attending');
	if($attending !== NULL) {
		if($attending == 0) {
			$u['j_status'] = 'notattending';
			user_save($mysqli, $u);
		} else {
			/* Do  a full check, this will set the user
			 * status to complete or incomplete and save it */
			$fields = array();
			incomplete_check($mysqli, $fields, $u, false, true);
		}
	}

	form_ajax_response(array('status'=>0, 'location'=>'judge_main.php'));
	exit();
}


$help = '
<ul><li><b>Attending the Fair</b> - If you are unable to attend the fair,
indicate that here and we will remove you will not be assigned to any judging
team, or we will remove you from any teams you have been assigned to.  
</ul>';

sfiab_page_begin("Judge Main", $page_id, $help);
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 
<?php
	form_page_begin($page_id, array());
?>
	<h3>Hello <?=$u['firstname']?>,</h3>

	<p> Welcome to the new GVRSF registration system.  
	
	<p>Help for most pages is available by clicking the information icon on the top right of the page.

<?php
	if($u['j_status'] == 'complete') {
?>
		<h3>Registration Status: <font color="green">Complete</font></h3>

		Thank you for completing your registration.  We will send out an email 
		when judging teams have been created and project abstracts are ready 
		for judges to view.

		<h3>Judging Team and Schedule</h3>
		Judging teams have not been assigned yet.  Look for this information
		approximately one week before the fair.  

<?php
	} else if($u['j_status'] == 'notattending') {
?>
		<h3>Registration Status: <font color="blue">Not Attending</font></h3>

		<p>You have indicated that you're not able to judge at the
		fair this year, thanks for letting us know.  You won't be
		assigned to judge anything and you don't need to fill out
		anything else.  
		
		<p>Your registration will still be here next year if you are
		able to judge again.  
		
		<p>If your plans change for this year, just indicate below that
		you are able to judge at the fair again, and finish the registration
		process.  If the registration deadline has passed, please contact
		the chief judges (leonard@gvrsf.ca or ceddy@gvrsf.ca)
		
		<p>Thank you.
<?php
	} else {
?>
		<h3>Registration Status: <font color="red">Incomplete</font></h3>
		
		The red numbers in the menu on the left indicate which sections
		have missing data.  Registration closes on March 30, 2014.  You
		have until this day to complete your registration.  After this
		date, the registration system closes and you will not be
		assigned to a judging team.
<?php
	}
?>
		

	<hr/>
	<h3>Cancelling</h3>
	If you are regrettably unable to judge at the fair this year, just flip
	the switch below to let us know.  This helps us re-organize judging teams if 
	judging assignments have already been made.  You can always flip the switch back again.
	<?php

	$attending = ($u['j_status'] == 'notattending') ? 0 : 1;

	$sel = array('1'=>'Yes, I\'ll be there', '0'=>'No, I can\'t make it');

	$form_id = 'j_attending_form';
	form_begin($form_id, 'judge_main.php');	
	form_radio_h($form_id, 'j_attending', "Judging at the fair", $sel, $attending);
	form_submit($form_id, 'save', 'Save', 'Information Saved');
	form_end($form_id);
?>

</div></div>

<?php
sfiab_page_end();
?>
