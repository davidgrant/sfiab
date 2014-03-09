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

$page_id = 'c_communication_send';
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

	if($to == 'one_user') {
		$username = $_POST['username'];
		$username = $mysqli->real_escape_string($username);
		$u = user_load_by_username($mysqli, $username);
		if($u === NULL) {
			form_ajax_response(array('status'=>1, 'error'=>'Unknown username'));
		}
		email_send($mysqli, $e['name'], $u['uid']);
	} else {
		/* Query all users */
		$query = "SELECT * FROM users WHERE year={$config['year']} AND {$elist['where']}";
	//	print($query);
		$r = $mysqli->query($query);
		print($mysqli->error);

		while($ua = $r->fetch_assoc()) {
	//		print_r($ua);
			$u = user_load_from_data($mysqli, $ua);
			email_send($mysqli, $e['name'], $u['uid']);
		}
	}
	form_ajax_response(array('status'=>0, 'location'=>'c_communication_queue.php'));
	exit();
}

sfiab_page_begin("Send Email", $page_id, $help);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 
<?php
	$form_id = $page_id."_form";
	$eid = (int)$_GET['eid'];
	if($eid == 0) exit();

	$q = $mysqli->query("SELECT * FROM emails WHERE id=$eid");
	$e = $q->fetch_assoc();
	form_page_begin($page_id, array());
	$current_list = '';
	$username = '';

?>
	<h3>Send Email</h3>
	<b><?=$e['name']?></b>
	<p><?=$e['description']?></b>
	<hr/>

<?php	form_begin($form_id, 'c_communication_send.php'); 
	form_hidden($form_id, 'eid', $e['id']);
	form_label($form_id, 'from', 'From', "{$e['from_name']} &lt;{$e['from_email']}&gt;");
	form_select($form_id, 'list', 'To', $email_lists, $current_list);
?>	<div id='to_single_user' style='display:none'>
<?php		form_text($form_id, 'username', 'Username', $username);
?>	</div>
<?php	form_label($form_id, 'subject', 'Subject', $e['subject']);
?>
	<hr/>
	<?=nl2br(htmlspecialchars($e['body']))?>
	<hr/>

<?php	form_button($form_id, 'send', 'Yes, Send Email', 'g', 'mail'); ?>
	<a href="#" data-role="button" data-inline="true" data-icon="delete" data-rel="back" data-theme="r">Cancel, Don't Send</a>
<?php	form_end($form_id); ?>

</div></div>

	<script>
		$( "#<?=$form_id?>_list" ).change(function() {
			var val = $("#<?=$form_id?>_list option:selected").val();
			if(val == 'one_user') {
				$('#to_single_user').show();
			} else {
				$('#to_single_user').hide();
			}
		});
	</script>


<?php

sfiab_page_end();
?>
