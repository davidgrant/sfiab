<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('form.inc.php');
require_once('incomplete.inc.php');
$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('volunteer'));

$u = user_load($mysqli);

$page_id = 'v_tours';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

switch($action) {
case 'save':
	$u['v_tour_username'] = NULL;
	if($u['v_relation'] == 'parent') {
		post_bool($u['v_tour_match_username'], 'v_tour_match_username');
	} else {
		$u['v_tour_match_username'] = NULL;
	}

	if($u['v_tour_match_username'] == 1) {
		post_text($u['v_tour_username'], 'v_tour_username');
	} else {
		post_int($u['tour_id_pref'][0], 'tour0');
		post_int($u['tour_id_pref'][1], 'tour1');
		post_int($u['tour_id_pref'][2], 'tour2');

		if($u['tour_id_pref'][1] == $u['tour_id_pref'][0]) {
			$u['tour_id_pref'][1] = NULL;
		}
		if($u['tour_id_pref'][2] == $u['tour_id_pref'][1] || $u['tour_id_pref'][2] == $u['tour_id_pref'][0] ) {
			$u['tour_id_pref'][2] = NULL;
		}
	}

	user_save($mysqli, $u);
	incomplete_check($mysqli, $ret, $u, $page_id, true);
	form_ajax_response(array('status'=>0, 'missing'=>$ret));
	exit();
}

$help='
<p>Select your top three tour choices.  Most volunteers will get matched
	to their first choice.  We can\'t guarantee that you will be matched
	to your first choice, but we\'ll do our best.  A description of each tour is at the bottom of the
	page.

	Note: If you indicate that you\'re the parent/guardian of a student at the fair on the preferences page,
	then you will be given the option to be matched to your student on this page.
';

sfiab_page_begin("Volunteer Tour Selection", $page_id, $help);
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 
<?php
	incomplete_check($mysqli, $fields, $u, $page_id);
	form_page_begin($page_id, $fields);

?>
	<h3>Tour Selection</h3>

	<p>Select your top three tour choices.  Most volunteers will get their
	first choice, but we can't guarantee that.  A description of each tour
	is at the bottom of the page.

	<p>If you are the parent/guardian of a student and wish to be on the same
	tour as your student, indicate that you are the parent/guardian of a 
	student at the fair on the Volunteer Personal Info page, then you will
	be given the option below to enter your student's username.

<?php
	$m_only = ($u['v_tour_match_username'] == 1) ? 1 : 0;
	$hidden = "style=\"display:none\"";

	$tours = tour_load_all($mysqli);
	
	$form_id = $page_id.'_form';
	form_begin($form_id, 'v_tours.php');

	if($u['v_relation'] == 'parent') {
		form_yesno($form_id, 'v_tour_match_username', "Since you are the parent/guardian/relative of a student at the fair, do you want to be matched to the same your as your student?", $u, true, true);
	}
?>
	<div id="v_tour_match_username_div" <?=$m_only ? '' : $hidden?> >
		<p>Please enter the username of your student below.  You will be matched to whichever tour he/she is assigned to after tour assignments are made.

<?php		form_text($form_id, 'v_tour_username', 'Username', $u['v_tour_username']); ?>
	</div>

	<div id="v_tour_normal" <?=$m_only ? $hidden : ''?> >
<?php
		form_select($form_id, 'tour0', 'First Choice', $tours, $u['tour_id_pref'][0]);
		form_select($form_id, 'tour1', 'Second Choice', $tours, $u['tour_id_pref'][1]);
		form_select($form_id, 'tour2', 'Third Choice', $tours, $u['tour_id_pref'][2]);
?>
	</div>

<?php
	form_submit($form_id, 'save', 'Save', 'Information Saved');
	form_end($form_id);
?>
	<script>
		$( "#<?=$form_id?>_v_tour_match_username" ).change(function() {
			var m = $("#<?=$form_id?>_v_tour_match_username option:selected").val();
			if(m == '0') {
				$('#v_tour_match_username_div').hide();
				$('#v_tour_normal').show();
			} else {
				$('#v_tour_match_username_div').show();
				$('#v_tour_normal').hide();
			}
		});
	</script>



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
