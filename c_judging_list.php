<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('awards.inc.php');
require_once('committee/judges.inc.php');

$mysqli = sfiab_init('committee');

$u = user_load($mysqli);


if(array_key_exists('switchto', $_GET)) {


	header("Location: j_main.php");
	exit();
}

$page_id = 'c_judging_list';

sfiab_page_begin($u, "Judge List", $page_id);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

Work in progress...

	<ul data-role="listview" data-filter="true" data-filter-placeholder="Search..." data-inset="true">

<?php

$judges = judges_load_all($mysqli, $config['year']);

foreach($judges as &$j) {

	$filter_text = "{$j['name']} {$j['organization']}";

?>
	<li data-filtertext="<?=$filter_text?>"><a href="#">
		<h3><?=$j['name']?></h3>
		Text about the judge
		
	</a></li>
<?php
}
?>
</ul>



<?php
sfiab_page_end();
?>
