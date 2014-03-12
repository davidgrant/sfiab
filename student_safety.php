<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('student'));

$u = user_load($mysqli);
$p = project_load($mysqli, $u['s_pid']);
$closed = sfiab_registration_is_closed($u);

$page_id = 's_safety';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

switch($action) {
case 'save':
	if($closed) exit();
	$a = array('display1', 'display2', 'display3',
			'institution',
			'electrical1', 'electrical2', 'electrical3', 'electrical4',
			'animals1', 'animals2', 'animals3',
			'bio1', "bio2", "bio3", "bio4", "bio5", "bio6",
			'hazmat1', "hazmat2", "hazmat3", "hazmat4", "hazmat5",
			'food1', "food2", "food3", "food4", "food5",
			'mech1', "mech2", "mech3", "mech4", "mech5", "mech6", 'mech7',
			'agree');

	foreach($a as $f) {
		if(!array_key_exists($f, $_POST)) {
			$p['safety'][$f] = NULL;
		} else {
			post_bool($p['safety'][$f], $f);
		}
	}
	project_save($mysqli, $p);

	incomplete_check($mysqli, $ret, $u, $page_id, true);
	break;
}


incomplete_check($mysqli, $incomplete_fields, $u, $page_id);

$help = '<p>Please complete all the questions on this page about safety';

sfiab_page_begin("Project Safety", $page_id, $help);
?>

<?php

function question($name, $text, $help, $v)
{
	global $page_id;
	global $incomplete_fields;
	global $closed;

	$id = $page_id.'_form_'.$name;

	if(is_array($v)) {
		$v = $v[$name];
	}

	$err = in_array($name, $incomplete_fields) ? 'border-color:red; border-width:2px;': '';
	$d = $closed ? 'disabled="disabled"' : '';

	$data = array(0=>'No', 1=>'Yes');
?>
	<li id="<?=$id?>_li" style="white-space:normal; <?=$err?>" >
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
			        <input name="<?=$name?>" id="<?=$name.'-'.$x?>" value="<?=$key?>" <?=$sel?> type="radio" <?=$d?>>
			        <label for="<?=$name.'-'.$x?>"><?=$val?></label>
<?php				$x++;
			} ?>
			</fieldset>
		</div>
	</li>
<?php
}


function questionc($name, $text, $help, $v)
{
	global $page_id;
	global $incomplete_fields;
	global $closed;

	$id = $page_id.'_form_'.$name;

	if(is_array($v)) {
		$v = $v[$name];
	}

	$err = in_array($name, $incomplete_fields) ? 'border-color:red; border-width:2px;': '';
	$d = $closed ? 'disabled="disabled"' : '';

?>
	<li id="<?=$id?>_li" style="white-space:normal; <?=$err?> " >
		<div style="float:left; width:85%">
		<?=$text?><br/>
		<ul>
<?php		foreach($help as $h) { ?>
			<li><?=$h?></li>
<?php		} ?>		
		</ul></div>
		<div style="float:right; text-align:center; width:15%"  >
		<fieldset id="<?=$id?>" >
<?php
		$sel = ($v === 1) ? 'checked="checked"' : ''; ?>
	        <input name="<?=$name?>" id="<?=$name?>" value="1" <?=$sel?> type="checkbox" <?=$d?> />
		</fieldset>
		
		</div>
	</li>
<?php
}

function divider($name, $text) 
{
	global $page_id;
	$id = $page_id.'_form_'.$name;
?>	<li data-role="list-divider" id="<?=$id?>_li"><?=$text?></li>
<?php
}

function policy($name, $text, $link = '') 
{
	if($link == '') {
?>		<li style="white-space:normal"><b><?=$name?></b><br/><?=$text?></li>
<?php
	} else { ?>
		<li style="white-space:normal">
			<a href="<?=$link?>">
				<b><?=$name?></b><br/><?=$text?>
			</a>
		</li>
<?php
	}
}

?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	$answers = $p['safety'];
//	print_r($fields);
	form_page_begin($page_id, $incomplete_fields, '', '', 'This page is incomplete.  Please complete all questions.');
	form_disable_message($page_id, $closed, $u['s_accepted']);

	$d = $closed ? 'disabled="disabled"' : '';
	
	$form_id = $page_id.'_form';
?>
	<form action="student_safety.php" method="post" data-ajax="false" id="<?=$form_id?>">

<?php 	if(count($incomplete_fields) == 0 || (count($incomplete_fields) == 1 && $incomplete_fields[0] == 'agree') ) {
?>
		<h3>Safety Information Documentation Required at the Fair</h3>

		<ul data-role="listview" data-inset="true">
<?php
	        if($answers['bio1'] || $answers['hazmat1']) {
			policy('Using Hazardous Materials', 'You are required to have a supervisor who is licensed or certified to handle the hazardous materials used in your project.  Documentation of license or certification will be required at the fair (put it in your log book so you don\'t lose or forget it).');
			$forms = true;
		} else {
			$forms = false;
			policy('None', 'For project safety, no forms or additional documentation are required for your project.  Note that ethics forms may still be required as indicated in the ethics section.');
		}	

?>		</ul>

		<h3>Agreement of Safety Requirements</h3>
		<ul data-role="listview" data-inset="true">
<?php
		if($forms == true) {
			questionc('agree', 'Please check the box on the right to acknowledge that you have collected the above forms or documentation and will bring them to the fair.',
					array('Failure to bring the necessary forms could result in disqualification.',
						'Failure to provide correct information about your project could result in disqualification.'), $answers);
		} else {
			questionc('agree', 'Please check the box on the right to acknowledge the information here is correct and that you will only bring materials agreed to below to the fair.',
					array('If anything about your project changes, you must adjust your answers here accordingly.',
						'Failure to provide correct information about your project could result in disqualificaiton.'), $answers);
		}
?>
		</ul>
		<button type="submit" data-role="button" id="<?=$form_id?>_submit_save" name="action" value="save" data-inline="true" data-icon="check" data-theme="g" <?=$d?> >
			Save
		</button>
		
		<br/>
		<hr/>
		<br/>

<?php
	}

?>	<h3>Safety Questions</h3>

	<p>In these questions "the display", or "on display", or "at the display" refers to the entire project area.  That includes the area on the table, under the table, behind the backboard, etc.

	<ul data-role="listview" data-inset="true">
	
<?php
	divider('materials', 'Materials');
	question('bio1',  'Does this project involve any potentially hazardous biological agents?', 
		array('Potentially Hazardous Biological Agents include micro-organisms, rDNA, fresh/frozen tissue, blood and body fluids, toxins.'), $answers);
	question('hazmat1',  'Does this project involve hazardous chemicals, explosives, firearms, or other hazardous materials or activities?', 
		array('Many common chemicals used at home are considered hazardous (i.e., poisonous or dangerous, etc) - look for warning labels.'), $answers);
	question('mech1', 'Will any materials or apparatus used in this project be on display at the fair?',
		array('In other words, will the display have anything at it other than a backboard, logbook, and laptop?',
			'Remember, "on display" also includes items under the table'), $answers);
	question('electrical1', 'Will any electrical or electricity-producing device, other than a laptop computer, be at the display?',
		array('e.g., plug-in devices, battery-operated devices, hand-generators, circuit boards'), $answers);
	question('animals1', 'Does this project use animals or animal parts?',
		array('e.g., live animals, micro-organisms, snake skin, feathers, bones, hair samples'), $answers);
	question('food1', 'Does this project use any liquids, non-hazardous chemicals, or materials subject to decomposition (including all food)?',
		array('e.g., pop, rubbing alcohol, toothpaste, cheese, candy, laundry detergent, dish soap, fruits, vegetables'), $answers);

	divider('facilities', 'Facilities');
	question('institution',  'Will your project be conducted at or assisted by a Research Institution such as a Hospital, University, College, or Commercial Laboratory?', array(), $answers);

	divider('display', 'Project Display');
	questionc('display1', 'The display will not contain photos of anyone other than myself (and my partner).',
		array('Images from a publicly available source are permitted if the source is credited.','Images of survey or test subjects are not permitted under any circumstances (unless the subject is yourself or your partner).'), $answers);
	questionc('display2', 'The display will not contain photos depicting violence or death of humans or animals.',
		array(), $answers);
	questionc('display3', 'The backboard is free-standing and structurally sound.',array(), $answers);


	divider('bio', 'Project Display -- Hazardous Biological Agent Safety');
	questionc('bio2', 'The display has no cell, tissue, or blood samples.',
		array('Samples on sealed microscope slides are permitted'), $answers);
	questionc('bio3', 'The display has no plants or plant tissues.',
		array('Use photographs or plastic plants as display substitutes'), $answers);
	questionc('bio4', 'The display has no soil containing organic material.',
		array('e.g., Topsoil is not permitted, but sand is usually permitted.'), $answers);
	questionc('bio5', 'The display has no active or dead cultures, including petri dishes or culture plates with media.',
		array('A photo in a petri dish is a good substitute'), $answers);
	questionc('bio6', 'The display has no spores or pollen e.g., in a ziploc bag.',
		array('A photo is a good substitute'), $answers);

	divider('hazmat', 'Project Display -- Firearms, Explosives, and Hazardous Materials Safety');
	questionc('hazmat2', 'The display has no firearms, ammunition, dangerous goods, or explsovies.',
		array(), $answers);
	questionc('hazmat3', 'The display has no flammable, toxic, or dangerous chemicals.',
		array('e.g., gasoline, kerosene, alcohol, cleaning supplies', 'Water and food colouring MUST be used as a substitute for any liquid.','Even better, leave all liquids at home.  Our judges will not penalize any project for not having materials on-hand.'), $answers);
	questionc('hazmat4', 'The display has less than 1L of water at the display.',
		array('Water and food colouring MUST be used as a substitute for any and all liquids.','You can note the substitution by marking it with a "Simulated X" label'), $answers);
	questionc('hazmat5', 'The display has no prescription drugs or over-the-counter medications.',
		array(), $answers);

	divider('mech', 'Project Display -- Structural, Mechanical, and Fire Safety');
	questionc('mech2', 'All fast, large, or dangerous moving parts at the display are fitted with a guard.',
		array('e.g., belts, gears, pulleys, blades'), $answers);
	questionc('mech3', 'All motors at the display have a safety shut-off.',
		array(), $answers);
	questionc('mech4', 'The display will not have any pressurized vessels or gas cylinders.',
		array(), $answers);
	questionc('mech5', 'All apparatus fits within the project display or under the table.',
		array(), $answers);
	questionc('mech6', 'All sharp objects are in a protective case.',
		array('e.g., corners of prisms, mirrors, glass, metal plates'), $answers);
	questionc('mech7', 'My display doesn\'t have any open flames or heating devices.',
		array('Examples: candles, lighters, torches, hot plates.'), $answers);
		
	divider('electrical', 'Project Display -- Electrical Safety');
	questionc('electrical2', 'All electrical equipment has 3-prong plugs and is CSA approved.',array(), $answers);
	questionc('electrical3', 'There are no wet cell batteries.',
		array('Dry cell batteries are permitted, e.g., alkalines, NiCd, lithium-ion'), $answers);
	questionc('electrical4', 'Any electronic components created or modified for the project conform to the following:',
		array('They use low voltage and current', 'They are in a non-combustable enclosure','An insulating grommet is used where wires enter the enclosure','A pilot light is present to indicate when the power is on.'), $answers);


	divider('animals', 'Project Display -- Animals and Animal Safety');
	questionc('animals2', 'The display has no living or dead animals or micro-organisms',
		array(), $answers);
	questionc('animals3', 'The display has no animal products subject to decomposition.',
		array('Items shed naturally by an animal are permitted: safely contained quills, shed snake skin, feathers, hair samples',
			' Items properly prepared and preserved are permitted: tanned pelts and hides, antlers, skeletons or skeletal parts'), $answers);
	
	divider('food', 'Project Display -- Liquids, Food, and other Chemicals');
	questionc('food2', 'No liquids other than water (and food colouring) are at the display.',
		array('Water and food colouring MUST be used as a substitute for any and all liquids.','You can note the substitution by marking it with a "Simulated X" label','Even better, leave all liquids at home.  Our judges will not penalize any project for not having materials on-hand.'), $answers);
	questionc('food3', 'Less than one litre of water is at the display.',
		array(), $answers);
	questionc('food4', 'No food items or items subject to decomposition are at the display.',
		array('Empty food packages are permitted.'), $answers);
	questionc('food5', 'No gels or other chemicals are at the display.',
		array('e.g., dish soap, toothpaste'), $answers);
		

	

?>
</ul>

	
	<input type="hidden" name="action" value="save"/>
	<button type="submit" data-role="button" id="<?=$form_id?>_submit_save" name="action" value="save" data-inline="true" data-icon="check" data-theme="g" <?=$d?>>
		Save
	</button>
	
		
	</form>
	<script>

		safety_hide(["animals2", "animals3"]);
		safety_hide(["electrical2", "electrical3", "electrical4"]);
		safety_hide(["bio2", "bio3", "bio4", "bio5", "bio6" ]);
		safety_hide(["hazmat2", "hazmat3", "hazmat4", "hazmat5" ]);
		safety_hide(["mech2", "mech3", "mech4", "mech5", "mech6" ,"mech7" ]);
		safety_hide(["food2", "food3", 'food4', 'food5' ]);
		safety_hide(['bio', 'hazmat', 'animals', 'electrical', 'mech']);
		safety_update(0);



		function safety_uncheck(ar) 
		{
			for(var i=0; i<ar.length; i++) {
				var e = $("#<?=$form_id?>_"+ar[i]+" input:checked");
				e.prop("checked", 0);
				e.checkboxradio("refresh");
			}
		}
		function safety_hide(ar)
		{
			for(var i=0; i<ar.length; i++) {
				$("#<?=$form_id?>_"+ar[i]+"_li").hide();
			}
		}

		function safety_show(ar)
		{
			for(var i=0; i<ar.length; i++) {
				$("#<?=$form_id?>_"+ar[i]+"_li").show();
			}
		}

		function safety_update(do_uncheck)
		{
			var el1 = $("#<?=$form_id?>_electrical1 input:checked").val();
			var bi1 = $("#<?=$form_id?>_bio1 input:checked").val();
			var haz1 = $("#<?=$form_id?>_hazmat1 input:checked").val();
			var ani1 = $("#<?=$form_id?>_animals1 input:checked").val();
			var mech1 = $("#<?=$form_id?>_mech1 input:checked").val();
			var food1 = $("#<?=$form_id?>_food1 input:checked").val();

			var ar = ['electrical1', 'food1', 'animals1'];
			if(mech1 == 1) {
				safety_show(ar);
			} else {
				if(do_uncheck) safety_uncheck(ar);
				safety_hide(ar);
			}
			var ar = ["electrical2", "electrical3", "electrical4"];
			if(el1 == 1 && mech1 == 1) {
				safety_show(['electrical']);
				safety_show(ar);
			} else {
				if(do_uncheck) safety_uncheck(ar);
				safety_hide(['electrical']);
				safety_hide(ar);
			}

			var ar = ["animals2", "animals3"];
			if(ani1 == 1 && mech1 == 1) {
				safety_show(['animals']);
				safety_show(ar);
			} else {
				if(do_uncheck) safety_uncheck(ar);
				safety_hide(ar);
				safety_hide(['animals']);
			}

			var ar = ["bio2", "bio3", "bio4", "bio5", "bio6" ];
			if(bi1 == 1 && mech1 == 1) {
				safety_show(ar);
				safety_show(['bio']);
			} else {
				if(do_uncheck) safety_uncheck(ar);
				safety_hide(['bio']);
				safety_hide(ar);
			}

			var ar = ["hazmat2", "hazmat3", "hazmat4", "hazmat5"  ];
			if(haz1 == 1 && mech1 == 1) {
				safety_show(ar);
				safety_show(['hazmat']);
			} else {
				if(do_uncheck) safety_uncheck(ar);
				safety_hide(['hazmat']);
				safety_hide(ar);
			}
			var ar = ["mech2", "mech3", "mech4", "mech5" , "mech6", "mech7" ];
			if(mech1 == 1) {
				safety_show(ar);
				safety_show(['mech']);
			} else {
				if(do_uncheck) safety_uncheck(ar);
				safety_hide(['mech']);
				safety_hide(ar);
			}
			var ar = ["food2", "food3", "food4", "food5" ];
			if(food1 == 1 && mech1 == 1) {
				safety_show(ar);
				safety_show(['food']);
			} else {
				if(do_uncheck) safety_uncheck(ar);
				safety_hide(['food']);
				safety_hide(ar);
			}

		}


		$( "#<?=$form_id?> :input" ).change(function(event) {
			var input_e = $(event.target);
			var input_name = input_e.attr('name');
			if(input_name != 'agree') {
				safety_uncheck(['agree']);
			}
			safety_update(1);
		});


	</script>


</div></div>





<?php
sfiab_page_end();
?>
