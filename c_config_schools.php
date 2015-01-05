<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('schools.inc.php');

$mysqli = sfiab_init('committee');

$u = user_load($mysqli);


$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}
switch($action) {

case 'add':
	$school_id = school_create($mysqli);	
	/* Print the id so the caller can jump to the edit page with the right id */
	print("$school_id");
	exit();

case 'save':
	$id = (int)$_POST['id'];
	$s = school_load($mysqli, $id);

	post_text($s['school'], 'school');
	post_text($s['city'], 'city');
	post_text($s['province'], 'province');

	school_save($mysqli, $s);

	form_ajax_response(array('status'=>0, 'location'=>'c_config_schools.php'));
	exit();

case 'del':
	/* Delete by id (not cid) and year just to be safe */
	$id = (int)$_POST['id'];
	$mysqli->real_query("DELETE FROM schools WHERE `id`='$id' AND `year`='{$config['year']}'");
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
	$page_id = 'c_config_edit_school';
	$help = '<p>';
	sfiab_page_begin("Edit Schools", $page_id, $help);

?>
	<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php		$school = school_load($mysqli, $id);
		/* Couldn't find the school */
		if($school === NULL) {
			exit();
		}

?>
		<h3>Edit School:  <?=$school['school']?></h3>
<?php
		$form_id = $page_id.'_form';
		form_begin($form_id, 'c_config_schools.php');
		form_hidden($form_id,'id',$school['id']);
		form_text($form_id, 'school', "School Name", $school['school']);
		form_text($form_id, 'city', "City", $school['city']);
		form_province($form_id, 'province', "Province", $school['province']);
		form_submit($form_id, 'save', 'Save', 'Information Saved');
?>		<a href="c_config_schools.php" data-ajax="false" data-role="button" data-icon="back" data-theme="r" data-inline="true">Cancel</a>
<?php		form_end($form_id);

	break;

default:

	$page_id = 'c_config_schools';


	$help = '<p>';

	sfiab_page_begin("Edit Schools", $page_id, $help);

?>


	<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php		$schools = school_load_all($mysqli); ?>

		<table id="config_schools" data-role="table" data-mode="none" class="table_stripes">
		<thead>
			<tr>
			<th align="center" width=30%>School</th>
			<th align="center" width=10%>City</th>
			<th align="center" width=5%>Province</th>
			<th align="center" width=5%></th>
		</thead>
		<tbody>
<?php
		$current_type = '';
		foreach($schools as $sid=>$s) {
?>
			<tr id="<?=$s['id']?>" >
			<td align="center"><?=$s['school']?></td>
			<td align="center"><?=$s['city']?></td>
			<td align="center"><?=$s['province']?></td>
			<td align="left">
				<div data-role="controlgroup" data-type="horizontal" data-mini="true">
					<a href="c_config_schools.php?edit=<?=$s['id']?>" data-role="button" data-iconpos="notext" data-icon="gear" data-ajax="false">Edit</a>
					<a href="#" data-role="button" data-iconpos="notext" data-icon="delete" data-icon="delete" onclick="delete_school(<?=$s['id']?>)">Delete</a>
				</div>
			</td>

			</tr>

<?php		} ?>
		</tbody>
		</table>
		<a href="#" onclick="return school_create();" data-role="button" data-icon="plus" data-inline="true" data-ajax="false" data-theme="g">New School</a>

<?php		
		break;
	}

/* Everything here is common to all pages */
?>

</div></div>
		
<script>

/* id is the mysql id, not the cid */
function delete_school(id)
{
	if(confirm('Really delete this school?')) {
		$.post('c_config_schools.php', { action: "del", id: id }, function(data) { 
			$("#"+id).remove();
		});
	}
}

function school_create() {
	$.post('c_config_schools.php', { action: "add" }, function(data) {
		window.location = "c_config_schools.php?edit="+data;
	});
	return false;
}


</script>



<?php
sfiab_page_end();
?>
