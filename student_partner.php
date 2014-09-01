<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('project.inc.php');
require_once('isef.inc.php');

header("Cache-Control: no-cache");

$mysqli = sfiab_init('student');

$u = user_load($mysqli);
$closed = sfiab_registration_is_closed($u);

if( $u['s_pid'] == 0) {
	print("Error 1011: pid {$u['username']}");
	exit();
}
$p = project_load($mysqli, $u['s_pid']);

$page_id = 's_partner';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

/* We need these numbers for everything */
$q_in_project = $mysqli->query("SELECT uid,firstname,lastname,username FROM users WHERE `s_pid`='{$p['pid']}'");
$students_in_project = $q_in_project->num_rows;
$students_missing = $p['num_students'] - $students_in_project;

$q_sent = $mysqli->query("SELECT * FROM partner_requests WHERE `from_uid`='{$u['uid']}'");
$invites_sent = $q_sent->num_rows;

$invite_error = "";
$need_sent_reload = false;
$need_missing_reload = false;

switch($action) {
case 'save':
	if($closed) exit();
	post_int($p['num_students'], 'num_students');

	/* How many students are actually attached to the project? */
	if($students_in_project > $p['num_students']) {
		$p['num_students'] = $students_in_project;
	}
	project_save($mysqli, $p);

	incomplete_check($mysqli, $ret, $u, $page_id, true);
	form_ajax_response(array('status'=>0, 'missing'=>$ret, 'location'=>'student_partner.php'));
	exit();

case 'invite':
	if($closed) exit();
	$un = $mysqli->real_escape_string($_POST['un']);

	$invite_error = 'Username not found';
	if(strlen($un) > 0) {
		/* Find this user */

		/* To send a request the user must have a project with num_students set to zero (or NULL), or 
		 * must have num_students set > 1 with fewer students attached tot he project and no
		  * partner requests sent at all (let's avoid creating accept loops) */
		$q = $mysqli->query("SELECT uid FROM users WHERE username='$un' 
							AND year='{$config['year']}' 
							AND FIND_IN_SET('student',`roles`)>0
							AND `enabled`='1' AND `new`='0'" );
		$request_ok = true;
		if($q->num_rows == 1) {

			$r = $q->fetch_assoc();
			$invite_uid = (int)$r['uid'];


			/* Make sure it's not ourself */
			if($invite_uid == $u['uid']) {
				/* Can't invite self to project */
				form_ajax_response_error(0, "Congratulations, you just invited yourself to the project.  I took the liberty of accepting 
					your invitation from you on your behalf.  Perhaps you should also invite someone else to the project.");
				exit();
			}

			/* Found, is there still room in this project (the user isn't reloading the post)? */
			if(($students_missing - $invites_sent) > 0) {

				/* Load the remote user and get the remote project */
				$u_partner = user_load($mysqli, $invite_uid);
				$p_partner = project_load($mysqli, $u_partner['s_pid']);

				if($p_partner['num_students'] == 1) {
					$request_ok = false;
					$invite_error = "Unable to send partner request because that user has specified there is only one student in his/her project.  If that really is your partner, have them go into their Partner settings, and change the number of students to 2.";
				}

				if($request_ok && $p_partner['num_students'] > 1) {
					/* Make sure the project isn't full */
					$q1 = $mysqli->query("SELECT uid FROM users WHERE `s_pid`='{$p_partner['pid']}'");
					if($q1->num_rows != 1) {
						$request_ok = false;
						$invite_error = "Unable to send partner request because that user is already in a project with more than one student.  Have the user send you a partner request.";
					}

				}

				if($request_ok) {
					/* Check that the user has no pending sent requests */
					$q1 = $mysqli->query("SELECT id FROM partner_requests WHERE `from_uid`='{$u_partner['uid']}'");
					if($q1->num_rows != 0) {
						$request_ok = false;
						$invite_error = "Unable to send partner request because that user has pending partner requests already.  Have the user send you a partner request or cancel the requests they have sent.";
					}
				}

				if($request_ok == true) {
					$mysqli->real_query("INSERT INTO partner_requests (`from_uid`,`to_uid`) VALUES
								('{$u['uid']}', '{$r['uid']}')");

					form_ajax_response(array('status'=>0, 'location'=>'student_partner.php'));
					exit();
					/* Redo invites query */
//					$need_sent_reload = true;
				}
			}
		} else {
			$invite_error = "Username not found";
			$request_ok = false;
		}
	}

	form_ajax_response_error(0, $invite_error);
	exit();

case 'cancel':
	if($closed) exit();
	$id = (int)$_POST['id'];
	print("Cancel id $id");
	/* Add the from_uid to the reqest so users can't delete any ID */
	$mysqli->query("DELETE FROM partner_requests WHERE id='$id' AND from_uid='{$u['uid']}'");
	/* Reload the partners */
	$need_sent_reload = true;
	break;

case 'remove':
	if($closed) exit();
	$uid = (int)$_POST['uid'];
	if($uid == $u['uid']) {
		/* Remove us from the project - create a new project and link that to us */
		$new_pid = project_create($mysqli);
		$u['s_pid'] = $new_pid;
		user_save($mysqli, $u);
		$p = project_load($mysqli, $new_pid);
		$need_missing_reload = true;
	} else {
		/* Remove a UID from the project */
		$this_u = user_load($mysqli, $uid);
		/* Make sure they're in this project */
		if($this_u['s_pid'] == $p['pid']) {
			/* Create a new project and set that to be the user's project */
			$new_pid = project_create($mysqli);
			$this_u['s_pid'] = $new_pid;
			user_save($mysqli, $this_u);
			$need_missing_reload = true;
		}
	}
	break;

case 'accept_request':
	if($closed) exit();
	$id = (int)$_POST['id'];
	$q = $mysqli->query("SELECT from_uid,to_uid FROM partner_requests WHERE `id`='$id' AND `to_uid`='{$u['uid']}'");
	if($q->num_rows != 1) {
		print("Error 1012: $id\n");
		exit();
	}

	$r = $q->fetch_assoc();
	$from_uid = $r['from_uid'];

	$u_partner = user_load($mysqli, $from_uid);
	$new_pid = $u_partner['s_pid'];
	$p_partner = project_load($mysqli, $new_pid);

	$old_pid = $u['s_pid'];

	$u['s_pid'] = $new_pid;
	user_save($mysqli, $u);

	/* Set to the new project */
	$p = $p_partner;


	$mysqli->real_query("DELETE FROM projects WHERE pid='$old_pid'");
//	$mysqli->real_query("DELETE FROM mentors WHERE pid='$old_pid'");
	$mysqli->real_query("DELETE FROM partner_requests WHERE id='$id' AND to_uid='{$u['uid']}'");
	$need_missing_reload = true;

	/* Update incomplete status of everything related to the project */
	$fields = array();
	incomplete_check($mysqli, $fields, $u, false, true);
	break;

case 'reject_request':
	if($closed) exit();
	$id = (int)$_POST['id'];
	$mysqli->real_query("DELETE FROM partner_requests WHERE id='$id' AND to_uid='{$u['uid']}'");
	break;
}

/* Reload the number of invites sent */
if($need_sent_reload) {
	$q_sent = $mysqli->query("SELECT * FROM partner_requests WHERE `from_uid`='{$u['uid']}'");
	$invites_sent = $q_sent->num_rows;
}

/* Reload the number of students and missing students in the project */
if($need_missing_reload) {
	$q_in_project = $mysqli->query("SELECT uid,firstname,lastname,username FROM users WHERE `s_pid`='{$p['pid']}'");
	$students_in_project = $q_in_project->num_rows;
	$students_missing = $p['num_students'] - $students_in_project;
}

$help = '<ul><li><b>Number of Students</b> - Only single and pair projects are eligible for the fair.  If more than two student worked on your project, ask your teacher or contact us for entrance options.
<li><b>Inviting Partners</b> - For two or more person projects, your partner(s) must create accounts before you can invite them by their username to the project.
<li><b>Removing Students</b> - Every student except the last student may be removed from a project.
</ul>
';

/* Recompute incomplete fields for this page before printing the leftnav */
incomplete_check($mysqli, $fields, $u, $page_id, true);

sfiab_page_begin("Project Partner", $page_id, $help);

?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	form_page_begin($page_id, $fields);
	form_disable_message($page_id, $closed, $u['s_accepted']);

	$d = $closed ? 'disabled="disabled"' : '';

	/* Check for an incoming request first */
	$q = $mysqli->query("SELECT * FROM partner_requests WHERE to_uid='{$u['uid']}'");
	if($q->num_rows > 0) { 
		$i=0;
		while($r = $q->fetch_assoc()) {
			$from_uid = $r['from_uid'];
			$request_id = $r['id'];

			$from_u = user_load($mysqli, $from_uid);
			?>
			<h3>Partner Request</h3>
			<p>You have a partner request from <b><?=$from_u['name']?></b> (Username: <?=$from_u['username']?>).
			<p>If you <b>accept</b> this request, the same project will be linked
			to both your accounts.  All information
			you have entered in the Project Info, Project Ethics,
			Project Safety, Mentor Info, and Awards pages will be
			lost and replaced with the information from <b><?=$from_u['name']?></b>.

			<p>If you have already entered all this information, you should reject this request and send a parter request 
			to <b><?=$from_u['name']?></b> (Username: <?=$from_u['username']?>).

			<p>If you <b>reject</b> this request, nothing will
			happen except that the request will be deleted.

			<form action="student_partner.php" method="post" data-ajax="false" id="<?=$this_form_id?>">
				
			<div class="ui-field-contain">
				<button id="<?$page_id?>_cancel_<?=$i?>" name="action" value="accept_request" type="submit" data-inline="true" data-theme="g" <?=$d?>>
					Accept Request
				</button>
				<button id="<?$page_id?>_cancel_<?=$i?>" name="action" value="reject_request" type="submit" data-inline="true" data-theme="r" <?=$d?>>
					Reject Request
				</button>
			</div>
			<input type="hidden" name="id" value="<?=$request_id?>"/>
			</form>


<?php			$i++;
		} 
		/* Cut the page short */ ?>
		</div>
<?php		sfiab_page_end();
		exit();
	} 



	/* Put this way up here so we can use it's error div for the entire
	 * page */
	$form_id = $page_id.'_num_students_form';
	form_begin($form_id, 'student_partner.php', $closed);
	?>


	<h3>Number of Students</h3>

<p>Enter the Number of Students who worked on this project.</p>
<?php
	$num_data = array();
	for($i=$students_in_project; $i<=2; $i++) $num_data[$i] = $i;

	form_select($form_id, 'num_students', "Num Students", $num_data, $p);
	form_submit($form_id, 'save', 'Save', 'Information Saved');
	form_end($form_id);

	/* For any students missing from the project, print a form to send invites */

	if( ($students_missing - $invites_sent) > 0) {
?>
		<h3>Invite Partner(s) to this Project</h3>
		<p>Enter your partner's username to invite them to
		this project.  Their account must already exist for this to
		work.  If your partner hasn't created an account yet, have them
		do that first. </p>


<?php		if($invite_error != '') {
			sfiab_error($invite_error);
		}

		for($i=$students_missing-$invites_sent+1; $i <= $p['num_students']; $i++) { 
			$this_form_id = $page_id.'_invite_'.$i;
			$button_id = "invite_submit_$i";
			$v = '';

			$form_incomplete_fields[] = 'un';
			form_begin($this_form_id, 'student_partner.php', $closed);
			form_text($this_form_id, 'un', 'Username', $v);
			form_submit($this_form_id, 'invite', 'Invite', 'Invite');
			form_end($this_form_id);
		} 
	}

	if($invites_sent > 0) {
?>
		<h3>Partner(s) Invited</h3>
		<p>You have invited these student(s) to your project.  You may
		cancel invitations here if they were sent to the wrong
		person.</p>
<?php		
		$i=0;
		while($r = $q_sent->fetch_assoc()) {
			$i++;
			$id = $r['id'];
			$to_uid = $r['to_uid'];
			$this_u = user_load($mysqli, $to_uid);
			$this_form_id = $page_id.'_cancel_'.$i;
			$button_id = "cancel_submit_$i";

			$v = "{$this_u['name']}<br/>({$this_u['username']})";
?>			<form action="student_partner.php" method="post" data-ajax="false" id="<?=$this_form_id?>">
				
			<div class="ui-field-contain-wide ui-field-contain">
				<label class="error" for="<?$page_id?>_cancel_<?=$i?>"><?=$v?></label>
				<button id="<?$page_id?>_cancel_<?=$i?>" type="submit" data-inline="true" data-theme="b" <?=$d?>>
					Cancel Invitation
				</button>
			</div>
			
			<input type="hidden" name="action" value="cancel"/>
			<input type="hidden" name="id" value="<?=$id?>"/>
			</form>
<?php		} 
	}

?>

	<br/>		
	<h3>Students for this Project</h3>

<?php

	/* List all students already attached to the project and the option to remove them. */
	$i = 0;
	while($r = $q_in_project->fetch_assoc()) {
		$uid = $r['uid'];
		$name = $r['firstname']. ' ' .$r['lastname'];
		$username = $r['username'];
		$i += 1;
		if($students_in_project == 1) {
			$button_disabled = "disabled='disabled'";
			$button_text = "Cannot Remove Only Student";
		} else {
			$button_disabled = '';
			$button_text = "Remove from Project";
		}
?>
		<form action="student_partner.php" method="post" data-ajax="false">
			<div class="ui-field-contain ui-field-contain-wide">
				<label for="<?$page_id?>_remove_<?=$i?>"><?=$name?></label>
				<button id="<?=$form_id?>_form_submit_<?=$i?>" type="submit" data-inline="true" data-theme="r" <?=$button_disabled?> <?=$d?>>
					<?=$button_text?>
				</button>
			</div>
			<input type="hidden" name="action" value="remove" />
			<input type="hidden" name="uid" value="<?=$uid?>"/>
		</form>
<?php	}?>



</div></div>
	




<?php
sfiab_page_end();
?>
