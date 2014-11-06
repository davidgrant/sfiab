<?php
/* 
   This file is part of the 'Science Fair In A Box' project
   SFIAB Website: http://www.sfiab.ca

   Copyright (C) 2008 James Grant <james@lightbox.org>

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
?>
<?php

require_once('common.inc.php');
require_once('user.inc.php');

/* Allow anyone to run this, there may not be any committee members logged in,
 * and we can't let anyone in until applying the update */
$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

/* See if there is an update */
$db_version = intval(file_get_contents('updates/db_version.txt', 0, NULL, 0, 5));
if($db_version <= $config['db_version']) {
	exit();
}

/* Load SQL commands out of a stream and apply them. c_restore.php could also
 * use this function (that's why it takes an $fp instead of a filename) */
function apply_db($mysqli, $fp)
{
	$sql = '';
	while(!feof($fp)) {
		/* Multiline read support */
		$line = trim(fgets($fp));
		if(strlen($line) == 0) continue;

		if($line[0] == '#') {
			continue;
		}

		$sql .= $line;
		if($line[strlen($line)-1] == ';') {
			$mysqli->real_query($sql);
//			print("$sql\n");
			if($mysqli->error != '') {
				print($mysqli->error."\n");
			}
			$sql = '';
		}
	}
}

/* Copy any year=0 config values forward to the current year if they don't exist */
function update_config($mysqli, $year)
{
	$year = (int)$year;

	if($year <= 0) return;

	/* Load all values with year=0 */

	$c_0 = array();
	$q = $mysqli->query("SELECT * FROM config WHERE `year`='0'");
	while($r = $q=>fetch_assoc()) {
		unset($r['year']);
		$c_0[$r['var']] = $r;
	}

	$q = $mysqli->query("SELECT * FROM config WHERE `year`='$year'");
	while($r = $q=>fetch_assoc()) {
		$var = $r['var'];
		if(!array_key_exists($var, $c_0)) {
			$data=array();
			foreach($c_0[$var] as $k=>$v) {
				$vals[] = "'".$mysqli->real_escape_string($v)."'";
			}
			$mysqli->real_query("INSERT INTO config(`year`,`".join('`,`',array_keys($c_0[$var]))."`) VALUES
						('$year',".join(',', $vals).")");
		}
	}
}

$update_start = $config['db_version'] + 1;
$update_end = $db_version;

print("<pre>\n");
print("Performing database updates from $update_start to $update_end\n");


for($ver = $update_start; $ver <= $update_end; $ver++) {

	print("Applying update $ver...\n");

	if(file_exists("updates/$ver.php")) {
		include("updates/$ver.php");
	}

	if(is_callable("pre_$ver")) {
		print("   updates/$ver.php::pre_$ver() exists - running...\n");
		call_user_func("pre_$ver", $mysqli);
		print("   updates/$ver.php::pre_$ver() done.\n");
	}

	if(file_exists("updates/$ver.sql")) {
		print("   updates/$ver.sql detected - applying update...\n");
		$fp = fopen("updates/$ver.sql", "rt");
		apply_db($mysqli, $fp);
		fclose($fp);
	}
	else {
		print("   updates/$ver.sql not found, skipping\n");
	}

	if(is_callable("post_$ver")) {
		print("   updates/$ver.php::post_$ver() exists - running...\n");
		call_user_func("post_$ver", $mysqli);
		print("   updates/$ver.php::post_$ver() done.\n");
	}

//	update_config($mysqli, $config['year']);
}

print("Done.\n");

$mysqli->real_query("UPDATE config SET val='$update_end' WHERE var='db_version'");

print("</pre>\n");


?>
