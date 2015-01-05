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


$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}
switch($action) {
case 'order':
	/* Called when awards are reordered.  awards is an array
	 * of awards, in order, starting from ord=1 */
	$ord = 0;
	foreach($_POST['ids'] as $id) {
		$ord++;
		$id = (int)$id;
		if($id == 0) continue;
		$mysqli->query("UPDATE categories SET cat_id='$ord' WHERE id='$id'");
	}
	form_ajax_response(0);
	exit();

case 'add':
	/* Create a new age category */
	$q = $mysqli->query("SELECT MAX(cat_id) FROM categories WHERE year='{$config['year']}'");

	if($q->num_rows > 0) {
		$r = $q->fetch_row();
		$max_cat_id = $r[0] + 1;
	} else {
		$max_cat_id = 1;
	}
	$mysqli->real_query("INSERT INTO categories(`cat_id`,`name`,`shortform`,`min_grade`,`max_grade`,`year`) VALUES('$max_cat_id','New Category','N','12','13','{$config['year']}')");
	$id = $mysqli->insert_id;
	/* Print the id so the caller can jump to the edit page with the right cat id */
	print("$id");
	exit();

case 'save':
	$id = (int)$_POST['id'];
	post_text($shortform, 'shortform');
	post_text($name, 'name');
	post_int($min_grade, 'min_grade');
	post_int($max_grade, 'max_grade');

	$shortform = $mysqli->real_escape_string($shortform);
	$name = $mysqli->real_escape_string($name);

	$mysqli->real_query("UPDATE categories SET `shortform`='$shortform',`name`='$name',min_grade='$min_grade',max_grade='$max_grade' WHERE id='$id'");
	form_ajax_response(array('status'=>0, 'location'=>'c_config_categories.php'));
	exit();

case 'del':
	/* Delete by id (not cid) and year just to be safe */
	$id = (int)$_POST['id'];
	$mysqli->real_query("DELETE FROM categories WHERE `id`='$id' AND `year`='{$config['year']}'");
	form_ajax_response(0);
	exit();
}

if(array_key_exists('edit', $_GET)) {
	$page = 'edit';
} else {
	$page = '';
}

switch($page) {
case 'edit':
	$id = (int)$_GET['edit'];
	$page_id = 'c_config_edit_category';
	$help = '<p>';
	sfiab_page_begin("Edit Categories", $page_id, $help);

?>
	<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php		$categories = categories_load($mysqli); 
		$category = NULL;
		foreach($categories as $cid=>&$c) {
			if($c['id'] == $id) {
				$category = $cid;
			}
		}

		/* Couldn't find the category */
		if($category === NULL) {
			exit();
		}

?>
		<h3>Edit Age Category:  <?=$categories[$category]['name']?></h3>
<?php
		$form_id = $page_id.'_form';
		form_begin($form_id, 'c_config_categories.php');
		form_hidden($form_id,'id',$categories[$category]['id']);
		form_text($form_id, 'name', "Name", $categories[$category]);
		form_text($form_id, 'shortform', "Shortform (usually a single character)", $categories[$category]);	
		form_text($form_id, 'min_grade', "Min Grade", $categories[$category]);	
		form_text($form_id, 'max_grade', "Max Grade", $categories[$category]);
		form_submit($form_id, 'save', 'Save', 'Information Saved');
?>		<a href="c_config_categories.php" data-ajax="false" data-role="button" data-icon="back" data-theme="r" data-inline="true">Cancel</a>
<?php		form_end($form_id);

	break;

default:

	$page_id = 'c_config_categories';


	$help = '<p>';

	sfiab_page_begin("Edit Categories", $page_id, $help);

?>


	<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php		$categories = categories_load($mysqli); ?>

		<p>Use the <a href="#" data-role="button" data-iconpos="notext" data-inline="true" data-icon="bars">Move</a> button to drag and drop to re-order the categories.  The category number always starts at 1 and increases by 1 for each category, and it may be used in the project number (depend on the project number configuration).


		<table id="config_categories" data-role="table" data-mode="none" class="table_stripes">
		<thead>
			<tr><th width="5%" align="center">Number </th>
			<th align="center" width=5%>ShortForm</th>
			<th align="center" width=30%>Name</th>
			<th align="center" width=5%>Min Grade</th>
			<th align="center" width=5%>Max Grade</th>
			<th align="center" width=5%></th>
		</thead>
		<tbody>
<?php
		$current_type = '';
		foreach($categories as $cid=>$c) {
?>
			<tr id="<?=$c['id']?>" >
			<td align="center"><span id="cat_id_<?=$c['id']?>"><?=$c['cat_id']?></span><br/>
			</td>
			<td align="center"><?=$c['shortform']?></td>
			<td align="center"><?=$c['name']?></td>
			<td align="center"><?=$c['min_grade']?></td>
			<td align="center"><?=$c['max_grade']?></td>
			<td align="left">
				<div data-role="controlgroup" data-type="horizontal" data-mini="true">
					<a href="#" data-role="button" data-iconpos="notext" data-icon="bars" class='handle'>Move</a>
					<a href="c_config_categories.php?edit=<?=$c['id']?>" data-role="button" data-iconpos="notext" data-icon="gear" data-ajax="false">Edit</a>
					<a href="#" data-role="button" data-iconpos="notext" data-icon="delete" data-icon="delete" onclick="delete_category(<?=$c['id']?>)">Delete</a>
				</div>
			</td>

			</tr>

<?php		} ?>
		</tbody>
		</table>
		<a href="#" onclick="return cat_create();" data-role="button" data-icon="plus" data-inline="true" data-ajax="false" data-theme="g">New Category</a>

<?php		
		break;
	}

/* Everything here is common to all pages */
?>

</div></div>
		
<script>

function update_category_ids()
{
	/* Create an array to store the ids, in order.  Cat in index 0 will be assigned ord=1, and up from there */
	var ids = [];
	$("#config_categories>tbody").children('tr').each(function(index) {
			var id = $(this).attr('id');
			ids[index] = id;
			$('#cat_id_'+id).text(index + 1);
		});
	$.post('c_config_categories.php', { action: "order", ids: ids }, function(data) { });

}

/* id is the mysql id, not the cid */
function delete_category(id)
{
	if(confirm('Really delete this category?')) {
		$.post('c_config_categories.php', { action: "del", id: id }, function(data) { 
			$("#"+id).remove();
			update_category_ids();
		});
	}
}


$('#config_categories>tbody').sortable({
		'containment': 'parent',
		'opacity': 0.6,
		update: function(event, ui) {
				update_category_ids();
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

function cat_create() {
	$.post('c_config_categories.php', { action: "add" }, function(data) {
		window.location = "c_config_categories.php?edit="+data;
	});
	return false;
}


</script>



<?php
sfiab_page_end();
?>
