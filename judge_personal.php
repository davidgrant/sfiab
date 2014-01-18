<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('judge'));

$u = user_load($mysqli);

$page_id = 'j_personal';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

$fields = array('salutation', 'firstname', 'lastname', 'phone1','phone2',
		'organization', 'sex', 'city', 'province', 
		'language', 'j_psd');


switch($action) {
case 'save':
	foreach($fields as $f) {
		if(!array_key_exists($f, $u)) {
			/* Key doesn't exist, user is injecting their own keys? */
			print("Error 1100: $f");
			exit();
		}
		/* Since 'sex' is a radiobutton, it's only included if there's a checked value */
		if(array_key_exists($f, $_POST)) {
			/* Save it to the user */
			$u[$f] = $_POST[$f];
		} 
	}

	user_save($mysqli, $u);

	$ret = incomplete_fields($mysqli, $page_id, $u, true);
	print(form_ajax_response(0, $ret));
	exit();
}



sfiab_page_begin("Judge Personal", $page_id);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	$fields = incomplete_fields($mysqli, $page_id, $u);

	$form_id = $page_id."_form";
	form_begin($form_id, 'judge_personal.php', $fields);

	form_text($form_id, 'salutation', "Salutation", $u);
	form_text($form_id, 'firstname', "First Name", $u);
	form_text($form_id, 'lastname', "Last Name", $u);
	form_radio_h($form_id, 'sex', 'Gender', array( 'male' => 'Male', 'female' => 'Female'), $u);
	form_text($form_id, 'phone1', "Primary Phone", $u, 'tel');
	form_text($form_id, 'phone2', "Secondary Phone", $u, 'tel');
	form_text($form_id, 'organization', "Organization", $u);
	form_text($form_id, 'city', 'City', $u['city']);
	form_province($form_id, 'province', 'Province', $u);
	form_lang($form_id, 'language', "Preferred Language", $u);
	form_text($form_id, 'j_psd', "Highest Post-Secondary Degree", $u);
	form_submit($form_id, 'save', 'Save', 'Information Saved');
	form_end($form_id);
?>


</div>
	




<?php
sfiab_page_end();
?>
