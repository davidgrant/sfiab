<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('awards.inc.php');
require_once('sponsors.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('committee'));

$u = user_load($mysqli);



$page_id = 'c_awards_edit';
$form_id = $page_id."_form";

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}
switch($action) {
case 'save':
case 'save_back':
	$cats = categories_load($mysqli);

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
	post_array($a['categories'], 'categories', $cats);

	$updates = array();
	if($a['sponsor_uid'] == 0) {
		/* Insert a new sponsor, provided the name doesn't already exist */
		$updates['sponsor_uid'] = 1;

		$sponsor_org = "New Sponsor";
		post_text($sponsor_org, 'sponsor_organization');
		$sponsor_uid = sponsor_create_or_get($mysqli, $sponsor_org);
		$updates['sponsor_uid'] = $sponsor_uid;

		$a['sponsor_uid'] = $sponsor_uid;
	}

	/* Iterate over the $_POST['prizes'][prize_id] and save data for each prize */
	foreach($_POST['prize'] as $pid=>$p) {
		$pid = (int)$pid;
		if($pid == 0) {
			/* Create new */
			$prize = NULL;
		} else {
			$prize = &$a['prizes'][$pid];
		}


		post_text($prize['name'],array('prize', $pid, 'name') );
		post_int($prize['number'],array('prize', $pid, 'number') );
		post_float($prize['cash'],array('prize', $pid, 'cash'));
		post_float($prize['scholarship'],array('prize', $pid, 'scholarship'));
		post_float($prize['value'],array('prize', $pid, 'value'));
		post_bool($prize['external_register_winners'],array('prize', $pid, 'external_register_winners'));
		post_array($prize['trophies'], array('prize',$pid,'trophies'), $award_trophies);

		prize_save($mysqli, $prize);
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
		/* Delete prize */
		$mysqli->real_query("DELETE FROM award_prizes WHERE id='$pid'");
		form_ajax_response(0);
		exit();
	}
	form_ajax_response(1);
	exit();

case 'padd':
	$aid = (int)$_POST['aid'];
	if($aid > 0) {
		$pid = prize_create($mysqli, $aid);
		$prize = prize_load($mysqli, $pid);
		$prize['name'] = 'New prize';
		$prize['number'] = 1;
		prize_save($mysqli, $prize);
		print_prize_div($form_id, $pid, true, $prize);
	}
	exit();
}

$help = '<p>Edit the award';


sfiab_page_begin("Edit Award", $page_id, $help);


function print_prize_div($form_id, $pid, $show, &$p)
{
	global $award_trophies;
	$show_attr = $show ? 'data-collapsed="false"' : '';

?>	<div data-role="collapsible" data-pid="<?=$pid?>" <?=$show_attr?> data-collapsed-icon="carat-r" and data-expanded-icon="carat-d">
		<h3><span class="prize_div_name"><?=$p['name']?></span></h3>
<?php	
		form_text($form_id, "prize[$pid][name]", 'Name', $p['name']);
		form_int($form_id, "prize[$pid][number]", "Number Available to be Awarded", $p['number']);
		form_text($form_id, "prize[$pid][cash]", 'Cash Award', $p['cash']);
		form_text($form_id, "prize[$pid][scholarship]", 'Scholarship', $p['scholarship']);
		form_text($form_id, "prize[$pid][value]", 'Prize Value', $p['value']);
		form_check_group($form_id, "prize[$pid][trophies]", "Trophies", $award_trophies, $p['trophies']);
?>
		<div class="award_external" style="display:none;"> 
<?php			form_yesno($form_id, "prize[$pid][external_register_winners]", "(External) Register Winners at this fair", $p['external_register_winners']); ?>
		</div>

		<div align="right">
			<a href="#" onclick="return prize_delete(<?=$pid?>);" data-role="button" data-icon="delete" data-inline="true" data-theme="r">Delete Prize</a>
		</div>
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

	form_begin($form_id, 'c_awards_edit.php');
	form_hidden($form_id, 'aid',$a['id']);
	form_text($form_id, 'name', "Name", $a);
	form_select($form_id, 'type', "Type", $award_types, $a);
	form_textbox($form_id, 's_desc', "Student Description (Student and Judges see this, public on website, goes in ceremony script)", $a);
	form_textbox($form_id, 'j_desc', "Judge Description (Only judges see this)", $a);
	form_textbox($form_id, 'c_desc', "Committee Notes (Only the committee sees this)", $a);

	/* If a sponsor_uid of 0 gets loaded, change it to null so the select list shows a "Select..." option.
	 * never default to "Create a New Sponsor" */
	if($a['sponsor_uid'] == 0) $a['sponsor_uid'] = NULL;
	form_select($form_id, 'sponsor_uid', "Sponsor", $sponsor_list, $a);

?>	<div id="new_sponsor" style="display:none">
<?php		form_text($form_id, 'sponsor_organization', "New Sponsor");?>
	</div>

<?php	form_check_group($form_id, 'categories', "Categories", $cats, $a);
	form_yesno($form_id, 'schedule_judges', 'Schedule Judges', $a);
	form_yesno($form_id, 'self_nominate', 'Students can Self Nominate', $a);
	form_yesno($form_id, 'include_in_script', 'Include in Ceremony Script', $a);
	form_text($form_id, 'presenter', "Presenter", $a);
	form_submit($form_id, 'save', 'Save Award and Prize(s)', 'Award and Prize(s) Saved');
	form_submit($form_id, 'save_back', 'Save Award and Prize(s) and Go Back', 'Save Award and Prize(s) and Go Back');
?>	<a href="#" data-rel="back" data-role="button" data-icon="back" data-inline="true" data-theme="r">Cancel, Go Back</a>

	<h3>Prizes</h3> 
	<p>Prizes are listed in the order they will appear in the ceremony script.  To change the prize order, drag and drop the prizes in the list below (or on the Award List page).
	<div id="prizes" >
<?php	foreach($a['prizes_in_order'] as &$p) {
		$pid = $p['id'];
		print_prize_div($form_id, $pid, false, $p);
	} ?>
	</div>
	<a href="#" onclick="return prize_create(<?=$aid?>);" data-role="button" data-icon="plus" data-inline="true" data-theme="g">Create a New Prize</a><br/>
<?php
	form_submit($form_id, 'save', 'Save Award and Prize(s)', 'Award and Prize(s) Saved');
	form_submit($form_id, 'save_back', 'Save Award and Prize(s) and Go Back', 'Save Award and Prize(s) and Go Back');
?>	<a href="#" data-rel="back" data-role="button" data-icon="back" data-inline="true" data-theme="r">Cancel, Go Back</a>

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
