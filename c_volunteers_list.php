<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('awards.inc.php');
require_once('committee/volunteers.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);
sfiab_session_start($mysqli, array('committee'));

$u = user_load($mysqli);


$page_id = 'c_volunteers_list';

sfiab_page_begin("Volunteer List", $page_id);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

Work in progress...

	<ul data-role="listview" data-filter="true" data-filter-placeholder="Search..." data-inset="true">

<?php

$volunteers = volunteers_load_all($mysqli, $config['year']);

foreach($volunteers as &$j) {

	$filter_text = "{$j['name']} {$j['organization']}";

?>
	<li data-filtertext="<?=$filter_text?>"><a href="#">
		<h3><?=$j['name']?></h3>
		<?=$j['not_attending'] ? 'Not Attending' :($j['v_complete'] ? 'Complete' : 'Incomplete' ) ?>
		
	</a></li>
<?php
}
?>
</ul>



<?php
sfiab_page_end();
?>
