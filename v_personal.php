<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');

$mysqli = sfiab_init('volunteer');

$u = user_load($mysqli);

$page_id = 'v_personal';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

switch($action) {
case 'save':
	post_text($u['salutation'], 'salutation');
	post_text($u['firstname'], 'firstname');
	post_text($u['lastname'], 'lastname');
	post_text($u['phone1'], 'phone1');
	post_text($u['phone2'], 'phone2');
	post_text($u['organization'], 'organization');
	post_text($u['sex'], 'sex');
	post_text($u['city'], 'city');
	post_text($u['province'], 'province');
	post_text($u['language'], 'language');
	post_text($u['v_relation'], 'v_relation');
	post_text($u['v_reason'], 'v_reason');

	$updates = array();
	filter_phone($u['phone1']);
	filter_phone($u['phone2']);
	$updates['phone1'] = $u['phone1'];
	$updates['phone2'] = $u['phone2'];

	user_save($mysqli, $u);

	incomplete_check($mysqli, $ret, $u, $page_id, true);
	form_ajax_response(array('status'=>0, 'missing'=>$ret, 'val'=>$updates));
	exit();
}

$help = '
<ul><li><b>Salutation</b> - Will appear before your name on your volunteer name badge.  Dr. for example.
<li><b>Language</b> - Preferred language of communication (the system is only in English right now, sorry.)
<li><b>I Am A</b> - If you are connected to a student at the fair, how? Parent/guardian/relative, teacher, friend, etc.
</ul>';

sfiab_page_begin("Volunteer Personal", $page_id, $help);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	$relation = array('parent'=>'Parent / Guardian / Relative of a student at the fair',
				'teacher'=>'Teacher / Educator',
				'unistudent'=>'University or post-secondary student',
				'other'=>'Other');


	incomplete_check($mysqli, $fields, $u, $page_id);
	form_page_begin($page_id, $fields);

	$form_id = $page_id."_form";
	form_begin($form_id, 'v_personal.php');

	form_text($form_id, 'salutation', "Salutation", $u);
	form_text($form_id, 'firstname', "First Name", $u);
	form_text($form_id, 'lastname', "Last Name", $u);
	form_radio_h($form_id, 'sex', 'Gender', array( 'male' => 'Male', 'female' => 'Female'), $u);
	form_text($form_id, 'phone1', "Primary Phone", $u, 'tel');
	form_text($form_id, 'phone2', "Secondary Phone", $u, 'tel');
	form_text($form_id, 'organization', "Organization", $u);
	form_text($form_id, 'city', 'City', $u['city']);
	form_province($form_id, 'province', 'Province / Territory', $u);
	form_lang($form_id, 'language', "Preferred Language", $u);
	form_select($form_id, 'v_relation', "I Am A", $relation, $u);
	form_textbox($form_id, 'v_reason', "Why do you want to volunteer?", $u);
	form_submit($form_id, 'save', 'Save', 'Information Saved');
	form_end($form_id);
?>


</div></div>
	




<?php
sfiab_page_end();
?>
