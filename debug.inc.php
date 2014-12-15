<?php

function debug($str) 
{
	global $debug_enable;
	global $config;

	if(!isset($debug_enable)) return;

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
