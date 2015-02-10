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
require_once('filter.inc.php');
require_once('db.inc.php');

$mysqli = sfiab_init('committee');

$action = '';
if(array_key_exists('action', $_POST)) {
	$action = $_POST['action'];
}

switch($action) {

case 'addimage':

	/* File should be in $_FILES['restore'], check the $_FILES array: */
	if ( !isset($_FILES['image']['error']) || is_array($_FILES['image']['error'])) {
		exit();
	}
//	print("Received a file.\n");

	/* Make sure the file uploaded successfully */
	switch($_FILES['image']['error']) {
        case UPLOAD_ERR_OK:
		break;
        case UPLOAD_ERR_NO_FILE:
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
        default:
		form_ajax_response(array('status'=>1, 'error'=>'File Upload Failed'));
		exit();
	}

	if ($_FILES['image']['size'] > 5000000) {
		print("File not OK1");
		exit();
	}

	$finfo = finfo_open(FILEINFO_MIME_TYPE);
	$mimetype_ext = array(	'image/jpeg' => 'jpg', 
				'image/png' => 'png',
				'image/gif' => 'gif' );

        $mimetype = finfo_file($finfo, $_FILES['image']['tmp_name']);
	if(!array_key_exists($mimetype, $mimetype_ext)) {
		print("File not OK2");
		exit();
	}
	$ext = $mimetype_ext[$mimetype];

	/* Final check, the image size */
	$image_size = getimagesize($_FILES['image']['tmp_name']);
	if(!array($image_size)) {
		print("File not OK3");
		exit();
	}

	$w = $image_size[0];
	$h = $image_size[1];
	$type = $image_size[2];

	if($w < 2 || $w > 10000 || $h < 2 || $h > 10000) {
		print("File not OK4");
		exit();
	}

	switch($type) {
	case IMAGETYPE_GIF:
	case IMAGETYPE_JPEG:
	case IMAGETYPE_PNG:
	case IMAGETYPE_JPEG2000:
		break;
	default:
		print("File not OK5");
		exit();
	}


	$orig_logo_filename = "files/logo-original.$ext";
	move_uploaded_file($_FILES['image']['tmp_name'], $orig_logo_filename);

	/* Now turn it into a jpg of various sizes */
	$image_data = file_get_contents($orig_logo_filename);
	$image = imagecreatefromstring($image_data);

	if($w != imagesx($image) || $h != imagesy($image) ) {
		print("File not OK");
		exit();
	}

	$ratio = $h / $w;
	foreach(array($w, 500, 100) as $s) {
		$i = imagecreate($w, round($ratio * $s));
		imagecolorallocate($i,255,255,255);
		imagecolortransparent($i,0);
		imagecopyresized($i, $image, 0, 0, 0, 0, $s, round($ratio * $s), $w, $h);
		$f = ($s == $w) ? "logo.jpg" : "logo-$s.jpg";
		imagejpeg($i, "files/$f",95);
		imagedestroy($i);
	}

//	print("Images Created.");
//	print("</pre>");
	break;
}

$page_id = "c_fair_logo";
$help = "<p>Fair Logo - upload any PNG, GIF, or JPG.";
sfiab_page_begin("Fair Logo", $page_id, $help);
?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 
	<h3>Fair Logo</h3>
	<p>Upload any PNG, GIF, or JPG.  It works better if it's square-ish.
	The fair logo is added to all generated reports and the student
	signature page. <br/>



<?php

	if(!file_exists('files/logo.jpg')) { ?>
		<p>No Fair Image detected, this means reports will be broken.
<?php	} else { ?>
		<img width="200" src="file.php?f=logo">
<?php	} 

	$form_id = $page_id.'_logo_form';

?>
	<form enctype="multipart/form-data" method="post" action="c_config_logo.php" data-ajax="false" >
	<input type="hidden" name="action" value="addimage" />
	<input type="file" name="image" />
	<input type="submit" value="Upload Logo" />
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
