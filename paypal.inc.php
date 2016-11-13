<?php
require_once('debug.inc.php');
require_once('user.inc.php');

function paypal_build_fees_data($fees)
{
	/* Sum */
	$data = array();

	$total = 0;
	for($i=0; $i<count($fees); $i++) {
		$total += $fees[$i]['amt'] * $fees[$i]['qty'];
		$data += array( "L_PAYMENTREQUEST_0_NAME$i" => $fees[$i]['name'],
				"L_PAYMENTREQUEST_0_DESC$i" => $fees[$i]['desc'],
    				"L_PAYMENTREQUEST_0_QTY$i" => $fees[$i]['qty'],
				"L_PAYMENTREQUEST_0_AMT$i" => $fees[$i]['amt']);
	}
	$data += array( 'PAYMENTREQUEST_0_AMT' => $total,
			'PAYMENTREQUEST_0_CURRENCYCODE' => 'CAD',
			'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
			'PAYMENTREQUEST_0_ITEMAMT' => $total,
			'PAYMENTREQUEST_0_TAXAMT' => 0,
			'NOSHIPPING' => '1');
	return $data;
}


function paypal_set_express_checkout($fees)
{
	global $config;
	/* 1. Build a set of fees, start an express checkout
	   2. Paypal responds with a token.
	   3. Redirect the webpage to paypal and pass paypal back the token 
	   4. Paypal does the payment and redirects to either the CANCEL or RETURN url (both handled in paypal.php)
	   5. If RETURN, Call Paypal with DoExpressCHeckout and the same fee query
	*/


	$data = paypal_build_fees_data($fees);
	$data += array(	
			'L_BILLINGAGREEMENTDESCRIPTION0' => 'Agreement',
			'CANCELURL' => $config['fair_url'].'/s_payment.php',
			'RETURNURL' => $config['fair_url'].'/paypal.php',
			    );

	/* Save the fees for do_express_checkout */
	$_SESSION['paypal_fees'] = $fees;

	$res = paypal_post('SetExpressCheckout', $data);

	if (isset($res['ACK']) && ($res['ACK'] == 'Success' || $res['ACK'] == 'SuccessWithWarning') ) {
		$query = array(
		        'cmd'    => '_express-checkout',
		        'token'  => $res['TOKEN'],
   		 );
		$sandbox = $config['paypal_sandbox'] ? 'sandbox.' : '';
		$redirectURL = sprintf('https://www.'.$sandbox.'paypal.com/cgi-bin/webscr?%s', http_build_query($query));
		header('Location: ' . $redirectURL);
	} else {
		print("Paypal query failed: {$res['L_LONGMESSAGE0']}.  No payment processed.");
		debug("Paypal query failed: {$res['L_LONGMESSAGE0']}.  No payment processed.");
		exit();
	}
	return true;
}

function paypal_do_express_checkout($mysqli, &$u, $token, $payer_id)
{
	$fees = $_SESSION['paypal_fees'];
	$data = paypal_build_fees_data($fees);
	$data += array(	'TOKEN' => $token,
			'PAYERID' => $payer_id );

	$res = paypal_post('DoExpressCheckoutPayment', $data);
	
	if($res['ACK'] == 'Success' || $res['ACK'] == 'SuccessWithWarning') {
		/* Array (
		    [TOKEN] => EC-5XN21764CV239260F
		    [SUCCESSPAGEREDIRECTREQUESTED] => false
		    [TIMESTAMP] => 2016-10-30T17:26:09Z
		    [CORRELATIONID] => c752c44f45956
		    [ACK] => SuccessWithWarning
		    [VERSION] => 124.0
		    [BUILD] => 26593028
		    [L_ERRORCODE0] => 11607
		    [L_SHORTMESSAGE0] => Duplicate Request
		    [L_LONGMESSAGE0] => A successful transaction has already been completed for this token.
		    [L_SEVERITYCODE0] => Warning
		    [INSURANCEOPTIONSELECTED] => false
		    [SHIPPINGOPTIONISDEFAULT] => false
		    [PAYMENTINFO_0_TRANSACTIONID] => 50F78584TP014673W
		    [PAYMENTINFO_0_RECEIPTID] => 975562220360333
		    [PAYMENTINFO_0_TRANSACTIONTYPE] => cart
		    [PAYMENTINFO_0_PAYMENTTYPE] => instant
		    [PAYMENTINFO_0_ORDERTIME] => 2016-10-30T14:31:54Z
		    [PAYMENTINFO_0_AMT] => 55.00
		    [PAYMENTINFO_0_FEEAMT] => 1.90
		    [PAYMENTINFO_0_TAXAMT] => 0.00
		    [PAYMENTINFO_0_CURRENCYCODE] => CAD
		    [PAYMENTINFO_0_PAYMENTSTATUS] => Completed
		    [PAYMENTINFO_0_PENDINGREASON] => None
		    [PAYMENTINFO_0_REASONCODE] => None
		    [PAYMENTINFO_0_PROTECTIONELIGIBILITY] => Eligible
		    [PAYMENTINFO_0_PROTECTIONELIGIBILITYTYPE] => ItemNotReceivedEligible,UnauthorizedPaymentEligible
		    [PAYMENTINFO_0_SECUREMERCHANTACCOUNTID] => AJPZJ3EZ2Y5HN
		    [PAYMENTINFO_0_ERRORCODE] => 0
		    [PAYMENTINFO_0_ACK] => Success
		 ) */
		
		$transaction_id = $mysqli->real_escape_string($res['PAYMENTINFO_0_TRANSACTIONID']);
		if(array_key_exists('PAYMENTINFO_0_RECEIPTID', $res)) {
			$receipt_id = $mysqli->real_escape_string($res['PAYMENTINFO_0_RECEIPTID']);
		} else {
			$receipt_id = '';
		}

		$amt = (float)$res['PAYMENTINFO_0_AMT'];
		$paypal_fees = (float)$res['PAYMENTINFO_0_FEEAMT'];
		switch($res['PAYMENTINFO_0_PAYMENTSTATUS']) {
		case 'Completed': $status = 'completed'; break;
		case 'Pending': $status = 'pending'; break;
		default: $status = 'failed'; break;
		}
		$pending_reason = $mysqli->real_escape_string($res['PAYMENTINFO_0_PENDINGREASON']);
		/*strtotime can handle paypal's date notation */
		$order_time = date('Y-m-d H:i:s', strtotime($res['PAYMENTINFO_0_ORDERTIME']));

		$notes = $pending_reason;


		/* If status == Pending, need to tell the user to login to their paypal <a target="_new" href="http://www.paypal.com">Paypal Account</a></div>';
		 * and authorize the payment */
		
		$details = paypal_get_transaction_details($token);

		$firstname = $mysqli->real_escape_string($details['FIRSTNAME']);
		$lastname = $mysqli->real_escape_string($details['LASTNAME']);
		$email = $mysqli->real_escape_string($details['EMAIL']);
		$country = $mysqli->real_escape_string($details['COUNTRYCODE']);

		/* Build a list of items from paypal and serialize them */
		$items = $mysqli->real_escape_string(serialize($details['L_PAYMENTREQUEST_0']));

		$uid = $u['uid'];
		$mysqli->query("INSERT INTO payments(`uid`,`method`,`payer_firstname`,`payer_lastname`,`payer_email`,`payer_country`,`amount`,`fees`,`token`,`transaction_id`,`receipt_id`,`status`,`order_time`,`time`,`items`,`notes`) VALUES 
				('$uid','paypal','$firstname','$lastname','$email','$country','$amt','$paypal_fees','$token','$transaction_id','$receipt_id','$status','$order_time',NOW(),'$items','$notes')");
		print($mysqli->error);
		$payment_id = $mysqli->insert_id;

		/* Set users to paid */
		foreach($fees as $f) {
			debug("Setting paid status for user {$f['uid']} = $payment_id\n");
			$u = user_load($mysqli, $f['uid']);
			$u['s_paid'] = $payment_id;
			user_save($mysqli, $u);
		}
		return true;
	} else{
		/* Payment failed. */
		$error_message = $res['L_LONGMESSAGE0'];
		return false;
	}
}

function paypal_get_transaction_details($token)
{
	$data = array(	'TOKEN' => $token );
	$res = paypal_post('GetExpressCheckoutDetails', $data);

	foreach($res as $var=>$val) {
		/* Build an L_PAYMENTREQUEST_0_array of produces returned from the query, like this:
		 * $res['L_PAYMENTREQUEST_0'][0] = Array('NAME'='', 'QTY'='', ...)
		 */
		if(substr($var, 0, 19) === 'L_PAYMENTREQUEST_0_') {
			if(!array_key_exists('L_PAYMENTREQUEST_0', $res)) {
				$res['L_PAYMENTREQUEST_0'] = array();
			}
			if (preg_match('/L_PAYMENTREQUEST_0_([A-Z]*)([0-9]*)/', $var, $matches)) {
				$offset = $matches[2];
				$pvar = $matches[1];

				if(!array_key_exists($offset, $res['L_PAYMENTREQUEST_0'])) {
					$res['L_PAYMENTREQUEST_0'][$offset] = array('NAME'=>'', 'QTY'=>1, 'AMT'=>0, 'DESC'=>'');
				}
				$res['L_PAYMENTREQUEST_0'][$offset][$pvar] = $val;
			}
		}
	}
	return $res;
		/* Array (
			    [TOKEN] => EC-5XN21764CV239260F
			    [BILLINGAGREEMENTACCEPTEDSTATUS] => 0
			    [CHECKOUTSTATUS] => PaymentActionCompleted
			    [TIMESTAMP] => 2016-10-30T17:26:09Z
			    [CORRELATIONID] => 79c04a9867fc4
			    [ACK] => Success
			    [VERSION] => 124.0
			    [BUILD] => 26593028
			    [EMAIL] => dave@gvrsf.ca
			    [PAYERID] => XPBM2CUQU8VDA
			    [PAYERSTATUS] => unverified
			    [FIRSTNAME] => David
			    [LASTNAME] => Grant
			    [COUNTRYCODE] => CA
			    [ADDRESSSTATUS] => Confirmed
			    [CURRENCYCODE] => CAD
			    [AMT] => 55.00
			    [ITEMAMT] => 55.00
			    [SHIPPINGAMT] => 0.00
			    [HANDLINGAMT] => 0.00
			    [TAXAMT] => 0.00
			    [INSURANCEAMT] => 0.00
			    [SHIPDISCAMT] => 0.00
			    [TRANSACTIONID] => 50F78584TP014673W
			    [INSURANCEOPTIONOFFERED] => false
			    [L_NAME0] => Registration
			    [L_QTY0] => 1
			    [L_TAXAMT0] => 0.00
			    [L_AMT0] => 55.00
			    [L_DESC0] => Registration Fee
			    [PAYMENTREQUEST_0_CURRENCYCODE] => CAD
			    [PAYMENTREQUEST_0_AMT] => 55.00
			    [PAYMENTREQUEST_0_ITEMAMT] => 55.00
			    [PAYMENTREQUEST_0_SHIPPINGAMT] => 0.00
			    [PAYMENTREQUEST_0_HANDLINGAMT] => 0.00
			    [PAYMENTREQUEST_0_TAXAMT] => 0.00
			    [PAYMENTREQUEST_0_INSURANCEAMT] => 0.00
			    [PAYMENTREQUEST_0_SHIPDISCAMT] => 0.00
			    [PAYMENTREQUEST_0_TRANSACTIONID] => 50F78584TP014673W
			    [PAYMENTREQUEST_0_SELLERPAYPALACCOUNTID] => dave-facilitator@gvrsf.ca
			    [PAYMENTREQUEST_0_INSURANCEOPTIONOFFERED] => false
			    [PAYMENTREQUEST_0_SOFTDESCRIPTOR] => PAYPAL *TESTFACILIT
			    [PAYMENTREQUEST_0_ADDRESSSTATUS] => Confirmed
			    [L_PAYMENTREQUEST_0_NAME0] => Registration
			    [L_PAYMENTREQUEST_0_QTY0] => 1
			    [L_PAYMENTREQUEST_0_TAXAMT0] => 0.00
			    [L_PAYMENTREQUEST_0_AMT0] => 55.00
			    [L_PAYMENTREQUEST_0_DESC0] => Registration Fee
			    [PAYMENTREQUESTINFO_0_TRANSACTIONID] => 50F78584TP014673W
			    [PAYMENTREQUESTINFO_0_ERRORCODE] => 0
			)
		*/
}



function paypal_post($method, $post_data)
{
	global $config;

	$arr = array(
	    	'METHOD' => $method,
		'VERSION' => '124.0',
		'LOCALECODE' => 'en_CA',
		);
	if($config['paypal_sandbox']) {
		$arr += array( 	'USER' => $config['paypal_sandbox_user'],
				'PWD' => $config['paypal_sandbox_pwd'],
				'SIGNATURE' => $config['paypal_sandbox_signature']);
	} else {
		$arr += array( 	'USER' => $config['paypal_user'],
				'PWD' => $config['paypal_pwd'],
				'SIGNATURE' => $config['paypal_signature']);
	}
	$arr += $post_data;		

	$curl = curl_init();

	$paypal_url = $config['paypal_sandbox'] ? 'https://api-3t.sandbox.paypal.com/nvp' : 'https://api.paypal.com/nvp';
 
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_URL, $paypal_url);
	curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($arr));
	debug("Curl send: ".print_r($arr, true)."\n");
 	$res = curl_exec($curl);
	debug("Curl response: ".print_r($res, true)."\n");

	if(!$res) {
		/* Curl failed */
		print("Failed to send anything to the PayPal server.  Payment not processed.");
		exit();
	}
	curl_close($curl);
 
	$response_array = array();
 
	if (preg_match_all('/(?<var>[^\=]+)\=(?<val>[^&]+)&?/', $res, $matches)) {
    		foreach ($matches['var'] as $offset=>$var) {
		        $data[$var] = urldecode($matches['val'][$offset]);
		}
	}
	debug("Curl parsed response: ".print_r($data, true)."\n");
	return $data;
    
}



?>
