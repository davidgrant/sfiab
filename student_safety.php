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

$page_id = 's_safety';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

switch($action) {
case 'save':
	$a = array('display1', 'display2', 'display3',
			'institution',
			'electrical1', 'electrical2', 'electrical3', 'electrical4',
			'animals1', 'animals2', 'animals3',
			'bio1', "bio2", "bio3", "bio4", "bio5", "bio6",
			'hazmat1', "hazmat2", "hazmat3", "hazmat4", "hazmat5",
			'mech1', "mech2", "mech3", "mech4", "mech5", "mech6", 'mech7');

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



sfiab_page_begin("Project Safety", $page_id);
?>

<?php

function question($name, $text, $help, $v)
{
	global $page_id;
	$id = $page_id.'_form_'.$name;

	if(is_array($v)) {
		$v = $v[$name];
	}

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


function questionc($name, $text, $help, $v)
{
	global $page_id;
	$id = $page_id.'_form_'.$name;

	if(is_array($v)) {
		$v = $v[$name];
	}

?>
	<li id="<?=$id?>_li" style="white-space:normal" >
		<div style="float:left; width:85%">
		<?=$text?><br/>
		<ul>
<?php		foreach($help as $h) { ?>
			<li><?=$h?></li>
<?php		} ?>		
		</ul></div>
		<div style="float:right; text-align:center; width:15%"  >
<?php
		$sel = ($v === 1) ? 'checked="checked"' : ''; ?>
	        <input name="<?=$name?>" id="<?=$name.'-'.$x?>" value="1" <?=$sel?> type="checkbox">
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
	incomplete_check($mysqli, $fields, $u, $page_id);
	print_r($fields);
	form_page_begin($page_id, $fields, '', '', 'This page is incomplete.  Please complete all questions.');


	
	$form_id = $page_id.'_form';
?>
	<form action="student_safety.php" method="post" data-ajax="false" id="<?=$form_id?>">

<?php 	if(count($fields) == 0) {

?>
	<h3>Safety Information and Forms</h3>

<?php
	$e = $p['safety'];

?>	

		<h4>Documentation Required</h4>
		<ul data-role="listview" data-inset="true">
<?php
	        if($e['bio1'] || $e['hazmat1']) 
			policy('Using Hazardous Materials', 'You are required to have a supervisor who is licensed or certified to handle the hazardous materials used in your project.  Documentation of license or certification will be required at the fair (put it in your log book so you don\'t lose or forget it).');
		else
			policy('None', 'For project safety, no forms or additional documentation are required for your project.  Note that ethics forms may still be required as indicated in the ethics section.');

?>		</ul>
<?php
	}

?>	<h3>Safety Questions</h3>

	<ul data-role="listview" data-inset="true">
	
<?php
	divider('materials', 'Materials');
	question('bio1',  'Does this project involve any potentially hazardous biological agents?', 
		array('Potentially Hazardous Biological Agents include micro-organisms, rDNA, fresh/frozen tissue, blood and body fluids, toxins.'), $answers);
	question('hazmat1',  'Does this project involve hazardous chemicals, explosives, firearms, or other hazardous materials or activities?', 
		array('Many common chemicals used at home are considered hazardous (i.e., poisonous or dangerous, etc) - look for warning labels.'), $answers);
	question('electrical1', 'Does this project use something that produces or uses electricity, other than a laptop computer?',
		array('e.g., a lamp, motor, hand-generator, battery, flashlight, circuit board'), $answers);
	question('animals1', 'Does this project use animals or animal parts?',
		array('e.g., live animals, micro-organisms, snake skin, feathers, bones, hair samples'), $answers);
	question('mech1', 'Will any apparatus used in this project be on display at the fair?',
		array('In other words, will the display have anything at it other than a backboard, logbook, and laptop?'
		), $answers);

	divider('facilities', 'Facilities');
	question('institution',  'Will your project be conducted at or assisted by a Research Institution such as a Hospital, University, College, or Commercial Laboratory?', array(), $answers);

	divider('display', 'Project Display');
	questionc('display1', 'The display will not contain photos of anyone other than myself (and my partner).',
		array('Images from a publicly available source are permitted if the source is credited.','Images of survey or test subjects are not permitted under any circumstances (unless the subject is yourself or your partner).'), $answers);
	questionc('display2', 'The display will not contain photos depicting violence or death of humans or animals.',
		array(), $answers);
	questionc('display3', 'The backboard is free-standing and structurally sound.',array(), $answers);


	divider('electrical', 'Project Display -- Electrical Safety');
	questionc('electrical2', 'All electrical equipment has 3-prong plugs and is CSA approved.',array(), $answers);
	questionc('electrical3', 'There are no wet cell batteries.',
		array('Dry cell batteries are permitted, e.g., alkalines, NiCd, lithium-ion'), $answers);
	questionc('electrical4', 'Any electronic components created or modified for the project conform to the following:',
		array('They use low voltage and current', 'They are in a non-combustable enclosure','An insulating grommet is used where wires enter the enclosure','A pilot light is present to indicate when the power is on.'), $answers);

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


	divider('animals', 'Project Display -- Animals and Animal Safety');
	questionc('animals2', 'The display has no living or dead animals or micro-organisms',
		array(), $answers);
	questionc('animals3', 'The display has no animal products subject to decomposition.',
		array('Items shed naturally by an animal are permitted: safely contained quills, shed snake skin, feathers, hair samples',
			' Items properly prepared and preserved are permitted: tanned pelts and hides, antlers, skeletons or skeletal parts'), $answers);
	
	divider('hazmat', 'Project Display -- Firearms, Explosives, and Hazardous Materials Safety');
	questionc('hazmat2', 'The display has no firearms, ammunition, dangerous goods, or explsovies.',
		array(), $answers);
	questionc('hazmat3', 'The display has no flammable, toxic, or dangerous chemicals.',
		array('e.g., gasoline, kerosene, alcohol, cleaning supplies', 'Water and food colouring is an excellent substitute for any liquid'), $answers);
	questionc('hazmat4', 'The display has less than 1L of water on display.',
		array('We highly recommend substituting water and food colouring for any and all liquids. Note the substitution by marking it with "Simulated X" label'), $answers);
	questionc('hazmat5', 'The display has no prescription drugs or over-the-counter medications.',
		array(), $answers);


	divider('mech', 'Project Display -- Structural, Mechanical, and Fire Safety');
	questionc('mech2', 'All fast, large, or dangerous moving parts on display are fitted with a guard.',
		array('e.g., belts, gears, pulleys, blades'), $answers);
	questionc('mech3', 'All motors on display have a safety shut-off.',
		array(), $answers);
	questionc('mech4', 'The display will not have any pressurized vessels or gas cylinders.',
		array(), $answers);
	questionc('mech5', 'All apparatus fits within the project display or under the table.',
		array(), $answers);
	questionc('mech6', 'All sharp objects are in a protective case.',
		array('e.g., corners of prisms, mirrors, glass, metal plates'), $answers);
	questionc('mech7', 'My display doesn\'t have any open flames or heating devices.',
		array('Examples: candles, lighters, torches, hot plates.'), $answers);
	

/*
	divider('old', 'Old SFIAB questions... let\'s condense these');
	question('211', 'Exhibit will not collapse: It is free-standing, well-balanced, and of solid construction, no more than 1.2m wide (at the widest point), by 0.8 metres deep (at the deepest point) by 3.5 metres from the floor.', array(), $answers);
	question('212', 'All display posters are completely and securely fastened to the exhibit baseboard.', array(), $answers);
	question('213', 'All moving parts are securely affixed and will not separate from the exhibit (ie: gears, pulleys, etc.).', array(), $answers);
	question('214', 'All sharp edges or corners (such as those on prisms or mirrors) are covered.', array(), $answers);
	question('215', 'All hoses and cords required in the exhibit are securely taped and of minimal length.', array(), $answers);
	question('216', 'All pressurized vessels have safety valves.', array(), $answers);
	question('217', 'Exhibit does not contain any compressed gases.', array(), $answers);
	question('218', 'Aisle and area under table will be clear of any debris.', array(), $answers);
	question('219', 'No combustible material is near a heat source.', array(), $answers);
	question('220', 'No open flames are present in the exhibit.', array(), $answers);
	question('221', 'No packing material or any other unnecessary flammable material is present in the exhibit hall.', array(), $answers);
	question('222', 'No burning or smouldering substances are present in the exhibit hall (including cigarettes).', array(), $answers);
	question('223', 'No radioisotopes are present in the exhibit.', array(), $answers);
	question('224', 'No biological toxins, microorganisms, or cultures are displayed in the exhibit. Where such displays are integral to the project content, visual substitutes (ie: photographs) may be used.  (No project will be penalized due to the replacement of hazardous material with innocuous substitutes.) ', array(), $answers);
	question('225', 'No matter subject to decomposition is present in the exhibit.', array(), $answers);
	question('226', 'No live animals are present in the display (but properly housed, non-decomposing animal parts may be displayed (ie: a snake skin)).', array(), $answers);
	question('227', 'If any vertebrate animal is part of an experiment, collection and use thereof must be humane. Such treatment cannot stress the animal or be otherwise deleterious to its health.', array(), $answers);
	question('228', 'No toxic, noxious, or flammable chemicals (including chemical preservatives) are present in the exhibit.', array(), $answers);
	question('229', 'No drugs, whether prescription or over-the-counter, are present in the exhibit.', array(), $answers);
	question('230', 'Where chemicals are required for illustrative purposes, appropriate safe substitutes have been used (ie: water for alcohol), which may be labelled with the intended name followed by \'simulated\' (ie: ether simulated)).  (No project will be penalized due to the replacement of hazardous material with innocuous substitutes.)', array(), $answers);
	question('231', 'Voltages used represent minimal quantities required to run any electrical components of the exhibit.', array(), $answers);
	question('232', 'All electrical components are entirely housed by an enclosure insofar as such remains practical.  Such an enclosure is of a non-combustible material.', array(), $answers);
	question('233', 'All metal parts not intended to carry a current but present in an exhibit that uses electrical components are grounded.', array(), $answers);
	question('234', 'All cords are CSA approved and in good repair (no exposed wires or breaks in insulation).', array(), $answers);
	question('235', 'All cords are three pronged.', array(), $answers);
	question('236', 'An insulating grommet has been installed at the interface of a cord and any electrical component (a grommet keeps the cord from being frayed by the edges of the component housing).', array(), $answers);
	question('237', 'Wet cells (ie: car batteries) have not been used.', array(), $answers);
	question('238', 'Exhibit is capable of being turned off at the end of the viewing period.', array(), $answers);
	question('239', 'No exposed part carries a voltage greater than 36V.', array(), $answers);
	question('240', 'No radiation-producing component is displayed without proper governmental authorization and adherence to governmental radiation safety protocols (exhibits involving voltages above 10kV are considered to be radiation-producing).', array(), $answers);
    
*/
?>
</ul>

	
	<input type="hidden" name="action" value="save"/>
	<button type="submit" data-role="button" id="<?=$form_id?>_submit_save" name="action" value="save" data-inline="true" data-icon="check" data-theme="g" >
		Save
	</button>
	
		
	</form>
	<script>

		safety_hide(["animals2", "animals3"]);
		safety_hide(["electrical2", "electrical3", "electrical4"]);
		safety_hide(["bio2", "bio3", "bio4", "bio5", "bio6" ]);
		safety_hide(["hazmat2", "hazmat3", "hazmat4", "hazmat5" ]);
		safety_hide(["mech2", "mech3", "mech4", "mech5", "mech6" ,'mech7' ]);
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

//			alert(human1);

			var ar = ["electrical2", "electrical3", "electrical4"];
			if(el1 == 1) {
				safety_show(['electrical']);
				safety_show(ar);
			} else {
				if(do_uncheck) safety_uncheck(ar);
				safety_hide(['electrical']);
				safety_hide(ar);
			}

			var ar = ["animals2", "animals3"];
			if(ani1 == 1) {
				safety_show(['animals']);
				safety_show(ar);
			} else {
				if(do_uncheck) safety_uncheck(ar);
				safety_hide(ar);
				safety_hide(['animals']);
			}

			var ar = ["bio2", "bio3", "bio4", "bio5", "bio6" ];
			if(bi1 == 1) {
				safety_show(ar);
				safety_show(['bio']);
			} else {
				if(do_uncheck) safety_uncheck(ar);
				safety_hide(['bio']);
				safety_hide(ar);
			}

			var ar = ["hazmat2", "hazmat3", "hazmat4", "hazmat5"  ];
			if(haz1 == 1) {
				safety_show(ar);
				safety_show(['hazmat']);
			} else {
				if(do_uncheck) safety_uncheck(ar);
				safety_hide(['hazmat']);
				safety_hide(ar);
			}
			var ar = ["mech2", "mech3", "mech4", "mech5" , "mech6", 'mech7' ];
			if(mech1 == 1) {
				safety_show(ar);
				safety_show(['mech']);
			} else {
				if(do_uncheck) safety_uncheck(ar);
				safety_hide(['mech']);
				safety_hide(ar);
			}

		}


		$( "#<?=$form_id?> :input" ).change(function() {
			safety_update(1);
		});


	</script>


</div></div>





<?php
sfiab_page_end();
?>
