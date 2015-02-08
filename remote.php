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
require_once('fairs.inc.php');
require_once('remote.inc.php');
require_once('debug.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);


/* According to PHP, $_POST is already urldecoded, so don't mirror our urlencode */
if(!array_key_exists('d', $_POST)) {
	debug("data sent to server is missing command: ".print_r($_POST, true));

	/* Hack to support old sfiab */
	if(array_key_exists('json', $_POST)) {
		debug("Attempting to convert from old sfiab json query\n");
		$data =  json_decode($_POST['json'], true);
		$data['password'] = $data['auth']['password'];
	} else {
		exit();
	}
} else {
	$data = json_decode($_POST['d'], true);
}

$password = $data['password'];
$fair = fair_load_by_hash($mysqli, $password);

if($fair === NULL) {
	debug("Coudln't find fair for hash: $password\n");
	exit();
}

$fair['old_sfiab'] = ($fair['username'] == '') ? false : true;

debug("Incoming command matched password for fair: {$fair['name']}\n");
debug("Decoded Command:".print_r($data, true)."\n");
if($fair['old_sfiab']) {
	debug("Using OLD sfiab support\n");
}

$response = remote_handle_cmd($mysqli, $fair, $data);

/* Send it back */
debug("response:".print_r($response, true)."\n");
print(json_encode($response));
exit();


function remote_handle_cmd($mysqli, &$fair, &$data) 
{
	$response = array();
	/* Fair must exist */
	if($fair === NULL) {
		$response['error'] = 1;
		$response['message'] = "Authentication Failed";
		return $response;
	}
	/* Must have a password set */
	if(!is_array($fair) || $fair['password'] == '') {
		$response['error'] = 1;
		$response['message'] = "Authentication Failed2";
		return $response;
	}
	/* Process a check token before checking a token back, we don't want to bounce
	 * back and forth checking tokens, but a check_token is the only command we will
	 * process without checking a token */
	if(array_key_exists('check_token', $data)) {
		debug("check token for fair:".print_r($fair, true)."\n");
		remote_handle_check_token($mysqli, $fair, $data, $response);
		return $response;
	}
 
	/* Check the token in the command by communicating back with the fair URL we have on record, 
	 * hack for old support, if there is a fair username, skip the token check */
	if($fair['old_sfiab'] == false && remote_check_token($mysqli, $fair, $data['token']) == false) {
 		$response['error'] = 1;
		$response['message'] = "Authentication Failed4";
		return $response;
	}


	if($fair['old_sfiab']) {
		/* Old allow a few commands */
		if(array_key_exists('getawards', $data)) remote_handle_old_get_awards($mysqli, $fair, $data, $response);
		if(array_key_exists('get_categories', $data)) remote_handle_old_get_categories($mysqli, $fair, $data, $response);
		if(array_key_exists('get_divisions', $data)) remote_handle_old_get_divisions($mysqli, $fair, $data, $response);
		if(array_key_exists('awards_upload', $data)) remote_handle_old_upload_assign($mysqli, $fair, $data, $response);


		$response['hi'] = 'hi';
		return $response;
	}

	/* Working */
	if(array_key_exists('push_award', $data)) remote_handle_push_award($mysqli, $fair, $data, $response);
	if(array_key_exists('push_winner', $data)) remote_handle_push_winner($mysqli, $fair, $data, $response);
	if(array_key_exists('get_stats', $data)) handle_get_stats($mysqli,$fair, $data, $response);

	/* Should work */
	if(array_key_exists('get_award', $data)) remote_handle_get_award($mysqli, $fair, $data, $response);

	$response['hi'] = 'hi';
	return $response;
}


?>
