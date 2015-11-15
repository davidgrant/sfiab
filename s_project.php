<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('project.inc.php');
require_once('isef.inc.php');

$mysqli = sfiab_init('student');

$u = user_load($mysqli);
$closed = sfiab_registration_is_closed($u);

if($u['s_pid'] == NULL || $u['s_pid'] == 0) {
	print("Error 1010: no project.\n");
	exit();
}

$p = project_load($mysqli, $u['s_pid']);

$page_id = 's_project';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}


switch($action) {
case 'save':
	if($closed) exit();
	post_text($p['title'], 'title');
	post_text($p['language'], 'language');
	post_text($p['tagline'], 'tagline');
	post_text($p['abstract'], 'abstract');
	post_int($p['challenge_id'], 'challenge_id');
	post_int($p['isef_id'], 'isef_id');
	post_bool($p['req_electricity'], 'req_electricity');

	$p['tagline'] = trim($p['tagline']);
	$p['abstract'] = trim($p['abstract']);
	project_save($mysqli, $p);

	incomplete_check($mysqli, $ret, $u, $page_id, true);
	$response = array('status'=>0, 'missing'=>$ret);
	if(count($incomplete_errors) > 0) 
		$response['error'] = join('<br/>', $incomplete_errors);

	form_ajax_response($response);
	exit();
}

$help = "
<ul><li><b>Title</b> - Limited to 255 characters
<li><b>Category</b> - The age category is chosen automatically from the maximum grade of all partners in a project.   You may register in a category at or above your grade level (not below).  If you wish to register in a different category, please contact the committee.
<li><b>Challenge</b> - Used exclusively for floor placement and general information.  It has no effect on judging or award distribution.
<li><b>Detailed Division</b> - Used to match qualified judges to your project.  We do not separate projects into distinct divisions any more, all projects in the same age category are judged together now (regardless of division). See the student handbook for more information about these judging changes.
<li><b>One-Sentence Summary</b> - One-setence summary for giving to the media.  Try to avoid scientific terms the general public may not be familiar with.  Between {$config['s_tagline_min_words']} and {$config['s_tagline_max_words']} words.
<li><b>Abstract</b> - Detailed abstract.  Between {$config['s_abstract_min_words']} and {$config['s_abstract_max_words']} words.
</ul>
";

sfiab_page_begin($u, "Project Information", $page_id, $help);

?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	incomplete_check($mysqli, $fields, $u, $page_id, true);
	$e = join('<br/>', $incomplete_errors);
	form_page_begin($page_id, $fields, $e);
	form_disable_message($page_id, $closed, $u['s_accepted']);

	$chals = challenges_load($mysqli);
	$cats = categories_load($mysqli);
	$legal_ids = project_get_legal_category_ids($mysqli, $p['pid']);
	$isef_data = isef_get_div_names();

	foreach($chals as $cid=>$c) {
		$chals_data[$cid] = $c['name'];
	}

	if(array_key_exists($p['cat_id'], $cats)) {
		$cat_name = $cats[$p['cat_id']]['name'];
	} else {
		$cat_name = "Your age category is automatically selected when you select your grade";
	}

?>
	<h3>Project Information</h3>
<?php	
	$form_id = $page_id.'_form';
	form_begin($form_id, 's_project.php', $closed);
	form_text($form_id, 'title', "Title", $p);
	form_select($form_id, 'challenge_id', "Challenge", $chals_data, $p);
	form_label($form_id, 'cat_id', 'Age Category', $cat_name);
	form_select_optgroup($form_id, 'isef_id', "Detailed Division", $isef_data, $p);
	form_lang($form_id, 'language', "Judging Language", $p);
	form_yesno($form_id, 'req_electricity', "Electricity Needed", $p);
	form_textbox($form_id, 'tagline', "One-Sentence Summary", $p,
			$config['s_tagline_min_words'], $config['s_tagline_max_words']);
	form_textbox($form_id, 'abstract', "Abstract", $p,
			$config['s_abstract_min_words'], $config['s_abstract_max_words']);
	form_submit($form_id, 'save', 'Save', 'Project Saved');
	form_end($form_id);
?>

</div></div>
	

<?php
sfiab_page_end();
?>
