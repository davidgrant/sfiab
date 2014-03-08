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

switch($action) {
case 'send':
	$eid = (int)$_POST['eid'];
	$to = $_POST['list'];
	if(!array_key_exists($to, $email_lists)) {
		exit();
	}

	$q = $mysqli->query("SELECT * FROM emails WHERE id=$eid");
	$e = $q->fetch_assoc();

	$elist = &$email_lists[$to];

	/* Query all users */
	$query = "SELECT * FROM users WHERE year={$config['year']} AND {$elist['where']}";
	$r = $mysqli->query($query);
	print($mysqli->error);
	while($ua = $r->fetch_assoc()) {
		$u = user_load_from_data($mysqli, $ua);
		email_send($mysqli, $e['name'], $u['uid']);
	}
	form_ajax_response(array('status'=>0, 'location'=>'c_communication.php#c_communication_queue'));
	exit();
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
			<a title="Send Email" href="c_communication.php?eid=<?=$e['id']?>#<?=$page_id?>_send" data-transition="right" data-ajax="false">
				<h3><?=$e['name']?><?=$s_str?></h3>
				<?=$e['description']?>
			</a>
			<a href="c_communication.php?page=edit&eid=<?=$e['id']?>" title="Edit Email" data-icon="gear" data-ajax="false">Edit</a>
		</li>
<?php	} ?>
	

</div></div>

<div data-role="page" id="<?=$page_id?>_send"><div data-role="main" class="sfiab_page" > 
<?php
	$form_id = $page_id."_send_form";
	$eid = (int)$_GET['eid'];
	if($eid == 0) exit();

	$q = $mysqli->query("SELECT * FROM emails WHERE id=$eid");
	$e = $q->fetch_assoc();
	form_page_begin($page_id.'_send', array());
	$current_list = '';

?>
	<h3>Send Email</h3>
	<b><?=$e['name']?></b>
	<p><?=$e['description']?></b>
	<hr/>

<?php	form_begin($form_id, 'c_communication.php'); 
	form_hidden($form_id, 'eid', $e['id']);
	form_label($form_id, 'from', 'From', "{$e['from_name']} &lt;{$e['from_email']}&gt;");
	form_select($form_id, 'list', 'To', $email_lists, $current_list);
	form_label($form_id, 'subject', 'Subject', $e['subject']);
?>
	<hr/>
	<?=nl2br(htmlspecialchars($e['body']))?>
	<hr/>

<?php	form_button($form_id, 'send', 'Yes, Send Email', 'g', 'mail'); ?>
	<a href="#" data-role="button" data-inline="true" data-icon="delete" data-rel="back" data-theme="r">Cancel, Don't Send</a>
<?php	form_end($form_id); ?>

</div></div>

<div data-role="page" id="<?=$page_id?>_queue"><div data-role="main" class="sfiab_page" > 
	<h3>Current Email Queue</h3>
<?php
	
?>
</div></div>


<?php

sfiab_page_end();
?>
