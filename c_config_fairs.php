<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('fairs.inc.php');

$mysqli = sfiab_init('committee');

$u = user_load($mysqli);


$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}
switch($action) {

case 'add':
	$fair_id = fair_create($mysqli);	
	/* Print the id so the caller can jump to the edit page with the right id */
	print("$fair_id");
	exit();

case 'pass':
	$id = (int)$_POST['id'];
	$f = fair_load($mysqli, $id);
	$f['password'] = base64_encode(mcrypt_create_iv(96, MCRYPT_DEV_URANDOM));
	fair_save($mysqli, $f);
	form_ajax_response(array('status'=>0, 'val' => array('password' => $f['password']))) ;
	exit();
	
case 'save':
	$id = (int)$_POST['id'];
	$f = fair_load($mysqli, $id);

	post_text($f['name'], 'name');
	post_text($f['abbrv'], 'abbrv');
	post_text($f['type'], 'type');
	post_text($f['url'], 'url');
	post_text($f['website'], 'website');
	post_text($f['password'], 'password');
	if($f['type'] == 'ysc') {
		post_text($f['username'], 'username');
	} else {
		$f['username'] = '';
	}
	fair_save($mysqli, $f);

	form_ajax_response(array('status'=>0, 'location'=>'c_config_fairs.php'));
	exit();

case 'del':
	/* Delete by id (not cid) and year just to be safe */
	$id = (int)$_POST['id'];
	$mysqli->real_query("DELETE FROM fairs WHERE `id`='$id'");
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
	$page_id = 'c_config_edit_fair';
	$help = '<p>';
	sfiab_page_begin("Edit Fairs", $page_id, $help);

?>
	<div data-role="page" id="<?=$page_id?>" class="sfiab_page" > 

<?php		$fair = fair_load($mysqli, $id);
		/* Couldn't find the fair */
		if($fair === NULL) {
			exit();
		}

?>
		<h3>Edit Fair:  <?=$fair['name']?></h3>
<?php
		$form_id = $page_id.'_form';
		form_begin($form_id, 'c_config_fairs.php');
		form_hidden($form_id,'id',$fair['id']);
		form_text($form_id, 'name', "Name", $fair['name']);
		form_text($form_id, 'abbrv', "Abbreviation", $fair['abbrv']);
		form_select($form_id, 'type', "Type", $fair_types, $fair['type']);
		form_text($form_id, 'url', "Server Address", $fair['url']);
		form_text($form_id, 'website', "Website", $fair['website']);
		form_text($form_id, 'password', "Secret Key", $fair['password']);
		form_text($form_id, 'username', "YSC Username (only for YSC upstream fairs)", $fair['username']);
		form_submit($form_id, 'save', 'Save', 'Information Saved');
?>		<a href="c_config_fairs.php" data-ajax="false" data-role="button" data-icon="back" data-theme="r" data-inline="true">Cancel</a>
		<hr/>
		<h3>Generate a new Secret Key</h3>
<?php		form_button($form_id, 'pass', 'Generate Random Secret Key');
		form_end($form_id);
?>
		<hr/>
		<h3>How To Connect two SFIABs</h3>
		<p>On the Feeder Fair:
		<ul><li>Create a fair entry for the Upstream fair, select the type as 'Upstream'
		<li>Enter the server address (usually the website of the registration system)
		<li>Enter or generate a secret key.  Both the Upstream and Feeder fair must have the SAME secret key, so generate it on one, and enter it on the other
		</ul>
		On the Upstream Fair:
		<ul><li>Create a fair entry for the Feeder fair, select the type as 'Feeder'
		<li>Enter the server address (usually the website of the registration system)
		<li>Enter or generate a secret key.  Both the Upstream and Feeder fair must have the SAME secret key, so generate it on one, and enter it on the other
		</ul>

<?php


	break;

default:
	$page_id = 'c_config_fairs';
	$help = '<p>';

	sfiab_page_begin("Edit Fairs", $page_id, $help);
?>
	<div data-role="page" id="<?=$page_id?>" class="sfiab_page" > 

<?php		$fairs = fair_load_all($mysqli); ?>

		<h3>Feeder/Upstream Fairs</h3>
		<p><b>Feeder Fairs</b> are fairs that provide this fair with students.  This fair may export awards to feeder fairs, retrieve winners for those awards, and download fair statistics.
		<p><b>Upstream Fairs</b> are fairs that this fair can send students to.  This fair can upload winners (of awards downloaded from the upstream fair) back to the upstream fair.
		<p>Youth Science Canada is a special type of upstream fair that uses a different communication protocol.  Winners uploaded to this fair are registered for the CWSF.

		<table id="config_fairs" data-role="table" data-mode="none" class="table_stripes">
		<thead>
			<tr>
			<th align="center" width=30%>Fair name</th>
			<th align="center" width=10%>Type</th>
			<th align="center" width=5%>Last Stats Sync</th>
			<th align="center" width=5%></th>
		</thead>
		<tbody>
<?php
		$current_type = '';
		foreach($fairs as $fid=>&$f) { 
			if($f['type'] == 'sfiab_feeder') {
				$q = $mysqli->query("SELECT * FROM log WHERE `year`='{$config['year']}' AND `type`='sync_stats' AND `result`='1' AND fair_id='$fid' ORDER BY `time` DESC LIMIT 1");
				if($q->num_rows != 1) {
					$last_sync = 'never';
				} else {
					$r = $q->fetch_assoc();
					$last_sync = date("F j, Y h:ia", strtotime($r['time']));
				}
			} else {
				$last_sync = '--';
			}
?>

			<tr id="<?=$s['id']?>" >
			<td align="center"><?=$f['name']?></td>
			<td align="center"><?=$fair_types[$f['type']]?></td>
			<td align="center"><?=$last_sync?></td>
			<td align="left">
				<div data-role="controlgroup" data-type="horizontal" data-mini="true">
					<a href="c_config_fairs.php?edit=<?=$f['id']?>" data-role="button" data-iconpos="notext" data-icon="gear" data-ajax="false">Edit</a>
					<a href="#" data-role="button" data-iconpos="notext" data-icon="delete" data-icon="delete" onclick="delete_fair(<?=$f['id']?>)">Delete</a>
				</div>
			</td>

			</tr>

<?php		} ?>
		</tbody>
		</table>
		<a href="#" onclick="return fair_create();" data-role="button" data-icon="plus" data-inline="true" data-ajax="false" data-theme="g">New Fair</a>

		<hr/>
		<h3>Statistics Synchronization</h3>
		SFIAB does three types of synchronization.  The last two (awards and winners) are automatic.

		<ul><li>Fair statistics (like student counts) from feeder fairs to upstream fairs
		<li>External Awards from upstream fairs to feeder fairs
		<li>Winners for External Awards from feeder fairs back to upstream fairs
		</ul>

		Before generating reports with statistics from feeder fairs, they
		should all be synchronized.  It may take a minute or two for all feeder
		fairs to respond.  
		
<?php
		$form_id = $page_id.'_form';
		form_begin($form_id, 'c_fairs.php');
		form_button($form_id, 'stats', 'Synchronize Statistics' );
		form_end($form_id);
?>
			

<?php		
		break;
	}

/* Everything here is common to all pages */
?>

	</div>
		
<script>

/* id is the mysql id, not the cid */
function delete_fair(id)
{
	if(confirm('Really delete this fair?')) {
		$.post('c_config_fairs.php', { action: "del", id: id }, function(data) { 
			$("#"+id).remove();
		});
	}
}

function fair_create() {
	$.post('c_config_fairs.php', { action: "add" }, function(data) {
		window.location = "c_config_fairs.php?edit="+data;
	});
	return false;
}


</script>



<?php
sfiab_page_end();
?>
