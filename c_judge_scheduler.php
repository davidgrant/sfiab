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

case 'status':
	$r = array();
	$r['running'] = false;
	$r['messages'] = time(NULL);
	/* Get data from most recent run of the scheduler from the log */

	print(json_encode($r));
	exit();
	
case 'run':
//	$mysqli->real_query("INSERT INTO queue(`command`,`result`) VALUES('judge_scheduler','queued')");
//	queue_start($mysqli);
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
	<p>The scheduler takes about one minute to run.  It will:
	<ul><li><b>Delete all automatically created judging teams</b> (e.g., from a previous run of this scheduler), manually created judging teams are not touched.
	<li>Create new judging teams for divisional, CUSP, and every special award marked as "schedule judges"
	<li>Assign judges to teams
	<li>Assign projects to divisional teams and special awards teams
	<li>Assign projects to specific judges for divisional teams
	<li>Create a judging schedule for each judging team and project (printable on the reports page)
	</ul>

<?php
	$form_id = $page_id.'_run_form';
	form_begin($form_id, 'c_judge_scheduler.php');
	form_button($form_id, 'run', 'Run');
	form_end($form_id);
?>

	<hr/>
	<h3>Scheduler Status</h3> 
	<table>
	<tr><td>Status:</td><td><div id="scheduler_percent" style="font-weight: bold;"></div></td></tr>
	<tr><td>Output:</td><td><div id="scheduler_messages"</div></td></tr>
	</table>






</div></div>

<script>
function c_judge_scheduler_run_form_post_submit(form,data) {
	$("#c_judge_scheduler_run_form_submit_run").attr('disabled', true);
	judge_scheduler_update();
}

function judge_scheduler_update() {
	$.ajax({url: 'c_judge_scheduler.php',
		type: 'POST',
		dataType: 'json',
		data: { action: 'status' },
		success: function(data) {
			if(!data.running) {
				$('#scheduler_percent').html('Not Running');
				$('#scheduler_messages').html(data.messages);
			} else {
				$('#scheduler_percent').html('Running: 0%');
				$('#scheduler_messages').html("Starting");
				setTimeout(judge_scheduler_update, 2000);
			}
		}
	});
}

var first_update = judge_scheduler_update();

</script>

	
<?php
sfiab_page_end();
?>
