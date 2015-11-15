<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('project_number.inc.php');

$mysqli = sfiab_init('committee');

$u = user_load($mysqli);

$roles = array();

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}
switch($action) {
case 'c':
	/* Mark project as approved */
	$pid = (int)$_POST['pid'];
	if($pid > 0) {
		$p = project_load($mysqli, $pid);
		$p['ethics_approved'] = 1;
		project_save($mysqli, $p);
	}
	form_ajax_response(0);
	exit();

case 'i':
	$pid = (int)$_POST['pid'];
	if($pid > 0) {
		$p = project_load($mysqli, $pid);
		$p['ethics_approved'] = 0;
		project_save($mysqli, $p);
	}
	form_ajax_response(0);
	exit();

}

function l_projects_load_all($mysqli, $year)
{
	/* Load projects first */
	$q = $mysqli->query("SELECT * FROM projects WHERE year='$year' ");
	$projects_tmp = array();
	while($p = $q->fetch_assoc()) {
		$p_temp = project_load($mysqli, $p['pid'], $p);
		$projects_tmp[$p['pid']] = $p_temp;
	}

	$projects = array();
	/* Now match users to projects, copying projects
	 * into the real return array as we find them */
	$q = $mysqli->query("SELECT users.*,schools.school FROM users 
					LEFT JOIN schools ON users.schools_id=schools.id
				WHERE users.year='$year'
				AND users.enabled = '1'
				AND users.new = '0'
				AND FIND_IN_SET('student', users.`roles`)>0
				");
	$users = array();
	while($j = $q->fetch_assoc()) {
		$p_user = user_load($mysqli, -1, -1, NULL, $j);
		$pid = $p_user['s_pid'];

		if($pid == 0) {
			print("No project for student uid={$p_user['uid']}<br/>");
		}

		if(!array_key_exists($pid, $projects)) {
			$projects[$pid] = $projects_tmp[$pid];
			$projects[$pid]['students'] = array();
			$projects[$pid]['s_complete'] = true;
		}

		$projects[$pid]['students'][] = $p_user;
		if($p_user['s_complete'] == 0) {
			$projects[$pid]['s_complete'] = false;
		}
	}
	return $projects;
}


$page_id = 'c_input_ethics';
$help = '<p>There are
	two buttons that may appear: <font color=red>Mark as Approved</font> and <font
	color=green>Mark as NOT Approved</font>.  The <font color=red>Mark as
	Approved</font> button is red so you can scan through the list quickly
	and find all projects that are not approved for ethics but need to be.  Similarly the <font
	color=green>Mark as NOT Approved</font> button is green 
	so you can find all the projects with ethics approval.  When the green <font
	color=green>Mark as NOT Approved</font> button is showing, it means the
	project has been marked as having ethics approval.';


sfiab_page_begin($u, "Input Ethics Approval", $page_id, $help);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

	<h3>Input Ethics Approvals</h3>

	<p>Use the list below to mark projects having ethics approval.  There are
	two buttons that may appear: <font color=red>Mark as Approved</font> and <font
	color=green>Mark as NOT Approved</font>.  The <font color=red>Mark as
	Approved</font> button is red so you can scan through the list quickly
	and find all projects that are not approved for ethics but need to be.  Similarly the <font
	color=green>Mark as NOT Approved</font> button is green 
	so you can find all the projects with ethics approval.  When the green <font
	color=green>Mark as NOT Approved</font> button is showing, it means the
	project has been marked as having ethics approval.

	<ul data-role="listview" data-filter="true" data-filter-placeholder="Search by project number, project title, student name, school name..." data-inset="true">

<?php


$projects = l_projects_load_all($mysqli, $config['year']);


foreach($projects as &$p) {
	$pid = $p['pid'];

	$e =& $p['ethics'];
	if($e['human1'] === NULL || $e['animals'] === NULL) {
		/* Project hasn't filled out ethics yet */
		continue;
	} else if($e['human1'] == 0 && $e['animals'] == 0) {
		/* Does not require ethics approval */
		continue;
	} 

	$ethics = '';
	if($e['human1']) {
		$ethics .= ' Human';
	}
	if($e['animals']) {
		$ethics .= ' Animals';
	}

	$filter_text = "{$p['pid']} {$p['title']}";
	$accepted = $p['students'][0]['s_accepted'] ? true : false;
	$paid = $p['students'][0]['s_paid'] ? true : false;
	foreach($p['students'] as &$s) {
		$filter_text .= " {$s['name']} {$s['school']}";
		if($accepted != $s['s_accepted'])
			$accepted = false;
		if($paid != $s['s_paid'])
			$paid = false;

	}

?>
	<li id="ethics_approval_<?=$p['pid']?>" data-filtertext="<?=$filter_text?>">
		<h3>Project <?=$p['pid']?>: <?=$p['title']?></h3>
		<div class="ui-grid-a" data-role="fieldcontain">
			<div class="ui-block-a" style="width:80%">
				<table>
<?php				foreach($p['students'] as &$s) { 
					$status = $s['s_complete'] == 0 ? '<font color=red>(incomplete)</font>' : '<font color=green>(complete)</font>';
?>
					<tr><td><?=$status?></td>
					    <td><?=$s['name']?>, </td>
					    <td>Grade <?=$s['grade']?>, </td>
					    <td><?=$s['school']?></td>
					</tr>
<?php				} ?>
				</table>
				<br/>
				<b>Ethics: <?=$ethics?></b><br/>
			</div>
			<div class="ui-block-b" style="width:20%">
<?php				if($p['ethics_approved']) {
					$mark_as_approved_style = 'style="display:none;"';
					$mark_as_notapproved_style = '';
				} else {
					$mark_as_approved_style = '';
					$mark_as_notapproved_style = 'style="display:none;"';
				}
?>

				<a href="#" onclick="input_ethics_mark_as_approved(<?=$pid?>)" id="input_ethics_c_<?=$pid?>" <?=$mark_as_approved_style?> data-role="button" data-theme="r" >Mark as Approved</a>
				<a href="#" onclick="input_ethics_mark_as_notapproved(<?=$pid?>)" id="input_ethics_i_<?=$pid?>" <?=$mark_as_notapproved_style?> data-role="button" data-theme="g" >Mark as NOT Approved</a>

			</div>
		</div>
	</li>
<?php
}

?>
</ul>

<script>
	function input_ethics_mark_as_approved(pid) {
		$.post('c_input_ethics.php', { action: "c", pid: pid }, function(data) {
			if(data.status == 0) {
				$('#input_ethics_c_'+pid).hide();
				$('#input_ethics_i_'+pid).show();
			}
		}, "json");
		return false;
	}
	function input_ethics_mark_as_notapproved(pid) {
		$.post('c_input_ethics.php', { action: "i", pid: pid }, function(data) {
			if(data.status == 0) {
				$('#input_ethics_c_'+pid).show();
				$('#input_ethics_i_'+pid).hide();
			}
		}, "json");
		return false;
	}


</script>

			


<?php
sfiab_page_end();
?>
