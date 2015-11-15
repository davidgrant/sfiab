<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('reports.inc.php');
require_once('timeslots.inc.php');

$mysqli = sfiab_init('committee');

$u = user_load($mysqli);

$page_id = 'c_reports';
$help = '<p>Download Reports';

$action = '';
if(array_key_exists('action', $_GET)) {
	$action = $_GET['action'];
}
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

switch($action) {
case 'get_options':
	$rid = (int)$_POST['rid'];
	$r = report_load($mysqli, $rid);

	$vals = array();
	$vals['type'] = $r['option']['type'];
	$vals['include_registrations'] = $r['option']['include_registrations'];
	$vals['test'] = time(NULL);

	form_ajax_response(array('status'=>0, 'val'=>$vals));
	exit();


case 'download':
	$rid = (int)$_GET['rid'];
	$r = report_load($mysqli, $rid);
	/* Add report overrides */
	$include_registrations = $_GET['include_registrations'];
	if(array_key_exists($include_registrations, $report_options['include_registrations']['values'])) {
		$r['option']['include_registrations'] = $include_registrations;
	}

	$year = (int)$_GET['year'];
	$r['year'] = $year;

	if(array_key_exists($_GET['type'], $report_options['type']['values']))
		$r['option']['type'] = $_GET['type'];

	report_gen($mysqli, $r);
	exit();
}

sfiab_page_begin($u, "Download Reports", $page_id, $help);

$q = $mysqli->query("SELECT MIN(year) AS M FROM users");
$u = $q->fetch_assoc();
$min_year = $u['M'];

$timeslots = timeslots_load_rounds($mysqli);


?>
<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 
	<h3>Download Reports</h3>
<?php
	$form_id = $page_id.'_form';

	$reports = report_load_all($mysqli);
	$report_sec = array();
	foreach($reports as $r) {
		$sec = $r['section'] ;
		if(!array_key_exists($sec, $report_sec)) {
			$report_sec[$sec] = array();
		}
		$report_sec[$sec][$r['id']] = $r;
	}

	$val = '';
?>
	<form action="c_reports.php" id="<?=$form_id?>" method="GET" data-ajax="false" >
	<input type="hidden" name="action" value="" class="sfiab_form_action" />
<?php
	form_select_optgroup($form_id, 'rid', 'Report', $report_sec, $val);
	$options = array();
	foreach($report_options as $o=>$v) {
		$options[$o] = $v['default'];
	}
	$options['year'] = (int)$config['year'];

?>
	<div data-role="collapsible" data-collapsed="true" data-iconpos="right" data-collapsed-icon="carat-d" data-expanded-icon="carat-u" >
		<h3>Report Options</h3>
<?php
		$years = array();
		for($x=$config['year']; $x >= $min_year; $x--) {
			$years[(int)$x] = $x;
		}
	        form_radio_h($form_id, 'year', "Year", $years, $options);
	        form_radio_h($form_id, 'type', "Report Format", $report_options['type']['values'], $options);
	        form_select($form_id, 'include_registrations', "Include Registrations", $report_options['include_registrations']['values'], $options);
		$t = '';
	        form_text($form_id, 'test', "test", $t);
?>
	</div>
<?php	
	form_button($form_id, 'download', 'Download');
	form_end($form_id);
?>

	<h3>Award Ceremony Scripts</h3>

	<table>
	<tr><td>Junior Awards Ceremony: </td>
	<td>
		<div data-role="controlgroup" data-type="horizontal">
		<a href="report_ceremony.php?name=Junior%20Ceremony%201/3%20-%20Divisional%20Awards&award_types[]=divisional&show_pronunciation=1&group_by_prize=1&cats[]=1" data-ajax="false" data-role="button">Part 1/3 - Divisional</a>
		<a href="report_ceremony.php?name=Junior%20Ceremony%202/3%20-%20Special%20Awards&award_types[]=special&show_pronunciation=1&cats[]=1" data-ajax="false" data-role="button">Part 2/3 - Special</a>
		<a href="report_ceremony.php?name=Junior%20Ceremony%203/3%20-%20Grand%20Awards&award_types[]=grand&show_pronunciation=1&cats[]=1" data-ajax="false" data-role="button">Part 3/3 - Grand</a>
		</div>
	</td></tr>
	<tr><td>Int+Senior Awards Ceremony: </td>
	<td>
		<div data-role="controlgroup" data-type="horizontal">
		<a href="report_ceremony.php?name=Int+Senior%20Ceremony%201/4%20-%20Divisional%20Awards&award_types[]=divisional&show_pronunciation=1&group_by_prize=1&cats[]=2&cats[]=3" data-ajax="false" data-role="button">Part 1/4 - Divisional</a>
		<a href="report_ceremony.php?name=Int+Senior%20Ceremony%202/4%20-%20Special%20Awards&award_types[]=special&show_pronunciation=1&cats[]=2&cats[]=3" data-ajax="false" data-role="button">Part 2/4 - Special</a>
		<a href="report_ceremony.php?name=Int+Senior%20Ceremony%203/4%20-%20Junior%20Grand%20Awards&award_types[]=grand&show_pronunciation=1&cats[]=1" data-ajax="false" data-role="button">Part 3/4 - Junior Grand</a>
		<a href="report_ceremony.php?name=Int+Senior%20Ceremony%204/4%20-%20Grand%20Awards&award_types[]=grand&show_pronunciation=1&cats[]=2&cats[]=3" data-ajax="false" data-role="button">Part 4/4 - Grand</a>
		</div>
	</td></tr>
	</table>
	<br/>

	<h3>Judging Reports and Forms</h3>
	<table>
	<tr><td>Project Judging Schedules for the Students: <br/>(one per page, takes a few seconds to generate)</td>
	<td>
		<div data-role="controlgroup" data-type="horizontal">
		<a href="report_project_timetable.php" data-ajax="false" data-role="button">All Projects</a>
	</td></tr>
	<tr><td>Judging Team Schedules: <br/>(one judging team per page)</td>
	<td>
		<div data-role="controlgroup" data-type="horizontal">
<?php		foreach($timeslots as $round=>&$ts) { ?>
			<a href="report_jteam_timetable.php?round=<?=$round?>&type=divisional" data-ajax="false" data-role="button"><?=$ts['name']?> - Divisional</a>
			<a href="report_jteam_timetable.php?round=<?=$round?>&type=special" data-ajax="false" data-role="button"><?=$ts['name']?> - Special</a>
<?php		} ?>
		</div>
	</td></tr>
	<tr><td>Judging Team Hand-In Forms: <br/>(one judging team per page) </td>
	<td>
		<div data-role="controlgroup" data-type="horizontal">
<?php		foreach($timeslots as $round=>&$ts) { ?>
			<a href="report_jteam_forms.php?round=<?=$round?>&type=divisional" data-ajax="false" data-role="button"><?=$ts['name']?> - Divisional</a>
			<a href="report_jteam_forms.php?round=<?=$round?>&type=special" data-ajax="false" data-role="button"><?=$ts['name']?> - Special</a>
<?php		} ?>
		</div>
	</td></tr>
	</table>
	<br/>


	<h3>Edit Reports</h3>
	<ul data-role="listview" data-inset="true">
	<li><a href="c_reports_edit.php" data-rel="external" data-ajax="false">Edit Reports</a></li>
	</ul>

	<script>
		$( "#<?=$form_id?>_rid" ).change(function() {
			var new_rid = $("#<?=$form_id?>_rid option:selected").val();
			$.post('c_reports.php', { action: "get_options", rid: new_rid }, function(data) {
				sfiab_form_update_vals("<?=$form_id?>", data.val);
			}, "json");
		});
	</script>

</div></div>

<?php


sfiab_page_end();
?>
