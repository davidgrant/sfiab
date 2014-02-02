<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('committee/judges.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('committee'));

$u = user_load($mysqli);

$page_id = 'c_judging';


$saved = false;
if(array_key_exists('action', $_POST)) {

	if($_POST['action'] == 'save') {
		form_ajax_response_error(0, 'save response');
		exit();
	}

	if($_POST['action'] == 'save2') {
		$saved = true;
	}

}

sfiab_page_begin("Judging", $page_id);

?>



<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	form_page_begin($page_id, array());
	/* Count judges */
	$judges = judges_load_all($mysqli, $config['year']);

	$j_complete = 0;
	$j_not_attending = 0;
	$j_incomplete = 0;
	foreach($judges as &$j) {
		if($j['not_attending']) {
			$j_not_attending++;
		} else {
			if($j['j_complete']) 
				$j_complete++;
			else
				$j_incomplete++;
		}
	}


?>	<h3>Judges</h3> 
	<p>Complete: <b><?=$j_complete?></b> / <b><?=$j_complete+$j_incomplete?></b>,  plus not attending: <b><?=$j_not_attending?></b>.

	<ul data-role="listview" data-inset="true">
	<li><a href="c_judging_invite.php" data-rel="external" data-ajax="false">X Invite a Judge</a></li>
	<li><a href="c_judging_list.php" data-rel="external" data-ajax="false">Judge List / Editor</a></li>
	</ul>



	<h3>Judging Assignments</h3> 
	<p>FIXME: how many juding teams are there?
	<ul data-role="listview" data-inset="true">
	<li><a href="#" data-rel="external" data-ajax="false">X Edit Judging Timeslots</a></li>
	<li><a href="#" data-rel="external" data-ajax="false">X Run the Judge Scheduler</a></li>
	</ul>

	<h3>Judging Assignments</h3> 
	<p>FIXME: how many assignments are there, or has the scheduler been run?
	<ul data-role="listview" data-inset="true">
	<li><a href="#" data-rel="external" data-ajax="false">X Edit Judging Teams</a></li>
	<li><a href="#" data-rel="external" data-ajax="false">X Edit Judging Team -- Project Assignments</a></li>
	</ul>

<?php
/*        $form_id = 'j_attending_form';
        form_begin($form_id, 'c_judging.php');
        form_text($form_id, 'j_not_attending', "Judging at the fair", $u['not_attending']);
        form_submit($form_id, 'save', 'Save', 'Information Saved');
        form_end($form_id);
*/	
?>

</div></div>
	

<?php
sfiab_page_end();
?>
