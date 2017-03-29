<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('committee/judges.inc.php');
require_once('awards.inc.php');
require_once('timeslots.inc.php');

$mysqli = sfiab_init('committee');

$u = user_load($mysqli);

$page_id = 'c_jteam_edit_jteam';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

$jteam_id = (int)$_GET['id'];
$jteam = jteam_load($mysqli, $jteam_id);
if($jteam == NULL) exit();

$awards = award_load_all($mysqli);
$rounds = timeslots_load_rounds($mysqli);

switch($action) {
case 'save':
	
	post_int($jteam['num'], 'num');
	post_text($jteam['name'], 'name');
	post_int($jteam['round'], 'round');
	post_int($jteam['award_id'], 'award_id');

	jteam_save($mysqli, $jteam);
	form_ajax_response(array('status'=>0, 'location'=>'c_jteam_edit.php'));
	exit();
}

$help = '
<ul><li>
</ul>';

sfiab_page_begin($u, "Edit Jteam", $page_id, $help);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	form_page_begin($page_id, array());

	$form_id = $page_id."_form";
	form_begin($form_id, "c_jteam_edit_jteam.php?id=$jteam_id");
	form_text($form_id, 'num', "Team Number", $jteam);
	form_text($form_id, 'name', "Team Name", $jteam);
	form_select($form_id, 'round', "Round", $rounds, $jteam);
	form_select($form_id, 'award_id', "Award", $awards, $jteam);
	form_submit($form_id, 'save', 'Save', 'Information Saved');
	form_end($form_id);
?>


</div></div>
	




<?php
sfiab_page_end();
?>
