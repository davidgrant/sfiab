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
require_once('isef.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);
sfiab_session_start($mysqli, array('committee'));

$u = user_load($mysqli);

$awards = award_load_all($mysqli);
$cats = categories_load($mysqli);

$js = judges_load_all($mysqli, $config['year']);
$judges = array();
foreach($js as &$j) {
	$judges[$j['uid']] = $j;
}

$ps = projects_load_all($mysqli, $config['year']);
$projects = array();
foreach($ps as &$p) {
	$projects[$p['pid']] = $p;
}


function l_jteams_load_all($mysqli, $year)
{
	$q = $mysqli->query("SELECT * FROM judging_teams 
				WHERE
				judging_teams.year='$year'
				");
	$jteams = array();
	while($j = $q->fetch_assoc()) {
		filter_int_list($j['project_ids']);
		filter_int_list($j['user_ids']);
		filter_int($j['award_id']);
		$jteams[] = $j;
	}
	return $jteams;
}

$jteams = l_jteams_load_all($mysqli, $config['year']);

$page_id = 'c_jteam_list';

$help = '<p>ISEF divisions:
<ul>';
foreach($isef_divs as $id=>$d) {
	if($d['parent'] == false) {
		$help .= '<li><b>'.$d['div'].'</b> - '.$d['name'].'</li>';
	}
}
$help .= '</ul>';

sfiab_page_begin("Judging Teams List", $page_id, $help);

/* 		
		<div style="display: inline-block; vertical-align: top"> */
?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 


<?php	/* Fixme, next year, cleanup this round0/round1 numbering nonsense */
	for($round=0; $round <=1;$round++) {
		$judge_list = array();
		foreach($judges as &$j) {
			if($j['j_rounds'][$round] == 1 && $j['j_complete'] == 1 && $j['not_attending'] == 0) {
				$judge_list[] =& $j;
			}
		}
?>
		<div data-role=collapsible data-collapsed=true>
			<h3>Round <?=$round+1?> - <?=count($judge_list)?> Unused Judges</h3>

			<div class="ui-grid-a">
			<div class="ui-block-a">
			<table>
<?php			judge_header();
			$c = 0;
			foreach($judge_list as &$j) {
				judge_row($j);
				$c++;
				if($c > count($judge_list)/2 ) {
?>					</table>
					</div>
					<div class="ui-block-b">
					<table>
		<?php			judge_header();
					$c = 0;
				}
			
			}
?>			</table>	
			</div></div>
		</div>
<?php	}
?>
					

	<ul data-role="listview" data-filter="true" data-filter-placeholder="Search..." data-inset="true">

<?php
	for($round=1; $round <= 2; $round++) {
		foreach(array('Divisional','Special','Other') as $t) {

?>			<li data-role="list-divider" data-filtertext="round <?=$round?>" ><h3><font size=+1>Round <?=$round?> - <?=$t?></font></h3></li>

<?php			foreach($jteams as &$jteam) {
				/* Match round */
				if($jteam['round'] != $round) continue;

				/* Match type if there's an award, if not match other */
				if($jteam['award_id'] > 0) {
					$a =& $awards[$jteam['award_id']];
					if($t == 'Divisional' && $a['type'] != 'divisional') continue;
					if($t == 'Special' && $a['type'] != 'special') continue;
					if($t == 'Other' && ($a['type'] == 'divisional' || $a['type'] == 'special') ) continue;
				} else {
					if($t != 'Other') continue;
				}
				jteam_li($jteam);
			}
		}
	}
?>

</ul>
<?php

function judge_header()
{
?>
	<tr><td align="center">Judge</td><td align="center">Cat Pref</td>
		<td align="center">Div Pref</td>
		<td align="center">Years</td>
		<td align="center">Langs</td>
		<td align="center">Lead?</td>
	</tr>
<?php
}

/* Print a table row for a judge, including the delte button */
function judge_row(&$j)
{
	global $jteams, $page_id, $isef_divs, $awards, $cats, $judges, $projects;
	$cat_pref = $j['j_pref_cat'] == 0 ? 'none' : $cats[$j['j_pref_cat']]['shortform'];

	if($j['j_sa_only']) {
		$exp = '';
		$div_pref = 'SA: '.join(',', $j['j_sa']);
	} else {
		$div_pref = '';
		for($x=1;$x<=3;$x++) {
			$pref = $j["j_pref_div$x"];
			if($pref > 0) {
				if($isef_divs[$pref]['parent'] != false)
					$d = $isef_divs[$pref]['parent'];
				else 
					$d = $isef_divs[$pref]['div'];

				$div_pref .= $d.' ';
			}
		}
		$exp = $j['j_years_regional'] + $j['j_years_national'];
	} 

	$langs = '';
	if(in_array('en', $j['j_languages'])) $langs.= 'en ';
	if(in_array('fr', $j['j_languages'])) $langs.= 'fr ';

	$lead = $j['j_willing_lead'] ? 'y' : 'n';
?>
	<tr><td><?=$j['name']?></td>
	    <td align="center"><?=$cat_pref?></td>
	    <td align="center"><?=$div_pref?></td>
	    <td align="center"><?=$exp?></td>
	    <td align="center"><?=$langs?></td>
	    <td align="center"><?=$lead?></td>
	    <td align="center"><button data-mini="true" class="ui-btn ui-icon-delete ui-btn-icon-notext" /></td>
	</tr>
<?php

}

function jteam_li(&$jteam) {

	global $jteams, $page_id, $isef_divs, $awards, $cats, $judges, $projects;

	$filter_text = $jteam['num'] . ' '. $jteam['name'] . 'round '.$jteam['round'];
	foreach($jteam['user_ids'] as $uid) {
		$j =& $judges[$uid]; 
		$filter_text .= ' '.$j['name'];
	}
?>
	<li id="jteam_list_<?=$v['uid']?>" data-filtertext="<?=$filter_text?>">
		<h3>#<?=$jteam['num']?> - <?=$jteam['name']?></h3>
		<div class="ui-grid-a">
		<div class="ui-block-a">
		Award: <b><?=$jteam['award_id']?>: <?=$awards[$jteam['award_id']]['name']?></b><br/>
		Round: <b><?=$jteam['round']?></b><br/>
		<table>
<?php		judge_header();
		foreach($jteam['user_ids'] as $uid) {
			judge_row($judges[$uid]);
		} ?>
		</table>
		<div data-role="controlgroup" data-type="horizontal">
		    <a href="#" class="ui-btn ui-corner-all ui-btn-inline">Add Judge</a>
		    <a href="#" class="ui-btn ui-corner-all  ui-btn-inline">Auto-add Best Judge</a>
		    <a href="#" class="ui-btn ui-corner-all  ui-btn-inline">Add Project</a>
		 </div>
		</div>
		<div class="ui-block-b">
		<table>
		<tr><td align="center">Project</td><td align="center">Title</td>
			<td align="center">Cat</td>
			<td align="center">Div</td>
			<td align="center">Lang</td>
		</tr>
<?php		foreach($jteam['project_ids'] as $pid) {
			$p =& $projects[$pid];
			$cat = $cats[$p['cat_id']]['shortform'];
			$pref = $p['isef_id'];
			if($isef_divs[$pref]['parent'] != false)
				$div = $isef_divs[$pref]['parent'];
			else 
				$div = $isef_divs[$pref]['div'];

			$title = $p['title'];
			if(strlen($title) > 50) $title = substr($title, 0, 47)."...";

			$lang = $p['language'];
?>

			<tr><td><?=$p['number']?></td>
			    <td align="center"><?=$title?></td>
			    <td align="center"><?=$cat?></td>
			    <td align="center"><?=$div?></td>
			    <td align="center"><?=$lang?></td>
			    <td align="center"><button data-mini="true" class="ui-btn ui-icon-delete ui-btn-icon-notext" /></td>
			</tr>
<?php		} ?>
		</table>
		</div>
		 </div>
					
	</li>
<?php
}
?>

</div></div>


<?php
sfiab_page_end();
?>
