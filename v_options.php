<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('form.inc.php');
require_once('incomplete.inc.php');

$mysqli = sfiab_init('volunteer');

$u = user_load($mysqli);
$closed = sfiab_registration_is_closed($u);

$page_id = 'v_options';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

$tshirt_sizes = array(//'none' => 'None',
			'xsmall' => 'X-Small',
			'small' => 'Small',
			'medium' => 'Medium',
			'large' => 'Large',
			'xlarge' => 'X-Large' );


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
<p>Some volunteer positions are provided with a tshirt.
';

sfiab_page_begin("Volunteer Registration Options", $page_id, $help);
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 
<?php
	incomplete_check($mysqli, $fields, $u, $page_id);
	form_page_begin($page_id, $fields);
	form_disable_message($page_id, $closed);

?>
	<h3>T-Shirt</h3>
	<p>A T-Shirt is provided for some volunteer positions (like tour guides).  Sizes are Adult sizes.
<?php
	$form_id = $page_id.'_form';
	form_begin($form_id, 'v_options.php');
	form_select($form_id, 'tshirt', 'T-Shirt', $tshirt_sizes, $u);
	form_submit($form_id, 'save', 'Save', 'Information Saved');
	form_end($form_id);
?>
</div></div>

<?php
sfiab_page_end();
?>
