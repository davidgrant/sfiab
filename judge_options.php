<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('filter.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('judge'));

$u = user_load($mysqli);
$closed = sfiab_registration_is_closed($u);

$page_id = 'j_options';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

$langs = array('en' => 'English', 'fr' => 'French' );

switch($action) {
case 'save':
	if($closed) exit();
	post_bool($u['j_willing_lead'], 'j_willing_lead');
	post_bool($u['j_dinner'], 'j_dinner');
	post_bool($u['j_rounds'][0], 'j_round0');
	post_bool($u['j_rounds'][1], 'j_round1');
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

$help = '
<ul>
<li><b>Team-Lead</b> - A team lead is responsible for communicating the final decision of the judging team to the chief judge.  
<li><b>Dinner</b> - There is a judge\'s dinner from 5 - 6pm for all judges.  This helps us guage the amount of food to purchse.
<li><b>Round 1/2</b> - When are you available to judge?  You will be assigned to judge in ALL rounds you answer \'Yes\' to.
</ul>';



sfiab_page_begin("Options", $page_id, $help);

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
	form_begin($form_id,'judge_options.php', $closed);
?>	
	<h3>Judging Options</h3> 
<?php	form_yesno($form_id, 'j_willing_lead', "Are you willing to be the team-lead on your judging team?", $u, true);
	form_yesno($form_id, 'j_dinner', "Will you be attending the Judge's Dinner (5-6pm on judging day)?", $u, true);
?>	
	<h3>Judging Languages</h3>
	
<?php	
	form_check_group($form_id, 'j_languages', "In what language(s) can you judge projects?", $langs, $u['j_languages'], true);

?>
	<h3>Time Availability</h3>
	Note: You will be scheduled to judge in ALL of the judging rounds you answer 'Yes' to, not just one.
<?php	form_yesno($form_id, 'j_round0', "Are you available to judge in Round 1 (April 10, 2-5pm)?", $u['j_rounds'][0], true);
	form_yesno($form_id, 'j_round1', "Are you available to judge in Round 2 (April 10, 6-9pm)?", $u['j_rounds'][1], true);
	form_submit($form_id, 'save','Save','Information Saved');
	form_end($form_id);
?>
</div></div>
	




<?php
sfiab_page_end();
?>
