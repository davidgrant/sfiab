<?php
require_once('debug.inc.php');
require_once('user.inc.php');
require_once('filter.inc.php');
require_once('project.inc.php');

function paypal_get_oauth2_token()
{
	$res = paypal_api('POST', '/v1/oauth2/token', NULL, true);
	/* {
  "scope":"https://api.paypal.com/v1/payments/.* https://api.paypal.com/v1/vault/credit-card https://api.paypal.com/v1/vault/credit-card/.*",
  "access_token":"Access-Token",
  "token_type":"Bearer",
  "app_id":"APP-6XR95014SS315863X",
  "expires_in":28800
}	*/

	/* Or an error:
 Array
(
    [error] => unsupported_grant_type
    [error_description] => Grant Type is NULL
) */
	if(array_key_exists('error', $res)) {
		debug("paypal_get_oauth2_token failed: ".print_r($res, true)."\n");
		return false;
	}

	$_SESSION['paypal_token'] = $res['access_token'];
	$_SESSION['paypal_token_type'] = $res['token_type'];
	debug("Set SESSION token to {$res['token_type']} {$res['access_token']}\n");
	return true;
}

/* Get the current experience profile (named by the fair name) or create a new
 * one if it doesn't exist */
function paypal_get_or_create_experience_profile()
{
	global $config;
	$ret = paypal_api('GET', '/v1/payment-experience/web-profiles');
	/* Find the profile with our fair name */
	foreach($ret as $p) {
		if($p['name'] == $config['fair_name']) {
			debug("Found existing experience id {$p['id']}\n");
			return $p['id'];
		}
	}

	/* Profile not found, create it */
	return paypal_create_experience_profile();
}


/* Create a new experience profile for this fair.  Basically, we just want to
 * turn off shippping */ 
function paypal_create_experience_profile()
{
	global $config;
	$data = array(	'name' => $config['fair_name'],
			'presentation' => array('brand_name' => $config['fair_abbreviation']),
			'input_fields' => array('no_shipping' => 1,
						'address_override' => 0),
			);
	$ret = paypal_api('POST', '/v1/payment-experience/web-profiles', $data);
/* 	{
	  "id": "XP-CP6S-W9DY-96H8-MVN2"
	} */
	if(!array_key_exists('id', $ret)) {
		return NULL;
	}
	return $ret['id'];
}

function paypal_api($method, $api, $data = NULL, $auth = false)
{
	global $config;

	debug("paypal_api: $method : $api\n");

	if(!isset($_SESSION['paypal_token']) && $auth == false) {
		/* Try to get a token */
		$res = paypal_get_oauth2_token();
		if($res == false) return;
	}

	$headers = array();
	if($data === NULL) {
		$data = array();
	}

	$paypal_url = $config['paypal_sandbox'] ? 'https://api.sandbox.paypal.com' : 'https://api.paypal.com';	
	$paypal_url .= $api;

	$curl = curl_init();

	if($auth) {
		/* Auth uses different headers and non-json encoded post data */
		$client_id = $config['paypal_sandbox'] ? $config['paypal_sandbox_client_id'] : $config['paypal_client_id'];
		$secret = $config['paypal_sandbox'] ? $config['paypal_sandbox_secret'] : $config['paypal_secret'];

		$data['grant_type'] = 'client_credentials';

		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curl, CURLOPT_USERPWD, $client_id.':'.$secret);
		$headers[] = 'Accept: application/json';
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
	} else {
		$token_type = $_SESSION['paypal_token_type'];
		$token = $_SESSION['paypal_token'];
		$headers[] = 'Content-Type: application/json';
		$headers[] = "Authorization: $token_type $token";
		switch($method) {
		case 'POST':
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
			break;
		case "PUT":
			curl_setopt($curl, CURLOPT_PUT, 1);
			break;
		default:
			if(count($data)) {
				$paypal_url .= '?'.http_build_query($data);
			}
			break;
		}
        }

	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_URL, $paypal_url);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

	debug("Curl send $paypal_url: \n");
	debug("Headers:".print_r($headers, true)."\nData:".print_r(json_encode($data), true)."\n");
 	$res = json_decode(curl_exec($curl), true);
	$c_errno = curl_errno($curl);
	$c_error = curl_error($curl);
	debug("Curl response (error: $c_errno $c_error): ".print_r($res, true)."\n");

	curl_close($curl);

	if(!$res) {
		/* Curl failed */
		print("Failed to send anything to the PayPal server.  Payment not processed.");
		exit();
	}
 
	return $res;
    
}


function payment_load($mysqli, $id, $transaction_id = NULL, $data = NULL)
{
	$id = (int)$id;
	if($id != 0) {
		$q = $mysqli->query("SELECT * FROM payments WHERE id='$id'");
		$t = $q->fetch_assoc();
		print($mysqli->error);
	} else if($transaction_id != NULL) {
		$tr = $mysqli->real_escape_string($transaction_id);
		$q = $mysqli->query("SELECT * FROM payments WHERE transaction_id='$tr'");
		$t = $q->fetch_assoc();
		print($mysqli->error);
	} else {
		$t = $data;
		$id = $t['id'];
	}

	filter_int_list($t['payfor_uids']);
	filter_int($t['year']);
	filter_float_or_null($t['amount']);
	filter_float_or_null($t['fees']);

	unset($t['original']);
	$original = $t;
	$t['original'] = $original;
	
	return $t;
}

function payment_load_by_transaction_id($mysqli, $transaction_id)
{
	return payment_load($mysqli, 0, $transaction_id);
}

function payment_create($mysqli, $year=NULL) 
{
	global $config;
	if($year === NULL) $year = $config['year'];
	$r = $mysqli->real_query("INSERT INTO payments(`year`) VALUES('$year')");
	$tid = $mysqli->insert_id;

	$p = payment_load($mysqli, $tid);

	return $p;
}

function payment_save($mysqli, &$p)
{
	generic_save($mysqli, $p, 'payments', 'id');
}


?>
