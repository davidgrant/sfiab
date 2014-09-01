<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('filter.inc.php');

$mysqli = sfiab_init('judge');

$u = user_load($mysqli);
$closed = sfiab_registration_is_closed($u);

$page_id = 'j_mentorship';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

switch($action) {
case 'save':
	if($closed) exit();
	post_bool($u['j_mentored'], 'j_mentored');
	user_save($mysqli, $u);

	incomplete_check($mysqli, $ret, $u, $page_id, true);
	form_ajax_response(array('status'=>0, 'missing'=>$ret));
	exit();
}

$help = '
<ul><li><b>Mentored</b> - Select \'Yes\' if you have acted as a mentor or in an advisory role for any project at the fair.
</ul>';

sfiab_page_begin("Mentorship", $page_id, $help);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	incomplete_check($mysqli, $fields, $u, $page_id);
	form_page_begin($page_id, $fields);
	form_disable_message($page_id, $closed);

?>
	<h3>Mentorship</h3>
<?php
	$form_id = $page_id.'_form';
	form_begin($form_id, 'judge_mentorship.php', $closed);
	form_yesno($form_id, 'j_mentored', "Have you mentored or acted in an advisory role for any project at the fair?", $u, true);
	form_submit($form_id, 'save', 'Save', 'Information Saved');
	form_end($form_id);
?>

</div></div>
	




<?php
sfiab_page_end();
?>
