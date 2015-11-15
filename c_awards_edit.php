<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('awards.inc.php');
require_once('sponsors.inc.php');
require_once('fairs.inc.php');
$mysqli = sfiab_init('committee');

$u = user_load($mysqli);


$fairs = fair_load_all_feeder($mysqli);

$page_id = 'c_awards_edit';
$form_id = $page_id."_form";

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}
switch($action) {
case 'save':
case 'save_back':
	/* We should guard against some thigns here for div awards (can't
	change prize names or the award category.  But that's ok, if someone
	really wants to do that it'll reset on the next award list load or
	judge scheduler load */

	$cats = categories_load($mysqli);

	$aid = (int)$_POST['aid'];
	$a = award_load($mysqli, $aid);

	/* Use $remote_award to determine what we're allowed to save */
	$remote_award = ($a['upstream_fair_id'] > 0) ? true : false;

	if(!$remote_award) {
		post_text($a['name'],'name');
		post_text($a['type'],'type');
		post_text($a['j_desc'],'j_desc');
		post_text($a['s_desc'],'s_desc');
		post_int($a['sponsor_uid'], 'sponsor_uid');
		post_bool($a['self_nominate'],'self_nominate');
		post_int($a['ord'],'ord');
		post_array($a['categories'], 'categories', $cats);
		$a['feeder_fair_ids'] = array();
		post_array($a['feeder_fair_ids'], 'feeder_fair_ids', $fairs);
		post_bool($a['upstream_register_winners'], 'upstream_register_winners');
	}

	post_bool($a['include_in_script'],'include_in_script');
	post_bool($a['schedule_judges'],'schedule_judges');
	post_text($a['presenter'],'presenter');
	post_text($a['c_desc'],'c_desc');


	$updates = array();
	if(!$remote_award && $a['sponsor_uid'] == 0) {
		/* Insert a new sponsor, provided the name doesn't already exist */
		$updates['sponsor_uid'] = 1;

		$sponsor_org = "New Sponsor";
		post_text($sponsor_org, 'sponsor_organization');
		$sponsor_uid = sponsor_create_or_get($mysqli, $sponsor_org);
		$updates['sponsor_uid'] = $sponsor_uid;

		$a['sponsor_uid'] = $sponsor_uid;
	}

	if(!$remote_award) {
		/* Iterate over the $_POST['prizes'][prize_id] and save data for each prize */
		if(array_key_exists('prize', $_POST)) {
			foreach($_POST['prize'] as $pid=>$p) {
				$pid = (int)$pid;

				if(!array_key_exists($pid, $a['prizes'])) {
					print("Prize id not found, stop.");
					exit();
				}

				$prize = &$a['prizes'][$pid];

				post_text($prize['name'],array('prize', $pid, 'name') );
				post_int($prize['number'],array('prize', $pid, 'number') );
				post_float($prize['cash'],array('prize', $pid, 'cash'));
				post_float($prize['scholarship'],array('prize', $pid, 'scholarship'));
				post_float($prize['value'],array('prize', $pid, 'value'));
				post_array($prize['trophies'], array('prize',$pid,'trophies'), $award_trophies);
			}
		}
	}

	award_save($mysqli, $a);

	$response = array('status'=>0, 'val'=>$updates);
	if($action == 'save_back') {
		$response['location'] = 'back';
	}

	form_ajax_response($response);
	exit();


case 'del':
	$aid = (int)$_POST['aid'];
	if($aid > 0) {
		$a = award_load($mysqli, $aid);
		if($a['upstream_fair_id'] > 0) {
			/* Can't del an award with an upstream fair id, someone is trying to bypass the html? */
			exit();
		}
		$mysqli->real_query("UPDATE awards SET `ord`=`ord`-1 WHERE year='{$config['year']}' AND ord > '{$a['ord']}'");
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
	if($pid) {
		/* Load prize by pid, because that's all we have */
		$prize = prize_load($mysqli, $pid);

		/* Now load the entire award so we can modify it and push out an update */
		$a = award_load($mysqli, $prize['award_id']);
		if($a['upstream_fair_id'] > 0) {
			/* Can't prize del an award with an upstream fair id, someone is trying to bypass the html? */
			exit();
		}
		
		prize_delete($mysqli, $a, $pid);
		award_save($mysqli, $a);
		form_ajax_response(0);
		exit();
	}
	form_ajax_response(1);
	exit();

case 'padd':
	$aid = (int)$_POST['aid'];
	if($aid > 0) {
		$a = award_load($mysqli, $aid);
		if($a['upstream_fair_id'] > 0) {
			/* Can't prize add an award with an upstream fair id, someone is trying to bypass the html? */
			exit();
		}
		$pid = prize_create($mysqli, $a);
		$a['prizes'][$pid]['name'] = 'New prize';
		$a['prizes'][$pid]['number'] = 1;
		award_save($mysqli, $a);
		print_prize_div($form_id, $a['prizes'][$pid], true);
	}
	exit();
}

$help = '<p>Edit the award';


sfiab_page_begin($u, "Edit Award", $page_id, $help);


function print_prize_div($form_id, &$p, $show)
{
	global $award_trophies;
	global $form_disabled;
	global $div_award;

	$pid = $p['id'];
	$show_attr = $show ? 'data-collapsed="false"' : '';

?>	<div data-role="collapsible" data-pid="<?=$pid?>" <?=$show_attr?> data-collapsed-icon="carat-r" and data-expanded-icon="carat-d">
		<h3><span class="prize_div_name"><?=$p['name']?></span></h3>
<?php	
		if($div_award) $form_disabled = true;
		form_text($form_id, "prize[$pid][name]", 'Name', $p['name']);
		if($div_award) $form_disabled = false;
		form_int($form_id, "prize[$pid][number]", "Number Available to be Awarded", $p['number']);
		form_text($form_id, "prize[$pid][cash]", 'Cash Award', $p['cash']);
		form_text($form_id, "prize[$pid][scholarship]", 'Scholarship', $p['scholarship']);
		form_text($form_id, "prize[$pid][value]", 'Prize Value', $p['value']);
		form_check_group($form_id, "prize[$pid][trophies]", "Trophies", $award_trophies, $p['trophies']);
?>

<?php		if(!$form_disabled && !$div_award) { ?>
			<div align="right">
			<a href="#" onclick="return prize_delete(<?=$pid?>);" data-role="button" data-icon="delete" data-inline="true" data-theme="r" >Delete Prize</a>
			</div>
<?php		} ?>
	</div>
<?php
}

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php

	$aid = (int)$_GET['aid'];
	$cats = categories_load($mysqli);

	$sponsor_list = array();
	$sponsor_list[0] = "Create a New Sponsor (Enter Sponsor Below)";
	$sponsor_list += sponsors_load_for_select($mysqli);

	$a = award_load($mysqli, $aid);
	$remote_award = ($a['upstream_fair_id'] != 0) ? true : false;
	$div_award = ($a['type'] == 'divisional') ? true : false;

	/* Use $remote_award to determine what fields to disable.  Disable
	 * individual fields by toggling the * global form form_disabled flags..
	 * it's a bit hacky but it works.  The save routine above also enforces
	 * which fields can be save if someone decides to work around the HTML for
	 * whatever reason. */

	if($a['upstream_award_id'] != 0) {
		$fair = fair_load($mysqli, $a['upstream_fair_id']);
?>		<p>This award was automatically downloaded from the <b><?=$fair['name']?></b>.  Because it is a downloaded award, some field cannot be changed. Upstream changes are automatically downloaded.
<?php	}

	if($div_award) { ?>
		<p><b>This is a divisional award.  It is created automatically and some field cannot be changed.  To edit the prize names, go to the Judge Scheduling options in the Configuration Variables.</b>
<?php	}

	form_begin($form_id, 'c_awards_edit.php');
	form_hidden($form_id, 'aid',$a['id']);
	
	if($remote_award) $form_disabled = true;
	form_text($form_id, 'name', "Name", $a);
	if($div_award) $form_disabled = true;
	form_select($form_id, 'type', "Type", $award_types, $a);
	if($div_award) $form_disabled = false;
	form_textbox($form_id, 's_desc', "Student Description (Student and Judges see this, public on website, goes in ceremony script)", $a);
	form_textbox($form_id, 'j_desc', "Judge Description (Only judges see this)", $a);
	if($remote_award) $form_disabled = false;
	
	form_textbox($form_id, 'c_desc', "Committee Notes (Only the committee sees this)", $a);

	/* If a sponsor_uid of 0 gets loaded, change it to null so the select list shows a "Select..." option.
	 * never default to "Create a New Sponsor" */
	if($remote_award) $form_disabled = true;
	if($a['sponsor_uid'] == 0) $a['sponsor_uid'] = NULL;
	form_select($form_id, 'sponsor_uid', "Sponsor", $sponsor_list, $a);

?>	<div id="new_sponsor" style="display:none">
<?php		form_text($form_id, 'sponsor_organization', "New Sponsor");?>
	</div>

<?php	
	if($div_award) $form_disabled = true;
	form_check_group($form_id, 'categories', "Categories", $cats, $a);
	form_yesno($form_id, 'self_nominate', 'Students can Self Nominate', $a);

	if($div_award) $form_disabled = false;
	if($remote_award) $form_disabled = false;

	form_yesno($form_id, 'schedule_judges', 'Schedule Judges', $a);
	form_yesno($form_id, 'include_in_script', 'Include in Ceremony Script', $a);
	form_text($form_id, 'presenter', "Presenter", $a);
	form_submit($form_id, 'save', 'Save Award and Prize(s)', 'Award and Prize(s) Saved');
	form_submit($form_id, 'save_back', 'Save Award and Prize(s) and Go Back', 'Save Award and Prize(s) and Go Back');
?>	<a href="#" data-rel="back" data-role="button" data-icon="back" data-inline="true" data-theme="r">Cancel, Go Back</a>

	<h3>Prizes</h3> 
	<p>Prizes are listed in the order they will appear in the ceremony script.  To change the prize order, drag and drop the prizes in the list below (or on the Award List page).
	<div id="prizes" >
<?php	
	if($remote_award) $form_disabled = true;
	foreach($a['prizes_in_order'] as &$p) {
		$pid = $p['id'];
		print_prize_div($form_id, $p, false);
	} ?>
	</div>

<?php	if(!$remote_award && !$div_award) { ?>
		<a href="#" onclick="return prize_create(<?=$aid?>);" data-role="button" data-icon="plus" data-inline="true" data-theme="g">Create a New Prize</a><br/>
<?php	}

	if($remote_award) $form_disabled = false;
	form_submit($form_id, 'save', 'Save Award and Prize(s)', 'Award and Prize(s) Saved');
	form_submit($form_id, 'save_back', 'Save Award and Prize(s) and Go Back', 'Save Award and Prize(s) and Go Back');
?>	<a href="#" data-rel="back" data-role="button" data-icon="back" data-inline="true" data-theme="r">Cancel, Go Back</a>

	<h3>Feeder Fairs</h3> 
	<div data-role="collapsible" data-pid="<?=$pid?>" data-collapsed="true" data-collapsed-icon="carat-r" and data-expanded-icon="carat-d">
		<h3>Feeder Fair Options</h3>
		<p>Any fair you check below will automatically download this award and it will appear in their awards list.  If/when each fair
		assigns winners, they will be automatically uploaded back to this fair.
<?php	
		if($remote_award) $form_disabled =  true;
		form_check_list($form_id, "feeder_fair_ids", "Feeder Fairs", $fairs, $a);
?>
		<p>You can also make accounts for winners assigned by a feeder
		fair so they can become participants in this fair.  When winners
		are assigned, an option will appear on the main committee page
		to send welcome emails to these participants.  Welcome emails
		are not sent automatically because winner uploading is
		automatic and a feeder fair could assign a winner by mistake,
		then remove the assignment.  We don't want to immediately send
		welcome emails in that case.</p> 
<?php
		form_yesno($form_id, "upstream_register_winners", "Register Winners at this fair", $a);
?>
	</div>


	<br/><hr/>
	<a href="#" onclick="return award_delete(<?=$aid?>);" data-role="button" data-icon="delete" data-inline="true" data-theme="r">Delete Award (and Prizes)</a><br/>
<?php
	form_end($form_id);

?>

</div></div>
	
<script>

function c_awards_edit_form_pre_post_submit(form,data) {
	if(data.status != 0) return;

	/* If the form sponsor value is 0, insert into the sponsor select list:
	 * - val = data.update_vals.sponsor_uid
	 * - name= whatever is in the new sponsor field  */
	var sponsor_uid = $("#c_awards_edit_form_sponsor_uid").val();
	if(sponsor_uid == 0) {
		/* Find the new uid */
		for(var i=0; i<data.val.length; i++) {
			var v = data.val[i];
			if(v[0] == 'sponsor_uid') {
				sponsor_uid = v[1];
			}
		}
		/* append it to the list */
		$('#c_awards_edit_form_sponsor_uid').append($("<option/>", {
		        value: sponsor_uid,
		        text : $("#c_awards_edit_form_sponsor_organization").val()
		}));

		/* Hide the new sponsor input field */
		$("#new_sponsor").hide();
	}
	/* The main form code will process the data.val and switch the select
	 * to this new input */
}


/* Update all the prize names in the collapsible sections after the
 * prize data is saved */
function c_awards_edit_form_post_submit(form,data) {
	if(data.status != 0) return;
	$("#prizes").children('div').each(function(index) {
		var name = $(this).find('input').first().val();
		$(this).find('h3 .prize_div_name').first().text(name);
	});
}


/* Delete the entire award (and go back since there's nothing else here) */
function award_delete(id) {
	if(confirm('Really delete this award?') == false) return false;
	$.post('c_awards_edit.php', { action: "del", aid: id }, function(data) {
		if(data.status == 0) {
			window.history.back();
		}
	}, "json");
	return false;
}

/* Delete a prize */
function prize_delete(id) {
	if(confirm('Really delete this prize?') == false) return false;
	$.post('c_awards_edit.php', { action: "pdel", pid: id }, function(data) {
		if(data.status == 0) {
			/* Remove the div and everything inside it */
			$("#prizes>div[data-pid="+id+"]").remove();
		}
	}, "json");
	return false;
}

function prize_create(aid) {
	$.post('c_awards_edit.php', { action: "padd", aid: aid }, function(data) {
		$("#prizes").append(data);
		$("#prizes").trigger('create');
	});
	return false;
}

/* Toggle for the new sponsor textbox */
$( "#c_awards_edit_form_sponsor_uid" ).change(function(event) {
	var val = $( "#c_awards_edit_form_sponsor_uid" ).val();
	if( val == 0) {
		$("#new_sponsor").show();
	} else {
		$("#new_sponsor").hide();
	}
});

/* Make the prize list sortable */
$('#prizes').sortable({
		'containment': 'parent',
		'opacity': 0.6,
		update: function(event, ui) {
			/* Create an array to store the awards, in order.  Award in index 0 will be assigned ord=1, and up from there */
			var prizes = [];
			$(this).children('div').each(function(index) {
				var prize_id = $(this).attr('data-pid');
				prizes[index] = prize_id;
			});
			$.post('c_awards_list.php', { action: "prize_order", prizes: prizes }, function(data) {
				});

		} 
	});


</script>



<?php
sfiab_page_end();
?>
