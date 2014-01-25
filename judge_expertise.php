<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('isef.inc.php');
require_once('awards.inc.php');
$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

sfiab_session_start($mysqli, array('judge'));

$u = user_load($mysqli);

$page_id = 'j_expertise';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

switch($action) {
case 'save':
	post_bool($u['j_sa_only'], 'j_sa_only');
	if($u['j_sa_only']) {
		post_int($u['j_sa'][0], 'j_sa');
		post_int($u['j_sa'][1], 'j_sa2');
		post_int($u['j_sa'][2], 'j_sa3');
		$u['j_pref_div1'] = NULL;
		$u['j_pref_div2'] = NULL;
		$u['j_pref_div3'] = NULL;
		$u['j_pref_cat'] = NULL;
		$u['j_years_school'] = NULL;
		$u['j_years_regional'] = NULL;
		$u['j_years_national'] = NULL;
	} else {
		$u['j_sa'] = array(NULL, NULL, NULL);
		post_int($u['j_pref_div1'], 'j_pref_div1');
		post_int($u['j_pref_div2'], 'j_pref_div2');
		post_int($u['j_pref_div3'], 'j_pref_div3');
		post_int($u['j_pref_cat'], 'j_pref_cat');
		post_int($u['j_years_school'], 'j_years_school');
		post_int($u['j_years_regional'], 'j_years_regional');
		post_int($u['j_years_national'], 'j_years_national');
	}

	user_save($mysqli, $u);

	$ret = incomplete_check($mysqli, $u, $page_id, true);
	form_ajax_response(array('status'=>0, 'missing'=>$ret));
	exit();
}

$help = '
<ul><li><b>Sponsor Judge</b> - If you represent the sponsor of a specific special award, then choose \'Yes\' here and select which special award(s) you will be judging.
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
	$form_id = $page_id.'_form';
	$fields = incomplete_check($mysqli, $u, $page_id);

	$cats = categories_load($mysqli);
	$isef_data = isef_get_div_names();

	$cats_data = array('0'=>'No Preference');
	foreach($cats as $cid=>$c) {
		$cats_data[$cid] = $c['name'];
	}

	$sa_only = ($u['j_sa_only'] == 1) ? 1 : 0;
	$hidden = "style=\"display:none\"";

	form_begin($form_id, 'judge_expertise.php', $fields);
?>
		<h3>Sponsor Judges</h3>
<?php
	form_yesno($form_id, 'j_sa_only', "Do you represent the sponsor of a special award?", $u, true, true);
	
?>
	<div id="j_expertise_sa" <?=$sa_only ? '' : $hidden?> >
		Note: Our chief judge will double-check with anyone who selects 'Yes' here to ensure they
		are a sponsor for a special award.  If you are not sure then you are probably not a Sponsor judge.
<?php
		$awards = award_load_special_for_select($mysqli);
		form_select($form_id, 'j_sa', "Special Award", $awards, $u['j_sa'][0]);
		form_select($form_id, 'j_sa2', "Special Award", $awards, $u['j_sa'][1]);
		form_select($form_id, 'j_sa3', "Special Award", $awards, $u['j_sa'][2]);
?>		
	</div>

	<div id="j_expertise_normal" <?=$sa_only ? $hidden : '' ?> >
	<h3>Years of Judging Experience</h3>
	Enter how many years of judging experience you have at each level of science fair competitions:
<?php

	form_int($form_id, 'j_years_school', "School/District", $u, 0, 100);
	form_int($form_id, 'j_years_regional', "Regional", $u, 0, 100);
	form_int($form_id, 'j_years_national', "National", $u, 0, 100);
?>
	<h3>Judging Preferences</h3>
	Select your top three detailed divisions to judge and an age category
	preference if you have one.  We use this to match you with projects to
	judge.  For the divisions, we will also match you with project in the same general area with
	slightly less priority.  That means if you select "Biochemistry--Medicinal", you will likely also be matched
	with projects in "Biochemistry--Analytical" and all other Biochemistry divisions as well.
<?php

	form_select_optgroup($form_id, 'j_pref_div1', "Detailed Division 1", $isef_data, $u);
	form_select_optgroup($form_id, 'j_pref_div2', "Detailed Division 2", $isef_data, $u);
	form_select_optgroup($form_id, 'j_pref_div3', "Detailed Division 3", $isef_data, $u);
	form_select($form_id, 'j_pref_cat', "Category Preference", $cats_data, $u);
?>
	</div>
<?php
	form_submit($form_id, 'save', 'Save', "Information Saved");
	form_end($form_id);
?>

	<script>
		$( "#<?=$form_id?>_j_sa_only" ).change(function() {
			var sa_only = $("#<?=$form_id?>_j_sa_only option:selected").val();
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
