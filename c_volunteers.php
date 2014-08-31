<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('committee/volunteers.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('committee'));

$u = user_load($mysqli);

$page_id = 'c_volunteers';


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
	<p>Most links here don't work.  The Volutneer List / Editor only gives a list currently.
<?php
	form_page_begin($page_id, array());
	/* Count judges */
	$judges = volunteers_load_all($mysqli, $config['year']);

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


?>	<h3>Volunteers</h3> 
	<p>Complete: <b><?=$j_complete?></b> / <b><?=$j_complete+$j_incomplete?></b>,  plus not attending: <b><?=$j_not_attending?></b>.

	<ul data-role="listview" data-inset="true">
	<li><a href="c_volunteers_invite.php" data-rel="external" data-ajax="false">X Invite a Volunteer</a></li>
	<li><a href="c_user_list.php?roles[]=volunteer" data-rel="external" data-ajax="false">Volunteer List / Editor</a></li>
	</ul>



	<h3>Volunteer Assignments</h3> 
	<ul data-role="listview" data-inset="true">
	<li><a href="#" data-rel="external" data-ajax="false">X Edit Assignments</a></li>
	<li><a href="#" data-rel="external" data-ajax="false">X Run the Automatic Scheduler</a></li>
	</ul>

<?php
/*        $form_id = 'j_attending_form';
        form_begin($form_id, 'c_volunteers.php');
        form_text($form_id, 'j_not_attending', "Judging at the fair", $u['not_attending']);
        form_submit($form_id, 'save', 'Save', 'Information Saved');
        form_end($form_id);
*/	
?>

</div></div>
	

<?php
sfiab_page_end();
?>
