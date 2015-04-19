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

$page_id = 'c_stats';

$timeslots = timeslots_load_all($mysqli);
$num_rounds = count($timeslots);

sfiab_page_begin("Statistics", $page_id);
?>

<div data-role="page" id="<?=$page_id?>" class="sfiab_page" > 

<?php	form_page_begin($page_id, array());

?>	
	<h3>Statistics</h3> 

	<h3>Other Fairs</h3> 
	<ul data-role="listview" data-inset="true">
	<li><a href="c_ysc_stats.php" data-rel="external" data-ajax="false">Send Stats to YSC</a></li>
	<li><a href="c_" data-rel="external" data-ajax="false">XXX Send Stats to Upstream Fairs</a></li>
	<li><a href="c_" data-rel="external" data-ajax="false">XXX Pull Stats from Feeder Fairs</a></li>
	</ul>

	<h3>Logs</h3> 
	<ul data-role="listview" data-inset="true">
	<li><a href="c_logs.php" data-rel="external" data-ajax="false">XXX View Logs</a></li>
	</ul>

</div>
	

<?php
sfiab_page_end();
?>
