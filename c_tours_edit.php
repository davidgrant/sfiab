<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');

$mysqli = sfiab_init('committee');

$u = user_load($mysqli);


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
	form_ajax_response(array('status'=>0, 'location'=>'c_tours_edit.php'));
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
	/* Print the id so the caller can jump to the edit page with the right id */
	print("$tid");
	exit();

case 'order':
	$ord = 0;
	foreach($_POST['ids'] as $tid) {
		$ord++;
		$tid = (int)$tid;
		if($tid == 0) continue;

		$mysqli->query("UPDATE tours SET num='$ord' WHERE id='$tid'");
	}
	form_ajax_response(0);
	exit();
	break;
	
}

if(array_key_exists('edit', $_GET)) {
	$page = 'edit';
} else {
	$page = '';
}

switch($page) {
case 'edit':
	$tid = (int)$_GET['edit'];
	$page_id = 'c_tours_edit';
	$help = '<p>';
	sfiab_page_begin($u, "Edit Tour", $page_id, $help);

?>
	<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php		$t = tour_load($mysqli, $tid);
		/* Couldn't find the tour */
		if($t === NULL) {
			exit();
		}
?>
		<h3>Edit Tour:  <?=$t['name']?></h3>
<?php
		$form_id = $page_id.'_form';
		form_begin($form_id, 'c_tours_edit.php');
		form_hidden($form_id, 'tid', $tid);
		form_text($form_id, "name", 'Name', $t);
		form_textbox($form_id, "description", "Description", $t);
		form_int($form_id, "capacity_min", 'Capacity Min', $t);
		form_int($form_id, "capacity_max", 'Capacity Max', $t);
		form_int($form_id, "grade_min", 'Grade Min', $t);
		form_int($form_id, "grade_max", 'Grade Max', $t);

		form_text($form_id, "contact", 'Contact', $t);
		form_textbox($form_id, "location", "Location", $t);
		form_submit($form_id, 'save', 'Save', 'Information Saved');
?>		<a href="c_tours_edit.php" data-ajax="false" data-role="button" data-icon="back" data-theme="r" data-inline="true">Cancel</a>
<?php		form_end($form_id);

	break;

default:

	$page_id = 'c_tours_list';


	$help = '<p>';

	sfiab_page_begin($u, "Edit Tours", $page_id, $help);

?>


	<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php		$tours = tour_load_all($mysqli); ?>

		<table id="config_tours" data-role="table" data-mode="none" class="tour_list table_stripes">
		<thead>
			<tr>
			<th align="center" width=30%>Tour / Description</th>
			<th align="center" width=5%>Capacity</th>
			<th align="center" width=5%>Grades</th>
			<th align="center" width=10%>Contact / Location</th>
			<th align="center" width=5%></th>
		</thead>
		<tbody>
<?php
		$current_type = '';
		foreach($tours as $tid=>$t) {
?>
			<tr id="<?=$t['id']?>" >
			<td align="center"><b><span id="order_<?=$tid?>"><?=$t['num']?></span> - <?=$t['name']?></b><br/><?=$t['description']?></td>
			<td align="center"><?=$t['capacity_min']?> - <?=$t['capacity_max']?></td>
			<td align="center"><?=$t['grade_min']?> - <?=$t['grade_max']?></td>
			<td align="center"><?=$t['contact']?><br/><?=$t['location']?></td>
			<td align="left">
				<div data-role="controlgroup" data-type="horizontal" data-mini="true">
					<a href="#" data-role="button" data-iconpos="notext" data-icon="bars" class='handle'>Move</a>
					<a href="c_tours_edit.php?edit=<?=$t['id']?>" data-role="button" data-iconpos="notext" data-icon="gear" data-ajax="false">Edit</a>
					<a href="#" data-role="button" data-iconpos="notext" data-icon="delete" data-icon="delete" onclick="delete_tour(<?=$t['id']?>)">Delete</a>
				</div>
			</td>

			</tr>

<?php		} ?>
		</tbody>
		</table>
		<a href="#" onclick="return tour_create();" data-role="button" data-icon="plus" data-inline="true" data-ajax="false" data-theme="g">New Tour</a>

<?php		
		break;
	}

/* Everything here is common to all pages */
?>

</div></div>
		
<script>

function delete_tour(id)
{
	if(confirm('Really delete this tour?')) {
		$.post('c_tours_edit.php', { action: "del", id: id }, function(data) { 
			$("#"+id).remove();
		});
	}
}

function tour_create() {
	$.post('c_tours_edit.php', { action: "add" }, function(data) {
		window.location = "c_tours_edit.php?edit="+data;
	});
	return false;
}

$('.tour_list>tbody').sortable({
		'containment': 'parent',
		'opacity': 0.6,
		update: function(event, ui) {
			/* Create an array to store the awards, in order.  Award in index 0 will be assigned ord=1, and up from there */
			var ids = [];
			$(this).children('tr').each(function(index) {
				var id = $(this).attr('id');
				ids[index] = id;
				$('#order_'+id).text(index + 1);
			});
			$.post('c_tours_edit.php', { action: "order", ids: ids }, function(data) {
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


</script>



<?php
sfiab_page_end();
?>
