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
	$pid = (int)$_POST['pid'];
	if($pid > 0) {
		$p = project_load($mysqli, $pid);
		project_load_students($mysqli, $p);

		$ok = true;
		foreach($p['students'] as &$u) {
			/* User must be "complete" */
			if($u['s_complete'] != 1) {
				$ok = false;
			}
		}

		if($ok == true) {
			$p['accepted'] = 1;
			project_save($mysqli, $p);

			foreach($p['students'] as &$u) {
				$u['s_accepted'] = 1;
				$u['s_paid'] = 1;
				user_save($mysqli, $u);
			}
			form_ajax_response(0);
		} else {
			form_ajax_response(1);
		}
		exit();
	}
	form_ajax_response(1);
	exit();

case 'i':
	$pid = (int)$_POST['pid'];
	if($pid > 0) {
		$p = project_load($mysqli, $pid);
		project_load_students($mysqli, $p);

		$p['accepted'] = 0;
		project_number_clear($mysqli, $p);
		project_save($mysqli, $p);

		foreach($p['students'] as &$u) {
			/* User must be "complete" */
			$u['s_accepted'] = 0;
			$u['s_paid'] = 0;
			user_save($mysqli, $u);
		}
		form_ajax_response(0);
		exit();
	}
	form_ajax_response(1);
	exit();


}


foreach($_GET as $k=>$v) {
	switch($k) {
	case 'roles':
		$g_roles = explode(',', $v);
		foreach($g_roles as $r) {
			if(!array_key_exists($r, $sfiab_roles)) exit();
			$roles[] = $r;
		}
		$_SESSION['edit_return'] = $roles;
		break;
	case 'edit':
		$uid = (int)$v;
		$new_u = user_load($mysqli, $uid);
		$_SESSION['edit_uid'] = $uid;
		$_SESSION['edit_roles'] = $new_u['roles'];
		$_SESSION['edit_name'] = $new_u['name'];
		header("Location: ".user_homepage($new_u));
		exit();

	case 'return':
		unset($_SESSION['edit_uid']);
		unset($_SESSION['edit_roles']);
		unset($_SESSION['edit_name']);
		$roles = $_SESSION['edit_return'];
		break;
	}
}

if(count($roles) == 0) {
	$roles = array('committee');
	$_SESSION['edit_return'] = $roles;
	
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

		$p_user['signatures'] = array();
		$users[$p_user['uid']] = $p_user;

		if($pid == 0) {
			print("No project for student uid={$p_user['uid']}<br/>");
		}

		if(!array_key_exists($pid, $projects)) {
			$projects[$pid] = $projects_tmp[$pid];
			$projects[$pid]['students'] = array();
			$projects[$pid]['s_complete'] = true;
		}

		$projects[$pid]['students'][] = &$users[$p_user['uid']];
		if($p_user['s_complete'] == 0) {
			$projects[$pid]['s_complete'] = false;
		}
	}

	/* Do the same for signatures (doesn't matter if they're not enabled here) */
	$q = $mysqli->query("SELECT * FROM signatures WHERE year='$year' and `date_signed`!='0000-00-00 00:00:00'");
	while($s = $q->fetch_assoc()) {
		$uid = $s['uid'];
		$p_user = &$users[$uid];
		$p_user['signatures'][$s['type']] = $s;
	}
	return $projects;
}


$page_id = 'c_input_signature_forms';
$help = '<p>There are
	two buttons that may appear: <font color=red>Mark as Complete</font>,
	and <font
	color=green>Mark as Incomplete</font>.  The <font color=red>Mark as
	Complete</font> button is red so you can scan through the list quickly
	and find all incomplete (red) applications.  Similarly the <font
	color=green>Mark as Incomplete</font> button is green 
	so you can find all the complete ones.  When the green <font
	color=green>Mark as Incomplete</font> button is showing, it means the
	project has been marked as complete.';


sfiab_page_begin($u, "Input Signature Forms", $page_id, $help);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

	<h3>Input Signature Forms</h3>

	<p>Use the list below to mark registrations as complete.  There are
	two buttons that may appear: <font color=red>Mark as Complete</font> and <font
	color=green>Mark as Incomplete</font>.  The <font color=red>Mark as
	Complete</font> button is red so you can scan through the list quickly
	and find all incomplete (red) applications.  Similarly the <font
	color=green>Mark as Incomplete</font> button is green 
	so you can find all the complete ones.  When the green <font
	color=green>Mark as Incomplete</font> button is showing, it means the
	project has been marked as complete.

	<p>Marking a project as Incomplete also clears the project number.  If
	you mark a project as incomplete, then mark it as complete again, you
	will need to re-assign the project numbers.

	<p>After most or all projects are complete, proceed to <a
	href="c_assign_project_numbers.php" data-ajax="false">Assign Project Numbers</a>.
	Project numbers can be safely assigned more than once without
	overwriting numbers already assigned.


	<ul data-role="listview" data-filter="true" data-filter-placeholder="Search by project number, project title, student name, school name..." data-inset="true">

<?php


$projects = l_projects_load_all($mysqli, $config['year']);


foreach($projects as &$p) {
	$pid = $p['pid'];
	if($p['s_complete'] == false) continue;

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

	list($regfee, $regitems) = compute_registration_fee($mysqli, $p, $p['students']);
	
?>
	<li id="received_form_<?=$p['pid']?>" data-filtertext="<?=$filter_text?>">
		<h3>Project <?=$p['pid']?>: <?=$p['title']?></h3>
		<div class="ui-grid-a" data-role="fieldcontain">
			<div class="ui-block-a" style="width:80%">
				<table>
<?php				foreach($p['students'] as &$s) { 
					$status = $s['s_complete'] == 0 ? '<font color=red>(incomplete)</font>' : '<font color=green>(complete)</font>';
?>
					<tr><td><?=$status?></td>
					    <td><b><?=$s['name']?></b>, </td>
					    <td>Grade <?=$s['grade']?>, </td>
					    <td><?=$s['school']?></td>
					</tr>
<?php					if($config['enable_electronic_signatures']) {
						foreach($s['signatures'] as &$sig) { 
							$d = date('F j, g:ia', strtotime($sig['date_signed'])); ?>
							<tr><td colspan="3" align="right"><?=$signature_types[$sig['type']]?> eSig:</td>
							<td>By <b><?=$sig['signed_name']?></b> on <b><?=$d?></b> (<?=$sig['email']?>)</td></tr>
<?php						}
					}
				} ?>
				</table>
				<br/>
				<b>Registration Fee: $<?=$regfee?></b><br/>
<?php				/* CHeck for ethics */
				if($p['ethics']['human1'] != 0 || $p['ethics']['animals'] != 0) {
					/* Needs ethics */
					if(!$p['ethics_approved']) {
						/* Needs ethics, not approved */ ?>
						<b>Ethics: <font color="red">Required but not approved</font></b><br/>
<?php					}
				} ?>

			</div>
			<div class="ui-block-b" style="width:20%">
<?php				if($accepted && $paid) {
					$mark_as_complete_style = 'style="display:none;"';
					$mark_as_incomplete_style = '';
				} else {
					$mark_as_complete_style = '';
					$mark_as_incomplete_style = 'style="display:none;"';
				}
?>

				<a href="#" onclick="input_reg_forms_mark_as_complete(<?=$pid?>)" id="input_reg_forms_c_<?=$pid?>" <?=$mark_as_complete_style?> data-role="button" data-theme="r" >Mark as Complete</a>
				<a href="#" onclick="input_reg_forms_mark_as_incomplete(<?=$pid?>)" id="input_reg_forms_i_<?=$pid?>" <?=$mark_as_incomplete_style?> data-role="button" data-theme="g" >Mark as Incomplete</a>

			</div>
		</div>
	</li>
<?php
}

?>
</ul>

<script>
	function input_reg_forms_mark_as_complete(pid) {
		$.post('c_input_signature_forms.php', { action: "c", pid: pid }, function(data) {
			if(data.status == 0) {
				$('#input_reg_forms_c_'+pid).hide();
				$('#input_reg_forms_w_'+pid).hide();
				$('#input_reg_forms_i_'+pid).show();
			}
		}, "json");
		return false;
	}
	function input_reg_forms_mark_as_incomplete(pid) {
		$.post('c_input_signature_forms.php', { action: "i", pid: pid }, function(data) {
			if(data.status == 0) {
				$('#input_reg_forms_c_'+pid).show();
				$('#input_reg_forms_w_'+pid).show();
				$('#input_reg_forms_i_'+pid).hide();
			}
		}, "json");
		return false;
	}


</script>

			


<?php
sfiab_page_end();
?>
