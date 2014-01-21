<?php
$form_incomplete_fields = array();

function form_inc($id)
{
	global $form_incomplete_fields;
	if(in_array($id, $form_incomplete_fields)) 
		return "class=\"error\"";
	return "";
}
		

function form_text($page_id, $name, $label, &$value = '', $type='text', $required=false) 
{ 
	if(!in_array($type, array('text', 'tel','date','email','password'))) {
		print("Error 1001: $type\n");
		exit();
	}

	/* This is so we can pass $u or $p in, and use the name to index into the array */
	$v = (is_array($value)) ? $value[$name] : $value;

	$id = $page_id.'_'.$name;
	$placeholder = $label;
	if($type == 'date') $placeholder.= ' (YYYY-MM-DD)';
	
	?>
	<div class="ui-field-contain">
		<label for="<?=$id?>"<?=form_inc($id)?>><?=$label?>:</label>
		<input id="<?=$id?>" name="<?=$name?>" value="<?=$v?>" placeholder="<?=$placeholder?>" data-clear-btn="true" type="<?=$type?>">
	</div>
<?php
}

function form_int($page_id, $name, $label, &$value = '', $min=NULL, $max=NULL) 
{ 
	/* This is so we can pass $u or $p in, and use the name to index into the array */
	$v = (is_array($value)) ? $value[$name] : $value;

	$id = $page_id.'_'.$name;
	$placeholder = $label;
	$minv = ($min === NULL) ? '' : "min=\"$min\"";
	$maxv = ($max === NULL) ? '' : "max=\"$max\"";
?>
	<div class="ui-field-contain">
		<label for="<?=$id?>"><?=$label?>:</label>
		<input id="<?=$id?>" name="<?=$name?>" value="<?=$v?>" placeholder="<?=$placeholder?>" data-clear-btn="true" type="number" <?=$min?> <?=$max?> >
	</div>
<?php
}


function form_radio_h($page_id, $name, $label, $data, &$value) { 
	$id = $page_id.'_'.$name;
	/* This is so we can pass $u or $p in, and use the name to index into the array */
	$v = (is_array($value)) ? $value[$name] : $value;
?>
	<div class="ui-field-contain">
		<label for="<?=$id?>"><?=$label?>:</label>
		<fieldset id="<?=$id?>" data-role="controlgroup" data-type="horizontal" >
<?php
			$x=0;
			foreach($data as $key=>$val) {
				$sel = ($v == $key) ? 'checked="checked"' : ''; ?>
			        <input name="<?=$name?>" id="<?=$name.'-'.$x?>" value="<?=$key?>" <?=$sel?> type="radio">
			        <label for="<?=$name.'-'.$x?>"><?=$val?></label>
<?php				$x++;
			} ?>
		</fieldset>
	</div>
<?php
}

function form_check_group($form_id, $name, $label, $data, &$value)
{
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
?>
	<div class="ui-field-contain">
		<label for="<?=$id?>"><?=$label?>:</label>
		<fieldset id="<?=$id?>" data-role="controlgroup" data-type="horizontal" >
<?php
			$x=0;
			foreach($data as $key=>$val) {
				if(is_array($val)) $val = $val['name'];
				$sel = (in_array($key,$v)) ? 'checked="checked"' : ''; ?>
				
			        <input name="<?=$name?>[]" id="<?=$name.'-'.$x?>" value="<?=$key?>" <?=$sel?> type="checkbox">
			        <label for="<?=$name.'-'.$x?>"><?=$val?></label>
<?php				$x++;
			} ?>
		</fieldset>
	</div>
<?php
}

function form_yesno($page_id, $name, $label, &$value, $wide=false) { 
	$data = array(0 => 'No', 1 => 'Yes');
	form_select($page_id, $name, $label, $data, $value, 'slider', $wide);
}


function form_select($page_id, $name, $label, $data, &$value, $data_role='', $wide=false, $multi=false)
{ 
	$id = $page_id.'_'.$name;
	/* This is so we can pass $u or $p in, and use the name to index into the array */
	$v = (is_array($value)) ? $value[$name] : $value;

	if($data_role != '') 
		$data_role = "data-role=\"$data_role\"";

	$extra_class = $wide ? 'ui-field-contain-wide' : '';

	$mstr = ($multi) ?  'multiple="true"' : '';
?>
	<div class="ui-field-contain <?=$extra_class?>">
		<label for="<?=$id?>"><?=$label?>:</label>
		<select name="<?=$name?>" id="<?=$id?>" <?=$data_role?> <?=$mstr?> data-native-menu="false">
<?php 			if($data_role == '') { ?>
				<option value="">Choose...</option>
<?php			}
			foreach($data as $key=>$val) {
				if(is_array($val)) $val = $val['name'];
				$sel = ($v == $key) ? 'selected="selected"' : ''; ?>
			        <option value="<?=$key?>" <?=$sel?> ><?=$val?></option>
<?php			} ?>
		</select>
	</div>
<?php
}

function form_multiselect($form_id, $name, $label, $data, &$value)
{
	form_select($form_id, $name, $label, $data, $value, '',false, true);
}

function form_select_optgroup($page_id, $name, $label, $data, &$value)
{
	$id = $page_id.'_'.$name;
	/* This is so we can pass $u or $p in, and use the name to index into the array */
	$v = (is_array($value)) ? $value[$name] : $value;

?>
	<div class="ui-field-contain">
		<label for="<?=$id?>"><?=$label?>:</label>
		<select name="<?=$name?>" id="<?=$id?>" >
		<option value="">Choose...</option>
<?php		foreach($data as $name=>$group) { ?>
			<optgroup label="<?=$name?>">
<?php			foreach($group as $key=>$val) { 
				$sel = ($v == $key) ? 'selected="selected"' : ''; ?>
			        <option value="<?=$key?>" <?=$sel?> ><?=$val?></option>
<?php			} ?>
			</optgroup>
<?php		} ?>
		</select>
	</div>
<?php
}

function form_lang($page_id, $name, $label, &$value)
{
	$data = array('en' => 'English', 'fr'=>'Fran�ais');
	form_select($page_id, $name, $label, $data, $value);
}

function form_province($page_id, $name, $label, &$value)
{
	$data = array( 'bc' => 'British Columbia', 'yk' => 'Yukon');
	form_select($page_id, $name, $label, $data, $value);
}

function form_textbox($form_id, $name, $label, &$value)
{
	$id = $form_id.'_'.$name;
	/* This is so we can pass $u or $p in, and use the name to index into the array */
	$v = (is_array($value)) ? $value[$name] : $value;
?>
	<div class="ui-field-contain">
		<label for="<?=$id?>" <?=form_inc($id)?>><?=$label?>:</label>
		<textarea rows="8" name="<?=$name?>" id="<?=$id?>"><?=$v?></textarea>
	</div>
<?php
}

function form_submit($form_id, $action, $text = "Save", $saved_text = "Information Saved", $theme='g')
{
?>
	<button type="submit" data-role="button" id="<?=$form_id?>_submit_<?=$action?>" name="action" value="<?=$action?>" disabled="disabled" data-inline="true" data-icon="check" data-theme="<?=$theme?>" data-alt1="<?=$text?>" data-alt2="<?=$saved_text?>" >
		<?=$text?>
	</button>
<?php
}
function form_button($form_id, $action, $text = "Save", $theme='g', $icon="check")
{
?>
	<button type="submit" data-role="button" id="<?=$form_id?>_submit_<?=$action?>" name="action" value="<?=$action?>" data-inline="true" data-icon="<?=$icon?>" data-theme="<?=$theme?>" >
		<?=$text?>
	</button>
<?php
}

function form_hidden($form_id, $name, $txt)
{
?>
	<input type="hidden" name="<?=$name?>" value="<?=$txt?>" />
<?php
}


function form_begin($form_id, $action, $fields)
{
	global $form_incomplete_fields;
	$missing_message = "This page is incomplete.  Missing information fields are highlighted in red.";

	$ids = array();
	foreach($fields as $f) {
		$ids[] = $form_id.'_'.$f;
	}
	$form_incomplete_fields = array_merge($form_incomplete_fields, $ids);
	$missing_style ='';
	if(count($fields) == 0) 
		$missing_style = 'style="display:none"';

?>
	<div id="<?=$form_id?>_missing_msg" class="error" <?=$missing_style?>>
	<?=$missing_message?>
	</div>
	<div id="<?=$form_id?>_error_msg" class="error" style="display:none">
	</div>
	<div id="<?=$form_id?>_happy_msg" class="happy" style="display:none">
	</div>

	<form action="<?=$action?>" id="<?=$form_id?>" class="sfiab_form">
	<input type="hidden" name="action" value="" class="sfiab_form_action" />
<?php
}

function form_end($form_id)
{
	print("</form>");
}

function form_messages($form_id, $missing_message="This page is incomplete.  Missing information fields are highlighted in red.")
{
?>
	<div id="<?=$form_id?>_missing_msg" class="error" style="display:none">
	<?=$missing_message?>
	</div>
	<div id="<?=$form_id?>_error_msg" class="error" style="display:none">
	</div>
	<div id="<?=$form_id?>_happy_msg" class="happy" style="display:none">
	</div>
<?php
}

function form_scripts($form_id, $fields)
{
?>
	<script>
		// highlight any incomplete fields
<?php 		foreach($fields as $f) { ?>
			$("label[for='<?=$form_id?>_<?=$f?>']").addClass('error');
<?php		}?>
	</script>			
<?php

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

function form_ajax_response($status, $missing_fields=array(), $left_nav_error_counts=array(), 
				$error_text='', $happy_text='', $location='')
{
	$response = array('status'=>$status, 
			'missing' => $missing_fields,
			'left_error_count' => $left_nav_error_counts,
			'error' => $error_text,
			'happy' => $happy_text,
			'location' => $location,
			);
	return json_encode($response);
}
?>
