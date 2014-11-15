<?php

function conv_config($mysqli, $old_prefix)
{
	print("Converting Config\n");

	/* Get the latest year */
	$q = $mysqli->query("SELECT val FROM {$old_prefix}config WHERE `var`='FAIRYEAR'");
	$r = $q->fetch_row();
	$year = (int)$r[0];
	print("   Latest Year = $year\n");

	$q = $mysqli->query("SELECT * FROM {$old_prefix}dates WHERE year='$year'");
	print($mysqli->error);
	$c = 0;
	while($old_c = $q->fetch_assoc()) {
		$old_name = $old_c['name'];
		$old_val = $old_c['date'];

		$val = $mysqli->real_escape_string($old_val);
		$name = NULL;
		switch($old_name) {
		case 'fairdate':
			$mysqli->real_query("UPDATE `config` SET `val`='$val' WHERE `var`='date_fair_begins'");
			$mysqli->real_query("UPDATE `config` SET `val`='$val' WHERE `var`='date_fair_ends'");
			break;
		case 'regopen':
			$mysqli->real_query("UPDATE `config` SET `val`='$val' WHERE `var`='date_student_registration_opens'");
			break;
		case 'regclose':
			$mysqli->real_query("UPDATE `config` SET `val`='$val' WHERE `var`='date_student_registration_closes'");
			break;
		case 'judgeregopen':
			$mysqli->real_query("UPDATE `config` SET `val`='$val' WHERE `var`='date_judge_registration_opens'");
			break;
		case 'judgeregclose':
			$mysqli->real_query("UPDATE `config` SET `val`='$val' WHERE `var`='date_judge_registration_closes'");
			break;
		}
	}

	$q = $mysqli->query("SELECT * FROM {$old_prefix}config WHERE year='$year'");
	print($mysqli->error);
	$c = 0;
	while($old_c = $q->fetch_assoc()) {
		$old_var = $old_c['var'];
		$old_val = $old_c['val'];
		$old_category = $old_c['category'];
		$old_type = $old_c['type'];
		$old_typevalues = $old_c['typevalues'];
		$old_ord = $old_c['ord'];
		$old_description = $old_c['description'];

		$var = NULL;
		if($old_type == 'yesno') {
			$val = $old_val == 'yes' ? 1 : 0;
		} else {
			$val = $old_val;
		}

		switch($old_var) {
		case 'fairmanageremail':
			$var = array('email_chair', 'email_chiefjudge', 'email_ethics','email_registration');
			break;

		case 'fairname': $var = 'fair_name'; break;
		case 'regfee': $var = 'regfee'; break;
		case 'tours_enable': $var = 'tours_enable'; break;
		case 'participant_student_tshirt': $var = 'tshirt_enable'; break;
		case 'participant_student_tshirt_cost': $var = 'tshirt_cost'; break;
		case 'volunteer_enable': $var = 'volunteers_enable'; break;
		case 'participant_project_summary_wordmax': $var = 's_abstract_max_words'; break;
		case 'participant_project_summary_wordmin': $var = 's_abstract_min_words'; break;

		case 'min_judges_per_team': $var = 'judge_div_min_team'; break;
		case 'max_judges_per_team': $var = 'judge_div_max_team'; break;
		case 'max_projects_per_team': $var = 'judge_div_max_projects'; break;

		case 'projects_per_special_award_judge': $var = 'judge_sa_max_projects'; break;
		}

		if($var !== NULL) {
			if(!is_array($var)) {
				$var = array($var);
			}

			$val = $mysqli->real_escape_string($val);

			foreach($var as $v) {
				$mysqli->real_query("UPDATE `config` SET `val`='$val' WHERE `var`='$v'");
			}
		}

/* These are all missing from the old config: 
//	fair_abbreviation 
//	s_tagline_max_words 	
//	s_tagline_min_words 	
//	judge_cusp_max_team 	
//	judge_cusp_min_team 	
//	judge_div_min_projects 	
//	judge_sa_min_projects 	
*/

	}
	print("   Converted config.\n");
}

?>
