<?php

require_once('common.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('awards.inc.php');
require_once('timeslots.inc.php');
require_once('committee/students.inc.php');
require_once('committee/judges.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

require_once('tcpdf.inc.php');

sfiab_session_start($mysqli, array('committee'));

$u = user_load($mysqli);

$projects = projects_load_all($mysqli);
$timeslots = timeslots_load_all($mysqli);
$jteams = jteams_load_all($mysqli);
$awards = award_load_all($mysqli);
$judges = judges_load_all($mysqli);

foreach($jteams as &$jteam) {
	$jteam['timeslots'] = array();
}

foreach($projects as &$project) {
	$project['timeslots'] = array();
}
//print("<pre>");

$q = $mysqli->query("SELECT * FROM timeslot_assignments WHERE year='{$config['year']}'");
while($r = $q->fetch_assoc()) {
	$pid = $r['pid'];
	$judge_id = $r['judge_id'];
	$jteam_id = $r['judging_team_id'];
	$timeslot_num = $r['num'];

	$projects[$pid]['timeslots'][$timeslot_num] = $r['type'];

//	print_r($r);
//	print_r($jteams[$jteam_id]);
	

	if($jteam_id == 0) continue;
	$timeslot = &$jteams[$jteam_id]['timeslots'];
	if(!array_key_exists($timeslot_num, $timeslot)) {
		$timeslot[$timeslot_num] = array();
	}

	$timeslot[$timeslot_num][$judge_id] = $pid;

}

$filter_jteam = 0;
if (array_key_exists('id', $_GET)) {
	$filter_jteam = (int)$_GET['id'];
}

$pdf=new pdf( "Judging Team Schedule", $config['year'] );

for($round=1;$round <=2; $round++) {
	foreach($jteams as $jteam_id=>&$jteam) {
		if($jteam['round'] != $round) continue;

		if($filter_jteam != 0 && $jteam_id != $filter_jteam) continue;

		$award = $awards[$jteam['award_id']];
		$n_judges = count($jteam['user_ids']);

		$pdf->AddPage();
		$x = $pdf->GetX();
		$y = $pdf->GetY();
		$pdf->setFontSize(14);
		$pdf->SetXY(-40, 10);
		$pdf->Cell(30, 0, "Round $round", 0);
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

		if($award['type'] == 'divisional' && $round == 1) {

			$x = 0;
			foreach($jteam['user_ids'] as $judge_id) {
				$table['fields'][] = "J$x";
				$table['header']["J$x"] = $judges[$judge_id]['firstname'];
				$table['col']["J$x"] = array('on_overflow' => '',
							      'align' => 'center');
				$table['widths']["J$x"] = 30;
				$x++;
			}

			for($num = 1; $num <=9; $num++) {
				$row = array();

				$ts = $timeslots[$num];
				$row['time'] = date("g:i a", strtotime($ts['start']));

				$x = 0;
				foreach($jteam['user_ids'] as $judge_id) {

					$jteam_timeslots = &$jteam['timeslots'];
					$txt = '';
					/* Is $judge_id on $jteam_id assigned to a project in slot number $num ? */
					if(array_key_exists($num, $jteam_timeslots)) {
						if(array_key_exists($judge_id, $jteam_timeslots[$num])) {
							$txt = $projects[$jteam_timeslots[$num][$judge_id]]['number'];
						} 
					}
					$row["J$x"] = $txt;
					$x++;
				}
				$table['data'][] = $row;
			}

			$pdf->add_table($table);
			$pdf->WriteHTML("<br/><br/><br/><h3>Important Notes:</h3>
			<ul>
			<li>Remember to fill out your ranking forms and submit them to the chief judge.  Without them we won't know which projects won.
			</ul>");



		} else {
			/* Use the same logic for cusp and SA teams, except query a different slot type */
			if($award['type'] == 'divisional' && $round == 2) 
				$slot_type = 'divisional';
			else 
				$slot_type = 'special';


			$table['header']['time'] = 'Project';
			for($x = 0; $x < 9; $x++) {
				$num  = ($round == 1) ? ($x+1) : ($x + 10);
				$ts = $timeslots[$num];

				$table['fields'][] = "T$x";
				$table['header']["T$x"] = date("g:i", strtotime($ts['start']));
				$table['col']["T$x"] = array('on_overflow' => '',
							      'align' => 'center');
				$table['widths']["T$x"] = 20;
			}

			$sorted_project_ids = array();
			foreach($jteam['project_ids'] as $pid) {
				$project = &$projects[$pid];
				$sorted_project_ids[$project['number_sort']] = $pid;
			}
			ksort($sorted_project_ids);
		

			foreach($sorted_project_ids as $pid) {
				$row = array();
				$project = &$projects[$pid];

				$row['time'] = $project['number'];

				$x = 0;
				for($x = 0; $x < 9; $x++) {
					$num  = ($round == 1) ? ($x+1) : ($x + 10);

					if(!array_key_exists($num, $project['timeslots'])) {
						
						print("<pre>Timeslot $num doesn't exist in timeslots for pid:$pid: ");
						print_r($project);
						exit();
					}

					$txt = '';
					if($project['timeslots'][$num] == $slot_type) {
						$txt = 'O';
					}
					$row["T$x"] = $txt;
				}
				$table['data'][] = $row;
			}

			$pdf->add_table($table);
			$pdf->WriteHTML("<br/><br/><br/><h3>Important Notes:</h3>
			<ul>
			<li>Remember to fill out your ranking forms and submit them to the chief judge.  Without them we won't know which projects won.
			</ul>");
		}	

	}

}

print($pdf->output());
?>
