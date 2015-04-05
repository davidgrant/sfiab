<?php
$form_incomplete_fields = array();
$form_page_id = NULL;
$form_form_id = NULL;
$form_disabled = false;
$form_show_data_clear_buttons = true;

function form_inc($name)
{
	global $form_incomplete_fields;
	if(in_array($name, $form_incomplete_fields)) 
		return "class=\"error\"";
	return "";
}

function form_label($page_id, $name, $label, $data)
{ 
	$id = $page_id.'_'.$name;
?>
	<div class="ui-field-contain">
		<label for="<?=$id?>" <?=form_inc($name)?>><?=$label?>:</label>
		<?=$data?>
	</div>
<?php
}


function form_data_clear_btn()
{
	global $form_show_data_clear_buttons;
	if($form_show_data_clear_buttons) {
		return 'data-clear-btn="true"';
	}
	return '';
}

function form_text($page_id, $name, $label, &$value = '', $type='text', $extra='') 
{ 
	global $form_disabled;
	global $form_show_data_clear_buttons;

	if(!in_array($type, array('text', 'tel','date','email','password'))) {
		print("Error 1001: $type\n");
		exit();
	}

	/* This is so we can pass $u or $p in, and use the name to index into the array */
	$v = (is_array($value)) ? $value[$name] : $value;

	$id = $page_id.'_'.$name;
	$placeholder = $label;
	if($type == 'date') $placeholder.= ' (YYYY-MM-DD)';
	if($type == 'tel') $placeholder .= ' (NNN-NNN-NNNN)';

	$extra .= $form_disabled ? ' disabled="disabled"': '';

	if($label !== NULL) {
?>		<div class="ui-field-contain">
		<label for="<?=$id?>" <?=form_inc($name)?>><?=$label?>:</label>
<?php	} ?>
		<input id="<?=$id?>" name="<?=$name?>" value="<?=$v?>" placeholder="<?=$placeholder?>" <?=form_data_clear_btn()?> type="<?=$type?>" <?=$extra?> >

<?php	if($label !== NULL) { ?>
		</div>
<?php	}
}

function form_text_inline($form_id, $name, &$value = '', $type='text', $extra='')
{
	form_text($form_id, $name, NULL, $value, $type, $extra.' data-inline="true"');
}

function form_int($page_id, $name, $label, &$value = '', $min=NULL, $max=NULL) 
{ 
	global $form_disabled;
	$d = $form_disabled ? ' disabled="disabled"': '';
	/* This is so we can pass $u or $p in, and use the name to index into the array */
	$v = (is_array($value)) ? $value[$name] : $value;

	$id = $page_id.'_'.$name;
	$placeholder = $label;
	$minv = ($min === NULL) ? '' : "min=\"$min\"";
	$maxv = ($max === NULL) ? '' : "max=\"$max\"";
	$d = $form_disabled ? ' disabled="disabled"': '';
?>
	<div class="ui-field-contain">
		<label for="<?=$id?>" <?=form_inc($name)?>><?=$label?>:</label>
		<input id="<?=$id?>" name="<?=$name?>" value="<?=$v?>" placeholder="<?=$placeholder?>" data-clear-btn="true" type="number" <?=$min?> <?=$max?> <?=$d?> >
	</div>
<?php
}

$__form_label_div_label = '';

function form_label_div_begin($id, $name, $label, $wide = false) 
{
	global $__form_label_div_label;
	
	$__form_label_div_label = $label;

	$extra_class = $wide ? 'ui-field-contain-wide' : '';
	if($label !== NULL) { ?>
		<div class="ui-field-contain <?=$extra_class?>">
		<label for="<?=$id?>" <?=form_inc($name)?>><?=$label?>:</label>
<?php 	}
}

function form_label_div_end()
{
	global $__form_label_div_label;

	if($__form_label_div_label !== NULL) { ?>
		</div>
<?php	}
}


function form_radio_h($form_id, $name, $label, $data, &$value, $wide=false)
{ 
	global $form_disabled;

	$id = $form_id.'_'.$name;

	/* This is so we can pass $u or $p in, and use the name to index into the array */
	$v = (is_array($value)) ? $value[$name] : $value;
	$d = $form_disabled ? ' disabled="disabled"': '';

	form_label_div_begin($id, $name, $label, $wide);
?>
	<fieldset id="<?=$id?>" data-role="controlgroup" data-type="horizontal" >
<?php
		$x=0;
		foreach($data as $key=>$val) {
			if(is_array($val)) $val = $val['name'];
			$sel = ($v === $key) ? 'checked="checked"' : '';  ?>
		        <input name="<?=$name?>" id="<?=$id.'-'.$x?>" value="<?=$key?>" <?=$sel?> type="radio" <?=$d?> >
		        <label for="<?=$id.'-'.$x?>"><?=$val?></label>
<?php			$x++;
		} ?>
	</fieldset>
<?php	form_label_div_end();
}

function form_check_group($form_id, $name, $label, $data, &$value, $wide = false)
{
	global $form_disabled;
	$id = $form_id.'_'.$name;
	/* This is so we can pass $u or $p in, and use the name to index into the array */
	if(is_array($value)) {
		if(array_key_exists($name, $value))
			$v = $value[$name];
		else 
			$v = $value;
	} else {
		$v = array($value);
	}
	$extra_class = $wide ? 'ui-field-contain-wide' : '';
	$d = $form_disabled ? ' disabled="disabled"': '';

	form_label_div_begin($id, $name, $label, $wide);
?>		<fieldset id="<?=$id?>" data-role="controlgroup" data-type="horizontal" >
<?php		$x=0;
		foreach($data as $key=>$val) {
			if(is_array($val)) $val = $val['name'];
			$sel = (in_array($key,$v)) ? 'checked="checked"' : ''; ?>
			
		        <input name="<?=$name?>[]" id="<?=$id.'-'.$x?>" value="<?=$key?>" <?=$sel?> type="checkbox" <?=$d?> >
		        <label for="<?=$id.'-'.$x?>"><?=$val?></label>
<?php			$x++;
		} ?>
		</fieldset>

<?php	form_label_div_end();
}

function form_check_list($form_id, $name, $label, $data, &$value, $wide = false)
{
	global $form_disabled;
	$id = $form_id.'_'.$name;
	/* This is so we can pass $u or $p in, and use the name to index into the array */
	if(is_array($value)) {
		if(array_key_exists($name, $value))
			$v = $value[$name];
		else 
			$v = $value;
	} else {
		$v = array($value);
	}
	$extra_class = $wide ? 'ui-field-contain-wide' : '';
	$d = $form_disabled ? ' disabled="disabled"': '';

	form_label_div_begin($id, $name, $label, $wide);
?>		<fieldset id="<?=$id?>" data-role="controlgroup"  >
<?php		$x=0;
		foreach($data as $key=>$val) {
			if(is_array($val)) $val = $val['name'];
				$sel = (in_array($key,$v)) ? 'checked="checked"' : ''; ?>
			
		        <input name="<?=$name?>[]" id="<?=$name.'-'.$x?>" value="<?=$key?>" <?=$sel?> type="checkbox" <?=$d?> >
		        <label for="<?=$name.'-'.$x?>"><?=$val?></label>
<?php			$x++;
		} ?>
		</fieldset>

<?php	form_label_div_end();
}

function form_checkbox($form_id, $name, $label, $data_value, &$value) 
{
	global $form_disabled;
	$id = $form_id.'_'.$name.'_'.$data_value;
	/* This is so we can pass $u or $p in, and use the name to index into the array */
	if(is_array($value)) {
		if(array_key_exists($name, $value))
			$v = $value[$name];
		else 
			$v = $value;
	} else {
		$v = array($value);
	}
	$sel = (in_array($data_value,$v)) ? 'checked="checked"' : ''; 
	$d = $form_disabled ? ' disabled="disabled"': ''; ?>

        <input name="<?=$name?>[]" id="<?=$id?>" value="<?=$data_value?>" <?=$sel?> type="checkbox" <?=$d?> >
        <label for="<?=$id?>"><?=$label?></label>
<?php
}

function form_yesno($form_id, $name, $label, &$value, $wide=false, $slider=false) 
{ 
	$choices = array(0 => 'No', 1 => 'Yes');
	/* v is usually 0 or 1, but could be NULL (no selection yet) */
	$v = (is_array($value)) ? $value[$name] : $value;

	if(is_null($v)) {
		/* Ok */
	} else if ((int)$v == 0 || (int)$v == 1) {
		$v = (int)$v;
	} else {
		$v = NULL;
	}

	if(!$slider ) {
		form_radio_h($form_id, $name, $label, $choices, $v, $wide);
	} else {
	        form_select($form_id, $name, $label, $choices, $v, 'slider', $wide);
	}
}

function form_get_value($name, &$value) 
{
	/* If the value is not an array, return the value */
	if(!is_array($value)) return $value;

	/* If it is, and the name request was something like j_pref_div[0], then pull out the specfic index and return
	 * that.  This can nest multiple leves deep.  prize[0][1] for exmaple.
	 * But if the specifier is [], then return the whole array */
	$p = strpos($name, '[');
	if($p !== false) {
		/* Expect the name is either in the form:  j_pref_div[0] */
		$array_name = substr($name, 0, $p);
		$array_index_str = substr($name, $p+1, -1);

		if(strlen($array_index_str) > 0 && array_key_exists($array_name, $value)) {
			$array_index = (int)$array_index_str;
			
			if(is_array($value[$array_name])) {
				if(array_key_exists($array_index, $value[$array_name])) {
					/* Return the double array deref at index */
					return $value[$array_name][$array_index];
				} else {
					/* Array exists, but index doesn't */
					return '';
				}
			} else {
				print("form_get_value(): values[$array_name] is not an array, but an index was specified.");
				exit();
			}
		} else {
			/* Set the name to the array name, and fall through to return the whole array */
			$name = $array_name;
		}
	}
	/* Value is an array, but name is not pointing to an array */
	return $value[$name];
}



function form_select($page_id, $name, $label, $data, &$value, $data_role='', $wide=false, $multi=false, $inline=false, $filterable=false)
{ 
	global $form_disabled;
	$id = $page_id.'_'.$name;

	$select_attrs = '';
	if($data_role != '') $select_attrs .= " data-role=\"$data_role\"";
	if($multi) $select_attrs .= ' multiple="true" data-native-menu="false"';
	if($form_disabled) $select_attrs .= ' disabled="disabled"';
	if($inline) $select_attrs .= ' data-inline="true"';
	if($filterable) $select_attrs .= 'data-native-menu="false" class="filterable-select"';

	/* For a multiselect, $v could be an array */
	$v = form_get_value($name, $value);

	form_label_div_begin($id, $name, $label, $wide);

?>
	<select name="<?=$name?>" id="<?=$id?>" <?=$select_attrs?> >
<?php 		if($data_role == '') { ?>
			<option value="">Choose...</option>
<?php		}
		foreach($data as $key=>$val) {
			if(is_array($val)) $val = $val['name'];
			if(is_array($v)) {
				$sel = in_array($key, $v) ? 'selected="selected"' : '';
			} else {
				$sel = ($v === $key) ? 'selected="selected"' : ''; 
			} ?>
		        <option value="<?=$key?>" <?=$sel?> ><?=$val?></option>
<?php		} ?>
	</select>

<?php	form_label_div_end();

}

function form_multiselect($form_id, $name, $label, $data, &$value)
{
	form_select($form_id, $name, $label, $data, $value, '',false, true);
}

function form_select_optgroup($page_id, $name, $label, $data, &$value)
{
	global $form_disabled;
	$id = $page_id.'_'.$name;
	/* This is so we can pass $u or $p in, and use the name to index into the array */
	$v = form_get_value($name, $value);

	$d = $form_disabled ? ' disabled="disabled"': '';

	form_label_div_begin($id, $name, $label, false);
?>
	
	<select name="<?=$name?>" id="<?=$id?>" <?=$d?> >
	<option value="">Choose...</option>
<?php	foreach($data as $name=>$group) { ?>
		<optgroup label="<?=$name?>">
<?php		foreach($group as $key=>$val) { 
			if(is_array($val)) $val = $val['name'];
			$sel = ($v == $key) ? 'selected="selected"' : ''; ?>
		        <option value="<?=$key?>" <?=$sel?> ><?=$val?></option>
<?php		} ?>
		</optgroup>
<?php	} ?>
	</select>
<?php	form_label_div_end();
}

function form_select_filterable($form_id, $name, $label, $data, &$value)
{
	form_select($form_id, $name, $label, $data, $value, '',false, false, false, true);
}

function form_select_filter($page_id, $name, $label, $data, &$value)
{
	global $form_disabled;
	$id = $page_id.'_'.$name;
	/* This is so we can pass $u or $p in, and use the name to index into the array */
	$v = (is_array($value)) ? $value[$name] : $value;
	$d = $form_disabled ? ' disabled="disabled"': '';

	form_label_div_begin($id, $name, $label, false);
?>
	<input data-type="search" id="<?=$id?>_filter">
	<select name="<?=$name?>" data-filter="true" data-input="#<?=$id?>_filter" id="<?=$id?>" <?=$d?> >
	<option value="">Choose...</option>
<?php	foreach($data as $key=>$val) {
		if(is_array($val)) $val = $val['name'];
		$sel = ($v === $key) ? 'selected="selected"' : ''; ?>
	        <option value="<?=$key?>" <?=$sel?> ><?=$val?></option>
<?php	} ?>
	</select>
<?php	form_label_div_end();
}

function form_lang($page_id, $name, $label, &$value)
{
//	$f = json_decode('"Fran\u00e7ais"');
	$data = array('en' => 'English', 'fr'=>'Fran&#231;ais');
	form_select($page_id, $name, $label, $data, $value);
}

function form_province($page_id, $name, $label, &$value)
{
	$data = array( 'bc' => 'British Columbia', 'yk' => 'Yukon');
	form_select($page_id, $name, $label, $data, $value);
}

function form_textbox($form_id, $name, $label, &$value, $minwords=false, $maxwords=false)
{
	global $form_disabled;
	/* Enabling word counts depends on having an object with ID= {$id}_count so
	 * onchange function in the .js can update it */
	$id = $form_id.'_'.$name;
	/* This is so we can pass $u or $p in, and use the name to index into the array */
	$v = (is_array($value)) ? $value[$name] : $value;
	$cnt = false;
	$hook = '';
	if($minwords !== false || $maxwords !== false) {
		$cnt = true;
		$hook = 'data-word-count="true"';
	}
	$d = $form_disabled ? ' disabled="disabled"': '';
?>
	<div class="ui-field-contain">
		<label for="<?=$id?>" <?=form_inc($name)?>><?=$label?>:</label>
		<textarea rows="8" name="<?=$name?>" id="<?=$id?>" <?=$hook?>  <?=$d?>><?=$v?> </textarea>
	</div>
<?php
	if($cnt == true) {
		$w = str_word_count($v);
		$min = ($minwords > 0) ? "Min: $minwords" : '';
		$max = ($maxwords > 0) ? "Max: $maxwords" : '';
?>
		<div class="ui-field-contain">
			<label></label>
			Word Count: <b><span id="<?=$id.'_count'?>"><?=$w?></span></b> (<?=$min?> <?=$max?>)
		</div>
<?php
	}
}

/* This has a data-alt2 attribute so it is enabled/disabled with the sfiab_form */
function form_submit($form_id, $action, $text = "Save", $saved_text = "Information Saved", $theme='g', $icon="check", $confirm='', $start_disabled=true)
{
	global $form_disabled;
	$attrs = $form_disabled ? ' disabled="disabled"': '';
	if($start_disabled) $attrs .= ' disabled="disabled"';
	if($confirm != '') $attrs .= " data-confirm=\"$confirm\"";
	if($icon != '') $attrs .= " data-icon=\"$icon\"";
?>
	<button type="submit" data-role="button" id="<?=$form_id?>_submit_<?=$action?>" name="action" value="<?=$action?>" data-inline="true" data-theme="<?=$theme?>" data-alt1="<?=$text?>" data-alt2="<?=$saved_text?>" <?=$attrs?> >
		<?=$text?>
	</button>
<?php
}

function form_submit_enabled($form_id, $action, $text = "Save", $saved_text = "Information Saved", $theme='g', $icon="check", $confirm='')
{
	form_submit($form_id, $action, $text, $saved_text, $theme, $icon, $confirm, false);
}

/* This doesn't create a data-alt2 attribute, so it won't be enabled/disabled with the sfiab_form, it's always
 * enabled */
function form_button($form_id, $action, $text = "Save", $theme='g', $icon="check", $confirm='', $attrs='')
{
	global $form_disabled;
	if($form_disabled) $attrs .= ' disabled="disabled"';
	if($icon != '') $attrs .= " data-icon=\"$icon\"";
	if($confirm != '') $attrs .= " data-confirm=\"$confirm\"";
?>
	<button type="submit" data-role="button" id="<?=$form_id?>_submit_<?=$action?>" name="action" value="<?=$action?>" data-inline="true" data-theme="<?=$theme?>" <?=$attrs?> >
		<?=$text?>
	</button>
<?php
}

function form_hidden($form_id, $name, $txt)
{
?>
	<input id="<?=$form_id?>_<?=$name?>" type="hidden" name="<?=$name?>" value="<?=$txt?>" />
<?php
}

function form_file($form_id, $name, $label)
{
	global $form_disabled;
	$d = $form_disabled ? ' disabled="disabled"': '';

	$id = $form_id.'_'.$name;
	$placeholder = $label;
	$d = $form_disabled ? ' disabled="disabled"': '';
	form_label_div_begin($id, $name, $label, false);
?>
	<input id="<?=$id?>" name="<?=$name?>" placeholder="<?=$placeholder?>" data-clear-btn="true" type="file" <?=$d?> >
<?php
	form_label_div_end();
}

function form_begin($form_id, $action, $disable_form=false, $enable_ajax=true, $method = "post")
{
	global $form_form_id, $form_disabled;
	$form_form_id = $form_id;
	$form_disabled = $disable_form;

	/* remove sfiab class from disabled forms so the buttons don't work */
	$attrs = '';
	if($form_disabled) {
		$cl = '';
	} else {
		$cl = 'sfiab_form';
		if($enable_ajax) {
			$cl .= ' sfiab_form_ajax';
		} else {
			$attrs = 'data-ajax="false"';
		}

/*		if($enable_files) {
			$attrs .= ' enctype="multipart/form-data"';
		}*/
	} 
?>
	<form method="<?=$method?>" action="<?=$action?>" id="<?=$form_id?>" class="<?=$cl?>" <?=$attrs?> >
	<input type="hidden" name="action" value="" class="sfiab_form_action" />
<?php
}

function form_end($form_id)
{
	print("</form>");
}

function form_page_begin($page_id, $fields, $error_msg = '', $happy_msg = '', $missing_message = '')
{
	global $form_incomplete_fields;
	global $form_page_id;
	
	$form_page_id = $page_id;
	if($missing_message == '') {
		$missing_message = "This page is incomplete.  Missing information fields are highlighted in red.";
	}

	$form_incomplete_fields = array_merge($form_incomplete_fields, $fields);

	$none = 'style="display:none"';
?>
	<div id="<?=$page_id?>_missing_msg" class="error" <?=(count($fields)==0) ? $none : ''?>>
	<?=$missing_message?>
	</div>
	<div id="<?=$page_id?>_error_msg" class="error" <?=($error_msg=='') ? $none : ''?>>
	<?=$error_msg?>
	</div>
	<div id="<?=$page_id?>_happy_msg" class="happy" <?=($happy_msg=='') ? $none : ''?>>
	<?=$happy_msg?>
	</div>
<?php
}

function form_disable_message($page_id, $closed, $accepted=false)
{
	if($accepted) {
?>		<div class="happy">
		Your signature form has been received.  Information on this page cannot be changed.
		</div>
<?php		return;
	} 
	if($closed) {
?>		<div class="info">
		Registration is closed.  Information on this page cannot be changed.
		</div>
<?php	} 
}

function form_ajax_response_error($status, $error) 
{
	form_ajax_response(array('status'=>$status, 'error'=>$error));
}

function form_ajax_response($response)
{
	$headers = array( 'status', 'missing', 'left_error_count', 'error', 'happy', 'info', 'location');
	if(!is_array($response)) {
		$response = array('status'=>$response);
	}
	foreach($headers as $h) {
		if(array_key_exists($h, $response)) {
			$r[$h] = $response[$h];
		} else {
			$r[$h] = '';
		}
	}

	$r['val'] = array();
	if(array_key_exists('val', $response)) {
		foreach($response['val'] as $k=>$v) {
			$r['val'][] = array($k, $v);
		}
	}

	print(json_encode($r));
}
?>
