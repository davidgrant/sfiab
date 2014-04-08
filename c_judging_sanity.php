<?php

require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('committee/judges.inc.php');
require_once('awards.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('committee'));

$u = user_load($mysqli);

$page_id = 'c_judging_sanity';

sfiab_page_begin("Judging", $page_id);

/* Count judges */
$judges = judges_load_all($mysqli, $config['year']);
$jteams = jteams_load_all($mysqli);
$awards = award_load_all($mysqli);
$projects = projects_load_all($mysqli);

/* $notices is divided into sections, and then a check type.  The first element of the check type array
 * is displayed if no other messages are added to it. It's like the "everything is ok" message.
 * if anything else is added, the first element of the array is ignored.
 * Messages can start with OK = green OK:, NO = red Notice:, ER = red Error: */

/* Jteam stanity */

/* Check that each round1 divisional jteam has exactly 3 judges, list the ones that don't */
$notices = array();

$notices['Judging Teams'] = array();
$notices['Judging Teams']['r1div_judges'] = array("OK All Round 1 Divisional Judging Teams have {$config['judge_div_min_team']}-{$config['judge_div_max_team']} judges");
$notices['Judging Teams']['r2div_judges'] = array("OK All Round 2 Divisional (Cusp) Judging Teams have {$config['judge_cusp_min_team']}-{$config['judge_cusp_max_team']} judges");
$notices['Judging Teams']['bad_projects'] = array("OK Projects assigned to all Judging Teams are accepted and exist");
foreach($jteams as &$jteam) {
	if($jteam['round'] == 1 && $awards[$jteam['award_id']]['type'] == 'divisional') {
		$c = count($jteam['user_ids']);
		/* round1 divisional */
		if($c < $config['judge_div_min_team'] || $c > $config['judge_div_max_team']) {
			$notices['Judging Teams']['r1div_judges'][] = "NO Round 1 Divisional Juding Team {$jteam['name']} has $c judges.  Not {$config['judge_div_min_team']}-{$config['judge_div_max_team']}";
		}
	}
	if($jteam['round'] == 2 && $awards[$jteam['award_id']]['type'] == 'divisional') {
		$c = count($jteam['user_ids']);
		/* round1 divisional */
		if($c < $config['judge_cusp_min_team'] || $c > $config['judge_cusp_max_team']) {
			$notices['Judging Teams']['r2div_judges'][] = "NO Round 2 Divisional (Cusp) Juding Team {$jteam['name']} has $c judges.  Not {$config['judge_cusp_min_team']}-{$config['judge_cusp_max_team']}";
		}
	}
	/* Make sure all projects exist */
	foreach($jteam['project_ids'] as $pid) {
		if(!array_key_exists($pid, $projects)) {
			$notices['Judging Teams']['bad_projects'][] = "ER Judging {$jteam['name']} is assigned (pid:$pid), but it doesn't exist.  Deleted project?";
		}
	}
}

$notices['Awards'] = array();
$notices['Awards']['all_sa'] = array("OK All Special Awards Marked as 'Schedule Judges' have a Judging Team");
foreach($awards as &$award) {
	if($award['type'] == 'special' && $award['schedule_judges'] == 1) {
		/* Find the judging team */
		$found = false;
		foreach($jteams as &$jteam) {
			if($jteam['award_id'] == $award['id']) {
				$found = true;
				break;
			}
		}
		if($found == false) {
			$notices['Awards']['all_sa'][] = "ER Award \"{$award['name']}\" is marked 'Schedule Judges', but has no Judging Team";
		}
	}
}

$num_projects = count($projects);

$notices['Projects'] = array();
$notices['Projects']['r1assigned'] = array("OK All $num_projects projects have a Round 1 Divisional judging team");
$notices['Projects']['unavailable'] = array("OK No Projects have restricted timeslot availability requirements");
foreach($projects as $pid=>&$p) {
	/* Check that each project is judged in round 1 */
	$found = false;
	foreach($jteams as &$jteam) {
		if($jteam['round'] == 1 && $awards[$jteam['award_id']]['type'] == 'divisional') {
			if(in_array($pid, $jteam['project_ids'])) {
				$found = true;
				break;
			}
		}
	}
	if(!$found) {
		$notices['Projects']['r1assigned'][] = "ER Project (id:$pid) <b>\"{$p['number']}\"</b> is not assigned to a Round 1 Judging Team.";
	}

	/* Info notices for timeslot execptions */
	if(count($p['unavailable_timeslots'])) {
		$notices['Projects']['unavailable'][] = "NO Project (id:$pid) <b>\"{$p['number']}\"</b> has been marked as unavailabled in the following timeslots: ".join(',', $p['unavailable_timeslots']);
	}
}


?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php	form_page_begin($page_id, array());


?>	

	<h3>Sanity Checks</h3> 
	<ul data-role="listview" data-inset="true">
<?php
	foreach($notices as $sec=>$ns) { ?>
		<li data-role="list-divider"><?=$sec?></li>
<?php		foreach($ns as $type=>$n) { 
			if(count($n) == 1) {
				$ns_to_display = $n;
			} else{
				$ns_to_display = array_slice($n, 1);
			}
			foreach($ns_to_display as $txt) {
				$n_type = substr($txt, 0, 2);

				print("<li>");
				switch(substr($txt, 0, 2)) {
				case 'OK':
					print("<font color=green>OK:</font>");
					break;
				case 'NO':
					print("<font color=red>Notice:</font>");
					break;
				case 'ER':
					print("<font color=red>ERROR:</font>");
					break;
				}
				print(substr($txt, 2));
				print("</li>");
			}
		}
	} ?>
	</ul>

</div></div>
	

<?php
sfiab_page_end();
?>
