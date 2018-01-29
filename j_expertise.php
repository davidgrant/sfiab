<?php
require_once('common.inc.php');
require_once('form.inc.php');
require_once('user.inc.php');
require_once('incomplete.inc.php');
require_once('isef.inc.php');
require_once('awards.inc.php');

$mysqli = sfiab_init('judge');

$u = user_load($mysqli);
$closed = sfiab_registration_is_closed($u);

$page_id = 'j_expertise';

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

$awards = award_load_special_for_select($mysqli);

switch($action) {
case 'save':
	if($closed) exit();
	post_bool($u['j_sa_only'], 'j_sa_only');
	if($u['j_sa_only']) {
		post_array($u['j_sa'], 'j_sa', $awards);
		$u['j_div_pref'] = array();
		$u['j_cat_pref'] = NULL;
		$u['j_years_school'] = NULL;
		$u['j_years_regional'] = NULL;
		$u['j_years_national'] = NULL;
	} else {
		$u['j_sa'] = array();
		post_int($u['j_cat_pref'], 'j_cat_pref');
		post_int_list($u['j_div_pref'], 'j_div_pref');
		post_int($u['j_years_school'], 'j_years_school');
		post_int($u['j_years_regional'], 'j_years_regional');
		post_int($u['j_years_national'], 'j_years_national');
	}

	user_save($mysqli, $u);

	incomplete_check($mysqli, $ret, $u, $page_id, true);
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

sfiab_page_begin($u, "Expertise", $page_id, $help);

?>


<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
	incomplete_check($mysqli, $fields, $u, $page_id);
	form_page_begin($page_id, $fields);
	form_disable_message($page_id, $closed);

	$cats = categories_load($mysqli);
	if($config['judge_scheduler_use_detailed_subdivisions'] == 1) {
		$isef_data = isef_get_div_names();
	} else {
		$isef_data = isef_get_major_div_names();
	}

	$cats_data = array('0'=>'No Preference');
	foreach($cats as $cid=>$c) {
		$cats_data[$cid] = $c['name'];
	}

	$sa_only = ($u['j_sa_only'] == 1) ? 1 : 0;
	$hidden = "style=\"display:none\"";

	$form_id = $page_id.'_form';
	form_begin($form_id, 'j_expertise.php', $closed);
?>
		<h3>Sponsor Judges</h3>
<?php

	form_yesno($form_id, 'j_sa_only', "Do you represent the sponsor of a special award?", $u, false);
	
?>
	<div id="j_expertise_sa" <?=$sa_only ? '' : $hidden?> >
		Note: Our chief judge will double-check with all Sponsor Judges ensure they
		are a sponsor for a special award.  If you are not sure then you are probably not a Sponsor judge.
<?php

		for($x=0;$x<3;$x++) {
//		$sa = ($x < count($u['j_sa'])) ? (int)$u['j_sa'][$x] : -1;
			form_select($form_id, "j_sa[$x]", "Special Award", $awards, $u);
		}
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
	judge.  We will do our best to match you with projects 
	only in divisions you select, but we cannot guarantee that all projects 
	you are assigned to judge will be a perfect match.

<?php	if($config['judge_scheduler_use_detailed_subdivisions'] == 1) { ?>
		<p>For the divisions, we will also match you with projects in the
		same general division with slightly less priority.  That means if you
		select "Biochemistry--Medicinal", you will also be matched with
		projects in "Biochemistry--Analytical" and all other Biochemistry
		divisions as well.  
<?php	}		
	
	form_select_optgroup($form_id, 'j_div_pref[0]', "Detailed Division 1", $isef_data, $u);
	form_select_optgroup($form_id, 'j_div_pref[1]', "Detailed Division 2", $isef_data, $u);
	form_select_optgroup($form_id, 'j_div_pref[2]', "Detailed Division 3", $isef_data, $u);
	form_select($form_id, 'j_cat_pref', "Category Preference", $cats_data, $u);
?>
	</div>
<?php
	form_submit($form_id, 'save', 'Save', "Information Saved");
	form_end($form_id);
?>

	<script>
		$( "#<?=$form_id?>_j_sa_only" ).change(function() {
			var sa_only = $('input[name="j_sa_only"]:checked').val();
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
