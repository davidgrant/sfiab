<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('timeslots.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);
sfiab_session_start($mysqli, array('committee'));

$u = user_load($mysqli);


$timeslots = timeslots_load_all($mysqli);

$page_id = 'c_timeslots';

sfiab_page_begin("Timeslot Editor", $page_id);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

	<h3>Sorry, can't edit yet, but here's the timeslots:</h3>
	<table>
	<tr><td>Round</td><td>Num</td><td>Start</td><td>Length</td></tr>
<?php	for($round=1;$round <= 2;$round++) {
		for($x=1;$x<9;$x++) {
			$num = $x + (($round == 1) ? 0 : 9);
			$ts = $timeslots[$num];
?>
			<tr><td><?=$ts['round']?></td>
			<td><?=$ts['num']?></td>
			<td><?=$ts['start']?></td>
			<td><?=$ts['length_minutes']?> min</td>
			</tr>
<?php		}
	}
?>
	</table>

	<h3>Timeslot Assignments</h3> 
	<ul data-role="listview" data-inset="true">
	<li><a href="c_timeslots_assign.php" data-rel="external" data-ajax="false">Automatically assign all timeslots</a></li>
	</ul>


</div></div>

<?php
sfiab_page_end();
?>
