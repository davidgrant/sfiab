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
require_once('timeslots.inc.php');

$mysqli = sfiab_init('committee');

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

switch($action) {
case 'jdel':
	/* Remove a judge from a judging team */
	$jteam_id = (int)$_POST['jteam_id'];
	$j_uid = (int)$_POST['uid'];

	$jteam = jteam_load($mysqli, $jteam_id);

	/* Need to fix this, deleting timeslot assignments can leave holds in the timeslot, we need a way to automatically fix them , 
	 * what we need is an incremental way to build a judge timeslot schedule that doesn't depend on running the full
	 * timeslot scheduler */
//	$mysqli->query("DELETE FROM timeslot_assignments WHERE judging_team_id='$jteam_id' AND judge_id='$j_uid'");

	$new_uids = array();
	foreach($jteam['user_ids'] as $uid) {
		if($uid == $j_uid) continue;
		$new_uids[] = $uid;
	}
	$jteam['user_ids'] = $new_uids;
	jteam_save($mysqli, $jteam);
	form_ajax_response(array('status'=>$jteam['round']));
	exit();

case 'jadd':
	/* Add a judge to a jduging team */
	$jteam_id = (int)$_POST['jteam_id'];
	$j_uid = (int)$_POST['uid'];
	$jteam = jteam_load($mysqli, $jteam_id);
	$jteam['user_ids'][] = $j_uid;
	jteam_save($mysqli, $jteam);
	/* Pass back the jteam round so the JS can setup the proper links
	 * and mvoe table elements around from the right unused judge list */
	form_ajax_response(array('status'=>$jteam['round']));
	exit();

case 'jautoadd':

	$projects = projects_load_all($mysqli);

	/* Add a best-match single free judge to a judging team */
	$jteam_id = (int)$_POST['jteam_id'];
	$jteam = jteam_load($mysqli, $jteam_id);

	$j_uid = false;

	/* Build a list of divs for this jteam */
	$jteam_divs = array();
	foreach($jteam['project_ids'] as $pid) {
		$p = &$projects[$pid];
		$pref = $p['isef_id'];
		if($isef_divs[$pref]['parent'] != false)
			$div = $isef_divs[$pref]['parent'];
		else 
			$div = $isef_divs[$pref]['div'];
		if(!array_key_exists($div, $jteam_divs)) {
			$jteam_divs[$div] = 0;
		}
		$jteam_divs[$div] += 1;
	}

	$best_matches = -1;
	$best_judge_id = 0;
	foreach($judges as $uid=>&$j) {

		if(!in_array($jteam['round'], $j['j_rounds'])) continue;

		if(!$j['attending'] || !$j['j_complete'] || !$j['enabled']) continue;

		/* Make sure this judge isn't assigned to a slot already */
		$q = $mysqli->query("SELECT * FROM judging_teams WHERE FIND_IN_SET('$uid',`user_ids`)>0 AND round='{$jteam['round']}'");
		if($q->num_rows > 0) {
			/* Judge is already assigned in this round */
			continue;
		}

		/* This judge is free in this round, see how their expertise matches */
		$matches = 0;
		foreach($j['j_div_pref'] as $pref) {
			if($pref > 0) {
				if($isef_divs[$pref]['parent'] != false)
					$d = $isef_divs[$pref]['parent'];
				else 
					$d = $isef_divs[$pref]['div'];
				if(array_key_exists($d, $jteam_divs) && $jteam_divs[$d] > 0) {
					$matches += 1;
				}
			}
		}

		if($matches > $best_matches) {
			$best_matches = $matches;
			$best_judge_id = $uid;
		}
	}

	if($best_matches > -1) {
		$jteam['user_ids'][] = $best_judge_id;
		jteam_save($mysqli, $jteam);
	}

	form_ajax_response(array('status'=>$jteam['round'], 'info'=>$best_judge_id));
	exit();


case 'pdel':
	/* Remove a project from a judging team */
	$jteam_id = (int)$_POST['jteam_id'];
	$del_pid = (int)$_POST['pid'];

	$jteam = jteam_load($mysqli, $jteam_id);

	$mysqli->query("DELETE FROM timeslot_assignments WHERE judging_team_id='$jteam_id' AND pid='$del_pid'");

	$new_pids = array();
	foreach($jteam['project_ids'] as $pid) {
		if($pid == $del_pid) continue;
		$new_pids[] = $pid;
	}
	$jteam['project_ids'] = $new_pids;
	jteam_save($mysqli, $jteam);
	form_ajax_response(array('status'=>0));
	exit();

case 'padd':
	/* Add a project to a jduging team */
	$jteam_id = (int)$_POST['jteam_id'];
	$pid = (int)$_POST['pid'];
	$jteam = jteam_load($mysqli, $jteam_id);
	$jteam['project_ids'][] = $pid;
	jteam_save($mysqli, $jteam);
	form_ajax_response(array('status'=>0));
	exit();


}


$projects = projects_load_all($mysqli, $config['year']);
$jteams = jteams_load_all($mysqli, $config['year']);
$rounds = timeslots_load_rounds($mysqli);


$page_id = 'c_jteam_list';

$help = '<p>ISEF divisions:
<ul>';
foreach($isef_divs as $id=>$d) {
	if($d['parent'] == false) {
		$help .= '<li><b>'.$d['div'].'</b> - '.$d['name'].'</li>';
	}
}
$help .= '</ul>';

sfiab_page_begin($u, "Judging Teams List", $page_id, $help);

/* 		
		<div style="display: inline-block; vertical-align: top"> */
?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 
	<h3>Unused Judges</h3>

<?php	
	foreach($rounds as $round=>&$r) {
		$judge_list = array();
		foreach($judges as &$j) {
			if(in_array($round, $j['j_rounds']) && $j['j_complete'] == 1 && $j['attending']) {
				/* Is this judge on a jteam in ths round? */
				$found = false;
				foreach($jteams as &$jteam) {
					if($jteam['round'] != $round) continue;

					foreach($jteam['user_ids'] as $uid) {
						if($uid == $j['uid']) {
							$found = true;
							break;
						}
					}
					if($found == true) break;
				}
				if(!$found) {
					$judge_list[] =& $j;
				}
			}
		}
?>
		<div data-role=collapsible data-collapsed=true>
			<h3><?=$r['name']?> - <span id="j_unused_<?=$round?>_count"><?=count($judge_list)?></span> Unused Judges</h3>

			<div class="ui-grid-a">
			<div class="ui-block-a">
			<table id="j_unused_<?=$round?>">
<?php			judge_header();
			$c = 0;
			foreach($judge_list as &$j) {
				judge_row($j);
				$c++;
				if($c > count($judge_list)/2 ) {
?>					</table>
					</div>
					<div class="ui-block-b">
					<table>
		<?php			judge_header();
					$c = 0;
				}
			
			}
?>			</table>	
			</div></div>
		</div>
<?php	}
?>
					
	<h3>Judging Teams</h3>

	<ul data-role="listview" data-filter="true" data-filter-placeholder="Search..." data-inset="true">

<?php

	foreach($rounds as $round=>&$r) {
		foreach(array('Divisional','Special','Other') as $t) {

?>			<li data-role="list-divider" data-filtertext="round <?=$round+1?> <?=$r['name']?>" ><h3><font size=+1><?=$r['name']?> - <?=$t?></font></h3></li>

<?php			foreach($jteams as &$jteam) {
				/* Match round */
				if($jteam['round'] != $round) continue;

				/* Match type if there's an award, if not match other */
				if($jteam['award_id'] > 0) {
					$a =& $awards[$jteam['award_id']];
					if($t == 'Divisional' && $a['type'] != 'divisional') continue;
					if($t == 'Special' && $a['type'] != 'special') continue;
					if($t == 'Other' && ($a['type'] == 'divisional' || $a['type'] == 'special') ) continue;
				} else {
					if($t != 'Other') continue;
				}
				jteam_li($jteam);
			}
		}
	}
?>

</ul>
<?php

function judge_header()
{
?>
	<tr><td align="center">Judge</td><td align="center">Cat Pref</td>
		<td align="center">Div Pref</td>
		<td align="center">Years</td>
		<td align="center">Langs</td>
		<td align="center">Lead?</td>
	</tr>
<?php
}

/* Print a table row for a judge, including the delete button */
function judge_row(&$j, $tr = true)
{
	global $jteams, $page_id, $isef_divs, $awards, $cats, $judges, $projects;
	$cat_pref = $j['j_cat_pref'] == 0 ? 'none' : $cats[$j['j_cat_pref']]['shortform'];

	if($j['j_sa_only']) {
		$exp = '';
		$div_pref = 'SA: '.join(',', $j['j_sa']);
	} else {
		$div_pref = '';
		foreach($j['j_div_pref'] as $pref) {
			if($pref > 0) {
				if($isef_divs[$pref]['parent'] != false)
					$d = $isef_divs[$pref]['parent'];
				else 
					$d = $isef_divs[$pref]['div'];

				$div_pref .= $d.' ';
			}
		}
		$exp = $j['j_years_regional'] + $j['j_years_national'];
	} 

	$langs = '';
	if(is_array($j['j_languages'])) {
		if(in_array('en', $j['j_languages'])) $langs.= 'en ';
		if(in_array('fr', $j['j_languages'])) $langs.= 'fr ';
	}

	$lead = $j['j_willing_lead'] ? 'y' : 'n';

	if($tr == true) { ?>
		<tr id="<?=$j['uid']?>"><td><?=$j['name']?></td>
		    <td align="center"><?=$cat_pref?></td>
		    <td align="center"><?=$div_pref?></td>
		    <td align="center"><?=$exp?></td>
		    <td align="center"><?=$langs?></td>
		    <td align="center"><?=$lead?></td>
		    <td align="center"></td>
		</tr>
<?php
	} else {
		$n = $j['name'];
//		$n = sprintf("%-20s", $j['name']);
//		$n = str_replace(' ', '&nbsp', $n);
		return "$n, $cat_pref, $div_pref, $exp, $langs, $lead";
	}
}

function project_row(&$p, $tr = true)
{
	global $cats, $isef_divs;

	if(!array_key_exists($p['cat_id'], $cats)) {
		print("--");
		print_r($p);
		print("Project {$p['pid']} has an invalid cat {$p['cat_id']}");
		debug_print_backtrace();
	}
	$cat = $cats[$p['cat_id']]['shortform'];
	$pref = $p['isef_id'];
	if($isef_divs[$pref]['parent'] != false)
		$div = $isef_divs[$pref]['parent'];
	else 
		$div = $isef_divs[$pref]['div'];

	$title = $p['title'];
	if(strlen($title) > 50) $title = substr($title, 0, 47)."...";

	$lang = $p['language'];

	if($tr == true) { ?>
		<tr id="<?=$p['pid']?>" ><td><?=$p['number']?></td>
		    <td align="center"><?=$title?></td>
		    <td align="center"><?=$cat?></td>
		    <td align="center"><?=$div?></td>
		    <td align="center"><?=$lang?></td>
		    <td align="center"></td>
		</tr>
<?php
	} else {
		return "{$p['number']}, $title, $cat, $div, $lang";
	}
}

function jteam_li(&$jteam) {

	global $jteams, $page_id, $isef_divs, $awards, $cats, $judges, $projects, $rounds;

	$filter_text = $jteam['num'] . ' '. $jteam['name'] . ' round '.$jteam['round'];
	foreach($jteam['user_ids'] as $uid) {
		$j =& $judges[$uid]; 
		$filter_text .= ' '.$j['name'];
	}
	$r = &$rounds[$jteam['round']];
?>
	<li id="jteam_list_<?=$jteam['id']?>" data-filtertext="<?=$filter_text?>">
		<h3>#<?=$jteam['num']?> - <?=$jteam['name']?></h3>
		<div class="ui-grid-a">
		<div class="ui-block-a">
		Award: <b><?=$jteam['award_id']?>: <?=$awards[$jteam['award_id']]['name']?></b><br/>
		Round: <b><?=$r['name']?></b><br/>
		<table id="j_jteam_<?=$jteam['id']?>">

<?php		judge_header();
		foreach($jteam['user_ids'] as $uid) {
			judge_row($judges[$uid]);
		} ?>
		</table>
		<br/>
		<div id="jteam_list_<?=$jteam['id']?>_control" data-role="controlgroup" data-type="horizontal" data-mini="true">
		    <a href="#" onclick="jteam_enable_edit(<?=$jteam['id']?>)" class="ui-btn ui-corner-all ui-btn-inline">Edit</a>
		    <a href="#" onclick="jteam_jautoadd(<?=$jteam['id']?>)" class="ui-btn ui-corner-all  ui-btn-inline">Auto-add Best Judge</a>
		</div>

		<div id="jteam_list_<?=$jteam['id']?>_function" style="display:none;">
		</div>

		</div>

		<div class="ui-block-b">
		<table id="p_jteam_<?=$jteam['id']?>">
		<tr><td align="center">Project</td><td align="center">Title</td>
			<td align="center">Cat</td>
			<td align="center">Div</td>
			<td align="center">Lang</td>
		</tr>
<?php		foreach($jteam['project_ids'] as $pid) {
				if(!array_key_exists($pid, $projects) || !array_key_exists('pid', $projects[$pid])) {
					print("pid $pid doesn't exist in accepted projects!");
				}
			project_row($projects[$pid]);
		} ?>
		</table>
		</div>
		 </div>
					
	</li>
<?php
}
?>



<div style="display:none">
	<table id="j_tr">
	<?php
	foreach($judges as &$j) {
		judge_row($j);
	}
	?>
	</table>
	<table id="p_tr">
	<?php
	foreach($projects as $pid=>&$p) {
		project_row($p);
	}
	?>
	</table>
</div>


<div id="jteam_jadd" style="display:none">
<hr/>
<div data-role="tabs" id="tabs">
  <div data-role="navbar" >
    <ul data-inset="true">
      <li><a href="#jteam_jadd_unused" data-ajax="false">Add Judge (Unused)</a></li>
      <li><a href="#jteam_jadd_all" data-ajax="false">Add Judge (All)</a></li>
      <li><a href="#jteam_padd_all" data-ajax="false">Add Project (All)</a></li>
    </ul>
  </div>
  <div id="jteam_jadd_unused" class="ui-body-d ">
<?php
	/* Build a list of all jduges.. round1, round2 special */
	$optlist = array();
	foreach($rounds as $round=>&$r) {
		$optlist[$r['name']][] = array();
		foreach($judges as $jid=>&$j) {
			if(in_array($round, $j['j_rounds'])) {
				$optlist[$r['name']][$jid] = judge_row($j, false);
			}
		}
	}
	form_select_optgroup("jteam_jadd_all", "jsel", NULL, $optlist, $val);
?>
	<button type="submit" data-role="button" onclick="jteam_jadd();" data-inline="true" data-icon="check" data-theme="g" >Add</button>
	<button type="submit" data-role="button" onclick="jteam_cancel_edit();" data-inline="true" data-icon="delete">Done</button>
  </div>
  <div id="jteam_jadd_all" class="ui-body-d">
<?php
	/* Build a list of all jduges.. round1, round2 special */
	$optlist = array();
	foreach($rounds as $round=>&$r) {
		$optlist[$r['name']][] = array();
		foreach($judges as $jid=>&$j) {
			if(in_array($round, $j['j_rounds'])) {
				$optlist[$r['name']][$jid] = judge_row($j, false);
			}
		}
	}
	form_select_optgroup("jteam_jadd_all", "jsel", NULL, $optlist, $val);
?>
	<button type="submit" data-role="button" onclick="jteam_jadd();" data-inline="true" data-icon="check" data-theme="g" >Add</button>
	<button type="submit" data-role="button" onclick="jteam_cancel_edit();" data-inline="true" data-icon="delete" >Done</button>

  </div>
  <div id="jteam_padd_all" class="ui-body-d ">
<?php
	$optlist = array();
	foreach($cats as $cid=>$cat) {
		$optlist[$cat['name']][] = array();
		foreach($projects as $pid=>&$p) {
			if($p['cat_id'] == $cid) {
				$optlist[$cat['name']][$pid] = project_row($p, false);
			}
		}
	}
	form_select_optgroup("jteam_padd_all", "psel", NULL, $optlist, $val);
?>
	<button type="submit" data-role="button" onclick="jteam_padd();" data-inline="true" data-icon="check" data-theme="g" >Add</button>
	<button type="submit" data-role="button" onclick="jteam_cancel_edit();" data-inline="true" data-icon="delete" >Done</button>
  </div>
</div>

</div>

</div></div>


<script>
var current_jteam_id = -1;


function jteam_enable_edit(jteam_id)
{
	if(current_jteam_id != -1) {
		jteam_cancel_edit();
	}
	current_jteam_id = jteam_id;

	$("#jteam_list_"+jteam_id+"_control").hide();
	$("#jteam_list_"+jteam_id+"_function").show();
	$("#jteam_jadd").detach().appendTo("#jteam_list_"+jteam_id+"_function");
	$("#jteam_jadd").show();

	$("#j_jteam_"+jteam_id+" tr").each(function( index ) {
		if( index > 0) { 
			var judge_uid = $(this).attr('id');
			$( this ).append("<td id='X'><a href=\"#\" onclick=\"jteam_jdel("+judge_uid+");\">[X]</a></td>");
		}
	});

	$("#p_jteam_"+jteam_id+" tr").each(function( index ) {
		if( index > 0) { 
			var pid = $(this).attr('id');
			$( this ).append("<td id='X'><a href=\"#\" onclick=\"jteam_pdel("+pid+");\">[X]</a></td>");
		}
	});

	return false;
}
function jteam_cancel_edit()
{
	$("#jteam_list_"+current_jteam_id+"_control").show();
	$("#jteam_list_"+current_jteam_id+"_function").hide();
	$("#jteam_jadd").hide();

	$("#j_jteam_"+current_jteam_id+" tr td[id='X']").remove();
	$("#p_jteam_"+current_jteam_id+" tr td[id='X']").remove();

	current_jteam_id = -1;
	return false;
}

function jteam_jdel(judge_uid)
{	
	$.post('c_jteam_edit.php', { action: "jdel", jteam_id: current_jteam_id, uid: judge_uid }, function(data) {
		if(data.status > 0) {
			var round = data.status;

			$('#j_jteam_'+current_jteam_id+' tr[id="'+judge_uid+'"]').remove();
			$('#j_tr tr[id="'+judge_uid+'"]').appendTo('#j_unused_'+round);

			var count_span = $("#j_unused_"+round+"_count");
			count_span.text(parseInt(count_span.text()) + 1);
		}
	}, "json");
	return false;
}


function jteam_jadd()
{
	/* Read the current selection from the select box, that's the jid */
	var judge_uid = $("#jteam_jadd_all_jsel option:selected").val();

	/* Post that */
	$.post('c_jteam_edit.php', { action: "jadd", jteam_id: current_jteam_id, uid: judge_uid }, function(data) {
		if(data.status >= 0) {
			round = data.status;
			judge_uid = data.info;

			/* Remove from unused judges */
			$('#j_unused_'+round+' tr[id="'+judge_uid+'"]').remove();
			/* Add to jteam list */

			$('#j_tr tr[id="'+judge_uid+'"]').appendTo('#j_jteam_'+current_jteam_id);
			/* Append the [X] to the new tr in the jteam table */
			$( "#j_jteam_"+current_jteam_id+' tr[id="'+judge_uid+'"]').append("<td id='X'><a href=\"#\" onclick=\"jteam_jdel("+judge_uid+");\">[X]</a></td>");

			/* Change the unused count */
			var count_span = $("#jteam_unused_"+round+"_count");
			count_span.text(parseInt(count_span.text()) - 1);
		}
	}, "json");

}


function jteam_jautoadd(jteam_id)
{
	/* Read the current selection from the select box, that's the jid */
	/* Post that */
	$.post('c_jteam_edit.php', { action: "jautoadd", jteam_id: jteam_id,  }, function(data) {
		if(data.status > 0) {
			round = data.status;

			/* Remove from unused judges */
			$('#j_unused_'+round+' tr[id="'+judge_uid+'"]').remove();
			/* Add to jteam list */

			$('#j_tr tr[id="'+judge_uid+'"]').appendTo('#j_jteam_'+current_jteam_id);
			/* Append the [X] to the new tr in the jteam table */
			$( "#j_jteam_"+current_jteam_id+' tr[id="'+judge_uid+'"]').append("<td id='X'><a href=\"#\" onclick=\"jteam_jdel("+judge_uid+");\">[X]</a></td>");

			/* Change the unused count */
			var count_span = $("#jteam_unused_"+round+"_count");
			count_span.text(parseInt(count_span.text()) - 1);
		}
	}, "json");

}



function jteam_pdel(id)
{
	$.post('c_jteam_edit.php', { action: "pdel", jteam_id: current_jteam_id, pid: id }, function(data) {
		if(data.status == 0) {
			/* Remove from jteam */
			$("#p_jteam_"+current_jteam_id+' tr[id="'+id+'"]').remove();
		}
	}, "json");
	return false;
}

function jteam_padd()
{
	var id = $("#jteam_padd_all_psel option:selected").val();
	$.post('c_jteam_edit.php', { action: "padd", jteam_id: current_jteam_id, pid: id }, function(data) {
		if(data.status == 0) {
			/* Add to jteam list */
			$('#p_tr tr[id="'+id+'"]').appendTo('#p_jteam_'+current_jteam_id);
			/* Append the [X] to the new tr in the jteam table */
			$( "#p_jteam_"+current_jteam_id+' tr[id="'+id+'"]').append("<td id='X'><a href=\"#\" onclick=\"jteam_pdel("+id+");\">[X]</a></td>");

		}
	}, "json");
	return false;
}



</script>

<?php
sfiab_page_end();
?>
