<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('paypal.inc.php');
require_once('debug.inc.php');

$mysqli = sfiab_init('student');
$u = user_load($mysqli);


if(array_key_exists('checkout', $_POST)) {
	/* First entry, construct the fees to be sent */

	/* Load fees from $u */
	$fees = array();

	$p = project_load($mysqli, $u['s_pid']);
	/* Get all users associated with this project */
	$users = user_load_all_for_project($mysqli, $u['s_pid']);

	/* Load fees */
	$fee_data = compute_per_user_reg_fee($mysqli, $p, $users);

	debug("got post data: ".print_r($_POST, true));

	/* Get the users to pay for */
	$fees = array();
	foreach($users as &$user) {
		$uid = $user['uid'];
		if(in_array($uid, $_POST['payfor'])) {
			foreach($fee_data[$uid] as $index=>$d) {
				$f = array(	'uid' => $uid,
						'name' => $d['id'],
						'amt' => $d['base'],
						'qty' => $d['num'],
						'desc' => $d['text']);
				$fees[] = $f;
			}
		}
	}

	$res = paypal_set_express_checkout($fees);
	exit();
}

if(array_key_exists('cancel', $_GET)) {
	/* User cancelled payment */
	print("Cancelled");
	exit();
} 

/* The return from set_express_payment ends up here with a token and payer_id, now 
 * process the payment */
if(array_key_exists('token', $_GET) && array_key_exists('PayerID', $_GET)) {
	$token = $_GET['token'];
	$payer_id = $_GET['PayerID'];

	if($token != '' && $payer_id != '') {
		paypal_do_express_checkout($mysqli, $u, $token, $payer_id);
		header("Location: s_payment.php");
	}
	exit();
}


?>
