<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('project.inc.php');
require_once('isef.inc.php');

$mysqli = sfiab_init('student');

$u = user_load($mysqli);
$closed = sfiab_registration_is_closed($u);

if($u['s_pid'] == NULL || $u['s_pid'] == 0) {
	print("Error 1010: no project.\n");
	exit();
}

$p = project_load($mysqli, $u['s_pid']);

$page_id = 's_cwsf';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}


switch($action) {
case 'save':
	if($closed) exit();
	post_text($p['cwsf_rsf_has_competed'], 'cwsf_rsf_has_competed');
	post_text($p['cwsf_rsf_will_compete'], 'cwsf_rsf_will_compete');
	project_save($mysqli, $p);

	incomplete_check($mysqli, $ret, $u, $page_id, true);
	$response = array('status'=>0, 'missing'=>$ret);
	if(count($incomplete_errors) > 0) 
		$response['error'] = join('<br/>', $incomplete_errors);

	form_ajax_response($response);
	exit();
}

$help = "
<ul><li>Have you already completed, or do you plan to compete in any other regional fair that selects projects for the upcoming Canada-Wide Science Fair
</ul>
";

sfiab_page_begin($u, "CWSF Eligibilty", $page_id, $help);

?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	incomplete_check($mysqli, $fields, $u, $page_id, true);
	$e = join('<br/>', $incomplete_errors);
	form_page_begin($page_id, $fields, $e);
	form_disable_message($page_id, $closed, $u['s_accepted']);

?>
	<h3>Canada-Wide Science Fair Eligibility</h3>
	<p>A project's eligibility for the Canada-Wide Science Fair is determined by the first regional science fair in which it is entered.
<?php	
	$form_id = $page_id.'_form';
	form_begin($form_id, 's_cwsf.php', $closed);
	form_yesno($form_id, 'cwsf_rsf_has_competed', "Have you already competed in any other regional fair that selects projects for the Canada-Wide Science Fair?", $p, true);
	form_yesno($form_id, 'cwsf_rsf_will_compete', "Are you planning to complete in any other regional fair that selects projects for the Canada-Wide Science Fair?", $p, true);
	form_submit($form_id, 'save', 'Save', 'Information Saved');
	form_end($form_id);
?>

</div></div>
	

<?php
sfiab_page_end();
?>
