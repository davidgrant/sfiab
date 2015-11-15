<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('fairs.inc.php');
require_once('remote.inc.php');

$mysqli = sfiab_init('committee');

$u = user_load($mysqli);

$page_id = 'c_fairs';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}
switch($action) {
case 'stats':
	remote_queue_get_stats_from_all_fairs($mysqli, $config['year']);
	form_ajax_response(0);
	exit();
}

$fairs = fair_load_all($mysqli);



sfiab_page_begin($u, "Sync Fair Data", $page_id);
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php	form_page_begin($page_id, array());

?>	
	<h3>Feeder/Upstream Fairs</h3>
	<p><b>Feeder Fairs</b> are fairs that provide this fair with students.  This fair may export awards to feeder fairs, retrieve winners for those awards, and download fair statistics.
	<p><b>Upstream Fairs</b> are fairs that this fair can send students to.  This fair can upload winners (of awards downloaded from the upstream fair) back to the upstream fair.
	<p>Youth Science Canada is a special type of upstream fair that uses a different communication protocol.  Winners uploaded to this fair are registered for the CWSF.
	

	<table data-role="table" data-mode="column-toggle">
	<thead>
	<tr><th>Fair Name</th><th>Type</th><th>Last Stats Sync</th>
	</tr></thead>

<?php	foreach($fairs as $fid=>&$f) { 
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
		<tr><td><?=$f['name']?></td>
		<td><?=$fair_types[$f['type']]?></td>
		<td><?=$last_sync?></td>
		</tr>
<?php	} ?>
	</table>
		

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

</div></div>
	

<?php
sfiab_page_end();
?>
