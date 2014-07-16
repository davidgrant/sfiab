<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('awards.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('committee'));

$u = user_load($mysqli);

$page_id = 'c_awards_edit';

$page = '';
if(array_key_exists('page',$_GET)) {
	$page = $_GET['page'];
}

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}
switch($action) {
case 'order':
	/* Called when awards are reordered.  awards is an array
	 * of awards, in order, starting from ord=1 */
	$ord = 0;
	print_r($_POST);

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
	print_r($_POST);

	foreach($_POST['prizes'] as $prize_id) {
		$ord++;
		$prize_id = (int)$prize_id;
		if($prize_id == 0) continue;

		$mysqli->query("UPDATE award_prizes SET ord='$ord' WHERE id='$prize_id'");
	}
	form_ajax_response(0);
	exit();


case 'save':
	$aid = (int)$_POST['aid'];
	$a = award_load($mysqli, $aid);
	post_text($a['name'],'name');
	post_text($a['type'],'type');
	post_text($a['c_desc'],'c_desc');
	post_text($a['j_desc'],'j_desc');
	post_text($a['s_desc'],'s_desc');
	post_text($a['presenter'],'presenter');
	post_bool($a['schedule_judges'],'schedule_judges');
	post_int($a['sponsor_uid'], 'sponsor_uid');
	post_bool($a['include_in_script'],'include_in_script');
	post_bool($a['self_nominate'],'self_nominate');
	post_int($a['ord'],'ord');
	post_array($a['categories'], 'categories');
	award_save($mysqli, $a);
	form_ajax_response(0);
	exit();

case 'psave':
	$pid = (int)$_POST['pid'];
	$p = prize_load($mysqli, $pid);

	post_text($p['name'],'name');
	post_int($p['number'],'number');
	post_float($p['cash'],'cash');
	post_float($p['scholarship'],'scholarship');
	post_float($p['value'],'value');
	post_bool($p['self_nominate'],'self_nominate');
	post_bool($p['external_register_winners'],'external_register_winners');
	post_int($p['ord'],'ord');
	$p['trophies'] = array();
	if(array_key_exists('trophies', $_POST)) {
		foreach($_POST['trophies'] as $index=>$val) {
			$p['trophies'][] = $val;
		}
	}
	generic_save($mysqli, $p, "award_prizes", "id");
	form_ajax_response(0);
	exit();


case 'del':
	$aid = (int)$_POST['aid'];
	if($aid > 0) {
		/* Delete prizes and awards */
		$mysqli->real_query("DELETE FROM award_prizes WHERE award_id='$aid'");
		$mysqli->real_query("DELETE FROM awards WHERE id='$aid'");
		form_ajax_response(0);
		exit();
	}
	form_ajax_response(1);
	exit();

case 'pdel':
	$pid = (int)$_POST['pid'];
	if($pid > 0) {
		/* Delete prizes and awards */
		$mysqli->real_query("DELETE FROM award_prizes WHERE id='$pid'");
		form_ajax_response(0);
		exit();
	}
	form_ajax_response(1);
	exit();
}

$award_types = array('divisional' => 'Divisional','special'=>'Special','grand'=>'Grand','other'=>'Other');
$trophies = array('keeper'=>'Student Keeper','return'=>'Student Return','school_keeper'=>'School Keeper','school_return'=>'School Return');

$help = 'Use the <button data-icon="gear" data-iconpos="notext" data-inline="true"></button> button to edit the award and prizes.  Drag and drop the <button data-icon="bars" data-iconpos="notext" data-inline="true"></button> icon to reorder the awards.  Drag the [=] handle before each prize to re-order the prizes within the award.  Awards/Prizes at the top of the list will go first in the award ceremony.';


sfiab_page_begin("Edit Awards", $page_id, $help);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	switch($page) {
	case 'edit':
		$form_id = $page_id.'_edit';
		$aid = (int)$_GET['aid'];
		$cats = categories_load($mysqli);

		if($aid == 0) {
			$mysqli->real_query("INSERT INTO awards (`name`,`year`) VALUES('New Award','{$config['year']}')");
			$aid = $mysqli->insert_id;
		}
		$show_pid = NULL;
		if(array_key_exists('pid', $_GET)) {
			$show_pid = (int)$_GET['pid'];
			if($show_pid == 0) {
				$mysqli->real_query("INSERT INTO award_prizes (`name`,`award_id`) VALUES('New Prize','$aid')");
				$show_pid = $mysqli->insert_id;
			}
		}


		$a = award_load($mysqli, $aid);
		form_begin($form_id, 'c_awards_edit.php?page=edit&aid='.$aid);
		form_hidden($form_id, 'aid',$a['id']);
		form_text($form_id, 'name', "Name", $a);
		form_select($form_id, 'type', "Type", $award_types, $a);
		form_textbox($form_id, 's_desc', "Student Description (Student and Judges see this, public on website, goes in ceremony script)", $a);
		form_textbox($form_id, 'j_desc', "Judge Description (Only judges see this)", $a);
		form_textbox($form_id, 'c_desc', "Committee Notes (Only the committee sees this)", $a);
		form_text($form_id, 'sponsor_organization', "Sponsor", $a);

		form_check_group($form_id, 'categories', "Categories", $cats, $a);
		form_yesno($form_id, 'schedule_judges', 'Schedule Judges', $a);
		form_yesno($form_id, 'self_nominate', 'Students can Self Nominate', $a);
		form_yesno($form_id, 'include_in_script', 'Include in Ceremony Script', $a);
		form_text($form_id, 'presenter', "Presenter", $a);
		form_submit($form_id, 'save', 'Save', 'Award Saved');
		form_end($form_id);
?>		<h3>Prizes</h3> 
<?php		foreach($a['prizes'] as $p) {
			$pid = $p['id'];
			$form_id = $page_id.'_prize'.$pid.'_form';
			$show = ($pid == $show_pid) ? 'data-collapsed="false"' : '';
?>			<div data-role="collapsible" id="prize_div_<?=$pid?>" <?=$show?>>
				<h3><?=$p['name']?></h3>
				<input type="hidden" name="pid<?=$x?>" value="<?=$pid?>"/>
<?php	
				form_begin($form_id, 'c_awards_edit.php?page=edit&aid='.$aid);
				form_hidden($form_id, 'pid', $pid);
				form_text($form_id, "name", 'Name', $p);
				form_int($form_id, "number", "Number Available to be Awarded", $p);
				form_text($form_id, "cash", 'Cash Award', $p);
				form_text($form_id, "scholarship", 'Scholarship', $p);
				form_text($form_id, "value", 'Prize Value', $p);
				form_check_group($form_id, "trophies", "Trophies", $trophies, $p);
				form_yesno($form_id, 'external_register_winners', "(External) Register Winners at this fair", $p);
				form_int($form_id, "ord", "Order", $p);
	
?>	
				<div class="ui-grid-a">
				<div class="ui-block-a"> 
				<?=form_submit($form_id, 'psave', 'Save Prize', 'Prize Saved');?>
				</div>
				<div class="ui-block-b"> 
				<a href="#" onclick="return prize_delete(<?=$p['id']?>);" data-role="button" data-icon="delete" data-theme="r">Delete Prize</a>
				</div></div>
<?php				form_end($form_id); ?>
			</div>
<?php		} ?>
		<a href="c_awards_edit.php?page=edit&aid=<?=$aid?>&pid=0" data-role="button" data-icon="plus" data-theme="g" data-ajax="false">New Prize</a>
<?php

		break;


	default:
		$awards = award_load_all($mysqli);
	?>
		<p>Use the <button data-icon="gear" data-iconpos="notext" data-inline="true"></button> button to edit the award and prizes.  
		<p>Drag and drop the <button data-icon="bars" data-iconpos="notext" data-inline="true"></button> icon to reorder the awards. 
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
					<a href="c_awards_edit.php?page=edit&aid=<?=$a['id']?>" data-mini="true" data-role="button" data-iconpos="notext" data-icon="gear" data-ajax="false">Edit</a>
				</div>
				
			</td>
			<td width="80%" colspan=4>
				<table width="100%">
					<tr><td width="75%"><b><?=$a['name']?></b>
<?php					if($a['presenter'] != '') { ?>
						<br/><font size="-1">Presented By: <?=$a['presenter']?></font>
<?php					} ?>
					</td>
					<td width="5%" align="center"><?=$a['include_in_script']==1 ? '<font color=green>Script</font>':'<font color=blue>No<br>Script</font>'?></td>
					<td width="5%" align="center"><?=$a['schedule_judges']==1 ? '<font color=green>Judges</font>':'<font color=blue>No<br/>Judges</font>'?></td>
					<td width="5%" align="center"><?=$a['self_nominate']==1 ? '<font color=green>Nom.</font>':'<font color=blue>No<br/>Nom.</font>'?></td>
					</tr>
				</table>

				<table class="prize_list table_stripes" width="100%" data-role="table" data-mode="none"  >
				<tbody>
<?php					foreach($a['prizes'] as &$p) { ?>
					<tr id="<?=$p['id']?>">
<?php /*					<td id="prize_order_<?=$a['id']?>_<?=$p['id']?>"><?=$p['ord']?></td> */ ?>
					<td width="30%"><span class="prize_handle">[=]</span> <?=$p['name']?></td>
					<td width="5%">x<?=$p['number']?></td>
<?php					$strs = array();
					if($p['cash'] > 0) $strs[] = "$".$p['cash']." cash";
					if($p['scholarship'] > 0) $strs[] = "$".$p['scholarship']." scholarship";
					if($p['value'] > 0) $strs[] = "$".$p['value']." value";
					foreach($p['trophies'] as $t) {
						$strs[] = "Trophy ".$trophies[$t];
					}
					$str = join("<br/>", $strs);
?>
					<td width="%"><?=$str?></td>
					</tr>
<?				}?>
				</tbody>
				</table>
						
			</td>



			</tr>

<?php		} ?>
</tbody>
		</table>
		<a href="c_awards_edit.php?page=edit&aid=0" data-role="button" data-icon="plus" data-ajax="false" data-theme="g">New Award</a>
	

<?php	}
?>


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
			$.post('c_awards_edit.php', { action: "order", awards: awards }, function(data) {
				});

		}, 
		/* This fixes the width bug on drag where a table is compressed instead of maintaining its original width */
		helper: function(e, ui) {
			ui.children().each(function() {
				$(this).width($(this).width());
			});
			return ui;
		},
		handle: ".handle" });


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
			$.post('c_awards_edit.php', { action: "prize_order", prizes: prizes }, function(data) {
				});

		}, 
		/* This fixes the width bug on drag where a table is compressed instead of maintaining its original width */
		helper: function(e, ui) {
			ui.children().each(function() {
				$(this).width($(this).width());
			});
			return ui;
		},
		handle: ".prize_handle" });

function award_delete(id) {
	if(confirm('Really delete this award?') == false) return false;
	$.post('c_awards_edit.php', { action: "del", aid: id }, function(data) {
		if(data.status == 0) {
			$("#award_row_"+id).hide();
		}
	}, "json");
	return false;
}
function prize_delete(id) {
	if(confirm('Really delete this prize?') == false) return false;
	$.post('c_awards_edit.php', { action: "pdel", pid: id }, function(data) {
		if(data.status == 0) {
			$("#prize_div_"+id).hide();
		}
	}, "json");
	return false;
}

</script>



<?php
sfiab_page_end();
?>
