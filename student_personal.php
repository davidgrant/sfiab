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

$fields = array('firstname', 'lastname', 'pronounce', 'sex', 'birthdate', 'address', 'address2',
	'city', 'province', 'postalcode', 'schools_id', 'grade', 'teacher', 'teacheremail',
	'medicalert');


switch($action) {
case 'save':
	foreach($fields as $f) {
		if(!array_key_exists($f, $u)) {
			/* Key doesn't exist, user is injecting their own keys? */
			print("Error 1000: $f");
			exit();
		}
		/* Since 'sex' is a radiobutton, it's only included if there's a checked value */
		if(array_key_exists($f, $_POST)) {
			/* Save it to the user */
			$u[$f] = $_POST[$f];
		} 
	}


	/* Filter special fields */
	$u['grade'] = (int)$u['grade'];
	$u['schools_id'] = (int)$u['schools_id'];

	user_save($mysqli, $u);

	$ret = incomplete_fields($mysqli, $page_id, $u, true);
	print(json_encode($ret));
	exit();
}



sfiab_page_begin("Student Personal", $page_id);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	$fields = incomplete_fields($mysqli, $page_id, $u);
	form_incomplete_error_message($page_id, $fields);
?>
	<form action="#" id="<?=$page_id?>_form">
<?php
		$q=$mysqli->query("SELECT id,school,city FROM schools WHERE year='{$config['year']}' ORDER by city,school");
		while($r=$q->fetch_assoc()) {
			$schools[$r['id']] = "{$r['city']} - {$r['school']}";
		}

		$grades = array();
		for($x=7;$x<=12;$x++) {
			$grades[$x] = $x;
		}

		/* Clear out invalid input so the placeholder is shown again */
		if($u['birthdate'] == '0000-00-00') $u['birthdate'] = '';

		form_text($page_id, 'firstname', "First Name", $u);
		form_text($page_id, 'lastname', "Last Name", $u);
		form_text($page_id, 'pronounce', "Name Pronunciation Key", $u);
		form_radio_h($page_id, 'sex', 'Gender', array( 'male' => 'Male', 'female' => 'Female'), $u);
		form_text($page_id, 'birthdate', "Date of Birth", $u, 'date');
		form_text($page_id, 'phone1', "Phone", $u, 'tel');
		form_text($page_id, 'address', 'Address 1', $u);
		form_text($page_id, 'address2', 'Address 2', $u);
		form_text($page_id, 'city', 'City', $u);
		form_province($page_id, 'province', 'Province', $u);
		form_text($page_id, 'postalcode', 'Postal Code', $u);
		form_select($page_id, 'schools_id','School', $schools, $u);
		form_select($page_id, 'grade', 'Grade', $grades, $u);
		form_text($page_id, 's_teacher', 'Teacher Name', $u);
		form_text($page_id, 's_teacheremail', 'Teacher E-Mail', $u);
		form_text($page_id, 'medicalert', 'Medical Alert Info', $u);
		form_submit($page_id, 'Save');
?>
		<input type="hidden" name="action" value="save"/>
	</form>

	<?=form_scripts('student_personal.php', $page_id, $fields);?>


</div>
	




<?php
sfiab_page_end();
?>
