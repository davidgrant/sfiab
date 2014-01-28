<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('student'));

$u = user_load($mysqli);

$page_id = 's_emergency';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

switch($action) {
case 'save':
	$vals = array();
	for($i=1; $i<=2; $i++) {
		post_text($u["emerg{$i}_firstname"], "emerg{$i}_firstname");
		post_text($u["emerg{$i}_lastname"], "emerg{$i}_lastname");
		post_text($u["emerg{$i}_relation"], "emerg{$i}_relation");
		post_text($u["emerg{$i}_email"], "emerg{$i}_email");
		post_text($u["emerg{$i}_phone1"], "emerg{$i}_phone1");
		post_text($u["emerg{$i}_phone2"], "emerg{$i}_phone2");
		post_text($u["emerg{$i}_phone3"], "emerg{$i}_phone3");
		filter_phone($u["emerg{$i}_phone1"]);
		filter_phone($u["emerg{$i}_phone2"]);
		filter_phone($u["emerg{$i}_phone3"]);
		$vals["emerg{$i}_phone1"] = $u["emerg{$i}_phone1"];
		$vals["emerg{$i}_phone2"] = $u["emerg{$i}_phone2"];
		$vals["emerg{$i}_phone3"] = $u["emerg{$i}_phone3"];
	}
	user_save($mysqli, $u);

	incomplete_check($mysqli, $ret, $u, $page_id, true);
	form_ajax_response(array('status'=>0, 'missing'=>$ret, 'val'=>$vals));
	exit();
}


$help = '
<p>Please provide the name and phone number of at least one adult who can be contacted during the fair in case of emergency.
';

sfiab_page_begin("Student Emergency Contact", $page_id, $help);

?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	$form_id = $page_id.'_form';

	incomplete_check($mysqli, $fields, $u, $page_id);
	form_page_begin($page_id, $fields);

	$relations=array('parent'=>"Parent",'legalguardian'=>"Legal Guardian",'grandparent'=>"Grandparent",
			'familyfriend'=>"Family Friend", 'other'=>"Other");

	form_begin($form_id, 'student_emergency.php');

?>	<h3>Emergency Contact 1</h3>
<?php
	form_text($form_id, 'emerg1_firstname', "First Name", $u);
	form_text($form_id, 'emerg1_lastname', "Last Name", $u);
	form_select($form_id, 'emerg1_relation', "Relation", $relations, $u);
	form_text($form_id, 'emerg1_email', 'Email', $u, 'email');
	form_text($form_id, 'emerg1_phone1', 'Phone 1', $u, 'tel');
	form_text($form_id, 'emerg1_phone2', 'Phone 2', $u, 'tel');
	form_text($form_id, 'emerg1_phone3', 'Phone 3', $u, 'tel');
	form_submit($form_id, 'save', 'Save', 'Emergency Contacts Saved');

?>	<h3>Emergency Contact 2</h3>
<?php
	form_text($form_id, 'emerg2_firstname', "First Name", $u);
	form_text($form_id, 'emerg2_lastname', "Last Name", $u);
	form_select($form_id, 'emerg2_relation', "Relation", $relations, $u);
	form_text($form_id, 'emerg2_email', 'Email', $u, 'email');
	form_text($form_id, 'emerg2_phone1', 'Phone 1', $u, 'tel');
	form_text($form_id, 'emerg2_phone2', 'Phone 2', $u, 'tel');
	form_text($form_id, 'emerg2_phone3', 'Phone 3', $u, 'tel');
	form_submit($form_id, 'save', 'Save', 'Emergency Contacts Saved');
?>

</div></div>

<?php
sfiab_page_end();
?>
