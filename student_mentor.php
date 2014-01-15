<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('project.inc.php');
$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('student'));

$u = user_load($mysqli);

if($u['student_pid'] == NULL || $u['student_pid'] == 0) {
	print("Error 1010: no project.\n");
	exit();
}

$p = project_load($mysqli, $u['student_pid']);

$page_id = 's_mentor';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

$fields = array('firstname','lastname','phone','email','organization','position','desc');


switch($action) {
case 'save':

	foreach($fields as $f) {
	}

	/* Num mentors */
	$num_mentors = (int)$p['num_mentors'];
	$p['num_mentors'] = $num_mentors;



	project_save($mysqli, $p);
	break;
}

$help = '
Please enter the number of mentors that assisted you in your project.  
<ul><li>
</ul>
';

$fields = incomplete_fields($mysqli, $page_id, $u, true);
sfiab_page_begin("Student Personal", $page_id, $help);

$num_mentors = $p['num_mentors'];
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	form_incomplete_error_message($page_id, $fields);
?>
	<p>
	Please enter the information about your project.  
	</p>
	<form action="student_mentor.php" method="post" data-ajax="false" id="<?=$page_id?>_form">
<?php
		form_text($page_id, 'num_mentors', "Number of Mentors", $p);

		for($x=0;$x<$num_mentors;$x++) {
			print("<h3>Mentor ".($x+1)."</h3>");
			form_text($page_id, 'firstname'.$x, "First Name", $u['firstname'.$x]);
			form_text($page_id, 'lastname'.$x, "Last Name", $u['lastname'.$x]);
			form_text($page_id, 'email'.$x, "Email", $u['email'.$x], 'email');
			form_text($page_id, 'phone'.$x, "Phone", $u['phonehome'.$x], 'tel');
			form_text($page_id, 'organization'.$x, 'Organization', $u['organization'.$x]);
			form_text($page_id, 'position'.$x, 'Position', $u['position'.$x]);
			form_textbox($page_id, 'desc'.$x, 'Description of Help', $u['desc'.$x]);
		}

		form_submit($page_id, 'Save');
?>
		<input type="hidden" name="action" value="save"/>

	</form>

	<?=form_scripts_no_ajax('student_mentor.php', $page_id, $fields);?>

</div>
	

<?php
sfiab_page_end();
?>
