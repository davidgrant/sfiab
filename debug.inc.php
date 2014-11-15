<?php

$debug_enable = false;

function debug($str) 
{
	global $debug_enable;
	global $config;

	if($debug_enable !== true) return;

	if(array_key_exists('fair_abbreviation', $config))
		$fair = $config['fair_abbreviation'];
	else
		$fair = 'n/a';

	$fp = fopen("/tmp/sfiab.log", "at");
	fwrite($fp, $fair.":".$str);
	fclose($fp);
}

?>
