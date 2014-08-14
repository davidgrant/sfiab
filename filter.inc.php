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
function filter_int_list(&$val, $size=0)
{
	if(is_array($val)) return;
	if($val === NULL or $val == '') {
		$val = array();
	} else {
		$l = explode(',', $val);
		$val = array();
		$x = 0;
		foreach($l as $value) {
			if($value === '') {
				$val[] = NULL;
			} else {
				$val[] = (int)$value;
			}

			/* If we hit size, stop.  If $size == 0 we'll never hit this */
			$x++;
			if($x == $size) break;
		}
	}

	/* If it's not big enough, pad it with NULLs */
	if(count($val) < $size) {
		for($x=count($val); $x<$size; $x++) {
			$val[] = NULL;
		}
	}

}

function filter_str_list(&$val)
{
	if(is_array($val)) return;
	if($val === NULL or $val == '') {
		$val = array();
	} else {
		$l = explode(',', $val);
		$val = array();
		foreach($l as $value) {
			$val[] = $value;
		}
	}
}

/* Return null if we can't find the var because sometimes $_POST doesn't contain
 * form fields if they weren't filled out and/or left untouched */
function post_get_value($var)
{
	if(is_array($var)) {
		$arr = &$_POST;
		foreach($var as $v) {
			if(!array_key_exists($v, $arr)) {
				return NULL;
			}

			if(is_array($arr[$v])) {
				$arr = &$arr[$v];
			} else {
				return $arr[$v];
			}
		}
		/* If we get all the way through the $var array, return
		 * whatever is left, it might be an array */
		return $arr;

	} else {
		if(!array_key_exists($var, $_POST)) 
			return NULL;
		return $_POST[$var];
	}
}

function post_bool(&$val, $var)
{
	$v = post_get_value($var);
	if($v !== NULL) {
		$val = (int)post_get_value($var) ? 1 : 0;
	}
}

function post_int(&$val, $var)
{
	$v = post_get_value($var);
	if($v !== NULL) {
		$val = ($v == '') ? NULL : (int)$v;
	}
}

function post_float(&$val, $var)
{
	$v = post_get_value($var);
	if($v !== NULL) {
		$val = ($v == '') ? NULL : (float)$v;
	}
}

function post_text(&$val, $var)
{
	$v = post_get_value($var);
	if($v !== NULL) {
		$val = ($v == '') ? NULL : $v;
	}
}

function post_array(&$val, $var, &$choices) 
{
	$v = post_get_value($var);
	if($v !== NULL) {
		$val = array();
		foreach($v as $idx=>$dat) {
			if($choices === NULL || array_key_exists($dat, $choices)) {
				$val[] = $dat;
			}
		}
	} 
}

function post_int_list(&$val, $var) 
{
	$v = post_get_value($var);
	if($v !== NULL) {
		$val = array();
		foreach($v as $idx=>$dat) {
			$val[(int)$idx] = (int)$dat;
		}
	}
}


?>
