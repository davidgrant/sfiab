<?php


$judges_map = array();


function conv_judges($mysqli, $old_prefix, $year) 
{
	global $awards_map, $awards_prizes_map;

	print("Converting Judges for $year...\n");

	$mysqli->real_query("DELETE FROM users WHERE FIND_IN_SET('judge',`roles`)>0 AND year='$year'");

	/* */

	$q = $mysqli->query("SELECT * FROM {$old_prefix}users WHERE FIND_IN_SET('judge',`types`)>0 AND deleted='no' AND year='$year'");
	print($mysqli->error);
	$c = 0;
	while($old_u = $q->fetch_assoc()) {

		$q1 = $mysqli->query("SELECT * FROM {$old_prefix}users_judge WHERE users_id='{$old_u['id']}'");
		$r = $q1->fetch_assoc();
		$old_u = array_merge($old_u, $r);

		/* Skip incomplete users */
		if($old_u['judge_active'] == 'no') continue;
		if($old_u['judge_complete'] == 'no') continue;

		/* Create a new user */
		$password = NULL;
		$uid = user_create($mysqli, NULL, $old_u['email'], 'judge', $year, $password);
		$u = user_load($mysqli, $uid);

		$u['phone1'] = $old_u['phonehome'];
		$u['phone2'] = $old_u['phonecell'];
		filter_phone($u['phone1']);
		filter_phone($u['phone2']);

		$u['username'] = NULL;
		$u['firstname'] = $old_u['firstname'];
		$u['lastname'] = $old_u['lastname'];
		$u['salutation'] = $old_u['salutation'];
		$u['organization'] = $old_u['organization'];
		$u['sex'] = $old_u['sex'];
		$u['city'] = $old_u['city'];
		$u['province'] = $old_u['province'];
		$u['language'] = ($old_u['lang'] == '') ? 'en' : $old_u['lang'];
		if($old_u['highest_psd'] == '') {
			$in['j_psd'] = NULL;
		} else {
			switch($old_u['highest_psd'][0]) {
			case 'P': case 'p':
				$u['j_psd'] = 'doctorate';
				break;
			case 'M': case 'm':
				$u['j_psd'] = 'master';
				break;
			default:
				$u['j_psd'] = 'bachelor';
				break;
			}
		}
		$u['j_willing_lead'] = ($old_u['willing_chair'] == 'yes') ? 1 : 0;
		if($old_u['languages'] != '') {
			$u['j_languages'] = array_values(unserialize($old_u['languages']));
		}
		$u['j_sa_only'] = ($old_u['special_award_only'] == 'yes') ? 1 : 0;
		$u['j_dinner'] = 0;
		$u['j_years_school'] = $old_u['years_school'];
		$u['j_years_regional'] = $old_u['years_regional'];
		$u['j_years_national'] = $old_u['years_national'];
		$u['enabled'] = 1;
		$u['new'] = 0;
		$u['j_rounds'] = array();
		$u['j_complete'] = 1;
		$u['j_cat_pref'] = 0;
		$u['j_mentored'] = 0;
		$highest = 0;
		if($old_u['cat_prefs'] != '') {
			$old_u_cat_prefs = unserialize($old_u['cat_prefs']);
			foreach($old_u_cat_prefs as $cat_pref=>$rank) {
				if($rank >= $highest) {
					$highest = $rank;
					$u['j_cat_pref'] = $cat_pref;
				}
			}
		} 

		user_save($mysqli, $u);
		$c++;
	}
	print("   Converted $c judges.\n");
}

?>
