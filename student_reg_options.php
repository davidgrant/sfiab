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
	if(array_key_exists($_POST['tshirt'], $tshirt_sizes)) {
		$u['tshirt'] = $_POST['tshirt'];
	}
	user_save($mysqli, $u);
	$ret = incomplete_fields($mysqli, $page_id, $u, true);
	print(json_encode($ret));
	exit();
}

sfiab_page_begin("Student Registration Options", $page_id);
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 
<?php
	$fields = incomplete_fields($mysqli, $page_id, $u);
	form_incomplete_error_message($page_id, $fields); ?>

	<form action="#" id="s_reg_options_form">
<?php
		printf(" The cost of each T-Shirt is $%.2f, sizes are Adult sizes. ", $config['tshirt_cost']);
		form_select($page_id, 'tshirt', 'T-Shirt', $tshirt_sizes, $u);
		form_submit($page_id, 'Save');
?>
		<input type="hidden" name="action" value="save"/>
	</form>
	<?=form_scripts('student_reg_options.php', $page_id, $fields);?>
</div>

<?php
sfiab_page_end();
?>
