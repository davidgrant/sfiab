<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('timeslots.inc.php');

$mysqli = sfiab_init('committee');

$u = user_load($mysqli);

$timeslots = timeslots_load_all($mysqli);
$page_id = 'c_timeslots';
$form_id = $page_id."_form";


$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

switch($action) {
case 'save':
	$fair_start = strtotime($config['date_fair_begins']);

	/* Iterate over the $_POST['ts'][timeslot_id] and save data for each timeslot */
	foreach($_POST['ts'] as $tid=>$p) {

		$ts = &$timeslots[$tid];
		post_int($ts['start'], array('ts', $tid, 'start'));
//		$timeslot_start = strtotime($_POST['ts'][$tid]['start']);
//		if($timeslot_start === false) {
//			$ts['start'] = 0;
//		} else {
//			$ts['start'] = (int)(($timeslot_start - $fair_start) / 60);
//		}
		if($ts['start'] < 0 || $ts['start'] > 100000) {
			$ts['start'] = 0;
		}

		post_int($ts['round'],array('ts', $tid, 'round') );
		post_int($ts['num_timeslots'],array('ts', $tid, 'num_timeslots') );
		post_int($ts['timeslot_length'],array('ts', $tid, 'timeslot_length'));
		timeslot_save($mysqli, $ts);
	}	
	form_ajax_response(0);
	exit();


case 'del':
	$tid = (int)$_POST['tid'];
	if($tid > 0) {
		timeslot_delete($mysqli, $tid);
		form_ajax_response(0);
		exit();
	}
	form_ajax_response(1);
	exit();

case 'add':
	$tid = timeslot_create($mysqli);
	$ts = timeslot_load($mysqli, $tid);
	$x = count($timeslots) + 1;
	$ts['name'] = "Round ".$x;
	print_timeslot_div($form_id, $tid, $ts);
	exit();
}



$help = '<p>Edit the award';
sfiab_page_begin($u, "Timeslot Editor", $page_id, $help);


function print_timeslot_div($form_id, $tid, &$ts)
{
	global $config;

?>
	<div data-tid="<?=$tid?>">
		<h3>Judging Round Number <span class="timeslot_div_round"><?=$ts['round'] + 1?></span></h3>
<?php	
		form_text($form_id, "ts[$tid][name]", "Name", $ts['name']);
		form_text($form_id, "ts[$tid][start]", "Round Start Time (in minutes after fair begins at {$config['date_fair_begins']})", $ts['start']);
		form_int($form_id, "ts[$tid][num_timeslots]", 'Number of Timeslots in Round', $ts['num_timeslots']);
		form_int($form_id, "ts[$tid][timeslot_length]", 'Length of each Timeslot (minutes)', $ts['timeslot_length']);

?>
		<div align="right">
			<a href="#" onclick="return timeslot_delete(<?=$tid?>);" data-role="button" data-icon="delete" data-inline="true" data-theme="r">Delete Timeslot</a>
		</div>
	</div>
<?php
}

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

	<h3>Timeslots</h3> 
	<ul>
	<li>The rounds are automatically numbered and should not overlap
	<li>The start time is relative to the start of the fair, defined in the configuration section.  Currently: <?=$config['date_fair_begins']?>.  Specify in minutes, don't have calendar integration for the start time yet, sorry.
	<li>I'll clean up this interface when I have time, but for now it works
	</ul>
	<p>So for example, Round one might be defined as: 120 start, 9 timeslots,
	20 minute timeslot length.  That means the first judging round starts 2
	hours after the fair begins and has 9 timeslots each of 20 minutes (3
	hours total).
	<p>The judge scheduler uses the round number (automatically assigned in order), number of timeslots, and timeslot length to schedule judges.  
	<p>The report system uses the timeslot start time, number of timeslots, and timeslot length to create project and judge schedules.
	<p>The judge registration pages uses the timeslot start time, total length (num timeslots * timeslot length), and name to ask judges to select which judging rounds (by name) they are available for judging.

<?php	form_begin($form_id, 'c_timeslots.php'); ?>

	<div id="timeslots" >
<?php	
	foreach($timeslots as &$ts) {
		$tid = $ts['id'];
		print_timeslot_div($form_id, $tid, $ts);
	} ?>
	</div>
<?php
	form_submit($form_id, 'save', 'Save Timeslot(s)', 'Timeslot(s) Saved'); ?>
	<a href="#" onclick="return timeslot_create();" data-role="button" data-icon="plus" data-inline="true" data-theme="g">Create a New Timeslot</a><br/>
<?php
	form_end($form_id);

?>

</div></div>
	
<script>



/* Delete a timeslot */
function timeslot_delete(id) {
	if(confirm('Really delete this timeslot?') == false) return false;
	$.post('c_timeslots.php', { action: "del", tid: id }, function(data) {
		if(data.status == 0) {
			/* Remove the div and everything inside it */
			$("#timeslots>div[data-tid="+id+"]").fadeOut("slow", function() {
					$(this).remove();
				});

			/* Renumber the remaining timeslots so they're in order.  The timeslot_delete() does
			 * this in the database, so we're just being consistent */
			$("#timeslots").children('div').each(function(index) {
					$(this).find('h3>.timeslot_div_round').html(index + 1);
				});
		}
	}, "json");
	return false;
}

function timeslot_create(tid) {
	$.post('c_timeslots.php', { action: "add" }, function(data) {
		$("#timeslots").append(data);
		$("#timeslots").trigger('create');
	});
	return false;
}

</script>


<?php
sfiab_page_end();
?>
