<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('email.inc.php');
require_once('awards.inc.php');
require_once('committee/judges.inc.php');
require_once('debug.inc.php');

$mysqli = sfiab_init('committee');

$config['projects_per_cusp'] = 6;

$u = user_load($mysqli);

$cats = categories_load($mysqli);
$awards = award_load_all($mysqli);
$projects = projects_load_all($mysqli, true); /* Only accepted projects */
$jteams = jteams_load_all($mysqli);

/* Link div1 jteams to projects */
foreach($jteams as &$jteam) {
	if($jteam['round'] == 0 && $awards[$jteam['award_id']]['type'] == 'divisional') {
		foreach($jteam['project_ids'] as $pid) {
			if(!array_key_exists($pid, $projects)) {
				/* Project $pid is assigned to a jteam but the project isn't complete */
				continue;
			}
			$projects[$pid]['round_1_jteam'] = &$jteam;
		}
	}
}

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

/* Build a list of cusp sections names */
$cusp_sections = array();
for($x=0;$x<count($config['judge_divisional_prizes']); $x++) {
	$prize_name = $config['judge_divisional_prizes'][$x];
	$cusp_sections[] = $prize_name;
	if($x < count($config['judge_divisional_prizes'])-1) {
		$cusp_sections[] = $prize_name.'-'.$config['judge_divisional_prizes'][$x+1]." Cusp";
	} else {
		$cusp_sections[] = $prize_name.'-Nothing Cusp';
	}
}
$cusp_sections[] = "Nothing";
/* e.g, 0 - Gold
 * 	1 = Gold-Silver Cusp
 * 	2 = Silver
 *	3 = S-B Cusp
 *	4 = B
 *	5 = B-HM Cusp
 *	6 = HM
 *	7 = HM-Nothing Cusp
 *	8 = Nothing */


$scores = array();
/* Load all scores, index by pid */
foreach($projects as $pid=>&$project) {
	$q = $mysqli->query("SELECT * FROM judging_scores WHERE pid='$pid'");
	if($q->num_rows == 0) {
		$scores[$pid] = array('scientific'=>'', 'originality'=>'', 'communication'=>'', 'total'=>0);
	} else {
		$scores[$pid] = $q->fetch_assoc();
		filter_int($scores[$pid]['scientific']);
		filter_int($scores[$pid]['originality']);
		filter_int($scores[$pid]['communication']);

		$map = array(0=> '', 1=>'1L', 2=>'1M', 3=>'1H',
				4=>'2L', 5=>'2M', 6=>'2H',
				7=>'3L', 8=>'3M', 9=>'3H',
				10=>'4L', 11=>'4M', 12=>'4H');

		$scores[$pid]['scientific'] = $map[$scores[$pid]['scientific']];
		$scores[$pid]['originality'] = $map[$scores[$pid]['originality']];
		$scores[$pid]['communication'] = $map[$scores[$pid]['communication']];
	}
	$project['jscore'] = $scores[$pid];
}

/* Sort projects by cat and then score */
$projects_sorted = array();
foreach($cats as $cid=>$c) {
	$projects_sorted[$cid] = array();
}

foreach($projects as $pid=>&$project) {
	$projects_sorted[$project['cat_id']][$pid] = &$project;	
	$project['cusp_index'] = count($cusp_sections)-1; /* Start at 'nothing' */

	if(!array_key_exists('cat_id', $project)) {
		print("<pre>cat id is missing from project $pid: ".print_r($project, true));
	}
}
function score_cmp($a, $b) {
	return (int)$b['jscore']['total'] - (int)$a['jscore']['total'];
}

/* Reindex array so the highest score is at index 0 */
foreach($cats as $cid=>$c) {
	uasort($projects_sorted[$cid], 'score_cmp');

	$n = array();
	$i = 0;
	foreach($projects_sorted[$cid] as $pid=>&$p) {
		$n[$i] = &$p;
		$i++;
	}
	$projects_sorted[$cid] = $n;
}

/* Desired number of projects at each index, adjusted for cusp teams */
$target_projects_at_cusp = array();
/* Actual number of projects at each inded */
$n_projects_at_cusp = array();
/* Medal distribution (even indexes), and the number of projects that are assined up to the previous index (odd indexes)
 *  actual number[odd index] - medal distribution[odd index] = number of projects to assign down to the next index */
$medal_distribution = array();

/* Sort out which projects get what */
foreach($cats as $cid=>$c) {
	$total_projects = count($projects_sorted[$cid]);

	debug("\n".$c['name']."- $total_projects projects \n");

	$n_projects_at_cusp[$cid] = array_fill(0, count($config['judge_divisional_distribution'])*2, 0);
	$target_projects_at_cusp[$cid] = array_fill(0, count($config['judge_divisional_distribution'])*2, 0);
	$medal_distribution[$cid] = array_fill(0, count($config['judge_divisional_distribution'])*2, 0);
	/* Build an array of fractions for each div award and cusp team 
	 * even indexes = div award (not rejudged)
	 * odd indexes = cusp team (rejudged) */

	$half_projects_per_cusp = (int)($config['projects_per_cusp'] / 2);
	$index = 0;
	foreach($config['judge_divisional_distribution'] as $c) {
		$c /= 100;
		$n = (int)round($c * $total_projects);
		$medal_distribution[$cid][$index] = $n;
		/* Each cusp has 3 parts:
		 * - top $config['projects_per_cusp']/2 projects that are assigned to [$index - 1] (unless $index=0)
		 * - middle $n - $config['projects_per_cusp'] that are assigned to [$index]
		 * - bottom $config['projects_per_cusp']/2 projects that are assigned to [$index + 1]
		 * We are not allowed to distribute more than $n projects, top and bottom are split
		 *  evenly (ties to bottom), the middle could be zero 
		 * By calculating the split this way, the CUSP judging teams can just assign 
		 *  half the projects to the div above, and half to the div below.  Although that will
		 *  change below */

		/* There is no cusp above gold (index == 0) so just remove the top cusp/2 projects completely */
		$top = ($index == 0) ? 0 : $half_projects_per_cusp;
		$bot = $half_projects_per_cusp;

		if($top + $bot > $n) {
			/* If there are more cusp projects for judging than actual projects in this cusp
			 * recalculate top/bot as a ratio of the projects that are available.
			 *  This either computes a 50/50 split, or a 0/100 if top==0, but it is written to
			 *  handle any ratio */
			$top = (int)($n * ($top / ($top + $bot) ));
			$bot = $n - $top;
			$mid = 0;
		} else {
			/* There are enough projects for a 3-way split, so assign mid whatever is left */
			$mid = $n - ($top + $bot);
		}

		/* Add top, mid, bot to the right project target counts */
		if($index > 0) 	$target_projects_at_cusp[$cid][$index - 1] += $top;
		$target_projects_at_cusp[$cid][$index] += $mid;
		$target_projects_at_cusp[$cid][$index + 1] += $bot;
		$index += 2;
	}
	/* Add another half to the projects at the last cusp so the HM-nothing cusp doesn't get a target 
	 * of just three projects, want it to be six */
	$target_projects_at_cusp[$cid][$index-1] += $half_projects_per_cusp;

	debug("CUSP: target project counts:\n");
	for($index=0; $index<count($target_projects_at_cusp[$cid]); $index++) {
		debug("CUSP: {$cusp_sections[$index]}: {$target_projects_at_cusp[$cid][$index]} projects\n");
		$n_projects_at_cusp[$cid][$index] = 0;
	}

	/* Now adjust the actual number of projects on each CUSP team that will be rejudged:
	 *  - Take the current cusp location, and go back until there is a project with a different jscore
	 *  - Assign all those projects to the cusp and out of the previous index
	 *    (e.g., move from gold to gold-silver) */

	$index = 0; 
	$pcount = count($projects_sorted[$cid]);
	$total_n = 0;
	$total_target = 0;
	$p_start = 0;

	for($index=0; $index<count($target_projects_at_cusp[$cid]); $index++) {
		debug("CUSP: adjust project counts for {$cusp_sections[$index]}:\n");

		/* Take the number we should assign, and subtract the number we've assigned to account for
		 * overages */
		$total_target += $target_projects_at_cusp[$cid][$index];
		$assigned_so_far = $total_n - $n_projects_at_cusp[$cid][$index];
		$target_n = $total_target - $assigned_so_far;

		debug("CUSP:    target is $target_n projects: total_target=$total_target -  assigned so far=$assigned_so_far (this cusp target added {$target_projects_at_cusp[$cid][$index]} to total_target)\n");
		/* No projects here, continue to next cusp */
		if($target_n <= 0) {
			debug("CUSP:    target is <= zero, skip.\n");
			continue;
		}
		
		$current_jscore = -1;
//		debug(print_r($projects_sorted[$cid][$p_start], true));

		debug("CUSP:    starting iteration at sorted index $p_start, up to $pcount, score = $current_jscore\n");
		for($project_index = $p_start; $project_index < $pcount; $project_index++) {
			$project = &$projects_sorted[$cid][$project_index];

			debug("CUSP:      Project {$project['pid']}: jscore={$project['jscore']['total']}\n");

			if($current_jscore != -1 && $project['jscore']['total'] != $current_jscore) {
				print("Your algorithm is broken.\n");
				exit();
			}
			$current_jscore = $project['jscore']['total'];

			/* Peek at the next entry, should we continue or assign what we've got */
			if($project_index + 1 < $pcount) {
				$p2 = &$projects_sorted[$cid][$project_index + 1];
				if($p2['jscore']['total'] == $current_jscore) {
					/* The next project will have the same jscore, so loop and include it */
					continue;
				}
			}

			$p_end = $project_index;

			/* Add projects[p_start .. p_end ] somewhere:
			 * - if the current index is a div (even)
			 *   - add it if it fits, otherwise unconditiontally add it to the cusp team at index+1
			 * - if the current index is a cusp team
			 *   - add it always even if it doesn't fit */

			$add_to_index = $index;
			$n_to_add = $p_end - $p_start + 1;
			$force_change_index = false;

			debug("CUSP:      n_to_add = $n_to_add, total_n = $total_n\n");


			if($index % 2 == 0) {
				debug("CUSP:      div check {$n_projects_at_cusp[$cid][$index]} + $n_to_add > $target_n?\n");
				/* Div section */
				if($n_projects_at_cusp[$cid][$index] + $n_to_add > $target_n) {
					/* Won't fit */
					debug("CUSP         add to next index\n");
					$add_to_index = $index + 1;
					$force_change_index = true;
				} else {
					debug("CUSP         add to current index\n");
					$add_to_index = $index;
				}
			} else {
				/* Current index is a cusp team */
				$add_to_index = $index;
			}

			/* Add it */
			for($j = $p_start; $j <= $p_end; $j++) {
				$projects_sorted[$cid][$j]['cusp_index'] = $add_to_index;
			}
			$n_projects_at_cusp[$cid][$add_to_index] += $n_to_add;
			$total_n += $n_to_add;

			/* Adjust the next start and reset the score */
			$p_start = $project_index + 1;
			$current_jscore = -1;

			debug("CUSP:      cusp[{$cusp_sections[$index]}] now has {$n_projects_at_cusp[$cid][$index]}/{$target_n} projects\n");
			/* Stop adding to this index if it's now full */
			if($force_change_index || $n_projects_at_cusp[$cid][$index] >= $target_n) {
				break;
			}
		}
	}

	/* Calculate the up/down just for printing */
	for($index=0; $index<count($target_projects_at_cusp[$cid]); $index++) {
		if($index %2 == 0) {
			/* How many projects are coming down from a previous index */
			if($index == 0) {
				$from_last = 0;
			} else {
				$from_last = $n_projects_at_cusp[$cid][$index-1] - $medal_distribution[$cid][$index-1];
			}

			/* Number of projects here at this index (from last cusp team + default awarded) */
			$here = $from_last + $n_projects_at_cusp[$cid][$index];
			/* Calculate number we need to bring up from the next cusp team */
			$up = $medal_distribution[$cid][$index] - $here;
			/* Assign so we can print it later */
			$medal_distribution[$cid][$index + 1] = $up;
		}
	}

	debug("Medal Distribution: ".print_r($medal_distribution[$cid], true));
	
}

/* Prize list plus 'nothing' */
$plist = $config['judge_divisional_prizes'];
$plist[] = 'Nothing';

debug("CUSP: prize list: ".print_r($plist, true));


switch($action) {
case 'assign':
	/* Assign all projects for a category to the cusp teams */
	$cid = (int)$_POST['cid'];

	/* Find the divisional award for this category */
	$award = NULL;
	foreach($awards as $aid=>&$a) {
		if($a['type'] == 'divisional' && in_array($cid, $a['categories'])) {
			$award = &$a;
			break;
		}
	}

	debug("Assign projects for CUSP category $cid:{$cats[$cid]['name']}\n");

	if($award === NULL) {
		print("Error, couldn't find divisional award");
		debug("   Error: Couldn't find divisional award.\n");
		exit();
	}
		
	/* Ensure it only has one categroy */
	if(count($award['categories']) != 1) {
		print("Error, turn on debug.");
		debug("   Error:Divisional Award has more than one category\n");
		exit();
	}

	debug("   Found divisional award {$award['id']}:{$award['name']}\n");
	/* Now iterate over the prizes find the jteam for each prize, and assign projects.
	 * Start at the last index above 'nothing' and work backwards.  The prizes_in_order
	 * are HM -> gold */
	$match_cusp_index = count($cusp_sections)-2;
	foreach($award['prizes_in_order'] as &$prize) {

		unset($jteam);
		$jteam = NULL;
		foreach($jteams as $jteam_id=>&$jt) {
			if($jt['prize_id'] == $prize['id']) {
				$jteam = &$jt;
				break;
			}
		}
		debug("   Processing prize {$prize['id']}:{$prize['name']}\n");

		if($jteam === NULL) {
			print("Coudln't find jteam");
			debug("   Couldn't find jteam for prize\n");
			exit();
		}

		debug("   Found jteam {$jteam['id']}:{$jteam['name']}\n");

		/* Delete projects on all cusp teams for $cid */
		$jteam['project_ids'] = array();

		/* Assign new projects */
		foreach($projects_sorted[$cid] as $index=>&$project) {
			if($project['cusp_index'] == $match_cusp_index) {
				$jteam['project_ids'][] = $project['pid'];
			}
		}
		debug("   Added projects: ".join(',', $jteam['project_ids'])."\n");

		/* Also record the number of cusp up projects (those that get this prize), all other projects
		 * get the next prize down */
		$up = $medal_distribution[$cid][$match_cusp_index];
		$down = $n_projects_at_cusp[$cid][$match_cusp_index] - $up;
		debug("   Award distribution up=$up, down=$down\n");
		if($up + $down != count($jteam['project_ids'])) {
			print("Error, turn on debug");
			debug("   Prize has $up up + $down down != ".count($jteam['project_ids'])." project ids which were added\n");
			exit();
		}

		$jteam['cusp_n_up'] = $up;

		/* Increment to next cusp index */
		$match_cusp_index -= 2;

		jteam_save($mysqli, $jteam);
	}

	form_ajax_response(array('status'=>0));
	exit();
}


$page_id = 'c_judge_score_summary';
$help = '<p>Judging Scores Summary';
sfiab_page_begin($u, "Judging Scores Summary", $page_id, $help);
?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 


<?php
	form_page_begin($page_id, array());
?>

	<p>Using the following medal distributions:
	<ul>
<?php	for($x=0; $x<count($config['judge_divisional_prizes']); $x++) { ?>
		<li><?=$config['judge_divisional_prizes'][$x]?>: <?=$config['judge_divisional_distribution'][$x]?>%
<?php	} ?>		
	</ul>

	<p>Choose a category below, review the Cusp projects, then assign the projects to Cusp judging teams.
	<hr/>

	<div data-role="tabs">
		<div data-role="navbar" >
		<ul data-inset="true">
<?php			foreach($cats as $cid=>$c) { ?>
				<li><a href="#scores_<?=$cid?>" data-ajax="false"><?=$c['name']?></a></li>
<?php			} ?>
		</ul>
		</div>

<?php		foreach($cats as $cid=>$c) {
			$current_section = -1;
			$x = 0;
?>
			<div data_role="tab" id="scores_<?=$cid?>">

<?php			$form_id = $page_id.'_'.$cid.'_form';
			form_begin($form_id, "c_judge_score_summary.php");
			form_hidden($form_id, 'cid', $cid);
			form_submit_enabled($form_id, 'assign',"Assign {$c['name']} Projects to Cusp Judging Teams", "Done");
			form_end($form_id);
			
?>
			<p><?=$c['name']?> Medal Allocations:
			<ul>
<?php			for($i=0; $i<count($config['judge_divisional_prizes']); $i++) { ?>
				<li><?=$config['judge_divisional_prizes'][$i]?>: <?=$medal_distribution[$cid][$i*2]?> project(s)
<?php			} ?>		
			</ul>

			<table data_role="table" data-mode="columntoggle" >
			<thead>
				<tr>
				<th>Rank</th>
				<th>Number</th>
				<th>Project</th>
				<th>Sci</th>
				<th>Org</th>
				<th>Comm</th>
				<th>Total</th>
				</tr>
			</thead>
<?php			foreach($projects_sorted[$cid] as $sorted_i=>&$project) { 
				$x++;
				if($current_section != $project['cusp_index']) { 
					$index = $project['cusp_index'];
					$current_section = $index; ?>
					<tr><td colspan="7"><hr/><b><?=$cusp_sections[$index]?></b>
<?php					
					if($index %2 == 1) {
						$up = $medal_distribution[$cid][$index];
						$down = $n_projects_at_cusp[$cid][$index] - $up;
						print(", <b>Assign $up {$plist[($index-1)/2]}, $down {$plist[($index+1)/2]}</b>");
						
					}

					if($project['cusp_index'] < count($cusp_sections)-1) {
						print(" - target: {$n_projects_at_cusp[$cid][$index]} / {$target_projects_at_cusp[$cid][$index]} project(s)");
					} 
					
					
					?>
					</td></tr>
<?php				} ?>

				<tr>
				<td><?=$x?></td>
				<td><?=$project['number']?></td>
				<td><?=$project['title']?></td>
				<td><?=$project['jscore']['scientific']?></td>
				<td><?=$project['jscore']['originality']?></td>
				<td><?=$project['jscore']['communication']?></td>
				<td><b><?=$project['jscore']['total']?></b></td>
				</tr>
<?php			} ?>
			</table>
			</div>
<?php		} ?>
	</div>

</div></div>


<?php
sfiab_page_end();
?>
