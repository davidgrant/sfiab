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
	$cats = categories_load($mysqli);
	$projects = projects_load_all($mysqli, false);

	$j_complete = 0;
	$j_not_attending = 0;
	$j_incomplete = 0;

	$stats_line = array('accepted' => 0);

	$stats = array();
	foreach($cats as $c)
		$stats[$c['id']] = array('students'=>$stats_line, 'projects'=>$stats_line);
	$stats['total'] = array('students'=>$stats_line, 'projects'=>$stats_line);
	
	foreach($students as &$s) {
		if($s['s_accepted'] == 1) {
			$p =& $projects[$s['s_pid']];
			$stats['total']['students']['accepted']+=1;
			$stats[$p['cat_id']]['students']['accepted']+=1;
		}
	}

	foreach($projects as &$p) {
		if($p['accepted']) {
			$stats['total']['projects']['accepted']+=1;
			$stats[$p['cat_id']]['projects']['accepted']+=1;
		}
	}
		





?>	
	<h3>Stats</h3> 
	<table>
	<tr><td></td><td colspan="2" align="center">Accepted</td></tr>
	<tr><td></td><td align="center">Students</td><td align="center">Projects</td></tr>
<?php	foreach($cats as $cat_id=>$c) { ?>
		<tr><td align="center"><?=$c['name']?></td>
		    <td align="center"><?=$stats[$cat_id]['students']['accepted']?></td>
		    <td align="center"><?=$stats[$cat_id]['projects']['accepted']?></td>
		</tr>
<?php	} ?>
	<tr><td align="center"><b>Total</b></td>
	    <td align="center"><b><?=$stats['total']['students']['accepted']?></b></td>
	    <td align="center"><b><?=$stats['total']['projects']['accepted']?></b></td>
	</tr>
	</table>
		

	<h3>Students</h3> 


	<ul data-role="listview" data-inset="true">
	<li><a href="index.php#register" data-rel="external" data-ajax="false">Invite a Student</a></li>
	<li><a href="c_user_list.php?roles[]=student" data-rel="external" data-ajax="false">Student List / Editor</a></li>
	</ul>

	<h3>Projects</h3> 
	<p>FIXME: coming soon.

	<h3>Projects</h3> 
	<ul data-role="listview" data-inset="true">
	<li><a href="c_input_signature_forms.php" data-rel="external" data-ajax="false">Input Received Signature Forms</a></li>
	</ul>
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
