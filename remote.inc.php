<?php
/* 
   This file is part of the 'Science Fair In A Box' project
   SFIAB Website: http://www.sfiab.ca

   Copyright (C) 2005 Sci-Tech Ontario Inc <info@scitechontario.org>
   Copyright (C) 2005 James Grant <james@lightbox.org>
   Copyright (C) 2009 David Grant <dave@lightbox.org>

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
require_once('user.inc.php');
require_once('awards.inc.php');
require_once('fairs.inc.php');
require_once('project.inc.php');
require_once('email.inc.php');
require_once('curl.inc.php');


/* Send a command to a remote fair */
function remote_query($mysqli, &$fair, &$cmd)
{
	/* Create a token and store it, this will give a 128 char token with chars
	 * that don't need to be escaped */
	$v = base64_encode(mcrypt_create_iv(96));

	$mysqli->real_query("UPDATE fairs SET token='$v' WHERE id={$fair['id']}");
	/* Attach to the command send send it along, the remote will query this
	 * token using their own fair location URL */
	$cmd['token'] = $v;
	$cmd['password'] = $fair['password'];
//	print("query: ".print_r($cmd, true)."\n");
	$response = curl_query($fair, $cmd);
//	print("response: ".print_r($response, true)."\n");

	/* Remove the token */
	$mysqli->real_query("UPDATE fairs SET token='' WHERE id={$fair['id']}");

	return $response;
}

/* Given a token, check it to see if it matches the command that was sent */
function remote_handle_check_token($mysqli, &$fair, &$data, &$response)
{
	if(strlen($fair['token']) != 128) {
		$response['error']  = 1;
		return;
	}

	if(strlen($data['check_token']) != 128) {
		$response['error'] = 1;
		return;
	}

	$response['check_token'] = ($data['check_token'] == $fair['token']) ? 1 : 0;
	$response['error'] = 0;
}

function remote_check_token($mysqli, &$fair, $token)
{
	$cmd = array();
	$cmd['check_token'] = $token;
	$response = remote_query($mysqli, $fair, $cmd);
	if($response['error'] == 0) {
		return ($response['check_token'] == true) ? true : false;
	}
	return false;
}


function remote_push_award_to_all_fairs($mysqli, &$award)
{
	$fairs = fair_load_all_feeder($mysqli);
	foreach($fairs as $fair_id=>$fair) {
		remote_push_award_to_fair($mysqli, $fair, $award);
	}
}

function remote_push_award_to_fair($mysqli, &$fair, &$award)
{
	/* Push an award to a single feeder fair */
	$cmd['push_award'] = award_get_export($mysqli, $fair, $award);
	$response = remote_query($mysqli, $fair, $cmd);
	return $response['error'];
}

function remote_handle_push_award($mysqli, &$fair, &$data, &$response) 
{
	/* Handle an incoming push request, sync the award */
	$incoming_award = &$data['push_award'];
 	award_sync($mysqli, $fair, $incoming_award);
	$response['push_award'] = array('error' => 0);
}

function remote_get_award($mysqli, $award_id)
{
	/* Get an award from an upstream server, specified by the local award_id, but
	 * requested by the upstream award id */
	$a = award_load($mysqli, $award_id);
	$fair = fair_load($mysqli, $a['upstream_fair_id']);
	$cmd['get_award'] = $a['upstream_award_id'];
	$response = remote_query($mysqli, $fair, $cmd);
	if($response['error'] == 0) {
		award_sync($mysqli, $fair, $response['get_award']);
	}
	return $response['error'];
}

function remote_handle_get_award($mysqli, &$fair, &$data, &$response)
{
	/* Handle a get award request from a feeder fair, return the award they ask for */
	$award_id = $data['get_award'];
	$a = award_load($mysqli, $award_id);
	$response['get_award'] = award_get_export($mysqli, $fair, $a);
}





function handle_getstats(&$u, $fair,&$data, &$response)
{
	$year = $data['getstats']['year'];

	/* Send back the stats we'd like to collect */
	$response['statconfig'] = split(',', $fair['gather_stats']);

	/* Send back the stats we currently have */
	$q = mysql_query("SELECT * FROM fairs_stats WHERE fair_id='{$u['fair_id']}'
				AND year='$year'");
	$response['stats'] = mysql_fetch_assoc($q);
	unset($response['stats']['id']);
	$response['error'] = 0;
}

function handle_stats(&$u,$fair, &$data, &$response)
{
	$stats = $data['stats'];
	foreach($stats as $k=>$v) {
		$stats[$k] = mysql_escape_string($stats[$k]);
	}

//	$str = join(',',$stats);
	$keys = '`fair_id`,`'.join('`,`', array_keys($stats)).'`';
	$vals = "'{$u['fair_id']}','".join("','", array_values($stats))."'";
	mysql_query("DELETE FROM fairs_stats WHERE fair_id='{$u['fair_id']}'
		AND year='{$stats['year']}'");
	echo mysql_error();
	mysql_query("INSERT INTO fairs_stats (`id`,$keys) VALUES ('',$vals)");
	echo mysql_error();

	$response['message'] = 'Stats saved';
	$response['error'] = 0;
}

function handle_getawards($mysqli, &$u, $fair, &$data, &$response)
{
	$awards = array();
	$year = $data['getawards']['year'];

	$ids = array();
	/* Load a list of awards linked to the fair id */
	foreach($fair['award_ids'] as $aid) {
		$a = award_load($mysqli, $aid);	

	
		$award['identifier'] = $a['id'];
		$award['external_additional_materials'] = '';
		$award['year'] = $a['year'];
		$award['name_en'] = $a['name'];
		$award['criteria_en'] = $a['s_desc'];
		$award['upload_winners'] = 'yes';
		$award['self_nominate'] = $a['self_nominate'] ? "yes" : "no";
		$award['schedule_judges'] = $a['schedule_judges'] ? "yes" : "no";
		$award['sponsor'] = $a['sponsor'];

		$external_register_winners = 0;
		$award['prizes'] = array();
		foreach($a['prizes'] as &$p) {
			/* Map array keys -> local database field */
			$prize['cash'] = $p['cash'];
			$prize['scholarship'] = $p['scholarship'];
			$prize['value'] = $p['value'];
			$prize['prize_en'] = $p['name'];
			$prize['number'] = $p['number'];
			$prize['trophystudentkeeper'] = in_array('keeper', $p['trophies']) ? 1 : 0;
			$prize['trophystudentreturn'] = in_array('return', $p['trophies']) ? 1 : 0;
			$prize['trophyschoolkeeper'] = in_array('school_keeper', $p['trophies']) ? 1 : 0;
			$prize['trophyschoolreturn'] = in_array('school_keeper', $p['trophies']) ? 1 : 0;
			$prize['identifier'] = '';
			if($p['external_register_winners'] == 1) $external_register_winners = 1;

			$award['prizes'][] = $prize;
		}
		$award['external_register_winners'] = $external_register_winners;
		$awards[] = $award;
	}
	$response['awards'] = $awards;
	$response['postback'] = 'http://localhost';
}

function award_upload_update_school($mysqli, &$mysql_query, &$school, $school_id = -1)
{
	if($mysql_query !== NULL) {
		$s = $mysql_query->fetch_assoc();
		return $s['id'];
	}

	/* transport name => mysql name */
	$school_fields = array( //'schoolname'=>'school',
				'schoollang'=>'schoollang',
				'schoollevel'=>'schoollevel',
				'board'=>'board',
				'district'=>'district',
				'phone'=>'phone',
				'fax'=>'fax',
				'address'=>'address',
				'city'=>'city',
				'province_code'=>'province_code',
				'postalcode'=>'postalcode',
				'schoolemail'=>'schoolemail');
/*				'principal'=>'principal',
				'sciencehead'=>'sciencehead',
				'scienceheademail'=>'scienceheademail',
				'scienceheadphone'=>'scienceheadphone');*/

	$sid = $school_id;
	$our_school = array();

	$set = '';
	foreach($school_fields as $t=>$m) {
		if(array_key_exists($m, $our_school) && $our_school[$m] == $school[$t]) continue;
		if($set != '') $set.=',';
		$set .= "`$m`='".$mysqli->real_escape_string($school[$t])."'";
	}
	$mysqli->real_query("UPDATE schools SET $set WHERE id='$sid'");
	return $sid;
}

function award_upload_school($mysqli, &$student, &$school, $year, &$response)
{

	$school_name = $mysqli->real_escape_string($school['schoolname']);
	$school_city = $mysqli->real_escape_string($school['city']);
	$school_phone = $mysqli->real_escape_string($school['phone']);
	$school_addr = $mysqli->real_escape_string($school['address']);
	$student_city = $student['city'];

	/* Find school by matching name, city, phone, year */
	$q = $mysqli->query("SELECT * FROM schools WHERE school='$school_name' AND city='$school_city' AND phone='$school_phone' AND year='$year'");
	if($q->num_rows == 1) return award_upload_update_school($mysqli, $q, $school);

	/* Find school by matching name, city, address, year */
	$q = $mysqli->query("SELECT * FROM schools WHERE school='$school_name' AND city='$school_city' AND address='$school_addr' AND year='$year'");
	if($q->num_rows  == 1) return award_upload_update_school($mysqli, $q, $school);

	/* Find school by matching name, city, year */
	$q = $mysqli->query("SELECT * FROM schools WHERE school='$school_name' AND city='$school_city' AND year='$year'");
	if($q->num_rows  == 1) return award_upload_update_school($mysqli, $q, $school);

	/* Find school by matching name, student city, year */
	$q = $mysqli->query("SELECT * FROM schools WHERE school='$school_name' AND city='$student_city' AND year='$year'");
	if($q->num_rows == 1) return award_upload_update_school($mysqli, $q, $school);

	$response['notice'][] = "      - Creating new school: $school_name";
	/* No? ok, make a new school */
	$mysqli->query("INSERT INTO schools(`school`,`year`) VALUES ('$school_name','$year')");
	$school_id = $mysqli->insert_id;
	$q = NULL;
	return award_upload_update_school($mysqli, $q, $school, $school_id);
}

function award_upload_assign($mysqli, &$fair, &$award, &$prize, &$remote_project, $year, &$response)
{
	$pn = $mysqli->real_escape_string($remote_project['projectnumber']);

	/* Sanity check a few things */
	if(count($remote_project['students']) > 2) {
		$c = count($remote_project['students']);
		$response['notice'][] = "   - ERROR uploading project : $pn, {$remote_project['title']}";
		$response['notice'][] = "      - too many students: $c";
		foreach($remote_project['students'] as &$remote_student) {
			$response['notice'][] = "         - {$remote_student['firstname']} {$remote_student['lastname']}";
		}
		return;
	}

	/* See if this project already exists */
	$q = $mysqli->query("SELECT * FROM projects WHERE number='$pn' AND fair_id='{$fair['id']}' AND year='$year'");
	if($q->num_rows == 1) {
		/* Project with this number+fairid+year already exists */
		$p = $q->fetch_assoc();
		$project = project_load($mysqli, $p['pid'], $p);
		$pid = $project['pid'];
		$response['notice'][] = "   - Found existing project: $pn, {$remote_project['title']}";
	} else {
		$response['notice'][] = "   - Creating new project: $pn, {$remote_project['title']}";

		$pid = project_create($mysqli);
		$project = project_load($mysqli, $pid);

		$project['year'] = $year;
		$project['number'] = $pn;
		$project['fair_id'] = $fair['id'];
	}
	/* Update the project in case anything changed (besides the project number, which will trigger
	 * a whole new registration */
	$project['title'] = $remote_project['title'];
	$project['summary'] = $remote_project['abstract'];
	$project['cat_id'] = $remote_project['projectcategories_id'];
	$project['challenge_id'] = $remote_project['projectdivisions_id'];
	$project['num_students'] = count($remote_project['students']);
	project_save($mysqli, $project);

	project_load_students($mysqli, $project);

	/* Remember if we matched remote students */
	foreach($remote_project['students'] as &$remote_student) {
		$remote_student['matched'] = false;
	}

	/* Check all students currently attached to the project (none if the project is new)  */
	foreach($p['students']as &$ps) {
		/* Is this remote student already attached to the project?, check by name */
		$match = false;
		foreach($remote_project['students'] as &$remote_student) {
			if($remote_student['firstname'] == $ps['firstname'] && $remote_student['lastname'] == $ps['lastname']) {
				/* Already in this project */
				$remote_student['matched'] = true;
				$remote_student['sid'] = $ps['uid'];
				$match = true;
				$response['notice'][] = "      - Found existing student {$remote_student['firstname']} {$remote_student['lastname']} ";
				break;
			}
		}

		/* If the student isn't matched, delete them from the project */
		if($match == false) {
			/* Delete the students attached to this project */
			$response['notice'][] = "      - Deleted student {$ps['firstname']} {$ps['lastname']} ";
			$mysqli->real_query("DELETE FROM users WHERE uid='{$ps['uid']}'");
		}
	}

	/* Any unmatched remote students must be new to the project */
	foreach($remote_project['students'] as &$remote_student) {
		if($remote_student['matched'] == false) {

			/* Create new student and attach to project */
			$username = $mysqli->real_escape_string(strstr($remote_student['email'], '@', true));
			$check_username = $username;
			$x = 1;
			while(1) {
				$q = $mysqli->query("SELECT * FROM users WHERE username='$check_username' AND year='$year'");
				if($q->num_rows == 0) {
					$username = $check_username;
					break;
				}
				$check_username = $username.".".$x;
				$x++;
			}
			$password = NULL;
			$sid = user_create($mysqli, $username, $remote_student['email'], 'student', $year, $password);
			$s = user_load($mysqli, $sid);
			$s['s_pid'] = $pid;
			$s['enabled'] = 1;
			$s['year'] = $year;
			$s['fair_id'] = $fair['id'];
			$s['firstname'] = $remote_student['firstname'];
			$s['lastname'] = $remote_student['lastname'];
			user_save($mysqli, $s);
			$response['notice'][] = "      - Created new student {$remote_student['firstname']} {$remote_student['lastname']}  ($username)";
			

			$remote_student['sid'] = $sid;
		}
	}

	/* Update the info for all students */
	foreach($remote_project['students'] as &$remote_student) {

		/* Load this student using the saved sid */
		$s = user_load($mysqli, $remote_student['sid']);

		$schools_id = award_upload_school($mysqli, $remote_student, $remote_student['school'], $year, $response);

		$s['sex'] = $remote_student['gender'];
		$s['birthdate'] = $remote_student['birthdate'];
		$s['address'] = $remote_student['address'];
		$s['city'] = $remote_student['city'];
		$s['province'] = strtolower($remote_student['province']);
		$s['postalcode'] = $remote_student['postalcode'];
		$s['phone1'] = $remote_student['phone'];
		$s['s_teacher'] = $remote_student['teachername'];
		$s['s_teacher_email'] = $remote_student['teacheremail'];
		$s['grade'] = $remote_student['grade'];
		$s['schools_id'] = $schools_id;

		$response['notice'][] = "      - Updated {$remote_student['firstname']} {$remote_student['lastname']}";
		
		if($prize['external_register_winners'] == 0) {
			/* Set to complete even if the data isn't, so we can query them */
			$s['s_complete'] = 1;
			$s['s_accepted'] = 1;
			$s['username'] = '*'.$username;
		}
		user_save($mysqli, $s);

		if($prize['external_register_winners'] == 1 && $remote_student['matched'] == false) {
			/* This award is for students who are participating in this fair, we need
			 * to get their reg number to them if this is a new registration 
			 * Only send it if they weren't matched to a student already in this project */
			$result = email_send($mysqli, "New Registration", $sid, array('PASSWORD'=>$password) );
			$response['notice'][] = "         - Sent welcome registration email to: {$s['firstname']} {$s['lastname']} &lt;{$s['email']}&gt;";
			sfiab_log($mysqli, "register", "username: {$username}, email: {$s['email']}, as: student, email status: $result");
		}
	}

	/* Record the winner */
	$mysqli->real_query("INSERT INTO winners(`award_prize_id`,`pid`,`year`,`fair_id`)
			VALUES('{$prize['id']}','$pid','$year','{$fair['id']}')");
}

function handle_awards_upload($mysqli, &$u, &$fair, &$data, &$response)
{

//	$response['debug'] = array_keys($data['awards_upload']);
//	$response['error'] = 0;
//	return;
	foreach($data['awards_upload'] as $award_data) {
		$external_identifier = (int)$award_data['external_identifier'];
		$year = intval($award_data['year']);

		$award = award_load($mysqli, $external_identifier);
		if($award === NULL || !is_array($award)) {
			$response['message'] = "Unknown award identifier '$external_identifier' for year $year";
			$response['error'] = 1;
			return;
		}
		$aaid = $award['id'];

		$response['notice'][] = "Found award: {$award['name']}";

		foreach($award['prizes'] as &$prize) {
			$response['notice'][] = " - Prize: {$prize['name']}";

			/* Clean out existing winners for this prize */
			$mysqli->real_query("DELETE FROM winners WHERE 
					award_prize_id='{$prize['id']}' 
					AND fair_id='{$fair['id']}'");

			/* Iterate over all prizes of the same name */
			$ul_p =& $award_data['prizes'][$prize['name']];
			if(!is_array($ul_p['projects'])) continue;

			foreach($ul_p['projects'] as &$project) {
				award_upload_assign($mysqli, $fair, $award, $prize, $project, $year, $response);
			}
		}
	}
	$response['notice'][] = 'All awards and winners saved';
	$response['error'] = 0;
}

function handle_get_categories($mysqli, &$u, &$fair, &$data, &$response)
{
	$year = intval($data['get_categories']['year']);
	$cats = categories_load($mysqli, $year);
	$ecat = array();
	foreach($cats as $c) {
	        $ecat[$c['id']]=array('id' => $c['id'],
				'category' => $c['name'],
				'mingrade' => $c['min_grade'],
				'maxgrade' => $c['max_grade']);
	}
	$response['categories'] = $ecat;
	$response['error'] = 0;
}

function handle_get_divisions($mysqli, &$u, &$fair, &$data, &$response)
{
	$year = intval($data['get_divisions']['year']);
	$chals = challenges_load($mysqli, $year);
	$ediv = array();
	foreach($chals as $c) {
	        $ediv[$c['id']]=array('id' => $c['id'],
				'division' => $c['name']);
	}
	$response['divisions'] = $ediv;
	$response['error'] = 0;
}

function handle_award_additional_materials(&$u, &$fair, &$data, &$response)
{
	$year = intval($data['award_additional_materials']['year']);
	$external_identifier = $data['award_additional_materials']['identifier'];

	$eid = mysql_real_escape_string($external_identifier);
	$q = mysql_query("SELECT * FROM award_awards WHERE external_identifier='$eid' AND year='$year'");
	if(mysql_num_rows($q) != 1) {
		$response['message'] = "Unknown award identifier '$eid'";
		$response['error'] = 1;
		return;
	}
	$award = mysql_fetch_assoc($q);

	$pdf = fair_additional_materials($fair, $award, $year);
	$response['award_additional_materials']['pdf']['header'] = $pdf['header'];
	$response['award_additional_materials']['pdf']['data64'] = base64_encode($pdf['data']);
	$response['error'] = 0;
}

?>
