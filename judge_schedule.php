<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('awards.inc.php');
require_once('committee/judges.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('judge'));

$u = user_load($mysqli);
$closed = sfiab_registration_is_closed($u);

$page_id = 'j_schedule';


$help = '
<p>';

sfiab_page_begin("Judge Schedule", $page_id, $help);

?>


<div data-role="page" id="<?=$page_id?>"  ><div data-role="main" class="sfiab_page" > 

	<h3>Judge Team and Project Assignments:<h3>
<?php
	/* Get all jteams this judge is on */
	$jteams = jteams_load_all_for_judge($mysqli, $u['uid']);
	$awards = award_load_all($mysqli);
	$projects = projects_load_all($mysqli);

	for($round=1;$round<=2;$round++) {
		$header_printed = false;
		foreach($jteams as &$jteam) {
			if($jteam['round'] != $round) continue;

			$a= [];
			foreach($jteam['user_ids'] as $uid) {
				$temp_u = user_load($mysqli, $uid);
				$a[] = $temp_u['name'];
			}
			$members = join(', ', $a);
			

			if(!$header_printed) {
?>				<h3>Round <?=$round?></h3>
<?php			}
			$header_printed = true;

?>
			<h4>Team #<?=$jteam['num']?> - <?=$jteam['name']?></h4>
			<p>Members: <?=$members?><br/>

			<p>Projects:
			<table>
<?php			foreach($jteam['project_ids'] as $pid) {
				$p =& $projects[$pid];
				$link = "<a data-ajax=\"false\" href=\"project_summary.php?pn={$p['number']}\">{$p['number']}</a>";
?>				<tr><td><?=$link?></td>
				<td><?=$p['title']?><td>
				</tr>
<?php			}?>
			</table>
<?php		}	
	}

?>
</div></div>
	

<?php
sfiab_page_end();
?>
