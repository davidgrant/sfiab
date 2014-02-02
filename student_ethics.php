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

$page_id = 's_ethics';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

switch($action) {
case 'save':
	$a = array('human1', 'humansurvey1', 'humantest1', 
		'humanfood1', 'humanfood2', 'humanfood6', 'humanfood5', 'humanfood4', 'humanfood3', 
	        'humanfooddrug', 'humanfoodlow1', 'humanfoodlow2', 
		'animals', 'animal_vertebrate', 'animal_ceph', 'animal_tissue', 'animal_drug', 'agree' );

	foreach($a as $f) {
		if(!array_key_exists($f, $_POST)) {
			$p['ethics'][$f] = NULL;
		} else {
			post_bool($p['ethics'][$f], $f);
		}
	}
	project_save($mysqli, $p);

	incomplete_check($mysqli, $ret, $u, $page_id, true);
	break;
}


incomplete_check($mysqli, $incomplete_fields, $u, $page_id);

$help = '<p>Please complete all the questions on this page about ethics';

sfiab_page_begin("Project Ethics", $page_id, $help);

function question($name, $text, $help, $v)
{
	global $page_id;
	global $incomplete_fields;
	$id = $page_id.'_form_'.$name;

	if(is_array($v)) {
		$v = $v[$name];
	}
	$err = in_array($name, $incomplete_fields) ? 'border-color:red; border-width:2px;': '';

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
	global $incomplete_fields;
	$id = $page_id.'_form_'.$name;

	if(is_array($v)) {
		$v = $v[$name];
	}

	$err = in_array($name, $incomplete_fields) ? 'border-color:red; border-width:2px;': '';

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
	        <input name="<?=$name?>" id="<?=$name?>" value="1" <?=$sel?> type="checkbox"/>
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
	$e = $p['ethics'];
	form_page_begin($page_id, $incomplete_fields, '', '', 'This page is incomplete.  Please complete all questions.');


	$form_id = $page_id.'_form';
?>
	<form action="student_ethics.php" method="post" data-ajax="false" id="<?=$form_id?>">

	<h3>Ethics Information and Forms</h3>

	<p>This page will guide you through several questions to determine the
	relative National policies regarding ethics and the use of humans and
	animals in your project.

	<p>It will tell you there are things you should have done before
	starting your project.  Since you've probably already started, please
	follow the requirements here as best you can.  

	<p>You can email any questions to ethics@gvrsf.ca

<?php 	if(count($incomplete_fields) == 0 || (count($incomplete_fields) == 1 && $incomplete_fields[0] == 'agree') ) {
?>
		<h4>Before You Start: Policies</h4> 

		<p>Here is a list of the Youth Science Canada policies you should be aware of for your project:
		<ul data-role="listview" data-inset="true">
<?php
		$form1 = true;
		
		if(!$e['human1'] && !$e['animals']) {
			policy('None', 'It does not appear that your project is subject to any special ethics requirements.');
			$form1 = false;
		}

		if($e['human1']) {
			policy('YSC Policy 4.1.1.1 - Low Risk', 'Human Participation is governed by YSC Policy 4.1.1.1 (Low Risk) and YSC Policy 4.1.1.2 (Significant Risk).' , 'http://youthscience.ca/policy/participation-humans-research-low-risk');
			policy('YSC Policy 4.1.1.2 - Significant Risk', '', 'http://youthscience.ca/participation-humans-research-significant-risk');
		}

		if($e['animals'])
			policy('YSC Policy 4.1.2', 'Use of Animals in a project is governed by YSC Policy 4.1.2, which can be found here:','http://www.youthscience.ca/policy/use-animals-research');

		if($e['humansurvey1'] && !$e['humanfood1'] && !$e['humanfood2'] && !$e['humantest1'] ) 
			policy('Survey Low Risk', 'Most Surveys, Skill Tests, and Observations of Behaviour are considered Low Risk (See YSC Policy 4.1.1.1 Section 3.1).');

		if($e['humanfooddrug'])
			policy('Food and Drug Significant Risk', 'This Drug related project is considered a Significant Risk project (See YSC Policy 4.1.1.2 Section 3.2). Significant Risk Drug must be carried out under professional supervision at a laboratory with its own internal Ethics Review Committee, such as a university or hospital laboratory (See YSC Policy 4.1.1.2 Section 3.2b).');

		if($e['humanfood4'] || $e['humanfoodlow1'] || $e['humanfoodlow2']) 
			policy('Food or Drink Significant Risk', 'This Food or Drink related project is considered a Significant Risk project (See YSC Policy 4.1.1.2 Section 3.4). Significant Risk Ingestion Projects must be carried out under professional supervision at a laboratory with its own internal Ethics Review Committee, such as a university or hospital laboratory (See YSC Policy 4.1.1.2 Section 3.4e).');

		if($e['humanfood1'] && !$e['humanfood3']  && !$e['humanfood4'] && !$e['humanfood5'] && !$e['humanfooddrug'] && !$e['humanfoodlow1'] && !$e['humanfoodlow2']) 
			policy('Food and Drink Low Risk', 'This Food or Drink related project may meet the requirements for a Low Risk project (See YSC Policy 4.1.1.1 Section 3.2).');

		if($e['humanfood3']) 
			policy('Caffeinated Drinks', 'Caffeinated Drinks are subject to Special Rules Caffeinated Drinks are only permitted in Science Fair Projects within strict limits based on caffeine content and the age of the participants (see Caffeine Guidelines and also YSC Policy 4.1.1.1 section 3.3).');
		
		if($e['human1'])
			policy('Letter of Information', 'Your Participants must be provided with a Letter of Information that provides details on your Project (See YSC Policy 4.1.1.1 Section 4.4). Click on this item for a blank Letter of Information template', 'http://cwsf.youthscience.ca/sites/default/files/documents/cwsf/letter_of_information_blank_en.doc');


		if($e['humansurvey1'] && !$e['humanfood1'] && !$e['humanfood2'] && !$e['humantest1'] ) 
			policy('Informed Consent for Surveys', 'Participants must give informed consent, but for Surveys this can be assumed by completion of the Survey itself (See YSC Policy 4.1.1.1 Section 4.3).');

		if($e['humanfood1'] || $e['humanfood2'] || $e['humantest1']) {
			policy('Informed Consent', 'Participants must give informed consent and complete a written Permission Form (See YSC Policy 4.1.1.1 Section 4.5). A blank Informed Consent Permission Form template can be downloaded here.','http://cwsf.youthscience.ca/sites/default/files/documents/cwsf/informed_consent_blank_en.doc');
			policy('Informed Consent Under 18', 'The Parents or Guardians of Participants under 18 must also provide their written consent (See YSC Policy 4.1.1.1 Section 4.2).');
			policy('Participants on Medications', 'Participants must not be taking prescription medications, to minimize the risk of drug-food interactions (See YSC Policy 4.1.1.1 Section 3.2b).');
		}
		if($e['animal_drug'])
			policy('Animal Drugs', 'Drugs may only be used in an experiment if carried out at a Hospital, University, Medical or similar Laboratory under the direction of a Scientific Supervisor.  See YSC Policy 4.1.2 "Use of Animals in Research" Section 10.');
		
		if($e['animal_tissue']) 
			policy('Animal Tissues', 'Animal tissues and parts may only be obtained and used in a project under very specific rules.  Make sure you comply with YSC Policy 4.1.2 "Use of Animals in Research"  Sections 8.2 and 8.3.');
		
		if($e['animal_vertebrate'] || $e['animal_ceph'])
			policy('Vertebrate Animals or Cephalopods', 'Vertebrate animals or cephalopods may only be used in Science Fair Projects under very specific rules and conditions - make sure you comply with YSC Policy 4.1.2 "Use of Animals in Research" Section 8.1.');

		if($e['human1'] && !$e['humanfood3'] && !$e['humanfood4'] && !$e['humanfood5'] && !$e['humantest1'] && !$e['humanfooddrug'] && !$e['humanfoodlow1'] && !$e['humanfoodlow2']) 
			policy('Adult Supervisor Review', 'Your Adult Supervisor will need to review your experiment before you start, confirm that you meet the above requirements, ensure that it does not put the participants at risk either physically or emotionally, and then sign Form 4.1A to confirm.');

		if($e['humanfood4'] || $e['humanfooddrug'] || $e['humanfoodlow1'] || $e['humantest1'] || $e['humanfoodlow2']) {
			policy('Adult Supervisor Review', 'Your Adult Supervisor will need to review your experiment before you start, confirm that you meet the above requirements, ensure that it does not put the participants at risk either physically or emotionally, and then sign Form 4.1B to confirm.');
			policy('Scientific Supervisor', 'You may need a Scientific Supervisor to review and approve your project, and sign Form 4.1B.');
			policy('Ethics Committee Review', 'Your School or Regional Science Fair Ethics Committee must review and confirm that the project meets the requirements of YSC Policy 4.1.1.2, and sign Form 4.1B.');
		}

?>
		</ul>

		<h4>Before You Start: Forms</h4>		

		<ul data-role="listview" data-inset="true">
<?php
		$forms2 = true;
		if(!$e['human1'] && !$e['humansurvey1'] && !$e['humanfood1'] && !$e['humanfood2'] && !$e['humantest1'] && !$e['animal_vertebrate'] && !$e['animal_ceph'] && !$e['animal_tissue'] && !$e['animal_drug']) {
			policy('None', 'It does not appear that you require any ethics or scientific review forms for your project.');
			$forms2 = false;
		}

		if( ($e['human1'] && !$e['humanfood3'] && !$e['humanfood4'] && !$e['humanfood5'] && !$e['humantest1'] && !$e['humanfooddrug'] && !$e['humanfoodlow1'] && !$e['humanfoodlow2']) 
				|| ($e['humansurvey1'] && !$e['humanfood1'] && !$e['humanfood2'] && !$e['humantest1']) ) 
			policy('Participation of Humans - Low Risk', 'You will need to complete YSC Form 4.1A "Participation of Humans - Low Risk". This form can be found here', 
				'http://main.youthscience.ca/sites/default/files/documents/ysc/forms/4.1A_Humans_Low_Risk_0.pdf');

			
		if( $e['humanfood4'] || $e['humanfooddrug'] || $e['humanfoodlow1'] || $e['humantest1'] || $e['humanfoodlow2']) 
			policy('Participation of Humans - Significant Risk', 'You will need to complete YSC Form 4.1B "Participation of Humans - Significant Risk". This form can be found here', 
				'http://main.youthscience.ca/sites/default/files/documents/ysc/forms/4.1B_Humans_Significant_Risk_0.pdf');

		if ($e['animal_vertebrate'] || $e['animal_ceph'] || $e['animal_tissue'] )
			policy('Use of Animals', 'Projects involving vertebrate animals, cephalopods, animal embryos, or animal tissues must complete YSC Form 4.1C Animals - Approval, signed by the student, the Adult Supervisor, the Scientific Supervisor, and approved by the school\'s Ethics Committee. This form can be found here.',
				'http://main.youthscience.ca/sites/default/files/documents/ysc/forms/4.1C_Animals_0.pdf');

		
//        rules_form_ysc_3: [$e['hazardbio'] or $e['hazardother']] 
//		Projects using potentially hazardous materials or devices must have a Designated Adult Supervisor, and a Designated Supervisor Form must be completed before experimentation starts. You can download the Designated Supervisor Form from here:   http://basef.ca/sites/default/files/DesignatedSupervisorFormYSF3%5Beditable%5D.pdf
?>
		</ul>
		<h3>Agreement of Ethics Requirements</h3>
		<ul data-role="listview" data-inset="true">
<?php
		if($form1 == true || $forms2 == true) {
			questionc('agree', 'Please check the box on the right to acknowledge that you have read the above policies and filled out the above forms and will bring them to the fair.',
					array('Failure to bring the necessary forms could result in disqualification.',
						'Failure to provide correct information about your project could result in disqualification.'), $e);
		} else {
			questionc('agree', 'Please check the box on the right to acknowledge that the information here is correct.',
					array('If anything about your project changes, you must adjust your answers here accordingly.','Failure to provide correct information about your project could result in disqualificaiton.'), $e);
		}
?>
		</ul>
		<button type="submit" data-role="button" id="<?=$form_id?>_submit_save" name="action" value="save" data-inline="true" data-icon="check" data-theme="g" >
			Save
		</button>
		<br/>
		<hr/>
		<br/>
<?php		
	}

?>	<h3>Ethics Questions</h3>

	<ul data-role="listview" data-inset="true">
	
<?php
	divider('human', 'Human Participation');
	question('human1',  'Does your project involve "Human Participants"?', 	array('Human Participants are any people such as other students, family members, or even yourself.','Participation can include taking surveys, doing tests, using products, or even just being observed.'), $e);
	
	question('humansurvey1',  'Does the project involve a Survey of the Participants, a Test of Skill, or an Observation of Behaviour?', array('Surveys may be verbal, written, or done on computer or over the internet.'), $e);
	question('humantest1',  'Does this project involve any invasive procedures?', array('Invasive procedures including taking blood samples, tissues samples, or samples of any other bodily fluids.'), $e);

	divider('humanfood', 'Human Participation involving Food');
	question('humanfood1', 'Does this project involve the Participant taking and/or consuming Food, Drink, Medicine or Drugs?', array(), $e);
	question('humanfood2', 'Does this project involve smelling or tasting any substances or products?', array(), $e);
	question('humanfood6', 'Does this project involve a product or substance that is applied to the skin or absorbed through the skin?', array(), $e);
        question('humanfood5', 'Does this project involve ingesting a Natural Health Product regulated by Health Canada?', array('These products are identified by a Health Canada Natural Product Number (NPN), Homeopathic Medicine Number (DIN-HM), or Exemption Number (EN) and are listed in the Health Canada Natural Health Product Database.'), $e);
        question('humanfood4', 'Does this project involve foods, drinks or products for which a health benefit is claimed?', array(), $e);
        question('humanfood3', 'Does this project involve Caffeinated Drinks?', array('Caffeine is found in soft drinks, coffee, tea, iced coffee, energy drinks, and many other food and drink products.'), $e);
        question('humanfooddrug', 'Does this project involve consuming, tasting or smelling any product defined as a drug?', array('Definition of a "drug" includes any substance or mixture of substances for use in:','i) the diagnosis, treatment, mitigation or prevention of a disease, disorder, abnormal physical state, or its symptoms, in humans or animals;','(ii)	restoring, correcting, or modifying organic functions in humans beings or animals;','(iii)	disinfection in premises in which food is manufactured, prepared or kept.'), $e);
	question('humanfoodlow1', 'Is the Food or Drink to be used in this project not usually considered as Food or Drink for human beings, or not commonly encountered in everyday life as Food or Drink?', array('Answer \'No\' only if the product being consumed or tested is generally manufactured or sold for use as food or drink for human beings.','(For more information see YSC Policy 4.1.1.1 section 3.2).'), $e);
        question('humanfoodlow2', 'Does the food or drink to be used in this project contain additives that exceed the Recommended Daily Allowance Guidelines (RDI) normally associated with those foods ?', array(), $e);

	divider('animal', 'Animal Participation');
	question('animals',  'Does this project involve any non-human animals or animal tissue?', array(), $e);
        question('animal_vertebrate', 'Does this project involve any Vertebrate animals ?', array('Vertebrate animals include fish, amphibians, reptiles, birds, and mammals'), $e);
        question('animal_ceph', 'Does this project include the use of Cephalopods (such as squid, octopus or cuttlefish)?', array(), $e);
        question('animal_tissue', 'Does this project involve animal parts or tissues, including organs, plasma, serum, or embryos, from Vertebrate Animals?', array(), $e);
        question('animal_drug', 'Does this project involve the use of drugs on animals?', array('See the definition of Drugs in YSC Policy 4.1.2 Section 10'), $e);

?>
</ul>

	
	<input type="hidden" name="action" value="save"/>
	<button type="submit" data-role="button" id="<?=$form_id?>_submit_save" name="action" value="save" data-inline="true" data-icon="check" data-theme="g" >
		Save
	</button>
	
		
	</form>
	<script>
		$("#<?=$form_id?>_humansurvey1_li").hide();
		$("#<?=$form_id?>_humanfood_li").hide();
		$("#<?=$form_id?>_humanfood1_li").hide();
		$("#<?=$form_id?>_humanfood2_li").hide();
		$("#<?=$form_id?>_humanfood6_li").hide();
		$("#<?=$form_id?>_humantest1_li").hide();
		$("#<?=$form_id?>_humanfood3_li").hide();
		$("#<?=$form_id?>_humanfood4_li").hide();
		$("#<?=$form_id?>_humanfood5_li").hide();
		$("#<?=$form_id?>_animal_vertebrate_li").hide();
		$("#<?=$form_id?>_animal_ceph_li").hide();
		$("#<?=$form_id?>_animal_tissue_li").hide();
		$("#<?=$form_id?>_animal_drug_li").hide();
		$("#<?=$form_id?>_humanfooddrug_li").hide();
		$("#<?=$form_id?>_humanfoodlow1_li").hide();
		$("#<?=$form_id?>_humanfoodlow2_li").hide();
		ethics_update(0);

		function ethics_uncheck(ar) 
		{
			for(var i=0; i<ar.length; i++) {
				var e = $("#<?=$form_id?>_"+ar[i]+" input:checked");
				e.prop("checked", 0);
				e.checkboxradio("refresh");
			}
		}
		function ethics_hide(ar)
		{
			for(var i=0; i<ar.length; i++) {
				$("#<?=$form_id?>_"+ar[i]+"_li").hide();
			}
		}

		function ethics_show(ar)
		{
			for(var i=0; i<ar.length; i++) {
				$("#<?=$form_id?>_"+ar[i]+"_li").show();
			}
		}

		function ethics_update(do_uncheck)
		{
			var human1 = $("#<?=$form_id?>_human1 input:checked").val();
			var humanfood1 = $("#<?=$form_id?>_humanfood1 input:checked").val();
			var humanfood2 = $("#<?=$form_id?>_humanfood2 input:checked").val();
			var animals = $("#<?=$form_id?>_animals input:checked").val();
			var animal_vertebrate = $("#<?=$form_id?>_animal_vertebrate input:checked").val();

//			alert(human1);

			if(human1 == 1) {
				ethics_show(["humansurvey1", "humanfood", "humanfood1", "humanfood2", "humanfood6", "humantest1"]);
			} else {
				if(do_uncheck) ethics_uncheck(["humansurvey1", "humanfood1", "humanfood2", "humanfood6", "humantest1"]);
				ethics_hide(["humansurvey1", "humanfood", "humanfood1", "humanfood2", "humanfood6", "humantest1"]);
			}

			if(human1 == 1 && (humanfood1 == 1 || humanfood2 == 1)) {
				ethics_show(['humanfood3', 'humanfood4', 'humanfood5', 'humanfooddrug', 'humanfoodlow1', 'humanfoodlow2']);
			} else {
				if(do_uncheck) ethics_uncheck(['humanfood3', 'humanfood4', 'humanfood5', 'humanfooddrug', 'humanfoodlow1', 'humanfoodlow2']);
				ethics_hide(['humanfood3', 'humanfood4', 'humanfood5', 'humanfooddrug', 'humanfoodlow1', 'humanfoodlow2']);
			}

			if(animals == 1) {
				ethics_show(['animal_vertebrate', 'animal_tissue','animal_drug']);
				if(animal_vertebrate == 0) {
					ethics_show(['animal_ceph']);
				} else {
					if(do_uncheck) ethics_uncheck(['animal_ceph']);
					ethics_hide(['animal_ceph']);
				}
			} else {
				if(do_uncheck) ethics_uncheck(['animal_vertebrate', 'animal_ceph', 'animal_tissue','animal_drug']);
				ethics_hide(['animal_vertebrate', 'animal_ceph', 'animal_tissue','animal_drug']);
			}
		}


		$( "#<?=$form_id?> :input" ).change(function(event) {
			var input_e = $(event.target);
			var input_name = input_e.attr('name');
			if(input_name != 'agree') {
				ethics_uncheck(['agree']);
			}
			ethics_update(1);
		});


	</script>


</div></div>





<?php
sfiab_page_end();
?>
