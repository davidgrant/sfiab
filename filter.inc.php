<?php
function filter_int(&$val)
{
	$val = (int)$val;
}

function filter_int_or_null(&$val)
{
	if(is_null($val)) return;
	$val = (int)$val;
}

function filter_bool_or_null(&$val)
{
	if(is_null($val)) return;
	$val = (int)$val;
}

function post_bool(&$val, $var)
{
	if(array_key_exists($var, $_POST)) {
		$val = (int)$_POST[$var] ? 1 : 0;
	}
}

function post_int(&$val, $var)
{
	if(array_key_exists($var, $_POST)) {
		if($_POST[$var] == '') 
			$val = NULL;
		else 
			$val = (int)$_POST[$var];
	}
}
function post_float(&$val, $var)
{
	if(array_key_exists($var, $_POST)) {
		if($_POST[$var] == '') 
			$val = NULL;
		else 
			$val = (float)$_POST[$var];
	}
}

function post_text(&$val, $var)
{
	if(array_key_exists($var, $_POST)) {
		if($_POST[$var] == '') 
			$val = NULL;
		else 
			$val = $_POST[$var];
	}
}

?>
