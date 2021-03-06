<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('form.inc.php');
require_once('incomplete.inc.php');

$mysqli = sfiab_init('student');

$u = user_load($mysqli);
$closed = sfiab_registration_is_closed($u);

$page_id = 's_reg_options';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

switch($action) {
case 'save':
	if($closed) exit();
	post_text($u['tshirt'], 'tshirt');
	if(!array_key_exists($u['tshirt'], $tshirt_sizes)) {
		$u['tshirt'] = NULL;
	}
	user_save($mysqli, $u);
	incomplete_check($mysqli, $ret, $u, $page_id, true);
	form_ajax_response(array('status'=>0, 'missing'=>$ret));
	exit();
}

$help='
<p>You can add a tshirt to your registration.  Tshirts are picked up at the fair during check-in
';

sfiab_page_begin($u, "Student Registration Options", $page_id, $help);
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 
<?php
	incomplete_check($mysqli, $fields, $u, $page_id);
	form_page_begin($page_id, $fields);
	form_disable_message($page_id, $closed, $u['s_accepted']);

	$options = 0;

	if($config['tshirt_enable']) { 
		$options += 1; ?>

		<h3>T-Shirt</h3>
<?php		if($config['tshirt_cost'] == 0) { ?>
			<p>T-Shirts are provided free of charge, sizes are Adult sizes. </p>
<?php		} else { ?>
			<p>The cost of each T-Shirt is $<?=$config['tshirt_cost']?>, sizes are Adult sizes. </p>
<?php		}
		$form_id = $page_id.'_form';
		form_begin($form_id, 's_reg_options.php', $closed);
		form_select($form_id, 'tshirt', 'T-Shirt', $tshirt_sizes, $u);
		form_submit($form_id, 'save', 'Save', 'Information Saved');
		form_end($form_id);
	}

	if($options == 0) {
?>		<p>No options to select.  This page is for options not applicable to your fair.  </p>
<?php	} ?>

</div></div>

<?php
sfiab_page_end();
?>
