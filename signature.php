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
	$old_grade = $u['grade'];

	$key = '';
	post_text($key, 'key');
	$sig = load_and_check_key($mysqli, $key);

	post_text($sig['signed_name'], 'name');
	$sig['date_signed'] = date( 'Y-m-d H:i:s' );

	signature_save($mysqli, $sig);
	form_ajax_response(0);
	exit();
}


$key = $_GET['k'];
$sig = load_and_check_key($mysqli, $key);

/* Load the student and project */
$student = user_load($mysqli, $sig['uid']);
$project = project_load($mysqli, $student['s_pid']);
$school = school_load($mysqli, $student['schools_id']);

output_start("Electronic Signature");


$page_id = 'signature';
?>


<div data-role="page" id="<?=$page_id?>" class="sfiab_page" > 
<?php

	switch($sig['type']) {
	case 'student':
		$decl = cms_get($mysqli, 'exhibitordeclaration', $student);
		break;		
	case 'parent':
	case 'teacher':
	case 'ethics':
	default:
		exit();
	}

$flags = ENT_QUOTES;
if(PHP_VERSION_ID >= 50400) $flags |= ENT_HTML401;

$logo = '';
?>

<h3>Electronic Signature for <?=$student['name']?></h3>

<p>A <b><?=$signature_types[$sig['type']]?> </b>signature has been requested from you, <?=$sig['name']?>.

<p>Please review the Project Information and Declaration below.  If you agree
to the declaration, type in your name below in lieu of a signature, check the
box to agree, and submit this form.  This will function as your electronic
signature and you do not need to sign the student's paper signature form.

<p>If you have any questions or concerns, please contact us at <?=mailto($config['email_registration'])?>.

<h3>Project Information</h3>

<table data-role="table" data-mode="none" class="table_stripes">
<TR><td>Project Title: </td><td><?=htmlentities($project['title'], $flags , "UTF-8")?></td></tr>
<TR><td>School: </td><td><?=htmlentities($school['school'], $flags , "UTF-8")?></td></tr>
<TR><td>Abstract: </td><td><?=nl2br(htmlentities($project['abstract'], $flags , "UTF-8"))?></td></tr>
</table>

<h3>Declaration</h3>

<?=nl2br($decl)?>


<h3>Signature</h3>
<?php

	$form_id = $page_id.'_form';
	form_page_begin($page_id, array());

	form_begin($form_id, 'signature.php');
	form_hidden($form_id, 'key', $key);
	$str = '';
	form_checkbox($form_id, 'agree', "I Agree to the Declaration Statement Above", 'agree', $str);
	form_text($form_id, 'name', 'Type Your Name', $str);
	form_submit($form_id, 'sign', "Send Electronic Signature", 'Sent');
	form_end($form_id);

?>
</div>



<?php
output_end();
?>
