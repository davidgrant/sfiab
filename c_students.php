<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('committee/students.inc.php');

$mysqli = sfiab_init('committee');

$u = user_load($mysqli);

$page_id = 'c_students';


sfiab_page_begin($u, "Students", $page_id);

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

	$stats_line = array('accepted' => 0, 'complete'=>0);

	$stats = array();
	foreach($cats as $c) {
		$stats[$c['cat_id']] = array('students'=>$stats_line, 'projects'=>$stats_line);
	}
	$stats['total'] = array('students'=>$stats_line, 'projects'=>$stats_line);

	foreach($students as &$s) {
		if($s['s_accepted'] == 1) {
			$p =& $projects[$s['s_pid']];
			$stats['total']['students']['accepted']+=1;
			if(!array_key_exists($p['cat_id'], $stats)) {
				print("array key doesn't exist for cat_id=[{$p['cat_id']}]<br/>");
				print_r($p);
				print_r(array_keys($stats));
			}
			$stats[$p['cat_id']]['students']['accepted']+=1;
		} else if($s['s_complete'] == 1) {
			$p =& $projects[$s['s_pid']];
			$stats['total']['students']['complete']+=1;
			if(!array_key_exists($p['cat_id'], $stats)) {
				print("array key doesn't exist for cat_id=[{$p['cat_id']}]<br/>");
				print_r($p);
				print_r(array_keys($stats));
			}
			$stats[$p['cat_id']]['students']['complete']+=1;
			$p['complete'] = true;
		} else {
			if(array_key_exists($s['s_pid'], $projects)) {
				$p =& $projects[$s['s_pid']];
				$p['complete'] = false;
			}
		}

	}

	foreach($projects as &$p) {
		if($p['accepted']) {
			$stats['total']['projects']['accepted']+=1;
			$stats[$p['cat_id']]['projects']['accepted']+=1;
		}
		if(array_key_exists('complete', $p) && $p['complete'] == true) {
			$stats['total']['projects']['complete']+=1;
			$stats[$p['cat_id']]['projects']['complete']+=1;
		}
	}
		





?>	
	<h3>Stats</h3> 
	<p><b>Accepted</b> Students and Projects means a signature form has been received and marked in the system.  <b>Complete</b> Students and Projects could print a signature form, but it hasn't been received or entered into the system yet.
	<table>
	<tr><td></td><td colspan="2" align="center"><b>Accepted</b></td><td colspan="2" align="center"><b>Complete</b></td>
		</tr>
	<tr><td></td><td align="center">Students</td><td align="center">Projects</td><td align="center">Students</td><td align="center">Projects</td>
	</tr>
<?php	foreach($cats as $cat_id=>$c) { ?>
		<tr><td align="center"><?=$c['name']?></td>
		    <td align="center"><?=$stats[$cat_id]['students']['accepted']?></td>
		    <td align="center"><?=$stats[$cat_id]['projects']['accepted']?></td>
		    <td align="center"><?=$stats[$cat_id]['students']['complete']?></td>
		    <td align="center"><?=$stats[$cat_id]['projects']['complete']?></td>
		</tr>
<?php	} ?>
	<tr><td align="center"><b>Total</b></td>
	    <td align="center"><b><?=$stats['total']['students']['accepted']?></b></td>
	    <td align="center"><b><?=$stats['total']['projects']['accepted']?></b></td>
	    <td align="center"><b><?=$stats['total']['students']['complete']?></b></td>
	    <td align="center"><b><?=$stats['total']['projects']['complete']?></b></td>
	</tr>
	</table>
		

	<h3>Students</h3> 


	<ul data-role="listview" data-inset="true">
	<li><a href="c_user_list.php?roles[]=student" data-rel="external" data-ajax="false">Student List / Editor</a></li>
	<li><a href="index.php#register" data-rel="external" data-ajax="false">Invite a Student</a></li>
	</ul>

<?php /*
	<h3>Projects</h3> 
	<p>FIXME: coming soon.
*/
?>

	<h3>Signature Forms and Project Numbers</h3> 
	<ul data-role="listview" data-inset="true">
	<li><a href="c_input_signature_forms.php" data-rel="external" data-ajax="false">Input Received Signature Forms</a></li>
	<li><a href="c_assign_project_numbers.php" data-rel="external" data-ajax="false">Assign Project Numbers</a></li>
	<li><a href="" data-rel="external" data-ajax="false">Floorplanning (not yet available)</a></li>
	</ul>

	<h3>Checkin and Tshirt List</h3> 
	<ul data-role="listview" data-inset="true">
	<li><a href="c_checkin.php" data-rel="external" data-ajax="false">Checkin and Tshirt Lists</a></li>
	</ul>
	<h3>Ethics Approval</h3> 
	<ul data-role="listview" data-inset="true">
	<li><a href="c_input_ethics.php" data-rel="external" data-ajax="false">Input Ethics Approval for Projects</a></li>
	</ul>

	<h3>Visit Lists</h3> 
	<ul data-role="listview" data-inset="true">
	<li><a href="c_visit_list.php" data-rel="external" data-ajax="false">Edit Visit List</a></li>
	<li><a href="c_visit_list.php?action=print" data-rel="external" data-ajax="false">Print Visit List</a></li>

</div></div>
	

<?php
sfiab_page_end();
?>
