<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('filter.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('judge'));

$u = user_load($mysqli);

$page_id = 'j_mentorship';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

switch($action) {
case 'save':
	post_bool($u['j_mentored'], 'j_mentored');
	user_save($mysqli, $u);

	$ret = incomplete_fields($mysqli, $page_id, $u, true);
	print(form_ajax_response(0, $ret));
	exit();
}

$help = '
<ul><li><b>Mentored</b> - Select \'Yes\' if you have acted as a mentor or in an advisory role for any project at the fair.
</ul>';

sfiab_page_begin("Mentorship", $page_id, $help);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	$fields = incomplete_fields($mysqli, $page_id, $u);
	$form_id = $page_id.'_form';
?>
	<h3>Mentorship</h3>
<?php
	form_begin($form_id, 'judge_mentorship.php', $fields);
	form_yesno($form_id, 'j_mentored', "Have you mentored or acted in an advisory role for any project at the fair?", $u, true);
	form_action($form_id, '');
	form_submit($form_id, 'save', 'Save', 'Information Saved');
	form_end($form_id);
?>

</div></div>
	




<?php
sfiab_page_end();
?>
