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
case 'save':
	$fields = array('judge_div_min_projects', 'judge_div_max_projects',
			'judge_div_min_team','judge_div_max_team',
			'judge_cusp_min_team','judge_cusp_max_team',
			'judge_sa_min_projects','judge_sa_max_projects');
	$t = array();
	foreach($fields as $f) {
		post_int($t[$f],$f);
		$mysqli->query("UPDATE config SET `val`='{$t[$f]}' WHERE `var`='$f' AND year='{$config['year']}'");
	}
	form_ajax_response(0);
	exit();

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

sfiab_page_begin("Judge Scheudler", $page_id);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	form_page_begin($page_id, array());
	$form_id = $page_id.'_form';

?>	<h3>Judge Scheduler Settings</h3> 
<?php	
	form_begin($form_id, 'c_judge_scheduler.php');
	form_int($form_id, "judge_div_min_projects", 'Divisional - Min Projects per Judge', $config['judge_div_min_projects']);
	form_int($form_id, "judge_div_max_projects", 'Divisional - Max Projects per Judge', $config['judge_div_max_projects']);
	form_int($form_id, "judge_div_min_team", 'Divisional - Min Judges per Team', $config['judge_div_min_team']);
	form_int($form_id, "judge_div_max_team", 'Divisional - Max Judges per Team', $config['judge_div_max_team']);
	form_int($form_id, "judge_cusp_min_team", 'Cusp - Min Judges per Team', $config['judge_cusp_min_team']);
	form_int($form_id, "judge_cusp_max_team", 'Cusp - Max Judges per Team', $config['judge_cusp_max_team']);
//	form_int($form_id, "judge_sa_min_projects", 'Special Awards - Min Projects per Judge', $config['judge_sa_min_projects']);
	form_int($form_id, "judge_sa_max_projects", 'Special Awards - Max Projects per Judge', $config['judge_sa_max_projects']);
	form_submit($form_id, 'save', 'Save', 'Saved');
	form_end($form_id);
?>
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
