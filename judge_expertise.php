<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('isef.inc.php');
$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('judge'));

$u = user_load($mysqli);

$page_id = 'j_expertise';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

$fields = array('j_pref_div1', 'j_pref_div2', 'j_pref_div3', 'j_pref_cat', 
		'j_years_school','j_years_regional','j_years_national');

switch($action) {
case 'save':

	$sa_only = NULL;
	post_bool($sa_only, 'j_special_award_only');

	if($sa_only) {
	} else {
		$u['j_sa'] = NULL;
		post_int($u['j_pref_div1'], 'j_pref_div1');
		post_int($u['j_pref_div2'], 'j_pref_div2');
		post_int($u['j_pref_div3'], 'j_pref_div3');
		post_int($u['j_pref_cat'], 'j_pref_cat');
		post_int($u['j_years_school'], 'j_years_school');
		post_int($u['j_years_regional'], 'j_years_regional');
		post_int($u['j_years_national'], 'j_years_national');
	}

	user_save($mysqli, $u);

	$ret = incomplete_fields($mysqli, $page_id, $u, true);
	print(json_encode($ret));
	exit();
}

$help = '
<ul><li><b>Sponsor Judge</b> - If you represent a sponsor of a specific special award, then choose \'Yes\' here and select which special award(s) you will be judging.
<li><b>School/District</b> - Years of judging experience at the school or district level
<li><b>Regional</b> - Years of judging experience at regional fairs (the GVRSF is a regional fair)
<li><b>National</b> - Years of judging experience at the national (CWSF or ISEF) fair.
<li><b>Div 1,2,3</b> - Choose the top three divisions you feel comfortable
judging in.  We use this to match projects for you to judge.  We will also
include projects in the same general area with slightly less priority if there
aren\'t perfect matches available.  That is, if you select Biochemistry--Medicinal, you may also be matched with other Biochemistry projects.
<li><b>Age Category</b> - You may choose an age category you prefer to judge.  This is not a guarantee you will judge this age group, but we will try.
</ul>';

sfiab_page_begin("Expertise", $page_id, $help);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	$fields = incomplete_fields($mysqli, $page_id, $u);
	form_incomplete_error_message($page_id, $fields);

	$cats = categories_load($mysqli);
	$isef_data = isef_get_div_names();

	$cats_data = array('0'=>'No Preference');
	foreach($cats as $cid=>$c) {
		$cats_data[$cid] = $c['name'];
	}

	if($u['j_sa'] === NULL) {
		$sa_only = false;
		$sa_style = "style=\"display:none\"";
		$normal_style = "";
	} else {
		$sa_only = true ;
		$sa_style = "";
		$normal_style = "style=\"display:none\"";
	}

?>
	<form action="#" id="<?=$page_id?>_form">
		<h3>Sponsor Judges</h3>
<?php
		form_yesno($page_id, 'j_special_award_only', "Do you represent a sponsor of a special award?", $sa_only, true);
		
//		form_sa($page_id, 'j_dinner', "Will you be attending the Judge's Dinner (5-6pm on judging day)?", $u, true);
?>
		<div id="j_expertise_sa" <?=$sa_style?> >
		Note: Our chief judge will double-check with anyone who selects 'Yes' here to ensure they
		are a sponsor for a special award.  If you're not sure then you're probably not a Sponsor judge.

		SA selector
		</div>


		<div id="j_expertise_normal" <?=$normal_style?> >
		<h3>Years of Judging Experience</h3>
		Enter how many years of judging experience you have at each level of science fair competitions:
<?php

		form_int($page_id, 'j_years_school', "School/District", $u, 0, 100);
		form_int($page_id, 'j_years_regional', "Regional", $u, 0, 100);
		form_int($page_id, 'j_years_national', "National", $u, 0, 100);
?>
		<h3>Judging Preferences</h3>
		Select your top three detailed divisions to judge and an age category preference if you have one.
<?php

		form_select_optgroup($page_id, 'j_pref_div1', "Detailed Division 1", $isef_data, $u);
		form_select_optgroup($page_id, 'j_pref_div2', "Detailed Division 2", $isef_data, $u);
		form_select_optgroup($page_id, 'j_pref_div3', "Detailed Division 3", $isef_data, $u);
		form_select($page_id, 'j_pref_cat', "Category Preference", $cats_data, $u);
?>
		</div>
<?php
		form_submit($page_id, 'Save');
?>
		<input type="hidden" name="action" value="save"/>
	</form>

	<?=form_scripts('judge_expertise.php', $page_id, $fields);?>

	<script>
		$( "#<?=$page_id?>_j_special_award_only" ).change(function() {
			var sa_only = $("#<?=$page_id?>_j_special_award_only option:selected").val();
			if(sa_only == '0') {
				$('#j_expertise_sa').hide();
				$('#j_expertise_normal').show();
			} else {
				$('#j_expertise_sa').show();
				$('#j_expertise_normal').hide();
			}
		});
	</script>
	


</div></div>
	




<?php
sfiab_page_end();
?>
