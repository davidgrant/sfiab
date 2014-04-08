<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('email.inc.php');
require_once('awards.inc.php');
require_once('committee/judges.inc.php');
require_once('isef.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);
sfiab_session_start($mysqli, array('committee'));

$u = user_load($mysqli);

$awards = award_load_all($mysqli);
$cats = categories_load($mysqli);

/* Need all jduges for smart add, so load them anyway */
$js = judges_load_all($mysqli, $config['year']);
$judges = array();
foreach($js as &$j) {
	$judges[$j['uid']] = $j;
}

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

/* Set winners to NULL so the get_prize_count queries in the actions below
 * re-query the winners after modifying them.. they dont' need to 
 * load the entire winner list either */
$winners = NULL;

switch($action) {
case 'pdel':
	/* Remove a project from a prize */
	$prize_id = (int)$_POST['prize_id'];
	$prize = prize_load($mysqli, $prize_id);
	$pid = (int)$_POST['pid'];
	$mysqli->query("DELETE FROM winners WHERE `awards_prizes_id`='$prize_id' AND `projects_id`='$pid'");
	form_ajax_response(array('status'=>0, 'happy'=>get_prize_count($prize) ));
	exit();

case 'padd':
	/* Add a project to a prize */
	$prize_id = (int)$_POST['prize_id'];
	$pid = (int)$_POST['pid'];
	$prize = prize_load($mysqli, $prize_id);
	/* This insert may fail because the table is keyed to unique (prize, project, year).  That's
	 * ok, just read the error and return that so the javascirpt doesn't think the insert was 
	 * successful */
	$mysqli->query("INSERT INTO winners(`awards_prizes_id`,`projects_id`,`year`,`fairs_id`) 
			VALUES('$prize_id','$pid','{$config['year']}','0')");
	
	$error = ($mysqli->errno == 0) ? 0 : 1;
	form_ajax_response(array('status'=>$error, 'happy'=>get_prize_count($prize) ));
	exit();
}

/* Load all winners */
$winners = array();
$q = $mysqli->query("SELECT * FROM winners WHERE year='{$config['year']}'");
while($r = $q->fetch_assoc()) {
	$prize_id = (int)$r['awards_prizes_id'];
	if(!array_key_exists($prize_id, $winners)) {
		$winners[$prize_id] = array();
	}
	$winners[$prize_id][] = (int)$r['projects_id'];
}

$projects = projects_load_all($mysqli, $config['year']);

$page_id = 'c_award_winners';
$help = '<p>Enter Winning Projects';
sfiab_page_begin("Enter Winning Projects", $page_id, $help);
?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

	<p>To add/edit winners, click on the number count beside each prize
	(e.g., <font color="red"><b>[0 / 4]</b></font>).
	
	<p>Use the search bar to
	filter the list by award or prize names, or winners.  After editing a project, 
	refresh this page to update the search index (or the search won't reflect the recent edits).

	<ul data-role="listview" data-filter="true" data-filter-placeholder="Search for award names, prize names, or winners..." data-inset="true">
<?php
	foreach($award_types as $tid=>$type) {
		$filter_text = $type;
?>		<li data-role="list-divider" data-filtertext="<?=$filter_text?>">
			<h2><?=$type?> Awards</h2>
		</li>

<?php		foreach($awards as $aid=>&$a) {
			if($a['type'] != $tid) continue;
			award_li($a);
		}
	}
?>

	</ul>

<?php

function project_row(&$p, $tr = true)
{
	global $cats, $isef_divs;

	$title = $p['title'];
	if(strlen($title) > 75) $title = substr($title, 0, 72)."...";
	$lang = $p['language'];

	if($p['disqualified_from_awards']) $pn = "DISQUALIFIED FROM AWARDS ".$p['number'];
	else $pn = $p['number'];

	if($tr == true) { ?>
		<tr id="<?=$p['pid']?>" ><td>(<?=$pn?>)</td>
		    <td align="left"><?=$title?></td>
		    <td align="center">(<?=$lang?>)</td>
		    <td align="center"></td>
		</tr>
<?php
	} else {
		return "({$pn}) $title, $lang";
	}
}

function get_prize_count(&$prize) 
{
	global $winners, $mysqli;

	if($winners === NULL) {
		$q = $mysqli->query("SELECT * FROM winners WHERE `awards_prizes_id`='{$prize['id']}'");
		$pcount = (int)$q->num_rows;
	} else {
		if(!array_key_exists($prize['id'], $winners)) {
			$pcount = 0;
		} else {
			$pcount = count($winners[$prize['id']]);
		}
	}

	if($pcount == 0) {
		$colour = "red";
	} else {
		$colour = "green";
	}
	return "<font color=\"$colour\">[$pcount / {$prize['number']}]</font>";
}

function award_li(&$a) {

	global $awards, $page_id, $awards, $projects, $winners;

	$filter_text = $a['type'].' '.$a['name']. ' '.$a['notes'];
	foreach($a['prizes'] as &$prize) { 
		$filter_text .= ' '.$prize['name'];
		if(array_key_exists($prize['id'], $winners)) {
			foreach($winners[$prize['id']] as $pid) {
				if(!array_key_exists($pid, $projects)) continue;
				$filter_text .= ' '.$projects[$pid]['number'].' '.$projects[$pid]['title'];
			}
		}
	}
?>
	<li id="award_<?=$a['id']?>" data-filtertext="<?=$filter_text?>">
		<h3><?=$a['name']?></h3>
		<div id="award_desc_<?=$a['id']?>" style="display:none" >
			Desc: <?=$a['description']?><br/>
			Criteria: <?=$a['criteria']?><br/>
			Notes: <?=$a['notes']?><br/>
		</div>
<?php		
		foreach($a['prizes'] as &$prize) { 
?>
			<div>
				<b><?=$prize['name']?></b>&nbsp;
				<a id="prize_count_<?=$prize['id']?>" style=" text-decoration: none;" href="#" onclick="prize_enable_edit(<?=$prize['id']?>)">
					<?=get_prize_count($prize)?>
				</a>
			</div>
			<table id="prize_<?=$prize['id']?>">
<?php			if(array_key_exists($prize['id'], $winners)) {
				foreach($winners[$prize['id']] as $pid) {
					project_row($projects[$pid]);
				} 
			}
?>
			</table>
			<div id="prize_editor_<?=$prize['id']?>" style="display:none;">
			</div>
<?
		}

		?>
	</li>
<?php
}
?>

<div style="display:none">
	<table id="p_tr">
	<?php
	foreach($projects as &$p) {
		project_row($p);
	}
	?>
	</table>
</div>


<div id="prize_editor" style="display:none">
<div data-role="tabs" id="tabs">
  <div data-role="navbar" >
    <ul data-inset="true">
      <li><a href="#prize_editor_all" data-ajax="false">All Projects</a></li>
      <li><a href="#prize_editor_eligible" data-ajax="false">Eligible Projects</a></li>
    </ul>
  </div>
  <div id="prize_editor_eligible" class="ui-body-d ">
<?php
//	form_select("prize_editor_unused", "psel", NULL, array(0=>'Does not work yet', 1=>'Does not work yet'), $val);
?>
	<button type="submit" data-role="button" onclick="prize_padd();" data-inline="true" data-icon="check" data-theme="g" >Add</button>
	<button type="submit" data-role="button" onclick="prize_cancel_edit();" data-inline="true" data-icon="delete">Done</button>
  </div>
 
  <div id="prize_editor_all" class="ui-body-d ">
<?php
	$optlist = array();
	foreach($projects as $pid=>&$p) {
		$optlist[$pid] = project_row($p, false);
	}
	form_select_filter("prize_editor_all", "psel", NULL, $optlist, $val);
?>
	<button type="submit" data-role="button" onclick="prize_padd();" data-inline="true" data-icon="check" data-theme="g" >Add</button>
	<button type="submit" data-role="button" onclick="prize_cancel_edit();" data-inline="true" data-icon="delete" >Done</button>
  </div>
</div>
<hr/>

</div>

</div></div>


<script>
var current_prize_id = -1;


function prize_enable_edit(prize_id)
{
	if(current_prize_id != -1) {
		prize_cancel_edit();
	}
	current_prize_id = prize_id;
	$("#prize_editor_"+prize_id).show();
	$("#prize_editor").detach().appendTo("#prize_editor_"+prize_id);
	$("#prize_editor").show();

	$("#prize_"+prize_id+" tr").each(function( index ) {
		var pid = $(this).attr('id');
		$( this ).append("<td id='X'><a href=\"#\" onclick=\"prize_pdel("+pid+");\">[X]</a></td>");
	});

	return false;
}
function prize_cancel_edit()
{
	$("#prize_editor_"+current_prize_id).hide();
	$("#prize_editor").hide();

	$("#prize_"+current_prize_id+" tr td[id='X']").remove();

	current_prize_id = -1;
	return false;
}



function prize_pdel(id)
{
	$.post('c_award_winners.php', { action: "pdel", prize_id: current_prize_id, pid: id }, function(data) {
		if(data.status == 0) {
			/* Remove from award */
			$("#prize_"+current_prize_id+' tr[id="'+id+'"]').remove();
			$("#prize_count_"+current_prize_id).html(data.happy);
		}
	}, "json");
	return false;
}

function prize_padd()
{
	var id = $("#prize_editor_all_psel option:selected").val();
	$.post('c_award_winners.php', { action: "padd", prize_id: current_prize_id, pid: id }, function(data) {
		if(data.status == 0) {
			/* Add to award list */
			$('#p_tr tr[id="'+id+'"]').appendTo('#prize_'+current_prize_id);
			/* Append the [X] to the new tr in the award table */
			$( "#prize_"+current_prize_id+' tr[id="'+id+'"]').append("<td id='X'><a href=\"#\" onclick=\"prize_pdel("+id+");\">[X]</a></td>");
			$("#prize_count_"+current_prize_id).html(data.happy);
		}
	}, "json");
	return false;
}



</script>

<?php
sfiab_page_end();
?>
