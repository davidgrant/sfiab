<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('committee/email_lists.inc.php');
require_once('email.inc.php');
require_once('reports.inc.php');

$mysqli = sfiab_init('committee');

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
	$year = $_POST['year'];

	if($year != 'all') {
		$year = (int)$year;
		if($year == 0) {
			form_ajax_response(array('status'=>1, 'error'=>'Please select a year'));
			exit();
		}
		$year_q = "year='$year'";
	} else {
		$year_q = '1';
	}


	if(!array_key_exists($to, $email_lists)) {
		exit();
	}

	$q = $mysqli->query("SELECT * FROM emails WHERE id=$eid");
	$e = $q->fetch_assoc();

	$elist = &$email_lists[$to];

	if($to == 'one_user') {
		$usernames = explode(',', $_POST['username']);
		$users = array();
		foreach($usernames as $username) {
			$username = $mysqli->real_escape_string(trim($username));
			$uu = user_load_by_username($mysqli, $username);
			if($uu === NULL) {
				form_ajax_response(array('status'=>1, 'error'=>"Unknown username $username, no email sent to any user"));
				exit;
			}
			$users[] = $uu;
		}

		foreach($users as $uu) {
			email_send($mysqli, $e['name'], $uu);
		}
	} else {
		/* Query all users, fetch only email per username  */
		$query = "SELECT * FROM users WHERE $year_q AND {$elist['where']} ORDER BY `year` DESC";
//		print($query);
		$r = $mysqli->query($query);
		print($mysqli->error);

		$users = array();
		$emails = array();
		while($ua = $r->fetch_assoc()) {
			$u = user_load_from_data($mysqli, $ua);
			if(array_key_exists($u['email'], $emails)) {
				/* Email already seen, send to the same email only in the same year
				 * e.g., multiple registrations int he same year using the same email */
				if($emails[$u['email']] != $u['year']) {
					continue;
				}
			}
			/* Tag this email with the year to avoid dupes */
			$emails[$u['email']] = $u['year'];
			/* Add this user to the list of recipients */
			$users[] = $u;
		}
		email_send_to_list($mysqli, $e['name'], $users);
	}
	form_ajax_response(array('status'=>0, 'location'=>'c_communication_queue.php'));
	exit();
}

sfiab_page_begin($u, "Send Email", $page_id, $help);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 
<?php
	$form_id = $page_id."_form";
	$eid = (int)$_GET['eid'];
	if($eid == 0) exit();

	$q = $mysqli->query("SELECT MIN(year) AS M FROM users");
	$u = $q->fetch_assoc();
	$min_year = (int)$u['M'];
	$year_list = array();
	for($y=$config['year']; $y>=$min_year; $y--) {
		$year_list[$y] = $y;
	}
	$year_list['all'] = "All Years";

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
	
<?php	
	$y = (int)$config['year'];
	form_select($form_id, 'year', 'Year', $year_list, $y);
	form_label($form_id, 'subject', 'Subject', $e['subject']);
?>
	<hr/>
	<?=nl2br(htmlspecialchars($e['body']))?>
	<hr/>

	<div class="error"><p>Note: It may take a few minutes (literally minutes) of seemingly doing nothing to inject mail to lots of recipients.  
	When it's done the page will change to the email send queue page.</div>

<?php	form_submit_enabled($form_id, 'send', 'Yes, Send Email', 'Sending.  Do not refresh this page or close it', 'g', 'mail'); ?>
	<a href="#" data-role="button" data-inline="true" data-icon="delete" data-rel="back" data-theme="r">Cancel, Don't Send</a>
<?php	form_end($form_id); ?>

</div></div>

	<script>
		function <?=$form_id?>_pre_submit(form) {
			$('#<?=$form_id?>_submit_send').addClass('ui-disabled');
		}

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
