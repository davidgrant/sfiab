<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('committee/judges.inc.php');
require_once('awards.inc.php');

$mysqli = sfiab_init('committee');

$u = user_load($mysqli);

$page_id = 'c_config';

sfiab_page_begin($u, "Configuration", $page_id);
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

	<h3>SFIAB Configuration</h3> 
	<ul data-role="listview" data-inset="true">
	<li><a href="c_config_variables.php" data-rel="external" data-ajax="false">Configuration Variables</a></li>
	<li><a href="c_config_cms.php" data-rel="external" data-ajax="false">Page Text and Signature Form</a></li>
	<li><a href="c_config_categories.php" data-rel="external" data-ajax="false">Age Categories and Grades</a></li>
	<li><a href="c_config_challenges.php" data-rel="external" data-ajax="false">Challenges</a></li>
	<li><a href="c_config_schools.php" data-rel="external" data-ajax="false">Schools</a></li>
	<li><a href="c_config_logo.php" data-rel="external" data-ajax="false">Fair Logo</a></li>
	</ul>

	<h3>External Fairs</h3> 
	<ul data-role="listview" data-inset="true">
	<li><a href="c_config_fairs.php" data-rel="external" data-ajax="false">Feeder/Upstream Fairs (Edit and Synchronize Data)</a></li>
	</ul>

	<h3>SFIAB Database Management</h3> 
	<ul data-role="listview" data-inset="true">
	<li><a href="c_rollover.php" data-rel="external" data-ajax="false">Rollover Fair Year</a></li>
	<li><a href="c_backup.php" data-rel="external" data-ajax="false">Backup / Restore Database</a></li>
	</ul>

</div></div>
	

<?php
sfiab_page_end();
?>
