<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('student'));

$u = user_load($mysqli);

$page_id = 's_safety';

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



sfiab_page_begin("Student Safety", $page_id);

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

?>		<h3>Safety Questions</h3>

<ul data-role="listview" data-inset="true">
	
<?php

	divider('materials', 'Materials');
	question('hazardbio',  'Does this project involve any potentially hazardous biological agents?', array('Potentially Hazardous Biological Agents include microorganisms, rDNA, fresh/frozen tissue, blood and body fluids.'), $answers);
	question('hazardother',  'Does this project involve hazardous chemicals, explosives, firearms, or other hazardous materials or activities?', array('Many common chemicals used at home are considered hazardous (ie poisonous or dangerous, etc) - look for warning labels.'), $answers);

	divider('facilities', 'Facilities');
	question('institution',  'Will your project be conducted at or assisted by a Research Institution such as a Hospital, University, College, or Commercial Laboratory?', array(), $answers);

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
    
?>
</ul>

		<input type="hidden" name="action" value="save"/>
	</form>
	<script>
		$("#<?=$page_id?>_humansurvey1_li").hide();
		$("#<?=$page_id?>_humanfood_li").hide();
		$("#<?=$page_id?>_humanfood1_li").hide();
		$("#<?=$page_id?>_humanfood2_li").hide();
		$("#<?=$page_id?>_humanfood6_li").hide();
		$("#<?=$page_id?>_humantest1_li").hide();
		$("#<?=$page_id?>_humanfood3_li").hide();
		$("#<?=$page_id?>_humanfood4_li").hide();
		$("#<?=$page_id?>_humanfood5_li").hide();
		$("#<?=$page_id?>_animal_vertebrate_li").hide();
		$("#<?=$page_id?>_animal_ceph_li").hide();
		$("#<?=$page_id?>_animal_tissue_li").hide();
		$("#<?=$page_id?>_animal_drug_li").hide();
		$("#<?=$page_id?>_humanfooddrug_li").hide();
		$("#<?=$page_id?>_humanfoodlow1_li").hide();
		$("#<?=$page_id?>_humanfoodlow2_li").hide();


<?php 	foreach($fields as $f) { ?>
			$("label[for='<?=$page_id?>_<?=$f?>']").addClass('error');
<?php		}?>


		$( "#<?=$page_id?>_form :input" ).change(function() {
			var human1 = $("#<?=$page_id?>_human1 input:checked").val();
			var humanfood1 = $("#<?=$page_id?>_humanfood1 input:checked").val();
			var humanfood2 = $("#<?=$page_id?>_humanfood2 input:checked").val();
			var animals = $("#<?=$page_id?>_animals input:checked").val();
			var animal_vertebrate = $("#<?=$page_id?>_animal_vertebrate input:checked").val();

//			alert(human1);

			if(human1 == 1) {
				$("#<?=$page_id?>_humansurvey1_li").show();
				$("#<?=$page_id?>_humanfood_li").show();
				$("#<?=$page_id?>_humanfood1_li").show();
				$("#<?=$page_id?>_humanfood2_li").show();
				$("#<?=$page_id?>_humanfood6_li").show();
				$("#<?=$page_id?>_humantest1_li").show();
			} else {
				$("#<?=$page_id?>_humansurvey1_li").hide();
				$("#<?=$page_id?>_humanfood_li").hide();
				$("#<?=$page_id?>_humanfood1_li").hide();
				$("#<?=$page_id?>_humanfood2_li").hide();
				$("#<?=$page_id?>_humanfood6_li").hide();
				$("#<?=$page_id?>_humantest1_li").hide();
			}

			if(humanfood1 == 1 || humanfood2 == 1) {
				$("#<?=$page_id?>_humanfood3_li").show();
				$("#<?=$page_id?>_humanfood4_li").show();
				$("#<?=$page_id?>_humanfood5_li").show();
				$("#<?=$page_id?>_humanfooddrug_li").show();
				$("#<?=$page_id?>_humanfoodlow1_li").show();
				$("#<?=$page_id?>_humanfoodlow2_li").show();
			} else {
				$("#<?=$page_id?>_humanfood3_li").hide();
				$("#<?=$page_id?>_humanfood4_li").hide();
				$("#<?=$page_id?>_humanfood5_li").hide();
				$("#<?=$page_id?>_humanfooddrug_li").hide();
				$("#<?=$page_id?>_humanfoodlow1_li").hide();
				$("#<?=$page_id?>_humanfoodlow2_li").hide();
			}

			if(animals == 1) {
				$("#<?=$page_id?>_animal_vertebrate_li").show();
				if(animal_vertebrate == 0) {
					$("#<?=$page_id?>_animal_ceph_li").show();
				} else {
					$("#<?=$page_id?>_animal_ceph_li").hide();
				}
				$("#<?=$page_id?>_animal_tissue_li").show();
				$("#<?=$page_id?>_animal_drug_li").show();
			} else {
				$("#<?=$page_id?>_animal_vertebrate_li").hide();
				$("#<?=$page_id?>_animal_ceph_li").hide();
				$("#<?=$page_id?>_animal_tissue_li").hide();
				$("#<?=$page_id?>_animal_drug_li").hide();
			}
				
		});


	</script>

	<?=form_scripts('student_safety.php', $page_id, $fields);?>


</div>





<?php
sfiab_page_end();
?>
