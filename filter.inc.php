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
function filter_language(&$val)
{
	$languages = array('en','fr');
	if(!in_array($val, $languages)) $val = NULL;
}
function filter_languages(&$val) 
{
	$langs = explode(',', $val);
	$val = array();
	foreach($langs as $l) {
		filter_language($l);
		if($l !== NULL) {
			$val[] = $value;
		}
	}
}
function filter_int_list(&$val)
{
	if(is_array($val)) return;
	if($val === NULL or $val == '') {
		$val = array();
	} else {
		$l = explode(',', $val);
		$val = array();
		foreach($l as $value) {
			$val[] = (int)$value;
		}
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

function post_float(&$val, $var, $arr=NULL)
{
	if($arr === NULL) $arr = &$_POST;
	if(array_key_exists($var, $arr)) {
		if($arr[$var] == '') 
			$val = NULL;
		else 
			$val = (float)$arr[$var];
	}
}

function post_text(&$val, $var, $arr = NULL)
{
	if($arr === NULL) $arr = &$_POST;
	if(array_key_exists($var, $arr)) {
		if($arr[$var] == '') 
			$val = NULL;
		else 
			$val = $arr[$var];
	}
}

function post_array(&$val, $var, &$choices, $arr = NULL) 
{
	if($arr === NULL) $arr = &$_POST;

	$val = array();
	if(array_key_exists($var, $arr)) {
		foreach($arr[$var] as $i=>$v) {
			if(array_key_exists($v, $choices)) {
				$val[] = $v;
			}
		}
	}
}

function post_int_list(&$val, $var, $arr = NULL) 
{
	if($arr === NULL) $arr = &$_POST;

	$val = array();
	if(array_key_exists($var, $arr)) {
		foreach($arr[$var] as $i=>$v) {
			$val[(int)$i] = (int)$v;
		}
	}
}


?>
