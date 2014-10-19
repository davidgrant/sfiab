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

$encrypted_command = urldecode($_POST['cmd']);
$password = urldecode($_POST['password']);

$fair = fair_load_by_hash($mysqli, $password);

debug("Incoming command matched password for fair: {$fair['name']}\n");

/* Decrypt the command with our private key, then their pulblic key */
if(!openssl_private_decrypt($encrypted_command, $de1, $config['private_key'])) exit();
if(!openssl_public_decrypt($de1, $decrypted_cmd, $fair['public_key'])) exit();
$data = json_decode($decrypted_cmd, true);

debug("Decoded Command:".print_r($data, true)."\n");

$response = remote_handle_cmd($fair, $data);

/* Encrypt the response with our privkey, then their pubkey */
if(!openssl_private_encrypt($json_encode($response), $enc1, $config['private_key'])) exit();
if(!openssl_public_encrypt($enc1, $encrypted_response, $fair['public_key'])) exit();

/* Send it back */
print($encrypted_response);
exit();


function remote_handle_cmd(&$fair, &$cmd) 
{
	$response = array();
	/* Fair must exist */
	if($fair === NULL) {
		$response['error'] = 1;
		$response['message'] = "Authentication Failed";
		debug("response:".print_r($response, true)."\n");
		return $response;
	}
	/* Must have a password set */
	if(!is_array($fair) || $fair['password'] == '') {
		$response['error'] = 1;
		$response['message'] = "Authentication Failed2";
		debug("response:".print_r($response, true)."\n");
		return $response;
	}

	/* Working */
	if(array_key_exists('push_award', $data)) remote_handle_push_award($mysqli, $fair, $data, $response);
	if(array_key_exists('push_winner', $data)) remote_handle_push_winner($mysqli, $fair, $data, $response);

	/* Should work */
	if(array_key_exists('get_award', $data)) remote_handle_get_award($mysqli, $fair, $data, $response);

	/* Unknown */
	if(array_key_exists('getstats', $data)) handle_getstats($u,$fair, $data, $response);
	if(array_key_exists('stats', $data)) handle_stats($u,$fair, $data, $response);
	if(array_key_exists('getawards', $data)) handle_getawards($mysqli, $u,$fair,$data, $response);

	$response['hi'] = 'hi';
	debug("response:".print_r($response, true)."\n");
	return $response;
}


?>
