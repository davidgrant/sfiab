<?php

require_once('common.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('email.inc.php');
require_once('form.inc.php');

$mysqli = sfiab_init(array('student', 'committee'));

$page_id = 's_signature_edit';

$u = user_load($mysqli);

/* This is a sub-page of the s_signature page, so check s_signature for prereg */
sfiab_check_abort_in_preregistration($u, 's_signature');

if(!$config['enable_electronic_signatures']) exit();

$p = project_load($mysqli, $u['s_pid']);
$closed = sfiab_registration_is_closed($u);

/* Get all users associated with this project */
$users = user_load_all_for_project($mysqli, $u['s_pid']);

/* Check for all complete */
$all_complete = true;
foreach($users as &$user) {
	if($user['s_complete'] == 0) {
		$all_complete = false;
	}
}

/* Shoudln't even e able to load this page if not everythign is complete */
if(!$all_complete) {
	print("Page is disabled.");
	exit();
}

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

$delete = array_key_exists('del', $_GET);

switch($action) {
case 'del':
	if($closed) exit();

	$key = $_POST['key'];
	$sig = signature_load($mysqli, $key);

	if($sig === NULL) exit();

	/* Is it already signed? */
	if($sig['date_signed'] != '0000-00-00 00:00:00') {
		print("already signed.");
		exit();
	}

	$mysqli->query("DELETE FROM signatures WHERE `key`='{$sig['key']}'");
	form_ajax_response(array('status'=>0, 'location'=>'back'));
	exit();

case 'send':
	if($closed) exit();

	$key = $_POST['key'];
	$sig = signature_load($mysqli, $key);

	if($sig === NULL) {
		print("not found");
		exit();
	}

	/* Make sure the key belongs to a user attached to this project */
	$send_u = NULL;
	foreach($users as &$user) {
		if($user['uid'] == $sig['uid']) {
			$send_u = $user;
			break;
		}
	}
	if($send_u === NULL) {
		print("NO user");
		exit();
	}

	/* Update info */
	post_text($sig['name'], 'name');
	post_text($sig['email'], 'email');

	/* Record the send time */
	$sig['date_sent'] = date('Y-m-d H:i:s');

	/* Save signature */
	signature_save($mysqli, $sig);

	/* Send the email */
	$additional_vars = array('student_name' => $send_u['name'],
				    'fair_url' => $config['fair_url'],
				    'signature_key' => $key);
	email_send_to_non_user($mysqli, 'Electronic Signature', $sig['name'], $sig['email'], $additional_vars);

	form_ajax_response(array('status'=>0, 'location'=>'back'));
	exit();
}

/* need a uid and a type */
$send_uid = (int)$_GET['uid'];
$send_type = $_GET['type'];

/* Make sure they're valid */
if($send_uid <= 0) exit();
if(!array_key_exists($send_type, $signature_types)) exit();

/* Find the user in the list of users */
$send_u = NULL;
foreach($users as &$user) {
	if($user['uid'] == $send_uid) {
		$send_u = $user;
	}
}
/* Not allowed, someone is manually posting data for random users */
if($send_u === NULL) exit();

/* Load the signature data for this user/type */
//$q = $mysqli->query("DELETE FROM signatures WHERE uid='{$send_u['uid']}' AND `type`='$send_type'");
$q = $mysqli->query("SELECT * FROM signatures WHERE uid='{$send_u['uid']}' AND `type`='$send_type'");
if($q->num_rows >= 1) {
	/* Take the first row */
	$sig = signature_load($mysqli, NULL, $q->fetch_assoc());
} else {
	/* No data */
	$key = signature_create($mysqli);
	$sig = signature_load($mysqli, $key);
	$sig['uid'] = $send_uid;
	$sig['type'] = $send_type;
	signature_save($mysqli, $sig);
}


$help='
<p>Use this page to send an electronic signature request
';

sfiab_page_begin($u, "Send Electronic Signature Form", $page_id, $help);

?>

<div data-role="page" id="<?=$page_id?>" class="sfiab_page" > 

<?php
form_page_begin($page_id, array());
form_disable_message($page_id, $closed, $u['s_accepted']);

?>

<h3>Electronic <?=$signature_types[$send_type]?> Signature</h3>

<p>This page lets you send an electronic signature form for the <?=$signature_types[$send_type]?> signature.

<p>The electronic signature works as follows:
<ol>
<li>Enter the name and email address of the person you need a signature from.  Make sure the signature type (Student, Parent/Guardian, Teacher) matches the name and email address you put in.  
	<ul>
	<li>Try doing the Student signature first, so the system emails you.
	</ul>
<li>Press the "Send Electronic Signature Form" button, and the system will email the recipient a URL that links back to this registration site and contains information about your project.
<li>When the recipient clicks on the URL, they will be taken to a page on this registration site where they can review the detail of your project and a legal statement about your project.
<li>When the recipient agrees to the statement and enters their name, we accept that as their signature.  When that is done you do not need to submit a paper copy of their signature.  Electronic signatures will appear on your <a href="s_signature.php" data-ajax="false">Signature Page</a>
</ol>

<?php

if($delete) { ?>
	<p><b>DELETING A SIGNATURE FORM - </b>means that the person you have emailed
	will now have a broken link in their mailbox.  Generally it is not a good idea
	to delete a form, but if you have entered an incorrect email address, you can
	delete the form and re-send it.
<?php 
} 

$name = NULL;
$email = NULL;
switch($send_type) {
case 'student':
	/* Use the registered student's name and email */
	$name = $send_u['name'];
	$email = $send_u['email'];
	break;

case 'teacher':
	/* Try the name/email in the teacher part of the project */
	$name = $send_u['s_teacher'];
	$email = $send_u['s_teacher_email'];
	break;

case 'parent':
	/* Try the first parent in the emergency contacts */
	$ecs = emergency_contact_load_for_user($mysqli, $send_u);
	foreach($ecs as $id=>&$ec) {
		if($ec['relation'] == 'parent') {
			$name = $ec['firstname'].' '.$ec['lastname'];
			$email = $ec['email'];
			break;
		}
	}
	break;
}

/* Override with whatever is in the sig, if non-zero */
if(strlen($sig['name']) > 0) {
	$name = $sig['name'];
}
if(strlen($sig['email']) > 0) {
	$email = $sig['email'];
}

$form_id = $page_id.'_form';
form_begin($form_id, 's_signature_edit.php');
form_label($form_id, 'type', 'Signature Type', $signature_types[$send_type]);
form_hidden($form_id, 'key', $sig['key']);
if($delete) {
	form_label($form_id, 'name', 'Name', $name);
	form_label($form_id, 'email', 'Email', $email);
	form_submit_enabled($form_id, 'del', 'Delete Electronic Signature Form', 'Form Sent', 'r', 'delete', 'This form has already been emailed to the recipient.  If you delete it they will have an invalid link in their mailbox.  Are you sure you want to delete it?');
} else {
	form_text($form_id, 'name', 'Name', $name);
	form_text($form_id, 'email', 'Email', $email);
	form_submit_enabled($form_id, 'send', 'Send Electronic Signature Form', 'Form Sent', 'g', 'check', 'This will send an email to the recipient for a signature.  Once sent you cannot un-send the email.  Are you sure you want to send it?');
}
?>
<a href="#" data-rel="back" data-role="button" data-icon="back" data-inline="true" data-theme="r">Cancel, Go Back</a>
<?php
form_end($form_id);
?>

</div>

<?php
sfiab_page_end();
exit();


?>
