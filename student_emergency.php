<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('student'));

$u = user_load($mysqli);
$closed = sfiab_registration_is_closed($u);

$page_id = 's_emergency';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

$ecs = emergency_contact_load_for_user($mysqli, $u);

switch($action) {
case 'save':
	if($closed) exit();

	print("Saving is broken on this page :(");

	$vals = array();
	for($i=1; $i<=2; $i++) {
	/*
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
		*/
	}
//
//	user_save($mysqli, $u);

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
	form_disable_message($page_id, $closed, $u['s_accepted']);

	$relations=array('parent'=>"Parent",'legalguardian'=>"Legal Guardian",'grandparent'=>"Grandparent",
			'familyfriend'=>"Family Friend", 'other'=>"Other");

	form_begin($form_id, 'student_emergency.php', $closed);

	$x = 0;
	foreach($ecs as $id=>&$ec) {
		$x += 1;
?>		<h3>Emergency Contact <?=$x?></h3>
<?php
		print_r($ec);
		form_hidden($form_id, "emerg{$x}_id", $ec['id']);
		form_text($form_id, "emerg{$x}_firstname", "First Name", $ec['firstname']);
		form_text($form_id, "emerg{$x}_lastname", "Last Name", $ec['lastname']);
		form_select($form_id, "emerg{$x}_relation", "Relation", $relations, $ec['relation']);
		form_text($form_id, "emerg{$x}_email", 'Email', $ec['email'], 'email');
		form_text($form_id, "emerg{$x}_phone1", 'Phone 1', $ec['phone1'], 'tel');
		form_text($form_id, "emerg{$x}_phone2", 'Phone 2', $ec['phone2'], 'tel');
		form_text($form_id, "emerg{$x}_phone3", 'Phone 3', $ec['phone3'], 'tel');
		form_submit($form_id, 'save', 'Save', 'Emergency Contacts Saved');
	}
?>

</div></div>

<?php
sfiab_page_end();
?>
