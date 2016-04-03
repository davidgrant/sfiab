<?php

require_once("common.inc.php");
require_once("user.inc.php");
require_once('project.inc.php');
require_once('awards.inc.php');
require_once('tcpdf.inc.php');

$mysqli = sfiab_init('committee');

$page_id = 'c_ceremony';

$script_debug = array_key_exists('debug',$_GET) ? true : false;
$script_year = array_key_exists('year',$_GET) ? (int)$_GET['year'] : $config['year'];
$script_show_pronunciation = true;
$script_start_award_on_new_page = true;
$script_group_by_prize = array_key_exists('group_by_prize', $_GET) ? (int)$_GET['group_by_prize'] : false;
$script_cats = array_key_exists('cats', $_GET) ? $_GET['cats'] : array();
$script_award_types = array_key_exists('award_types', $_GET) ? $_GET['award_types'] : array();
$script_type = 'pdf';
$script_show_unawarded_awards = false;
$script_show_unawarded_prizes = false;
$script_name = array_key_exists('name',$_GET) ? $_GET['name'] : 'Award Ceremony Script';
$script_slides = array_key_exists('slides', $_GET) ? true : false;

if($script_debug) print("<pre>");

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

/* If no cats are specified, show all */
if(count($script_cats) == 0) {
	$script_cats = array_keys($cats);
}

debug(print_r($cats, true));

debug("Ceremony script options: \n");
debug("   year = $script_year\n");
debug("   slides = ".($script_slides ? 1 : 0)."\n");

if($script_slides) {
	/* One slide per project per award */
	$pdf=new pdf($script_name, $config['year'], "LETTER", "L");
	$pdf->setFontSize(14);
	$pdf->SetFont('freesans');
	$pdf->setup_for_labels(false, false, false, 279, 215, 0, 0, 1, 1);
	generate_title_slide($pdf, $script_name);

} else if($script_type == 'pdf') {
	$pdf=new pdf( $script_name , $script_year );
	$pdf->setFontSize(14);
	$pdf->SetFont('times');

	$pdf->addPage();
	$pdf->writeHTML("<br/><br/><br/><br/><br/><br/><h1>$script_name</h1>");

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




function get_award_info_html($mysqli, &$award)
{
	$sponsor = user_load($mysqli, $award['sponsor_uid']);
	$html = '';
	if($award['type'] != 'divisional')
		$html .= "<tr><td width=\"30mm\" align=\"right\">Sponsored by:</td><td width=\"150mm\">{$sponsor['organization']}</td></tr>";

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

/* Return a list of project IDs that won a prize, filter by 
 * cat and whether the prize was loaded.  If no winners, return
 * an empty array */
function get_winning_project_ids_for_prize(&$prize)
{
	global $winners, $projects, $script_cats;

	$winning_ids = array();
	$prize_id = (int)$prize['id'];

	/* No winners if the prize ID wasn't loaded from the winners table */
	if(!array_key_exists($prize_id, $winners)) {
		return $winning_ids;
	}


	foreach($winners[$prize_id] as $project_id) {
		if(in_array($projects[$project_id]['cat_id'], $script_cats)) {
			$winning_ids[] = $project_id;
		}
	}
	return $winning_ids;
}


/* Get the winners HTML block for a prize.  Filter by the
 * script cats.
 * If there are no winners (or the filter filtered them all)
 * return an empty string */
function get_winners_html_for_prize(&$prize, $title, $winning_project_ids, $show_prize_info=false)
{
	global $winners, $projects, $schools;
	global $script_show_pronunciation, $script_cats;

	$html = "<b>$title</b>";

	/* Show prize info if request and if there is info to show */
	if($show_prize_info != '') {
		$cash = $prize['cash'];
		$scholarship = $prize['scholarship'];
		$p = array();
		if((float)$cash != 0) $p[] = "\$$cash cash";
		if((float)$scholarship != 0) $p[] = "\$$scholarship scholarship";

		if(count($p) > 0) $html .= ' - ('.join(' / ', $p). ')';
	}
	$html .= '<br/>';

	if(count($winning_project_ids) == 0) {
		$html .= '<p>No Winners';
		return $html;
	}
	
	$html .='<table>';
	foreach($winning_project_ids as $project_id) {
		$project =& $projects[$project_id];

		/* Make sure no one is doing something unexpected and passing us a list of winners that
		 * hasn't been filtered by cats */
		assert(in_array($project['cat_id'], $script_cats));

		$pn = $project['number'];
		foreach($project['students'] as $s) {
			$n = '<b>'.$s['name'].'</b>';
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
	return $html;
}

function generate_title_slide(&$pdf, $title)
{
	global $config;
	$pdf->label_new();
	$pdf->label_fair_logo(1,1,30,30,false);
	$pdf->label_text(30, 5, 60, 10, $config['fair_name'], false, 'left','middle');
	$pdf->label_text(30, 17, 50, 8, $config['year'], false, 'left','middle');
	$pdf->label_text(10, 42, 80, 30, $title, false, 'center','middle',3);

}

function generate_prize_slides(&$pdf, &$prize, &$winning_project_ids)
{
	global $projects, $schools, $config;
	$award = $prize['award'];

	foreach($winning_project_ids as $pid) {
		$project =&$projects[$pid];

		$pdf->label_new();
		$pdf->label_fair_logo(1,1,14,14,false);
//		$pdf->label_text(1,25,20,5,$config['year']);
		$pdf->label_line(1,17,99,17);

		if($award['type'] == 'divisional') {
			$pdf->label_text(25, 2, 70, 6, $award['name']);
			$pdf->label_text(25, 9, 70, 6, $prize['name']);
		} else {
			$pdf->label_text(25, 2, 70, 13, $award['name'], false, 'center', 'middle', 2);
		}

		$pn = $project['number'];
		/* Build a unique array of school IDs */
		$sids = array();
		foreach($project['students'] as $s) {
			if(!in_array($s['schools_id'], $sids)) {
				$sids[] = $s['schools_id'];
			}
		}

		$y = 18;

		/* Change layout depending if the image exists */
		$filename = "{$project['number']}.JPG";
		if(!file_exists("files/{$config['year']}/".$filename)) {
			$pdf->label_text(1, 18, 98, 4, $pn, false, 'right');
			$pdf->label_text(5, 30, 90, 14, $project['title'], false, 'center','middle' , 2);
			$y = 50;
			foreach($project['students'] as $s) {
				$pdf->label_text(5, $y, 90, 5, $s['name']);
				$y += 6;
			}
			$y += 8;

			foreach($sids as $sid) {
				$pdf->label_text(5, $y, 90, 5, $schools[$sid]);
				$y += 5;
			}
		} else {
			/* Image file exists */
			$pdf->label_text(1, 18, 98, 4, $pn, false, 'right');
			$pdf->label_text(5, 22, 90, 14, $project['title'], false, 'center','middle' , 2);
			$pdf->label_image(30, 38, 40, 40, "{$project['number']}.JPG");
			$names = array();
			foreach($project['students'] as $s) {
				$names[] = $s['name'];
			}
			$pdf->label_text(5, 75, 90, 10, implode(' & ', $names), false, 'center', 'middle', 2);

			$names = array();
			foreach($sids as $sid) {
				$names[] = $schools[$sid];
			}
			$pdf->label_text(5, 87, 90, 8, implode(' & ', $names), false, 'center', 'middle', 2);
		}
	}


}

if($script_group_by_prize && in_array('divisional', $script_award_types)) {
	/* Go through each divisional award, and build an outer list of prize lists.  The idea is
	 * to group together all the divisional awards sorted by prize order (
	 * which must be the same for all grouped prizes)
	 * (100) Prize-Int HM, Prize S HM
	 * (200) Prize-Int Bronze, Prize S Bronze
	 * ...

	 * We're then going to ksort the list from lowest to highest, and
	 * then display them */
	 
	$outer_prize_list = array();

	foreach($awards as $aid=>&$award) {

		/* Skip non-divisional awards */
		if($award['type'] != 'divisional') continue;

		foreach($award['prizes'] as $prize_id => &$prize) {

			if(!array_key_exists($prize_id, $winners)) {
				$winners[$prize_id] = array();
			}

			if(array_key_exists($prize['ord'], $outer_prize_list)) {
				/* Check that all the prizes here that already have the same name */
				$name = $prize['name'];
				foreach($outer_prize_list[$prize['ord']] as $check_prize) {
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
				$outer_prize_list[$prize['ord']] = array();
			}
			$outer_prize_list[$prize['ord']][] =& $prize;
		}
	}
	ksort($outer_prize_list);
	debug("Generated outer/inner prize list: ".print_r($outer_prize_list, true)."\n");
}


$divisional_printed = false;

foreach($awards as $aid=>&$award) {

	debug("Processing award: {$award['name']}\n");
	
	/* Skip award types we're not displaying */
	if(!in_array($award['type'], $script_award_types)) {
		debug("   skip because not in award types\n");
		continue;
	}

	if($award['type'] == 'divisional' && $script_group_by_prize) {

		/* Skip if we alread printed the divisional awards/prizes */
		if($divisional_printed) continue;

		/* We found the first divisionl award.  Since divisioanl awards are being grouped by prize, we're going to print
		 * all the divisional awards (ordered by prize) now */
		 $divisional_printed = true;

		 debug("   printing divisional awards\n");

		/* Go through the outer prize list and generate the HTML, skipping
		 * prizes with no winners if requested.
		 * The outer prizes should lists of all the divisional HM, Bronze, Silver, Gold prizes */
		foreach($outer_prize_list as $order=>&$prize_list) {

			/* Do we need a header or a title slide? */
			$winning_count = 0;
			foreach($prize_list as $porder=>&$prize) {
				$winning_project_ids = get_winning_project_ids_for_prize($prize);
				$winning_count += count($winning_project_ids);
			}

			debug("   outer prize list $order has $winning_count winners\n");
			if($winning_count == 0 && !$script_show_unawarded_awards) {
				/* No winners, and we're not showing unawarded awards, next outer prize*/
				continue;
			}

			if($script_slides) {
				generate_title_slide($pdf, $prize['name']);
			} else {
				if($script_start_award_on_new_page) {
					$pdf->AddPage();
				}
				/* Ok, we need a title or title slide */
				$html = "<h3>{$prize['name']}</h3>";
			}

			/* Foreach prize in the list */
			foreach($prize_list as $porder=>&$prize) {

				$winning_project_ids = get_winning_project_ids_for_prize($prize);

				if(count($winning_project_ids) == 0 && !$script_show_unawarded_prizes) {
					continue;
				}

				$award =& $prize['award'];

				if($script_slides) {
					generate_prize_slides($pdf, $prize, $winning_project_ids);
				} else {
					$html .= get_winners_html_for_prize($prize, $award['name'], $winning_project_ids, false);
				}
			}

			/* Write out the HTML all at once for the ceremony script */
			if(!$script_slides) {
				$pdf->writeHTML($html);
			}
		}

	} else {

		/* Check if this award has any prizes awarded */
		$winner_count = 0;
		foreach($award['prizes_in_order'] as &$prize) {
			$winning_project_ids = get_winning_project_ids_for_prize($prize);
			$winner_count += count($winning_project_ids);
		}
		debug("   total winners: $winner_count\n");
	
		if($winner_count == 0 && !$script_show_unawarded_awards) {
			continue;
		}
		
		if($script_slides) {
			generate_title_slide($pdf, $award['name']);
		} else {
			if($script_start_award_on_new_page) {
				$pdf->AddPage();
			}
			/* Ok, we need a title or title slide */
			$html = "<h3>{$award['name']}</h3>";
			$html .= get_award_info_html($mysqli, $award);
		}

		foreach($award['prizes_in_order'] as &$prize) {

			$prize_id = $prize['id'];
			debug("   processing prize: {$prize['name']}\n");

			/* This writes to $p_winner_count */
			$winning_project_ids = get_winning_project_ids_for_prize($prize);
			debug("      winning projects: ".join(',', $winning_project_ids)."\n");
			

			if(count($winning_project_ids) == 0 && !$script_show_unawarded_prizes) {
				continue;
			}

			if($script_slides) {
				generate_prize_slides($pdf, $prize, $winning_project_ids);
			} else {
				$html .= get_winners_html_for_prize($prize, $prize['name'], $winning_project_ids, true);
			}
		}

		if(!$script_slides) {
			$pdf->writeHTML($html);
		}
	}
		
}

$pdf->output();

?>
