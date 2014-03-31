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

/* Need all jduges for smart add, so load them anyway */
$js = judges_load_all($mysqli, $config['year']);
$judges = array();
foreach($js as &$j) {
	$judges[$j['uid']] = $j;
}

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

switch($action) {
case 'jdel':
	/* Remove a judge from a judging team */
	$jteam_id = (int)$_POST['jteam_id'];
	$j_uid = (int)$_POST['uid'];

	$jteam = jteam_load($mysqli, $jteam_id);

	$mysqli->query("DELETE FROM timeslot_assignments WHERE judging_team_id='$jteam_id' AND judge_id='$j_uid'");

	$new_uids = array();
	foreach($jteam['user_ids'] as $uid) {
		if($uid == $j_uid) continue;
		$new_uids[] = $uid;
	}
	$jteam['user_ids'] = $new_uids;
	jteam_save($mysqli, $jteam);
	form_ajax_response(array('status'=>0));
	exit();

case 'jadd':
	/* Add a judge to a jduging team */
	$jteam_id = (int)$_POST['jteam_id'];
	$j_uid = (int)$_POST['uid'];
	$jteam = jteam_load($mysqli, $jteam_id);
	$jteam['user_ids'][] = $j_uid;
	jteam_save($mysqli, $jteam);
	form_ajax_response(array('status'=>0));
	exit();

case 'jadd_smart':
	/* Add a best-match single free judge to a judging team */
	$jteam_id = (int)$_POST['jteam_id'];
	$jteam = jteam_load($mysqli, $jteam_id);

	$j_uid = false;
	foreach($judges as $uid=>&$j) {
		if($j['j_round'][$jteam['round']-1] != 1) continue;

	}


	$jteam['user_ids'][] = $j_uid;
	jteam_save($mysqli, $jteam);
	form_ajax_response(array('status'=>0));
	exit();


case 'pdel':
	/* Remove a project from a judging team */
	$jteam_id = (int)$_POST['jteam_id'];
	$del_pid = (int)$_POST['pid'];

	$jteam = jteam_load($mysqli, $jteam_id);

	$mysqli->query("DELETE FROM timeslot_assignments WHERE judging_team_id='$jteam_id' AND pid='$del_pid'");

	$new_pids = array();
	foreach($jteam['project_ids'] as $pid) {
		if($pid == $del_pid) continue;
		$new_pids[] = $pid;
	}
	$jteam['project_ids'] = $new_pids;
	jteam_save($mysqli, $jteam);
	form_ajax_response(array('status'=>0));
	exit();

case 'padd':
	/* Add a project to a jduging team */
	$jteam_id = (int)$_POST['jteam_id'];
	$pid = (int)$_POST['pid'];
	$jteam = jteam_load($mysqli, $jteam_id);
	$jteam['project_ids'][] = $pid;
	jteam_save($mysqli, $jteam);
	form_ajax_response(array('status'=>0));
	exit();


}


$ps = projects_load_all($mysqli, $config['year']);
$projects = array();
foreach($ps as &$p) {
	$projects[$p['pid']] = $p;
}




$jteams = jteams_load_all($mysqli, $config['year']);




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
				/* Is this judge on a jteam in ths round? */
				$found = false;
				foreach($jteams as &$jteam) {
					if($jteam['round'] != $round+1) continue;

					foreach($jteam['user_ids'] as $uid) {
						if($uid == $j['uid']) {
							$found = true;
							break;
						}
					}
					if($found == true) break;
				}
				if(!$found) {
					$judge_list[] =& $j;
				}
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
				judge_row($round, $j, false);
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
function judge_row($jteam, &$j, $show_del = true)
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

	if(is_array($jteam)) {
		$del = $show_del ? "<a href=\"#\" onclick=\"return jteam_jdel({$jteam['id']},{$j['uid']});\">[X]</a>" : '';
		$tr_id = "jteam_list_{$jteam['id']}_judge_{$j['uid']}";

	} else {
		$del = '';
		$tr_id = "jteam_list_unused_round_{$jteam}_{$j['uid']}";
	}
?>
	<tr id="<?=$tr_id?>" ><td><?=$j['name']?></td>
	    <td align="center"><?=$cat_pref?></td>
	    <td align="center"><?=$div_pref?></td>
	    <td align="center"><?=$exp?></td>
	    <td align="center"><?=$langs?></td>
	    <td align="center"><?=$lead?></td>
	    <td align="center"><?=$del?></td>
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
	<li id="jteam_list_<?=$jteam['id']?>" data-filtertext="<?=$filter_text?>">
		<h3>#<?=$jteam['num']?> - <?=$jteam['name']?></h3>
		<div class="ui-grid-a">
		<div class="ui-block-a">
		Award: <b><?=$jteam['award_id']?>: <?=$awards[$jteam['award_id']]['name']?></b><br/>
		Round: <b><?=$jteam['round']?></b><br/>
		<table id="jteam_list_<?=$jteam['id']?>_table">

<?php		judge_header();
		foreach($jteam['user_ids'] as $uid) {
			judge_row($jteam, $judges[$uid]);
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
			    <td align="center"><a href="#" >[X]</a></td>
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

<script>
function jteam_jdel(jteam_id, id)
{
//	if(confirm('Really delete this award?') == false) return false;
	alert(jteam_id + " " + id);
	$.post('c_jteam_edit.php', { action: "jdel", jteam_id: jteam_id, uid: id }, function(data) {
		if(data.status == 0) {
			$("#jteam_list_"+jteam_id+"_judge_"+id).hide();
		}
	}, "json");
	return false;
}

</script>

<?php
sfiab_page_end();
?>
