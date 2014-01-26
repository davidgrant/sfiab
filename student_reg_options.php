<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('form.inc.php');
require_once('incomplete.inc.php');
$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('student'));

$u = user_load($mysqli);

$page_id = 's_reg_options';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

$tshirt_sizes = array('none' => 'None',
			'xsmall' => 'X-Small',
			'small' => 'Small',
			'medium' => 'Medium',
			'large' => 'Large',
			'xlarge' => 'X-Large' );


switch($action) {
case 'save':
	post_text($u['s_tshirt'], 's_tshirt');
	if(!array_key_exists($u['s_tshirt'], $tshirt_sizes)) {
		$u['s_tshirt'] = NULL;
	}
	user_save($mysqli, $u);
	$ret = incomplete_check($mysqli, $u, $page_id, true);
	form_ajax_response(array('status'=>0, 'missing'=>$ret));
	exit();
}

$help='
<p>You can add a tshirt to your registration.  Tshirts are picked up at the fair during check-in
';

sfiab_page_begin("Student Registration Options", $page_id, $help);
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 
<?php
	$fields = incomplete_check($mysqli, $u, $page_id);
	form_page_begin($page_id, $fields);

?>
	<h3>T-Shirt</h3>
	<p>The cost of each T-Shirt is $<?=$config['tshirt_cost']?>, sizes are Adult sizes.
<?php
	$form_id = $page_id.'_form';
	form_begin($form_id, 'student_reg_options.php');
	form_select($form_id, 's_tshirt', 'T-Shirt', $tshirt_sizes, $u);
	form_submit($form_id, 'save', 'Save', 'Information Saved');
	form_end($form_id);
?>
</div></div>

<?php
sfiab_page_end();
?>
