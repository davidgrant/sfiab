<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('email.inc.php');
require_once('committee/students.inc.php');

$mysqli = sfiab_init('committee');

$u = user_load($mysqli);

$page_id = 'c_tours';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}
switch($action) {

case 'status':
	$r = array();

	/* Look for a start message */
	$q = $mysqli->query("SELECT `id` FROM log WHERE `type`='tour_scheduler' AND `result`='1' AND `year`='{$config['year']}' ORDER BY `id` DESC LIMIT 1");
	if($q->num_rows != 1) {
		$r['running'] = false;
		$r['messages'] = "";
		$r['percent'] = 0;
	} else {
		$d = $q->fetch_assoc();

		/* Get all messages */
		$r['running'] = true;
		$r['messages'] = '';
		$q = $mysqli->query("SELECT * FROM log WHERE `id`>='{$d['id']}' AND `type`='tour_scheduler' AND year='{$config['year']}' ORDER BY `id`");
		$last_message = '';
		while($d = $q->fetch_assoc()) {
			if($last_message != $d['data']) {
				$r['messages'] .= $d['data']."<br/>";
				$last_message = $d['data'];
			}
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
	sfiab_log($mysqli, "tour_scheduler", $u, 1, "Initializing...");
	$mysqli->real_query("INSERT INTO queue(`command`,`result`) VALUES('tour_scheduler','queued')");
	queue_start($mysqli);
	form_ajax_response(array('status'=>0, ));
	exit();
	
}

sfiab_page_begin("Tours", $page_id);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	form_page_begin($page_id, array());
	$tours = tour_load_all($mysqli);
	$cats = categories_load($mysqli);

	$stats = array();
	$stats['by_grade'] = array();
	$stats['max_total'] = 0;
	$stats['min_total'] = 0;

	foreach($tours as $tid=>&$t) {
		/* Count max tour slots per grade */
		for($g=$t['grade_min']; $g <= $t['grade_max']; $g++) {
			if(!array_key_exists($g, $stats['by_grade'])) {
				$stats['by_grade'][$g] = 0;
			}
			$stats['by_grade'][$g] += $t['capacity_max'];
		}
		$stats['max_total'] += $t['capacity_max'];
		$stats['min_total'] += $t['capacity_min'];
	}

	$stats['students_assigned'] = 0;
	$students = students_load_all_accepted($mysqli);
	foreach($students as $sid => &$s) {
		if($s['tour_id'] === NULL) continue;
		if($s['tour_id'] > 0) {
			$stats['students_assigned'] += 1;
		}
	}

?>	<h3>Stats</h3>
	<ul>
	<li>Total Number of Tours: <b><?=count($tours)?></b>
	<li>Total Min/Max capacity: <b><?=$stats['min_total']?> - <?=$stats['max_total']?></b>
	<li>Capacity by category:
	<table>
	<thead><tr><th>Category</th><th>Grade</th><th>Max Capacity</th></tr></thead>
<?php
	foreach($cats as $cid=>&$c) {
		$c_str = $c['name'];
		for($g = $c['min_grade']; $g <=$c['max_grade']; $g++) {
?>			<tr><td><?=$c_str?></td><td><?=$g?></td><td><?=$stats['by_grade'][$g]?></td></tr>
<?php			$c_str = '';
		}
	} ?>
	</table>
	<li>Accepted students assigned to a tour: <b><?=$stats['students_assigned']?> / <?=count($students)?></b>
	</ul>
	



	<h3>Tours</h3>

	<ul data-role="listview" data-inset="true">
	<li><a href="c_tours_edit.php" data-rel="external" data-ajax="false">Tour Editor</a></li>
	</ul>

	<h3>Tour Assignments</h3>
	<p>Individual students can be assigned to a Tour on the <a href="c_user_list.php?roles[]=student">Student List/Editor</a> pages.

	<p>The tour scheduler takes about a minute to run and will:
	<ul><li><b>Delete all tour assignments</b> for this year
	<li>Assign students to tours
	</ul>
<?php
	$form_id = $page_id.'_run_form';
	form_begin($form_id, 'c_tours.php');
	form_button($form_id, 'run', 'Run', 'g', 'check', 'Delete all tour assignemnts and create new ones?');
	form_end($form_id);
?>

	<hr/>
	<h3>Scheduler Status</h3> 
	<table>
	<tr><td>Status:</td><td><div id="scheduler_percent" style="font-weight: bold;"></div></td></tr>
	<tr><td valign="top" >Output:</td><td><div id="scheduler_messages"></div></td></tr>
	</table>

	<p>The complete output log is available here: <a href="file.php?f=tour_scheduler_log" data-ajax="false">Tour Scheduler Log</a>


	</div></div>


<script>
var started = false;
var update_ticker = 0;

function c_tours_run_form_post_submit(form,data) {
	$("#c_tours_run_form_submit_run").attr('disabled', true);
	tour_scheduler_update();
	started = true;
}


function tour_scheduler_update() {
	$.ajax({url: 'c_tours.php',
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
				$('#scheduler_percent').html('Running: '+data.percent+'% ' + ticker_str);
				$('#scheduler_messages').html(data.messages);
				if(data.percent != 100) {
					setTimeout(tour_scheduler_update, 2000);
				} else {
					started = false;
				}
			}
		}
	});
}

var first_update = tour_scheduler_update();

</script>

<?php
sfiab_page_end();
?>
