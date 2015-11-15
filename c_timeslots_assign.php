<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('timeslots.inc.php');
require_once('committee/judges.inc.php');
require_once('awards.inc.php');


$mysqli = sfiab_init('committee');

$u = user_load($mysqli);

$timeslots = timeslots_load_all($mysqli);
$jteams = jteams_load_all($mysqli);
$awards = award_load_all($mysqli);
$projects = projects_load_all($mysqli);

$action = array_key_exists('action', $_POST) ? $_POST['action'] : '';

switch($action) {
case 'go':
	$error = do_timeslot_assignments_from_scratch($mysqli);
	if($error == '') {
		form_ajax_response(0);
	} else {
		form_ajax_response(array('status'=>0, 'error'=>$error));
	}
	exit();
		
}


function do_timeslot_assignments_from_scratch($mysqli)
{
	global $timeslots, $jteams, $awards, $projects;
	global $config, $debug;

	$timeslot_type_str = array(0=>"divisional", 1 => "special", 2=>"free");

	/* Sanity checks */
	foreach($jteams as $jteam_id=>&$jteam) {
		if($jteam['round'] != 1) continue;
		if($awards[$jteam['award_id']]['type'] != 'divisional') continue;

		if(count($jteam['user_ids']) != 3) {
			$error = "Rount 1 Divisional JTeam {$jteam['name']} has ".count($jteam['user_ids']).", not 3";
			return $error;
		}
	}


	$mysqli->query("DELETE FROM timeslot_assignments WHERE year='{$config['year']}'");
	foreach($jteams as $jteam_id=>&$jteam) {
		if($jteam['round'] != 1) continue;
		if($awards[$jteam['award_id']]['type'] != 'divisional') continue;

		$timeslot_judge = array();
		$timeslot_type = array();

		for($iproject=0;$iproject<count($jteam['project_ids']);$iproject++) {
			$timeslot_judge[$iproject] = array();
			$timeslot_type[$iproject] = array();
			for($y=0;$y<18;$y++) {
				$timeslot_judge[$iproject][$y] = 0;
				$timeslot_type[$iproject][$y] = '';
			}
		}

		$start_judge_index = 0;
		$judge_index = 0;
		$start_type = 0;
		$n_rise = 1;
		$n_run = 0;

		$target_rise_run = count($jteam['project_ids']) / count($jteam['user_ids']);
		
		for($iproject=0;$iproject<count($jteam['project_ids']);$iproject++) {
			$pid = $jteam['project_ids'][$iproject];

			$current_timeslot_type = $start_type;
			$judge_index = $start_judge_index;

			/* Go down all 9 timeslots and fill them out */
			for($y=0;$y<9;$y++) {
				$timeslot_type[$iproject][$y] = $current_timeslot_type;
				if($current_timeslot_type == 0) {
					/* Divisional */
					$timeslot_judge[$iproject][$y] = $jteam['user_ids'][$judge_index];
					$judge_index++;
					if($judge_index == count($jteam['user_ids'])){
						$judge_index = 0;
					}
				}
				$current_timeslot_type++;
				if($current_timeslot_type == 3) $current_timeslot_type = 0;
			}

			$start_judge_index++;
			if($start_judge_index == count($jteam['user_ids'])) {
				$start_judge_index = 0;
			}

			$n_run += 1;
			if($n_run / $n_rise >= $target_rise_run) {
				$n_rise += 1;
				$start_type -= 1;
				if($start_type == -1) $start_type = 2;
			}

			/* Copy the round1 to round2 */
			for($y=0;$y<9;$y++) {
				$timeslot_type[$iproject][$y+9] = $timeslot_type[$iproject][$y];
			}

			/* Check for round2 missing timeslots and move them if we can to a free one */
			for($y=9;$y<18;$y++) {
				if($timeslot_type[$iproject][$y] == 2) continue;
				$timeslot_num = $y+1;
				if(in_array($timeslot_num, $projects[$pid]['unavailable_timeslots'])) {
					/* Move it elsewhere if we can */
					for($z=9;$z<18;$z++) {
						$z_timeslot_num = $z+1;
						/* Can't go here if it's an unavailable slot, or if there's already a non-break */
						if(in_array($z_timeslot_num, $projects[$pid]['unavailable_timeslots'])) continue;
						if($timeslot_type[$iproject][$z] != 2) continue;

						/* Move it */
						$timeslot_type[$iproject][$z] = $timeslot_type[$iproject][$y];
						$timeslot_type[$iproject][$y] = 2;
					}
				}
			}
		}

		if($debug) {
			debug("   Timeslot assignments for {$jteam['name']}");
			debug(print_r($jteam, true));
			foreach($jteam['project_ids'] as $pid) {
				debug("\t$pid");
			}
			debug("\n");
		}

		$queries = array();
		for($y=0;$y<9;$y++) {
			debug($y+1);
			for($iproject=0;$iproject<count($jteam['project_ids']);$iproject++) {
				$pid = $jteam['project_ids'][$iproject];
				$judge_id = $timeslot_judge[$iproject][$y];

				if($debug) {
					if($timeslot_type[$iproject][$y] == 0) 
						debug("\t$judge_id");
					else if($timeslot_type[$iproject][$y] == 2) 
						debug("\t--");
					else
						debug("\tsa");
				}

				/* Round 1 */
				$timeslot_num = $y+1;
				$t_type = $timeslot_type[$iproject][$y];

				if($t_type == 0) {
					/* Divisional */
					$t_id = $jteam['id'];
					$j_id = $judge_id;
				} else {
					$j_id = 0;
					$tid = 0;
				}
					
				if(in_array($timeslot_num, $projects[$pid]['unavailable_timeslots'])) {
					$t_type = 2;
				}
				$queries[] = "('$timeslot_num','$pid','$t_id','$j_id','{$timeslot_type_str[$t_type]}', '{$config['year']}')";

				/* Round 2 */
				$t_type = $timeslot_type[$iproject][$y+9];
				$timeslot_num = $y+1 + 9;
				if(in_array($timeslot_num, $projects[$pid]['unavailable_timeslots'])) {
					$t_type = 2;
				}
				$queries[] = "('$timeslot_num','$pid','0','0','{$timeslot_type_str[$t_type]}', '{$config['year']}')";
			}
			debug("\n");
		}
		$query = "INSERT INTO timeslot_assignments (`num`,`pid`,`judging_team_id`,`judge_id`,`type`,`year`) VALUES ";

		$query .= join(',',$queries);
		$mysqli->query($query);
	}
	return '';
}


$page_id = 'c_timeslots_assign';

sfiab_page_begin($u, "Timeslot Assignments", $page_id);



?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 
<?php
	form_page_begin($page_id, array());
?>
	<h3>Assign Timeslots</h3>

	<p>For this to work, all divisional round 1 judging teams must have 3
	judges.  Project must be assigned to the round 1 divisional teams too.
	Special awards don't need assignments.

	<p>Here's what will happen:
	<ul><li>All existing judge timeslot assignments and project timeslot assignments will be deleted
	<li>A timetable will be computed for all projects that has slots for:
		<ul><li>divisional judges
		<li>special awards judges
		<li>free time
		</ul>
	<li>All judges on round 1 divisional judging teams will be assigned a specific timeslot to visit each project.  These will match up
	with divisional timeslots assigned to each project.

	<li>NO special awards teams are assigned to timeslots.  Instead, the
	system can print a report for a special awards judging team that just
	lists ALL the special award timeslots for each project.  (Basically,
	when each student will be at their project and won't have divisional
	judges).

	<li>NO round 2 divisional (cusp) teams are assigned to timeslots for three reasons:
		<ul><li>we don't know which projects need to be judged,
		<li>there are more than 3 judges/cusp team, there aren't enough timeslots for 1:1 timeslot assignments,
		<li>There's no guarantee that the divisional timeslots assigned to each project actually allow an even distribution of judges,
		</ul>
	Instead, after projects are assigned to these teams (there's a button
	to do this from the judge score entry), the system will print cusp
	judging team timeslot reports for each team that lists when each
	project is available for divisional judging.  

<?php
	$form_id = $page_id.'form';
	form_begin($form_id, 'c_timeslots_assign.php');
	form_button($form_id, 'go', 'Recalculate All Asisgnments');
	form_end($form_id);
?>
	<p>The button will disable when you click on it.  The assignments only
	take a few seconds By the time you've read this they'll be done and
	you can leave this page.

</div></div>

<?php
sfiab_page_end();
?>
