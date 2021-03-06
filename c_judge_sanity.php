<?php

require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('committee/judges.inc.php');
require_once('awards.inc.php');
require_once('timeslots.inc.php');

$mysqli = sfiab_init('committee');

$u = user_load($mysqli);

$page_id = 'c_judge_sanity';

sfiab_page_begin($u, "Judging", $page_id);

/* Count judges */
$judges = judges_load_all($mysqli, $config['year']);
$jteams = jteams_load_all($mysqli);
$awards = award_load_all($mysqli);
$projects = projects_load_all($mysqli);
$timeslots = timeslots_load_all($mysqli);

/* $notices is divided into sections, and then a check type.  The first element of the check type array
 * is displayed if no other messages are added to it. It's like the "everything is ok" message.
 * if anything else is added, the first element of the array is ignored.
 * Messages can start with OK = green OK:, NO = red Notice:, ER = red Error:, IN = blue info */

/* Jteam stanity */

/* Check that each round1 divisional jteam has exactly 3 judges, list the ones that don't */
$notices = array();

/* Count round0 and 1 div jteams */
$round0_div_jteam_count = 0;
$round1_div_jteam_count = 0;
foreach($jteams as &$jteam) {
	if($jteam['round'] == 0 && $awards[$jteam['award_id']]['type'] == 'divisional') {
		$round0_div_jteam_count += 1;
	}
	if($jteam['round'] == 1 && $awards[$jteam['award_id']]['type'] == 'divisional') {
		$round1_div_jteam_count += 1;
	}
}
	

$notices['Judging Teams'] = array();
if($config['judge_div_shuffle']) {
	$notices['Judging Teams']['r1div_judges'] = array("OK All <b>$round0_div_jteam_count</b> Round 1 Divisional Judging Teams have enough judges to judge each project {$config['div_times_each_project_judged']} times");
} else {
	$notices['Judging Teams']['r1div_judges'] = array("OK All <b>$round0_div_jteam_count</b> Round 1 Divisional Judging Teams have {$config['div_times_each_project_judged']} judges");
}

$num_timeslots = count($timeslots);
if($num_timeslots > 1) {
	$notices['Judging Teams']['r2div_judges'] = array("OK All <b>$round1_div_jteam_count</b> Round 2 Divisional (Cusp) Judging Teams have {$config['judge_cusp_max_team']} judges");
}
$notices['Judging Teams']['bad_projects'] = array("OK Projects assigned to all Judging Teams are accepted and exist");
$notices['Judging Teams']['sa_judges'] = array("OK All Special Award Judging Teams have (at most) {$config['judge_sa_max_projects']} projects per judge");

foreach($jteams as &$jteam) {

	if($config['judge_div_shuffle']) {
		$judges_required = (int)((count($jteam['project_ids']) * $config['div_times_each_project_judged']) / $num_timeslots) + 1;
	} else {
		$judges_required = $config['div_times_each_project_judged'];
	}

	if($jteam['round'] == 0 && $awards[$jteam['award_id']]['type'] == 'divisional') {
		$c = count($jteam['user_ids']);
		/* round1 divisional */
		if($c < $config['div_times_each_project_judged']) {
			$notices['Judging Teams']['r1div_judges'][] = "NO Round 1 Divisional Judging Team <b>{$jteam['name']}</b> has <b>$c</b> judges, but needs <b>$judges_required</b> so that each project can be judged <b>{$config['div_times_each_project_judged']}</b> times";
		}
	}
	if($jteam['round'] == 1 && $awards[$jteam['award_id']]['type'] == 'divisional') {
		$c = count($jteam['user_ids']);
		/* round1 divisional */
		if($c < $config['judge_cusp_max_team']) {
			$notices['Judging Teams']['r2div_judges'][] = "NO Round 2 Divisional (Cusp) Judging Team <b>{$jteam['name']}</b> has <b>$c</b> judges.  Not {$config['judge_cusp_max_team']}";
		}
	}
	/* Make sure all projects exist */
	foreach($jteam['project_ids'] as $pid) {
		if(!array_key_exists($pid, $projects)) {
			$notices['Judging Teams']['bad_projects'][] = "ER Judging <b>{$jteam['name']}</b> is assigned (pid:$pid), but it doesn't exist.  Project deleted or not accepted?";
		}
	}

	if($awards[$jteam['award_id']]['type'] == 'special') {
		$c = count($jteam['user_ids']);
		$p = count($jteam['project_ids']);
		if($c * (int)$config['judge_sa_max_projects'] < $p) {
			$notices['Judging Teams']['sa_judges'][] = "NO Special Award Judging Team <b>{$jteam['name']}</b> has <b>$c</b> judges and <b>$p</b> projects.  More than {$config['judge_sa_max_projects']} projects per judge";
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
			$notices['Awards']['all_sa'][] = "ER Award <b>{$award['name']}</b> is marked 'Schedule Judges', but has no Judging Team";
		}
	}
}

$num_projects = count($projects);

$notices['Projects'] = array();
$notices['Projects']['r1assigned'] = array("OK All $num_projects projects have a Round 1 Divisional judging team");
$notices['Projects']['unavailable'] = array("OK No Projects have restricted timeslot availability requirements");
$notices['Projects']['disqualified_from_awards'] = array("OK No Projects have been disqualifed from winning awards");
foreach($projects as $pid=>&$p) {
	/* Check that each project is judged in round 1 */
	$found = false;
	foreach($jteams as &$jteam) {
		if($jteam['round'] == 0 && $awards[$jteam['award_id']]['type'] == 'divisional') {
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
		$notices['Projects']['unavailable'][] = "IN Project (id:$pid) <b>\"{$p['number']}\"</b> has been marked as unavailabled in the following timeslots: ".join(',', $p['unavailable_timeslots']);
	}

	if($p['disqualified_from_awards']) {
		$notices['Projects']['disqualified_from_awards'][] = "IN Project (id:$pid) <b>\"{$p['number']}\"</b> has been marked as <b>Disqualified From Awards</b>";
	}
		
}


?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php	form_page_begin($page_id, array());


?>	

	<h3>Judging Sanity Checks</h3> 
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
				case 'IN':
					print("<font color=green>Info:</font>");
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
