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
case 'save':
	$aid = (int)$_POST['aid'];
	$a = award_load($mysqli, $aid);

	post_text($a['name'],'name');
	post_text($a['type'],'type');
	post_text($a['c_desc'],'c_desc');
	post_text($a['j_desc'],'j_desc');
	post_text($a['s_desc'],'s_desc');
	post_text($a['sponsor_organization'],'sponsor_organization');
	post_text($a['sponsor_website'],'sponsor_website');
	post_text($a['sponsor_name'],'sponsor_name');
	post_text($a['sponsor_email'],'sponsor_email');
	post_text($a['sponsor_phone'],'sponsor_phone');
	post_text($u['sponsor_address'],'sponsor_address');
	post_text($u['sponsor_city'],'sponsor_city');
	post_text($u['sponsor_province'],'sponsor_province');
	post_text($u['sponsor_postalcode'],'sponsor_postalcode');
	post_text($u['sponsor_notes'],'sponsor_notes');
	
	post_text($a['presenter'],'presenter');
	post_bool($a['schedule_judges'],'schedule_judges');
//	post_bool($a['include_in_script'],'include_in_script');
	post_bool($a['self_nominate'],'self_nominate');
	post_int($a['order'],'order');
	$a['categories'] = array();

	foreach($_POST['categories'] as $index=>$val) {
		$cid = (int)$val;
		if($val > 0) $a['categories'][] = $val;
	}
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
	post_bool($p['include_in_script'],'include_in_script');
	post_bool($p['self_nominate'],'self_nominate');
	post_bool($p['external_register_winners'],'external_register_winners');
	post_int($p['order'],'order');
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

sfiab_page_begin("Edit Awards", $page_id);

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
?>		<div data-role="collapsible" data-collapsed="true">
			<h3>Sponsor Contact Information</h3>
<?php			form_text($form_id, 'sponsor_website', "Sponsor Contact Name", $a);
			form_text($form_id, 'sponsor_name', "Sponsor Contact Name", $a);
			form_text($form_id, 'sponsor_email', "Sponsor Contact Email", $a, 'email');
			form_text($form_id, 'sponsor_phone', "Sponsor Contact Phone", $a, 'tel');
			form_text($form_id, 'sponsor_address', 'Address 1', $a);
			form_text($form_id, 'sponsor_city', 'City', $a);
			form_province($form_id, 'sponsor_province', 'Province / Territory', $a);
			form_text($form_id, 'sponsor_postalcode', 'Postal Code', $a);
			form_text($form_id, 'sponsor_notes', 'Notes', $a);
?>		</div>
<?php		
		form_check_group($form_id, 'categories', "Categories", $cats, $a);
		form_yesno($form_id, 'schedule_judges', 'Schedule Judges', $a);
		form_yesno($form_id, 'self_nominate', 'Students can Self Nominate', $a);
//		form_yesno($form_id, 'include_in_script', 'Include in Ceremony Script', $a);
		form_text($form_id, 'presenter', "Presenter", $a);
		form_int($form_id, 'order', "Order", $a);
		form_submit($form_id, 'save', 'Save', 'Award Saved');
		form_end($form_id);
?>		<h3>Prizes</h3> 
<?php	foreach($a['prizes'] as $p) {
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
				form_int($form_id, "number", "Number of this Prize", $p);
				form_text($form_id, "cash", 'Cash Award', $p);
				form_text($form_id, "scholarship", 'Scholarship', $p);
				form_text($form_id, "value", 'Prize Value', $p);
				form_check_group($form_id, "trophies", "Trophies", $trophies, $p);
				form_yesno($form_id, "include_in_script", 'Include in Ceremony Script', $p);
				form_yesno($form_id, 'external_register_winners', "(External) Register Winners at this fair", $p);
				form_int($form_id, "order", "Order", $p);
	
?>	
				<div class="ui-grid-a">
				<div class="ui-block-a"> 
				<?=form_submit($form_id, 'psave', 'Save Prize', 'Prize Saved');?>
				</div>
				<div class="ui-block-b"> 
				<a href="#" onclick="return prize_delete(<?=$p['id']?>);" data-role="button" data-icon="delete" data-theme="r">Delete Prize</a>
				</div></div>
<?php
				form_end($form_id);
?>
			</div>
<?php	
		}
?>
		<a href="c_awards_edit.php?page=edit&aid=<?=$aid?>&pid=0" data-role="button" data-icon="plus" data-theme="g" data-ajax="false">New Prize</a>
<?php

		break;


	default:
		$awards = award_load_all($mysqli);
	?>
		<table >
		<thead>
		<tr><th>Order</th><th>Name</th><th>Prizes</th><th>Actions</th></td>
		</thead>
	<?php
		$current_type = '';
		foreach($awards as $aid=>$a) {
			if($a['type'] != $current_type) {
				$current_type = $a['type'];
				print("<tr><td colspan=4><h3>{$award_types[$current_type]}</h3></td></tr>");
			}
	?>
		<tr id="award_row_<?=$a['id']?>" >
		<td align="center"><?=$a['order']?></td>
		<td width="50%" ><?=$a['name']?></td>
		<td align="center"><?=count($a['prizes'])?></td>
			<td align="center">
				<div data-role="controlgroup" data-type="horizontal" data-mini="true">
					<a href="c_awards_edit.php?page=edit&aid=<?=$a['id']?>" data-role="button" data-iconpos="notext" data-icon="gear" data-ajax="false">Edit</a>
					<a href="#" onclick="return award_delete(<?=$a['id']?>);" data-role="button" data-iconpos="notext" data-icon="delete">Delete</a>
				</div>
			</td>
		</tr>

	<?php
	}
	?>
		</table>
		<a href="c_awards_edit.php?page=edit&aid=0" data-role="button" data-icon="plus" data-ajax="false" data-theme="g">New Award</a>
	<?php
	
	}
?>


</div></div>
	
<script>
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
