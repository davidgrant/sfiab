<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('committee/email_lists.inc.php');
require_once('email.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

require_once('reports.inc.php');

sfiab_session_start($mysqli, array('committee'));

$u = user_load($mysqli);

$page_id = 'c_communication';
$help = '<p>Communication';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

sfiab_page_begin("Send Emails", $page_id, $help);


$emails = array();
$q = $mysqli->query("SELECT * FROM emails");
while($e = $q->fetch_assoc()) {
	$emails[] = $e;
}

?>
<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 
	<ul data-role="listview" data-inset="true" >
		<li><a data-ajax="false" href="#c_communication_queue">View email send queue and history</a></li>
		<li><a href="#">EMERGENCY pause the queue NOW</a></li>
	</ul>
	<h3>Send / Edit Emails</h3>
	<p>Click on an email below to send it.  You'll be shown the full email text and have to confirm who to send it to before it actually sends.  Click on the gear icon on the right of each email to edit or delete it.
	
<?php
	$form_id = $page_id.'_form';
?>
	<ul data-role="listview" data-inset="true" data-filter="true" data-filter-placeholder="Search by email name or description..." >
<?php	foreach($emails as $e) { 
		$filter_text = $e['name'].' '.$e['description'];
		$s_str = '';
		if($e['type'] == 'system') { 
			$s_str = ' <font size="-1">(System Email)</font>';
		}
?>
		<li data-filtertext="<?=$filter_text?>">
			<a title="Send Email" href="c_communication_send.php?eid=<?=$e['id']?>#<?=$page_id?>_send" data-transition="right" data-ajax="false">
				<h3><?=$e['name']?><?=$s_str?></h3>
				<?=$e['description']?>
			</a>
			<a href="c_communication.php?page=edit&eid=<?=$e['id']?>" title="Edit Email" data-icon="gear" data-ajax="false">Edit</a>
		</li>
<?php	} ?>
	

</div></div>

<?php

sfiab_page_end();
?>
