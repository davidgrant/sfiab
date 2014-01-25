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



sfiab_page_begin("Project Ethics", $page_id);

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
	$fields = incomplete_check($mysqli, $u, $page_id);
?>
	<form action="#" id="<?=$page_id?>_form">
<?php
		$relations=array('parent'=>"Parent",'legalguardian'=>"Legal Guardian",'grandparent'=>"Grandparent",
				'familyfriend'=>"Family Friend", 'other'=>"Other");

?>		<h3>Ethics Questions</h3>

<ul data-role="listview" data-inset="true">
	
<?php
	divider('human', 'Human Participation');
	question('human1',  'Does your project involve "Human Participants"?', 	array('Human Participants are any people such as other students, family members, or even yourself.','Participation can include taking surveys, doing tests, using products, or even just being observed.'), $answers);
	
	question('humansurvey1',  'Does the project involve a Survey of the Participants, a Test of Skill, or an Observation of Behaviour?', array('Surveys may be verbal, written, or done on computer or over the internet.'), $answers);
	question('humantest1',  'Does this project involve any invasive procedures?', array('Invasive procedures including taking blood samples, tissues samples, or samples of any other bodily fluids.'), $answers);

	divider('humanfood', 'Human Participation involving Food');
	question('humanfood1', 'Does this project involve the Participant taking and/or consuming Food, Drink, Medicine or Drugs?', array(), $answers);
	question('humanfood2', 'Does this project involve smelling or tasting any substances or products?', array(), $answers);
	question('humanfood6', 'Does this project involve a product or substance that is applied to the skin or absorbed through the skin?', array(), $answers);
        question('humanfood5', 'Does this project involve ingesting a Natural Health Product regulated by Health Canada?', array('These products are identified by a Health Canada Natural Product Number (NPN), Homeopathic Medicine Number (DIN-HM), or Exemption Number (EN) and are listed in the Health Canada Natural Health Product Database.'), $answers);
        question('humanfood4', 'Does this project involve foods, drinks or products for which a health benefit is claimed?', array(), $answers);
        question('humanfood3', 'Does this project involve Caffeinated Drinks?', array('Caffeine is found in soft drinks, coffee, tea, iced coffee, energy drinks, and many other food and drink products.'), $answers);
        question('humanfooddrug', 'Does this project involve consuming, tasting or smelling any product defined as a drug?', array('Definition of a "drug" includes any substance or mixture of substances for use in:','i) the diagnosis, treatment, mitigation or prevention of a disease, disorder, abnormal physical state, or its symptoms, in humans or animals;','(ii)	restoring, correcting, or modifying organic functions in humans beings or animals;','(iii)	disinfection in premises in which food is manufactured, prepared or kept.'), $answers);
	question('humanfoodlow1', 'Is the Food or Drink to be used in this project not usually considered as Food or Drink for human beings, or not commonly encountered in everyday life as Food or Drink?', array('Answer \'No\' only if the product being consumed or tested is generally manufactured or sold for use as food or drink for human beings.','(For more information see YSC Policy 4.1.1.1 section 3.2).'), $answers);
        question('humanfoodlow2', 'Does the food or drink to be used in this project contain additives that exceed the Recommended Daily Allowance Guidelines (RDI) normally associated with those foods ?', array(), $answers);

	divider('animals', 'Animal Participation');
	question('animals',  'Does this project involve any non-human animals or animal tissue?', array(), $answers);
        question('animal_vertebrate', 'Does this project involve any Vertebrate animals ?', array('Vertebrate animals include fish, amphibians, reptiles, birds, and mammals'), $answers);
        question('animal_ceph', 'Does this project include the use of Cephalopods (such as squid, octopus or cuttlefish)?', array(), $answers);
        question('animal_tissue', 'Does this project involve animal parts or tissues, including organs, plasma, serum, or embryos, from Vertebrate Animals?', array(), $answers);
        question('animal_drug', 'Does this project involve the use of drugs on animals?', array('See the definition of Drugs in YSC Policy 4.1.2 Section 10'), $answers);

   
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

	<?=form_scripts('student_ethics.php', $page_id, $fields);?>


</div></div>





<?php
sfiab_page_end();
?>
