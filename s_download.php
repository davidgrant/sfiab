<?php

require_once('common.inc.php');
require_once('user.inc.php');
require_once('project.inc.php');
require_once('incomplete.inc.php');
require_once('form.inc.php');
require_once('tcpdf.inc.php');

$mysqli = sfiab_init(array('student'));

$page_id = 's_payment';

$u = user_load($mysqli);

sfiab_check_abort_in_preregistration($u, $page_id);

$p = project_load($mysqli, $u['s_pid']);
$closed = sfiab_registration_is_closed($u);

$help = "
<p>Various downloads and photos are on this page
";


sfiab_page_begin($u, "Photos and Downloads", $page_id, $help);

?>

<div data-role="page" id="<?=$page_id?>"><div data-role="main" class="sfiab_page" > 

<?php
sfiab_page_messages();
?>

<h3>Photos</h3>

<?php 
/* Are tehre any photos? */
$possible_filenames = array("{$p['number']}", 
			    "{$p['floor_number']}",
			    sprintf("%03d", $p['floor_number']));
$files = array();
foreach($possible_filenames as $f) {
	if(preg_match('/[^A-Za-z0-9 _\-]/', $f)) {
		/* STring contains non alphanumeric+space characters */
		continue;
	}

	$fn = "files/{$config['year']}/$f.jpg";
	debug("Trying: $fn");
	if(file_exists("$fn")) {
		$files[] = "$f.jpg";
	}

	/* Alos try adding _number to the end of it */
	for($x = 1; $x < 50; $x++) {
		$fn = "files/{$config['year']}/{$f}_{$x}.jpg";
		debug("Trying: $fn");
		if(file_exists("$fn")) {
			$files[] = "{$f}_{$x}.jpg";
		} else {
			break;
		}
	}
}


if(count($files) == 0) { ?>
	<p>No Photos are available.
<?php 
} else { ?>
	<p>Click on a photo to download the original<br/>
<?php	foreach($files as $f) { ?>
		<a href="file.php?f=<?=$f?>"  data-ajax="false"><img src="file.php?f=<?=$f?>" width="512"/></a>
<?php	}
}



sfiab_page_end();
?>
