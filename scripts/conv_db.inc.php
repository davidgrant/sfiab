<?php


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
		$mysqli->real_query("RENAME TABLE `$t` TO `{$prefix}_$t`");
	}
}

?>
