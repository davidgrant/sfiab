<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');

$mysqli = sfiab_init('student');

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

	$vals = array();

	for($i=1; $i<=2; $i++) {
		$id = (int)$_POST["emerg{$i}_id"];
		if($id == 0) {
			/* Need a new one, insert and loadback */
			$mysqli->query("INSERT INTO emergency_contacts(`uid`) VALUES ('{$u['uid']}')");
			$id = $mysqli->insert_id;
			$ecs[$id] = emergency_contact_load($mysqli, $id);
			$vals["emerg{$i}_id"] = $id;
		}

		post_text($ecs[$id]["firstname"], "emerg{$i}_firstname");
		post_text($ecs[$id]["lastname"], "emerg{$i}_lastname");
		post_text($ecs[$id]["relation"], "emerg{$i}_relation");
		post_text($ecs[$id]["email"], "emerg{$i}_email");
		post_text($ecs[$id]["phone1"], "emerg{$i}_phone1");
		post_text($ecs[$id]["phone2"], "emerg{$i}_phone2");
		post_text($ecs[$id]["phone3"], "emerg{$i}_phone3");
		filter_phone($ecs[$id]["phone1"]);
		filter_phone($ecs[$id]["phone2"]);
		filter_phone($ecs[$id]["phone3"]);

		emergency_contact_save($mysqli, $ecs[$id]);

		$vals["emerg{$i}_phone1"] = $ecs[$id]["phone1"];
		$vals["emerg{$i}_phone2"] = $ecs[$id]["phone2"];
		$vals["emerg{$i}_phone3"] = $ecs[$id]["phone3"];
	}

	incomplete_check($mysqli, $ret, $u, $page_id, true);
	form_ajax_response(array('status'=>0, 'missing'=>$ret, 'val'=>$vals));
	exit();
}


$help = '
<p>Please provide the name and phone number of at least one adult who can be contacted during the fair in case of emergency.
';

sfiab_page_begin($u, "Student Emergency Contact", $page_id, $help);

?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	$form_id = $page_id.'_form';

	incomplete_check($mysqli, $fields, $u, $page_id);
	form_page_begin($page_id, $fields);
	form_disable_message($page_id, $closed, $u['s_accepted']);

	$relations=array('parent'=>"Parent",'legalguardian'=>"Legal Guardian",'grandparent'=>"Grandparent",
			'familyfriend'=>"Family Friend", 'other'=>"Other");

	form_begin($form_id, 's_emergency.php', $closed);

	for($x=count($ecs); $x < 2; $x++) {
		$ec = array('id' => 0);
		$ecs[-$x] = $ec;
	}

	$x = 0;
	foreach($ecs as $id=>&$ec) {
		$x += 1;
?>		<h3>Emergency Contact <?=$x?></h3>
<?php
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
