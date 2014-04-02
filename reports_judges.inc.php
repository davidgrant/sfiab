<?php
/* 
   This file is part of the 'Science Fair In A Box' project
   SFIAB Website: http://www.sfiab.ca

   Copyright (C) 2005 Sci-Tech Ontario Inc <info@scitechontario.org>
   Copyright (C) 2005 James Grant <james@lightbox.org>

   This program is free software; you can redistribute it and/or
   modify it under the terms of the GNU General Public
   License as published by the Free Software Foundation, version 2.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
    General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program; see the file COPYING.  If not, write to
   the Free Software Foundation, Inc., 59 Temple Place - Suite 330,
   Boston, MA 02111-1307, USA.
*/
require_once('common.inc.php');
require_once('project.inc.php');
require_once('user.inc.php');


/* Take the language array in users_judge, unserialize it, and join it
 * with a space */
function report_judges_languages($mysqli, &$report, $field, $text)
{
	$l = unserialize($text);
	return ($l?join(' ', $l):'');
}

function report_judges_team_members($mysqli, &$report, $field, $text)
{
	$year = $report['year'];
	$judges_teams_id = $text;
	$q = mysql_query("SELECT * FROM judges_teams_link 
						LEFT JOIN users ON judges_teams_link.users_id=users.id
						WHERE judges_teams_link.year='$year'
						AND judges_teams_link.judges_teams_id='$judges_teams_id'");
	$ret = '';
	while( ($m = mysql_fetch_assoc($q))) {
		$add = false;
		switch($field) {
		case 'team_captain':
			if($m['captain'] == 'yes') $add = true;
			break;
	
		case 'team_members_all_except_this':
			/* Not implemented, need to pass teams_id AND users_id in here */
			break;

		case 'team_members_all_except_captain':
			if($m['captain'] == 'no') $add = true;
			break;

		case 'team_members_all':
			$add = true;
			break;
		}

		if($add) {
			if($ret != '') $ret .= ', ';
			$ret .= "{$m['firstname']} {$m['lastname']}";
		}
	}
	return $ret;
}



//$round_special_awards = array();
$report_judges_rounds = array();
function report_judges_load_rounds($year)
{	
	global $config, $report_judges_rounds;
	if(count($report_judges_rounds)) return ;

	$q = mysql_query("SELECT * FROM judges_timeslots WHERE round_id='0' AND `year`='$year'");
	/* Loads judges_timeslots.id, .starttime, .endtime, .date, .name */
	while($r = mysql_fetch_assoc($q)) {
        	$report_judges_rounds[] = $r;

		if($r['type'] == 'divisional1') $report_judges_rounds['divisional1'] = $r;
		if($r['type'] == 'divisional2') $report_judges_rounds['divisional2'] = $r;
	}
//        if($r['type'] == 'special') $round_special_awards[] = $r;
}

function report_judges_time_availability($mysqli, &$report, $field, $text)
{
	global $config, $report_judges_rounds;
	$year = $report['year'];
	$users_id = $text;

	report_judges_load_rounds($year);

	switch($field) {
	case 'available_in_divisional1':
		$round = $report_judges_rounds['divisional1'];
		break;
	case 'available_in_divisional2':
		$round = $report_judges_rounds['divisional2'];
		break;
	default:
		echo "Not implemented.";
		exit;
	}

	$q = mysql_query("SELECT * FROM judges_availability WHERE users_id='$users_id'");
//	echo mysql_error();
	while(($r = mysql_fetch_assoc($q))) {
		 if($r['start'] <= $round['starttime'] 
                  && $r['end'] >= $round['endtime'] 
                  && $r['date'] == $round['date'] ) {
                        return 'Yes';
                }
	}
	return 'No';
}

/* Components:  languages, teams */

$report_judges_fields = array(
	'last_name' =>  array(
		'name' => 'Judge -- Last Name',
		'header' => 'Last Name',
		'width' => 1.0,
		'table' => 'users.lastname' ),

	'first_name' => array(
		'name' => 'Judge -- First Name',
		'header' => 'First Name',
		'width' => 1.0,
		'table' => 'users.firstname' ),

	'name' =>  array(
		'name' => 'Judge -- Full Name (last, first)',
		'header' => 'Name',
		'width' => 1.75,
		'table' => "CONCAT(users.lastname, ', ', users.firstname)",
		'table_sort'=> 'users.lastname' ),
		
	'namefl' =>  array(
		'name' => 'Judge -- Full Name (salutation first last)',
		'header' => 'Name',
		'width' => 1.75,
		'table' => "CONCAT(IF(users.salutation IS NULL OR users.salutation='', '', CONCAT(users.salutation, ' ')), users.firstname, ' ', users.lastname)",
		'table_sort'=> 'users.lastname' ),

	'email' =>  array(
		'name' => 'Judge -- Email',
		'header' => 'Email',
		'width' => 2.0,
		'table' => 'users.email'),

	'address' =>  array(
		'name' => 'Judge -- Address Street',
		'header' => 'Address',
		'width' => 2.0,
		'table' => "CONCAT(users.address, ' ', users.address2)"),

	'city' =>  array(
		'name' => 'Judge -- Address City',
		'header' => 'City',
		'width' => 1.5,
		'table' => 'users.city' ),

	'province' =>  array(
		'name' => 'Judge -- Address '.$config['provincestate'],
		'header' => $config['provincestate'],
		'width' => 0.75,
		'table' => 'users.province' ),

	'postal' =>  array(
		'name' => 'Judge -- Address '.$config['postalzip'],
		'header' => $config['postalzip'],
		'width' => 0.75,
		'table' => 'users.postalcode' ),
	
	'phone_home' => array(
		'name' => 'Judge -- Phone 1',
		'header' => 'Phone1',
		'width' => 1,
		'table' => 'users.phone1'),

	'phone_work' => array(
		'name' => 'Judge -- Phone2)',
		'header' => 'Phone2',
		'width' => 1,
		'table' => "users.phone2"),

	'organization' => array(
		'name' => 'Judge -- Organization',
		'header' => 'Organization',
		'width' => 2,
		'table' => 'users.organization'),

	'languages' => array(
		'name' => 'Judge -- Languages',
		'header' => 'Lang',
		'width' => 0.75,
		'table' => 'users.languages',
		'exec_function' => 'report_judges_languages'),

	'complete' =>  array(
		'name' => 'Judge -- Registration Complete',
		'header' => 'Cmpl',
		'width' => 0.4,
		'table' => 'users.j_complete',
		'value_map' => array ('0' => 'No', '1' => 'Yes')),

	'active' =>  array(
		'name' => 'Judge -- Registration Active for this year',
		'header' => 'Act',
		'width' => 0.4,
		'table' => 'users.judge_active',
		'value_map' => array ('no' => 'No', 'yes' => 'Yes')),

	'willing_chair' => array(
		'name' => 'Judge -- Willing Lead',
		'header' => 'Will Lead?',
		'width' => 1,
		'table' => 'users.j_willing_lead',
		'value_map' => array ('no' => 'No', 'yes' => 'Yes')),

	'years_school' => array(
		'name' => 'Judge -- Years of Experience at School level',
		'header' => 'Sch',
		'width' => 0.5,
		'table' => 'users.j_years_school'),

	'years_regional' => array(
		'name' => 'Judge -- Years of Experience at Regional level',
		'header' => 'Rgn',
		'width' => 0.5,
		'table' => 'users.j_years_regional'),

	'years_national' => array(
		'name' => 'Judge -- Years of Experience at National level',
		'header' => 'Ntl',
		'width' => 0.5,
		'table' => 'users.j_years_national'),

	'highest_psd' => array(
		'name' => 'Judge -- Highest Post-Secondary Degree',
		'header' => 'Highest PSD',
		'width' => 1.25,
		'table' => 'users.highest_psd'),



/* Time Availability 
	'available_in_divisional1' =>  array(
		'name' => 'Time Availability -- Available in Divisional Round 1 ',
		'header' => 'R1',
		'width' => 0.5,
		'exec_function' => 'report_judges_time_availability',
		'table' => 'users.id'),
	'available_in_divisional2' =>  array(
		'name' => 'Time Availability -- Available in Divisional Round 2 ',
		'header' => 'R2',
		'width' => 0.5,
		'exec_function' => 'report_judges_time_availability',
		'table' => 'users.id'),
*/
/* Others  */

	'special_award_only' =>  array(
		'name' => 'Judge -- Special Award Only Requested',
		'header' => 'SA Only',
		'width' => 0.8,
		'table' => 'users.j_sa_only'),

	'year' =>  array(
		'name' => 'Judge -- Year',
		'header' => 'Year',
		'width' => 0.5,
		'table' => 'users.year'),


/* Judging Teams */
	'team_name' => array(
		'start_option_group' => 'Judging Team',
		'name' => 'Judge Team -- Name',
		'header' => 'Team Name',
		'width' => 3.0,
		'table' => 'judging_teams.name',
		'components' => array('teams')),

	'team_num' => array(
		'name' => 'Judge Team -- Team Number',
		'header' => 'Num',
		'width' => 0.5,
		'table' => 'judging_teams.num',
		'components' => array('teams')),

	'team_round' => array(
		'name' => 'Judge Team -- Team Round',
		'header' => 'Round',
		'width' => 0.5,
		'table' => 'judging_teams.round',
		'components' => array('teams')),

	'team_award_name' => array(
		'name' => 'Judge Team -- Award Name',
		'header' => 'Award Name',
		'width' => 1.5,
		'table' => 'awards.name',
		'components' => array('teams')),
		

/* Fixme, this requires passing 2 args to the function, can't do that yet 
	'team_members_all_except_this' => array(
		'name' => 'Judge Team -- All other team members',
		'header' => 'Members',
		'width' => 2,
		'table' => 'judges_teams.id',
		'exec_function' => 'report_judges_team_members',
		'components' => array('teams')),

	'team_captain' => array(
		'name' => 'Judge Team -- Name of the Team Captain',
		'header' => 'Captain',
		'width' => 1.75,
		'table' => 'judges_teams.id',
		'exec_function' => 'report_judges_team_members',
		'components' => array('teams')),
	
	'team_members_all_except_captain' => array(
		'name' => 'Judge Team -- All team members, except the Captain',
		'header' => 'Members',
		'width' => 2,
		'table' => 'judges_teams.id',
		'exec_function' => 'report_judges_team_members',
		'components' => array('teams')),
	
	'team_members_all' => array(
		'name' => 'Judge Team -- All team members including the Captain',
		'header' => 'Members',
		'width' => 2,
		'table' => 'judges_teams.id',
		'exec_function' => 'report_judges_team_members',
		'components' => array('teams')),
	
	'project_pn' => array(
		'name' => 'Project -- Number',
		'header' => 'Number',
		'width' => 0.5,
		'table' => 'projects.projectnumber',
		'components' => array('teams', 'projects')),

	'project_title' => array(
		'name' => 'Project -- Title',
		'header' => 'Project',
		'width' => 3,
		'table' => 'projects.title',
		'components' => array('teams', 'projects')),

	'project_summary' => array(
		'name' => 'Project -- Summary',
		'header' => 'Summary',
		'width' => 5,
		'table' => 'projects.summary',
		'components' => array('teams', 'projects')),

	'project_language' => array(
		'name' => 'Project -- Language',
		'header' => 'Lang',
		'width' => 0.4,
		'table' => 'projects.language',
		'components' => array('teams', 'projects')),
		
	'project_students' => array(
		'name' => 'Project -- Student Name(s) (REQUIRES MYSQL 5.0) ',
		'header' => 'Student(s)',
		'width' => 3.0,
		'table' => "GROUP_CONCAT(students.firstname, ' ', students.lastname ORDER BY students.lastname SEPARATOR ', ')",
		'group_by' => array('users.id','judges_teams_timeslots_projects_link.id'),
		'components' => array('teams', 'projects', 'students')),

	'project_timeslot_start' => array(
		'name' => 'Project -- Timeslot Start Time (HH:MM)',
		'header' => 'Start',
		'width' => 0.75,
		'table' => "TIME_FORMAT(judges_timeslots.starttime,'%H:%i')",
		'components' => array('teams', 'projects')),

	'project_timeslot_end ' => array(
		'name' => 'Project -- Timeslot End Time (HH:MM)',
		'header' => 'End',
		'width' => 0.75,
		'table' => "TIME_FORMAT(judges_timeslots.endtime,'%H:%i')",
		'components' => array('teams', 'projects')),

	'project_timeslot' => array(
		'name' => 'Project -- Timeslot Start - End (HH:MM - HH:MM)',
		'header' => 'Timeslot',
		'width' => 1.5,
		'table' => "CONCAT(TIME_FORMAT(judges_timeslots.starttime,'%H:%i'),'-',TIME_FORMAT(judges_timeslots.endtime,'%H:%i'))",
		'components' => array('teams', 'projects')),

	'project_timeslot_date' => array(
		'name' => 'Project -- Timeslot Date - (YYYY-MM-DD)',
		'header' => 'Timeslot Date',
		'width' => 1,
		'table' => "judges_timeslots.date",
		'components' => array('teams', 'projects')),

	'rank' => array(
		'name' => 'Project -- Rank (left blank for judges to fill out)',
		'header' => 'Rank',
		'width' => 1.00,
		'table' => '""' ),

*/

	'static_text' =>  array(
		'name' => 'Static Text (useful for labels)',
		'header' => '',
		'width' => 0.1,
		'table' => "CONCAT(' ')"),
);

function report_judges_fromwhere($report, $components)
{
 	global $config, $report_judges_fields;

	$year = $report['year'];

	$teams_from = '';
	$teams_where = '';
	if(in_array('teams', $components)) {
		$teams_from = "LEFT JOIN judging_teams ON FIND_IN_SET(`users`.`uid`, `judging_teams`.`user_ids`)
				LEFT JOIN awards on `awards`.`id`=`judging_teams`.`award_id`";
	}
/*
	$projects_from='';
	$projects_where='';
	if(in_array('projects', $components)) {
		$projects_from = "LEFT JOIN judges_teams_timeslots_projects_link ON
					judges_teams_timeslots_projects_link.judges_teams_id=judges_teams.id
				LEFT JOIN projects ON projects.id=judges_teams_timeslots_projects_link.projects_id
				LEFT JOIN judges_timeslots ON judges_timeslots.id=judges_teams_timeslots_projects_link.judges_timeslots_id";
		$projects_where = "AND judges_teams_timeslots_projects_link.year='$year'
				AND projects.year='$year'";
	}

	$students_from='';
	$students_where='';
	if(in_array('students', $components)) {
		$students_from = "LEFT JOIN students ON students.registrations_id=projects.registrations_id";
		$students_where = "AND students.year='$year'";
	}
*/

	/* Search the report for a filter based on judge year */
	$year_where = "AND users.year='$year'";
	foreach($report['filter'] as $d) {
		if($d['field'] == 'year') {
			/* Don't interally filter on year, we'll do it externally */
			$year_where = '';
		}
	}
	switch($report['option']['include_registrations']) {
	case 'complete':
		$reg_where = "AND users.j_complete='1'";
		break;
	default:		
		$reg_where = '';
	}
	
										
	$q = "	FROM 	users
			$teams_from
		WHERE
			FIND_IN_SET('judge',`users`.`roles`)>0
			$year_where
			$reg_where
			AND state='active'
		";

	return $q;
}

?>
