<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('timeslots.inc.php');
require_once('committee/students.inc.php');
require_once('tcpdf.inc.php');

$mysqli = sfiab_init('committee');

$u = user_load($mysqli);

$projects = projects_load_all($mysqli);
$timeslots = timeslots_load_all($mysqli);
$students = students_load_all_accepted($mysqli);

/* Build a list of timeslots by round */
$timeslots_by_round = array();
foreach($timeslots as $tid=>&$ts) {
	$timeslots_by_round[$ts['round']] = $ts;
}

/* For each loaded project, add some more info */
foreach($projects as $pid=>&$p) {
	$p['students'] = array();
	$p['timeslots'] = array();
	foreach($timeslots as $timeslot_id=>&$ts) {
		$p['timeslots'][$timeslot_id] = array();
	}
}

/* Add student info to projects */
foreach($students as $uid=>&$s) {
	/* Cross link them with projects */
	$pid = $s['s_pid'];
	if(!array_key_exists($pid, $projects)) continue;
	$projects[$pid]['students'][] = $s;
}

/* Build a list of timeslot assignments for each project.  There could be more than one assignment
 * for a project, so store it as an array, e.g. let's say 18 is the timeslot ID for round 0 
 * $project[pid][timeslots][18][0] = array of timeslot assignments
 * $project[pid][timeslots][18][1] = empty if no assignments
 * $project[pid][timeslots][18][2] = array of timeslot assignments
 * ...
 * $project[pid][timeslots][18][num_timeslots_in_ts_18] = array of timeslot assignments
 */
$q = $mysqli->query("SELECT * FROM timeslot_assignments WHERE year='{$config['year']}'");
while($r = $q->fetch_assoc()) {
	$pid = $r['pid'];
	$timeslot_id = $r['timeslot_id'];
	$timeslot_num = $r['timeslot_num'];

	$timeslot = &$projects[$pid]['timeslots'][$timeslot_id];

	if(!array_key_exists($r['timeslot_num'], $timeslot)) {
		$timeslot[$timeslot_num] = array();
	}
	$timeslot[$timeslot_num][] = $r;
}

/* Special command line options for generating a single scheudle */
$project_numbers = array();
$project_floor_numbers = array();
if(array_key_exists('pn', $_GET)) {
	$project_numbers[] = $_GET['pn'];
} else if (array_key_exists('p', $_GET)) {
	$project_floor_numbers[] = (int)$_GET['p'];
}

$pdf=new pdf( "Project Judging Schedule", $config['year'] );

$filter_project_numbers = (count($project_numbers) > 0) ? true : false;
$filter_project_floor_numbers = (count($project_floor_numbers) > 0) ? true : false;

$pdf->SetFont('helvetica');

//print("<pre>");
foreach($projects as $pid=>&$p) {

//	print_r($p);

	if($p['number'] == '') continue;

	if($filter_project_numbers) {
		if(!in_array($p['number'], $project_numbers)) continue;
	}
	if($filter_project_floor_numbers) {
		if(!in_array($p['floor_number'], $project_floor_numbers)) continue;
	}

	$pdf->AddPage();

	$x = $pdf->GetX();
	$y = $pdf->GetY();
	$pdf->setFontSize(20);
	$pdf->SetXY(-40, 10);
	$pdf->Cell(30, 0, $p['number'], 0);
	$pdf->barcode_2d(185, $y, 30, 30, "reg.gvrsf.ca/?p={$p['floor_number']}");
	$pdf->SetXY($x, $y);
	$pdf->setFontSize(11);

	$n = array();
	foreach($p['students'] as &$s) {
		$n[] = $s['name'];
	}
	$names = join(', ', $n);
	
	$pdf->WriteHTMLCell(175, '', '', '', "<h3>{$p['title']}</h3>".i18n('Students').": <b>$names</b><br/>", 0, 2);
//	$pdf->SetXY($x, $y + 20);


	/* Do rounds in order */
	foreach($timeslots_by_round as $round=>&$ts) {

		/* Get a pointer to the timeslot list for this project and round */
		$ptimeslot = &$p['timeslots'][$ts['id']];

		$start_date = date('F j, g:ia', $ts['start_timestamp']); /* April 10 2:00pm */
		$end_date = date('g:ia', $ts['end_timestamp']); /* 5:00pm */
		$pdf->WriteHTML("<h3>".i18n("{$ts['name']} -- $start_date - $end_date")."</h3><br/>");

		$table = array('col'=>array(), 'widths'=>array() );
		$table['fields'] = array('time','slot');
		$table['header']['time'] = 'Time';
		$table['header']['slot'] = 'Judging';
		$table['col']['time'] = array('on_overflow' => '',
		 			      'align' => 'center');
		$table['col']['slot'] = array('on_overflow' => '',
		 			      'align' => 'center');
		$table['widths']['time'] = 20;
		$table['widths']['slot'] = 80;
		$table['total'] = 0;

		$table['data'] = array();
		for($itimeslot = 0; $itimeslot<$ts['num_timeslots']; $itimeslot++) {
			$row = array();

			$row['time'] = date("g:i a", $ts['timeslots'][$itimeslot]['start_timestamp']);

			/* No information? Just call it a judging timeslot */
			if(!array_key_exists($itimeslot, $ptimeslot)) {
				$row['slot'] = 'Judging';
			} else {
				$txt = '';
				switch($ptimeslot[$itimeslot][0]['type']) {
				case 'free':
					$txt = "-- Break --";
					break;
				case 'special':
					if($round == 0) {
						$txt = 'Special Awards Judging';
					} else {
						$txt = 'Additional Judging';
					}
					break;
				case 'divisional':
					if($round == 0) {
						$txt = $ts['name'].' Divisional Judging';
					} else {
						$txt = 'Additional Judging';
					}
					break;
				}

				$row['slot'] = $txt;
			}
			$table['data'][] = $row;

		}

		$pdf->add_table($table);
		$pdf->WriteHTML("<br/><br/><br/>");

//		$html=$pdf->get_table_html($table);
//		$pdf->WriteHTML($html);

	}

	$pdf->WriteHTML("<h3>Important Notes:</h3>
	<ul><li>You must be at your project at all times, except during the Breaks indicated on your schedule.
	<li>You may not get a judge during every judging time slot.
	<li>Please don't leave your project just because you don't have a judge at the beginning of a time slot, you might miss a judge.
	<li>Special Award Judges, VIPs, and Committee Members may visit your project at any time during judging time slots (except during your breaks).
	<li>Please remain inside the exhibit halls during your breaks.  You are free move between Ballroom and the Partyroom, and of course go to the washroom.
	</ul>");

}

print($pdf->output());
?>
