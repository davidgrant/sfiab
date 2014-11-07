<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('committee/volunteers.inc.php');

$mysqli = sfiab_init('committee');

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

<?php	if(!$config['volunteers_enable']) { ?>
		<h2><font color=red>*** Note: Volunteer registration is disabled in the configuration. </font></h2>

<?php	} ?>
	<p>Working on the links in here.
<?php
	form_page_begin($page_id, array());
	/* Count judges */
	$judges = volunteers_load_all($mysqli, $config['year']);

	$v_complete = 0;
	$v_not_attending = 0;
	$v_incomplete = 0;
	foreach($judges as &$j) {
		if($j['attending'] == 0) {
			$v_not_attending++;
		} else {
			if($j['v_complete']) 
				$v_complete++;
			else
				$v_incomplete++;
		}
	}
?>

	<h3>Volunteers</h3> 
	<p>Complete: <b><?=$v_complete?></b> / <b><?=$v_complete+$v_incomplete?></b>,  plus not attending: <b><?=$v_not_attending?></b>.

	<ul data-role="listview" data-inset="true">
	<li><a href="index.php#register" data-rel="external" data-ajax="false">Invite a Volunteer</a></li>
	<li><a href="c_user_list.php?roles[]=volunteer" data-rel="external" data-ajax="false">Volunteer List / Editor</a></li>
	</ul>



	<h3>Volunteer Assignments</h3> 
	<ul data-role="listview" data-inset="true">
	<li><a href="#" data-rel="external" data-ajax="false">X Edit Assignments</a></li>
	<li><a href="#" data-rel="external" data-ajax="false">X Run the Automatic Scheduler</a></li>
	</ul>

</div></div>
	

<?php
sfiab_page_end();
?>
