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
require_once('debug.inc.php');


/* Send a command to a remote fair */
function remote_query($mysqli, &$fair, &$cmd)
{
	/* Create a token and store it, this will give a 128 char token with chars
	 * that don't need to be escaped */
	$v = base64_encode(mcrypt_create_iv(96));

	debug("remote_query: set token for fair {$fair['name']}, token: $v\n");
	$mysqli->real_query("UPDATE fairs SET token='$v' WHERE id={$fair['id']}");
	/* Attach to the command send send it along, the remote will query this
	 * token using their own fair location URL */
	$cmd['token'] = $v;
	$cmd['password'] = $fair['password'];
	debug("remote_query: curl query:".print_r($cmd, true)."\n");
	$response = curl_query($fair, $cmd);
	debug("remote_query: curl response:".print_r($response, true)."\n");

	debug("remote_query: remove token for fair {$fair['name']}\n");
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

function remote_queue_push_award_to_all_fairs($mysqli, &$award) 
{
	$fairs = fair_load_all_feeder($mysqli);
	foreach($fairs as $fair_id=>$fair) {
		$mysqli->real_query("INSERT INTO queue(`command`,`fair_id`,`award_id`,`result`) VALUES('push_award','$fair_id','{$award['id']}','queued')");
	}
	queue_start($mysqli);

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


function remote_push_winner_to_fair($mysqli, &$fair, &$prize, &$project)
{
	$cmd['push_winner'] = array();
	$cmd['push_winner']['prize_id'] = $prize['upstream_prize_id'];
	$cmd['push_winner']['project'] = project_get_export($mysqli, $project);
	$response = remote_query($mysqli, $fair, $cmd);
	return $response['error'];
}

function remote_queue_push_winner_to_fair($mysqli, &$fair, &$prize, &$project)
{
	$mysqli->real_query("INSERT INTO queue(`command`,`fair_id`,`award_id`,`prize_id`,`project_id`,`result`) 
				VALUES('push_award','$fair_id','{$prize['award_id']}','{$prize['id']}','{$project['pid']}','queued')");
	queue_start($mysqli);
}

function remote_handle_push_winner($mysqli, &$fair, &$data, &$response)
{
	$incoming_prize_id = $data['prize_id'];
	$incoming_project = &$data['project'];
	/* Sync the project */
	$p = project_sync($mysqli, $fair, $incoming_project);

	/* Make sure this fair is allowed to push winners.  incoming_prize_id should
	 * be set to upstream_prize_id by the caller, which is the ID in our database */
	$prize = prize_load($mysqli, $incoming_prize_id);
	$award = award_load($mysqli, $prize['award_id']);
	if(!in_array($fair['id'], $award['feeder_fair_ids'])) {
		$response['error'] = 1;
		return;
	}

	/* Insert the prize */
	$mysqli->query("INSERT INTO winners(`award_prize_id`,`pid`,`year`,`fair_id`) 
			VALUES('$incoming_prize_id','{$p['pid']}','{$p['year']}','{$fair['id']}')");

	$response['error'] = 0;
}
	


function remote_push_finalize_winners()
{
}


/* Called when a feeder fair wants to finalize the winners for a prize that
 * is marked as "upstream register winners".  That means we have to iterate
 * over all the winners, email them, and ready their accounts to login */
function remote_handle_finalize_winners()
{
			/* This award is for students who are participating in this fair, we need
			 * to get their reg number to them if this is a new registration 
			 * Only send it if they weren't matched to a student already in this project */
			$result = email_send($mysqli, "New Registration", $sid, array('PASSWORD'=>$password) );
			$response['notice'][] = "         - Sent welcome registration email to: {$s['firstname']} {$s['lastname']} &lt;{$s['email']}&gt;";
			sfiab_log($mysqli, "register", "username: {$username}, email: {$s['email']}, as: student, email status: $result");

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


?>
