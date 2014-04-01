<?php

require_once('common.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('timeslots.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

require_once('tcpdf.inc.php');

sfiab_session_start($mysqli, array('committee'));

$u = user_load($mysqli);

$projects = projects_load_all($mysqli);
$timeslots = timeslots_load_all($mysqli);


$pdf=new pdf( "Project Judging Schedule", $config['year'] );

foreach($projects as &$p) {

	if($p['number'] == '') continue;

	$users = user_load_all_for_project($mysqli, $p['pid']);

	$pdf->AddPage();


	$x = $pdf->GetX();
	$y = $pdf->GetY();

	$pdf->SetFont('helvetica');
	$pdf->setFontSize(20);
	$pdf->SetXY(-40, 10);
	$pdf->Cell(30, 0, $p['number'], 0);
	$pdf->barcode_2d(185, $y, 30, 30, $p['pid']);
	$pdf->SetXY($x, $y);
	$pdf->setFontSize(11);

	$n = array();
	foreach($users as &$s) {
		$n[] = $s['name'];
	}
	$names = join(', ', $n);
	
	$pdf->WriteHTML("<h3></h3><p>
		".i18n('Project Title').": <b>{$p['title']}</b> <br/>
		".i18n('Students').": <b>$names</b><br/>");

	$timeslot = array();
	$q = $mysqli->query("SELECT * FROM timeslot_assignments WHERE pid='{$p['pid']}'");
	while($r = $q->fetch_assoc()) {
		if(!array_key_exists($r['num'], $timeslot)) {
			$timeslot[$r['num']] = array();
		}
		$timeslot[$r['num']][] = $r;
	}

	$num = 0;
	for($round=1;$round<=2;$round++) {
		if($round == 1) {
			$pdf->WriteHTML("<h3>".i18n("Round 1 -- April 10, 2pm - 5pm")."</h3>");
		} else {
			$pdf->WriteHTML("<h3>".i18n("Round 2 -- April 10, 6pm - 9pm")."</h3>");
		}
		$pdf->WriteHTML("<br/>");

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
		$round_start_num = ($round == 1) ? 1 : 10;
		$round_end_num = $round_start_num + 9;
		for($num = $round_start_num; $num < $round_end_num; $num++) {
			$row = array();

			
			$ts = $timeslots[$num];
			$row['time'] = date("g:i a", strtotime($ts['start']));

			if(!array_key_exists($num, $timeslot)) {
				$row['slot'] = 'Judging';
			} else {
				$txt = '';
				switch($timeslot[$num][0]['type']) {
				case 'free':
					$txt = "--\nBreak --\n \n";
					break;
				case 'special':
					if($round == 1) {
						$txt = 'Special Awards Judging';
					} else {
						$txt = 'Additional Judging';
					}
					break;
				case 'divisional':
					if($round == 1) {
						$txt = 'Round 1 Divisional Judging';
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
