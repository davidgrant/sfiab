<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('student'));

$u = user_load($mysqli);

$page_id = 's_ethics';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

$fields = array('');

switch($action) {
case 'save':
	foreach($fields as $f) {
		if(!array_key_exists($f, $u)) {
			/* Key doesn't exist, user is injecting their own keys? */
			print("Error 1005: $f");
			exit();
		}
		/* Since 'sex' is a radiobutton, it's only included if there's a checked value */
		if(array_key_exists($f, $_POST)) {
			/* Save it to the user */
			$u[$f] = $_POST[$f];
		} 
	}

	user_save($mysqli, $u);

	$ret = incomplete_fields($mysqli, $page_id, $u, true);
	print(json_encode($ret));
	exit();
}



sfiab_page_begin("Student Emergency Contact", $page_id);

function question($name, $text, $help, $v)
{
	global $page_id;
	$id = $page_id.'_'.$name;

	$data = array(0=>'No', 1=>'Yes');
?>
	<li id="<?=$id?>_li" style="white-space:normal" >
		<div style="float:left; width:85%">
		<?=$text?><br/>
		<ul>
<?php		foreach($help as $h) { ?>
			<li><?=$h?></li>
<?php		} ?>		
		</ul></div>
		<div style="float:right; width:15%"  >
			<fieldset id="<?=$id?>" data-role="controlgroup" data-type="horizontal" data-mini="true" >
<?php			$x=0;
			foreach($data as $key=>$val) {
				$sel = ($v === $key) ? 'checked="checked"' : ''; ?>
			        <input name="<?=$name?>" id="<?=$name.'-'.$x?>" value="<?=$key?>" <?=$sel?> type="radio">
			        <label for="<?=$name.'-'.$x?>"><?=$val?></label>
<?php				$x++;
			} ?>
			</fieldset>
		</div>
	</li>
<?php
}

function divider($name, $text) 
{
	global $page_id;
	$id = $page_id.'_'.$name;
?>	<li data-role="list-divider" id="<?=$id?>_li"><?=$text?></li>
<?php
}

?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	$answers = array();
	$fields = incomplete_fields($mysqli, $page_id, $u);
	form_incomplete_error_message($page_id, $fields);
?>
	<form action="#" id="<?=$page_id?>_form">
<?php
		$relations=array('parent'=>"Parent",'legalguardian'=>"Legal Guardian",'grandparent'=>"Grandparent",
				'familyfriend'=>"Family Friend", 'other'=>"Other");

?>		<h3>Ethics Questions</h3>

<ul data-role="listview" data-inset="true">
	
<?php
	divider('human', 'Human Participation');
	question('human1',  'Does your project involve "Human Participants"?', array('Human Participants are any people such as other students, family members, or even yourself.','Participation can include taking surveys, doing tests, using products, or even just being observed.'), $answers);
	
	question('humansurvey1',  'Does the project involve a Survey of the Participants, a Test of Skill, or an Observation of Behaviour?', array(), $answers);
	question('humantest1',  'Does this project involve any invasive procedures?', array(), $answers);

	divider('humanfood', 'Human Participation involving Food');
	question('humanfood1',  'Does this project involve the Participant taking and/or consuming Food, Drink, Medicine or Drugs?', array(), $answers);
	question('humanfood2',  'Does this project involve smelling or tasting any substances or products?', array(), $answers);
	question('humanfood6',  'Does this project involve a product or substance that is applied to the skin or absorbed through the skin?', array(), $answers);

	divider('animals', 'Animal Participation');
	question('animals',  'Does this project involve any non-human animals or animal tissue?', array(), $answers);

	divider('safety', 'Safety');
	question('hazardbio',  'Does this project involve any potentially hazardous biological agents?', array(), $answers);
	question('hazardother',  'Does this project involve hazardous chemicals, explosives, firearms, or other hazardous materials or activities?', array(), $answers);
	question('institution',  'Will your project be conducted at or assisted by a Research Institution such as a Hospital, University, College, or Commercial Laboratory?', array(), $answers);

    
?>
</ul>

		<input type="hidden" name="action" value="save"/>
	</form>
	<script>
<?php 	foreach($fields as $f) { ?>
			$("label[for='<?=$page_id?>_<?=$f?>']").addClass('error');
<?php		}?>


		$( "#<?=$page_id?>_human1" ).change(function() {
			var value = $(this).val();

			if(value == 0) {
			  	/* Disable questions */
				$("#<?=$page_id?>_humansurvey1_li").hide();
			} else {
				$("#<?=$page_id?>_humansurvey1_li").show();
			}
		});


	</script>

	<?=form_scripts('student_ethics.php', $page_id, $fields);?>


</div>





<?php
sfiab_page_end();
?>
