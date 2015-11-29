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

$page_id = 'c_communication';
$help = '<p>Communication';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
} else if(array_key_exists('action', $_GET)) {
	$action = $_GET['action'];
}

$from_name = array('email_chair' => $config['fair_abbreviation']." Chair", 
		   'email_chiefjudge' => $config['fair_abbreviation']." Chief Judge",
		   'email_ethics' => $config['fair_abbreviation']." Ethics",
		   'email_registration' => $config['fair_abbreviation']." Registration");

$from_email = array('email_chair' => $from_name['email_chair']." &lt;".$config['email_chair']."&gt;",
	            'email_chiefjudge' => $from_name['email_chiefjudge']." &lt;".$config['email_chiefjudge']."&gt;",
		    'email_ethics' => $from_name['email_ethics']." &lt;".$config['email_ethics']."&gt;",
		    'email_registration' => $from_name['email_registration']." &lt;".$config['email_registration']."&gt;",
		    'specify_email' => "Other - Enter the from email name and address below");

switch($action) {

case 'new': 
	$eid = email_create($mysqli);

case 'edit':
	if($action == 'new') {
		/* Fell through from above, leave eid alone */
	} else {
		$eid = (int)$_GET['eid'];
		if($eid == 0) exit();
	}

	/* load available categories */
	$sections = array();
	$q = $mysqli->query("SELECT DISTINCT(`section`) AS S FROM emails ORDER BY `section`");
	while($s = $q->fetch_assoc()) {
		$sections = $s['S'];
	}

	sfiab_page_begin($u, "Edit Email", $page_id.'_edit', $help);
?>
	<div data-role="page" id="<?=$page_id?>_edit"><div data-role="main" class="sfiab_page" > 

	<h3>Edit Email</h3>
<?php
	form_page_begin($page_id.'_edit', array());
	$form_id = $page_id.'_edit_form';
	$e = email_load($mysqli, '', $eid);

	/* Figure out what the from email is */
	$email_key = 'specify_email';
	foreach($from_name as $key=>$val) {
		if($e['from_name'] == $val) {
			$email_key = $key;
			break;
		}
	}
?>

	<div data-role="collapsible" data-collapsed="true" data-iconpos="right" data-collapsed-icon="carat-d" data-expanded-icon="carat-u" >
	<h3>Email Replacement Keys</h3>
	<?=print_replace_vars_table($u);?>
	</div>
	
	<p>Replacement Keys can be used in the subject and body of the email.
<?php

	

	form_begin($form_id, 'c_communication.php');
	form_hidden($form_id, 'eid', $eid);
	$d = ($e['section'] == 'System') ? 'disabled="disabled"' : '';
	form_text($form_id, 'section', 'Section', $e, 'text', $d);
	form_text($form_id, 'name', 'Name', $e, 'text', $d);
	form_textbox($form_id, 'description', 'Description', $e);
	form_select($form_id, 'email_key', "From", $from_email, $email_key);
	$div_style = $email_key == 'specify_email' ? '' : 'style="display:none"';
?>	<div id="specify_email" <?=$div_style?> >
<?php		form_text($form_id, 'from_name', 'From Name', $e);
		form_text($form_id, 'from_email', 'From Email', $e, 'email');
?>	</div> 		
<?php	form_text($form_id, 'subject', 'Subject', $e);
	form_textbox($form_id, 'body', 'Body', $e);
	form_submit($form_id, 'save', 'Save', 'Email Saved');
?>
	<a href="#" data-role="button" data-inline="true" data-icon="back" data-rel="back" data-theme="r">Cancel</a>
	<hr/>
<?php
	if($e['section'] == 'System') {
?>		<button type="submit" data-role="button" data-inline="true" data-icon="delete" data-theme="r" disabled="disabled">System Emails cannot be Deleted</button>
<?php	} else {
		form_button($form_id, 'delete', 'Delete this Email', 'r', 'delete');
	}
	form_end($form_id);
	sfiab_page_end();
?>

	<script>
	$( "#c_communication_edit_form_email_key" ).change(function(event) {
		var val = $( "#c_communication_edit_form_email_key" ).val();
		if( val == 'specify_email') {
			$("#specify_email").show();
		} else {
			$("#specify_email").hide();
		}
	});
	</script>


	</div></div>
<?php
	exit();

case 'save':
	$eid = (int)$_POST['eid'];
	if($eid == 0) exit();

	$e = email_load($mysqli, '', $eid);

	if($e['section'] != 'System') { 
		post_text($e['section'], 'section');
		post_text($e['name'], 'name');
	}
	post_text($e['description'], 'description');
	$email_key = $_POST['email_key'];
	if($email_key == 'specify_email') {
		post_text($e['from_name'], 'from_name');
		post_text($e['from_email'], 'from_email');
	} else if(!array_key_exists($email_key, $from_name) || !array_key_exists($email_key, $config) ) {
		$e['from_name'] = '';
		$e['from_email'] = '';
	} else {
		$e['from_name'] = $from_name[$email_key];
		$e['from_email'] = $config[$email_key];
	}
	post_text($e['subject'], 'subject');
	post_text($e['body'], 'body');

	email_save($mysqli, $e);
	form_ajax_response(array('status'=>0, 'location'=>'back'));
	exit();

case 'delete':
	$eid = (int)$_POST['eid'];
	if($eid == 0) exit();

	$e = email_load($mysqli, '', $eid);
	if($e['section'] == 'System') exit();

	$q = $mysqli->real_query("DELETE FROM emails WHERE id='$eid'");
	form_ajax_response(array('status'=>0, 'location'=>'back'));
	exit();

};


sfiab_page_begin($u, "Send Emails", $page_id, $help);

$emails = array();
$q = $mysqli->query("SELECT * FROM emails ORDER BY `section`,`name` ");
while($e = $q->fetch_assoc()) {
	$emails[] = $e;
}


?>
<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 
	<h3>Actions</h3>
	<ul data-role="listview" data-inset="true" >
		<li><a data-ajax="false" href="c_communication_queue.php">View email send queue and history</a></li>
		<li><a data-ajax="false" href="c_communication_queue.php?action=qstop">EMERGENCY pause the queue NOW</a></li>
	</ul>
	<h3>Send / Edit Emails</h3>
	<p>Click on an email below to send it.  You'll be shown the full email text and have to confirm who to send it to before it actually sends.  Click on the gear icon on the right of each email to edit or delete it.
	
<?php
	$form_id = $page_id.'_form';
?>
	<ul data-role="listview" data-inset="true" data-filter="true" data-filter-placeholder="Search by email name or description..." >
<?php	$last_section = '';
	foreach($emails as $e) { 
		$filter_text = $e['name'].' '.$e['description'];
		$s_str = '';
		if($last_section != $e['section']) { 
			$last_section = $e['section']; ?>
			<li data-role="list-divider"><?=$last_section?></li>
<?php		} ?>

		<li data-filtertext="<?=$filter_text?>">
			<a title="Send Email" href="c_communication_send.php?eid=<?=$e['id']?>" data-transition="right" data-ajax="false">
				<h3><?=$e['name']?><?=$s_str?></h3>
				<?=$e['description']?>
			</a>
			<a href="c_communication.php?action=edit&eid=<?=$e['id']?>" title="Edit Email" data-icon="gear" data-ajax="false">Edit</a>
		</li>
<?php	} ?>
	<a href="c_communication.php?action=new" data-role="button" data-inline="true" data-icon="plus"  data-theme="l">Create a New Email</a>
	

</div></div>


<?php

sfiab_page_end();
?>
