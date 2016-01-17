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

$page_id = 'c_judging';

$timeslots = timeslots_load_all($mysqli);
$num_rounds = count($timeslots);

sfiab_page_begin($u, "Judging", $page_id);
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php	form_page_begin($page_id, array());
	/* Count judges */
	$judges = judges_load_all($mysqli, $config['year']);
	$jteams = jteams_load_all($mysqli);
	$awards = award_load_all($mysqli);
	$timeslots = timeslots_load_all($mysqli);

	$j_complete = 0;
	$j_not_attending = 0;
	$j_incomplete = 0;

	$j_round = array();
	$j_round_all = 0;
	$jteam_count = array();
	$jteam_judge_count = array();
	$judges_used_in_round = array();
	$unused_judge_count = array();
	for($r=0; $r<$num_rounds; $r++) {
		$j_round[$r] = 0;
		$jteam_count[$r] = array('divisional'=>0, 'special'=>0);
		$jteam_judge_count[$r] = array('divisional'=>0, 'special'=>0);
		$judges_used_in_round[$r] = array();
		$unused_judge_count[$r] = 0;
	}

	foreach($judges as &$j) {
		if($j['attending'] == 0) {
			$j_not_attending++;
		} else {
			if($j['j_complete']) {
				$all = true;
				foreach($j['j_rounds'] as $r) {
					if($r >= $num_rounds) {
						continue;
					}
					if($r === NULL || $r == -1) {
						$all = false;
						continue;
					}
					$j_round[$r] += 1;
				}
				if($all) $j_round_all += 1;
				$j_complete += 1;
			} else {
				$j_incomplete += 1;
			}
		}
	}


	foreach($jteams as &$jteam) {
		$round = $jteam['round'];

		if($round >= $num_rounds) {
			/* Can create teams for rounds that don't exist, like cusp teams */
			continue;
		}

		$type = $awards[$jteam['award_id']]['type'];
		$n_judges = count($jteam['user_ids']);

		if($type == 'grand' || $type == 'other') $type = 'special';

		$jteam_count[$round][$type] += 1;
		$jteam_judge_count[$round][$type] += $n_judges;

		$judges_used_in_round[$round] = array_merge($judges_used_in_round[$round], $jteam['user_ids']);
	}

	/* Count unused judges */
	foreach($judges as &$j) {
		for($r=0; $r<$num_rounds; $r++) {
			if(in_array($r, $j['j_rounds']) && !in_array($j['uid'], $judges_used_in_round[$r])) {
				$unused_judge_count[$r]+=1;
			}
		}
	}

	$timeslot_msg = '';
	if($num_rounds == 0) {
		$timeslot_msg = "<br/><font size=-1><font color=red>ERROR</font>: There are <font color=red>0</font> judging rounds defined</font>";
	}

?>	
	<h3>Stats</h3> 
	<table border=1>
	<tr><td valign="top">
		<table>
		<tr><td colspan="2" align="center"><b>Complete Judges</b></td></tr>
<?php		foreach($timeslots as $tid=>&$t) {
			$r = $t['round']; ?>
			<tr><td align="center"><?=$t['name']?></td><td align="center"><?=$j_round[$r]?></td></tr>
<?php		} ?>
		<tr><td align="center">All</td><td align="center"><?=$j_round_all?></td></tr>
		<tr><td align="center"><b>Total</b></td><td align="center"><b><?=$j_complete?></b></td></tr>
		</table>
	</td><td valign="top">
		<table>
		<tr><td colspan="2" align="center"><b>Incomplete Judges</b></td></tr>
		<tr><td align="center">Incomplete</td><td align="center"><?=$j_incomplete?></td></tr>
		<tr><td align="center">Not Attending</td><td align="center"><?=$j_not_attending?></td></tr>
		</table>
	</td><td valign="top">
		<table>
		<tr><td colspan="5" align="center"><b>Judging Teams</b></td></tr>
		<tr><td colspan="2"></td><td align="center">Divisional</td><td align="center">Special</td><td align="center">Unused</td></tr>
<?php		foreach($timeslots as $tid=>&$t) {
			$r = $t['round']; ?>
			<tr><td align="center"><?=$t['name']?></td><td align="center">Teams</td><td align="center"><?=$jteam_count[$r]['divisional']?></td><td align="center"><?=$jteam_count[$r]['special']?></td><td align="center">  </td></tr>
			<tr><td align="center">       </td><td align="center">Judges</td><td align="center"><?=$jteam_judge_count[$r]['divisional']?></td><td align="center"><?=$jteam_judge_count[$r]['special']?></td><td align="center"><?=$unused_judge_count[$r]?></td></tr>
<?php		} ?>
		</table>

	</td></tr></table>


	<h3>Judges</h3> 
	<ul data-role="listview" data-inset="true">
	<li><a href="c_user_list.php?roles[]=judge" data-rel="external" data-ajax="false">Judge List / Editor</a></li>
	<li><a href="index.php#register" data-rel="external" data-ajax="false">Invite a Judge</a></li>
	</ul>


	<h3>Judging Assignments</h3> 
	<ul data-role="listview" data-inset="true">
	<li><a href="c_jteam_edit.php" data-rel="external" data-ajax="false">Edit Judging Teams</a></li>
	<li><a href="c_timeslots.php" data-rel="external" data-ajax="false">Edit Judging Timeslots<?=$timeslot_msg?></a></li>
	<li><a href="c_judge_sanity.php" data-rel="external" data-ajax="false">Display Judging Sanity Checks</a></li>
	<li><a href="c_judge_scheduler.php" data-rel="external" data-ajax="false">Run the Judge Scheduler</a></li>
	</ul>

	<h3>Judge Score Entry</h3> 
	<ul data-role="listview" data-inset="true">
	<li><a href="c_judge_score_entry.php" data-rel="external" data-ajax="false">Enter Round 1 Divisional Scores</a></li>
	<li><a href="c_judge_score_summary.php" data-rel="external" data-ajax="false">Round 1 Score Summary / Build Cusp Teams</a></li>


</div></div>
	

<?php
sfiab_page_end();
?>
