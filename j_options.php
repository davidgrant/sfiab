<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('filter.inc.php');
require_once('timeslots.inc.php');

$mysqli = sfiab_init('judge');

$u = user_load($mysqli);
$closed = sfiab_registration_is_closed($u);

$page_id = 'j_options';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

$langs = array('en' => 'English', 'fr' => 'French' );

$rounds = timeslots_load_rounds($mysqli);
$num_rounds = count($rounds);

switch($action) {
case 'save':
	if($closed) exit();
	post_bool($u['j_willing_lead'], 'j_willing_lead');

	if($config['judge_ask_dinner']) {
		post_bool($u['j_dinner'], 'j_dinner');
	} else {
		$u['j_dinner'] = 0;
	}

	$u['j_rounds'] = array();
	for($r = 0; $r<$num_rounds; $r++) {
		post_int($u['j_rounds'][$r], array('j_rounds', $r) );
	}
	
	$u['j_languages'] = NULL;
	post_array($u['j_languages'], 'j_languages', $langs);
	user_save($mysqli, $u);

	incomplete_check($mysqli, $ret, $u, $page_id, true);
	$e = '';
	if(count($incomplete_errors) > 0) {
		$e = join('<br/>', $incomplete_errors);
	}
	form_ajax_response(array('status'=>0, 'missing'=>$ret, 'error'=>$e));
	exit();
}


$dinner_time = '';
$dinner_help = '';

if($config['judge_ask_dinner'] == 1) {
	if($num_rounds >= 2) {
		$dinner_start = date('g:ia', $rounds[0]['end_timestamp']);
		$dinner_end = date('g:ia', $rounds[1]['start_timestamp']);
		$dinner_time = " from $dinner_start - $dinner_end on judging day";
	}
	$dinner_help = "<li><b>Dinner</b> - There is a judge's dinner$dinner_time for all judges.  This helps us guage the amount of food to purchse.";
}

$help = "
<ul>
<li><b>Team-Lead</b> - A team lead is responsible for communicating the final decision of the judging team to the chief judge. 
$dinner_help
<li><b>Round 1/2</b> - When are you available to judge?  You will be assigned to judge in ALL rounds you answer \'Yes\' to.
</ul>";



sfiab_page_begin($u, "Options", $page_id, $help);

?>


<div data-role="page" id="<?=$page_id?>"  ><div data-role="main" class="sfiab_page" > 

<?php
	incomplete_check($mysqli, $fields, $u, $page_id);
	$e = '';
	if(count($incomplete_errors) > 0) {
		$e = join('<br/>', $incomplete_errors);
	}
	form_page_begin($page_id, $fields, $e);
	form_disable_message($page_id, $closed);

	$form_id=$page_id.'_form';
	form_begin($form_id,'j_options.php', $closed);
?>	
	<h3>Judging Options</h3> 
<?php	form_yesno($form_id, 'j_willing_lead', "Are you willing to be the team-lead on your judging team?", $u, true);
	if($config['judge_ask_dinner']) {
		form_yesno($form_id, 'j_dinner', "Will you be attending the Judge's Dinner$dinner_time?", $u, true);
	}
?>	
	<h3>Judging Languages</h3>
	
<?php	
	form_check_group($form_id, 'j_languages', "In what language(s) can you judge projects?", $langs, $u['j_languages'], true);

?>
	<h3>Time Availability</h3>
	Note: You will be scheduled to judge in <b>ALL</b> of the judging rounds you answer 'Yes' to, not just one.
	
<?php	for($r=0; $r<$num_rounds; $r++) {
		$ts = &$rounds[$r];
		$date_str = date("F j, g:ia", $ts['start_timestamp']);
		$date_str .= ' - '.date("g:ia", $ts['end_timestamp']);

		$data_values = array(-1 => 'No', $r => 'Yes');

		$v = array_key_exists($r, $u['j_rounds']) ?  $u['j_rounds'][$r] : '';
		form_radio_h($form_id, "j_rounds[$r]", "Are you available to judge in {$ts['name']} ($date_str)?", $data_values, $u['j_rounds'][$r], true);
	}
	form_submit($form_id, 'save','Save','Information Saved');
	form_end($form_id);
?>
</div></div>
	




<?php
sfiab_page_end();
?>
