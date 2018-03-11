<?php

require_once('common.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('awards.inc.php');
require_once('timeslots.inc.php');
require_once('committee/students.inc.php');
require_once('committee/judges.inc.php');
require_once('debug.inc.php');
require_once('tcpdf.inc.php');

$mysqli = sfiab_init('committee');

$u = user_load($mysqli);


$projects = projects_load_all($mysqli);
$timeslots = timeslots_load_all($mysqli);
$jteams = jteams_load_all($mysqli);
$awards = award_load_all($mysqli);
$judges = judges_load_all($mysqli);

debug("Loaded ".count($projects)." projects\n");


$generate_rounds = array();
for($round=0;$round<count($timeslots); $round++) {
	if(!array_key_exists('round', $_GET) || intval($_GET['round']) == $round) {
		$generate_rounds[] = $round;
	}
}

$generate_types = array();
if(!array_key_exists('type', $_GET) || $_GET['type'] == 'divisional') {
	$generate_types[] = 'divisional';
}
if(!array_key_exists('type', $_GET) || $_GET['type'] == 'special') {
	$generate_types[] = 'special';
	$generate_types[] = 'grand';
	$generate_types[] = 'other';
}

debug("Generate Rounds: ".print_r($generate_rounds, true)."\n");


foreach($jteams as &$jteam) {
	$jteam['timeslots'] = array();
	foreach($timeslots as $timeslot_id=>&$ts) {
		$jteam['timeslots'][$timeslot_id] = array();
	}
}

foreach($projects as &$project) {
	$project['timeslots'] = array();
	foreach($timeslots as $timeslot_id=>&$ts) {
		$project['timeslots'][$timeslot_id] = array();
	}
}

/* Create an index of timeslots by round too */
$timeslots_by_round = array();
foreach($timeslots as $tid=>&$ts) {
	$timeslots_by_round[$ts['round']] = $ts;
}

$q = $mysqli->query("SELECT * FROM timeslot_assignments WHERE year='{$config['year']}'");
while($r = $q->fetch_assoc()) {
	$pid = $r['pid'];
	$judge_id = $r['judge_id'];
	$jteam_id = $r['judging_team_id'];
	$timeslot_num = $r['timeslot_num'];
	$timeslot_id = $r['timeslot_id'];

	if(!array_key_exists($pid, $projects)) {
		print("Project $pid is assigned to judging, but wasn't loaded by project_load_all");
		exit();
	}

	/* Make a list of slot types for each round for each project */
	$projects[$pid]['timeslots'][$timeslot_id][$timeslot_num] = $r['type'];

	/* There isn't always a jteam, but if there is, link it to the
	 * project too */
	if($jteam_id != 0) {
		$timeslot = &$jteams[$jteam_id]['timeslots'][$timeslot_id];
		if(!array_key_exists($timeslot_num, $timeslot)) {
			$timeslot[$timeslot_num] = array();
		}
		$timeslot[$timeslot_num][$judge_id] = $pid;
	}

}

$filter_jteam = 0;
if (array_key_exists('id', $_GET)) {
	$filter_jteam = (int)$_GET['id'];
}

$pdf=new pdf( "Judging Team Schedule", $config['year'] );

/* Do the rounds in order, we built this array in order */
foreach($generate_rounds as $round ) {
	$ts = &$timeslots_by_round[$round];
	$timeslot_id = $ts['id'];

	foreach($jteams as $jteam_id=>&$jteam) {
		if($jteam['round'] != $round) continue;

		if($filter_jteam != 0 && $jteam_id != $filter_jteam) continue;

		if(!array_key_exists($jteam['award_id'], $awards)) {
			/* A judging team with no award can happen if they create a judging team and don't assign it to an award. */
			continue;
		}
		$award = $awards[$jteam['award_id']];
		$n_judges = count($jteam['user_ids']);

		if(!in_array($award['type'], $generate_types)) {
			continue;
		}

		$pdf->AddPage();
		$x = $pdf->GetX();
		$y = $pdf->GetY();
		$pdf->setFontSize(14);
		$pdf->SetXY(-40, 10);
		$pdf->Cell(30, 0, $ts['name'], 0);
		$pdf->SetXY($x, $y);
		$pdf->setFontSize(11);

		$n = array();
		foreach($jteam['user_ids'] as $judge_id) {
			$n[] = $judges[$judge_id]['name'];
		}
		$names = join(', ', $n);


		$html = "<h3>{$jteam['name']}</h3><br/><br/>
			<table>
			<tr><td align=\"right\" width=\"40mm\">Team Members: &nbsp;</td><td width=\"150mm\"><b>$names</b></td></tr>
			<tr><td></td><td></td></tr>
			<tr><td align=\"right\" width=\"40mm\">Award: &nbsp;</td><td><b>{$award['name']}</b></td></tr>";
		if($award['s_desc'] != '')
			$html .= "<tr><td align=\"right\" >Award Description: &nbsp;</td><td>{$award['s_desc']}</td></tr>";
		if($award['j_desc'] != '') 
			$html .= "<tr><td align=\"right\" >Award Judging Info: &nbsp;</td><td>{$award['j_desc']}</td></tr>";
		$html .= "</table><br/><br/><br/>";

		$pdf->WriteHTML($html);

		$table = array('col'=>array(), 'widths'=>array() );
		$table['fields'] = array('time');
		$table['header']['time'] = 'Time';
		$table['col']['time'] = array('on_overflow' => '',
					      'align' => 'center');
		$table['widths']['time'] = 20;
		$table['total'] = 0;
		$table['data'] = array();

		if(count($jteam['user_ids']) == 0) {
			$pdf->WriteHTML("<h3>No Judges Assigned</h3>");
			continue;
		}
		
		if($award['type'] == 'divisional' && $round == 0) {
			/* Round 1 */
			$x = 0;
			$width = 180 / count($jteam['user_ids']);
			if($width > 30) $width = 30;
			foreach($jteam['user_ids'] as $judge_id) {
				$table['fields'][] = "J$x";
				$table['header']["J$x"] = $judges[$judge_id]['firstname'];
				$table['col']["J$x"] = array('on_overflow' => '',
							      'align' => 'center');
				$table['widths']["J$x"] = $width;
				$x++;
			}

			for($itimeslot=0; $itimeslot<$ts['num_timeslots']; $itimeslot++) {

				$row = array();
				$row['time'] = date("g:i a", $ts['timeslots'][$itimeslot]['start_timestamp']);

				$x = 0;
				foreach($jteam['user_ids'] as $judge_id) {

					$round_timeslots = &$jteam['timeslots'][$timeslot_id];
					$txt = '';
					/* Is $judge_id on $jteam_id assigned to a project in slot number $num ? */
					if(array_key_exists($itimeslot, $round_timeslots)) {
						if(array_key_exists($judge_id, $round_timeslots[$itimeslot])) {
							$txt = $projects[$round_timeslots[$itimeslot][$judge_id]]['number'];
							if(!array_key_exists('number', $projects[$round_timeslots[$itimeslot][$judge_id]])) {
								print_r($projects[$round_timeslots[$itimeslot][$judge_id]]);
							}
						} 
					}
					$row["J$x"] = $txt;
					$x++;
				}
				$table['data'][] = $row;
			}

			$pdf->add_table($table);
			$pdf->WriteHTML("<br/><br/>
			<ul>
			<li><b>Remember to fill out your ranking forms and submit them to the chief judge.  Without them we won't know which projects won.</b>
			</ul>");



		} else {
			/* Use the same logic for cusp and SA teams, except query a different slot type */
			if($award['type'] == 'divisional' && $round == 1) 
				$slot_type = 'divisional';
			else 
				$slot_type = 'special';


			$table['header']['time'] = 'Project';
			/* 9 timeslots with width 20 fit, more than 9, scale appropriately */
			$width = ($ts['num_timeslots'] <= 9) ? 20 : (180 / $ts['num_timeslots']);
			for($itimeslot=0; $itimeslot<$ts['num_timeslots']; $itimeslot++) {
				$table['fields'][] = "T$itimeslot";
				$table['header']["T$itimeslot"] = date("g:i", $ts['timeslots'][$itimeslot]['start_timestamp']);
				$table['col']["T$itimeslot"] = array('on_overflow' => '',
							      'align' => 'center');
				$table['widths']["T$itimeslot"] = $width;
			}
			debug(print_r($table, true));

			$sorted_project_ids = array();
			foreach($jteam['project_ids'] as $pid) {
				$project = &$projects[$pid];
				$sorted_project_ids[$project['number_sort']] = $pid;
			}
			ksort($sorted_project_ids);
		

			$showed_vbar = false;
			foreach($sorted_project_ids as $pid) {
				$row = array();
				$project = &$projects[$pid];

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
					} else if($round != 0 && $slot_type == 'special' && $project['timeslots'][$timeslot_id][$itimeslot] == 'divisional') {
						$txt = '+';
						$showed_vbar =true;
					}
					$row["T$itimeslot"] = $txt;
				}
				$table['data'][] = $row;
			}


			$pdf->add_table($table);
			$pdf->WriteHTML("<br/><ul><li>O = Student is availble for judging.</li>".
			($showed_vbar ? '<li>&nbsp;+ = Student is availble for other judges. You may judge the student but other judges have priority.</li>' : '').
			"<br/><li><b>Remember to fill out your ranking forms and submit them to the chief judge.  Without them we won't know which projects won.</b></ul>");
		}	

	}

}

print($pdf->output());
?>
