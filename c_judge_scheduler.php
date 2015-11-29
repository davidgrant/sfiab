<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('email.inc.php');
require_once('timeslots.inc.php');

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

	/* Look for a start message */
	$q = $mysqli->query("SELECT `id` FROM log WHERE `type`='judge_scheduler' AND `result`='1' AND `year`='{$config['year']}' ORDER BY `id` DESC LIMIT 1");
	if($q->num_rows != 1) {
		$r['running'] = false;
		$r['messages'] = "";
		$r['percent'] = 0;
	} else {
		$d = $q->fetch_assoc();

		/* Get all messages */
		$r['running'] = true;
		$r['messages'] = '';
		$q = $mysqli->query("SELECT * FROM log WHERE `id`>='{$d['id']}' AND `type`='judge_scheduler' AND year='{$config['year']}' ORDER BY `id`");
		while($d = $q->fetch_assoc()) {
			$r['messages'] .= $d['data']."<br/>";
			$r['percent'] = $d['result'];
			if($d['result'] == 100) {
				$r['running'] = false;
			}
		}
	}
	/* Get data from most recent run of the scheduler from the log */

	print(json_encode($r));
	exit();
	
case 'run':
	sfiab_log($mysqli, "judge_scheduler", $u, 1, "Initializing...");
	$mysqli->real_query("INSERT INTO queue(`command`,`result`) VALUES('judge_scheduler','queued')");
	queue_start($mysqli);
	form_ajax_response(array('status'=>0, ));
	exit();

case 'run_ts':
	sfiab_log($mysqli, "judge_scheduler", $u, 1, "Initializing...");
	$mysqli->real_query("INSERT INTO queue(`command`,`result`) VALUES('timeslot_scheduler','queued')");
	queue_start($mysqli);
	form_ajax_response(array('status'=>0, ));
	exit();

}

sfiab_page_begin($u, "Judge Scheduler", $page_id);

$timeslots = timeslots_load_all($mysqli);
?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	form_page_begin($page_id, array());
	$form_id = $page_id.'_form';

?>	<h3>Judge Scheduler Settings</h3> 
	<p>These settings can be changed on the <a href="c_config_variables.php#Judge_Scheduler" data-ajax="false">Configuration Variables - Judge Scheduler</a> 
	and <a href="c_timeslots.php" data-ajax="false">Timeslots</a> pages.
		

	<table data-role="table" data-mode="none" class="table_stripes">
<?php	
	$q = $mysqli->query("SELECT * FROM config WHERE category='Judge Scheduler' ORDER BY `order`,var");
	while($r = $q->fetch_assoc()) { 
		if($r['var'] == 'judge_divisional_prizes' || $r['var'] == 'judge_divisional_distribution') {
			continue;
		} ?>
		<tr><td width="25%"><?=$r['name']?></td><td width="75%"><b><?=$r['val']?></b></td></tr>
<?php	} ?>
	<tr><td>Divisional Prizes and Distribution</td><td><b>
<?php
	$prizes = explode(',', $config['judge_divisional_prizes']);
	$dist = explode(',', $config['judge_divisional_distribution']);
	for($i=0;$i<count($prizes);$i++) { ?>
		<?=$prizes[$i]?> - <?=$dist[$i]?>%<br/>
<?php	} ?>
	</b></td></tr>
	<tr><td>Timeslots</td>
		<td><table>
<?php		foreach($timeslots as &$ts) { 
			$start_ts = strtotime($config['date_fair_begins']) + ($ts['start'] * 60);
			$end_ts = $start_ts + ($ts['num_timeslots'] * $ts['timeslot_length'] * 60);
			$start = date("F j, Y h:ia", $start_ts); 
			$end = date("h:ia", $end_ts); 
			?>
			<tr><td><?=$ts['name']?> -</td>
			<td><?=$start?> - <?=$end?> -</td>
			<td><?=$ts['num_timeslots']?> timeslots of <?=$ts['timeslot_length']?> minutes each.</td></tr>
<?php		} ?>
		</table>
	</td></tr>
	</table>

	<hr/>
	<h3>Run The Scheduler</h3> 
	<p>The judge scheduler takes about one minute to run.  It will:
	<ul><li><b>Delete all automatically created judging teams</b> (e.g., from a previous run of this scheduler), manually created judging teams are not touched.
	<li>Create new judging teams for divisional, CUSP, and every special award marked as "schedule judges"
	<li>Assign judges to teams
	<li>Assign projects to divisional teams and special awards teams
	<li>Assign projects to specific judges for divisional teams
	<li>Create a judging schedule for each judging team and project (printable on the reports page)
	</ul>
	<p>Additional help is is available: <a href="help/judging.html#judge_scheduler" data-ajax="false">Judge Scheduler Documentation</a>

<?php
	$form_id = $page_id.'_run_form';
	form_begin($form_id, 'c_judge_scheduler.php');
	form_submit_enabled($form_id, 'run', 'Run', 'Started, check status below', 'g', 'check', 'Delete all judging teams and assignments and create new ones?');
	form_submit_enabled($form_id, 'run_ts', 'Assign Timeslots Only', 'Started, check status below', 'l', 'check', 'Recalculate all judge and project timetables?');
	form_end($form_id);
?>

	<hr/>
	<h3>Scheduler Status</h3> 
	<table>
	<tr><td>Status:</td><td><div id="scheduler_percent" style="font-weight: bold;"></div></td></tr>
	<tr><td valign="top" >Output:</td><td><div id="scheduler_messages"></div></td></tr>
	</table>

	<p>The complete output log is available here: <a href="file.php?f=judge_scheduler_log" data-ajax="false">Judge Scheduler Log</a>



</div></div>

<script>
var started = false;
var update_ticker = 0;

function c_judge_scheduler_run_form_post_submit(form,data) {
	judge_scheduler_update();
	started = true;
}


function judge_scheduler_update() {
	$.ajax({url: 'c_judge_scheduler.php',
		type: 'POST',
		dataType: 'json',
		data: { action: 'status' },
		success: function(data) {
			if(!data.running && !started) {
				$('#scheduler_percent').html('Not Running');
				$('#scheduler_messages').html(data.messages);
			} else {
				ticker_str = '';
				update_ticker += 1;
				for(x=0;x<update_ticker;x++) ticker_str += '.';
				if(update_ticker == 3) update_ticker = 0;

				if(data.percent != 100) {
					setTimeout(judge_scheduler_update, 1000);
					$('#scheduler_percent').html('Running: '+data.percent+'% ' + ticker_str);
				} else {
					started = false;
					$('#scheduler_percent').html('Done.');
				}
				$('#scheduler_messages').html(data.messages);
			}
		}
	});
}

var first_update = judge_scheduler_update();

</script>

	
<?php
sfiab_page_end();
?>
