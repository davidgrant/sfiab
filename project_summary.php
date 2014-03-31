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
require_once('user.inc.php');
require_once('project.inc.php');

$mysqli = sfiab_db_connect();
sfiab_load_config($mysqli);


$pn = $mysqli->real_escape_string(stripslashes($_GET['pn']));
$q=$mysqli->query("SELECT * FROM projects WHERE number='$pn' AND year='{$config['year']}'");
if($q->num_rows != 1) {
	print("not found");
	exit();
}

$p = project_load($mysqli, -1, $q->fetch_assoc());
$students = project_load_students($mysqli, $p);

$s_names = "";
$s_schools = "";

$school_ids = array();

foreach($students as &$s) {
	if($s_names != '') $s_names .= ', ';
	$s_names .= $s['name'];


	if(!in_array($s['schools_id'], $school_ids)) {
		$school_ids[] = $s['schools_id'];

		if($s_schools != '') $s_schools .= ', ';
		$q2 = $mysqli->query("SELECT school from schools WHERE id='{$s['schools_id']}' and year='{$config['year']}'");
	
		$r2 = $q2->fetch_assoc();
		$s_schools .= $r2['school'];
	}
}

if(file_exists("data/logo-100.gif"))
	$logo = "<img align=\"left\" height=\"50\" src=\"data/logo-100.gif\">";
else 
	$logo = "";


?>
<html><head>
<title>Project Summary for <?=$p['number']?></title>
</head>
<body bgcolor="#FFFFFF">
<P> 
<center>
<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0" col="3">
<TR>
	<td><?=$logo?></td>
	<td><center><p><strong><font size="3" face="Verdana, Arial, Helvetica, sans-serif" color="#6699CC">
		<?=$p['title']?><br />
		<?=$s_names?><br />
		<?=$s_schools?><br />
		Floor Location : <?=$p['number']?></font></strong></center></td>
	<td></td>
</tr>
</table>
</center>
<font size="2" face="Verdana, Arial, Helvetica, sans-serif">
<?=nl2br(htmlentities(utf8_decode($p['summary'])))?>
</font>

</body></html>
