<?php
require_once('../common.inc.php');
require_once('../form.inc.php');
require_once('../user.inc.php');
require_once('../project.inc.php');
require_once('../filter.inc.php');
require_once('judges.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('committee'));

$u = user_load($mysqli);

$page_id = 'c_judging';

//sfiab_page_begin("Judging", $page_id);

$saved = false;
if(array_key_exists('action', $_POST)) {

	if($_POST['action'] == 'save') {
		form_ajax_response_error(0, 'save response');
		exit();
	}

	if($_POST['action'] == 'save2') {
		$saved = true;
	}

}

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	/* Count judges */
	$judges = judges_load_all($mysqli, $config['year']);

	$j_complete = 0;
	$j_not_attending = 0;
	$j_incomplete = 0;
	foreach($judges as &$j) {
		if($j['not_attending']) {
			$j_not_attending++;
		} else {
			if($j['j_complete']) 
				$j_complete++;
			else
				$j_incomplete++;
		}
	}

	print(time(NULL));

	print("Saved: $saved");

?>	<h3>Judges</h3> 
	<p>Count: <?=$j_complete?> / <?=$j_complete+$j_incomplete?> complete, plus <?=$j_not_attending?> more not attending.

<?php
        $form_id = 'j_attending_form';
	form_page_begin($page_id, array());
        form_begin($form_id, 'c_judging.php');
        form_text($form_id, 'j_not_attending', "Judging at the fair", $u['not_attending']);
        form_submit($form_id, 'save', 'Save', 'Information Saved');
        form_end($form_id);
	
?>

	<form method="post" action="c_judging.php" data-ajax="false" data-rel="external">
	<input type="hidden" value="save2" name="action" />
	<input type="submit" value="go" />
	</form>




</div></div>
	

<?php
//sfiab_page_end();
?>
