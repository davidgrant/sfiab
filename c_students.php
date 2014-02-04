<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('committee/students.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('committee'));

$u = user_load($mysqli);

$page_id = 'c_students';


sfiab_page_begin("Students", $page_id);

?>
<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 
<?php
	form_page_begin($page_id, array());
	/* Count students */
	$students = students_load_all($mysqli, $config['year']);

	$j_complete = 0;
	$j_not_attending = 0;
	$j_incomplete = 0;
	foreach($students as &$j) {
		if($j['not_attending']) {
			$j_not_attending++;
		} else {
			if($j['s_complete']) 
				$j_complete++;
			else
				$j_incomplete++;
		}
	}


?>	<h3>Students</h3> 
	<p>Complete: <b><?=$j_complete?></b> / <b><?=$j_complete+$j_incomplete?></b>,  plus not attending: <b><?=$j_not_attending?></b>.

	<ul data-role="listview" data-inset="true">
	<li><a href="c_student_invite.php" data-rel="external" data-ajax="false">X Invite a Student</a></li>
	<li><a href="c_user_list.php?roles=student" data-rel="external" data-ajax="false">Student List / Editor</a></li>
	</ul>

	<h3>Projects</h3> 
	<p>FIXME: coming soon.


<?php
/*        $form_id = 'j_attending_form';
        form_begin($form_id, 'c_student.php');
        form_text($form_id, 'j_not_attending', "Judging at the fair", $u['not_attending']);
        form_submit($form_id, 'save', 'Save', 'Information Saved');
        form_end($form_id);
*/	
?>

</div></div>
	

<?php
sfiab_page_end();
?>
