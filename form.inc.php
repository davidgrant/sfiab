<?php

function form_text($page_id, $name, $label, &$value = '', $type='text', $required=false) 
{ 
	if(!in_array($type, array('text', 'tel','date','email'))) {
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
		<label for="<?=$id?>"><?=$label?>:</label>
		<input id="<?=$id?>" name="<?=$name?>" value="<?=$v?>" placeholder="<?=$placeholder?>" data-clear-btn="true" type="<?=$type?>">
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

function form_yesno($page_id, $name, $label, &$value) { 
	$data = array(0 => 'No', 1 => 'Yes');
	form_select($page_id, $name, $label, $data, $value, 'slider');
}


function form_select($page_id, $name, $label, $data, &$value, $data_role='')
{ 
	$id = $page_id.'_'.$name;
	/* This is so we can pass $u or $p in, and use the name to index into the array */
	$v = (is_array($value)) ? $value[$name] : $value;

	if($data_role != '') 
		$data_role = "data-role=\"$data_role\"";

?>
	<div class="ui-field-contain">
		<label for="<?=$id?>"><?=$label?>:</label>
		<select name="<?=$name?>" id="<?=$id?>" <?=$data_role?> >
<?php 			if($data_role == '') { ?>
				<option value="">Choose...</option>
<?php			}
			foreach($data as $key=>$val) {
				$sel = ($v == $key) ? 'selected="selected"' : ''; ?>
			        <option value="<?=$key?>" <?=$sel?> ><?=$val?></option>
<?php			} ?>
		</select>
	</div>
<?php
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
	$data = array('en' => 'English', 'fr'=>'Français');
	form_select($page_id, $name, $label, $data, $value);
}

function form_textbox($page_id, $name, $label, &$value)
{
	$id = $page_id.'_'.$name;
	/* This is so we can pass $u or $p in, and use the name to index into the array */
	$v = (is_array($value)) ? $value[$name] : $value;
?>
	<div class="ui-field-contain">
		<label for="<?=$id?>"><?=$label?>:</label>
		<textarea rows="8" name="<?=$name?>" id="<?=$id?>"><?=$v?></textarea>
	</div>
<?php
}

function form_submit($form_id, $text)
{
?>
	<button id="<?=$form_id?>_form_submit" disabled="disabled" type="submit" data-inline="true" data-icon="check" data-theme="g">
		<?=$text?>
	</button>
<?php
}

function form_incomplete_error_message($page_id, $fields)
{
	$error_class = (count($fields) == 0) ? 'error_hidden' : '';
?>	
	<div id="<?=$page_id?>_error_msg" class="error <?=$error_class?>">
		This page is incomplete.  Missing information fields are highlighted in red.
	</div>
<?php
}

function form_scripts($action, $page_id, $fields)
{
	$form_id = $page_id . "_form";
	$button_id = $page_id . "_form_submit";
?>
	<script>
		// highlight any incomplete fields
<?php 		foreach($fields as $f) { ?>
			$("label[for='<?=$page_id?>_<?=$f?>']").addClass('error');
<?php		}?>

		// Attach a submit handler to the form
		$( "#<?=$form_id?>" ).submit(function( event ) {
		
			// Stop form from submitting normally
			event.preventDefault();
			$.post( "<?=$action?>", $('#<?=$form_id?>').serialize(), function( data ) {
		                $('#<?=$button_id?>').attr('disabled', true);
			        $('#<?=$button_id?>').text('Information Saved');

				// Clear all errors
				$("#<?=$form_id?> label").removeClass('error');

				if(data.length > 0) {
					$("#left_nav_<?=$page_id?> span").show();
					$("#left_nav_<?=$page_id?> span").text(data.length);
					$("#<?=$page_id?>_error_msg").removeClass('error_hidden');

					for (var i = 0; i < data.length; i++) {
						var $label = $("label[for='<?=$page_id?>_"+data[i]+"']");
						$label.addClass('error');
					}
				} else {
					// No error fields 
					$("#<?=$page_id?>_error_msg").addClass('error_hidden');
					$("#left_nav_<?=$page_id?> span").hide();
				}

				return false;
			}, "json");
			// Stop any more actions
			return false;
		});

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
?>
