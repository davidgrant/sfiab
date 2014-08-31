<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('form.inc.php');
require_once('incomplete.inc.php');
$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('volunteer'));

$page_id = "v_home";

/* Load the logged in user or the one being editted */
$u = user_load($mysqli);

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

switch($action) {
case 'save':
	$attending = NULL;
	post_bool($u['attending'], 'v_attending');
	user_save($mysqli, $u);
	if($u['attending']) {
		/* Do a full check, this will set the user
		 * status to complete or incomplete and save it */
		$fields = array();
		incomplete_check($mysqli, $fields, $u, false, true);
	}

	form_ajax_response(array('status'=>0, 'location'=>'v_main.php'));
	exit();
}


$help = '
<ul><li><b>Attending the Fair</b> - If you are unable to attend the fair,
indicate that here and we will remove you will not be assigned to any judging
team, or we will remove you from any teams you have been assigned to.  
</ul>';

sfiab_page_begin("Volunteer Main", $page_id, $help);
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 
<?php
	form_page_begin($page_id, array());
?>
	<h3>Hello <?=$u['firstname']?>,</h3>

	<p> Welcome to the new GVRSF registration system.  
	
	<p>Help for all pages is available by pressing the information icon <a href="#help_panel_v_home" data-role="button" data-icon="info" data-inline="true" data-iconpos="notext" class="ui-nodisc-icon ui-alt-icon"></a> on the top right of the page.

<?php
	if(!$u['attending']) {
?>
		<h3>Registration Status: <font color="blue">Not Attending</font></h3>

		<p>You have indicated that you're not able to volunteer at the
		fair this year, thanks for letting us know.  You won't be
		assigned to any duties and you don't need to fill out
		anything else.  
		
		<p>Your registration will still be here next year if you are
		able to volunteer again.  
		
		<p>If your plans change for this year, just indicate below that
		you are able to volunteer at the fair again, and finish the registration
		process.  If the registration deadline has passed, please contact
		our registration coordinator at registration@gvrsf.ca.
		
		<p>Thank you.	
<?php
	} else if($u['v_complete'] == 1) {
?>
		<h3>Registration Status: <font color="green">Complete</font></h3>

		Thank you for completing your registration.  We will send out an email 
		when volunteer assignments have been created.

<?php
	} else {
?>
		<h3>Registration Status: <font color="red">Incomplete</font></h3>
		
		The red numbers in the menu on the left indicate which sections
		have missing data.  Registration closes on April 5, 2014.  You
		have until this day to complete your registration.  After this
		date, the registration system closes and you will not be
		assigned to a volunteer position.

		Currently we are only accepting fair volunteers for help with 
		tours.  Please see the tour menu on the left.
<?php
	}
?>
		

	<hr/>
	<h3>Cancelling</h3>
	If you are regrettably unable to volunteer at the fair this year, just flip
	the switch below to let us know.  This helps us re-organize volunteers if 
	assignments have already been made.  You can always flip the switch back again.
	<?php

	/* This is backwards because it's "not attending" */
	$sel = array('1'=>'Yes, I\'ll be there', '0'=>'No, I can\'t make it');

	$form_id = 'v_attending_form';
	form_begin($form_id, 'v_main.php');	
	form_radio_h($form_id, 'v_attending', "Volunteering at the fair", $sel, $u['attending']);
	form_submit($form_id, 'save', 'Save', 'Information Saved');
	form_end($form_id);
?>

</div></div>

<?php
sfiab_page_end();
?>
