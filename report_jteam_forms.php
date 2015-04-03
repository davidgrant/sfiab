<?php

require_once('common.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('awards.inc.php');
require_once('timeslots.inc.php');
require_once('committee/students.inc.php');
require_once('committee/judges.inc.php');
require_once('tcpdf.inc.php');

$mysqli = sfiab_init('committee');

$u = user_load($mysqli);

$projects = projects_load_all($mysqli);
$timeslots = timeslots_load_all($mysqli);
$jteams = jteams_load_all($mysqli);
$awards = award_load_all($mysqli);
$judges = judges_load_all($mysqli);


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

foreach($generate_rounds as $round ) {
	$ts = &$timeslots_by_round[$round];
	$timeslot_id = $ts['id'];

	foreach($jteams as $jteam_id=>&$jteam) {
		if($jteam['round'] != $round) continue;

		if($filter_jteam != 0 && $jteam_id != $filter_jteam) continue;

		$award = $awards[$jteam['award_id']];

		if(!in_array($award['type'], $generate_types)) {
			continue;
		}
		
		$n_judges = count($jteam['user_ids']);

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
			$html .= "<tr><td align=\"right\" >Student Description: &nbsp;</td><td>{$award['s_desc']}</td></tr>";
		if($award['j_desc'] != '') 
			$html .= "<tr><td align=\"right\" >Judge Information: &nbsp;</td><td>{$award['j_desc']}</td></tr>";
		$html .= "</table><br/><br/><br/>";

		$pdf->WriteHTML($html);

		$sorted_project_ids = array();
		foreach($jteam['project_ids'] as $pid) {
			$project = &$projects[$pid];
			$sorted_project_ids[$project['number_sort']] = $pid;
		}
		ksort($sorted_project_ids);

		if($award['type'] == 'divisional' && $round != 1) {

			$html = '<table>';
			$html .= '<tr><td></td><td align="center"><b>Scientific Thought</b></td>';
			$html .= '<td align="center"><b>Creativity and <br/>Originality</b></td>';
			$html .= '<td align="center"><b>Communication</b></td>';
			$html .= '</tr><tr><td colspan="4"><hr/></td></tr>';
			
			foreach($sorted_project_ids as $pid) {
				$row = array();
				$project = &$projects[$pid];

				$short_title = htmlentities(substr($project['title'], 0, 65));
				if(strlen($project['title']) > 65) $short_title .= "...";

				$html .= "<tr><td align=\"right\"><b>{$project['number']}</b> &nbsp;<br/><font size=\"+2\">&nbsp;</font><font size=\"-3\">$short_title</font></td>";

				for($x=0;$x<3;$x++) {
					$html .= '<td align="center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.td_box().'</td>';
				}
				$html .= '</tr><tr><td colspan="4"><hr/></td></tr>';
			}

			$html.= '</table>';
			$pdf->WriteHTML($html);

		} else if($award['type'] == 'divisional' && $round == 1) {
			/* Special CUSP report, print the exact number of boxes with the exact number of UP and DOWN prize names, e.g., so 
			 * the judges assign 3 gold and 4 silver */

			/* The up prize is the prize attached to the jteam */
			$prize = $award['prizes'][$jteam['prize_id']];

			/* The down prize is the previous one in the sorted prize list, could 
			 * be NULL (Nothing) */
			foreach($award['prizes_in_order'] as &$p) {
				debug("{$award['name']}:{$p['name']}\n");
			}

			unset($down_prize);
			$down_prize = NULL;
			foreach($award['prizes_in_order'] as &$p) {
				if($p['id'] == $jteam['prize_id']) {
					break;
				}
				$down_prize = &$p;
			}

			$down_name = ($down_prize === NULL) ? 'Nothing' : $down_prize['name'];
			$n_up = $jteam['cusp_n_up'];
			$n_down = count($jteam['project_ids']) - $n_up;

			$html = "<h4>CUSP Instructions: Assign $n_up {$prize['name']} and $n_down $down_name</h4>";

			$html .= '<br/>&nbsp;<br/>';
			$html .= '<table>';
			$html .= "<tr><td align=\"center\" width=\"80mm\">";

			foreach($sorted_project_ids as $pid) {

				$project = &$projects[$pid];

				$short_title = htmlentities(substr($project['title'], 0, 50));
				if(strlen($project['title']) > 50) $short_title .= "...";

				$html .= "<b>{$project['number']}</b><font size=\"-3\"><br/>$short_title<br/><br/></font>";
			}

			$html .= '</td>';
			
			$html .= '<td width="80mm">';
			$html .= '<table><tr><td width="45mm">&nbsp;</td>';
			$html .= '<td align="center" width="30mm"><b>Project Number<br/></b></td></tr>';

			for($x=0; $x<$n_up; $x++) {
				$html .= "<tr><td width=\"45mm\" align=\"right\"><font size=\"+2\">&nbsp;{$prize['name']}</font>: &nbsp;&nbsp;</td>";
				$html .= '<td style="border:1px solid black;" width="30mm" height="10mm">&nbsp;</td></tr>';
			}
			$html .= "<tr><td align=\"center\" colspan=\"2\"><font size=\"+2\"><br/>All remaining projects will be awarded <b>$down_name</b></font></td></tr>";
			$html .= '</table>';

			$html .= '</td>';
			$html .= '</tr></table></hr>';
			$pdf->WriteHTML($html);
				
		} else {
			/* Use the same logic for cusp and SA teams, except query a different slot type */

			$html = '';
			foreach($award['prizes_in_order'] as $p) { 
				$plural = ($p['number'] == 1) ? '' : 's';
				$html .= '<h4>'.$p['name'].' - '.$p['number']." Prize$plural To Award</h4>";


				$html .= '<table>';
				$html .= "<tr><td></td>";
				$html .= "<td align=\"center\" width=\"30mm\"><b>Project Number</b></td><td></td>";
				$html .= "</tr>";

				for($y=1;$y<=$p['number'];$y++) {
					$html .= "<tr><td align=\"right\"><font size=\"+5\">&nbsp;</font>Award #$y: &nbsp;</td>";
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
		$pdf->WriteHTML("<br/><h3>Important Notes:</h3>
		<ul>
		<li>Submit this form to the Chief Judge!
		</ul>");

	}

}

print($pdf->output());
?>
