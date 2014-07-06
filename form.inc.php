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
		return 'data-clear-btin="true"';
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


function form_radio_h($form_id, $name, $label, $data, &$value, $wide=false)
{ 
	global $form_disabled;

	$id = $form_id.'_'.$name;
	/* This is so we can pass $u or $p in, and use the name to index into the array */
	$v = (is_array($value)) ? $value[$name] : $value;
	$extra_class = $wide ? 'ui-field-contain-wide' : '';
	$d = $form_disabled ? ' disabled="disabled"': '';

	if($label !== NULL) { ?>
	<div class="ui-field-contain <?=$extra_class?>">
		<label for="<?=$id?>" <?=form_inc($name)?>><?=$label?>:</label>
<?php	} ?>

		<fieldset id="<?=$id?>" data-role="controlgroup" data-type="horizontal" >
<?php
			$x=0;
			foreach($data as $key=>$val) {
				$sel = ($v === $key) ? 'checked="checked"' : ''; ?>
			        <input name="<?=$name?>" id="<?=$id.'-'.$x?>" value="<?=$key?>" <?=$sel?> type="radio" <?=$d?> >
			        <label for="<?=$id.'-'.$x?>"><?=$val?></label>
<?php				$x++;
			} ?>
		</fieldset>
<?php	if($label !== NULL) { ?>
	</div>
<?php	}
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

	if($label !== NULL) { ?>
		<div class="ui-field-contain <?=$extra_class?>">
			<label for="<?=$id?>" <?=form_inc($name)?>><?=$label?>:</label>
<?php	} ?>
			<fieldset id="<?=$id?>" data-role="controlgroup" data-type="horizontal" >
<?php			$x=0;
			foreach($data as $key=>$val) {
				if(is_array($val)) $val = $val['name'];
				$sel = (in_array($key,$v)) ? 'checked="checked"' : ''; ?>
				
			        <input name="<?=$name?>[]" id="<?=$name.'-'.$x?>" value="<?=$key?>" <?=$sel?> type="checkbox" <?=$d?> >
			        <label for="<?=$name.'-'.$x?>"><?=$val?></label>
<?php				$x++;
			} ?>
			</fieldset>

<?php	if($label !== NULL) { ?>
		</div>
<?php	}
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
	$data = array(0 => 'No', 1 => 'Yes');
	if(!$slider ) {
		form_radio_h($form_id, $name, $label, $data, $value, $wide);
	} else {
	        form_select($form_id, $name, $label, $data, $value, 'slider', $wide);
	}
}

function form_get_value(&$name, &$value) 
{
	if(!is_array($value)) return $value;

	$p = strpos($name, '[');
	if($p !== false) {
		/* Expect the name is either in the form:  j_pref_div[0] */
		$array_name = substr($name, 0, $p);
		$array_index = (int)substr($name, $p+1, -1);

		if(array_key_exists($array_name, $value)) {
			
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
		}
	}
	/* Value is an array, but name is not pointing to an array */
	return $value[$name];
}



function form_select($page_id, $name, $label, $data, &$value, $data_role='', $wide=false, $multi=false, $inline=false)
{ 
	global $form_disabled;
	$id = $page_id.'_'.$name;


	if($data_role != '') 
		$data_role = "data-role=\"$data_role\"";

	$extra_class = $wide ? 'ui-field-contain-wide' : '';
	$mstr = ($multi) ?  'multiple="true" data-native-menu="false"' : '';
	$d = $form_disabled ? ' disabled="disabled"': '';
	$d .= $inline ? 'data-inline="true"' : '';

	$v = form_get_value($name, $value);

	if($label !== NULL) { ?>
		<div class="ui-field-contain <?=$extra_class?>">
		<label for="<?=$id?>" <?=form_inc($name)?>><?=$label?>:</label>
<?php	} ?>
		<select name="<?=$name?>" id="<?=$id?>" <?=$data_role?> <?=$mstr?> <?=$d?> >
<?php 			if($data_role == '') { ?>
				<option value="">Choose...</option>
<?php			}
			foreach($data as $key=>$val) {
				if(is_array($val)) $val = $val['name'];
				$sel = ($v === $key) ? 'selected="selected"' : ''; ?>
			        <option value="<?=$key?>" <?=$sel?> ><?=$val?></option>
<?php			} ?>
		</select>
<?php	if($label !== NULL) { ?>
		</div>
<?php	}
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

	if($label !== NULL) { ?>
		<div class="ui-field-contain">
		<label for="<?=$id?>" <?=form_inc($name)?>><?=$label?>:</label>
<?php	} ?>
	
		<select name="<?=$name?>" id="<?=$id?>" <?=$d?> >
		<option value="">Choose...</option>
<?php		foreach($data as $name=>$group) { ?>
			<optgroup label="<?=$name?>">
<?php			foreach($group as $key=>$val) { 
				if(is_array($val)) $val = $val['name'];
				$sel = ($v == $key) ? 'selected="selected"' : ''; ?>
			        <option value="<?=$key?>" <?=$sel?> ><?=$val?></option>
<?php			} ?>
			</optgroup>
<?php		} ?>
		</select>
<?php	if($label !== NULL) { ?>
		</div>
<?php
	}
}

function form_select_filter($page_id, $name, $label, $data, &$value)
{
	global $form_disabled;
	$id = $page_id.'_'.$name;
	/* This is so we can pass $u or $p in, and use the name to index into the array */
	$v = (is_array($value)) ? $value[$name] : $value;
	$d = $form_disabled ? ' disabled="disabled"': '';

	if($label !== NULL) { ?>
		<div class="ui-field-contain">
		<label for="<?=$id?>" <?=form_inc($name)?>><?=$label?>:</label>
<?php	} ?>
		<input data-type="search" id="<?=$id?>_filter">
		<select name="<?=$name?>" data-filter="true" data-input="#<?=$id?>_filter" id="<?=$id?>" <?=$d?> >
		<option value="">Choose...</option>
<?php		foreach($data as $key=>$val) {
			if(is_array($val)) $val = $val['name'];
			$sel = ($v === $key) ? 'selected="selected"' : ''; ?>
		        <option value="<?=$key?>" <?=$sel?> ><?=$val?></option>
<?php		} ?>
		</select>
<?php	if($label !== NULL) { ?>
		</div>
<?php
	}
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

function form_submit($form_id, $action, $text = "Save", $saved_text = "Information Saved", $theme='g')
{
	global $form_disabled;
	$d = $form_disabled ? ' disabled="disabled"': '';
?>
	<button type="submit" data-role="button" id="<?=$form_id?>_submit_<?=$action?>" name="action" value="<?=$action?>" disabled="disabled" data-inline="true" data-icon="check" data-theme="<?=$theme?>" data-alt1="<?=$text?>" data-alt2="<?=$saved_text?>" <?=$d?> >
		<?=$text?>
	</button>
<?php
}
function form_button($form_id, $action, $text = "Save", $theme='g', $icon="check")
{
	global $form_disabled;
	$d = $form_disabled ? ' disabled="disabled"': '';
?>
	<button type="submit" data-role="button" id="<?=$form_id?>_submit_<?=$action?>" name="action" value="<?=$action?>" data-inline="true" data-icon="<?=$icon?>" data-theme="<?=$theme?>" <?=$d?> >
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

function form_begin($form_id, $action, $disable_form=false)
{
	global $form_form_id, $form_disabled;
	$form_form_id = $form_id;
	$form_disabled = $disable_form;

	/* remove sfiab class from disabled forms so the buttons don't work */
	$cl = $form_disabled ? '' : 'sfiab_form';
?>
	<form action="<?=$action?>" id="<?=$form_id?>" class="<?=$cl?>">
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

function form_scripts_no_ajax($action, $page_id, $fields)
{
	$form_id = $page_id . "_form";
	$button_id = $page_id . "_form_submit";
?>
	<script>
		// highlight any incomplete fields
<?php 		foreach($fields as $f) { ?>
			$("label[for='<?=$page_id?>_<?=$f?>']").addClass('error');
<?php		}?>

		$( "#<?=$form_id?> :input" ).change(function() {
	               $('#<?=$button_id?>').removeAttr('disabled');
		       $('#<?=$button_id?>').text('Save');
		});

		$( "#<?=$form_id?> :input" ).keyup(function() {
	               $('#<?=$button_id?>').removeAttr('disabled');
		       $('#<?=$button_id?>').text('Save');
		});

	</script>			
<?php
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
