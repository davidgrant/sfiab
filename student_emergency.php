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

$fields = array('emerg1_firstname','emerg1_lastname','emerg1_relation','emerg1_email','emerg1_phone1','emerg1_phone2','emerg1_phone3',
		'emerg2_firstname','emerg2_lastname','emerg2_relation','emerg2_email','emerg2_phone1','emerg2_phone2','emerg2_phone3');

switch($action) {
case 'save':
	foreach($fields as $f) {
		if(!array_key_exists($f, $u)) {
			/* Key doesn't exist, user is injecting their own keys? */
			print("Error 1005: $f");
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
	print(json_encode($ret));
	exit();
}



sfiab_page_begin("Student Emergency Contact", $page_id);

?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	$fields = incomplete_fields($mysqli, $page_id, $u);
	form_incomplete_error_message($page_id, $fields);
?>
	<form action="#" id="<?=$page_id?>_form">
<?php
		$relations=array('parent'=>"Parent",'legalguardian'=>"Legal Guardian",'grandparent'=>"Grandparent",
				'familyfriend'=>"Family Friend", 'other'=>"Other");

?>		<h3>Contact 1</h3>
<?php
		form_text($page_id, 'emerg1_firstname', "First Name", $u);
		form_text($page_id, 'emerg1_lastname', "Last Name", $u);
		form_select($page_id, 'emerg1_relation', "Relation", $relations, $u);
		form_text($page_id, 'emerg1_email', 'Email', $u, 'email');
		form_text($page_id, 'emerg1_phone1', 'Phone 1', $u, 'tel');
		form_text($page_id, 'emerg1_phone2', 'Phone 2', $u, 'tel');
		form_text($page_id, 'emerg1_phone3', 'Phone 3', $u, 'tel');

?>		<h3>Contact 2</h3>
<?php
		form_text($page_id, 'emerg2_firstname', "First Name", $u);
		form_text($page_id, 'emerg2_lastname', "Last Name", $u);
		form_select($page_id, 'emerg2_relation', "Relation", $relations, $u);
		form_text($page_id, 'emerg2_email', 'Email', $u, 'email');
		form_text($page_id, 'emerg2_phone1', 'Phone 1', $u, 'tel');
		form_text($page_id, 'emerg2_phone2', 'Phone 2', $u, 'tel');
		form_text($page_id, 'emerg2_phone3', 'Phone 3', $u, 'tel');

		form_submit($page_id, 'Save');
?>
		<input type="hidden" name="action" value="save"/>
	</form>
	<script>
<?php 	foreach($fields as $f) { ?>
			$("label[for='<?=$page_id?>_<?=$f?>']").addClass('error');
<?php		}?>
	</script>

	<?=form_scripts('student_emergency.php', $page_id, $fields);?>


</div>
	




<?php
sfiab_page_end();
?>
