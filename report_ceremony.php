<?php

require_once("common.inc.php");
require_once("user.inc.php");
require_once('project.inc.php');
require_once('awards.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

require_once('tcpdf.inc.php');

sfiab_session_start($mysqli, array('committee'));

$page_id = 'c_ceremony';

$script_debug = array_key_exists('debug',$_GET) ? true : false;
$script_year = array_key_exists('year',$_GET) ? $_GET['year'] : $config['year'];
$script_show_pronunciation = true;
$script_start_award_on_new_page = true;
$script_group_by_prize = array_key_exists('group_by_prize', $_GET) ? (int)$_GET['group_by_prize'] : false;
$script_cats = array_key_exists('cats', $_GET) ? $_GET['cats'] : array();
$script_award_types = array_key_exists('award_types', $_GET) ? $_GET['award_types'] : array();
$script_type = 'pdf';
$script_show_unawarded_awards = false;
$script_show_unawarded_prizes = false;
$script_name = array_key_exists('name',$_GET) ? $_GET['name'] : 'Award Ceremony Script';

if($script_debug) print("<pre>");
function debug($str) {
	global $script_debug;
	if($script_debug) print($str);
}

$projects = projects_load_all($mysqli, $script_year);
foreach($projects as &$p) {
	project_load_students($mysqli, $p);
}

$q=$mysqli->query("SELECT id,school FROM schools WHERE year='{$script_year}' ORDER by city,school");
while($r=$q->fetch_assoc()) {
	$schools[$r['id']] = $r['school'];
}

$cats = categories_load($mysqli, $script_year);
$chals = challenges_load($mysqli, $script_year);
$awards = award_load_all($mysqli, $script_year);

foreach($cats as $cid=>$c) {
	$script_show_categories[$cid] = false;
}

debug(print_r($cats, true));

if($script_type == 'pdf') {
	$pdf=new pdf( $script_name , $script_year );
	$pdf->setFontSize(14);
	$pdf->SetFont('times');
} else {
	$pdf = new csv($script_name, $script_year );
}

$winners = array();
$q = $mysqli->query("SELECT * FROM winners WHERE year='$script_year'");
while($r = $q->fetch_assoc()) {
	$prize_id = (int)$r['award_prize_id'];
	if(!array_key_exists($prize_id, $winners)) {
		$winners[$prize_id] = array();
	}
	$winners[$prize_id][] = (int)$r['pid'];
}





function get_award_info_html(&$award)
{
	$html = '';
	if($award['type'] != 'divisional')
		$html .= "<tr><td width=\"30mm\" align=\"right\">Sponsored by:</td><td width=\"150mm\">{$award['sponsor']}</td></tr>";

	if($award['presenter'] != '')
		$html .= "<tr><td width=\"30mm\" align=\"right\">Presented by:</td><td width=\"150mm\">{$award['presenter']}</td></tr>";
	if($award['s_desc'] != '') 
		$html .= "<tr><td width=\"30mm\" align=\"right\"t>Description:</td><td width=\"150mm\">{$award['s_desc']}</td></tr>";

	/* Did we do anything? */
	if($html == '') {
		return '';
	} 
	$html .= "<tr><td></td><td></td></td>";

	return "<table>$html</table>";
}

function get_prize_info_html(&$prize)
{
	$cash = $prize['cash'];
	$scholarship = $prize['scholarship'];

	$html = '';
	$p = array();
	if((float)$cash != 0) $p[] = "\$$cash cash";
	if((float)$scholarship != 0) $p[] = "\$$scholarship scholarship";

	if(count($p) > 0) $html .= '('.join(' / ', $p). ')<br/>';

	return $html;
}

/* Get the winners HTML block for a prize.  Filter by the
 * script cats.
 * If there are no winners (or the filter filtered them all)
 * return an empty string */
function get_winners_html_for_prize(&$prize, &$winner_count)
{
	global $winners, $projects, $schools;
	global $script_show_pronunciation, $script_cats;

	$winner_count = 0;
	/* No winners if the prize ID wasn't loaded from the winners table */
	if(!array_key_exists($prize['id'], $winners)) {
		return '';
	}

	$html = '<table>';
	foreach($winners[$prize['id']] as $project_id) {
		$project =& $projects[$project_id];

		if(!in_array($project['cat_id'], $script_cats)) continue;

		$winner_count += 1;

		$pn = $project['number'];
		foreach($project['students'] as $s) {
			$n = $s['name'];
			if($script_show_pronunciation && trim($s['pronounce']) != '') {
				$n .= "<br/>&nbsp;&nbsp;&nbsp;({$s['pronounce']})";
			}

			$html .= "<tr><td width=\"25mm\" align=\"center\">$pn</td>";
			$html .= "<td width=\"80mm\">$n</td>";
			$html .= "<td width=\"80mm\">{$schools[$s['schools_id']]}</td></tr>";
			$pn = '';
		}


		$html .= "<tr><td></td><td></td><td></td></tr>";
		$html .= "<tr><td></td><td colspan=\"2\">{$project['title']}</td></tr>";
		$html .= "<tr><td></td><td></td><td></td></tr>";
		$html .= "<tr><td></td><td></td><td></td></tr>";
	}
	$html .= '</table>';

	if($winner_count == 0) {
		return '';
	}
	return $html;
}


$pdf->AddPage();



if(!$script_group_by_prize) {

	foreach($awards as $aid=>&$award) {

		/* Skip award types we're not displaying */
		if(!in_array($award['type'], $script_award_types)) continue;

		
		$a_html = '';
		$a_html .= "<h3>{$award['name']}</h3>";
		$a_html .= get_award_info_html($award);

		$award_winner_count = 0;
		$p_html = '';
		foreach($award['prizes'] as $prize_id => &$prize) {

			if($prize['include_in_script'] == 0) continue;

			$p_html = '';

			$p_html .= "<b>{$prize['name']}</b>";
			$i_html = get_prize_info_html($prize);
			if($i_html != '') {
				$p_html .= ' - '.$i_html;
			}
			$p_html .= '<br/>';

			$p_winner_count = 0;
			/* This writes to $p_winner_count */
			$w_html = get_winners_html_for_prize($prize, $p_winner_count);

			if($p_winner_count == 0 && !$script_show_unawarded_prizes) {
				continue;
			}

			$a_html .= $p_html . $w_html;
			$award_winner_count += $p_winner_count;
		}

		if($award_winner_count == 0 && !$script_show_unawarded_awards) {
			continue;
		}

		if($script_start_award_on_new_page) 
			$pdf->AddPage();

		$pdf->writeHTML($a_html);

	}

} else {


	/* Go through each award, and build an outer list of prize lists.  The idea is
	 * to group together all the divisional awards sorted by prize order (
	 * which must be the same for all grouped prizes)
	 * (100) Prize-Int HM, Prize S HM
	 * (200) Prize-Int Bronze, Prize S Bronze
	 * ...

	 * We're then going to ksort the list from lowest to highest, and
	 * then display them */
	 
	$outer_prize_list = array();

	foreach($awards as $aid=>&$award) {

		/* Skip award types we're not displaying */
		if(!in_array($award['type'], $script_award_types)) continue;

		foreach($award['prizes'] as $prize_id => &$prize) {

			if($prize['include_in_script'] == 0) continue;

			if(!array_key_exists($prize_id, $winners)) {
				$winners[$prize_id] = array();
			}

			if(array_key_exists($prize['order'], $outer_prize_list)) {
				/* Check that all the prizes here that already have the same name */
				$name = $prize['name'];
				foreach($outer_prize_list[$prize['order']] as $check_prize) {
					if($check_prize['name'] != $name) {
						/* prize order exists, but the name doesn't match */
						print("Prize order/name mismatch for group_by_prize");
						print("<pre>");
						print_r($prize);
						print("Total List:");

						print_r($outer_prize_list);
						exit();
					}
				}
			} else {
				$outer_prize_list[$prize['order']] = array();
			}
			$outer_prize_list[$prize['order']][] =& $prize;
		}
	}

	ksort($outer_prize_list);

	/* Now go through the outer prize list and generate the HTML, skipping
	 * prizes with no winners if requested */
	foreach($outer_prize_list as $order=>&$prize_list) {

		$html = '';

		$header_printed = false;
		$outer_winner_count = 0;

		/* Foreach prize in the list */
		foreach($prize_list as $porder=>&$prize) {
			if(!$header_printed) {
				/* Print the major h3 prize header 8/
				/* Print the minor header */
				$html .= "<h3>{$prize['name']}</h3>";
				$header_printed = true;
			}

			$award =& $prize['award'];

			/* Add a minor header for the award */
			$minor_html = "<b>{$award['name']}</b><br/>";

			$p_winner_count = 0;
			/* This writes to $p_winner_count */
			$w_html = get_winners_html_for_prize($prize, $p_winner_count);

			if($p_winner_count == 0) {
				if($script_show_unawarded_awards) {
					$w_html = '<p>No Winners';
				} else {
					continue;
				}
			}
			$outer_winner_count += $p_winner_count;

			$html .= $minor_html . $w_html;
		}

		if($outer_winner_count == 0 && !$script_show_unawarded_prizes) {
			continue;
		}

		if($script_start_award_on_new_page) 
			$pdf->AddPage();

		$pdf->writeHTML($html);
	}
}
$pdf->output();

?>
