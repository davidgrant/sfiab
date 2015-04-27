<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('fairs.inc.php');
require_once('remote.inc.php');

$mysqli = sfiab_init('committee');

$u = user_load($mysqli);


function get_last_sync($mysqli, $fair_id)
{
	global $config;
	$text = '';
	$q = $mysqli->query("SELECT time,result FROM log WHERE `year`='{$config['year']}' AND `type`='sync_stats' AND fair_id='$fair_id' ORDER BY `time` DESC LIMIT 1");
	if($q->num_rows != 1) {
		$text = 'never';
	} else {
		$r = $q->fetch_assoc();
		$text = date("F j, Y h:ia", strtotime($r['time'])).'<br/>';
		if($r['result'] == 1) {
			$text .= '<font color="green">OK</font>';
		} else {
			$text .= '<font color="red">failed</font>';
		}
	}
	return $text;
}


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

case 'check':
	$id = (int)$_POST['id'];
	$f = fair_load($mysqli, $id);
	post_text($f['url'], 'url');
	if($f['password'] === NULL) $f['password'] = '';
	$ret = remote_ping($mysqli, $f);
	if($ret['error'] == 0) {
		$val = array();
		if($f['name'] == '') {
			$f['name'] = $ret['name'];
			$val['name'] = $ret['name'];
		}
		if($f['abbrv'] == '') {
			$f['abbrv'] = $ret['abbrv'];
			$val['abbrv'] = $ret['abbrv'];
		}
		$f['password'] = $f['original']['password'];
		fair_save($mysqli, $f);
		form_ajax_response(array('status'=>0, 'happy'=>"Server Responded: {$ret['name']}.  Use the \"Check Authentication\" button to verify the secret key works", 'val' => $val)) ;
		exit();
	}
	form_ajax_response(array('status'=>1, 'error'=>"Server couldn't be contacted"));
	exit();

case 'auth':
	$id = (int)$_POST['id'];
	$f = fair_load($mysqli, $id);
	$ret = remote_auth_ping($mysqli, $f);
	if($ret['error'] == 0) {
		form_ajax_response(array('status'=>0, 'happy'=>"Server Responded: {$ret['name']}.  Everything seems to be working")) ;
		exit();
	}
	form_ajax_response(array('status'=>1, 'error'=>"Authentication failed.  Make sure the remote fair is using the same secret key"));
	exit();



case 'saveback':
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

	$ret = array('status'=>0);
	if($action == 'saveback') {
		$ret['location'] = 'c_config_fairs.php';
	}
	form_ajax_response($ret);
	exit();

case 'del':
	/* Delete by id (not cid) and year just to be safe */
	$id = (int)$_POST['id'];
	$mysqli->real_query("DELETE FROM fairs WHERE `id`='$id'");
	form_ajax_response(array('status'=>1, 'location' => 'c_config_fairs.php'));
	exit();

case 'allstats':
	remote_queue_get_stats_from_all_fairs($mysqli, $config['year']);
	form_ajax_response(0);
	exit();

case 'sync':
	$id = (int)$_POST['id'];
	$f = fair_load($mysqli, $id);
	if($f['type'] == 'sfiab_upstream') {
		/* Push our stats to upstream */
		$response = remote_push_stats_to_fair($mysqli, $f, $config['year']);
	} else {
		/* Get stats from upstream */
		$response = remote_get_stats_from_fair($mysqli, $f, $config['year']);
	}

	$text = get_last_sync($mysqli, $id);
	print($text);
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
		form_page_begin($page_id, array());

?>
		<h3>Edit Fair:  <?=$fair['name']?></h3>
		<p>For creating a new fair: Enter the Server Address, then press "Check Server", that will verify the server and populate the Name and Abbreviation
<?php
		$form_id = $page_id.'_form';
		form_begin($form_id, 'c_config_fairs.php');
		form_hidden($form_id,'id',$fair['id']);
		form_text($form_id, 'url', "Server Address", $fair['url']);
		form_button_with_label($form_id, 'check', '', 'Check Server');
		form_text($form_id, 'name', "Name", $fair['name']);
		form_text($form_id, 'abbrv', "Abbreviation", $fair['abbrv']);
		form_select($form_id, 'type', "Type", $fair_types, $fair['type']);
		form_text($form_id, 'website', "Website", $fair['website']);
		form_text($form_id, 'password', "Secret Key", $fair['password']);
		form_button_with_label($form_id, 'pass', '', 'Generate Random Secret Key');
		form_text($form_id, 'username', "YSC Username (only for YSC upstream fairs)", $fair['username']);
		form_submit($form_id, 'save', 'Save', 'Information Saved');
		form_submit($form_id, 'saveback', 'Save and Go Back', 'Information Saved', 'g', 'back');
?>		<a href="c_config_fairs.php" data-ajax="false" data-role="button" data-icon="back" data-theme="r" data-inline="true">Cancel, Go Back</a>
<?php		form_end($form_id);
?>
		<hr/>
		<h3>Check Authentication</h3>
		<p>Use this button to check that your secret key is working after you have saved all the information above</p>
<?php		
		$form_id = $page_id.'_auth_form';
		form_begin($form_id, 'c_config_fairs.php');
		form_hidden($form_id,'id',$fair['id']);
		form_button($form_id, 'auth', 'Check Authentication');
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

		<hr/>
		<h3>Delete Fair</h3>
<?php
		$form_id = $page_id.'_del_form';
		form_begin($form_id, 'c_config_fairs.php');
		form_hidden($form_id,'id',$fair['id']);
		form_submit_enabled($form_id, 'del', 'Delete This Fair', 'Deleted', 'r', 'delete', 'Really Delete this fair?');
		form_end($form_id);

	break;

default:
	$page_id = 'c_config_fairs';
	$help = '<p>';

	sfiab_page_begin("Edit Fairs", $page_id, $help);
?>
	<div data-role="page" id="<?=$page_id?>" class="sfiab_page" > 

<?php		$fairs = fair_load_all($mysqli); ?>

		<h3>Feeder/Upstream Fairs</h3>
		<p>Fair Types:
		<ul><li><b>Feeder Fairs</b> are fairs that provide this fair with students.  This fair may export awards to feeder fairs, retrieve winners for those awards, and download fair statistics.
		<li><b>Upstream Fairs</b> are fairs that this fair can send students to.  This fair can upload winners (of awards downloaded from the upstream fair) to the upstream fair, and can upload fair statistics.
		<li><b>Youth Science Canada</b> is a special type of upstream fair that uses a different communication protocol.  Winners uploaded to this fair are registered for the CWSF.
		</ul>

		<table id="config_fairs" data-role="table" data-mode="none" class="table_stripes">
		<thead>
			<tr>
			<th align="center" width=20%>Fair name</th>
			<th align="center" width=10%>Type</th>
			<th align="center" width=10%>Last Stats Sync</th>
			<th align="center" width=5%></th>
		</thead>
		<tbody>
<?php
		$current_type = '';
		foreach($fairs as $fid=>&$f) { 

			$sync_text = get_last_sync($mysqli, $fid);


			switch($f['type']) {
			case 'sfiab_feeder':
			case 'sfiab_upstream':
				$sync_link = "href=\"#\" onclick=\"fair_sync_stats({$f['id']})\"";
				break;
			case 'ysc':
				$sync_link = "href=\"c_ysc_stats.php\"";
				break;
			}

?>

			<tr id="<?=$s['id']?>" >
			<td align="center"><?=$f['name']?></td>
			<td align="center"><?=$fair_types[$f['type']]?></td>
			<td align="center"><span id="fair_sync_result_<?=$f['id']?>"><?=$sync_text?></span></td>
			<td align="left">
				<div data-role="controlgroup" data-type="horizontal" data-mini="true">
					<a <?=$sync_link?> data-role="button" data-icon="recycle" data-iconpos="notext" >Sync</a>
					<a href="c_config_fairs.php?edit=<?=$f['id']?>" data-role="button" data-iconpos="notext" data-icon="gear" data-ajax="false">Edit</a>
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
		form_begin($form_id, 'c_config_fairs.php');
		form_button($form_id, 'allstats', 'Synchronize All Statistics' );
		form_end($form_id);
?>
			

<?php		
		break;
	}

/* Everything here is common to all pages */
?>

	</div>
		
<script>

function fair_create() {
	$.post('c_config_fairs.php', { action: "add" }, function(data) {
		window.location = "c_config_fairs.php?edit="+data;
	});
	return false;
}

function fair_sync_stats(id) {
	$("#fair_sync_result_"+id).html("");
	$.post('c_config_fairs.php', { action: "sync", id: id }, function(data) {
		$("#fair_sync_result_"+id).html(data);
		
		
	});
	return false;
}


</script>



<?php
sfiab_page_end();
?>
