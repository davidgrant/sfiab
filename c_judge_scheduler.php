<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');

$mysqli = sfiab_init('committee');

$u = user_load($mysqli);

$page_id = 'c_judge_scheduler';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}
switch($action) {

case 'run':
	if(!file_exists("logs")) {
		mkdir("logs");
	}

	print("hi");
	print exec("./src/sfiab_annealer judges > logs/judge_scheduler.log &");

	print("hi");
	form_ajax_response(array('status'=>0, ));
	exit();
	
}

sfiab_page_begin("Judge Scheduler", $page_id);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	form_page_begin($page_id, array());
	$form_id = $page_id.'_form';

?>	<h3>Judge Scheduler Settings</h3> 
	<p>These settings can be changed on the <a href="c_config_variables.php#Judge_Scheduler" data-ajax="false">Configuration Variables - Judge Scheduler</a> page.
		

	<table data-role="table" data-mode="none" class="table_stripes">
	<thead><tr><th>Variable</th><th>Value</th></tr></thead>
<?php	
	$q = $mysqli->query("SELECT * FROM config WHERE category='Judge Scheduler' ORDER BY `order`,var");
	while($r = $q->fetch_assoc()) { 
		if($r['var'] == 'judge_divisional_prizes' || $r['var'] == 'judge_divisional_distribution') {
			continue;
		} ?>
		<tr><td><?=$r['name']?></td><td><b><?=$r['val']?></b></td></tr>
<?php	} ?>
	<tr><td>Divisional Prizes and Distribution</td><td><b>
<?php
	$prizes = explode(',', $config['judge_divisional_prizes']);
	$dist = explode(',', $config['judge_divisional_distribution']);
	for($i=0;$i<count($prizes);$i++) { ?>
		<?=$prizes[$i]?> - <?=$dist[$i]?>%<br/>
<?php	} ?>
	</b></td></tr>
	</table>

	<hr/>
	<h3>Run The Scheduler</h3> 
	<p>The scheduler is much faster than the old one, but it'll still take
	about 2-3 minutes to run.  There's no indication (yet) when it's done, but
	you can watch for the output here (just keep reloading after the scheduler is 
	started it until text shows up): <a data-ajax="false" href="logs/judge_scheduler.log">Log
	File</a>

	<p>There will be no indication that you pressed the button below
	either.  Just press it once, then check the log file... it'll be empty.
	Then in 2-3min i'll show some data.


<?php
	$form_id = $page_id.'_run_form';
	form_begin($form_id, 'c_judge_scheduler.php');
	form_button($form_id, 'run', 'Run');
	form_end($form_id);
?>


</div></div>
	
<?php
sfiab_page_end();
?>
