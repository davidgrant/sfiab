<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('project.inc.php');
require_once('isef.inc.php');
$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('student'));

$u = user_load($mysqli);

if($u['student_pid'] == NULL || $u['student_pid'] == 0) {
	print("Error 1010: no project.\n");
	exit();
}

$p = project_load($mysqli, $u['student_pid']);

$page_id = 's_project';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

$fields = array('title', 'cat_id', 'challenge_id', 'isef_id', 'language', 'req_electricity', 'summary');


switch($action) {
case 'save':
	foreach($fields as $f) {
		if(!array_key_exists($f, $p)) {
			/* Key doesn't exist, user is injecting their own keys? */
			print("Error 1008: $f");
			exit();
		}
		/* Since 'sex' is a radiobutton, it's only included if there's a checked value */
		if(array_key_exists($f, $_POST)) {
			/* Save it to the user */
			$p[$f] = $_POST[$f];
		} 
	}

	/* Filter special fields */
	$p['cat_id'] = (int)$p['cat_id'];
	$p['challenge_id'] = (int)$p['challenge_id'];
	$p['isef_id'] = (int)$p['isef_id'];
	$p['req_electricity'] = (int)$p['req_electricity'];

	project_save($mysqli, $p);

	$ret = incomplete_fields($mysqli, $page_id, $u, true);
	print(json_encode($ret));
	exit();
}

$help = '
<ul><li><b>Title</b> - Limited to 255 characters
<li><b>Category</b> - Eligible categories are based on the maximum grade of all partners in a project.  You may register in a category at or above your grade level (not below).
<li><b>Challenge</b> - Used exclusively for floor placement and general information.  It has no effect on judging or award distribution.
<li><b>Detailed Division</b> - Used to match qualified judges to the project, not to separate projects into distinct divisions.  All projects in the same age category are judged together now (regardless of division). See the student handbook for more information about these judging changes.
</ul>
';

sfiab_page_begin("Student Personal", $page_id, $help);

?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	$fields = incomplete_fields($mysqli, $page_id, $u, true);
	form_incomplete_error_message($page_id, $fields);
?>
	<p>
	Please enter the information about your project.  
	</p>
	<form action="#" id="<?=$page_id?>_form">
<?php
		$chals = challenges_load($mysqli);
		$cats = categories_load($mysqli);
		$legal_ids = project_get_legal_category_ids($mysqli, $p['pid']);
		$isef_data = isef_get_div_names();

		foreach($chals as $cid=>$c) {
			$chals_data[$cid] = $c['name'];
		}

		$cats_data = array();
		foreach($legal_ids as $cid) {
			$cats_data[$cid] = $cats[$cid]['name'];
		}

		/* Clear out invalid input so the placeholder is shown again */
		if($u['birthdate'] == '0000-00-00') $u['birthdate'] = '';

		form_text($page_id, 'title', "Title", $p);
		form_select($page_id, 'cat_id', "Category", $cats_data, $p);
		form_select($page_id, 'challenge_id', "Challenge", $chals_data, $p);
		form_select_optgroup($page_id, 'isef_id', "Detailed Division", $isef_data, $p);
		form_lang($page_id, 'language', "Judging Language", $p);
		form_yesno($page_id, 'req_electricity', "Electricity Needed", $p);
		form_textbox($page_id, 'summary', "Summary", $p);

		form_submit($page_id, 'Save');
?>
		<input type="hidden" name="action" value="save"/>
	</form>

	<?=form_scripts('student_project.php', $page_id, $fields);?>


</div>
	




<?php
sfiab_page_end();
?>
