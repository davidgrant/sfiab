<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('project_number.inc.php');
require_once('tcpdf.inc.php');


$mysqli = sfiab_init('committee');

$u = user_load($mysqli);

$roles = array();

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}
if(array_key_exists('action', $_GET) && $_GET['action']=='print') {
	$action = 'print';
}

switch($action) {
case 'savenote':
	post_int($uid, 'uid');
	post_text($note, 'note');
	if($uid <= 0) exit();

	$uu = user_load($mysqli, $uid);
	$uu['notes'] = $note;
	user_save($mysqli, $uu);
	form_ajax_response(0);
	exit();

case 'checkin':
	post_int($uid, 'uid');
	post_bool($checkin, 'checkin');
	if($uid <= 0) exit();

	$uu = user_load($mysqli, $uid);
	$uu['checked_in'] = $checkin;
	user_save($mysqli, $uu);

	/* Let the GUI know it was successfully saved */
	form_ajax_response(0);
	exit();

case 'tshirt':
	post_int($uid, 'uid');
	post_bool($t, 'tshirt');
	if($uid <= 0) exit();

	$uu = user_load($mysqli, $uid);
	$uu['tshirt_given'] = $t;
	user_save($mysqli, $uu);

	/* Let the GUI know it was successfully saved */
	form_ajax_response(0);
	exit();
}

function l_projects_load_all($mysqli, $year, &$u)
{
	/* Load projects first */
	$q = $mysqli->query("SELECT * FROM projects WHERE year='$year' AND accepted='1' ORDER BY number_sort ");
	$projects_tmp = array();
	while($p = $q->fetch_assoc()) {
		$p_temp = project_load($mysqli, $p['pid'], $p);

		if($p_temp['number_sort'] == 0) {
			$p_temp['number_sort'] = $p['pid'];
		}
		$projects_tmp[$p['pid']] = $p_temp;
	}

	$projects = array();
	/* Now match users to projects, copying projects
	 * into the real return array as we find them */
	$q = $mysqli->query("SELECT users.*,schools.school FROM users 
					LEFT JOIN schools ON users.schools_id=schools.id
				WHERE users.year='$year'
				AND users.enabled = '1'
				AND users.s_accepted = '1'
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

		$projects[$pid]['visit'] = false;
		$projects[$pid]['visit_notes'] = '';
		
	}

	$q = $mysqli->query("SELECT pid,notes,visit FROM visit_list WHERE uid='{$u['uid']}'");
	while($d = $q->fetch_row()) {
		$pid = (int)$d[0];
		$notes = $d[1];
		$visit = (int)$d[2];
		if(array_key_exists($pid, $projects)) {
			$projects[$pid]['visit'] = $visit;
			$projects[$pid]['visit_notes'] = $notes;
		}
	}

	return $projects;
}


$page_id = 'c_checkin_list';
$help = '<p>';


sfiab_page_begin($u, "Checkin List", $page_id, $help);

?>


<div data-role="page" id="<?=$page_id?>" class="sfiab_page" > 

	<h3>Visit List</h3>

	<ul data-role="listview" data-filter="true" data-filter-placeholder="Search by project number, project ID, project title, student name, school name..." data-inset="true">

<?php


$projects = l_projects_load_all($mysqli, $config['year'], $u);

$sorted_project_ids = array();
foreach($projects as &$p) {
	$sorted_project_ids[$p['number_sort']] = $p['pid'];
}
ksort($sorted_project_ids);

foreach($sorted_project_ids as $pid) {
	$p = &$projects[$pid];

	if($p['s_complete'] == false) continue;

	$filter_text = "{$p['pid']} {$p['title']} {$p['visit_notes']} Project {$p['number']}";
	if($p['visit']) $filter_text .= ' visit';

	$accepted = $p['students'][0]['s_accepted'] ? true : false;
	foreach($p['students'] as &$s) {
		$filter_text .= " {$s['name']} {$s['school']}";
		if($accepted != $s['s_accepted'])
			$accepted = false;
	}

?>
	<li id="received_form_<?=$p['pid']?>" data-filtertext="<?=$filter_text?>">
		<h3>Project <?=$p['number']?>: <?=$p['title']?></h3>
<?php		
		foreach($p['students'] as &$s) {
			$uid = $s['uid'];
			$form_id = $page_id.$uid;

			if($s['checked_in']) {
				$ci_style =  '';
				$cb_style = 'style="display:none;"';
			} else {
				$ci_style = 'style="display:none;"';
				$cb_style = '';
			} 
			if($s['tshirt_given']) {
				$ti_style =  '';
				$tb_style = 'style="display:none;"';
			} else {
				$ti_style = 'style="display:none;"';
				$tb_style = '';
			} 
			

			?>
			<?=$s['name']?>, Grade <?=$s['grade']?>, <?=$s['school']?><br/>

			<div class="ui-grid-d" data-role="fieldcontain">
			<div class="ui-block-a" style="width:10%">
			</div>
			<div class="ui-block-b" style="width:20%">
				<button disabled="disabled" id="checkin1_<?=$uid?>" data-icon="check" data-theme="g" <?=$ci_style?> data-inline="true" >Checked In</button>
				<a href="#" onclick="checkin_checkin(<?=$uid?>, 1)" id="checkin0_<?=$uid?>" <?=$cb_style?> data-inline="true" data-role="button"  >Check In</a>

			</div>
			<div class="ui-block-c" style="width:20%">
<?php				if($config['tshirt_enable']) {
					$size = $s['tshirt'];
					if($size != 'none') { ?>
						T-shirt: 
						<button disabled="disabled" id="tshirt1_<?=$uid?>" data-icon="check" data-theme="g" <?=$ti_style?> data-inline="true"  >Given <?=$tshirt_sizes[$size]?></button>
						<a href="#" onclick="checkin_tshirt(<?=$uid?>, 1)" id="tshirt0_<?=$uid?>" <?=$tb_style?> data-inline="true" data-role="button"  ><?=$tshirt_sizes[$size]?></a>
<?php					} else { ?>
						T-shirt <button disabled="disabled" data-inline="true"  >None</button>
<?php					} 
				}  ?>
			</div>
			<div class="ui-block-d" style="width:40%">
<?php				form_begin($form_id, 'c_checkin.php');
				form_hidden($form_id, 'uid', $uid); ?>
				<table><tr><td width="90%">
<?php				form_text_inline($form_id, 'note', $s['notes'], 'text');  ?>
				</td><td>	
<?php				form_submit($form_id, 'savenote', 'Save', 'Saved'); ?>
				</td></tr></table>
<?php				form_end($form_id); ?>
			</div>
			<div class="ui-block-e" style="width:10%">
				<div data-role="collapsible" data-collapsed="true" data-iconpos="right" data-collapsed-icon="carat-d" data-expanded-icon="carat-u" data-inline="true" >
					<h3>More</h3>
					<a href="#" onclick="checkin_checkin(<?=$uid?>, 0)" id="uncheckin_<?=$uid?>" data-inline="true" data-role="button" data-theme='r'  >Un-Check In</a><br/>
					<a href="#" onclick="checkin_tshirt(<?=$uid?>, 0)" id="untshirt_<?=$uid?>" data-inline="true" data-role="button" data-theme='r'  >Un-Tshirt</a>
				</div>
			</div>
			</div>
<?php		} ?>

	</li>
<?php
}

?>
</ul>

<script>
	function checkin_checkin(uid,checkin) {
		$.post('c_checkin.php', { action: "checkin", uid: uid, checkin: checkin }, function(data) {
			if(data.status == 0) {
				if(checkin == 1) {
					$('#checkin0_'+uid).hide();
					$('#checkin1_'+uid).show();
				} else {
					$('#checkin0_'+uid).show();
					$('#checkin1_'+uid).hide();
				}
			}
		}, "json");
		return false;
	}
	function checkin_tshirt(uid,tshirt) {
		$.post('c_checkin.php', { action: "tshirt", uid: uid, tshirt: tshirt }, function(data) {
			if(data.status == 0) {
				if(tshirt == 1) {
					$('#tshirt0_'+uid).hide();
					$('#tshirt1_'+uid).show();
				} else {
					$('#tshirt0_'+uid).show();
					$('#tshirt1_'+uid).hide();
				}
			}
		}, "json");
		return false;
	}
</script>

<?php
sfiab_page_end();
?>
