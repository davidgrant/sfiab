<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('email.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('committee'));

$u = user_load($mysqli);

$page_id = 'c_user_edit';
$help = '<p>Edit a User';

$edit_uid = 0;
if(array_key_exists('uid', $_POST)) {
	$edit_uid = (int)$_POST['uid'];
} else if(array_key_exists('uid', $_GET)) {
	$edit_uid = (int)$_GET['uid'];
}
if($edit_uid == 0) exit();

$edit_u = user_load($mysqli, $edit_uid);

$edit_p = NULL;
if(in_array('student', $edit_u['roles'])) {
	$edit_p = project_load($mysqli, $edit_u['s_pid']);
}

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

switch($action) {
case 'save':
case 'save_back':
	post_text($edit_u['firstname'], 'firstname');
	post_text($edit_u['lastname'], 'lastname');
	post_text($edit_u['email'], 'email');
	post_text($edit_u['username'], 'username');
	post_bool($edit_u['not_attending'], 'not_attending');
	post_text($edit_u['reg_close_override'], 'reg_close_override');

	if(in_array('student', $edit_u['roles'])) {
		post_int($edit_u['tour_id'], 'tour_id');
	}

	if($edit_u['reg_close_override'] !== NULL) {
		$d = date_parse($edit_u['reg_close_override']);
		if($d['year'] > 1900 && $d['month'] > 0 && $d['day'] > 0) {
			$edit_u['reg_close_override'] = sprintf("%04d-%02d-%02d 23:59:59", $d['year'], $d['month'], $d['day']);
		} else {
			$edit_u['reg_close_override'] = NULL;
		}
	}
	user_save($mysqli, $edit_u);
	if($action == 'save') {
		form_ajax_response(array('status'=>0));
	} else {
		form_ajax_response(array('status'=>0, 'location'=>'back'));
	}
	exit();

case 'psave': 
case 'psave_back':
	if(in_array('student', $edit_u['roles'])) {
		post_int($edit_p['disqualified_from_awards'], 'disqualified_from_awards');
		project_save($mysqli, $edit_p);
		if($action == 'psave') {
			form_ajax_response(array('status'=>0));
		} else {
			form_ajax_response(array('status'=>0, 'location'=>'back'));
		}
	}
	exit();


case 'purge':
	if(in_array('student', $edit_u['roles'])) {
		/* If only one student in project, delete project too */
		$q_in_project = $mysqli->query("SELECT uid FROM users WHERE `s_pid`='{$edit_u['s_pid']}'");
		if($q_in_project->num_rows == 1) {
			$mysqli->real_query("DELETE FROM projects WHERE pid='{$edit_u['s_pid']}'");
			$mysqli->real_query("DELETE FROM mentors WHERE pid='{$edit_u['s_pid']}'");
		}
	}
	/* Do this for all users, doesn't matter if it's a student or not */
	$mysqli->real_query("DELETE FROM partner_requests WHERE to_uid='$edit_uid' OR from_uid='$edit_uid'");
	/* Purge the user */
	$mysqli->real_query("DELETE FROM users WHERE uid='$edit_uid'");
	form_ajax_response(0);
	exit();


case 'del':
	$edit_u['state'] = 'deleted';
	user_save($mysqli, $edit_u);
	form_ajax_response(0);
	exit();

case 'resend':
	$password = user_new_password();
	$edit_u['password'] = hash('sha512', $password);
	$edit_u['password_expired'] = 1;
	user_save($mysqli, $edit_u);

	$result = email_send($mysqli, "New Registration", $edit_u['uid'], array('PASSWORD'=>$password) );
	form_ajax_response(0);
	exit();

case 'change_pw':
	$pw1 = $_POST['pw1'];
	$pw2 = $_POST['pw2'];
	if($pw1 != $pw2) {
		form_ajax_response_error(1, 'Passwords don\'t match');
		exit();
	}
	$hash = hash('sha512', $pw1);
	$mysqli->query("UPDATE users SET password='$hash', password_expired='0' WHERE uid=$edit_uid");
	sfiab_log($mysqli, 'change pw', "");
	form_ajax_response(0);
	exit();
}






sfiab_page_begin("Edit User", $page_id, $help);

form_page_begin($page_id, array());
?>
<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<a href="#" data-role="button" data-inline="true" data-icon="back" data-rel="back" data-icon="back" >Back</a>

<h3>Edit <?=$edit_u['name']?></h3>
<?php
	$form_id = $page_id.'_form';
	form_begin($form_id, 'c_user_edit.php');
	form_hidden($form_id, 'uid', $edit_u['uid']);
	form_text($form_id, 'firstname', 'First Name', $edit_u);
	form_text($form_id, 'lastname', 'Last Name', $edit_u);
	form_text($form_id, 'email', 'Email', $edit_u, 'email');
	form_text($form_id, 'username', 'Username', $edit_u);
	$sel = array('0'=>'Yes, I\'ll be there', '1'=>'No, I can\'t make it');
	form_radio_h($form_id, 'not_attending', "At the fair", $sel, $edit_u['not_attending']);
	form_text($form_id, 'reg_close_override', "Registration Close Override", $edit_u, 'date');

	if(in_array('student', $edit_u['roles'])) { 
		$tours = tour_get_for_student_select($mysqli, $edit_u);
		form_select($form_id, 'tour_id', 'Assigned Tour', $tours, $edit_u['tour_id']);
	}

	form_submit($form_id, 'save', 'Save', 'User Saved');
	form_submit($form_id, 'save_back', 'Save and Go Back', 'User Saved');
	form_end($form_id); 


if(in_array('student', $edit_u['roles'])) { ?>
	<h3>Project <?=$edit_p['number']?></h3>
	<p>Title: <?=$edit_p['title']?>
<?php	$form_id = $page_id.'_project_form';
	form_begin($form_id, 'c_user_edit.php');
	form_hidden($form_id, 'uid', $edit_u['uid']);
	form_yesno($form_id, 'disqualified_from_awards', 'Project Disqualifed from Awards', $edit_p['disqualified_from_awards']);
	form_submit($form_id, 'psave', 'Save', 'Project Saved');
	form_submit($form_id, 'psave_back', 'Save and Go Back', 'Project Saved');
	form_end($form_id); 
}
?>


<h3>Change Password</h3>
	<p>Passwords must be at least 8 characters long and contain at least one letter, one number, and one non-alphanumberic character (something other than a letter and a number)
<?php
	$pw1 = '';
	$pw2 = '';
	$form_id = $page_id.'_pw_form';
	form_begin($form_id, 'c_user_edit.php');
	form_hidden($form_id, 'uid', $edit_u['uid']);
	form_text($form_id, 'pw1','New Password',$pw1, 'password');
	form_text($form_id, 'pw2','New Password Again',$pw2, 'password');
	form_submit($form_id, 'change_pw', "Change Password", 'Password Saved');
	form_end($form_id);
?>

<h3>Actions</h3>

<table>
	<tr>
		<td><a id="resend_reg" href="#" onclick="return user_list_info_resend_welcome(<?=$edit_u['uid']?>);" data-role="button" data-inline="true" data-ajax="false">Re-send Welcome Email</a></td>
		<td>Resend the initial welcome email to the user.  This also re-scrambles their password.</td>
	</tr>
	<tr>
		<td><a href="c_user_list.php?edit=<?=$edit_u['uid']?>" data-role="button" data-inline="true" data-ajax="false">Change To User</a></td>
		<td>Temporarily change to this user.  You can also do this by pressing the gear icon beside each user on the user list page.</td>
	</tr>
	<tr>
		<td><a href="#" data-role="button"  data-inline="true" data-theme="r" onclick="return user_list_info_delete(<?=$edit_u['uid']?>);" >Delete</a></td>
		<td>This deletes the user but keeps a copy of their info to avoid breaking database links... e.g. if it's a student that won an award, the student info will still be available and linked to an award.  This just 
		means the student cannot login anymore and can never recover their password.  This action can be undone if you have direct access to the database (will add SFIAB support eventually).</td>
	</tr>
	<tr>
		<td><a href="#" data-role="button"  data-inline="true" data-theme="r" onclick="return user_list_info_purge(<?=$edit_u['uid']?>);" >Purge</a></td>
		<td>Purging a user deletes all traces of them.  They are removed from judging teams, awards, like they never existed.  This action cannot be undone.  They're gone.</td>
	</tr>
</table>


</div></div>


<?php
sfiab_page_end();
?>
