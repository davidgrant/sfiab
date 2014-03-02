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

function filter_bool(&$val)
{
	$val = ((int)$val == 0) ? 0 : 1;
}

function filter_float(&$val)
{
	$val = (float)$val;
}
function filter_float_or_null(&$val)
{
	if(is_null($val)) return;
	$val = (float)$val;
}



function filter_phone(&$val)
{
	$ret = preg_match('/.*1?[^0-9]*([0-9][0-9][0-9])[^0-9]*([0-9][0-9][0-9])[^0-9]*([0-9][0-9][0-9][0-9]) *(.*)/', $val, $matches);
	if($ret > 0) {
		$val = "{$matches[1]}-{$matches[2]}-{$matches[3]} {$matches[4]}";
	} else {
		$val = NULL;
	}
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

function post_array(&$val, $var, &$choices) 
{
	$val = array();
	if(array_key_exists($var, $_POST)) {
		foreach($_POST[$var] as $i=>$v) {
			if(array_key_exists($v, $choices)) {
				$val[] = $v;
			}
		}
	}
}

?>
