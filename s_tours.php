<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('form.inc.php');
require_once('incomplete.inc.php');

$mysqli = sfiab_init('student');

$u = user_load($mysqli);
$closed = sfiab_registration_is_closed($u);

$page_id = 's_tours';

sfiab_check_abort_in_preregistration($u, $page_id);

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

switch($action) {
case 'save':
	if($closed) exit();
	post_int($u['tour_id_pref'][0], 'tour0');
	post_int($u['tour_id_pref'][1], 'tour1');
	post_int($u['tour_id_pref'][2], 'tour2');

	if($u['tour_id_pref'][1] == $u['tour_id_pref'][0]) {
		$u['tour_id_pref'][1] = NULL;
	}
	if($u['tour_id_pref'][2] == $u['tour_id_pref'][1] || $u['tour_id_pref'][2] == $u['tour_id_pref'][0] ) {
		$u['tour_id_pref'][2] = NULL;
	}

	user_save($mysqli, $u);
	incomplete_check($mysqli, $ret, $u, $page_id, true);
	form_ajax_response(array('status'=>0, 'missing'=>$ret));
	exit();
}

$help='
<p>Select your top three tour choices.  Most students will get their
	first or second choice.  We can\'t guarantee that everyone will, but
	we\'ll do our best.  A description of each tour is at the bottom of the
	page.
';

sfiab_page_begin($u, "Student Tour Selection", $page_id, $help);
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 
<?php
	incomplete_check($mysqli, $fields, $u, $page_id);
	form_page_begin($page_id, $fields);
	form_disable_message($page_id, $closed, $u['s_accepted']);

?>
	<h3>Tour Selection</h3>

<?php	if($u['grade'] === NULL || $u['grade'] == 0) { ?>
		<p>Please enter your grade on the <a href="s_personal.php" data-ajax="false">Personal Info</a> page.  Some tours are only applicable to certain grades.
		</div></div>
	
<?php		sfiab_page_end();
	}
?>


	<p>Select your top three tour choices.  Most students will get their
	first or second choice.  We can't guarantee that everyone will, but
	we'll do our best.  A description of each tour is at the bottom of the
	page.

<?php
	$tours = tour_get_for_student_select($mysqli, $u);

	if (count($tours) == 0) { ?>
		<p>There are no tours available.</p>
<?php 	} else {
		$form_id = $page_id.'_form';
		form_begin($form_id, 's_tours.php', $closed);
		form_select($form_id, 'tour0', 'First Choice', $tours, $u['tour_id_pref'][0]);
		form_select($form_id, 'tour1', 'Second Choice', $tours, $u['tour_id_pref'][1]);
		form_select($form_id, 'tour2', 'Third Choice', $tours, $u['tour_id_pref'][2]);
		form_submit($form_id, 'save', 'Save', 'Information Saved');
		form_end($form_id);
	}

?>
	<h3>Tour Descriptions</h3>
	<ul data-role="listview" data-inset="true" >
<?php
	foreach($tours as $t) {
?>
		<li style="white-space: normal"><b><?=$t['name']?></b><br/>
			<?=$t['description']?>
		</li>
<?php
	}
?>
	</ul>
</div></div>

<?php
sfiab_page_end();
?>
