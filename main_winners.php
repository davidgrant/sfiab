<?php
require_once('common.inc.php');
require_once('awards.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start();

sfiab_page_begin("Winners", "winners");

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
	if($year == 0) {
		/* Get all years and types */
		$q = $mysqli->query("SELECT DISTINCT(year) FROM winners ORDER BY year DESC");
		while($r = $q->fetch_assoc()) {
			$year = (int)$r['year'];
?>
			<h3><?=$year?></h3>
			<ul>
			<li><a data-external=true href="main_winners.php?year=<?=$year?>&type=divisional"><?=$year?> Divisional Award Winners</a>
			<li><a data-external=true href="main_winners.php?year=<?=$year?>&type=special"><?=$year?> Special Award Winners</a>
			<li><a data-external=true href="main_winners.php?year=<?=$year?>&type=grand"><?=$year?> Grand Award Winners</a>
			</ul>
<?php
		}
	} else {
?>		<h3><?=$year?> <?=$award_types[$type]?> Awards</h3>
<?php
		/* Load the winners list for the specific type */
		$q = $mysqli->query("SELECT `awards`.`name` AS award_name,
						`awards`.`description`,
						`award_prizes`.`name` AS prize_name,
						`projects`.`number`,`projects`.`title`
					FROM winners 
						LEFT JOIN award_prizes ON `winners`.`awards_prizes_id`=`award_prizes`.`id`
						LEFT JOIN awards on `award_prizes`.`award_id` = `awards`.`id`
						LEFT JOIN projects on `winners`.`projects_id`=`projects`.`pid`
					WHERE
						`awards`.`type`='$type'
						AND `winners`.`year`='$year'
					ORDER BY
						`awards`.`order`, `award_prizes`.`order`, `projects`.`number_sort`
					");

		$current_award = NULL;
		$current_prize = NULL;

		$w = array();
		$desc = array();
		while($r = $q->fetch_assoc()) {
			if($r['award_name'] != $current_award) {
				$current_award = $r['award_name'];
				$w[$current_award] = array();
				$desc[$current_award] = $r['description'];
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

<?php			if($r['description'] != '') { 
?>				<p><?=$r['description']?>
<?php			}
			
?>			<ul>

<?php			foreach($prizes as $prize_name => $projects) {
				if($type == 'divisional') print("<li>$prize_name<ul>");

				foreach($projects as $p) {
?>					<li><a href="project_summary.php?pn=<?=$p['number']?>"><?=$p['number']?></a> - <?=$p['title']?>
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
