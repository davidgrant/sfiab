<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('paypal.inc.php');
require_once('debug.inc.php');

$mysqli = sfiab_init('student');
$u = user_load($mysqli);

$action = substr($_SERVER['PATH_INFO'], 1);

debug("Called with post: ".print_r($_POST, true));
debug("Called with get: ".print_r($_GET, true));
debug("REST action: $action\n");

/* Calling //paypal.php/checkout  gives:
	_SERVER['PATH_INFO'] = /checkout
	*/


if($action == 'checkout') {
/* First entry, construct the fees to be sent */
//	if(!array_key_exists('payfor', $_POST)) {
		/* User hit pay without selecting any boxes */
//		exit();
//	}


	/* The POST includes serialized form data with a payfor array: action=&payfor%5B%5D=5869
	 * turn that into an actual array */
	 /* v4.0 checkout, which is broken */
	parse_str($_POST['data'], $data);
	$payfor_uids = $data['payfor'];

	/* v3.5 checkout */
//	$payfor_uids = $_POST['payfor'];

	/* The invoice number is a hash of the users to pay for and the year */
	$invoice = hash('sha224', $config['year'].json_encode($payfor_uids).time(NULL));

	/* Get the experinece id */
	$experience_id = paypal_get_or_create_experience_profile();

	/* Load fees from $u */
	$fees = array();

	$p = project_load($mysqli, $u['s_pid']);
	/* Get all users associated with this project */
	$users = user_load_all_for_project($mysqli, $u['s_pid']);

	/* Load fees */
	$fee_data = compute_per_user_reg_fee($mysqli, $p, $users);
	debug("Computed fees: ".print_r($fee_data, true));

	$payment = array();
	$payment['intent'] = 'sale';
	$payment['experience_profile_id'] = $experience_id;
	$payment['redirect_urls'] = array('return_url' => $config['fair_url'].'/paypal.php/return',
					'cancel_url' => $config['fair_url'].'/paypal.php/cancel');
	$payment['payer'] = array('payment_method' => "paypal");
	$payment['transactions'] = array();

	/* Get the users to pay for */
	$transaction = array('amount' => array(),
			     'item_list' => array('items' => array()));
	$total = 0;
	$uids = array();
	foreach($users as &$user) {
		$uid = (int)$user['uid'];
		if(in_array($uid, $payfor_uids)) {
			/* Build a uid array just in case there is any funniness in the passed in payfor_uids */
			$uids[] = $uid;
			/* Add all items for this uid */
			foreach($fee_data[$uid] as $index=>$d) {
				if(!is_array($d)) continue;
				$item = array('quantity' => $d['num'],
						'name' => $d['id'],
						'price' => $d['base'],
						'currency' => 'CAD',
						'description' => $d['text'],
						'tax' => 0 );
				$transaction['item_list']['items'][] = $item;
				$total += $d['num'] * $d['base'];
			}
		}
	}
	$transaction['amount']['total'] = sprintf("%.02f", $total);
	$transaction['amount']['currency'] = 'CAD';
	$transaction['amount']['details'] = array('subtotal' => sprintf("%.02f", $total));
	$transaction['description'] = "Registration Fees";
	$transaction['invoice_number'] = $invoice;
	$transaction['custom'] = "Project {$u['s_pid']}";
	$payment['transactions'][] = $transaction;

	$res = paypal_api('POST', '/v1/payments/payment', $payment);

	if(!array_key_exists('state', $res) || $res['state'] != 'created') {
		debug("ERROR: Return from /payment doesn't contain a state=created, aborted payment\n");
		print(json_encode(array()));
		exit();
	}
	/* Failed */
	/* Record some details about this payment so we don't have to
	 * use session or other variables to get info to the /execute */
	$pay = payment_create($mysqli);
	$pay['status'] = 'created';
	$pay['transaction_id'] = $res['id'];
	$pay['payfor_uids'] = $uids;
	$pay['amount'] = sprintf("%.02f", $total);
	$pay['uid'] = $u['uid'];
	$pay['method'] = 'paypal';
	$pay['created_time'] = date('Y-m-d H:i:s', time(NULL));
	payment_save($mysqli, $pay);

/*		 
	[id] => PAY-6B744200WW232480SLAUNSHY
	[intent] => sale
	[state] => created
    [payer] => Array
        (
            [payment_method] => paypal
        )

    [transactions] => Array
        (
            [0] => Array
                (
                    [amount] => Array
                        (
                            [total] => 50.00
                            [currency] => CAD
                            [details] => Array
                                (
                                    [subtotal] => 50.00
                                )

                        )

                    [description] => Registration Fees
                    [custom] => Project 2559
                    [invoice_number] => 0520d0edfd458a73463a9ac31725f7d02e637c8de0bdb10d6cf34e14
                    [item_list] => Array
                        (
                            [items] => Array
                                (
                                    [0] => Array
                                        (
                                            [name] => Registration Fee
                                            [description] => Fair Registration (per student)
                                            [price] => 50.00
                                            [currency] => CAD
                                            [tax] => 0.00
                                            [quantity] => 1
                                        )

                                )

                        )

                    [related_resources] => Array
                        (
                        )

                )

        )

    [create_time] => 2016-11-13T21:20:31Z
    [links] => Array
        (
            [0] => Array
                (
                    [href] => https://api.sandbox.paypal.com/v1/payments/payment/PAY-6B744200WW232480SLAUNSHY
                    [rel] => self
                    [method] => GET
                )

            [1] => Array
                (
                    [href] => https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=EC-4PL73252CN928122T
                    [rel] => approval_url
                    [method] => REDIRECT
                )

            [2] => Array
                (
                    [href] => https://api.sandbox.paypal.com/v1/payments/payment/PAY-6B744200WW232480SLAUNSHY/execute
                    [rel] => execute
                    [method] => POST
                )

        )

)

*/	

	/* checkout 3.5: Find the approval url and redirect to it */
/*	foreach($res['links'] as $l) {
		if($l['rel'] == 'approval_url') {
			debug("Done with /checkout, redirecting to approval URL: {$l['href']}\n");
			header('Location: '.$l['href']);
		}
	}*/

	/* checkout 4.0: Return the paymentID as JSON */
	$response = array('paymentID' => $res['id']);
	print(json_encode($response));
	exit();
}

if($action == 'cancel') {
	/* User cancelled payment */
	sfiab_message("INF Payment Cancelled");
	header('Location: s_payment.php');
	exit();
}

if($action == 'return') {
	/* Final return URL I think */
	header('Location: s_payment.php');
	exit();
}

/* Paypal callback to let us know a payment is authorized, send an 
 * execute to complete it */
if($action == 'authorize') {
	/* BIG NOTE: anyone who is logged in could post here with a random
	 * payment ID and payerID, * we created a record of this payment in the
	 * DB, look it up and make sure it's int he created state.
	 * PayPal won't let it /execute unless the payer has authorized it, so
	 * if someone does manage to guess the paymentID, paypal will reject it
	 * below */

	/* 3.5 
	$payment_id = $_GET['paymentId'];
	$token = $_GET['token'];
	$payer_id = $_GET['PayerID'];
	/*

	/* 4.0 */
	$payment_id = $_POST['paymentID'];
	$payer_id = $_POST['payerID'];

	/* FInd the payment by payment_id */
	$pay = payment_load_by_transaction_id($mysqli, $payment_id);

	if($pay == NULL) {
		/* Not found */
		exit();
	}

	$pay['completed_time'] = date('Y-m-d H:i:s', time(NULL));

	$res = paypal_api('POST', "/v1/payments/payment/$payment_id/execute/", 
				array('payer_id' => $payer_id));

	if($res['state'] == 'approved') {
		/* Find the sale response */
		$sale = $res['transactions'][0]['related_resources'][0]['sale'];
		$sale_state = $mysqli->real_escape_string($sale['state']);

		$payer = $res['payer']['payer_info'];
		$pay['payer_firstname'] = $payer['first_name'];
		$pay['payer_lastname'] = $payer['last_name'];
		$pay['payer_country'] = $payer['country_code'];
		$pay['payer_email'] = $payer['email'];

		if($sale_state == 'completed') {
			/* Build a list of items from paypal and serialize them */
			$pay['items'] = serialize($res['transactions'][0]['item_list']);
			$pay['receipt_id'] = $sale['id'];
			$pay['status'] = 'completed';

			if((float)$sale['amount']['total'] != $pay['amount']) {
				debug("executing payment, database pay={$pay['amount']}, but paypal returned {$sale['amount']['total']}\n");
				exit();
			}
			if($pay['transaction_id'] != $sale['parent_payment']) {
				debug("executing payment, database transaction_id={$pay['transaction_id']}, but paypal returned {$sale['parent_payment']}\n");
				exit();
			}
			if($pay['uid'] != $u['uid']) {
				debug("executing payment, database uid={$pay['uid']}, but page loaded {$u['uid']}\n");
				exit();
			}


			$pay['fees'] = (float)$sale['transaction_fee']['value'];
			$pay['notes'] =  $res['transactions'][0]['invoice_number'];


			/* Set users to paid */
			foreach($pay['payfor_uids'] as $uid) {
				debug("Setting paid status for user {$uid} = {$pay['id']}\n");
				$u = user_load($mysqli, $uid);
				$u['s_paid'] = $pay['id'];
				user_save($mysqli, $u);
			}
		} else {
			/* state is not completed */
			$pay['status'] = 'failed';
		}
	} else {
		/* state is not completed */
		$pay['status'] = 'failed';
	}

	payment_save($mysqli, $pay);

	sfiab_message("HAP Payment Successful.  Your receipt ID is displayed below.");
	$response = array('status' => 0);
	print(json_encode($response));
	exit();

	/*

Curl response: Array
(
    [id] => PAY-3LG08151VN281071BLAUUS4Y
    [intent] => sale
    [state] => approved
    [cart] => 05E708580V3535735
    [payer] => Array
        (
            [payment_method] => paypal
            [status] => VERIFIED
            [payer_info] => Array
                (
                    [email] => dave-buyer@gvrsf.ca
                    [first_name] => test
                    [last_name] => buyer
                    [payer_id] => TJ97EMP5K3M46
                    [shipping_address] => Array
                        (
                            [recipient_name] => test buyer
                            [line1] => 1 Maire-Victorin
                            [city] => Toronto
                            [state] => Ontario
                            [postal_code] => M5A 1E1
                            [country_code] => CA
                        )

                    [country_code] => CA
                )

        )

    [transactions] => Array
        (
            [0] => Array
                (
                    [amount] => Array
                        (
                            [total] => 50.00
                            [currency] => CAD
                            [details] => Array
                                (
                                    [subtotal] => 50.00
                                )

                        )

                    [payee] => Array
                        (
                            [merchant_id] => AJPZJ3EZ2Y5HN
                            [email] => dave-facilitator@gvrsf.ca
                        )

                    [description] => Registration Fees
                    [invoice_number] => 0520d0edfd458a73463a9ac31725f7d02e637c8de0bdb10d6cf34e14
                    [item_list] => Array
                        (
                            [items] => Array
                                (
                                    [0] => Array
                                        (
                                            [name] => Registration Fee
                                            [description] => Fair Registration (per student)
                                            [price] => 50.00
                                            [currency] => CAD
                                            [tax] => 0.00
                                            [quantity] => 1
                                        )

                                )

                            [shipping_address] => Array
                                (
                                    [recipient_name] => test buyer
                                    [line1] => 1 Maire-Victorin
                                    [city] => Toronto
                                    [state] => Ontario
                                    [postal_code] => M5A 1E1
                                    [country_code] => CA
                                )

                        )

                    [related_resources] => Array
                        (
                            [0] => Array
                                (
                                    [sale] => Array
                                        (
                                            [id] => 3RS536664M764514L
                                            [state] => completed
                                            [amount] => Array
                                                (
                                                    [total] => 50.00
                                                    [currency] => CAD
                                                    [details] => Array
                                                        (
                                                            [subtotal] => 50.00
                                                        )

                                                )

                                            [payment_mode] => INSTANT_TRANSFER
                                            [protection_eligibility] => ELIGIBLE
                                            [protection_eligibility_type] => ITEM_NOT_RECEIVED_ELIGIBLE,UNAUTHORIZED_PAYMENT_ELIGIBLE
                                            [transaction_fee] => Array
                                                (
                                                    [value] => 1.75
                                                    [currency] => CAD
                                                )

                                            [parent_payment] => PAY-3LG08151VN281071BLAUUS4Y
                                            [create_time] => 2016-11-14T05:20:00Z
                                            [update_time] => 2016-11-14T05:20:01Z
                                            [links] => Array
                                                (
                                                    [0] => Array
                                                        (
                                                            [href] => https://api.sandbox.paypal.com/v1/payments/sale/3RS536664M764514L
                                                            [rel] => self
                                                            [method] => GET
                                                        )

                                                    [1] => Array
                                                        (
                                                            [href] => https://api.sandbox.paypal.com/v1/payments/sale/3RS536664M764514L/refund
                                                            [rel] => refund
                                                            [method] => POST
                                                        )

                                                    [2] => Array
                                                        (
                                                            [href] => https://api.sandbox.paypal.com/v1/payments/payment/PAY-3LG08151VN281071BLAUUS4Y
                                                            [rel] => parent_payment
                                                            [method] => GET
                                                        )

                                                )

                                        )

                                )

                        )

                )

        )

    [create_time] => 2016-11-14T05:20:01Z
    [links] => Array
        (
            [0] => Array
                (
                    [href] => https://api.sandbox.paypal.com/v1/payments/payment/PAY-3LG08151VN281071BLAUUS4Y
                    [rel] => self
                    [method] => GET
                )

        )
*/
}

?>
