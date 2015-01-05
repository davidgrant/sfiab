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
		$mysqli->query("UPDATE challenges SET chal_id='$ord' WHERE id='$id'");
	}
	form_ajax_response(0);
	exit();

case 'add':
	/* Create a new age challenge */
	$q = $mysqli->query("SELECT MAX(chal_id) FROM challenges WHERE year='{$config['year']}'");

	if($q->num_rows > 0) {
		$r = $q->fetch_row();
		$max_chal_id = $r[0] + 1;
	} else {
		$max_chal_id = 1;
	}
	$mysqli->real_query("INSERT INTO challenges(`chal_id`,`name`,`shortform`,`year`) VALUES('$max_chal_id','New Challenge','N','{$config['year']}')");
	$id = $mysqli->insert_id;
	/* Print the id so the caller can jump to the edit page with the right cat id */
	print("$id");
	exit();

case 'save':
	$id = (int)$_POST['id'];
	post_text($shortform, 'shortform');
	post_text($name, 'name');

	$shortform = $mysqli->real_escape_string($shortform);
	$name = $mysqli->real_escape_string($name);

	$mysqli->real_query("UPDATE challenges SET `shortform`='$shortform',`name`='$name' WHERE id='$id'");
	form_ajax_response(array('status'=>0, 'location'=>'c_config_challenges.php'));
	exit();

case 'del':
	/* Delete by id (not cid) and year just to be safe */
	$id = (int)$_POST['id'];
	$mysqli->real_query("DELETE FROM challenges WHERE `id`='$id' AND `year`='{$config['year']}'");
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
	$page_id = 'c_config_edit_challenge';
	$help = '<p>';
	sfiab_page_begin("Edit Challenges", $page_id, $help);

?>
	<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php		$challenges = challenges_load($mysqli); 
		$challenge = NULL;
		foreach($challenges as $cid=>&$c) {
			if($c['id'] == $id) {
				$challenge = $cid;
			}
		}

		/* Couldn't find the challenge */
		if($challenge === NULL) {
			exit();
		}

?>
		<h3>Edit Age Challenge:  <?=$challenges[$challenge]['name']?></h3>
<?php
		$form_id = $page_id.'_form';
		form_begin($form_id, 'c_config_challenges.php');
		form_hidden($form_id,'id',$challenges[$challenge]['id']);
		form_text($form_id, 'name', "Name", $challenges[$challenge]);
		form_text($form_id, 'shortform', "Shortform (usually a single character)", $challenges[$challenge]);	
		form_submit($form_id, 'save', 'Save', 'Information Saved');
?>		<a href="c_config_challenges.php" data-ajax="false" data-role="button" data-icon="back" data-theme="r" data-inline="true">Cancel</a>
<?php		form_end($form_id);

	break;

default:

	$page_id = 'c_config_challenges';


	$help = '<p>';

	sfiab_page_begin("Edit Challenges", $page_id, $help);

?>


	<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php		$challenges = challenges_load($mysqli); ?>

		<p>Use the <a href="#" data-role="button" data-iconpos="notext" data-inline="true" data-icon="bars">Move</a> button to drag and drop to re-order the challenges.  The challenge number always starts at 1 and increases by 1 for each challenge, and it may be used in the project number (depend on the project number configuration).


		<table id="config_challenges" data-role="table" data-mode="none" class="table_stripes">
		<thead>
			<tr><th width="5%" align="center">Number </th>
			<th align="center" width=5%>ShortForm</th>
			<th align="center" width=30%>Name</th>
			<th align="center" width=5%></th>
		</thead>
		<tbody>
<?php
		$current_type = '';
		foreach($challenges as $cid=>$c) {
?>
			<tr id="<?=$c['id']?>" >
			<td align="center"><span id="chal_id_<?=$c['id']?>"><?=$c['chal_id']?></span><br/>
			</td>
			<td align="center"><?=$c['shortform']?></td>
			<td align="center"><?=$c['name']?></td>
			<td align="left">
				<div data-role="controlgroup" data-type="horizontal" data-mini="true">
					<a href="#" data-role="button" data-iconpos="notext" data-icon="bars" class='handle'>Move</a>
					<a href="c_config_challenges.php?edit=<?=$c['id']?>" data-role="button" data-iconpos="notext" data-icon="gear" data-ajax="false">Edit</a>
					<a href="#" data-role="button" data-iconpos="notext" data-icon="delete" data-icon="delete" onclick="delete_challenge(<?=$c['id']?>)">Delete</a>
				</div>
			</td>

			</tr>

<?php		} ?>
		</tbody>
		</table>
		<a href="#" onclick="return cat_create();" data-role="button" data-icon="plus" data-inline="true" data-ajax="false" data-theme="g">New Challenge</a>

<?php		
		break;
	}

/* Everything here is common to all pages */
?>

</div></div>
		
<script>

function update_challenge_ids()
{
	/* Create an array to store the ids, in order.  Cat in index 0 will be assigned ord=1, and up from there */
	var ids = [];
	$("#config_challenges>tbody").children('tr').each(function(index) {
			var id = $(this).attr('id');
			ids[index] = id;
			$('#chal_id_'+id).text(index + 1);
		});
	$.post('c_config_challenges.php', { action: "order", ids: ids }, function(data) { });

}

/* id is the mysql id, not the cid */
function delete_challenge(id)
{
	if(confirm('Really delete this challenge?')) {
		$.post('c_config_challenges.php', { action: "del", id: id }, function(data) { 
			$("#"+id).remove();
			update_challenge_ids();
		});
	}
}


$('#config_challenges>tbody').sortable({
		'containment': 'parent',
		'opacity': 0.6,
		update: function(event, ui) {
				update_challenge_ids();
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
	$.post('c_config_challenges.php', { action: "add" }, function(data) {
		window.location = "c_config_challenges.php?edit="+data;
	});
	return false;
}


</script>



<?php
sfiab_page_end();
?>
