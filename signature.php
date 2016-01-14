<?php

require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('schools.inc.php');

$mysqli = sfiab_init(NULL);
sfiab_load_config($mysqli);

function load_and_check_key($mysqli, $key) 
{
	global $signature_types;

	$sig = signature_load($mysqli, $key);
	
	if($sig['uid'] == 0) exit();
	if(!array_key_exists($sig['type'], $signature_types)) exit();

	return $sig;
}


$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

switch($action) {
case 'sign':
	$key = '';
	post_text($key, 'key');
	$sig = load_and_check_key($mysqli, $key);

	/* Refuse to save if it has already been saved */
	if($sig['date_signed'] != '0000-00-00 00:00:00') {
		print("Already signed.");
		exit();
	}

	post_text($sig['signed_name'], 'name');
	$sig['date_signed'] = date( 'Y-m-d H:i:s' );

	/* Check that all necessary text boxes are checked, the .js below doesn't let the form
	 * get subbmit unless they all are, so just double check that.*/
	if(!in_array('agree', $_POST['agree_decl'])) { print("Checkbox mismatch"); exit(); };
	if(!in_array('agree', $_POST['agree_sig'])) { print("Checkbox mismatch"); exit(); };
	if($config['sig_enable_release_of_information'] && ($sig['type'] == 'student' || $sig['type'] == 'parent')) { 
		if(!in_array('agree', $_POST['agree_rel'])) { print("Checkbox mismatch"); exit() ;};
	}
	if(strlen($sig['signed_name']) < 2) { print("bad name"); exit(); };

	signature_save($mysqli, $sig);
	form_ajax_response(array('status'=>0, 'location'=>'signature.php?k='.$sig['key']));
	exit();
}

/* key could have + signs in it, the URL parser turns those into spaces.  Turn them back into +s */
$key = str_replace(' ', '+', $_GET['k']);

$sample = false;
if($key == 'sample_student') {
	$sample = true;
	$sample_type = 'student';
	$sample_name = 'John Q. Doe';
	$sample_email = 'john@exmaple.com';
} else if($key == 'sample_parent') {
	$sample = true;
	$sample_type = 'parent';
	$sample_name = 'Jane Doe';
	$sample_email = 'jdoe@industry.com';
} else if($key == 'sample_teacher') {	
	$sample = true;
	$sample_type = 'teacher';
	$sample_name = 'Miss Krabapple';
	$sample_email = 'msk@exampleschool.com';
}

if(!$sample) {
	$sig = load_and_check_key($mysqli, $key);
	/* Load the student and project */
	$student = user_load($mysqli, $sig['uid']);
	$project = project_load($mysqli, $student['s_pid']);
	$school = school_load($mysqli, $student['schools_id']);
} else {
	$cats = categories_load($mysqli);

	$sig = array();
	$sig['date_signed'] = '0000-00-00 00:00:00';
	$sig['uid'] = -1;
	$sig['type'] = $sample_type;
	$sig['name'] = $sample_name;
	$sig['email'] = $sample_email;
	$sig['signed_name'] = '';
	$sig['year'] = $config['year'];

	$project = array();
	$project['pid'] = 1234;
	$project['title'] = "My Science Fair Project";
	$project['cat_id'] = 1;
	$project['challenge_id'] = 1;

	$student = array();
	$student['schools_id'] = 0;
	$student['uid'] = 1111;
	$student['grade'] = $cats[1]['min_grade'];
	$student['name'] = "John Q. Doe";
	$student['firstname'] = "John";
	$student['lastname'] = "Doe";
	$student['sex'] = "male";
	$student['email'] = "john@example.com";
	$student['username'] = 'sample';
	$student['salutation'] = '';
	$student['organization'] = '';

	$school = array();
	$school['school'] =  'Example Secondard School';
}
			


$str = '';
$already = '';
$disable = false;
if($sig['date_signed'] != '0000-00-00 00:00:00') {
	$str = 'agree';
	$already = "<font color=\"green\"> This form was signed on ". date('F j, g:ia', strtotime($sig['date_signed']))."</font>";
	$disable = true;
}

output_start("Electronic Signature");


$page_id = 'signature';
?>


<div data-role="page" id="<?=$page_id?>" class="sfiab_page" > 
<?php

	switch($sig['type']) {
	case 'student':
		$decl = cms_get($mysqli, 'sig_student_declaration', $student);
		break;		
	case 'parent':
		$decl = cms_get($mysqli, 'sig_parent_declaration', $student);
		break;		
	case 'teacher':
		$decl = cms_get($mysqli, 'sig_teacher_declaration', $student);
		break;		
	case 'ethics':
		$decl = cms_get($mysqli, 'sig_ethics_declaration', $student);
		break;		
	default:
		exit();
	}

$flags = ENT_QUOTES;
if(PHP_VERSION_ID >= 50400) $flags |= ENT_HTML401;

$logo = '';
?>

<h2><?=$signature_types[$sig['type']]?> Electronic Signature Form</h2>

<?php
if($already != '') {
	print("<h3>$already</h3>");
}

if($sig['type'] == 'student') { ?>
	<p>This form is for the <b>Exhibitor</b> signature for <?=$sig['name']?>.  If you are not <?=$sig['name']?> then this is not the signature page for you.  Please contact the science fair committee <?=mailto($config['email_registration'])?>.
<?php } else { ?>
	<p>A <b><?=$signature_types[$sig['type']]?> </b>signature has been requested from you, <?=$sig['name']?>, by <?=$student['name']?>. If you are not <?=$sig['name']?>, then this is not the signature page for you. Please contact the science fair committee <?=mailto($config['email_registration'])?>.
<?php } ?>

<p>Please review the Project Information and Declaration(s) below.  If you agree
to the declaration(s), type in your name in the box below in lieu of a
signature and submit this form.  This will function as your electronic
signature and you do not need to sign the paper signature form.

<p>If you have any questions or concerns, please contact us at <?=mailto($config['email_registration'])?>.

<?php if($sig['type'] == 'student') { ?>
	<p>If you do not agree to use an electronic signature, please print the paper signature form and submit that.
<?php } else { ?>
	<p>If you do not agree to use an electronic signature, please request a paper copy of the signature form from the student and sign that.
<?php } ?>


<h3>Project Information</h3>

<table data-role="table" data-mode="none" class="table_stripes">
<TR><td>Student: </td><td><?=htmlentities($student['name'], $flags , "UTF-8")?></td></tr>
<TR><td>Project Title: </td><td><?=htmlentities($project['title'], $flags , "UTF-8")?></td></tr>
<TR><td>School: </td><td><?=htmlentities($school['school'], $flags , "UTF-8")?></td></tr>
</table>
<hr/>

<?php
$form_id = $page_id.'_form';
form_page_begin($page_id, array());
form_begin($form_id, 'signature.php', $disable);
form_hidden($form_id, 'key', $key);

?>

<h3><?=$signature_types[$sig['type']]?> Declaration</h3>
<blockquote>
<?=nl2br($decl)?>
</blockquote>
<?=form_checkbox($form_id, 'agree_decl', "I Agree to the {$signature_types[$sig['type']]} Declaration above", 'agree', $str)?>
<hr/>

<?php
if($config['sig_enable_release_of_information'] && ($sig['type'] == 'student' || $sig['type'] == 'parent')) {

	if($sig['type'] == 'student') {
		$rel_of_info = cms_get($mysqli, 'sig_release_of_information_student', $student);
	} else {
		$rel_of_info = cms_get($mysqli, 'sig_release_of_information_parent', $student);
	}
?>
	<h3><?=$signature_types[$sig['type']]?> Release of Information</h3>
	<blockquote>
	<?=nl2br($rel_of_info)?>
	</blockquote>
<?php
	form_checkbox($form_id, 'agree_rel', "I Agree to the {$signature_types[$sig['type']]} Release of Information above", 'agree', $str);	
?>
	<hr/>
<?php

}  ?>



<h3><?=$signature_types[$sig['type']]?> Signature</h3>
<blockquote>
<?php if($sig['type'] == 'student') { ?>
	<p>If you do not agree to the use of an electronic signature, please print a paper copy of this form to sign and submit it 
to the Science Fair Committee.
<?php } else { ?>
	<p>If you do not agree to the use of an electronic signature, please ask the student to print a paper copy of this form to sign and have the student submit that 
to the Science Fair Committee.
<?php } ?>
</blockquote>

<?php
	form_checkbox($form_id, 'agree_sig', "I Agree to the use of an Electronic Signature instead of a Paper/Ink Signature", 'agree', $str);
	form_text($form_id, 'name', 'Type Your Name', $sig['signed_name']);
	if($already == '' && $sample == false) {
		form_submit($form_id, 'sign', "Send Electronic Signature", 'Sent');
	}
	if($sample == true) {
?>		<a href="#" data-rel="back" data-role="button" data-icon="check" data-inline="true" data-theme="g">Send Electronic Signature</a> <?php
	}
	form_end($form_id);
	if($already != '') {
		print("<h3>$already</h3>");
	}
?>
</div>

<script>
	function <?=$form_id?>_button_enable() {
		/* Check that all checkboxes are checked */
		if($('#<?=$form_id?>_agree_decl_agree').is(':checked') == false) return false;
<?php		if($config['sig_enable_release_of_information'] && ($sig['type'] == 'student' || $sig['type'] == 'parent')) { ?>
			if($('#<?=$form_id?>_agree_rel_agree').is(':checked') == false) return false;
<?php		} ?>
		if($('#<?=$form_id?>_agree_sig_agree').is(':checked') == false) return false;
		if($('#<?=$form_id?>_name').val().length < 2) return false;
		return true;
	}
</script>


<?php
output_end();
?>
