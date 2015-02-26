<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('email.inc.php');
require_once('awards.inc.php');
require_once('fairs.inc.php');

$mysqli = sfiab_init('committee');


$page_id = 'c_register_feeder';

$help = '
<ul>
</ul>';

sfiab_page_begin("Register Participants from Feeder Fairs", $page_id, $help);

function get_new_users_for_prize($mysqli, &$all_users, &$award, &$prize)
{
	$users = array();
	$winning_projects = prize_load_winners($mysqli, $prize, false);
	foreach($all_users as $uid=>&$user) {
		if(array_key_exists($user['s_pid'], $winning_projects)) {
			$users[$uid] = $user;
		}
	}
	return $users;
}

function get_new_users_for_fair($mysqli, &$all_users, &$fair)
{
	$users = array();
	foreach($all_users as $uid=>&$user) {
		if($user['fair_id'] == $fair['id']) {
			$users[$uid] = $user;
		}
	}
	return $users;
}

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php

$new_users = find_users_needing_registration_email($mysqli);

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

$send_list = array();

switch($action) {
case 'send_all':
	$send_list = $new_users;
	break;

case 'send_by_prize':
	$award_id = (int)$_POST['award_id'];
	$prize_id = (int)$_POST['prize_id'];

	if($award_id > 0) {
		$award = award_load($mysqli, $award_id);
		$send_list = get_new_users_for_prize($mysqli, $new_users, $award, $award['prizes'][$prize_id]);
	}
	break;

case 'send_by_fair':
	$fair_id = (int)$_POST['fair_id'];
	if($fair_id > 0) {
		$fair = fair_load($mysqli, $fair_id);
		$send_list = get_new_users_for_fair($mysqli, $new_users, $fair);
	}
	break;
}


if(count($send_list) > 0) {
	debug("Sending feeder fair welcome emails to ".count($send_list)." users\n");
	foreach($send_list as $uid=>&$user) {
		email_send_welcome_email($mysqli, $user);
	}
?>
	<p>
	<?=count($send_list)?> emails were just sent.  It may take a few moments for to determine if they were sent successfully or not.  Try refreshing this page in a minute or two
<?php
}
			
$feeder_awards = array();
$awards = award_load_all($mysqli);
foreach($awards as $aid=>&$a) {
	if($a['upstream_register_winners']) {
		$feeder_awards[$aid] = $a;
	}
}

form_page_begin($page_id, array());
?>
<h3>Send Feeder Fair Welcome Emails</h3>

When Feeder Fairs upload winners to this fair for an award marked as
"register participants at this fair", accounts are created for all
winners with random passwords. The final step is to send a welcome
email to all winners of such awards.

<h4>All Participants Needing Welcome Email</h4>
<?php
$form_id = $page_id."_form_all";
form_begin($form_id,'c_register_feeder.php', false, false);
form_button($form_id, 'send_all', "Send All ".count($new_users)." Welcome Emails", 'g', 'mail', "Really send ".count($new_users)." welcome emails?" );
form_end($form_id); 
?>

<h3>By Award</h3>
<table data-role="table" data-mode="none">
<thead><tr><th>Award</th><th>#Needing Welcome Email</th><th></th></tr></thead>
<?php	foreach($feeder_awards as $aid=>&$a) { 
	/* Count users */
	foreach($a['prizes'] as $prize_id=>&$prize) {
		$users = get_new_users_for_prize($mysqli, $new_users, $award, $prize);
?>			<tr><td><?=$a['name']?> - <?=$prize['name']?></td>
		<td><?=count($users)?></td>
		<td>
<?php
			$form_id = $page_id."_form_award_$prize_id";
			form_begin($form_id,'c_register_feeder.php', false, false);
			form_hidden($form_id, "award_id", $aid);
			form_hidden($form_id, "prize_id", $prize_id);
			form_button($form_id, 'send_by_prize', 'Send Welcome Emails', 'g', 'mail', "Really send ".count($users)." welcome emails?" );
			form_end($form_id); ?>
		</td></tr>
<?php	}
}
?>

<tr><td colspan=3>
<h3>By Feeder Fair </h3>
</td></tr>
<tr><th>Fair</th><th>#Needing Welcome Email</th><th></th></tr>
<?php
$fairs = fair_load_all($mysqli);
foreach($fairs as $fair_id=>&$fair) { 

	if($fair['type'] != 'sfiab_feeder') {
		continue;
	}
	$users = get_new_users_for_fair($mysqli, $new_users, $fair);
?>	<tr><td><?=$fair['name']?></td>
	<td><?=count($users)?></td>
	<td>
<?php
		$form_id = $page_id."_form_fair_$fair_id";
		form_begin($form_id,'c_register_feeder.php', false, false);
		form_hidden($form_id, "fair_id", $fair_id);
		form_button($form_id, 'send_by_fair', 'Send Welcome Emails', 'g', 'mail', "Really send ".count($users)." welcome emails?" );
		form_end($form_id); ?>
	</td></tr>
<?php	} ?>

<tr><td colspan=3>
<h3>Individually </h3>
</td></tr>
<tr><th>Fair/Award</th><th>Name/Email</th><th></th></tr>
<?php
foreach($new_users as $uid=>&$user) {
	$fair = $fairs[$user['fair_id']];
?>	<tr><td><?=$fair['name']?></td>
	<td><?=$user['name']?><br/><?=$user['email']?></td>
	<td></td>
	</tr>
<?php
} ?>


</table>



</div></div>

<?php
sfiab_page_end();
?>
