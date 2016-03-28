<?php
/* 
   This file is part of the 'Science Fair In A Box' project
   SFIAB Website: http://www.sfiab.ca

   Copyright (C) 2010 David Grant <dave@lightbox.org>

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
require_once('project.inc.php');


if(array_key_exists('SERVER_ADDR', $_SERVER)) {
	echo "This script must be run from the command line";
	exit;
}

$action = '';
switch($argv[1]) {
case '--images':
	$action = 'images';
	break;
case '--pn':
	$action = 'pn';
	break;
}

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);

//function TRACE() { }
//function TRACE_R() { }
function TRACE($str) { print($str); }
function TRACE_R($array) { print_r($array); }


function point_rotate($x, $y, $deg)
{
	/* Use - orienttaiotn because rotation is always done from dest->src */
	$r = deg2rad(-$deg);
	return array(round($x*cos($r) - $y*sin($r), 6), round($x*sin($r) + $y*cos($r), 6));
}

function point_translate($x, $y, $dx, $dy)
{
	return array ($x+$dx, $y+$dy);
}

function is_point_in_object($x, $y, $o)
{
	/* Translate the point to the object origin */
	list($x, $y) = point_translate($x, $y, -$o['x'], -$o['y']);
	/* Rotate the point to the object's frame of reference*/
	list($x, $y) = point_rotate($x, $y, -$o['orientation']);
	/* Is it within the object now ? */
	if(abs($x) <= $o['w2'] && abs($y) <= $o['h2'])
		return true;
	return false;
}

function queue_new()
{
	return array('head' => NULL, 'tail' => NULL);
}

TRACE("<pre>\n");

/* Load exhibit halls */
$exhibithall = array();
$q = $mysqli->query("SELECT * FROM exhibithall WHERE type='exhibithall'");
TRACE("Loading exhibit halls...\n");
while(($r = $q->fetch_assoc())) {
	$r['challenges'] = explode(',', $r['challenges']); //unserialize($r['challenges']);
	$r['cats'] = explode(',', $r['cats']); //unserialize($r['cats']);
	$exhibithall[$r['id']] = $r;
	TRACE("   - {$r['name']}\n");
}

/* Load objects */
$objects = array();
$q = $mysqli->query("SELECT * FROM exhibithall WHERE type='wall' OR type='project'");
TRACE("Loading objects...\n");
while(($r = $q->fetch_assoc())) {
	$r['challenges'] = unserialize($r['challenges']);
	$r['cats'] = unserialize($r['cats']);
	$r['pid'] = 0;
	$objects[$r['floornumber']] = $r;
}
TRACE(count($objects)." objects loaded.\n");

/* Compute stuff */
foreach($objects as $oid=>$o) {
	$objects[$oid]['w2'] = $o['w']/2;
	$objects[$oid]['h2'] = $o['h']/2;
}

/* The grid size is the smallest object dimension */
$grid_size = 100;
foreach($objects as $oid=>$o) {
	if($grid_size > $o['w']) $grid_size = $o['w'];
	if($grid_size > $o['h']) $grid_size = $o['h'];
}
$grid_size /= 2;
TRACE("Grid size: {$grid_size}m\n");

foreach($exhibithall as $eid=>&$eh) {
	$eh['grid_w'] = (int)($eh['w'] / $grid_size) + 1;
	$eh['grid_h'] = (int)($eh['h'] / $grid_size) + 1;
}



//print_r($exhibithall);

//print_r($objects);

$challenges = challenges_load($mysqli);
$cats = categories_load($mysqli);

$projects = projects_load_all($mysqli);
TRACE(count($projects)." projects loaded.\n");

foreach($projects as &$p) {
	$objects[$p['floor_number']]['pid'] = $p['pid'];

	project_load_students($mysqli, $p);
}


switch($action) {
case 'images':
	exhibithall_images();
	exit;
}

function exhibithall_images()
{
	global $exhibithall, $objects, $projects, $challenges;

	foreach($exhibithall as &$i_eh) {


		$i = imagecreatetruecolor($i_eh['w']*100, $i_eh['h']*100);
		$c_grey = imagecolorallocate($i, 128, 128, 128);
		$c_white = imagecolorallocate($i, 255, 255, 255);
		$c_black = imagecolorallocate($i, 0, 0, 0);

		// Fill the background with the color selected above.
		imagefill($i, 0, 0, $c_white);
		imagerectangle($i, 0, 0, $i_eh['w']*100 - 1, $i_eh['h']*100 - 1, $c_black);

		for($ix=0;$ix<=$i_eh['grid_w'];$ix++) {
			for($iy=0;$iy<=$i_eh['grid_h'];$iy++) {
//				$l = $i_eh[$ix][$iy];
//				if(count($l['ids']) > 0) {
//					imageellipse($i, $l['x']*100, $l['y']*100, 1, 1, $c_black);
//				} else {
					imageellipse($i, $ix*100, $iy*100, 1, 1, $c_grey);
//				}
			}
		}
		foreach($objects as $oid=>$o) {
			if($o['exhibithall_id'] != $i_eh['id']) continue;
			

			list($x1,$y1) = point_rotate(-$o['w2'], -$o['h2'], $o['orientation']);
			list($x2,$y2) = point_rotate($o['w2'], $o['h2'], $o['orientation']);
			imagerectangle($i, ($o['x']+$x1)*100, ($o['y']+$y1)*100, ($o['x']+$x2)*100, ($o['y']+$y2)*100, $c_black);

			$pid = $o['pid'];
			if($pid <= 0) continue;

			$p = $projects[$o['pid']];
			imagestring($i, 4, $o['x']*100 - 30, $o['y']*100 - 35, "{$o['floornumber']} ({$p['pid']})", $c_black);
			imagestring($i, 4, $o['x']*100 - 30, $o['y']*100 - 20, "gr:{$p['students'][0]['grade']}  ", $c_black);
			$d = $challenges[$p['challenge_id']]['shortform'];
			imagestring($i, 4, $o['x']*100 - 30, $o['y']*100 - 5, "d:$d ({$p['challenge_id']})", $c_black);
			imagestring($i, 4, $o['x']*100 - 30, $o['y']*100 + 10, "s:{$p['students'][0]['schools_id']}", $c_black);
			imagestring($i, 4, $o['x']*100 - 30, $o['y']*100 + 25, "e:{$p['req_electricity']}", $c_black);

		
		}


		imagepng($i, "./eh-{$i_eh['id']}.png");

	}
}



?>
