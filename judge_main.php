<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('form.inc.php');
$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('judge'));

$page_id = "s_home";

$u = user_load($mysqli);

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

switch($action) {
case 'save':

	$attending = NULL;
	post_bool($attending, 'j_attending');
	if($attending !== NULL) {
		if($attending == 0) {
			$u['j_status'] = 'notattending';
		} else {
			$u['j_status'] = 'inprogress';
		}
	}
	user_save($mysqli, $u);

	$ret = incomplete_fields($mysqli, $page_id, $u, true);
	print(json_encode($ret));
	exit();
}


$help = '
<ul><li><b>Attending the Fair</b> - If you are unable to attend the fair,
indicate that here and we will remove you will not be assigned to any judging
team, or we will remove you from any teams you have been assigned to.  
</ul>';

sfiab_page_begin("Judge Main", 'j_home', $help);
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

	<h3>Hello <?=$u['firstname']?></h3>

	<h3>Judging Team and Schedule</h3>
	Judging teams have not been assigned yet.  Look for this information after March 31, 2014.

	<hr/>
	<h3>Cancelling</h3>
	If your plans change and you're unable to judge this year, just flip
	the switch below to let us know.  You can always flip the switch back again.
	<?php
		$page_id = 'j_main';

	if($u['j_status'] == 'notattending') {
		$attending = 0;
		sfiab_error("You have indicated that you're not attending the
		fair this year, thanks for letting us know.  You won't be
		assigned to judge anything and you don't need to fill out
		anything else.  The information will be here next year if you
		are able to judge again.  If your plans change for this year,
		just indicate below that you are attending the fair, and finish
		the registration process.  Thank you.");
	} else {
		$attending = 1;
	}

		
	?> 	<form action="#" id="<?=$page_id?>_form">
	<?php
		form_yesno($page_id, 'j_attending', "Are you judging at the fair?", $attending, true);
		form_submit($page_id, 'Save');
?>
		<input type="hidden" name="action" value="save"/>
		</form>
		<?=form_scripts('judge_main.php', $page_id, array());?>

</div></div>

<?php
sfiab_page_end();
?>
