<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('awards.inc.php');

$mysqli = sfiab_init('student');

$u = user_load($mysqli);
$p = project_load($mysqli, $u['s_pid']);
$closed = sfiab_registration_is_closed($u);

$page_id = 's_awards';

sfiab_check_abort_in_preregistration($u, $page_id);

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

switch($action) {
case 'save':
	if($closed) exit();
	$awards = array();
	$vals = array();
	$error = '';
	$disable_remaining = false;
	if(array_key_exists('award', $_POST)) {
		foreach($_POST['award'] as $aid) {
			$aid = (int)$aid;
			if($disable_remaining) {
				/* If we encounter $aid==0, or the user seletes more than 4, 
				we take every other award and turn it off */
				$vals["award_$aid"] = 0;
			} else {
				/* Add it to the list to save */
				$awards[] = $aid;
			}
			if($aid == 0) {
				if(count($_POST['award']) > 1) {
					$error = 'You have selected not to self-nominate for any awards and also have selected some awards.  Your award selections have been removed.';
					$disable_remaining = true;
				}
			}
			if(count($awards) == 4) {
				if(count($_POST['award']) > 4) {
					$error = 'You have selected more than 4 awards.  Your selections beyond this limit have been removed.';
				}
				$disable_remaining = true;
			}
		}
	}
	$p['sa_nom'] = $awards;

	project_save($mysqli, $p);

	incomplete_check($mysqli, $ret, $u, $page_id, true);
	$response = array('status'=>0, 'missing'=>$ret);
	if($error != '') $response['error'] =  $error;
	if(count($vals) > 0) $response['val'] = $vals;

	form_ajax_response($response);
	exit();
}


$help = '
<p>There are a number of special awards that each project can self-nominate for.  You can nominate your project for up to 4 awards.
';

sfiab_page_begin($u, "Student Award Nomination", $page_id, $help);

?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	incomplete_check($mysqli, $fields, $u, $page_id);

	if($p['cat_id'] <= 0) {
		/* Category hasn't been selected yet */
?>
		<h3>Special Award Selection</h3>
		<p>Please select your project age category on the <a href="s_project.php" data-ajax="false">Project Info</a> page first.
		</div></div>
<?php
		sfiab_page_end();
		exit();
	}


	form_page_begin($page_id, $fields, '', '', 'This page is incomplete.  Please choose up to 4 awards or select the first option below to indicate you don\'t want to self-nominate for any awards');
	form_disable_message($page_id, $closed, $u['s_accepted']);

	$awards = award_load_special_for_project_select($mysqli, $p);

	$form_id = $page_id.'_form';
	form_begin($form_id, 's_awards.php', $closed);
?>
	<p>Please choose up to 4 awards for self-nomination.  If you don't wish to self-nominate for any awards, select the first option below.
<?php

	$id = $form_id.'_0';
?>
	<fieldset data-role="controlgroup">
		<?=form_checkbox($form_id, 'award', '<b>No Special Award Nominations</b><br/>Select this option if you do not wish to nominate for any special awards', 
				0, $p['sa_nom']); ?>
	</fieldset>

	<?=form_submit($form_id, 'save', 'Save', 'Award Selections Saved');?>
	<fieldset data-role="controlgroup">
<?php	foreach($awards as $aid=>$a) { 
		form_checkbox($form_id, 'award', "<b>{$a['name']}</b><br/>{$a['s_desc']}", 
				$aid, $p['sa_nom']); 
	}?>
	</fieldset>

<?php
	form_submit($form_id, 'save', 'Save', 'Award Selections Saved');
	form_end($form_id);
?>

</div></div>

<?php
sfiab_page_end();
?>
