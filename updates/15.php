<?php
function pre_15($mysqli)
{
/*	global $config;

	$config = array(
	    "digest_alg" => "sha512",
	    "private_key_bits" => 4096,
	    "private_key_type" => OPENSSL_KEYTYPE_RSA,
	);

	$res = openssl_pkey_new($config);
	openssl_pkey_export($res, $priv);
	$priv = $mysqli->real_escape_string($priv);
	$pubKey = openssl_pkey_get_details($res);
	$pub = $mysqli->real_escape_string($pubKey["key"]);

	$mysqli->real_query("INSERT INTO config (`year`,`var`,`val`,`category`,`type`) VALUES
					('-1','public_key','$pub','system','text'),
					('-1','private_key','$priv','system','text')");


	$mysqli->real_query("ALTER TABLE `fairs` CHANGE `token` `public_key` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
	*/
}
