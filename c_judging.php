<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('committee/judges.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('committee'));

$u = user_load($mysqli);

$page_id = 'c_judging';


$saved = false;
if(array_key_exists('action', $_POST)) {

	if($_POST['action'] == 'save') {
		form_ajax_response_error(0, 'save response');
		exit();
	}

	if($_POST['action'] == 'save2') {
		$saved = true;
	}

}

sfiab_page_begin("Judging", $page_id);

?>



<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	form_page_begin($page_id, array());
	/* Count judges */
	$judges = judges_load_all($mysqli, $config['year']);

	$j_complete = 0;
	$j_not_attending = 0;
	$j_incomplete = 0;
	$j_round1 = 0;
	$j_round2 = 0;
	$j_round_both = 0;
	foreach($judges as &$j) {
		if($j['not_attending']) {
			$j_not_attending++;
		} else {
			if($j['j_complete']) {
				if($j['j_rounds'][0] == 1) $j_round1++;
				if($j['j_rounds'][1] == 1) $j_round2++;
				if($j['j_rounds'][0] == 1 && $j['j_rounds'][1] == 1) $j_round_both++;
				$j_complete++;
			} else {
				$j_incomplete++;
			}
		}
	}

	$jteam_count = array();

	$q = $mysqli->query("SELECT round,awards.`type`,COUNT(`judging_teams`.`id`) AS c FROM judging_teams 
				LEFT JOIN awards ON awards.id=judging_teams.award_id
				WHERE judging_teams.year='{$config['year']}' GROUP BY round,awards.`type`");

	while($r = $q->fetch_assoc()) {
		$jteam_count[$r['round']][$r['type']] = $r['c'];
	}


?>	
	<h3>Stats</h3> 
	<table border=1>
	<tr><td valign="top">
		<table>
		<tr><td colspan="2" align="center"><b>Complete Judges</b></td></tr>
		<tr><td align="center">Round 1</td><td align="center"><?=$j_round1?></td></tr>
		<tr><td align="center">Round 2</td><td align="center"><?=$j_round2?></td></tr>
		<tr><td align="center">Both</td><td align="center"><?=$j_round_both?></td></tr>
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
		<tr><td align="center">Round 1</td><td align="center">Teams</td><td align="center"><?=$jteam_count[1]['divisional']?></td><td align="center"><?=$jteam_count[1]['special']?></td><td align="center">  </td></tr>
		<tr><td align="center">       </td><td align="center">Judges</td><td align="center">fixme</td><td align="center">fixme</td><td align="center">fixme</td></tr>
		<tr><td align="center">Round 2</td><td align="center">Teams</td><td align="center"><?=$jteam_count[2]['divisional']?></td><td align="center"><?=$jteam_count[2]['special']?></td><td align="center">  </td></tr>
		<tr><td align="center">       </td><td align="center">Judges</td><td align="center">fixme</td><td align="center">fixme</td><td align="center">fixme</td></tr>
		</table>

	</td></tr></table>


	<h3>Judges</h3> 
	<ul data-role="listview" data-inset="true">
	<li><a href="index.php#register" data-rel="external" data-ajax="false">Invite a Judge</a></li>
	<li><a href="c_user_list.php?roles=judge" data-rel="external" data-ajax="false">Judge List / Editor</a></li>
	</ul>

	<h3>Judging Assignments</h3> 
	<ul data-role="listview" data-inset="true">
	<li><a href="c_timeslots.php" data-rel="external" data-ajax="false">Edit Judging Timeslots</a></li>
	<li><a href="c_judge_scheduler.php" data-rel="external" data-ajax="false">Run the Judge Scheduler</a></li>
	<li><a href="c_jteam_edit.php" data-rel="external" data-ajax="false">Edit Judging Teams</a></li>
	<li><a href="#" data-rel="external" data-ajax="false">X Edit Team/Project Timeslot Assignments</a></li>
	</ul>

<?php
/*        $form_id = 'j_attending_form';
        form_begin($form_id, 'c_judging.php');
        form_text($form_id, 'j_not_attending', "Judging at the fair", $u['not_attending']);
        form_submit($form_id, 'save', 'Save', 'Information Saved');
        form_end($form_id);
*/	
?>

</div></div>
	

<?php
sfiab_page_end();
?>
