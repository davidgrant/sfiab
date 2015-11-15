<?php
require_once('common.inc.php');
require_once('awards.inc.php');

$mysqli = sfiab_init(NULL);

sfiab_page_begin($u, "Winners", "winners");

/* Load year, cast to int */
$year = array_key_exists('year', $_GET) ? (int)$_GET['year'] : '';

/* Load type, make sure it's in the types array */
$type = '';
if(array_key_exists('type', $_GET)) {
	$type = $_GET['type'];
	if(!array_key_exists($type, $award_types)) {
		$type = '';
	}

	/* Don't let 'other' go through */
	if($type =='other') $type = '';
}
	


?>
<div data-role="page" id="winners"><div data-role="main" class="sfiab_page" > 

<?php
	/* Figure out the latest year to show */
	$now = date('Y-m-d', time(NULL));
	$fair_ends = date('Y-m-d', strtotime($config['date_fair_ends']));
	if($now <= $fair_ends) {
		$last_year_to_show = $config['year'] - 1;
	} else {
		$last_year_to_show = $config['year'];
	}

	if($year == 0) {
		/* Get all years and types */
		$q = $mysqli->query("SELECT DISTINCT(year) FROM winners ORDER BY year DESC");
		while($r = $q->fetch_assoc()) {
			$year = (int)$r['year'];

			/* Don't print anything for the current year unless we're on the day after the fair */
			if($year > $last_year_to_show) continue;
?>
			<h3><?=$year?></h3>
			<ul>
			<li><a data-rel="external" data-ajax="false" href="main_winners.php?year=<?=$year?>&type=divisional"><?=$year?> Divisional Award Winners</a>
			<li><a data-rel="external" data-ajax="false" href="main_winners.php?year=<?=$year?>&type=special"><?=$year?> Special Award Winners</a>
			<li><a data-rel="external" data-ajax="false" href="main_winners.php?year=<?=$year?>&type=grand"><?=$year?> Grand Award Winners</a>
			</ul>
<?php
			if($year == $config['year']) {
				print('<hr/>');
			}
		}
	} else {
?>		<h3><?=$year?> <?=$award_types[$type]?> Awards</h3>
<?php
		if($year > $last_year_to_show) {
			/* Shouldn't be able to get here unless someone is fudging with URLs and manually inserting dates.
			 * I bet students will try to do this. */
			print("Crystal Ball Error: Go to the award ceremony to find out who wins.<br/>");
			exit();
		}
		/* Load the winners list for the specific type */
		$q = $mysqli->query("SELECT `awards`.`name` AS award_name,
						`awards`.`s_desc`,
						`award_prizes`.`name` AS prize_name,
						`projects`.`number`,`projects`.`title`
					FROM winners 
						LEFT JOIN award_prizes ON `winners`.`award_prize_id`=`award_prizes`.`id`
						LEFT JOIN awards on `award_prizes`.`award_id` = `awards`.`id`
						LEFT JOIN projects on `winners`.`pid`=`projects`.`pid`
					WHERE
						`awards`.`type`='$type'
						AND `winners`.`year`='$year'
						AND `awards`.`include_in_script`='1'
					ORDER BY
						`awards`.`ord`, `award_prizes`.`ord`, `projects`.`number_sort`
					");
		print($mysqli->error);
		$current_award = NULL;
		$current_prize = NULL;

		$w = array();
		$desc = array();
		while($r = $q->fetch_assoc()) {
			if($r['award_name'] != $current_award) {
				$current_award = $r['award_name'];
				$w[$current_award] = array();
				$desc[$current_award] = $r['s_desc'];
				$current_prize = NULL;
			}
			if($r['prize_name'] != $current_prize ) {
				$current_prize = $r['prize_name'];
				$w[$current_award][$current_prize] = array();
			}
			$w[$current_award][$current_prize][] = array('number' => $r['number'], 'title' => $r['title']);
		}


		foreach($w as $award_name => $prizes) {
?>			<h4><?=$year?> - <?=$award_name?></h4>	

<?php			if($desc[$award_name] != '') { 
?>				<p><blockquote><i><?=$desc[$award_name]?></i></blockquote>
<?php			}
			
?>			<ul>

<?php			foreach($prizes as $prize_name => $projects) {
				if($type == 'divisional') print("<li>$prize_name<ul>");

				foreach($projects as $p) {
?>					<li><a data-rel="external" data-ajax="false" href="project_summary.php?year=<?=$year?>&pn=<?=$p['number']?>"><?=$p['number']?></a> - <?=$p['title']?>
<?php				}
				if($type == 'divisional') print("</ul>");
			}
?>			</ul>
<?php

		}
	}
?>

</div></div>

<?php

sfiab_page_end();


?>
