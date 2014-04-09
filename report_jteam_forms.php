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

$filter_jteam = 0;
if (array_key_exists('id', $_GET)) {
	$filter_jteam = (int)$_GET['id'];
}

$pdf=new pdf( "Judging Team Hand-In Form", $config['year'] );

function td_box()
{
	return "<table><tr>
		<td style=\"border:1px solid black;\" width=\"10mm\" height=\"10mm\">&nbsp;</td>
		<td width=\"5mm\"></td>
		<td style=\"border:1px solid black;\" width=\"10mm\" height=\"10mm\">&nbsp;</td>
	</tr>
	<tr>
		<td align=\"center\"><font size=\"-2\">1-4</font></td>
		<td></td><td align=\"center\"><font size=\"-2\">H/M/L</font></td>
	</tr></table>";
}

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
		if($award['description'] != '')
			$html .= "<tr><td align=\"right\" >Award Description: &nbsp;</td><td>{$award['description']}</td></tr>";
		if($award['criteria'] != '') 
			$html .= "<tr><td align=\"right\" >Award Criteria: &nbsp;</td><td>{$award['criteria']}</td></tr>";
		$html .= "</table><br/><br/><br/>";

		$pdf->WriteHTML($html);

		if($award['type'] == 'divisional') {

			$html = '<table>';
			$html .= '<tr><td></td><td align="center"><b>Scientific Thought</b></td>';
			$html .= '<td align="center"><b>Creativity and <br/>Originality</b></td>';
			$html .= '<td align="center"><b>Communication</b></td>';
			$html .= '</tr>';
			

			$sorted_project_ids = array();
			foreach($jteam['project_ids'] as $pid) {
				$project = &$projects[$pid];
				$sorted_project_ids[$project['number_sort']] = $pid;
			}
			ksort($sorted_project_ids);

			foreach($sorted_project_ids as $pid) {
				$row = array();
				$project = &$projects[$pid];

				$html .= "<tr><td align=\"right\"><b>{$project['number']}  &nbsp;</b></td>";

				for($x=0;$x<3;$x++) {
					$html .= '<td align="center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.td_box().'</td>';
				}
				$html .= '</tr><tr><td colspan="4"><hr/></td></tr>';
			}

			$html.= '</table>';
			$pdf->WriteHTML($html);

		} else {
			/* Use the same logic for cusp and SA teams, except query a different slot type */

			$html = '';
			foreach($award['prizes'] as $p) { 
				$html .= '<h4>'.$p['name'].' - '.$p['number'].' Prizes To Award</h4>';


				$html .= '<table>';
				$html .= "<tr><td></td>";
				$html .= "<td align=\"center\" width=\"30mm\"><b>Project Number</b></td><td></td>";
				$html .= "</tr>";

				for($y=1;$y<=$p['number'];$y++) {
					$html .= "<tr><td align=\"right\" >Award #$y: &nbsp;</td>";
					$html .= "<td style=\"border:1px solid black;\" width=\"30mm\" height=\"10mm\">&nbsp;</td>";
					$html .= "<td></td></tr>";

				}
				$html .= "<tr><td align=\"right\">Backups: &nbsp;</td>";
				$html .= "<td style=\"border:1px solid black;\" width=\"30mm\" height=\"10mm\">&nbsp;</td>";
				$html .= "<td style=\"border:1px solid black;\" width=\"30mm\" height=\"10mm\">&nbsp;</td></tr>";
				$html .= '</table></hr>';
			
			}
			$pdf->WriteHTML($html);
			
		}	
		$pdf->WriteHTML("<br/><br/><br/><h3>Important Notes:</h3>
		<ul>
		<li>Submit this sheet to the Chief Judge!
		</ul>");

	}

}

print($pdf->output());
?>
