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
require_once('debug.inc.php');
require_once('stats.inc.php');


/* Send a command to a remote fair */
function remote_query($mysqli, &$fair, &$cmd)
{
	/* Create a token, 128 chars */
	$v = base64_encode(mcrypt_create_iv(96, MCRYPT_DEV_URANDOM));
	$mysqli->real_query("UPDATE fairs SET token='$v' WHERE id='{$fair['id']}'");

	if($fair['password'] === NULL || $fair['url'] === NULL) {
		$response = array('error'=>1, 'invalid URL or password for fair');
		return $response;
	}

	$cmd['token'] = $v;
	$cmd['password'] = $fair['password'];

	debug("remote_query: curl to {$fair['url']}/remote.php  query:".print_r($cmd, true)."\n");

	
	$post_fields = "d=".urlencode(json_encode($cmd));

	$ch = curl_init(); /// initialize a cURL session
	curl_setopt ($ch, CURLOPT_URL, $fair['url'].'/remote.php');
	curl_setopt ($ch, CURLOPT_HEADER, 0); // Header control
	curl_setopt ($ch, CURLOPT_POST, 1);  // tell it to make a POST, not a GET
	curl_setopt ($ch, CURLOPT_POSTFIELDS, $post_fields);  // put the query string here starting with "?"
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); // This allows the output to be set into a variable $datastream
	curl_setopt ($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt ($ch, CURLOPT_SSLVERSION, 3);
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
	$remote_response = curl_exec ($ch); 
	$c_errno = curl_errno($ch);
	$c_error = curl_error($ch);
	curl_close ($ch); 

	debug("raw response: $remote_response\n");

	if($c_errno > 0) {
		debug("curl error: [$c_errno] $c_error\n");
		$response = array('error'=>1);
	} else {
		$response = json_decode($remote_response, true);
		if(!is_array($response)) {
			$response = array('error'=>1);
		}
	}
	debug("remote_query: curl response:".print_r($response, true)."\n");
	debug("remote_query: remove token for {$fair['name']}\n");
	/* Remove the token */
	$mysqli->real_query("UPDATE fairs SET token='' WHERE id={$fair['id']}");

	return $response;
}

/* Handle a check token request.  Compares the request token to the
 *  last token from a fair. */
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

/* Call to check a token, returns true if the token is valid for $fair */
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

/* Unused */
function remote_encrypt(&$fair, $text)
{
	global $config;

	$v = mcrypt_create_iv(96, MCRYPT_DEV_URANDOM);

	/* encrypt the password with our private key, then the remote's public key */
	if(!openssl_private_encrypt($v, $enc1, $config['private_key'])) {
		debug("priv encrypt failed\n");
		print("local privatekey encrypt failed.  Key={$config['private_key']}\n");
		print("error: ".openssl_error_string()."\n");
		return NULL;
	}
	if(!openssl_public_encrypt($enc1, $signed_password, $fair['public_key'])) {
		debug("pub encrypt failed\n");
		print("remote publickey encrypt failed.  Key=\n{$fair['public_key']}\n");
		print("error: ".openssl_error_string()."\n");
		return NULL;
	}

	/* Encrypt the text */
	$encrypted_text = openssl_encrypt($text, "aes-256-cbc", $v);
	return $signed_password.$encrypted_text;
}

/* Unused */
function remote_decrypt(&$fair, $encrypted_text) 
{
	global $config;

	$encrypted_password = substr($encrypted_text, 0, 512);

	/* Decrypt the command with our private key, then their public key */
	if(!openssl_private_decrypt($encrypted_password, $de1, $config['private_key'])) {
		debug("   private decrypt failed\n");
		return NULL;
	}
	if(!openssl_public_decrypt($de1, $password, $fair['public_key'])) {
		debug("   public decrypt failed.\n");
		return NULL;
	}

	$text = openssl_decrypt($substr($encrypted_text, 512), "aes-256-cbc", $password);

	return $text;
}

/* Push the given award to all fairs, and delete it from ones who aren't allowed to have it */
function remote_push_award_to_all_fairs($mysqli, &$award)
{
	$fairs = fair_load_all_feeder($mysqli);
	foreach($fairs as $fair_id=>$fair) {
		remote_push_award_to_fair($mysqli, $fair, $award);
	}
}

/* Queue a command to push the given given award to all fairs, then start the queue runner. */
function remote_queue_push_award_to_all_fairs($mysqli, &$award) 
{
	$fairs = fair_load_all_feeder($mysqli);
	foreach($fairs as $fair_id=>$fair) {
		$mysqli->real_query("INSERT INTO queue(`command`,`fair_id`,`award_id`,`result`) VALUES('push_award','$fair_id','{$award['id']}','queued')");
	}
	queue_start($mysqli);
}

/* Call to actually push the award to a remote fair */
function remote_push_award_to_fair($mysqli, &$fair, &$award)
{
	$cmd['push_award'] = award_get_export($mysqli, $fair, $award);
	$response = remote_query($mysqli, $fair, $cmd);

	sfiab_log_push_award($mysqli, $fair['id'], $award['id'], $response['error'], $award['name']);
	
	return $response['error'];
}

/* Handle an award push request from an upstream fair pushing an award to us */
function remote_handle_push_award($mysqli, &$fair, &$data, &$response) 
{
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

function remote_push_winner_to_fair($mysqli, $prize_id, $project_id)
{
	$prize = prize_load($mysqli, $prize_id);
	$award = award_load($mysqli, $prize['award_id']);
	$fair = fair_load($mysqli, $award['upstream_fair_id']);
	$project = project_load($mysqli, $project_id);

	debug("remote_push_winner_to_fair ======================================\n");

	$cmd['push_winner'] = array();
	$cmd['push_winner']['prize_id'] = $prize['upstream_prize_id'];
	/* Is this winner attached to this prize? */
	$q = $mysqli->query("SELECT * FROM winners WHERE pid='{$project['pid']}' AND award_prize_id='{$prize['id']}' AND year='{$project['year']}'");
	if($q->num_rows == 0) {
		$cmd['push_winner']['project'] = array('year'=>$project['year'], 'pid'=>$project['pid'], 'delete'=>'1');
	} else {
		$cmd['push_winner']['project'] = project_get_export($mysqli, $fair, $project);
	}
	$response = remote_query($mysqli, $fair, $cmd);
	debug("remote_push_winner_to_fair: response=".print_r($response, true)."\n");

	sfiab_log_push_winner($mysqli, $fair['id'], $award['id'], $prize['id'], $project['pid'], $response['error']);
	return $response['error'];
}

function remote_queue_push_winner_to_fair($mysqli, $prize_id, $project_id)
{
	$mysqli->real_query("INSERT INTO queue(`command`,`fair_id`,`award_id`,`prize_id`,`project_id`,`result`) 
				VALUES('push_winner','','','$prize_id','$project_id','queued')");
	print($mysqli->error);
	queue_start($mysqli);
}

function remote_handle_push_winner($mysqli, &$fair, &$data, &$response)
{
	$incoming_prize_id = (int)$data['push_winner']['prize_id'];
	$incoming_project = &$data['push_winner']['project'];
	$year = (int)$incoming_project['year'];

	debug("push winner: data=".print_r($data, true)."\n");
	debug("push winner: incoming_prize_id=$incoming_prize_id, project_id={$incoming_project['pid']}, year=$year\n");

	if(array_key_exists('delete', $incoming_project)) {
		debug("push winner: delete.\n");
		$inc_pid = (int)$incoming_project['pid'];
		$q = $mysqli->query("SELECT pid FROM projects WHERE feeder_fair_id='{$fair['id']}' AND feeder_fair_pid='$inc_pid' AND year='$year'");
		if($q->num_rows > 0) {
			$r = $q->fetch_row();
			$pid = $r[0];
			$mysqli->real_query("DELETE FROM winners WHERE `award_prize_id`=$incoming_prize_id AND `pid`='$pid' AND year='$year'");
		}
		$response['error'] = 0;
		return;
	}
	$prize = prize_load($mysqli, $incoming_prize_id);
	$award = award_load($mysqli, $prize['award_id']);
	/* Make sure this fair is allowed to push winners.  upstream_prize_id should
	 * be set to upstream_prize_id by the caller, which is the ID in our database */
	if(!in_array($fair['id'], $award['feeder_fair_ids'])) {
		debug("push winner: fair id {$fair['id']} not in award feeder ids: ".print_r($award['feeder_fair_ids'], true)."\n");
		$response['error'] = 1;
		return;
	}

	/* If this award registers students at this fair, override the incoming
	 * project number, otherwise * keep it */
	if($award['upstream_register_winners'] == 1) {
		$incoming_project['number'] = NULL;
	}

	debug("push winner: sync project\n");
	/* Sync the project */
	$p = project_sync($mysqli, $fair, $incoming_project);

	debug("push winner: check fair\n");

	debug("push winner: insert winner\n"); 
	/* Insert the prize */
	$mysqli->query("INSERT INTO winners(`award_prize_id`,`pid`,`year`,`fair_id`) 
			VALUES('$incoming_prize_id','{$p['pid']}','{$p['year']}','{$fair['id']}')");

	/* If this award registers students at this fair, leave the students as incomplete.  
	 * If not, mark the student as complete and accepted.  We could modify incoming_proejct and
	 * save this all at once, but best to let the get_export/sync be mirrors of each other, rather than
	 * sometimes introducing new fields */
	project_load_students($mysqli, $p);
	if($award['upstream_register_winners'] == 0) {
		debug("push winner: set winner to complete/accepted because upstream_register_winners == 0\n"); 
		$p['accepted'] = 1;
		project_save($mysqli, $p);

		foreach($p['students'] as &$u) {
			$u['s_complete'] = 1;
			$u['s_accepted'] = 1;
			$u['s_paid'] = 0;
		}
		user_save($mysqli, $u);
	}

	$response['error'] = 0;
}
	
/* Fair $fair has requested our stats */
function handle_get_stats($mysqli, &$fair, &$data, &$response) 
{
	$year = (int)$data['get_stats']['year'];
	$response['get_stats'] = stats_get_export($mysqli, $fair, $year);
	$response['error'] = 0;
	return true;
}

/* Queue a command to get stats from all fairs */
function remote_queue_get_stats_from_all_fairs($mysqli, $year) 
{
	$fairs = fair_load_all_feeder($mysqli);
	foreach($fairs as $fair_id=>$fair) {
		$mysqli->real_query("INSERT INTO queue(`command`,`fair_id`,`year`,`result`) VALUES('get_stats','$fair_id','$year','queued')");
	}
	queue_start($mysqli);
}

function remote_queue_push_stats_to_fair($mysqli, &$fair, $year)
{
	$mysqli->real_query("INSERT INTO queue(`command`,`fair_id`,`year`,`result`) VALUES('push_stats','$fair_id','$year','queued')");
}

/* Ask $fair for their stats and sync the result */
function remote_get_stats_from_fair($mysqli, &$fair, $year)
{
	/* Year is stored in award_id */
	$cmd['get_stats'] = array();
	$cmd['get_stats']['year'] = $year;
	$response = remote_query($mysqli, $fair, $cmd);

	if($response['error'] == 0) {
		if(is_array($response['get_stats'])) {
			stats_sync($mysqli, $fair, $response['get_stats']);
		}
	}
	$result = ($response['error'] == 0) ? 1 : 0;
	sfiab_log_sync_stats($mysqli, $fair['id'], $result);
	
	return $response['error'];
}

/* Ping (no password required **************************************************/
function remote_ping($mysqli, &$fair)
{
	$cmd['ping'] = array();
	$response = remote_query($mysqli, $fair, $cmd);

	$ret = array();
	$ret['error'] = $response['error'];
	$ret['name'] = '';
	$ret['abbrv'] = '';
	if(array_key_exists('pong', $response)) {
		$ret['name'] = $response['pong']['name'];
		$ret['abbrv'] = $response['pong']['abbrv'];
	}
	return $ret;
}


/* Auth Ping **************************************************/
function remote_auth_ping($mysqli, &$fair)
{
	/* Get an award from an upstream server, specified by the local award_id, but
	 * requested by the upstream award id */
	$cmd['auth_ping'] = array();
	$response = remote_query($mysqli, $fair, $cmd);
	if($response['error'] == 0) {
		award_sync($mysqli, $fair, $response['get_award']);
	}
	return $response['error'];
}

function remote_handle_auth_ping($mysqli, &$fair, &$data, &$response)
{
	global $config;
	$response['auth_pong'] = array('name' => $config['fair_name'],
				  'abbrv' => $config['fair_abbreviation'],
				  'url' => $config['fair_url'] );
	$response['error'] = 0;
}



function remote_handle_old_get_awards($mysqli, &$fair, &$data, &$response)
{
	/* Get an award from an upstream server, specified by the local award_id, but
	 * requested by the upstream award id */
	$awards = array();
	$year = $data['getawards']['year'];

	$response['error'] = 0;

	$ids = array();
	/* Load a list of awards linked to the fair id */
	$q = $mysqli->query("SELECT * FROM awards WHERE year='$year' AND FIND_IN_SET('{$fair['id']}',`feeder_fair_ids`)>0");
	debug("Query: SELECT * FROM awards WHERE FIND_IN_SET('{$fair['id']}', `feeder_fair_ids`)>0\n");
	while($a = $q->fetch_assoc()) {
		debug(print_r($a, true));
		$award = array();
		$award['identifier'] = $a['id'];
		$award['external_additional_materials'] = '';
		$award['external_register_winners'] = $a['upstream_register_winners'];
		$award['year'] = $a['year'];
		$award['name_en'] = $a['name'];
		$award['criteria_en'] = $a['s_desc'];
		$award['upload_winners'] = '1';
		$award['self_nominate'] = $a['self_nominate'];
		$award['schedule_judges'] = $a['schedule_judges'];
		
		if($a['sponsor_uid']) {
			$sq = $mysqli->query("SELECT * FROM users WHERE uid='{$a['sponsor_uid']}'");
			if($sq->num_rows) {
				$s =  $sq->fetch_assoc();
				$award['sponsor'] = $s['organization'];
			}
		}

		$award['prizes'] = array();
		$pq = $mysqli->query("SELECT * FROM award_prizes WHERE award_id='{$a['id']}'");
		while($p = $pq->fetch_assoc()) {
			/* Map array keys -> local database field */
			$map = array(	'cash' => 'cash', 'scholarship' => 'scholarship',
					'value' => 'value', 'prize_en' => 'name', 'number'=>'number',
					'ord'=>'ord');
			$prize = array('identifier' => '');
			foreach($map as $k=>$field) $prize[$k] = $p[$field];

			$prize['trophystudentkeeper']='0';
			$prize['trophystudentreturn']='0';
			$prize['trophyschoolkeeper']='0';
			$prize['trophyschoolreturn']='0';

			$award['prizes'][] = $prize;
		}
		$awards[] = $award;
	}
	$response['awards'] = $awards;

	return $response['error'];
}

function remote_handle_old_get_categories($mysqli, &$fair, &$data, &$response)
{
	$year = intval($data['get_categories']['year']);
	$cat = array();
	$q=$mysqli->query("SELECT * FROM categories WHERE year='$year' ORDER BY cat_id");
	while($r=$q->fetch_object()) {
	        $cat[$r->cat_id]=array('id' => $r->cat_id,
				'category' => $r->name,
				'mingrade' => $r->min_grade,
				'maxgrade' => $r->max_grade);
	}
	$response['categories'] = $cat;
	$response['error'] = 0;
}

function remote_handle_old_get_divisions($mysqli, &$fair, &$data, &$response)
{
	$year = intval($data['get_divisions']['year']);
	$div = array();
	$q=$mysqli->query("SELECT * FROM challenges WHERE year='$year' ORDER BY chal_id");
	while($r=$q->fetch_object()) {
		$div[$r->chal_id] = array('id' => $r->chal_id,
				'division' => $r->name);
	}
	$response['divisions'] = $div;
	$response['error'] = 0;
}
function remote_handle_old_upload_assign($mysqli, &$fair, &$data, &$response)
{
	$challenges = challenges_load($mysqli);

	foreach($data['awards_upload'] as &$up) {
		$our_award_id = $up['external_identifier'];
		$year = $up['year'];

		/* Find the award */
		$a = award_load($mysqli, $our_award_id);
		debug("Loaded award to accept remote upload of winners: {$a['name']}\n");

		foreach($up['prizes'] as &$incoming_prize) {
			$prize_name = $incoming_prize['name'];

			/* Find this prize in the award */
			$match = false;
			foreach($a['prizes'] as $prize) {
				if($prize['name'] == $prize_name) {
					$match = true;
					break;
				}
			}
			if($match == false) {
				debug("Unable to find prize $prize_name in award\n");
				continue;
			}

			/* Pull in the projects */
			foreach($incoming_prize['projects'] as &$incoming_project) {
				/* Construct a valid local project, then just call project_sync */
				$p = array();
				$p['pid'] = $incoming_project['projectid'];
				$p['title'] = $incoming_project['title'];
				$p['tagline'] = '';
				$p['abstract'] = $incoming_project['abstract'];
				$p['language'] = $incoming_project['language'];
				$p['number'] = $incoming_project['projectnumber'];
				$p['req_electricity'] = 0;
				$p['challenge'] = $challenges[$incoming_project['projectdivisions_id']]['name'];
				$p['year'] = $year;

				$p['mentors'] = array();
				$p['num_mentors'] = 0;

				$p['students'] = array();
				foreach($incoming_project['students'] as &$incoming_student) {
					$s = array();
					$s['year'] = $year;
					$s['uid'] = ($p['pid']*10) + count($p['students']); /* Create a number to uniquely identifiy this user */
					$s['unique_uid'] = $s['uid'];
					$s['roles'] = array('student');
					$s['username'] = $incoming_student['email'];
					$s['salutation'] = '';
					$s['firstname'] = $incoming_student['firstname'];
					$s['lastname'] = $incoming_student['lastname'];
					$s['pronounce'] = NULL;
					$s['sex'] = $incoming_student['gender'];
					$s['email'] = $incoming_student['email'];
					$s['grade'] = $incoming_student['grade'];
					$s['language'] = $incoming_student['language'];
					$s['birthdate'] = $incoming_student['birthdate'];
					$s['address'] = $incoming_student['address'];
					$s['city'] = $incoming_student['city'];
					$s['postalcode'] = $incoming_student['postalcode'];
					$s['province'] = $incoming_student['school']['province_code'];
					$s['phone1'] = $incoming_student['phone'];
					$s['phone2'] = NULL;
					$s['organization'] = '';
					$s['medicalert'] = NULL;
					$s['food_req'] = '';
					$s['s_teacher'] = $incoming_student['teachername'];
					$s['s_teacher_email'] = $incoming_student['teacheremail'];

					$s['emergency_contacts'] = array();

					$s['school'] = array();
					$s['school']['school'] = $incoming_student['school']['schoolname'];
					$s['school']['city'] = $incoming_student['school']['city'];
					$s['school']['province'] = $incoming_student['school']['province_code'];

					$p['students'][] = $s;
				}

				$push_winner = array();
				$push_winner['prize_id'] = $prize['id'];
				$push_winner['project'] = $p;


				$push_response = array();
				$cmd = array('push_winner' => $push_winner);
				debug("Push winner command: ".print_r($push_winner, true)."\n");
				remote_handle_push_winner($mysqli, $fair, $cmd, $push_response);

			}
		}
	}
}



?>
