<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('committee'));

$u = user_load($mysqli);

$page_id = 'c_tours';

$show_tid = 0;
if(array_key_exists('show', $_GET)) {
	$show_tid = (int)$_GET['show'];
}

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}
switch($action) {
case 'save':
	$tid = (int)$_POST['tid'];
	$t = tour_load($mysqli, $tid);

	post_text($t['name'],'name');
	post_int($t['num'],'num');
	post_text($t['description'],'description');
	post_int($t['capacity_min'],'capacity_min');
	post_int($t['capacity_max'],'capacity_max');
	post_int($t['grade_min'],'grade_min');
	post_int($t['grade_max'],'grade_max');
	post_text($t['contact'],'contact');
	post_text($t['location'],'location');
	tour_save($mysqli, $t);
	form_ajax_response(0);
	exit();

case 'del':
	/* Delete tour */
	$tid = (int)$_POST['tid'];
	if($tid > 0) {
		$mysqli->real_query("DELETE FROM tours WHERE id='$tid'");
		form_ajax_response(0);
		exit();
	}
	form_ajax_response(1);
	exit();


case 'add':
	$tid = tour_create($mysqli);
	form_ajax_response(array('status'=>0, 'location'=>'c_tours.php?show='.$tid));
	exit();
	
}

sfiab_page_begin("Edit Tours", $page_id);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	form_page_begin($page_id, array());
	$tours = tour_load_all($mysqli);

?>	<h3>Tours</h3> 
<?php	foreach($tours as $t) {
		$tid = $t['id'];
		$form_id = $page_id.'_tour'.$tid.'_form';
//		$show = false;
		$show = ($tid == $show_tid) ? 'data-collapsed="false"' : '';
?>		
		<div data-role="collapsible" id="tour_div_<?=$tid?>" <?=$show?>>
			<h3><?=$t['num']?>. <?=$t['name']?></h3>
			<input type="hidden" name="tid<?=$x?>" value="<?=$tid?>"/>
<?php	
			form_begin($form_id, 'c_tours.php');
			form_hidden($form_id, 'tid', $tid);
			form_int($form_id, "num", 'Tour Number', $t);
			form_text($form_id, "name", 'Name', $t);
			form_textbox($form_id, "description", "Description", $t);
			form_int($form_id, "capacity_min", 'Capacity Min', $t);
			form_int($form_id, "capacity_max", 'Capacity Max', $t);
			form_int($form_id, "grade_min", 'Grade Min', $t);
			form_int($form_id, "grade_max", 'Grade Max', $t);

			form_text($form_id, "contact", 'Contact', $t);
			form_textbox($form_id, "location", "Location", $t);
?>	
			<div class="ui-grid-a">
			<div class="ui-block-a"> 
				<?=form_submit($form_id, 'save', 'Save Tour', 'Tour Saved');?>
			</div>
			<div class="ui-block-b"> 
				<a href="#" onclick="return tour_delete(<?=$t['id']?>);" data-role="button" data-icon="delete" data-theme="r">Delete Tour</a>
			</div>
			</div>
<?php
			form_end($form_id);
?>
		</div>
<?php	
	}
	$form_id = $page_id.'_add_form';
	form_begin($form_id, 'c_tours.php');
	form_button($form_id, 'add', 'Add a Tour', 'Add a Tour');
	form_end($form_id);
?>

</div></div>
	
<script>
function tour_delete(id) {
	if(confirm('Really delete this tour?') == false) return false;
	$.post('c_tours.php', { action: "del", tid: id }, function(data) {
		if(data.status == 0) {
			$("#tour_div_"+id).hide();
		}
	}, "json");
	return false;
}

</script>



<?php
sfiab_page_end();
?>
