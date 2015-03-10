<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('awards.inc.php');
require_once('sponsors.inc.php');

$mysqli = sfiab_init('committee');

$u = user_load($mysqli);

$page_id = 'c_awards_list';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}
switch($action) {
case 'order':
	/* Called when awards are reordered.  awards is an array
	 * of awards, in order, starting from ord=1 */
	$ord = 0;
//	print_r($_POST);

	foreach($_POST['awards'] as $aid) {
		$ord++;
		$aid = (int)$aid;
		if($aid == 0) continue;

		$mysqli->query("UPDATE awards SET ord='$ord' WHERE id='$aid'");
	}
	form_ajax_response(0);
	exit();

case 'prize_order':
	/* Called prizes in an award are  reordered.  prizes is an array
	 * of prizes, in order, starting from ord=1 */
	$ord = 0;
//	print_r($_POST);

	foreach($_POST['prizes'] as $prize_id) {
		$ord++;
		$prize_id = (int)$prize_id;
		if($prize_id == 0) continue;

		$mysqli->query("UPDATE award_prizes SET ord='$ord' WHERE id='$prize_id'");
	}
	form_ajax_response(0);
	exit();

case 'add':
	/* Create an award with a default name and ord=1 (move all other awards down one).
	 * Create a prize for it too */
	$mysqli->real_query("UPDATE awards SET `ord` = `ord`+1 WHERE year='{$config['year']}'");
	$mysqli->real_query("INSERT INTO awards (`name`,`ord`,`year`,`type`) VALUES('New Award',1,'{$config['year']}','special')");
	$aid = $mysqli->insert_id;
	$a = award_load($mysqli, $aid);
	$pid = prize_create($mysqli, $a);
	$a['prizes'][$pid]['name'] = 'New prize';
	$a['prizes'][$pid]['number'] = 1;
	$a['prizes'][$pid]['ord'] = 1;
	award_save($mysqli, $a);
	/* Print the award id, the js function takes this and redirects to c_awards_edit?aid=..." */
	print("$aid");
	exit();
}

$sponsors = sponsors_load_all($mysqli);
$fairs = fair_load_all_upstream($mysqli);

/* Update divisional awards.  This will be a noop if everything is up to date */
award_update_divisional($mysqli);


$help = 'Use the <button data-icon="gear" data-iconpos="notext" data-inline="true"></button> button to edit the award and prizes.  Drag and drop the <button data-icon="bars" data-iconpos="notext" data-inline="true"></button> icon to reorder the awards.  Drag the [=] handle before each prize to re-order the prizes within the award.  Awards/Prizes at the top of the list will go first in the award ceremony.';

sfiab_page_begin("Edit Awards", $page_id, $help);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php	$awards = award_load_all($mysqli); ?>

	<p>Use the <button data-icon="gear" data-iconpos="notext" data-inline="true"></button> button to edit the award and prizes.  Drag and drop the <button data-icon="bars" data-iconpos="notext" data-inline="true"></button> icon to reorder the awards. 
	<p>Drag the [=] handle before each prize to re-order the prizes within the award.  Awards/Prizes at the top of the list will go first in the award ceremony.

	<table id="awards_list" data-role="table" data-mode="none" class="table_stripes">
	<thead>
		<tr><th width="20%" align="center">Order /<br/>Type</th>
		<th align="center" width=65%>Name / Prize(s)</th>
		<th align="center" width=5%><font size=-1>Include<br/>in<br/>Script</font></th>
		<th align="center" width=5%><font size=-1>Sched.<br/>Judges</font></th>
		<th align="center" width=5%><font size=-1>Students<br/>Self-<br/>Nominate</font></th>
	</thead>
	<tbody>
<?php
	$current_type = '';
	foreach($awards as $aid=>$a) {

		$prizes = '';
		if(count($a['prizes']) == 0) {
			$prizes = '<b><font color="red">Award has NO prizes (it can\'t be awarded)</font></b>';
		}

		?>
		<tr id="<?=$a['id']?>" >
		<td align="center" width="20%">
			<b><span id="award_order_<?=$a['id']?>"><?=$a['ord']?></span></b><br/>
			<font size=-1><?=$award_types[$a['type']]?></font><br/>
			<div data-role="controlgroup" data-type="horizontal" data-mini="true">
				<a href="#" data-role="button" data-iconpos="notext" data-icon="bars" class='handle'>Move</a>
				<a href="c_awards_edit.php?aid=<?=$a['id']?>" data-mini="true" data-role="button" data-iconpos="notext" data-icon="gear" data-ajax="false">Edit</a>
			</div>
			
		</td>
		<td width="80%" colspan=4>
			<table width="100%">
				<tr><td width="75%"><b><?=$a['name']?></b>
<?php					if($a['upstream_award_id'] != 0) { ?>
						<br/><font size="-1">From Upstream Fair: <b><?=$fairs[$a['upstream_fair_id']]['name']?></b>
<?php					} ?>

					<br/><font size="-1">Presented By: <?=$a['presenter']?></font>

<?php					if($a['sponsor_uid'] > 0) {
						$s = $sponsors[$a['sponsor_uid']]['organization'];
					} else {
						$s = "<font color=red>NOT SET</font> -- Please set a sponsor";
					} ?>
					<br/><font size="-1">Sponsored By: <?=$s?></font>
						
				</td>
				<td width="5%" align="center"><?=$a['include_in_script']==1 ? '<font color=green>Script</font>':'<font color=blue>No<br>Script</font>'?></td>
				<td width="5%" align="center"><?=$a['schedule_judges']==1 ? '<font color=green>Judges</font>':'<font color=blue>No<br/>Judges</font>'?></td>
				<td width="5%" align="center"><?=$a['self_nominate']==1 ? '<font color=green>Nom.</font>':'<font color=blue>No<br/>Nom.</font>'?></td>
				</tr>
			</table>

<?php			/* There must be prizes to do anything */
			if(count($a['prizes']) == 0) {?>
				<br/><b><font color="red">Award has NO prizes which means it can't be awarded.  Edit the award to add prizes.</font></b>
<?php			} else { ?>

				<table class="prize_list table_stripes" width="100%" data-role="table" data-mode="none"  >
				<tbody>
<?php				foreach($a['prizes_in_order'] as &$p) { ?>
					<tr id="<?=$p['id']?>">
					<td width="30%"><span class="prize_handle">[=]</span> <?=$p['name']?></td>
<?php					if($p['number'] == 0) {
						$num = "unlimited";
					} else {
						$num = "x {$p['number']}";
					} ?>
					<td width="5%"><?=$num?></td>
<?php					$strs = array();
					if($p['cash'] > 0) $strs[] = "$".$p['cash']." cash";
					if($p['scholarship'] > 0) $strs[] = "$".$p['scholarship']." scholarship";
					if($p['value'] > 0) $strs[] = "$".$p['value']." value";
					foreach($p['trophies'] as $t) {
						$strs[] = "Trophy ".$award_trophies[$t];
					}
					$str = join("<br/>", $strs);
?>
					<td width="%"><?=$str?></td>
					</tr>
<?php				}?>
				</tbody>
				</table>
<?php			}?>				
		</td>
		</tr>

<?php	} ?>
	</tbody>
	</table>
	<a href="#" onclick="return award_create();" data-role="button" data-icon="plus" data-inline="true" data-ajax="false" data-theme="g">New Award</a>

</div></div>
	
<script>

$('#awards_list>tbody').sortable({
		'containment': 'parent',
		'opacity': 0.6,
		update: function(event, ui) {
			/* Create an array to store the awards, in order.  Award in index 0 will be assigned ord=1, and up from there */
			var awards = [];
			$(this).children('tr').each(function(index) {
				var award_id = $(this).attr('id');
				awards[index] = award_id;
				$('#award_order_'+award_id).text(index + 1);
			});
			$.post('c_awards_list.php', { action: "order", awards: awards }, function(data) {
				});

		}, 
		/* This fixes the width bug on drag where a table is compressed instead of maintaining its original width */
		helper: function(e, ui) {
			ui.children().each(function() {
				$(this).width($(this).width());
			});
			return ui;
		},
		handle: ".handle" 
	});


$('.prize_list>tbody').sortable({
		'containment': 'parent',
		'opacity': 0.6,
		update: function(event, ui) {
			/* Create an array to store the awards, in order.  Award in index 0 will be assigned ord=1, and up from there */
			var prizes = [];
			var aid = $(this).parents('tr').attr('id');
			$(this).children('tr').each(function(index) {
				var prize_id = $(this).attr('id');
				prizes[index] = prize_id;
				$('#prize_order_'+aid+'_'+prize_id).text(index + 1);
			});
			$.post('c_awards_list.php', { action: "prize_order", prizes: prizes }, function(data) {
				});

		}, 
		/* This fixes the width bug on drag where a table is compressed instead of maintaining its original width */
		helper: function(e, ui) {
			ui.children().each(function() {
				$(this).width($(this).width());
			});
			return ui;
		},
		handle: ".prize_handle" 
	});

function award_create() {
	$.post('c_awards_list.php', { action: "add" }, function(data) {
		window.location = "c_awards_edit.php?aid="+data;
	});
	return false;
}


</script>



<?php
sfiab_page_end();
?>
