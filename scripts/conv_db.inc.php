<?php

require_once('scripts/conv_db_config.inc.php');
require_once('scripts/conv_db_categories.inc.php');
require_once('scripts/conv_db_sponsors.inc.php');
require_once('scripts/conv_db_awards.inc.php');
require_once('scripts/conv_db_schools.inc.php');
require_once('scripts/conv_db_tours.inc.php');
require_once('scripts/conv_db_students.inc.php');
require_once('scripts/conv_db_judges.inc.php');
require_once('scripts/conv_db_fairs.inc.php');
require_once('scripts/conv_db_reports.inc.php');
require_once('scripts/conv_db_emails.inc.php');
require_once('scripts/conv_db_volunteers.inc.php');
require_once('scripts/conv_db_committee.inc.php');

function conv_rename_old_tables($mysqli, $prefix)
{
	$tables = array('award_awards', 'award_awards_projectcategories',
			'award_awards_projectdivisions', 'award_prizes',
			'award_types', 'cms', 'committees', 'committees_link',
			'conferences', 'config', 'countries', 'dates',
			'documents', 'emailqueue', 'emailqueue_recipients',
			'emails', 'emergencycontact', 'exhibithall', 'fairs',
			'fairs_awards_link', 'fairs_stats',
			'fundraising_campaigns',
			'fundraising_campaigns_users_link',
			'fundraising_donations', 'fundraising_donor_levels',
			'fundraising_donor_logs', 'fundraising_goals',
			'judges_availability', 'judges_jdiv',
			'judges_schedulerconfig', 'judges_specialaward_sel',
			'judges_teams', 'judges_teams_awards_link',
			'judges_teams_link', 'judges_teams_timeslots_link',
			'judges_teams_timeslots_projects_link',
			'judges_timeslots', 'languages', 'mentors', 'pagetext',
			'projectcategories', 'projectcategoriesdivisions_link',
			'projectdivisions', 'projectdivisionsselector',
			'projects', 'projectsubdivisions',
			'project_specialawards_link', 'provinces', 'questions',
			'question_answers', 'regfee_items',
			'regfee_items_link', 'registrations', 'reports',
			'reports_committee', 'reports_items', 'safety',
			'safetyquestions', 'schools', 'signaturepage',
			'sponsors', 'students', 'tours', 'tours_choice',
			'translations', 'users', 'users_alumni',
			'users_committee', 'users_fair', 'users_judge',
			'users_mentor', 'users_parent', 'users_principal',
			'users_sponsor', 'users_teacher', 'users_volunteer',
			'volunteer_positions', 'volunteer_positions_signup',
			'winners');
	foreach($tables as $t) {
		$mysqli->real_query("RENAME TABLE `$t` TO `{$prefix}$t`");
	}
}



function conv_db($mysqli, $old_prefix)
{
	/* Figure out which years to convert */
	$skip_years = array();
	$q = $mysqli->query("SELECT DISTINCT(year) FROM users WHERE year>0");
	while($r = $q->fetch_assoc()) {
		$skip_years[] = (int)$r['year'];
	}

	/* Get years in the old database */
	$years = array();
	$q = $mysqli->query("SELECT DISTINCT(year) FROM {$old_prefix}config WHERE year>1");
	while($r = $q->fetch_assoc()) {
		$y = (int)$r['year'];
		if(!in_array($y, $skip_years)) {
			$years[] = $y;
		}
	}

	sort($years);

	print("   Will convert years: ".join(', ', $years)."\n");


	conv_config($mysqli, $old_prefix);
	sfiab_load_config($mysqli);

	load_sponsors($mysqli, $old_prefix);

	conv_reports($mysqli, $old_prefix);
	conv_fairs($mysqli, $old_prefix);
	conv_emails($mysqli, $old_prefix);

	foreach($years as $year) {
		conv_categories($mysqli, $old_prefix, $year);
		conv_schools($mysqli, $old_prefix, $year);
		conv_tours($mysqli, $old_prefix, $year);
	}

	foreach($years as $year) {
		clear_sponsors($mysqli, $year);
		conv_awards($mysqli, $old_prefix, $year);
		conv_students($mysqli, $old_prefix, $year);
		conv_winners($mysqli, $old_prefix, $year);

		conv_judges($mysqli, $old_prefix, $year);
		conv_committee($mysqli, $old_prefix, $year);
		conv_volunteers($mysqli, $old_prefix, $year);
	}

}


?>
