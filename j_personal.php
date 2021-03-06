<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');

$mysqli = sfiab_init('judge');

$u = user_load($mysqli);
$closed = sfiab_registration_is_closed($u);

$page_id = 'j_personal';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

switch($action) {
case 'save':
	if($closed) exit();
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
	post_text($u['j_psd'], 'j_psd');
	post_text($u['j_heard_about'], 'j_heard_about');

	$updates = array();
	filter_phone($u['phone1']);
	filter_phone($u['phone2']);
	$updates['phone1'] = $u['phone1'];
	$updates['phone2'] = $u['phone2'];

	if($u['salutation'] == 'dr' || $u['salutation'] == 'Dr') {
		$u['salutation'] = 'Dr.';
		$updates['salutation'] = $u['salutation'];
	}

	user_save($mysqli, $u);

	incomplete_check($mysqli, $ret, $u, $page_id, true);
	form_ajax_response(array('status'=>0, 'missing'=>$ret, 'val'=>$updates));
	exit();
}

$help = '
<ul><li><b>Salutation</b> - Will appear before your name on your judge name badge.  Dr. for example.
<li><b>Language</b> - Preferred language of communication (the system is only in English right now, sorry.)
<li><b>Highest Post-Secondary Degree</b> - PhD, MSc, BASc, etc.
</ul>';

sfiab_page_begin($u, "Judge Personal", $page_id, $help);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	$degrees = array('none'=>'None', 'bachelor'=>'Bachelor', 'master'=>'Master', 'doctorate'=>'Doctorate' );

	incomplete_check($mysqli, $fields, $u, $page_id);
	form_page_begin($page_id, $fields);
	form_disable_message($page_id, $closed);

	$form_id = $page_id."_form";
	form_begin($form_id, 'j_personal.php', $closed);

	form_text($form_id, 'salutation', "Salutation (This goes on your name tag!)", $u);
	form_text($form_id, 'firstname', "First Name", $u);
	form_text($form_id, 'lastname', "Last Name", $u);
	form_radio_h($form_id, 'sex', 'Gender', array( 'male' => 'Male', 'female' => 'Female'), $u);
	form_text($form_id, 'phone1', "Primary Phone", $u, 'tel');
	form_text($form_id, 'phone2', "Secondary Phone", $u, 'tel');
	form_text($form_id, 'organization', "Organization (This goes on your name tag!)", $u);
	form_text($form_id, 'city', 'City', $u['city']);
	form_province($form_id, 'province', 'Province / Territory', $u);
	form_lang($form_id, 'language', "Preferred Language", $u);
	form_select($form_id, 'j_psd', "Highest Post-Secondary Degree (or equivalent)", $degrees, $u);
	form_text($form_id, 'j_heard_about', "How did you hear about becoming a judge at the fair?", $u);
	form_submit($form_id, 'save', 'Save', 'Information Saved');
	form_end($form_id);
?>


</div></div>
	




<?php
sfiab_page_end();
?>
