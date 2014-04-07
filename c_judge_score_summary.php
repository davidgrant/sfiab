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

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);
sfiab_session_start($mysqli, array('committee'));

$config['cusps'] = array(0.05, 0.10, 0.15, 0.20);

$u = user_load($mysqli);

$cats = categories_load($mysqli);
$awards = award_load_all($mysqli);
$projects = projects_load_all($mysqli);
$jteams = jteams_load_all($mysqli);

/* Link div1 jteams to projects */
foreach($jteams as &$jteam) {
	if($jteam['round'] == 1 && $awards[$jteam['award_id']]['type'] == 'divisional') {
		foreach($jteam['project_ids'] as $pid) {
			$projects[$pid]['round_1_jteam'] = &$jteam;
		}
	}
}

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}


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
}
function score_cmp($a, $b) {
	return (int)$b['jscore']['total'] - (int)$a['jscore']['total'];
}
foreach($cats as $cid=>$c) {
	uasort($projects_sorted[$cid], 'score_cmp');
}

$cusp_sections = array('gold','gold_cusp','silver','silver_cusp','bronze','bronze_cusp','hm','hm_cusp','nothing');

/* Sort out which projects get what */
foreach($cats as $cid=>$c) {
	$total_projects = count($projects_sorted[$cid]);



//	print("<br/>".$c['name']."- $total_projects projects <br/>");

	/* Adjust config cusp percentages so we don't have to compute +3 from each side, isntead
	 * we'd just like to take 6 projects from the bottom of each category */
	$half_cusp_threshold = 3;
		
	$n_projects_at_cusp = array();
	$index = 0;
	foreach($config['cusps'] as $c) {	
		$n_at = (int)round($c * $total_projects);

//		print("index=$index, n-at=$n_at<br/>");

		if($index == 0) {
			/* Ideally $half_cusp_threshold of these are cusp projects */
			$n_cusp_below = $half_cusp_threshold;
			$n_cusp_above = 0;
		} else {
			$n_cusp_below = $half_cusp_threshold;
			$n_cusp_above = $half_cusp_threshold;
		}

		/* Is there enough projcts? */
		if($n_at < $n_cusp_above + $n_cusp_below) {
			/* Nope, divide them up according to current distribution */
			$n_cusp_below = floor($n_at * ($n_cusp_below / ($n_cusp_above + $n_cusp_below)));
			/* Paranoia for rounding errors */
			if($n_cusp_above >  0) {
				$n_cusp_above = $n_at - $n_cusp_below;
			}
		}

		/* Recalculate */
		$n_middle = $n_at - ($n_cusp_above + $n_cusp_below);

//		print("$n_cusp_above, $n_middle, $n_cusp_below<br/>");

		/* Store */
		if($n_cusp_above > 0) $n_projects_at_cusp[$index-1] += $n_cusp_above;
		$n_projects_at_cusp[$index] = $n_middle;
		$n_projects_at_cusp[$index+1] = $n_cusp_below;
		$index += 2;
	}

	/* Add the last few nothing projects */
	$n_projects_at_cusp[7] += $half_cusp_threshold;

//	print_r($n_projects_at_cusp);

	$current_cusp_index = 0; 
	$current_cusp_project_count = 0;

	foreach($projects_sorted[$cid] as $pid=>&$project) {
		if($current_cusp_index < 8 && $current_cusp_project_count == $n_projects_at_cusp[$current_cusp_index]) {
			$current_cusp_project_count = 0;
			while(1) {
				$current_cusp_index += 1;
				if($current_cusp_index >= 8 ) break;
				if($n_projects_at_cusp[$current_cusp_index] > 0) break;
			}
		}
		$current_cusp_project_count += 1;
		$project['cusp_index'] = $current_cusp_index;
	}
}




switch($action) {
case 'assign':
	/* Add a project to a prize */
	$cid = (int)$_POST['cid'];

	/* Should load these out of prizes */
	$plist = array('Gold', 'Silver','Bronze','Honourable Mention','Nothing');

	/* Identify the jteams involved */
	$cusp_jteams = array();
	for($i=0; $i<count($plist)-1; $i++) {
		$name = "{$cats[$cid]['name']} Cusp {$plist[$i]}-{$plist[$i+1]}";
		$match = false;
		foreach($jteams as $jteam_id=>&$jteam) {
			if($jteam['name'] == $name) {
				$cusp_jteams[] = &$jteam;
				$match = true;
				break;
			}
		}
		if($match == false) {
			form_ajax_response(array('status'=>1, 'error'=>"Couldn't find JTeam: $name"));
			exit;
		}
	}

	/* Start at gold-silver (index 1) */
	$match_cusp_index = 1;
	foreach($cusp_jteams as &$jteam) {
		/* Delete projects on all cusp teams for $cid */
		$jteam['project_ids'] = array();

		/* Assign new projects */
		foreach($projects_sorted[$cid] as $pid=>&$project) {
			if($project['cusp_index'] == $match_cusp_index) {
				$jteam['project_ids'][] = $pid;
			}
		}

		/* Increment to next cusp index */
		$match_cusp_index += 2;

		jteam_save($mysqli, $jteam);
	}

	form_ajax_response(array('status'=>0));
	exit();
}


$page_id = 'c_judge_score_summary';
$help = '<p>Judging Scores Summary';
sfiab_page_begin("Judging Scores Summary", $page_id, $help);
?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 


<?php
	form_page_begin($page_id, array());
?>

	<p>Using the following medal distributions:
	<ul><li>Gold: <?=$config['cusps'][0] * 100?>%
	<li>Silver: <?=$config['cusps'][1] * 100?>%
	<li>Bronze: <?=$config['cusps'][2] * 100?>%
	<li>Honourable Mention: <?=$config['cusps'][3] * 100?>%
	</ul>

	<p>Choose a category below, review the Cusp projects, then assign the projects to Cusp judging teams.

	<p>The algorithm doesn't take ties into account, so two projects with the same score may be in different Cusp sections.  They have to be
	manually moved around for now 
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
			form_button($form_id, 'assign',"Assign {$c['name']} Projects to Cusp Judging Teams");
			form_end($form_id);
?>

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
<?php			foreach($projects_sorted[$cid] as $pid=>&$project) { 
				$x++;
				if($current_section != $project['cusp_index']) { 
					$current_section = $project['cusp_index']; ?>
					<tr><td colspan="7"><hr/><b>
<?php					switch($cusp_sections[$project['cusp_index']]) {
					case 'gold': print("Gold"); break;
					case 'gold_cusp': print("Gold-Silver Cusp"); break;
					case 'silver': print("Silver"); break;
					case 'silver_cusp': print("Silver-Bronze Cusp"); break;
					case 'bronze': print("Bronze"); break;
					case 'bronze_cusp': print("Bronze-Honourable Mention Cusp"); break;
					case 'hm': print("Honourable Mention"); break;
					case 'hm_cusp': print("Honourable Mention-Nothing Cusp"); break;
					case 'nothing': print("Nothing"); break;
					default:
						print("ERROR");
						exit();
					}  ?>
					</b></td></tr>
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
