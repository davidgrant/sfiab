<?php

require_once('paypal.inc.php');

$config = array();

$config['fair_url'] = "muon/sfiab";
$config['paypal_sandbox'] = true;
$config['paypal_sandbox_client_id'] = 'ATtg97-PC4XjKuQgylZVwM3H7yvhISZ7p4NTaFec6oT6ZVqgQdL8JU__vL0AK9H3MsKDABiNxC8myULN';
$config['paypal_sandbox_secret'] = 'EP9fQ8U3-bKsC0nFs-ELEeFMVsEkt9oop7GEtW_wi8Rkd3SzeaJJxok25h5Ghslj2QArAnsd-seqj3EB';

$debug_enable = true;
$config['fair_abbreviation'] = "PPTEST";

$action = NULL;
if(array_key_exists('PATH_INFO', $_SERVER)) {
	$action = substr($_SERVER['PATH_INFO'], 1);
}

if($action == 'checkout') {

	$payment = array();
	$payment['intent'] = 'sale';
//	$payment['experience_profile_id'] = $experience_id;
	$payment['redirect_urls'] = array('return_url' => $config['fair_url'].'/paypal.php/return',
					'cancel_url' => $config['fair_url'].'/paypal.php/cancel');
	$payment['payer'] = array('payment_method' => "paypal");
	$payment['transactions'] = array();

	/* Get the users to pay for */
	$transaction = array('amount' => array(),
			     'item_list' => array('items' => array()));
	$total = 0;
	$item = array('quantity' => 1,
			'name' => 'Registration Fee',
						'price' => 55,
						'currency' => 'CAD',
						'description' => 'Registration Fee (Per Student)',
						'tax' => 0 );
	$transaction['item_list']['items'][] = $item;
	$total += 55;

	$transaction['amount']['total'] = sprintf("%.02f", $total);
	$transaction['amount']['currency'] = 'CAD';
	$transaction['amount']['details'] = array('subtotal' => sprintf("%.02f", $total));
	$transaction['description'] = "Registration Fees";
	$transaction['custom'] = "Project 1246";
	$payment['transactions'][] = $transaction;

	$res = paypal_api('POST', '/v1/payments/payment', $payment);

	if(!array_key_exists('state', $res) || $res['state'] != 'created') {
		debug("ERROR: Return from /payment doesn't contain a state=created, aborted payment\n");
		print(json_encode(array()));
		exit();
	}
	$response = array('paymentID' => $res['id']);
	print(json_encode($response));
	exit();

} 

if($action == 'execute') {
	$payment_id = $_POST['paymentID'];
	$payer_id = $_POST['payerID'];

	$res = paypal_api('POST', "/v1/payments/payment/$payment_id/execute/", 
				array('payer_id' => $payer_id));

	$response = array('status' => 0, 'res' => $res);
	print(json_encode($response));
	exit();

}
if($action == 'success') {
?>	
	<html><body>
	<p>PayPal Checkout v4 Sandbox Test!</p>

	Paypal execute called /success!  Uhhh.. success!  (hurrah!):
	</body></html>
<?php
	exit();
}

if($action == 'error') {
?>	
	<html><body>
	<p>PayPal Checkout v4 Sandbox Test!</p>

	Paypal execute called /error!  Blarglefish  (Dave, that means you have to check the logs and write more error catching code, sorry about that).
	</body></html>
<?php
	exit();
}

if($action != NULL) {
	print("Got unknown REST req $action");
	exit();
}


?>


<html><body>

<p>PayPal Checkout v4 Sandbox Test!</p>





<div id="paypal-button"></div>


<script src="https://www.paypalobjects.com/api/checkout.js" data-version-4></script>

<script>
    paypal.Button.render({
    
        env: 'sandbox', // Optional: specify 'sandbox' environment
    
        payment: function(resolve, reject) {
               
            var CREATE_PAYMENT_URL = 'paypal.test.v4.php/checkout';
                
            paypal.request.post(CREATE_PAYMENT_URL)
                .then(function(data) { resolve(data.paymentID); })
                .catch(function(err) { reject(err); });
        },

        onAuthorize: function(data) {
        
            // Note: you can display a confirmation page before executing
            
            var EXECUTE_PAYMENT_URL = 'paypal.test.v4.php/execute';

            paypal.request.post(EXECUTE_PAYMENT_URL,
                    { paymentID: data.paymentID, payerID: data.payerID })
                    
                .then(function(data) { window.location="paypal.test.v4.php/success" })
                .catch(function(err) { window.location="paypal.test.v4.php/error" });
        }

    }, '#paypal-button');
</script>

</html></body>
