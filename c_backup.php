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
require_once('form.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('filter.inc.php');
require_once('committee/email_lists.inc.php');
require_once('email.inc.php');
require_once('db.inc.php');

$mysqli = sfiab_init('committee');

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

switch($action) {
case 'backup':

	$ts=time();
	$dump="#SFIAB SQL BACKUP: ".date("r",$ts)."\n";
	$dump.="#SFIAB VERSION: ".$config['version']."\n";
	$dump.="#SFIAB DB VERSION: ".$config['db_version']."\n";
	$dump.="#SFIAB FAIR NAME: ".$config['fair_name']."\n";
	$dump.="#-------------------------------------------------\n";

	/* Get all the tables in the database */
	$tableq=$mysqli->query("SHOW TABLES FROM `$dbdatabase`");
	while($tr=$tableq->fetch_row()) {
		/* For each table... */
		$table=$tr[0];
		$dump.="#TABLE: $table\n";

		# Drop and create the table
		$dump.="DROP TABLE IF EXISTS `$table`;\n";

		$createq = $mysqli->query("SHOW CREATE TABLE `$table`");
		$r = $createq->fetch_assoc();
		$dump .= $r['Create Table'].";\n";

		/* Get all the columns */
		$columnq=$mysqli->query("SHOW COLUMNS FROM `$table`");
		unset($fields);
		$fields=array();
		while($cr=$columnq->fetch_assoc()) {
			$fields[]=$cr['Field'];
		}
		$insert_str="INSERT INTO `$table` (`".join('`,`', $fields)."`) VALUES \n";

		/* Create an INSERT command for all the data */
		$dataq=$mysqli->query("SELECT * FROM `$table` ORDER BY `{$fields[0]}`");
		$cnt = 0;
		$cnt_total = $dataq->num_rows;
		while($data=$dataq->fetch_assoc() ) {

			if($cnt % 10 == 0) {
				$dump .= $insert_str;
			}

			$value_str = '   (';
			foreach($fields AS $field) {
				if(is_null($data[$field]))
					$value_str.="NULL,";
				else
				{
					$escaped=str_replace("\\","\\\\",$data[$field]);
					$escaped=str_replace("'","''",$escaped);
					$escaped=str_replace("\n","\\n",$escaped);
					$escaped=str_replace("\r","\\r",$escaped);
					$value_str.="'".$escaped."',";
				}
			}
			$value_str=substr($value_str,0,-1);
			$value_str.=")";

			$dump.=$value_str;

			/* Should we append a ; for the last entry or before a new header, or a , if there are more values */
			$cnt += 1;
			if($cnt == $cnt_total || $cnt % 10 == 0) {
				$dump .= ";\n";
			} else {
				/* There are more */
				$dump .= ",\n";
			}
		}
	}
	/* gzip makes it about 10x smaller */
	$gzdump = gzencode($dump);
	header("Content-type: application/x-gzip");
	header("Content-Disposition: attachment; filename=sfiab_backup_".date("Y-m-d-H-i-s",$ts).".sql.gz");
	header("Content-Length: ".strlen($gzdump));
	//Make IE with SSL work
	header("Pragma: public");
	print($gzdump);
	exit();

case 'restore':

	print("<pre>Begin Restore.\n");
	/* File should be in $_FILES['restore'], check the $_FILES array: */
	if ( !isset($_FILES['restore']['error']) || is_array($_FILES['restore']['error'])) {
		exit();
	}
	print("Received a file.\n");

	/* Make sure the file uploaded successfully */
	switch($_FILES['restore']['error']) {
        case UPLOAD_ERR_OK:
		break;
        case UPLOAD_ERR_NO_FILE:
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
        default:
		form_ajax_response(array('status'=>1, 'error'=>'File Upload Failed'.$_FILES['restore']['error']));
		exit();
	}
	print("File OK.\n");

	$fp = gzopen($_FILES['restore']['tmp_name'], "rb");

	/* Get the first 4 lines of the file to check */
	$hdr = array();
	for($x=0;$x<4;$x++) {
		$hdr[$x] = trim(fgets($fp));
	}

	if(substr($hdr[0], 0, 17) != "#SFIAB SQL BACKUP") {
		print("\nERROR: File is NOT an SFIAB Backup. Stop.\n");
//		form_ajax_response(array('status'=>1, 'error'=>'File is not an SFIAB backup'));
		exit();
	}
	print("File is an SFIAB Backup.\n");

	/* The backup needs to go back into a database of the same version, or things will get broken */
	$m = preg_match("/^#SFIAB DB VERSION: ([0-9]+)/", $hdr[2], $matches);
	if($m != 1) {
		print("\nERROR: File database version is corrupt. Stop.\n");
//		form_ajax_response(array('status'=>1, 'error'=>'File database version is not properly formatted'));
		exit();
	}
	print("File is DB Version {$matches[1]}\n");

	print("Starting multiread restore...\n");
	db_apply_update($mysqli, $fp);
	gzclose($fp);
	unlink($_FILES['restore']['tmp_name']);

	print("Restore Complete.\n\nYou may have to login again because the database was just completely overriden.  You can try the link below though, it might work.\n");
	print("</pre>");
?>
	<a href="c_backup.php">Go Back</a>
<?php
//	form_ajax_response(array('status'=>0, 'info'=>'Database Restored'));
	exit();
}


$page_id = "c_backup";
$help = "<p>Backup and Restore";
sfiab_page_begin($u, "Backup and Restore", $page_id, $help);
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 
	<h3>Backup Database</h3>
	<p>This downloads a file containing your entire SFIAB database.  Save it somewhere safe, it contains student contact information, judging results, private committee information, everything.
<?php
	$form_id = $page_id.'_backup_form';

	form_begin($form_id, 'c_backup.php', false, false);
	form_button($form_id, 'backup','Create Database Backup', 'g', '');
	form_end($form_id);
?>
	<br/><hr />

	<h3>Restore Database</h3>
<?php
	$form_id = $page_id.'_restore_form';
	sfiab_error("WARNING: Restoring a backup will completely DESTROY all data currently in the database and replace it with what is in the backup file.  This operation can't be undone.");
?>
	<p>Consider doing a database backup before restoring a different database file so you can go back the current state.
	<p>Choose a database backup file to upload:

	<form method="post" action="c_backup.php" data-ajax="false" enctype="multipart/form-data">
	<input type="hidden" name="action" value="restore">
	<input type="file" name="restore">
	<input type="submit" value="Restore Database">
	</form>

<?php
//	form_begin($form_id, 'c_backup.php', false, false, true);
//	form_file($form_id, 'file', NULL);
//	form_submit($form_id, 'restore', 'Upload Restore File', 'Database Restored');

//	form_end($form_id);
	?>
</div></div>

<?php
sfiab_page_end();
?>
