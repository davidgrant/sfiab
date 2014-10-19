<?php
require_once('common.inc.php');
require_once('user.inc.php');
require_once('form.inc.php');
require_once('incomplete.inc.php');
require_once('stats.inc.php');
require_once('fairs.inc.php');
require_once('remote.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);


$key_config = array(
    "digest_alg" => "sha512",
    "private_key_bits" => 2048,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
);
   
print("create key\n");
// Create the private and public key
$res = openssl_pkey_new($key_config);

print("export \n");
// Extract the private key from $res to $privKey
openssl_pkey_export($res, $privKey);
print("priv key: $privKey\n");
#print("privkey: ".openssl_pkey_get_private($res));

// Extract the public key from $res to $pubKey
$pubKey = openssl_pkey_get_details($res);
$pubKey = $pubKey["key"];
print("priv key: $pubKey\n");


//$privKey = $config['private_key'];

//$pubKey = $config['public_key'];

$data = "plaintext data goes here\n";

// Encrypt the data to $encrypted using the public key
openssl_private_encrypt($data, $encrypted, $privKey);

print("len=".strlen($encrypted)."\n");
//echo "encrypted: ".$encrypted;

// Decrypt the data using the private key and store the results in $decrypted
openssl_public_decrypt($encrypted, $decrypted, $pubKey);

echo $decrypted;

$fair = fair_load_by_hash($mysqli, "dc4f6cb2cdc9e3a95055392dada70ce780a965922850e4ea7d478a572aa67436fa00920ef97efdd7c2d4ac621f9eebc795978517b28e6d2b677d022e4bc68fce");

$text = "This is a test";

echo"encrypt...\n";
$enc = remote_encrypt($fair, $text);

if($enc === NULL) {
	print("encrypt failed\n");
	exit();
}
print("$enc");



// set the default timezone to use. Available since PHP 5.1
date_default_timezone_set('America/Toronto');
print("Now: ".date('Y-m-d H:i:s', time())."\n");


#print_r(stats_gather($mysqli, 2014));

$fstart = "2014-04-12";
$s = date('Y-m-d H:i:s', strtotime($fstart));

print("Fair start: $fstart, back and forth: $s\n");

$fstart = "2014-04-12";
$s = date('Y-m-d H:i:s', strtotime($fstart)+ (120 * 60)) ;

print("Fair start: $fstart, back and forth: $s\n");


$d = date_parse($fstart);
print_r($d);


#sfiab_session_start($mysqli);

#print('<pre>');
#print_r($_SESSION);
#print('</pre>');
#print phpinfo();




sfiab_page_end();
?>
