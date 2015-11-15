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
case 'save':
	$pid = (int)$_POST['pid'];
	if($pid > 0) {
		$visit = 0;
		post_int($visit, 'visit');
		$notes = $mysqli->real_escape_string($_POST['notes']);
		$mysqli->real_query("DELETE FROM visit_list WHERE uid='{$u['uid']}' AND pid='$pid'");
		print($mysqli->error);
		$mysqli->real_query("INSERT INTO visit_list(`uid`,`pid`,`notes`,`visit`) VALUES('{$u['uid']}','$pid','$notes','$visit')");
		print($mysqli->error);
		form_ajax_response(0);
		exit();
	}
	form_ajax_response(1);
	exit();

case 'print':

	$projects = l_projects_load_all($mysqli, $config['year'], $u);
	$timeslots = timeslots_load_all($mysqli);

	$generate_rounds = array();
	for($round=0;$round<count($timeslots); $round++) {
		if(!array_key_exists('round', $_GET) || intval($_GET['round']) == $round) {
			$generate_rounds[] = $round;
		}
	}

	/* Create an index of timeslots by round too */
	$timeslots_by_round = array();
	foreach($timeslots as $tid=>&$ts) {
		$timeslots_by_round[$ts['round']] = $ts;
	}

	foreach($projects as &$project) {
		$project['timeslots'] = array();
		foreach($timeslots as $timeslot_id=>&$ts) {
			$project['timeslots'][$timeslot_id] = array();
		}
	}

	$q = $mysqli->query("SELECT * FROM timeslot_assignments WHERE year='{$config['year']}'");
	while($r = $q->fetch_assoc()) {
		$pid = $r['pid'];
		$judge_id = $r['judge_id'];
		$jteam_id = $r['judging_team_id'];
		$timeslot_num = $r['timeslot_num'];
		$timeslot_id = $r['timeslot_id'];

		/* Make a list of slot types for each round for each project */
		$projects[$pid]['timeslots'][$timeslot_id][$timeslot_num] = $r['type'];
	}
	

	$pdf=new pdf( "Visit List", $config['year'] );

	foreach($generate_rounds as $round ) {
		$ts = &$timeslots_by_round[$round];
		$timeslot_id = $ts['id'];
		/* Do the rounds in order, we built this array in order */
		$pdf->AddPage();

		
		$x = $pdf->GetX();
		$y = $pdf->GetY();
		$pdf->setFontSize(14);
		$pdf->SetXY(-40, 10);
		$pdf->Cell(30, 0, $ts['name'], 0);
		$pdf->SetXY($x, $y);
		$pdf->setFontSize(11);

		$n = array();
		$html = "<h3>{$u['firstname']}'s Visit List</h3><br/><br/><br/>";
		$pdf->WriteHTML($html);

		$table = array('col'=>array(), 'widths'=>array() );

		$table['fields'] = array('time');
		$table['header']['time'] = 'Time';
		$table['col']['time'] = array('on_overflow' => '',
					      'align' => 'center');
		$table['widths']['time'] = 20;
		$table['total'] = 0;
		$table['data'] = array();


		/* Use the same logic for cusp and SA teams, except query a different slot type */
		$slot_type = 'special';

		$table['header']['time'] = 'Project';
		for($itimeslot=0; $itimeslot<$ts['num_timeslots']; $itimeslot++) {
			$table['fields'][] = "T$itimeslot";
			$table['header']["T$itimeslot"] = date("g:i", $ts['timeslots'][$itimeslot]['start_timestamp']);
			$table['col']["T$itimeslot"] = array('on_overflow' => '',
						      'align' => 'center');
			$table['widths']["T$itimeslot"] = 20;
		}

		$sorted_project_ids = array();
		foreach($projects as &$p) {
			$sorted_project_ids[$p['number_sort']] = $p['pid'];
		}
		ksort($sorted_project_ids);

		$showed_vbar = false;
		foreach($sorted_project_ids as $pid) {
			$row = array();
			$project = &$projects[$pid];

			if($project['visit'] == 0) continue;

			$row['time'] = $project['number'];

			for($itimeslot=0; $itimeslot<$ts['num_timeslots']; $itimeslot++) {

					if($project['timeslots'][$timeslot_id] == NULL) {
						print("project $pid timeslots [$timeslot_id] is nULL\n");
						print_r($project);
					}

					if(!array_key_exists($itimeslot, $project['timeslots'][$timeslot_id])) {
						
						print("<pre>Timeslot $itimeslot doesn't exist in timeslots[$timeslot_id] for pid:$pid: ");
						print_r($project);
						exit();
					}

			
				$txt = '';
				if($project['timeslots'][$timeslot_id][$itimeslot] == $slot_type) {
					$txt = 'O';
				} else if($slot_type == 'special' && $project['timeslots'][$timeslot_id][$itimeslot] == 'divisional') {
					$txt = '+';
					$showed_vbar =true;
				}
				$row["T$itimeslot"] = $txt;
			}
			$table['data'][] = $row;
		}


		$pdf->add_table($table);
		$pdf->WriteHTML("<br/><ul><li>O = Student has Divisional Judging</li>".
				($showed_vbar ? '<li>&nbsp;+ = Student has Special Awards Judging</li>' : '').
				"</ul>");
	}

	print($pdf->output());
	exit();
}

function l_projects_load_all($mysqli, $year, &$u)
{
	/* Load projects first */
	$q = $mysqli->query("SELECT * FROM projects WHERE year='$year' ORDER BY number_sort ");
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


$page_id = 'c_visit_list';
$help = '<p>';


sfiab_page_begin($u, "Visit List", $page_id, $help);

?>


<div data-role="page" id="<?=$page_id?>" class="sfiab_page" > 

	<h3>Visit List</h3>

	<ul data-role="listview" data-filter="true" data-filter-placeholder="Search by project number, project title, student name, school name..." data-inset="true">

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
<?php		$form_id = $page_id.$p['pid'];
		form_begin($form_id, 'c_visit_list.php');
		form_hidden($form_id, 'pid', $p['pid']);
		?>
		<div class="ui-grid-b" data-role="fieldcontain">
			<div class="ui-block-a" style="width:60%">
				<table>
<?php				foreach($p['students'] as &$s) {
?>
					<tr>
					    <td><?=$s['name']?>, </td>
					    <td>Grade <?=$s['grade']?>, </td>
					    <td><?=$s['school']?></td>
					</tr>
<?php				} ?>
				</table>
				<br/>
			</div>
			<div class="ui-block-b" style="width:30%">
<?php				form_text($form_id, 'notes', NULL, $p['visit_notes'], 'text');
				$data = array('1'=>'Visit');
				form_check_group($form_id, 'visit', NULL, $data, $p['visit']);
?>
			</div>
			<div class="ui-block-c" style="width:10%">
<?php				form_submit($form_id, 'save', 'Save', 'Saved');?>
			</div>
				
			
		</div>
<?php		form_end($form_id); ?>		
	</li>
<?php
}

?>
</ul>

<?php
sfiab_page_end();
?>
