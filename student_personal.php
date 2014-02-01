<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('student'));

$u = user_load($mysqli);

$page_id = 's_personal';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

switch($action) {
case 'save':
	$old_grade = $u['grade'];
	post_text($u['firstname'],'firstname');
	post_text($u['lastname'],'lastname');
	post_text($u['pronounce'],'pronounce');
	post_text($u['sex'],'sex');
	post_text($u['birthdate'],'birthdate');
	post_text($u['phone1'],'phone1');
	post_text($u['address'],'address');
	post_text($u['address2'],'address2');
	post_text($u['city'],'city');
	post_text($u['province'],'province');
	post_text($u['postalcode'],'postalcode');
	post_int($u['schools_id'],'schools_id');
	post_int($u['grade'],'grade');
	post_text($u['s_teacher'],'s_teacher');
	post_text($u['s_teacheremail'],'s_teacheremail');
	post_text($u['medicalert'],'medicalert');

	if($old_grade !== $u['grade']) {
		$u['tour_id_pref'][0] = NULL;
		$u['tour_id_pref'][2] = NULL;
		$u['tour_id_pref'][1] = NULL;
	}

	/* Scrub data */
	$update = array();

	filter_phone($u['phone1']);
	$update['phone1'] = $u['phone1'];

	if($u['birthdate'] !== NULL) {
		$d = date_parse($u['birthdate']);
		if($d['year'] > 1900 && $d['month'] > 0 && $d['day'] > 0) {
			$u['birthdate'] = sprintf("%04d-%02d-%02d", $d['year'], $d['month'], $d['day']);
			$update['birthdate'] = $u['birthdate'];
		} else {
			$update['birthdate'] = '';
			$u['birthdate'] = NULL;
		}
	}

	user_save($mysqli, $u);

	incomplete_check($mysqli, $ret, $u, $page_id, true);
	form_ajax_response(array('status'=>0, 'missing'=>$ret, 'val'=>$update));
	exit();
}

$help = '
<ul>
<li><b>Name Pronounciation Key</b> - If your name is often mis-pronounced, describe how to properly pronounce it.
<li><b>Birthdate</b> - In the format: YYYY-MM-DD.  It\'ll take most values and attempt to convert them.
<li><b>School</b> - If your school doesn\'t appear in the list, please contact the committee to have it added.
</ul>';

sfiab_page_begin("Student Personal", $page_id, $help);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	$form_id = $page_id.'_form';
	incomplete_check($mysqli, $fields, $u, $page_id);
	form_page_begin($page_id, $fields);

	$q=$mysqli->query("SELECT id,school,city FROM schools WHERE year='{$config['year']}' ORDER by city,school");
	while($r=$q->fetch_assoc()) {
		$schools[$r['id']] = "{$r['city']} - {$r['school']}";
	}

	$grades = array();
	for($x=7;$x<=12;$x++) $grades[$x] = $x;

	form_begin($form_id, 'student_personal.php');
	form_text($form_id, 'firstname', "First Name", $u);
	form_text($form_id, 'lastname', "Last Name", $u);
	form_text($form_id, 'pronounce', "Name Pronunciation Key", $u);
	form_radio_h($form_id, 'sex', 'Gender', array( 'male' => 'Male', 'female' => 'Female'), $u);
	form_text($form_id, 'birthdate', "Date of Birth", $u, 'date');
	form_text($form_id, 'phone1', "Phone", $u, 'tel');
	form_text($form_id, 'address', 'Address 1', $u);
	form_text($form_id, 'address2', 'Address 2', $u);
	form_text($form_id, 'city', 'City', $u);
	form_province($form_id, 'province', 'Province / Territory', $u);
	form_text($form_id, 'postalcode', 'Postal Code', $u);
	form_select($form_id, 'schools_id','School', $schools, $u);
	form_select($form_id, 'grade', 'Grade', $grades, $u);
	form_text($form_id, 's_teacher', 'Teacher Name', $u);
	form_text($form_id, 's_teacheremail', 'Teacher E-Mail', $u);
	form_text($form_id, 'medicalert', 'Medical Alert Info', $u);
	form_submit($form_id, 'save', 'Save', 'Information Saved');
	form_end($form_id);

?>
</div></div>

<?php
sfiab_page_end();
?>
