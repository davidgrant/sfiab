<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('debug.inc.php');
require_once('project_number.inc.php');
require_once('form.inc.php');


$mysqli = sfiab_init('committee');

$u = user_load($mysqli);

$roles = array();

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}
switch($action) {
case 'c':
	$pid = (int)$_POST['pid'];
}



$page_id = 'c_assign_project_numbers';
$help = '<p>';


sfiab_page_begin("Assign Project Numbers", $page_id, $help);

$projects = projects_load_all($mysqli, true);

?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

	<h3>Assign Project Numbers</h3>

<?php
	switch($action) {
	case 'go':
		foreach($projects as $pid=>&$p) {
			if($p['number'] == '' or $p['number'] === NULL) {
				project_number_assign($mysqli, $p);
				project_save($mysqli, $p);
				print("{$p['number']} - {$p['title']}<br/>");
			}
		} ?>

		<a href="c_assign_project_numbers.php" data-ajax="false" data-role="button" data-icon="back" data-theme="g" >All Done.  Go Back</a>
		<?php

		break;
	default:
		/* Count completed projects without project numbers */
		$accepted_projects_with_no_number = 0;
		foreach($projects as $pid=>&$p) {
			if($p['number'] == '' or $p['number'] === NULL) {
				$accepted_projects_with_no_number += 1;
			}
		}
?>
		<p>There are <b><?=$accepted_projects_with_no_number?> /
		<?=count($projects)?></b> accepted projects with no number.  
<?php
		if($accepted_projects_with_no_number == 0) { ?>
			<p>There are no projects that need a number.  Use the <a href="c_input_signature_forms.php" data-ajax="false">Input Signature Forms</a> page to accept projects.
<?php		} else { ?>
			<p>Use the button below to assign a project number to these
			<?=$accepted_projects_with_no_number?> projects.  This will not
			change any project numbers already assigned.
<?php
			$form_id = $page_id.'_form';
			form_page_begin($page_id, array());
			form_begin($form_id, 'c_assign_project_numbers.php', false, false);
			form_button($form_id, 'go', "Assign $accepted_projects_with_no_number Project Numbers", 'g', 'check' );
			form_end($form_id);	
		}
	} ?>

</div></div>
<?php

sfiab_page_end();

?>
