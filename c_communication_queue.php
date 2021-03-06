<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('committee/email_lists.inc.php');
require_once('email.inc.php');

$mysqli = sfiab_init('committee');

$u = user_load($mysqli);

$page_id = 'c_communication_queue';
$help = '<p>Communication';

$emails = array();
$q = $mysqli->query("SELECT * FROM emails");
while($e = $q->fetch_assoc()) {
	$emails[$e['id']] = email_load($mysqli, '', -1, $e);
}

$action = '';
if(array_key_exists('action', $_GET)) {
	$action = $_GET['action'];
}

switch($action) {
case 'qdelall':
	$mysqli->real_query("DELETE FROM queue WHERE command='email' AND result='queued'");
	$action = '';
	break;

case 'qstop':
	queue_stop($mysqli);
	$action = '';
	break;
case 'qstart':
	queue_start($mysqli);
	$action = '';
	break;

}


sfiab_page_begin($u, "Email Queue", $page_id, $help);
?>


<div data-role="page" id="<?=$page_id?>_queue"><div data-role="main" class="sfiab_page" > 
	<h3>Current Email Queue</h3>
<?php
	$qstopped = queue_stopped($mysqli);
	$q = $mysqli->query("SELECT * FROM queue WHERE command='email' AND result='queued'");
?>
	<p>Current Email Queue Status is: <b>
<?php	if($qstopped) { ?>	
		<font color="red">Stopped</font> 
<?php	} else { ?>
		<font color="green">Active</font> 
<?php	} ?>
	</b><br/>
	<p>Queued Emails: <b><?=$q->num_rows?></b>

	<div data-role="controlgroup" data-type="horizontal" data-mini="true">
<?php		if($qstopped) { ?>	
			<a href="c_communication_queue.php?action=qstart" data-role="button" data-icon="power" data-theme='g' data-inline="true" data-ajax="false">Start Queue</a>
<?php		} else { ?>
			<a href="c_communication_queue.php?action=qstop" data-role="button" data-icon="forbidden" data-theme='r' data-inline="true" data-ajax="false">Stop Queue</a>
<?php		} ?>

		<a href="c_communication_queue.php?action=qdelall" data-role="button" data-icon="delete" data-theme='r' data-inline="true" data-ajax="false">Delete Emails in Queue</a>
	</div>

	<table>
	<tr><td>Email</td><td>To Name (uid)</td><td>To Email</td><td>Status</td></tr>
<?php
	while($eq = $q->fetch_assoc()) {
?>		<tr><td><?=$emails[$eq['emails_id']]['name']?></td>
			<td><?=$eq['to_name']?> (<?=$eq['to_uid']?>)</td>
			<td><?=$eq['to_email']?></td><td>Queued</td></tr>
<?php	} 

		
	
?>
</div></div>


<?php

sfiab_page_end();
?>
