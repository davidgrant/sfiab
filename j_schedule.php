<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('awards.inc.php');
require_once('committee/judges.inc.php');
require_once('timeslots.inc.php');

$mysqli = sfiab_init('judge');

$u = user_load($mysqli);
$closed = sfiab_registration_is_closed($u);

$page_id = 'j_schedule';


$help = '
<p>';

sfiab_page_begin($u, "Judge Schedule", $page_id, $help);

$timeslots = timeslots_load_all($mysqli);

?>


<div data-role="page" id="<?=$page_id?>" class="sfiab_page" > 

	<h3>Judge Team and Project Assignments:</h3>
<?php
	/* Get all jteams this judge is on */
	$jteams = jteams_load_all_for_judge($mysqli, $u['uid']);
	$awards = award_load_all($mysqli);
	$projects = projects_load_all($mysqli);

	$found_assignment = false;

	/* Print a message if judigng assignemnts are not shown, then skip all
	 * the code below */
	if($config['judge_show_assignments'] == false) { ?>
		<p>Judging assignments are not available yet.
<?php		
		/* Skip all the code below by setting timeslots to an empty array */
		$timeslots = array();		
	}

	foreach($timeslots as $timeslot_id => &$ts) {
		$header_printed = false;

		if(!in_array($ts['round'], $u['j_rounds'])) {
			continue;
		}

		$round_start = date('F j, g:ia', $ts['start_timestamp']);
		$round_end = date('g:ia', $ts['end_timestamp']);

?>		<h3><?=$ts['name']?> - <?=$round_start?> - <?=$round_end?></h3>

<?php		foreach($jteams as &$jteam) {
			if($jteam['round'] != $ts['round']) continue;

			$found_assignment = true;

			$a=array();
			foreach($jteam['user_ids'] as $uid) {
				$temp_u = user_load($mysqli, $uid);
				$a[] = $temp_u['name'];
			}
			$members = join(', ', $a);
?>
			<h4>Team #<?=$jteam['num']?> - <?=$jteam['name']?></h4>
			<table><tr><td>Members: </td><td><?=$members?></td></tr>

			<tr><td valign="top">Projects:</td><td>
<?php
			if($ts['round'] == 1 && $awards[$jteam['award_id']]['type'] == 'divisional') { ?>
				This is a CUSP judging team, projects will be assigned after the first round of judging is complete
<?php			} else if (count($jteam['project_ids']) == 0) { ?>
				No projects yet.  This could be because students cannot self-nominate for this award, or because all projects on the floor are eligible.  You will given judging instructions at the fair.
<?php			} else { ?>
				<table>
<?php				$sorted_project_ids = array();
				foreach($jteam['project_ids'] as $pid) {
					$project = &$projects[$pid];
					$sorted_project_ids[$project['number_sort']] = $pid;
				}
				ksort($sorted_project_ids);
				foreach($sorted_project_ids as $pid) {
					$p =& $projects[$pid];
					$link = "<a data-ajax=\"false\" href=\"project_summary.php?pn={$p['number']}\">{$p['number']}</a>";
?>					<tr><td><?=$link?></td>
					<td><?=$p['title']?><td>
					</tr>
<?php				}?>
				</table>
<?php			} ?>				
			</td></tr></table>
<?php		}
		if(!$found_assignment) {
?>			<p>You have no judging assignments (yet). You <b>will</b> be
			assigned to a judging team at or before fair, we're just not
			sure which one yet.  e.g., some judges cancel at the last
			minute, some judging teams need extra expertise in certain
			areas, and some unlisted special awards still need judges.
<?php		}
	}

?>
</div>
	

<?php
sfiab_page_end();
?>
