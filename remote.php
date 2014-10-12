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

$data = json_decode($_POST['json'], true);


$fair = fair_load_by_hash($mysqli, $data['password']);

debug("This Fair: {$config['fair_name']}\n");
debug("Matched password from fair: {$fair['name']}\n");
debug("Decoded Command:".print_r($data, true)."\n");

$response = array();

/* Fair must exist */
if($fair === NULL) {
 	$response['error'] = 1;
	$response['message'] = "Authentication Failed";
	print(json_encode($response));
	debug("response:".print_r($response, true)."\n");
	exit;
}
/* Must have a password set */
if(!is_array($fair) || $fair['password'] == '') {
 	$response['error'] = 1;
	$response['message'] = "Authentication Failed2";
	print(json_encode($response));
	debug("response:".print_r($response, true)."\n");
	exit;
}

/* Process a check token before checking a token back, we don't want to bounce
 * back and forth checking tokens, but a check_token is the only command we will
 * process without checking a token */
if(array_key_exists('check_token', $data)) {
	remote_handle_check_token($mysqli, $fair, $data, $response);
	print(json_encode($response));
	debug("check token for fair:".print_r($fair, true)."\n");
	debug("response:".print_r($response, true)."\n");
	exit();
}

/* Check the token in the command by communicating back with the fair URL we have on record */
if(remote_check_token($mysqli, $fair, $data['token']) == false) {
 	$response['error'] = 1;
	$response['message'] = "Authentication Failed4";
	print(json_encode($response));
	debug("response:".print_r($response, true)."\n");
	exit();
}

if(array_key_exists('push_award', $data)) remote_handle_push_award($mysqli, $fair, $data, $response);
if(array_key_exists('get_award', $data)) remote_handle_get_award($mysqli, $fair, $data, $response);
if(array_key_exists('getstats', $data)) handle_getstats($u,$fair, $data, $response);
if(array_key_exists('stats', $data)) handle_stats($u,$fair, $data, $response);
if(array_key_exists('getawards', $data)) handle_getawards($mysqli, $u,$fair,$data, $response);
if(array_key_exists('awards_upload', $data)) handle_awards_upload($mysqli, $u,$fair,$data, $response);
if(array_key_exists('get_categories', $data)) handle_get_categories($mysqli, $u,$fair,$data, $response);
if(array_key_exists('get_divisions', $data)) handle_get_divisions($mysqli, $u,$fair,$data, $response);
if(array_key_exists('award_additional_materials', $data)) handle_award_additional_materials($u,$fair,$data, $response);

$response['hi'] = 'hi';
print(json_encode($response));
debug("response:".print_r($response, true)."\n");
fclose($fp);

?>


