<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('project.inc.php');
$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('student'));

header("Cache-Control: no-cache");

$u = user_load($mysqli);

if($u['s_pid'] == NULL || $u['s_pid'] == 0) {
	print("Error 1010: no project.\n");
	exit();
}

$p = project_load($mysqli, $u['s_pid']);

$page_id = 's_mentor';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

switch($action) {
case 'save':
	/* Num mentors */

	post_bool($mentors, 'num_mentors');

	if($mentors === NULL) {
		form_ajax_response(array('status'=>0));
		exit();
	}

	if($mentors == false && $p['num_mentors'] > 0) {
		$mysqli->real_query("DELETE FROM mentors WHERE pid='{$p['pid']}'");
		$p['num_mentors'] = 0;
		project_save($mysqli, $p);
		form_ajax_response(array('status'=>0));
		/* javascript will delete all the html */
		exit();
	} else if($mentors == true && $p['num_mentors'] == 0) {
		/* Add one to startw ith */
		$mid = mentor_create($mysqli, $p['pid']);
		$p['num_mentors'] = 1;
		project_save($mysqli, $p);
		form_ajax_response(array('status'=>0, 'location'=>'student_mentor.php'));
		exit();
	}

	form_ajax_response(array('status'=>0));
	exit();

case 'savem':
	post_text($mid, 'id');
	$m = mentor_load($mysqli, $mid);

	if($m['pid'] != $p['pid']) {
		print("Error 1030: This project cannot load this mentor");
		exit();
	}
	post_text($m['firstname'], "firstname$mid");
	post_text($m['lastname'], "lastname$mid");
	post_text($m['email'], "email$mid");
	post_text($m['phone'], "phone$mid");
	post_text($m['organization'], "organization$mid");
	post_text($m['position'], "position$mid");
	post_text($m['desc'], "desc$mid");
	filter_phone($m['phone']);


	mentor_save($mysqli, $m);
	incomplete_check($mysqli, $fields, $u, $page_id, true);
	form_ajax_response(array('status'=>0, 'missing'=>$fields));
	exit();

case 'add':
	if($p['num_mentors'] < 10) {
		$mid = mentor_create($mysqli, $p['pid']);
		$p['num_mentors'] += 1;
		project_save($mysqli, $p);
		incomplete_check($mysqli, $fields, $u, $page_id, true);
		form_ajax_response(array('status'=>0, 'location'=>'student_mentor.php'));
	} else {
		form_ajax_response(array('status'=>0, 'error'=>'Limit of 10 mentors reached'));
	}
	exit();

case 'del':
	post_int($mid, 'mid');
	$mysqli->real_query("DELETE FROM mentors WHERE pid='{$p['pid']}' AND id='$mid'");
	$q = $mysqli->query("SELECT * FROM mentors WHERE pid='{$p['pid']}'");
	$p['num_mentors'] = $q->num_rows;
	project_save($mysqli, $p);

	incomplete_check($mysqli, $fields, $u, $page_id, true);

	form_ajax_response(array('status'=>0, 'missing'=>$fields));
	exit();
}

$help = '
<p>Please tell us about any mentors who assisted you in your project.

<p>Example of "mentorship" interactions.  These are just examples, not complete lists:
<ul><li>A research assistant who showed you how to use a pipette, a microscope, or how to stain slides
<li>A university professor who provided lab space for your project (even if they didn\'t interact with you at all).
<li>A teacher who provided guidance in developing the procedure
<li>A parent who taught you about relevant background materials necessary for your project.
</ul>

<p>Examples of interactions that are not considered mentorship:
<ul><li>A parent who drives you to Home Depot to buy materials to build an apparatus for your project that you designed yourself.  (Note: if your parents helped design the apparatus, that would be considered mentorship).
<li>A teacher who gave you the project idea and only answered a few small questions over the course of the project
<li>A university professor who forwarded papers to you which you requested.
</ul>

<p>Other Info
<ul><li><b>Organization</b> - The company where your mentor works
<li><b>Position</b> - The position your mentor has at the company/organization.  For exmaple: Researcher, Primary Investigator.
<li><b>Description</b> - Briefly describe how your mentor assisted you.  For exmaple, did they give you ideas, suggest experiments, show you how to use lab equipment?
</ul>
';


sfiab_page_begin("Student Personal", $page_id, $help);

$num_mentors = $p['num_mentors'];
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	incomplete_check($mysqli, $fields, $u, $page_id);	
	form_page_begin($page_id, $fields);
?>
	<h3>Project Mentorship Information</h3>

	A project mentor is someone who has given considerable assistance to a project.  See the help panel on the right (click on the info icon at the top right) for examples of
	what would be considered mentorship.
<?php
	if($p['num_mentors'] === NULL) {
		$mentors = NULL;
	} else if($p['num_mentors'] == 0) {
		$mentors = 0;
	} else {
		$mentors = 1;
	}
	$form_id = $page_id.'_form';
	form_begin($form_id, 'student_mentor.php');
	form_yesno($form_id, 'num_mentors', "Were you assisted by mentors in doing this project?", $mentors, true);
	form_submit($form_id, 'save', 'Save', 'Mentor Info Saved');
	form_end($form_id);

	$mentors = mentor_load_all($mysqli, $p['pid']);
	$x = 0;
	foreach($mentors as $mid => $u) {
		$x++;
		$form_id = $page_id.'_form_'.$mid;
		print("<div id=\"mentor_div_$mid\"><h3>Mentor $x</h3>");
		form_begin($form_id, 'student_mentor.php');
		form_text($form_id, "firstname$mid", "First Name", $u['firstname']);
		form_text($form_id, "lastname$mid", "Last Name", $u['lastname']);
		form_text($form_id, "email$mid", "Email", $u['email'], 'email');
		form_text($form_id, "phone$mid", "Phone", $u['phone'], 'tel');
		form_text($form_id, "organization$mid", 'Organization', $u['organization']);
		form_text($form_id, "position$mid", 'Position', $u['position']);
		form_hidden($form_id, 'id', $mid);
		form_textbox($form_id, "desc$mid", 'Description of Help', $u['desc']);
?>
		<div class="ui-grid-a">
			<div class="ui-block-a"> 
			<?=form_submit($form_id, 'savem', 'Save Mentor', 'Mentor Info Saved');?>
			</div>
			<div class="ui-block-b"> 
			<a href="#" onclick="return mentor_delete(<?=$mid?>);" data-role="button" data-icon="delete" data-theme="r">Delete Mentor</a>
			</div>
		</div>
		<?=form_end($form_id);?>
		</div>
<?php
	}

	$form_id = $page_id.'_add_form';
	form_begin($form_id, 'student_mentor.php');
	form_button($form_id, 'add', 'Add A Mentor', 'Add A Mentor');
	form_end($form_id);
?>

</div></div>
	
<script>
function mentor_delete(id) {
	if(confirm('Really delete this mentor?') == false) return false;
	$.post('student_mentor.php', { action: "del", mid: id }, function(data) {
		if(data.status == 0) {
			$("#mentor_div_"+id).hide();
		}
	}, "json");
	return false;
}

</script>

<?php
sfiab_page_end();
?>
