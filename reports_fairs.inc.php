<?php
/* 
   This file is part of the 'Science Fair In A Box' project
   SFIAB Website: http://www.sfiab.ca

   Copyright (C) 2005 David Grant <dave@lightbox.org>

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

$report_fairs_fields = array(

	'fairinfo_name' =>  array(
		'start_option_group' => 'Fair Statistics',
		'name' => 'Fair -- Fair Name',
		'header' => 'Name',
		'width' => 38.1 /*mm*/,
		'table' => 'fairs.name'),

	'fairstats_year' => array (
		'name' => 'Fair Stats -- Year',
		'header' => 'Year',
		'width' => 25.4 /*mm*/,
		'table' => 'fair_stats.year',
		'components' => array('fair_stats')),

	'fairstats_start_date' => array (
		'name' => 'Fair Stats -- Fair Start',
		'header' => 'Fair Start',
		'width' => 25.4 /*mm*/,
		'table' => 'fair_stats.start_date',
		'components' => array('fair_stats')), 

	'fairstats_end_date' => array (
		'name' => 'Fair Stats -- Fair End',
		'header' => 'Fair End',
		'width' => 25.4 /*mm*/,
		'table' => 'fair_stats.end_date',
		'components' => array('fair_stats')), 

	'fairstats_scholarships' => array (
		'name' => 'Fair Stats -- Scholarship Info',
		'header' => 'Scholarship Info',
		'width' => 25.4 /*mm*/,
		'table' => 'fair_stats.scholarships',
		'components' => array('fair_stats')), 

	'fairstats_male_1' => array (
		'name' => 'Fair Stats -- Males Grade 1-3',
		'header' => 'Males Grade 1-3',
		'width' => 25.4 /*mm*/,
		'table' => '`fair_stats`.`male_0`',
		'components' => array('fair_stats')), 
	'fairstats_male_4' => array (
		'name' => 'Fair Stats -- Males Grade 4-6',
		'header' => 'Males Grade 4-6',
		'width' => 25.4 /*mm*/,
		'table' => '`fair_stats`.`male_4` + `fair_stats`.`male_5` + `fair_stats`.`male_6`',
		'components' => array('fair_stats')), 
	'fairstats_male_7' => array (
		'name' => 'Fair Stats -- Males Grade 7-8',
		'header' => 'Males Grade 7-8',
		'width' => 25.4 /*mm*/,
		'table' => '`fair_stats`.`male_7` + `fair_stats`.`male_8`',
		'components' => array('fair_stats')), 
	'fairstats_male_9' => array (
		'name' => 'Fair Stats -- Males Grade 9-10',
		'header' => 'Males Grade 9-10',
		'width' => 25.4 /*mm*/,
		'table' => '`fair_stats`.`male_9` + `fair_stats`.`male_10`',
		'components' => array('fair_stats')), 
	'fairstats_male_11' => array (
		'name' => 'Fair Stats -- Males Grade 11-12',
		'header' => 'Males Grade 11-12',
		'width' => 25.4 /*mm*/,
		'table' => '`fair_stats`.`male_11` + `fair_stats`.`male_12`',
		'components' => array('fair_stats')), 
	'fairstats_female_1' => array (
		'name' => 'Fair Stats -- Females Grade 1-3',
		'header' => 'Females Grade 1-3',
		'width' => 25.4 /*mm*/,
		'table' => '`fair_stats`.`female_0`',
		'components' => array('fair_stats')), 
	'fairstats_female_4' => array (
		'name' => 'Fair Stats -- Females Grade 4-6',
		'header' => 'Females Grade 4-6',
		'width' => 25.4 /*mm*/,
		'table' => '`fair_stats`.`female_4` + `fair_stats`.`female_5`+ `fair_stats`.`female_6`',
		'components' => array('fair_stats')), 
	'fairstats_female_7' => array (
		'name' => 'Fair Stats -- Females Grade 7-8',
		'header' => 'Females Grade 7-8',
		'width' => 25.4 /*mm*/,
		'table' => '`fair_stats`.`female_7` + `fair_stats`.`female_8`',
		'components' => array('fair_stats')), 
	'fairstats_female_9' => array (
		'name' => 'Fair Stats -- Females Grade 9-10',
		'header' => 'Females Grade 9-10',
		'width' => 25.4 /*mm*/,
		'table' => '`fair_stats`.`female_9` + `fair_stats`.`female_10`',
		'components' => array('fair_stats')), 
	'fairstats_female_11' => array (
		'name' => 'Fair Stats -- Females Grade 11-12',
		'header' => 'Females Grade 11-12',
		'width' => 25.4 /*mm*/,
		'table' => '`fair_stats`.`female_11` + `fair_stats`.`female_12`',
		'components' => array('fair_stats')), 
	'fairstats_projects_1' => array (
		'name' => 'Fair Stats -- Projects Grade 1-3',
		'header' => 'Projects Grade 1-3',
		'width' => 25.4 /*mm*/,
		'table' => '`fair_stats`.`project_0`',
		'components' => array('fair_stats')), 
	'fairstats_projects_4' => array (
		'name' => 'Fair Stats -- Projects Grade 4-6',
		'header' => 'Projects Grade 4-6',
		'width' => 25.4 /*mm*/,
		'table' => '`fair_stats`.`project_4` + `fair_stats`.`project_5`+ `fair_stats`.`project_6`',
		'components' => array('fair_stats')), 
	'fairstats_projects_7' => array (
		'name' => 'Fair Stats -- Projects Grade 7-8',
		'header' => 'Projects Grade 7-8',
		'width' => 25.4 /*mm*/,
		'table' => '`fair_stats`.`project_7` + `fair_stats`.`project_8`',
		'components' => array('fair_stats')), 
	'fairstats_projects_9' => array (
		'name' => 'Fair Stats -- Projects Grade 9-10',
		'header' => 'Projects Grade 9-10',
		'width' => 25.4 /*mm*/,
		'table' => '`fair_stats`.`project_9` + `fair_stats`.`project_10`',
		'components' => array('fair_stats')), 
	'fairstats_projects_11' => array (
		'name' => 'Fair Stats -- Projects Grade 11-12',
		'header' => 'Projects Grade 11-12',
		'width' => 25.4 /*mm*/,
		'table' => '`fair_stats`.`project_11` + `fair_stats`.`project_12`',
		'components' => array('fair_stats')), 
	'fairstats_students_atrisk' => array (
		'name' => 'Fair Stats -- Inner City Students',
		'header' => 'Inner City Students',
		'width' => 25.4 /*mm*/,
		'table' => 'fair_stats.students_atrisk',
		'components' => array('fair_stats')), 
	'fairstats_schools_atrisk' => array (
		'name' => 'Fair Stats -- Inner City Schools',
		'header' => 'Inner City Schools',
		'width' => 25.4 /*mm*/,
		'table' => 'fair_stats.schools_atrisk',
		'components' => array('fair_stats')), 
	'fairstats_students_total' => array (
		'name' => 'Fair Stats -- Total Participants',
		'header' => 'Total Participants',
		'width' => 25.4 /*mm*/,
		'table' => '`fair_stats`.`students_public` + `fair_stats`.`students_private`',
		'components' => array('fair_stats')), 
	'fairstats_schools_total' => array (
		'name' => 'Fair Stats -- Total Schools',
		'header' => 'Total Schools',
		'width' => 25.4 /*mm*/,
		'table' => 'fair_stats.schools',
		'components' => array('fair_stats')), 
	'fairstats_students_public' => array (
		'name' => 'Fair Stats -- Participants from Public',
		'header' => 'Participants from Public',
		'width' => 25.4 /*mm*/,
		'table' => 'fair_stats.students_public',
		'components' => array('fair_stats')), 
	'fairstats_schools_public' => array (
		'name' => 'Fair Stats -- Public Schools',
		'header' => 'Public Schools',
		'width' => 25.4 /*mm*/,
		'table' => 'fair_stats.schools_public',
		'components' => array('fair_stats')), 
	'fairstats_students_private' => array (
		'name' => 'Fair Stats -- Participants from Independent',
		'header' => 'Participants from Independent',
		'width' => 25.4 /*mm*/,
		'table' => 'fair_stats.students_private',
		'components' => array('fair_stats')), 
	'fairstats_schools_private' => array (
		'name' => 'Fair Stats -- Independent Schools',
		'header' => 'Independent Schools',
		'width' => 25.4 /*mm*/,
		'table' => 'fair_stats.schools_private',
		'components' => array('fair_stats')), 
	'fairstats_schools_districts' => array (
		'name' => 'Fair Stats -- School Districts',
		'header' => 'School Districts',
		'width' => 25.4 /*mm*/,
		'table' => 'fair_stats.schools_districts',
		'components' => array('fair_stats')), 
	'fairstats_committee_members' => array (
		'name' => 'Fair Stats -- Committee Members',
		'header' => 'Committee Members',
		'width' => 25.4 /*mm*/,
		'table' => 'fair_stats.committee_members',
		'components' => array('fair_stats')), 
	'fairstats_judges' => array (
		'name' => 'Fair Stats -- Judges',
		'header' => 'Judges',
		'width' => 25.4 /*mm*/,
		'table' => 'fair_stats.judges',
		'components' => array('fair_stats')), 

	/* The label system depends on each report type having fair_name and fair_logo */
	'fair_name' =>  array(
		'start_option_group' => 'Local Fair Information',
                'name' => 'Fair -- Name',
                'header' => 'Fair Name',
                'width' => 76.2 /*mm*/,
		'table' => "'".$mysqli->real_escape_string($config['fair_name'])."'"),
		
        'fair_year' => array (
                'name' => 'Fair -- Year',
                'header' => 'Year',
                'width' => 12.7 /*mm*/,
                'table' => "{$config['year']}"),

	'fair_logo' =>  array(
		'name' => 'Fair -- Logo (for Labels only)',
		'header' => '',
		'width' => 1 /*mm*/,
		'table' => "CONCAT(' ')"),

	'static_text' => array (
		'name' => 'Static Text (useful for labels)',
		'header' => '',
		'width' => 2.54 /*mm*/,
		'table' => "CONCAT(' ')"),

);

 function report_fairs_fromwhere($report, $components)
 {
 	global $config, $report_fairs_fields;
	
	$fields = $report_fairs_fields;
	$year = $report['year'];

	if(in_array('fair_stats', $components)) {
		$fs_from = 'LEFT JOIN fair_stats ON fair_stats.fair_id=fairs.id';
		$fs_where = "fair_stats.year='$year' AND";

	}

	$q = "	FROM 	fairs 
			$fs_from
		WHERE
			$fs_where
			1
		";

	return $q;
}


?>
